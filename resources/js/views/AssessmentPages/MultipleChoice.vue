<template>
    <div class="quiz-wrapper">
        <!-- NAVBAR -->
        <header class="quiz-navbar">
            <div class="nav-content">
                <div class="nav-left">
                    <button type="button" class="btn-exit" title="Exit quiz" aria-label="Exit quiz" @click="openExit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <path d="m15 18-6-6 6-6" />
                        </svg>
                    </button>

                    <div class="title-stack">
                        <h1 class="quiz-title">
                            {{ quiz.title || "Multiple Choice Assessment" }}
                        </h1>
                        <span class="quiz-subtitle">Computer Systems Servicing</span>
                    </div>
                </div>

                <div class="nav-right">
                    <div class="stat-item">
                        <span class="stat-label">Question</span>
                        <span class="stat-value">
                            {{ questions.length ? currentIndex + 1 : 0 }}/{{ questions.length }}
                        </span>
                    </div>

                    <div class="v-divider" aria-hidden="true"></div>

                    <div class="stat-item">
                        <span class="stat-label">Time</span>
                        <span class="stat-value font-mono">{{ formattedTime }}</span>
                    </div>
                </div>
            </div>

            <div class="progress-track" role="progressbar" :aria-valuenow="Math.round(progress)" aria-valuemin="0"
                aria-valuemax="100" :aria-label="`Quiz progress: ${Math.round(progress)}%`">
                <div class="progress-fill" :style="{ width: progress + '%' }"></div>
            </div>
        </header>

        <!-- QUIZ CONTENT -->
        <main class="container">
            <transition name="fade" mode="out-in">
                <!-- LOADING -->
                <div v-if="loading" class="state-card" key="loading" role="status">
                    <div class="loader" aria-hidden="true"></div>
                    <p>Preparing your questions...</p>
                </div>

                <!-- ERROR -->
                <div v-else-if="error && !questions.length" class="state-card error" key="error" role="alert">
                    <div class="error-icon" aria-hidden="true">!</div>
                    <h2>Unable to load quiz</h2>
                    <p>{{ error }}</p>

                    <button type="button" class="btn-primary" @click="goBackToQuizzes">
                        Return to Dashboard
                    </button>

                    <button type="button" class="btn-retry-load" @click="retryLoading">
                        Try Loading Again
                    </button>
                </div>

                <!-- QUESTIONS -->
                <div v-else-if="questions.length" class="active-quiz" key="quiz">
                    <transition name="slide-fade" mode="out-in">
                        <section :key="currentIndex" class="question-card"
                            :aria-labelledby="`question-${currentQuestion.id}`">
                            <div class="image-container">
                                <img v-if="currentQuestion.image_path" :src="currentQuestion.image_path"
                                    alt="Question illustration" class="question-image" />

                                <h2 :id="`question-${currentQuestion.id}`" class="question-text">
                                    {{ currentQuestion.text }}
                                </h2>
                            </div>

                            <div v-if="currentQuestion.hint" class="hint-box">
                                <span aria-hidden="true">💡</span>
                                <span>{{ currentQuestion.hint }}</span>
                            </div>

                            <div v-if="error" class="error-message" role="alert">
                                {{ error }}
                            </div>

                            <div class="options-grid" role="radiogroup"
                                :aria-labelledby="`question-${currentQuestion.id}`">
                                <div v-for="(option, index) in currentQuestion.options" :key="option.id"
                                    class="option-item">
                                    <input :id="'opt-' + option.id" v-model="selectedAnswer" type="radio"
                                        :name="'question-' + currentQuestion.id" :value="option.id"
                                        class="hidden-radio" />

                                    <label :for="'opt-' + option.id" class="option-card">
                                        <span class="option-index" aria-hidden="true">
                                            {{ String.fromCharCode(65 + index) }}
                                        </span>
                                        <span class="option-content">{{ option.text }}</span>
                                    </label>
                                </div>
                            </div>

                            <footer class="action-bar">
                                <button type="button" class="btn-submit" :disabled="!selectedAnswer || submitting"
                                    @click="submitAnswer">
                                    <span>
                                        {{
                                            submitting
                                                ? "Submitting..."
                                                : currentIndex + 1 === questions.length
                                                    ? "Finish Assessment"
                                                    : "Next Question"
                                        }}
                                    </span>

                                    <svg v-if="currentIndex + 1 !== questions.length && !submitting"
                                        xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14m-7-7 7 7-7 7" />
                                    </svg>

                                    <span v-if="submitting" class="button-loader" aria-hidden="true"></span>
                                </button>
                            </footer>
                        </section>
                    </transition>
                </div>

                <!-- NO QUESTIONS -->
                <div v-else class="state-card" key="empty">
                    <h2>No questions available</h2>
                    <p>This assessment currently has no questions to answer.</p>

                    <button type="button" class="btn-primary" @click="goBackToQuizzes">
                        Return to Dashboard
                    </button>
                </div>
            </transition>
        </main>

        <!-- RESULT -->
        <transition name="modal-fade">
            <div v-if="showResult && resultRecord" class="result-overlay" role="dialog" aria-modal="true"
                aria-labelledby="result-title">
                <section class="result-modal">
                    <div class="result-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="27" height="27" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6 9 17l-5-5" />
                        </svg>
                    </div>

                    <p class="result-eyebrow">ASSESSMENT COMPLETE</p>
                    <h2 id="result-title" class="result-title">Quiz submitted!</h2>
                    <p class="result-description">
                        Your answers have been submitted successfully.
                    </p>

                    <div class="result-score">
                        <span class="score-number">{{ resultRecord.score }}</span>
                        <span class="score-divider">/</span>
                        <span class="score-total">{{ resultRecord.total_questions }}</span>
                    </div>

                    <p class="result-caption">Correct answers</p>

                    <div class="result-meta">
                        <div class="result-meta-label">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 6v6l4 2" />
                            </svg>
                            <span>Time taken</span>
                        </div>

                        <strong>{{ formatResultTime(resultRecord.elapsed_time) }}</strong>
                    </div>

                    <div class="result-percentage">
                        <div class="percentage-heading">
                            <span>Final score</span>
                            <strong>{{ resultPercentage }}%</strong>
                        </div>

                        <div class="percentage-track">
                            <div class="percentage-fill" :style="{ width: resultPercentage + '%' }"></div>
                        </div>
                    </div>

                    <div class="result-feedback">
                        <template v-if="resultPercentage >= 75">
                            <strong>Excellent work!</strong>
                            <p>You have a solid grasp of this topic. Keep up the great work!</p>
                        </template>

                        <template v-else-if="resultPercentage >= 50">
                            <strong>Good effort!</strong>
                            <p>You're making progress. Keep practicing to improve your score.</p>
                        </template>

                        <template v-else>
                            <strong>Keep practicing!</strong>
                            <p>Every attempt is a learning opportunity. Review the topic and try again.</p>
                        </template>
                    </div>

                    <div v-if="resultRecord.status" class="result-status">
                        <span class="status-dot" aria-hidden="true"></span>
                        {{ resultRecord.status }}
                    </div>

                    <div class="result-actions">
                        <button type="button" class="result-btn result-btn-primary" @click="goToDashboard">
                            Go to Dashboard
                        </button>

                        <button type="button" class="result-btn result-btn-secondary" :disabled="restarting"
                            @click="reTake">
                            {{ restarting ? "Preparing quiz..." : "Try Again" }}
                        </button>
                    </div>
                </section>
            </div>
        </transition>

        <!-- EXIT CONFIRMATION -->
        <Teleport to="body">
            <Transition name="exit-modal">
                <div v-if="showExitModal" class="exit-backdrop" @click.self="closeExit">
                    <section ref="exitDialog" class="exit-modal" role="alertdialog" aria-modal="true"
                        aria-labelledby="exit-title" aria-describedby="exit-desc" tabindex="-1"
                        @keydown.tab="trapExitFocus">
                        <div class="exit-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <path d="m16 17 5-5-5-5" />
                                <path d="M21 12H9" />
                            </svg>
                        </div>

                        <h2 id="exit-title">Leave this assessment?</h2>

                        <p id="exit-desc">
                            Your answers will not be saved, and the timer will stop. You will start again from
                            question 1 next time.
                        </p>

                        <div class="exit-progress" aria-live="polite">
                            <span class="exit-progress-value">{{ answeredCount }}</span>
                            <span>of {{ questions.length }} answered</span>
                        </div>

                        <div class="exit-actions">
                            <button ref="stayButton" type="button" class="exit-btn exit-btn-primary" @click="closeExit">
                                Keep answering
                            </button>

                            <button type="button" class="exit-btn exit-btn-leave" @click="leaveAssessment">
                                Leave assessment
                            </button>
                        </div>
                    </section>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, watch, onMounted, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import { multipleChoiceRequest } from "@/assessmentrequests/multipleChoice";

