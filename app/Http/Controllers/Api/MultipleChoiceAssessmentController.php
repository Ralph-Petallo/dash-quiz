<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MultipleChoiceAssessmentController extends Controller
{
    private function logActivity(string $action, string $description): void
    {
        $admin = auth('sanctum')->user();

        if (! $admin) {
            return;
        }

        AdminLog::create([
            'admin_id' => $admin->id,
            'action_type' => $action,
            'description' => $description,
        ]);
    }

    /**
     * Display a multiple-choice assessment.
     */
    public function show(int $id): JsonResponse
    {
        $quiz = Quiz::with([
            'questions.options',
            'assessments',
        ])->findOrFail($id);

        $assessment = $quiz->assessments
            ->firstWhere('type', 'multiple_choice');

        if (! $assessment) {
            return response()->json([
                'status' => 'error',
                'message' => 'No multiple-choice assessment is associated with this quiz.',
            ], 404);
        }

        $questions = $quiz->questions
            ->filter(fn ($question) => $question->options->isNotEmpty())
            ->shuffle()
            ->take(20)
            ->values();

        $optionLabels = ['A', 'B', 'C', 'D'];

        return response()->json([
            'status' => 'success',
            'quiz' => [
                'id' => (int) $quiz->id,
                'assessment_id' => (int) $assessment->id,
                'coc_id' => (int) $assessment->coc_id,
                'coc_number' => $quiz->coc_number,
                'title' => $assessment->title ?? $quiz->title,
                'description' => $assessment->description ?? $quiz->description,
                'quiz_type' => $assessment->type,
                'category' => $assessment->category ?? $quiz->category,
                'total_questions' => $questions->count(),
                'questions' => $questions->map(
                    function ($question, $index) use ($optionLabels) {
                        $options = $question->options
                            ->shuffle()
                            ->values();

                        return [
                            'id' => (int) $question->id,
                            'text' => $question->question_text,
                            'question_number' => $index + 1,
                            'options' => $options->map(
                                function ($option, $optionIndex) use ($optionLabels) {
                                    return [
                                        'id' => (int) $option->id,
                                        'text' => $option->option_text,
                                        'label' => $optionLabels[$optionIndex]
                                            ?? chr(65 + $optionIndex),
                                    ];
                                }
                            )->values(),
                        ];
                    }
                )->values(),
            ],
        ]);
    }

    private function deleteStoredImage(?string $path): void
    {
        if (
            $path &&
            ! filter_var($path, FILTER_VALIDATE_URL) &&
            Storage::disk('public')->exists($path)
        ) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Submit answers and save a new assessment result.
     */
    public function submitAnswer(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'answers_array' => ['required', 'array', 'min:1'],
            'answers_array.*.question_id' => [
                'required',
                'integer',
                'distinct',
                'exists:questions,id',
            ],
            'answers_array.*.answer_id' => [
                'required',
                'integer',
                'exists:question_options,id',
            ],
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'elapsed_time' => ['required', 'integer', 'min:0'],
        ]);

        $quiz = Quiz::with([
            'questions.options',
            'assessments',
        ])->findOrFail($validated['quiz_id']);

        $assessment = $quiz->assessments
            ->firstWhere('type', 'multiple_choice');

        if (! $assessment) {
            return response()->json([
                'status' => 'error',
                'message' => 'No multiple-choice assessment is associated with this quiz.',
            ], 422);
        }

        $answers = collect($validated['answers_array']);
        $questionsById = $quiz->questions->keyBy('id');

        // Verify each question belongs to this quiz, and each option belongs
        // to the question it was submitted with.
        foreach ($answers as $answer) {
            $question = $questionsById->get((int) $answer['question_id']);

            if (! $question) {
                throw ValidationException::withMessages([
                    'answers_array' => [
                        'A submitted question does not belong to this quiz.',
                    ],
                ]);
            }

            $optionBelongsToQuestion = $question->options->contains(
                fn ($option) => (int) $option->id === (int) $answer['answer_id']
            );

            if (! $optionBelongsToQuestion) {
                throw ValidationException::withMessages([
                    'answers_array' => [
                        'A selected option does not belong to its question.',
                    ],
                ]);
            }
        }

        $expectedQuestionCount = min(
            20,
            $quiz->questions
                ->filter(fn ($question) => $question->options->isNotEmpty())
                ->count()
        );

        if ($answers->count() !== $expectedQuestionCount) {
            throw ValidationException::withMessages([
                'answers_array' => [
                    'Submit one answer for every question in this assessment.',
                ],
            ]);
        }

        $totalQuestions = $expectedQuestionCount;

        $result = DB::transaction(function () use ($user, $quiz, $assessment, $validated, $answers, $questionsById, $totalQuestions) {
            $matchingRecords = QuizRecord::query()
                ->where('user_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('quiz_type', $assessment->type);

            // Lock existing matching records to reduce the chance of two
            // simultaneous submissions receiving the same attempt number.
            $previousAttemptCount = (clone $matchingRecords)
                ->lockForUpdate()
                ->count();

            $attemptNumber = $previousAttemptCount + 1;
            $passingScore = (float) ($quiz->passing_score ?? 75);

            // Every submission creates a new QuizRecord.
            $record = QuizRecord::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'quiz_type' => $assessment->type,
                'category' => $assessment->category ?? $quiz->category,
                'score' => 0,
                'correct_answers' => 0,
                'total_questions' => $totalQuestions,
                'percentage' => 0,
                'passing_score' => $passingScore,
                'status' => 'In Progress',
                'attempt_count' => $attemptNumber,
                'elapsed_time' => $validated['elapsed_time'],
            ]);

            $correctCount = 0;

            foreach ($answers as $answer) {
                $question = $questionsById->get((int) $answer['question_id']);

                $selectedOption = $question->options->first(
                    fn ($option) => (int) $option->id === (int) $answer['answer_id']
                );

                $correctOption = $question->options->first(
                    fn ($option) => (bool) $option->is_correct
                );

                $isCorrect = $correctOption !== null
                    && (int) $selectedOption->id === (int) $correctOption->id;

                if ($isCorrect) {
                    $correctCount++;
                }

                QuizAttempt::create([
                    'quiz_record_id' => $record->id,
                    'question_id' => $question->id,
                    'selected_option_id' => $selectedOption->id,
                    'is_correct' => $isCorrect,
                ]);
            }

            $percentage = $totalQuestions > 0
                ? round(($correctCount / $totalQuestions) * 100, 2)
                : 0;

            $record->update([
                'score' => $correctCount,
                'correct_answers' => $correctCount,
                'total_questions' => $totalQuestions,
                'percentage' => $percentage,
                'status' => $percentage >= $passingScore ? 'Passed' : 'Failed',
                'completed_at' => now(),
            ]);

            return $record->fresh();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Quiz submitted successfully.',
            'record_id' => (int) $result->id,
            'quiz_id' => (int) $result->quiz_id,
            'assessment_id' => (int) $assessment->id,
            'quiz_type' => $result->quiz_type,
            'attempt_count' => (int) $result->attempt_count,
            'score' => (int) $result->score,
            'correct_answers' => (int) $result->correct_answers,
            'total_questions' => (int) $result->total_questions,
            'percentage' => (float) $result->percentage,
            'passing_score' => (float) $result->passing_score,
            'status_result' => $result->status,
            'elapsed_time' => (int) $result->elapsed_time,
        ], 201);
    }

    private function syncQuestionOptions(
        Question $question,
        array $optionTexts,
        int $correctIndex
    ): void {
        $existingOptions = DB::table('question_options')
            ->where('question_id', $question->id)
            ->orderBy('id')
            ->get();

        DB::table('answers')
            ->where('question_id', $question->id)
            ->delete();

        $retainedOptionIds = [];

        foreach ($optionTexts as $index => $optionText) {
            $isCorrect = $index === $correctIndex;

            if (isset($existingOptions[$index])) {
                $optionId = $existingOptions[$index]->id;

                DB::table('question_options')
                    ->where('id', $optionId)
                    ->update([
                        'option_text' => $optionText,
                        'is_correct' => $isCorrect,
                    ]);
            } else {
                $optionId = DB::table('question_options')->insertGetId([
                    'question_id' => $question->id,
                    'option_text' => $optionText,
                    'is_correct' => $isCorrect,
                ]);
            }

            $retainedOptionIds[] = $optionId;

            DB::table('answers')->insert([
                'question_id' => $question->id,
                'answer_text' => $optionText,
                'is_correct' => $isCorrect,
            ]);
        }

        $optionsQuery = DB::table('question_options')
            ->where('question_id', $question->id);

        if ($retainedOptionIds) {
            $optionsQuery->whereNotIn('id', $retainedOptionIds);
        }

        $optionsQuery->delete();
    }

    public function edit(int $id)
    {
        $quiz = Quiz::query()->find($id);

        if (! $quiz) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quiz not found.',
            ], 404);
        }

        $questions = Question::query()
            ->where('quiz_id', $quiz->id)
            ->where(function ($query) {
                $query->whereNull('question_type')
                    ->orWhere('question_type', '!=', 'drag_drop');
            })
            ->with([
                'options' => function ($query) {
                    $query->select(
                        'id',
                        'question_id',
                        'option_text',
                        'is_correct'
                    );
                },
            ])
            ->orderBy('id')
            ->get()
            ->map(function (Question $question) {
                $question->image_url = $question->image_path
                    ? Storage::disk('public')->url($question->image_path)
                    : null;

                $question->correct_option_id = $question->options
                    ->firstWhere('is_correct', true)?->id;

                return $question;
            });

        return response()->json([
            'status' => 'success',
            'quiz' => $quiz,
            'questions' => $questions,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['nullable', 'string', 'in:easy, medium, hard'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.text' => ['required', 'string'],
            'questions.*.options' => ['required', 'array', 'size:4'],
            'questions.*.options.*' => ['required', 'string', 'max:1000'],
            'questions.*.correct_option' => [
                'required',
                'integer',
                'between:0,3',
            ],
            'questions.*.image' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:5120',
            ],
            'questions.*.remove_image' => ['nullable', 'boolean'],
        ]);

        try {
            $quiz = DB::transaction(function () use ($validated, $request, $id) {
                $quiz = Quiz::query()->findOrFail($id);

                $quiz->update([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'category' => $validated['category'] ?? null,
                    'difficulty' => $validated['difficulty'] ?? null,
                ]);

                $submittedQuestionIds = [];

                foreach ($validated['questions'] as $index => $questionData) {
                    if (! empty($questionData['id'])) {
                        $question = Question::query()
                            ->where('quiz_id', $quiz->id)
                            ->find($questionData['id']);

                        if (! $question) {
                            throw ValidationException::withMessages([
                                "questions.$index.id" => 'The selected question does not belong to this quiz.',
                            ]);
                        }
                    } else {
                        $question = new Question;
                        $question->quiz_id = $quiz->id;
                    }

                    $question->question_text = $questionData['text'];

                    if ($request->hasFile("questions.$index.image")) {
                        $this->deleteStoredImage($question->image_path);

                        $question->image_path = $request
                            ->file("questions.$index.image")
                            ->store('questions', 'public');
                    } elseif (! empty($questionData['remove_image'])) {
                        $this->deleteStoredImage($question->image_path);
                        $question->image_path = null;
                    }

                    $question->save();
                    $submittedQuestionIds[] = $question->id;

                    $this->syncQuestionOptions(
                        $question,
                        $questionData['options'],
                        (int) $questionData['correct_option']
                    );
                }

                $removedQuestions = Question::query()
                    ->where('quiz_id', $quiz->id)
                    ->whereNotIn('id', $submittedQuestionIds)
                    ->get();

                foreach ($removedQuestions as $removedQuestion) {
                    $this->deleteStoredImage($removedQuestion->image_path);
                    $removedQuestion->delete();
                }

                $this->logActivity(
                    'Update',
                    "Competency '{$quiz->title}' (ID: {$quiz->id}) was updated."
                );

                return $quiz;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Quiz updated successfully.',
                'quiz_id' => $quiz->id,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to update the quiz.',
            ], 500);
        }
    }

    public function oldeEdit(int $id)
    {
        $quiz = Quiz::query()->find($id);

        if (! $quiz) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quiz not found.',
            ], 404);
        }

        $questions = Question::query()
            ->where('quiz_id', $quiz->id)
            ->where(function ($query) {
                $query->whereNull('question_type')
                    ->orWhere('question_type', '!=', 'drag_drop');
            })
            ->with([
                'options' => function ($query) {
                    $query->select(
                        'id',
                        'question_id',
                        'option_text',
                        'is_correct'
                    );
                },
            ])
            ->orderBy('id')
            ->get()
            ->map(function (Question $question) {
                $question->correct_option_id = $question->options
                    ->firstWhere('is_correct', true)?->id;

                return $question;
            });

        return response()->json([
            'status' => 'success',
            'quiz' => $quiz,
            'questions' => $questions,
        ]);
    }

    /**
     * Retrieve a submitted result and its answer details.
     */
    public function result(int $id): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $record = QuizRecord::with([
            'quiz.assessments',
            'attempts.question.options',
            'attempts.selectedOption',
        ])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Result not found.',
            ], 404);
        }

        $quiz = $record->quiz;

        $assessment = $quiz?->assessments
            ?->firstWhere('type', $record->quiz_type);

        $answers = $record->attempts
            ->map(function ($attempt) {
                $question = $attempt->question;

                if (! $question) {
                    return null;
                }

                $correctOption = $question->options?->first(
                    fn ($option) => (bool) $option->is_correct
                );

                return [
                    'question_id' => (int) $attempt->question_id,
                    'question_text' => $question->question_text
                        ?? 'Question unavailable',
                    'selected_option_id' => $attempt->selected_option_id,
                    'selected_answer' => $attempt->selectedOption?->option_text
                        ?? 'No answer',
                    'correct_answer' => $correctOption?->option_text ?? 'N/A',
                    'is_correct' => (bool) $attempt->is_correct,
                ];
            })
            ->filter()
            ->values();

        $correctAnswers = (int) ($record->correct_answers ?? 0);
        $totalQuestions = (int) ($record->total_questions ?? 0);

        $percentage = $record->percentage !== null
            ? (float) $record->percentage
            : ($totalQuestions > 0
                ? round(($correctAnswers / $totalQuestions) * 100, 2)
                : 0);

        $passingScore = (float) ($record->passing_score ?? 75);

        $status = $record->status ?: (
            $percentage >= $passingScore ? 'Passed' : 'Failed'
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'record_id' => (int) $record->id,
                'quiz_id' => (int) $record->quiz_id,
                'assessment_id' => $assessment
                    ? (int) $assessment->id
                    : null,
                'coc_id' => $quiz ? (int) $quiz->id : null,
                'coc_number' => $quiz?->coc_number,
                'quiz_title' => $assessment?->title
                    ?? $quiz?->title
                    ?? 'Assessment',
                'quiz_description' => $assessment?->description
                    ?? $quiz?->description,
                'quiz_type' => $assessment?->type
                    ?? $record->quiz_type
                    ?? $quiz?->quiz_type,
                'category' => $record->category
                    ?: $assessment?->category
                    ?: $quiz?->category,
                'score' => (int) ($record->score ?? $correctAnswers),
                'correct_answers' => $correctAnswers,
                'total_questions' => $totalQuestions,
                'percentage' => $percentage,
                'accuracy' => $percentage,
                'passing_score' => $passingScore,
                'status' => $status,

                // This is now saved when the record is created.
                'attempt_count' => (int) ($record->attempt_count ?? 1),

                'elapsed_time' => $record->elapsed_time !== null
                    ? (int) $record->elapsed_time
                    : null,
                'completed_at' => (
                    $record->completed_at ?? $record->created_at
                )?->toISOString(),
                'answers' => $answers->all(),
            ],
        ]);
    }
}
