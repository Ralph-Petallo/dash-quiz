<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Dasher;
use App\Models\ImageIdentificationAttempt;
use App\Models\ImageIdentificationItem;
use App\Models\InputOutput;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssessmentSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_image_identification_requires_a_complete_single_use_attempt(): void
    {
        $user = $this->createUser();
        $quiz = $this->createQuiz();
        $assessment = $this->createAssessment($quiz, 'image_identification');

        foreach ([
            ['Power supply', 'Converts electrical power for computer components.'],
            ['RAM', 'Provides short-term working memory.'],
            ['Motherboard', 'Connects computer components.'],
        ] as [$name, $description]) {
            ImageIdentificationItem::query()->create([
                'assessment_id' => $assessment->id,
                'item_name' => $name,
                'image_path' => strtolower(str_replace(' ', '-', $name)).'.png',
                'description' => $description,
            ]);
        }

        $this->actingAs($user, 'sanctum');

        $show = $this->getJson("/api/image-identification/{$quiz->id}")
            ->assertOk()
            ->assertJsonMissingPath('questions.0.image.id')
            ->assertJsonMissingPath('questions.0.image.item_name')
            ->json();

        $attempt = ImageIdentificationAttempt::query()
            ->where('token', $show['attempt_token'])
            ->firstOrFail();
        $questionSet = $attempt->question_set;
        $questions = $show['questions'];
        $partialAnswers = [[
            'question_id' => $questions[0]['id'],
            'answer_id' => $questions[0]['options'][0]['id'],
        ]];

        $this->postJson('/api/image-identification/answer', [
            'quiz_id' => $quiz->id,
            'attempt_token' => $show['attempt_token'],
            'answers' => $partialAnswers,
        ])->assertUnprocessable();

        $answers = collect($questions)->map(fn (array $question) => [
            'question_id' => $question['id'],
            'answer_id' => array_search(
                $questionSet[$question['id']]['item_id'],
                $questionSet[$question['id']]['options'],
                true
            ),
        ])->all();

        $this->postJson('/api/image-identification/answer', [
            'quiz_id' => $quiz->id,
            'attempt_token' => $show['attempt_token'],
            'answers' => $answers,
        ])
            ->assertOk()
            ->assertJsonMissingPath('results')
            ->assertJsonPath('total', 3)
            ->assertJsonPath('correct', 3);

        $this->postJson('/api/image-identification/answer', [
            'quiz_id' => $quiz->id,
            'attempt_token' => $show['attempt_token'],
            'answers' => $answers,
        ])->assertStatus(409);
    }

    public function test_multiple_choice_rejects_a_partial_question_set(): void
    {
        $user = $this->createUser();
        $quiz = $this->createQuiz();
        $this->createAssessment($quiz, 'multiple_choice');

        $question = Question::query()->create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Which component stores temporary data?',
        ]);
        $correctOption = DB::table('question_options')->insertGetId([
            'question_id' => $question->id,
            'option_text' => 'RAM',
            'is_correct' => true,
        ]);
        DB::table('question_options')->insert([
            'question_id' => $question->id,
            'option_text' => 'Power supply',
            'is_correct' => false,
        ]);

        $secondQuestion = Question::query()->create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Which component connects other components?',
        ]);
        DB::table('question_options')->insert([
            'question_id' => $secondQuestion->id,
            'option_text' => 'Motherboard',
            'is_correct' => true,
        ]);
        DB::table('question_options')->insert([
            'question_id' => $secondQuestion->id,
            'option_text' => 'Mouse',
            'is_correct' => false,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/multiple-choice/answer', [
                'quiz_id' => $quiz->id,
                'elapsed_time' => 10,
                'answers_array' => [[
                    'question_id' => $question->id,
                    'answer_id' => $correctOption,
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('answers_array');

        $this->assertDatabaseCount('quiz_records', 0);
    }

    public function test_hardware_attempt_does_not_return_the_next_expected_step(): void
    {
        $user = $this->createUser();
        $quiz = $this->createQuiz();
        $this->createAssessment($quiz, 'hardware_procedure');
        $question = Question::query()->create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Remove and install the system fan.',
        ]);

        DB::table('drag_drop_items')->insert([
            'question_id' => $question->id,
            'component_key' => 'system_fan',
            'display_name' => 'System fan',
            'item_type' => 'component',
            'quantity' => 1,
            'phase' => 'disassemble',
            'action' => 'remove',
            'target_zone' => 'parts_tray',
            'correct_position' => 1,
            'required' => true,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/hardware/{$quiz->id}/start")
            ->assertOk()
            ->assertJsonMissingPath('data.next_step')
            ->assertJsonMissingPath('data.next_step.component_key')
            ->assertJsonMissingPath('data.next_step.action')
            ->assertJsonMissingPath('data.next_step.target_zone');
    }

    public function test_input_output_review_does_not_return_correct_classifications(): void
    {
        $user = $this->createUser();
        $quiz = $this->createQuiz();
        $assessment = $this->createAssessment($quiz, 'input_output');
        $input = InputOutput::query()->create([
            'name' => 'Keyboard',
            'type' => 'input',
            'image_key' => 'keyboard.png',
            'is_active' => true,
        ]);
        $output = InputOutput::query()->create([
            'name' => 'Monitor',
            'type' => 'output',
            'image_key' => 'monitor.png',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/input-output/answer', [
                'assessment_id' => $assessment->id,
                'session_uuid' => 'b64ff984-20cb-40ce-bb7f-c21a8f4e3f81',
                'placements' => [
                    ['device_id' => $input->id, 'selected_type' => 'input'],
                    ['device_id' => $output->id, 'selected_type' => 'output'],
                ],
            ])
            ->assertOk();

        foreach ($response->json('data.review') as $item) {
            $this->assertArrayNotHasKey('correct_type', $item);
            $this->assertArrayNotHasKey('is_correct', $item);
        }
    }

    public function test_forgot_password_response_does_not_disclose_account_existence(): void
    {
        Notification::fake();
        $this->createUser(['email' => 'registered@example.test']);

        $registered = $this->postJson('/api/forgot-password', [
            'email' => 'registered@example.test',
        ]);
        $unknown = $this->postJson('/api/forgot-password', [
            'email' => 'unknown@example.test',
        ]);

        $registered->assertOk();
        $unknown->assertOk();
        $this->assertSame($registered->json(), $unknown->json());
    }

    public function test_registration_requires_terms_acceptance_on_the_server(): void
    {
        Notification::fake();

        $this->postJson('/api/register', [
            'first_name' => 'Taylor',
            'last_name' => 'Learner',
            'email' => 'learner@example.test',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('accept_terms');

        $this->assertDatabaseCount('dasher', 0);
    }

    public function test_leaderboard_returns_only_public_score_fields(): void
    {
        $currentUser = $this->createUser(['email' => 'current@example.test']);
        $learner = $this->createUser([
            'email' => 'other@example.test',
            'first_name' => 'Morgan',
            'last_name' => 'Student',
            'profile_photo' => 'morgan.png',
        ]);
        $quiz = $this->createQuiz();
        $assessment = $this->createAssessment($quiz, 'multiple_choice');

        DB::table('quiz_records')->insert([
            'user_id' => $learner->id,
            'quiz_id' => $quiz->id,
            'score' => 8,
            'correct_answers' => 8,
            'total_questions' => 10,
            'percentage' => 80,
            'passing_score' => 75,
            'status' => 'Passed',
            'attempt_count' => 4,
            'quiz_type' => 'multiple_choice',
            'category' => 'Hardware',
            'elapsed_time' => 120,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($currentUser, 'sanctum')
            ->getJson("/api/dashboard/leaderboard?assessment_id={$assessment->id}")
            ->assertOk();

        $entry = $response->json('data.0');
        $this->assertSame([
            'rank',
            'is_you',
            'name',
            'profile_photo',
            'score',
            'percentage',
            'passed',
        ], array_keys($entry));
        $this->assertSame('Morgan Student', $entry['name']);
        $this->assertFalse($entry['is_you']);
    }

    public function test_account_deletion_removes_the_public_profile_photo(): void
    {
        Storage::fake('public');
        $user = $this->createUser(['profile_photo' => 'profile-photo.png']);
        Storage::disk('public')->put('images/profiles/profile-photo.png', 'image-data');

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/profile/delete')
            ->assertOk()
            ->assertJsonPath('status', 'success');

        Storage::disk('public')->assertMissing('images/profiles/profile-photo.png');
        $this->assertDatabaseMissing('dasher', ['id' => $user->id]);
    }

    public function test_admin_can_create_a_multiple_choice_quiz_and_its_answer_key(): void
    {
        $admin = $this->createUser([
            'email' => 'admin@example.test',
            'role' => 'admin',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/quizzes/create', $this->quizPayload())
            ->assertCreated()
            ->assertJsonPath('status', 'success');

        $quiz = Quiz::query()->where('title', 'Admin-created COC')->firstOrFail();
        $question = Question::query()->where('quiz_id', $quiz->id)->firstOrFail();

        $this->assertDatabaseCount('question_options', 4);
        $this->assertDatabaseCount('answers', 4);
        $this->assertSame(
            1,
            DB::table('question_options')
                ->where('question_id', $question->id)
                ->where('is_correct', true)
                ->count()
        );
    }

    public function test_admin_can_update_multiple_choice_options_without_duplicates(): void
    {
        $admin = $this->createUser([
            'email' => 'admin@example.test',
            'role' => 'admin',
        ]);
        $quiz = $this->createQuiz();
        $question = Question::query()->create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Original question',
        ]);
        DB::table('question_options')->insert([
            ['question_id' => $question->id, 'option_text' => 'Old A', 'is_correct' => true],
            ['question_id' => $question->id, 'option_text' => 'Old B', 'is_correct' => false],
            ['question_id' => $question->id, 'option_text' => 'Old C', 'is_correct' => false],
            ['question_id' => $question->id, 'option_text' => 'Old D', 'is_correct' => false],
        ]);

        $payload = $this->quizPayload();
        $payload['questions'][0]['id'] = $question->id;
        $payload['questions'][0]['correct_option'] = 2;

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/quizzes/{$quiz->id}", $payload)
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseCount('question_options', 4);
        $this->assertDatabaseCount('answers', 4);
        $this->assertSame(
            'New C',
            DB::table('question_options')
                ->where('question_id', $question->id)
                ->where('is_correct', true)
                ->value('option_text')
        );
        $this->assertSame(
            1,
            DB::table('answers')
                ->where('question_id', $question->id)
                ->where('is_correct', true)
                ->count()
        );
    }

    private function createUser(array $attributes = []): Dasher
    {
        return Dasher::query()->create(array_merge([
            'first_name' => 'Alex',
            'last_name' => 'Learner',
            'email' => 'alex@example.test',
            'password' => Hash::make('password'),
            'role' => 'dasher',
            'active_status' => 1,
        ], $attributes));
    }

    private function createQuiz(): Quiz
    {
        return Quiz::query()->create([
            'title' => 'CSS Competency',
            'description' => 'Computer systems servicing assessment.',
            'coc_number' => 'COC-1',
            'category' => 'Hardware',
            'quiz_type' => 'multiple_choice',
        ]);
    }

    private function createAssessment(Quiz $quiz, string $type): Assessment
    {
        return Assessment::query()->create([
            'coc_id' => $quiz->id,
            'type' => $type,
            'title' => ucfirst(str_replace('_', ' ', $type)),
            'path' => '/assessment',
            'available' => true,
            'category' => 'Hardware',
        ]);
    }

    private function createSchema(): void
    {
        foreach ([
            'image_identification_attempts',
            'image_identification_items',
            'input_output_attempts',
            'input_output_devices',
            'drag_drop_attempt_steps',
            'drag_drop_attempts',
            'drag_drop_items',
            'quiz_attempts',
            'admin_log',
            'answers',
            'quiz_records',
            'question_options',
            'questions',
            'assessments',
            'password_reset_tokens',
            'quizzes',
            'dasher',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('dasher', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('dasher');
            $table->string('profile_photo')->nullable();
            $table->boolean('active_status')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamp('last_activity')->nullable();
            $table->timestamps();
        });

        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('coc_number')->nullable();
            $table->string('category')->nullable();
            $table->string('difficulty')->nullable();
            $table->string('quiz_type')->nullable();
            $table->timestamps();
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coc_id');
            $table->string('type');
            $table->string('title');
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('difficulty')->nullable();
            $table->text('meta')->nullable();
            $table->string('path');
            $table->boolean('available')->default(true);
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->text('question_text');
            $table->string('question_type')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->string('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->text('answer_text');
            $table->boolean('is_correct')->default(false);
        });

        Schema::create('quiz_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('quiz_id');
            $table->integer('score')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('total_questions')->default(0);
            $table->decimal('percentage', 6, 2)->default(0);
            $table->decimal('passing_score', 6, 2)->default(75);
            $table->string('status')->nullable();
            $table->integer('attempt_count')->default(1);
            $table->string('quiz_type')->nullable();
            $table->string('category')->nullable();
            $table->integer('elapsed_time')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_record_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('selected_option_id');
            $table->boolean('is_correct');
            $table->timestamps();
        });

        Schema::create('image_identification_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->string('item_name');
            $table->string('image_path');
            $table->text('description');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('image_identification_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedBigInteger('quiz_id');
            $table->uuid('token')->unique();
            $table->json('question_set');
            $table->timestamp('expires_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('input_output_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('image_key');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('input_output_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('quiz_record_id')->nullable();
            $table->uuid('session_uuid');
            $table->unsignedBigInteger('device_id');
            $table->string('selected_type');
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('points')->default(0);
            $table->timestamps();
        });

        Schema::create('drag_drop_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->string('component_key')->nullable();
            $table->string('display_name')->nullable();
            $table->string('item_type')->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->text('item_text')->nullable();
            $table->text('instruction')->nullable();
            $table->string('item_image_path')->nullable();
            $table->string('phase')->nullable();
            $table->string('action')->nullable();
            $table->string('target_zone')->nullable();
            $table->unsignedInteger('correct_position')->default(0);
            $table->boolean('required')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('drag_drop_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('quiz_record_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->integer('completed_steps')->default(0);
            $table->integer('correct_steps')->default(0);
            $table->integer('total_steps')->default(0);
            $table->integer('mistake_count')->default(0);
            $table->decimal('percentage', 6, 2)->default(0);
            $table->boolean('passed')->default(false);
            $table->integer('elapsed_time')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('drag_drop_attempt_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drag_drop_attempt_id');
            $table->unsignedBigInteger('expected_item_id');
            $table->integer('sequence_number');
            $table->integer('expected_position')->nullable();
            $table->string('phase')->nullable();
            $table->string('expected_action')->nullable();
            $table->string('performed_action')->nullable();
            $table->string('expected_component_key')->nullable();
            $table->string('performed_component_key')->nullable();
            $table->string('source_zone')->nullable();
            $table->string('destination_zone')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->boolean('first_try')->default(false);
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('admin_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->string('action_type');
            $table->text('description');
            $table->timestamps();
        });
    }

    private function quizPayload(): array
    {
        return [
            'title' => 'Admin-created COC',
            'description' => 'Multiple-choice quiz created through admin.',
            'category' => 'Hardware',
            'difficulty' => 'easy',
            'questions' => [[
                'text' => 'Which component is temporary memory?',
                'options' => ['New A', 'New B', 'New C', 'New D'],
                'correct_option' => 0,
            ]],
        ];
    }
}