const route = useRoute();
const router = useRouter();

/* =========================================
   STATE
========================================= */

const quiz = ref({
    id: null,
    title: "",
    description: "",
    total_questions: 0,
});

const questions = ref([]);
const answers = ref([]);
const currentIndex = ref(0);
const selectedAnswer = ref(null);
const timeElapsed = ref(0);

const loading = ref(true);
const submitting = ref(false);
const restarting = ref(false);
const error = ref(null);

const showResult = ref(false);
const resultRecord = ref(null);

const showExitModal = ref(false);
const exitDialog = ref(null);
const stayButton = ref(null);
let lastFocused = null;

let timerInterval = null;

/* =========================================
   COMPUTED
========================================= */

const currentQuestion = computed(() => {
    return (
        questions.value[currentIndex.value] || {
            id: null,
            text: "",
            image_path: null,
            hint: null,
            options: [],
        }
    );
});

const progress = computed(() => {
    if (!questions.value.length) return 0;
    return ((currentIndex.value + 1) / questions.value.length) * 100;
});

const answeredCount = computed(() => answers.value.length);

const formattedTime = computed(() => formatResultTime(timeElapsed.value));

const resultPercentage = computed(() => {
    if (!resultRecord.value || !resultRecord.value.total_questions) return 0;

    return Math.round(
        (resultRecord.value.score / resultRecord.value.total_questions) * 100,
    );
});

/* =========================================
   TIMER
========================================= */

const startTimer = () => {
    stopTimer();

    timerInterval = setInterval(() => {
        // The timer pauses while the result or the exit dialog is open.
        if (
            !showResult.value &&
            !submitting.value &&
            !loading.value &&
            !showExitModal.value
        ) {
            timeElapsed.value++;
        }
    }, 1000);
};

const stopTimer = () => {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
};

const formatResultTime = (seconds) => {
    const total = Math.max(0, Number(seconds) || 0);
    const minutes = Math.floor(total / 60);
    const secs = total % 60;

    return `${String(minutes).padStart(2, "0")}:${String(secs).padStart(2, "0")}`;
};

/* =========================================
   LOAD
========================================= */

const fetchQuiz = async () => {
    loading.value = true;
    error.value = null;

    const quizId = route.params.quiz_id;

    if (!quizId) {
        error.value = "Missing quiz ID.";
        loading.value = false;
        return false;
    }

    try {
        const { data } = await multipleChoiceRequest.fetch(quizId);

        if (data?.status !== "success" || !data?.quiz) {
            throw new Error(data?.message || "Invalid quiz response.");
        }

        quiz.value = data.quiz;
        questions.value = Array.isArray(data.quiz.questions)
            ? data.quiz.questions
            : [];

        answers.value = [];
        selectedAnswer.value = null;
        currentIndex.value = 0;
        timeElapsed.value = 0;

        if (!questions.value.length) {
            error.value = "No questions found for this quiz.";
            return false;
        }

        return true;
    } catch (err) {
        error.value =
            err.response?.data?.message ||
            err.message ||
            "Unable to load quiz.";

        console.error("Failed to load quiz:", err);
        return false;
    } finally {
        loading.value = false;
    }
};

const retryLoading = async () => {
    const loaded = await fetchQuiz();
    if (loaded) startTimer();
};

/* =========================================
   ANSWERS AND SUBMISSION
========================================= */

const submitAnswer = async () => {
    if (
        submitting.value ||
        !selectedAnswer.value ||
        !currentQuestion.value.id
    ) {
        return;
    }

    error.value = null;

    const answer = {
        question_id: currentQuestion.value.id,
        answer_id: selectedAnswer.value,
    };

    const existingIndex = answers.value.findIndex(
        (item) => item.question_id === answer.question_id,
    );

    if (existingIndex >= 0) {
        answers.value[existingIndex] = answer;
    } else {
        answers.value.push(answer);
    }

    if (currentIndex.value < questions.value.length - 1) {
        currentIndex.value++;
        selectedAnswer.value = null;
        return;
    }

    submitting.value = true;

    try {
        const { data } = await multipleChoiceRequest.submitAnswer({
            quiz_id: quiz.value.id,
            answers_array: answers.value,
            elapsed_time: timeElapsed.value,
        });

        if (data?.status !== "success" || !data?.record_id) {
            throw new Error(data?.message || "Unable to save quiz result.");
        }

        resultRecord.value = {
            id: data.record_id,
            quiz_id: data.quiz_id ?? quiz.value.id,
            score: Number(data.score ?? 0),
            total_questions: Number(
                data.total_questions ?? questions.value.length,
            ),
            elapsed_time: Number(data.elapsed_time ?? timeElapsed.value),
            status: data.status_result ?? null,
        };

        stopTimer();
        showResult.value = true;
    } catch (err) {
        error.value =
            err.response?.data?.message ||
            err.message ||
            "Submission failed. Please try again.";

        console.error("Quiz submission failed:", err);
    } finally {
        submitting.value = false;
    }
};

const reTake = async () => {
    if (restarting.value || submitting.value) return;

    restarting.value = true;
    showResult.value = false;
    resultRecord.value = null;
    stopTimer();

    try {
        const loaded = await fetchQuiz();
        if (loaded) startTimer();
    } finally {
        restarting.value = false;
    }
};

/* =========================================
   NAVIGATION
========================================= */

const goToDashboard = () => {
    stopTimer();
    router.push("/user/");
};

const goBackToQuizzes = () => {
    stopTimer();
    router.push("/user/assessments");
};

/* =========================================
   EXIT CONFIRMATION
========================================= */

const openExit = () => {
    if (submitting.value || showResult.value) return;
    showExitModal.value = true;
};

const closeExit = () => {
    showExitModal.value = false;
};

const leaveAssessment = () => {
    stopTimer();
    showExitModal.value = false;

    // Go back in history when there is a previous page, otherwise open the list.
    if (window.history.state?.back) {
        router.back();
    } else {
        router.push("/user/assessments");
    }
};

// Keeps Tab inside the dialog so keyboard users can't reach the quiz behind it.
const trapExitFocus = (event) => {
    const focusable = exitDialog.value?.querySelectorAll("button:not([disabled])");
    if (!focusable?.length) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
};

// Focus the safe action when the dialog opens, and return focus to the trigger when it closes.
watch(showExitModal, async (open) => {
    if (open) {
        lastFocused = document.activeElement;
        await nextTick();
        stayButton.value?.focus();
    } else if (lastFocused instanceof HTMLElement) {
        lastFocused.focus();
        lastFocused = null;
    }
});

const handleEscape = (event) => {
    if (event.key === "Escape" && showExitModal.value) closeExit();
};

/* =========================================
   LIFECYCLE
========================================= */

onMounted(async () => {
    window.addEventListener("keydown", handleEscape);

    const loaded = await fetchQuiz();
    if (loaded) startTimer();
});

onBeforeUnmount(() => {
    stopTimer();
    window.removeEventListener("keydown", handleEscape);
});
</script>

<style scoped>
.quiz-wrapper {
    --white: #fff;
    --black: #000;
    --gray-light: #d3d3d3;
    --gray: #a9a9a9;
    --gray-dark: #696969;
    --page-bg: #f6f6f6;
    --surface: #fff;
    --border: #d3d3d3;

    box-sizing: border-box;
    width: 100%;
    min-width: 0;
    min-height: 100vh;
    overflow-x: hidden;
    background: var(--page-bg);
    color: var(--black);
    font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.quiz-wrapper *,
.quiz-wrapper *::before,
.quiz-wrapper *::after {
    box-sizing: border-box;
}

.quiz-wrapper button {
    font-family: inherit;
}

/* HEADER */

.quiz-navbar {
    position: sticky;
    top: 0;
    z-index: 100;
    width: 100%;
    border-bottom: 1px solid var(--border);
    background: rgb(255 255 255 / 94%);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.nav-content {
    display: flex;
    width: 100%;
    max-width: 720px;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin: 0 auto;
    padding: 11px 18px;
}

.nav-left {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 10px;
}

.btn-exit {
    display: grid;
    width: 36px;
    height: 36px;
    flex: 0 0 36px;
    place-items: center;
    padding: 0;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--surface);
    color: var(--gray-dark);
    cursor: pointer;
    transition: background 0.18s ease, color 0.18s ease, border-color 0.18s ease;
}

.btn-exit:hover {
    border-color: var(--black);
    background: var(--black);
    color: var(--white);
}

.title-stack {
    display: flex;
    min-width: 0;
    flex-direction: column;
}

.quiz-title {
    max-width: 320px;
    margin: 0;
    overflow: hidden;
    color: var(--black);
    font-size: 13px;
    font-weight: 750;
    line-height: 1.25;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.quiz-subtitle {
    margin-top: 2px;
    color: var(--gray-dark);
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 0.03em;
}

.nav-right {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 13px;
}

.stat-item {
    display: flex;
    min-width: 42px;
    flex-direction: column;
    align-items: flex-end;
}

.stat-label {
    color: var(--gray);
    font-size: 8px;
    font-weight: 750;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.stat-value {
    margin-top: 1px;
    color: var(--black);
    font-size: 11px;
    font-weight: 750;
}

.font-mono {
    font-family: "JetBrains Mono", "Fira Code", Consolas, monospace;
    font-variant-numeric: tabular-nums;
}

.v-divider {
    width: 1px;
    height: 25px;
    background: var(--border);
}

.progress-track {
    width: 100%;
    height: 3px;
    overflow: hidden;
    background: #e8e8e8;
}

.progress-fill {
    height: 100%;
    background: var(--black);
    transition: width 0.35s ease;
}

/* LAYOUT */

.container {
    box-sizing: border-box;
    width: 100%;
    max-width: 640px;
    margin: 0 auto;
    padding: clamp(20px, 5vw, 40px) clamp(12px, 4vw, 20px) 50px;
}

.question-card {
    width: 100%;
    padding: clamp(18px, 4vw, 26px);
    border: 1px solid var(--border);
    border-radius: 14px;
    background: var(--surface);
    box-shadow: 0 5px 18px rgb(0 0 0 / 4.5%);
}

.image-container {
    width: 100%;
}

.question-image {
    display: block;
    width: auto;
    max-width: 100%;
    max-height: 220px;
    margin: 0 auto 18px;
    border-radius: 8px;
    object-fit: contain;
}

.question-text {
    margin: 0 0 20px;
    color: var(--black);
    font-size: clamp(16px, 2.5vw, 19px);
    font-weight: 700;
    line-height: 1.45;
    overflow-wrap: anywhere;
}

.hint-box {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 18px;
    padding: 10px 12px;
    border-left: 3px solid var(--gray-dark);
    border-radius: 6px;
    background: #f4f4f4;
    color: var(--gray-dark);
    font-size: 11px;
    line-height: 1.5;
}

.error-message {
    margin-bottom: 16px;
    padding: 11px 12px;
    border: 1px solid var(--gray-light);
    border-left: 3px solid var(--black);
    border-radius: 7px;
    background: #f5f5f5;
    color: #333;
    font-size: 12px;
    line-height: 1.5;
    overflow-wrap: anywhere;
}

/* ANSWER OPTIONS */

.options-grid {
    display: grid;
    width: 100%;
    gap: 9px;
}

.option-item {
    position: relative;
    min-width: 0;
}

.hidden-radio {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    opacity: 0;
    pointer-events: none;
}

.option-card {
    display: flex;
    width: 100%;
    min-width: 0;
    align-items: center;
    gap: 11px;
    padding: 11px 12px;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: var(--white);
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}

.option-card:hover {
    border-color: var(--gray-dark);
    background: #fafafa;
}

.option-index {
    display: grid;
    width: 27px;
    height: 27px;
    flex: 0 0 27px;
    place-items: center;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    background: #eee;
    color: var(--gray-dark);
    font-size: 10px;
    font-weight: 800;
    transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.option-content {
    min-width: 0;
    color: #303030;
    font-size: 12px;
    font-weight: 550;
    line-height: 1.45;
    overflow-wrap: anywhere;
}

.hidden-radio:checked+.option-card {
    border-color: var(--black);
    background: #f2f2f2;
    box-shadow: 0 0 0 1px var(--black);
}

.hidden-radio:checked+.option-card .option-index {
    border-color: var(--black);
    background: var(--black);
    color: var(--white);
}

.hidden-radio:checked+.option-card .option-content {
    color: var(--black);
    font-weight: 650;
}

.hidden-radio:focus-visible+.option-card {
    outline: 2px solid var(--black);
    outline-offset: 3px;
}

/* ACTIONS */

.action-bar {
    display: flex;
    width: 100%;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 22px;
    padding-top: 17px;
    border-top: 1px solid #e8e8e8;
}

.btn-submit {
    display: inline-flex;
    min-height: 40px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 9px 16px;
    border: 1px solid var(--black);
    border-radius: 7px;
    background: var(--black);
    color: var(--white);
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.18s ease, transform 0.12s ease;
}

.btn-submit:hover:not(:disabled) {
    background: var(--gray-dark);
}

.btn-submit:active:not(:disabled) {
    transform: scale(0.97);
}

.btn-submit:disabled {
    border-color: #d3d3d3;
    background: #d3d3d3;
    color: #fff;
    cursor: not-allowed;
}

.button-loader,
.loader {
    border: 2px solid rgb(255 255 255 / 40%);
    border-top-color: #fff;
    border-radius: 50%;
    animation: rotation 0.7s linear infinite;
}

.button-loader {
    width: 13px;
    height: 13px;
}

/* LOADING AND ERROR */

.state-card {
    display: flex;
    width: 100%;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 40px 24px;
    border: 1px solid var(--border);
    border-radius: 14px;
    background: var(--white);
    text-align: center;
    box-shadow: 0 5px 18px rgb(0 0 0 / 4%);
}

.state-card p {
    max-width: 320px;
    margin: 0;
    color: var(--gray-dark);
    font-size: 12px;
    line-height: 1.6;
}

.state-card h2 {
    margin: 5px 0 0;
    color: var(--black);
    font-size: 16px;
    font-weight: 750;
}

.state-card.error {
    background: #fafafa;
}

.error-icon {
    display: grid;
    width: 42px;
    height: 42px;
    place-items: center;
    border-radius: 50%;
    background: #eee;
    font-size: 22px;
    font-weight: 800;
}

.btn-primary,
.btn-retry-load {
    min-height: 40px;
    padding: 9px 14px;
    border: 1px solid var(--black);
    border-radius: 7px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}

.btn-primary {
    background: var(--black);
    color: var(--white);
}

.btn-primary:hover {
    background: var(--gray-dark);
}

.btn-retry-load {
    background: #fff;
    color: #000;
}

.btn-retry-load:hover {
    background: #eee;
}

.loader {
    width: 30px;
    height: 30px;
    margin-bottom: 5px;
    border-color: #e5e5e5;
    border-top-color: var(--black);
}

/* RESULT MODAL */

.result-overlay {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
    overflow-y: auto;
    overscroll-behavior: contain;
    background: rgb(0 0 0 / 58%);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
}

.result-modal {
    width: 100%;
    max-width: 420px;
    max-height: calc(100dvh - 36px);
    padding: clamp(24px, 5vw, 34px);
    overflow-y: auto;
    overscroll-behavior: contain;
    border: 1px solid #d3d3d3;
    border-radius: 18px;
    background: #fff;
    color: #000;
    text-align: center;
    box-shadow: 0 24px 80px rgb(0 0 0 / 20%);
    animation: result-pop 0.28s ease-out both;
}

.result-icon {
    display: grid;
    width: 52px;
    height: 52px;
    place-items: center;
    margin: 0 auto 16px;
    border-radius: 50%;
    background: #000;
    color: #fff;
}

.result-eyebrow {
    margin: 0 0 8px;
    color: #696969;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.12em;
}

.result-title {
    margin: 0;
    font-size: clamp(21px, 5vw, 27px);
    font-weight: 800;
    letter-spacing: -0.04em;
}

.result-description {
    margin: 9px 0 0;
    color: #696969;
    font-size: 11px;
    line-height: 1.6;
}

.result-score {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 9px;
    margin-top: 24px;
}

.score-number {
    font-size: clamp(48px, 12vw, 64px);
    font-weight: 850;
    letter-spacing: -0.06em;
    line-height: 1;
}

.score-divider {
    color: #a9a9a9;
    font-size: 27px;
}

.score-total {
    color: #696969;
    font-size: 30px;
    font-weight: 700;
}

.result-caption {
    margin: 8px 0 20px;
    color: #696969;
    font-size: 12px;
}

.result-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 14px;
    border: 1px solid #e5e5e5;
    border-radius: 9px;
    background: #f5f5f5;
    font-size: 12px;
}

.result-meta-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #696969;
}

.result-meta strong {
    color: #000;
    font-variant-numeric: tabular-nums;
}

.result-percentage {
    margin-top: 18px;
    text-align: left;
}

.percentage-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 9px;
    font-size: 11px;
}

.percentage-heading span {
    color: #696969;
}

.percentage-heading strong {
    color: #000;
    font-size: 12px;
    font-weight: 800;
}

.percentage-track {
    width: 100%;
    height: 6px;
    overflow: hidden;
    border-radius: 99px;
    background: #e9e9e9;
}

.percentage-fill {
    height: 100%;
    border-radius: inherit;
    background: #000;
    transition: width 0.5s ease;
}

.result-feedback {
    margin-top: 18px;
    padding: 15px;
    border: 1px solid #e5e5e5;
    border-radius: 10px;
    background: #fafafa;
    text-align: left;
}

.result-feedback strong {
    color: #000;
    font-size: 13px;
    font-weight: 800;
}

.result-feedback p {
    margin: 6px 0 0;
    color: #696969;
    font-size: 12px;
    line-height: 1.6;
}

.result-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    margin-top: 14px;
    color: #696969;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #000;
}

.result-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 22px;
}

.result-btn {
    min-width: 0;
    min-height: 44px;
    padding: 11px 12px;
    border: 1px solid #000;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 750;
    line-height: 1.4;
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
}

.result-btn:active:not(:disabled) {
    transform: scale(0.97);
}

.result-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.result-btn-primary {
    background: #000;
    color: #fff;
}

.result-btn-primary:hover {
    background: #444;
}

.result-btn-secondary {
    background: #fff;
    color: #000;
}

.result-btn-secondary:hover:not(:disabled) {
    background: #eee;
}

/* EXIT CONFIRMATION */

.exit-backdrop {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: grid;
    place-items: center;
    padding: 18px;
    background: rgb(0, 0, 0, 0.5);
}

.exit-modal {
    width: min(100%, 380px);
    padding: 28px 24px 22px;
    border: 1px solid rgb(169 169 169 / 35%);
    border-radius: 20px;
    background: rgb(255 255 255 / 94%);
    color: #000;
    text-align: center;
    box-shadow: 0 24px 60px rgb(0 0 0 / 18%), inset 0 1px 0 #fff;
    font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.exit-modal:focus {
    outline: none;
}

.exit-icon {
    display: grid;
    width: 52px;
    height: 52px;
    place-items: center;
    margin: 0 auto 16px;
    border: 1px solid #d3d3d3;
    border-radius: 15px;
    background: #fff;
    color: #000;
    box-shadow: 0 2px 6px rgb(0 0 0 / 6%);
}

.exit-modal h2 {
    margin: 0 0 8px;
    font-size: 19px;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.3;
}

.exit-modal p {
    margin: 0;
    color: #696969;
    font-size: 12px;
    line-height: 1.6;
}

.exit-progress {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 16px 0 0;
    padding: 6px 12px;
    border: 1px solid #e5e5e5;
    border-radius: 999px;
    background: #f5f5f5;
    color: #696969;
    font-size: 11px;
    font-weight: 600;
}

.exit-progress-value {
    color: #000;
    font-size: 12px;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
}

.exit-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: 22px;
}

.exit-btn {
    min-height: 44px;
    padding: 0 12px;
    border: 1px solid #000;
    border-radius: 10px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.2;
    cursor: pointer;
    transition: background 0.18s ease, color 0.18s ease, border-color 0.18s ease, transform 0.12s ease;
}

.exit-btn:active {
    transform: scale(0.98);
}

.exit-btn:focus-visible {
    outline: 2px solid #000;
    outline-offset: 3px;
}

/* Default action keeps the learner in the quiz */
.exit-btn-primary {
    background: #000;
    color: #fff;
}

.exit-btn-primary:hover {
    background: #262626;
}

/* Leaving is secondary, so it is not the first thing a learner reaches */
.exit-btn-leave {
    border-color: #d3d3d3;
    background: #fff;
    color: #000;
}

.exit-btn-leave:hover {
    border-color: #fee4e2;
    background: #fef3f2;
    color: #b42318;
}

/* TRANSITIONS */

.fade-enter-active,
.fade-leave-active,
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to,
.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.slide-fade-enter-from {
    transform: translateX(12px);
    opacity: 0;
}

.slide-fade-leave-to {
    transform: translateX(-12px);
    opacity: 0;
}

.exit-modal-enter-active,
.exit-modal-leave-active {
    transition: opacity 0.2s ease;
}

.exit-modal-enter-active .exit-modal,
.exit-modal-leave-active .exit-modal {
    transition: transform 0.22s cubic-bezier(0.2, 0.7, 0.2, 1), opacity 0.2s ease;
}

.exit-modal-enter-from,
.exit-modal-leave-to {
    opacity: 0;
}

.exit-modal-enter-from .exit-modal,
.exit-modal-leave-to .exit-modal {
    opacity: 0;
    transform: translateY(10px) scale(0.98);
}

@keyframes rotation {
    to {
        transform: rotate(360deg);
    }
}

@keyframes result-pop {
    from {
        transform: translateY(12px) scale(0.98);
        opacity: 0;
    }

    to {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

/* RESPONSIVE */

/* Tablet */
@media (max-width: 768px) {
    .nav-content {
        padding: 10px 15px;
    }

    .container {
        max-width: 600px;
    }

    .question-card {
        border-radius: 12px;
    }
}

/* Mobile */
@media (max-width: 600px) {
    .nav-content {
        gap: 10px;
        padding: 9px 12px;
    }

    .nav-left {
        gap: 8px;
    }

    .btn-exit {
        width: 34px;
        height: 34px;
        flex-basis: 34px;
    }

    .quiz-title {
        max-width: 160px;
        font-size: 12px;
    }

    .quiz-subtitle {
        display: none;
    }

    .nav-right {
        gap: 8px;
    }

    .stat-label {
        font-size: 7px;
    }

    .stat-value {
        font-size: 10px;
    }

    .v-divider {
        height: 22px;
    }

    .container {
        padding: 16px 10px 32px;
    }

    .question-card {
        padding: 16px;
        border-radius: 11px;
    }

    .question-image {
        max-height: 160px;
        margin-bottom: 14px;
    }

    .question-text {
        margin-bottom: 16px;
        font-size: 15px;
    }

    .hint-box {
        margin-bottom: 15px;
        font-size: 10px;
    }

    .option-card {
        gap: 9px;
        padding: 10px;
        border-radius: 8px;
    }

    .option-index {
        width: 26px;
        height: 26px;
        flex-basis: 26px;
    }

    .option-content {
        font-size: 11px;
    }

    .action-bar {
        margin-top: 18px;
        padding-top: 14px;
    }

    .result-overlay {
        padding: 12px;
    }

    .result-modal {
        max-height: calc(100dvh - 24px);
        padding: 25px 18px;
        border-radius: 14px;
    }

    .result-actions {
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .result-btn {
        min-height: 45px;
    }

    .exit-actions {
        gap: 8px;
    }
}

/* Small phones */
@media (max-width: 400px) {
    .nav-content {
        padding-inline: 10px;
    }

    .quiz-title {
        max-width: 125px;
        font-size: 11px;
    }

    .stat-item {
        min-width: 35px;
    }

    .nav-right {
        gap: 6px;
    }

    .container {
        padding: 12px 8px 28px;
    }

    .question-card {
        padding: 13px;
    }

    .question-text {
        font-size: 14px;
    }

    .option-card {
        padding: 9px;
    }

    .option-content {
        font-size: 10.5px;
    }

    .hint-box {
        padding: 9px 10px;
    }

    .result-modal {
        padding: 23px 15px;
    }

    .score-number {
        font-size: 52px;
    }

    .score-total {
        font-size: 26px;
    }

    .exit-modal {
        padding: 24px 18px 18px;
    }

    .exit-actions {
        grid-template-columns: 1fr;
    }
}

/* Very small phones */
@media (max-width: 330px) {
    .quiz-title {
        max-width: 95px;
    }

    .nav-right {
        gap: 4px;
    }

    .v-divider {
        display: none;
    }

    .stat-label {
        font-size: 6px;
    }

    .stat-value {
        font-size: 9px;
    }

    .question-card {
        padding: 11px;
    }

    .question-text {
        font-size: 13px;
    }

    .option-content {
        font-size: 10px;
    }

    .result-modal {
        padding: 20px 12px;
    }

    .result-title {
        font-size: 20px;
    }

    .result-feedback {
        padding: 12px;
    }
}

/* ACCESSIBILITY */

.quiz-wrapper :focus-visible {
    outline: 2px solid #000;
    outline-offset: 3px;
}

@media (prefers-reduced-motion: reduce) {

    .quiz-wrapper *,
    .quiz-wrapper *::before,
    .quiz-wrapper *::after,
    .exit-modal,
    .exit-btn {
        scroll-behavior: auto !important;
        animation: none !important;
        transition: none !important;
    }
}
</style>