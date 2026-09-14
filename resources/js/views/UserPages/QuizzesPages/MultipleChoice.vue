<template>
    <div class="quiz-wrapper">
        <!-- Navbar -->
        <header class="quiz-navbar">
            <div class="nav-content">
                <div class="nav-left">
                    <button @click="confirmExit" class="btn-exit" title="Exit Quiz">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-6-6 6-6" />
                        </svg>
                    </button>
                    <div class="title-stack">
                        <h1 class="quiz-title">{{ quiz.title }}</h1>
                        <span class="quiz-subtitle">Computer Systems Servicing</span>
                    </div>
                </div>

                <div class="nav-right">
                    <div class="stat-item">
                        <span class="stat-label">Question</span>
                        <span class="stat-value">{{ currentIndex + 1 }}/{{ questions.length }}</span>
                    </div>
                    <div class="v-divider"></div>
                    <div class="stat-item">
                        <span class="stat-label">Time</span>
                        <span class="stat-value font-mono">{{ formattedTime }}</span>
                    </div>
                </div>
            </div>
            <div class="progress-track">
                <div class="progress-fill" :style="{ width: progress + '%' }"></div>
            </div>
        </header>

        <!-- Main -->
        <main class="container">
            <transition name="fade">
                <div v-if="loading" class="state-card">
                    <div class="loader"></div>
                    <p>Preparing your questions…</p>
                </div>

                <!-- shows error if there is no questions -->
                <div v-else-if="error" class="state-card error">
                    <div class="icon">⚠️</div>
                    <h2>Unable to load quiz</h2>
                    <small>You can ask the admins.</small>
                    <button @click="goBackToQuizzes" class="btn-primary">Return to Dashboard</button>
                </div>

                <div v-else-if="questions.length" class="active-quiz">
                    <transition name="slide-fade" mode="out-in">
                        <div class="question-card" :key="currentIndex">
                            <div class="image-container">
                                <img v-if="currentQuestion.image_path" :src="currentQuestion.image_path"
                                    alt="Question Image" class="question-image" />
                                <h2 class="question-text">{{ currentQuestion.text }}</h2>
                            </div>

                            <!-- Hint Box -->
                            <div v-if="currentQuestion.hint" class="hint-box">
                                💡 <span>{{ currentQuestion.hint }}</span>
                            </div>

                            <!-- Options -->
                            <div class="options-grid">
                                <div v-for="(option, index) in currentQuestion.options" :key="option.id"
                                    class="option-item">
                                    <input type="radio" :id="'opt-' + option.id" v-model="selectedAnswer"
                                        :value="option.id" class="hidden-radio" />
                                    <label :for="'opt-' + option.id" class="option-card">
                                        <span class="option-index">{{ String.fromCharCode(65 + index) }}</span>
                                        <span class="option-content">{{ option.text }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Action Bar -->
                            <footer class="action-bar">
                                <button @click="submitAnswer" class="btn-submit"
                                    :disabled="!selectedAnswer || submitting">
                                    <span>{{ currentIndex + 1 === questions.length ? 'Finish Quiz' : 'Continue'
                                        }}</span>
                                    <svg v-if="currentIndex + 1 !== questions.length" xmlns="http://www.w3.org/2000/svg"
                                        width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 12h14m-7-7 7 7-7 7" />
                                    </svg>
                                </button>
                            </footer>
                        </div>
                    </transition>
                </div>
            </transition>
        </main>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const quiz = ref({ title: '', total_questions: 0 })
const questions = ref([])
const answers = ref([])
const timeElapsed = ref(0)
const submitting = ref(false)

const currentIndex = ref(parseInt(localStorage.getItem('quiz_current_index')) || 0)
const selectedAnswer = ref(null)

const loading = ref(true)
const error = ref(null)

let timerInterval = null

// CURRENT QUESTION
const currentQuestion = computed(() => {
    return questions.value[currentIndex.value] || {
        id: null,
        text: '',
        options: []
    }
})

// PROGRESS
const progress = computed(() => {
    return questions.value.length
        ? ((currentIndex.value + 1) / questions.value.length) * 100
        : 0
})

// FORMAT TIME
const formattedTime = computed(() => {
    const mins = Math.floor(timeElapsed.value / 60)
    const secs = timeElapsed.value % 60
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
})

// FETCH QUIZ
const fetchQuiz = async () => {
    loading.value = true
    error.value = null

    const quizId = route.params.quiz_id

    if (!quizId) {
        router.push('/user/quizzes')
        return
    }

    localStorage.setItem('quiz_id', quizId)

    try {
        const { data } = await axios.get(`/api/quiz/${quizId}`)

        if (data.status === 'success') {
            quiz.value = data.quiz
            questions.value = data.quiz.questions || []

            if (!questions.value.length) {
                error.value = 'No questions found'
            }
        }
    } catch (err) {
        error.value = err.message
    } finally {
        loading.value = false
    }
}

// SUBMIT ANSWER
const submitAnswer = async () => {
    if (!selectedAnswer.value || submitting.value) return

    answers.value.push({
        question_id: currentQuestion.value.id,
        answer_id: selectedAnswer.value
    })

    selectedAnswer.value = null

    if (currentIndex.value < questions.value.length - 1) {
        currentIndex.value++
        localStorage.setItem('quiz_current_index', currentIndex.value)
    } else {
        submitting.value = true // <-- fix: set before the request, not just reset after

        try {
            const { data } = await axios.post('/api/quiz/answer', {
                answers_array: answers.value,
                quiz_id: route.params.quiz_id,
                elapsed_time: timeElapsed.value
            })

            if (data?.record_id) {
                localStorage.removeItem('quiz_current_index')
                localStorage.removeItem('quiz_id')

                router.push(`/quiz-result/${data.record_id}`)
            }
        } catch (err) {
            console.error(err)
        } finally {
            submitting.value = false
        }
    }
}

// EXIT
const confirmExit = () => {
    if (confirm("Exit quiz?")) {
        // cleanup
        localStorage.removeItem('quiz_current_index')
        localStorage.removeItem('quiz_score')
        localStorage.removeItem('quiz_id')

        //go back to quizzes page
        router.back()
    }
}

const goBackToQuizzes = () => router.push('/user/quizzes')

// TIMER
onMounted(() => {
    timerInterval = setInterval(() => {
        timeElapsed.value++
    }, 1000)

    fetchQuiz()
})

onBeforeUnmount(() => {
    clearInterval(timerInterval)
})
</script>

<style scoped>
/* =========================================================
   FROSTED NOIR — QUIZ
   #FFFFFF  White
   #000000  Black
   #A9A9A9  Gray
   #D3D3D3  Light Gray
   #696969  Dark Gray
========================================================= */

.quiz-wrapper {
    --white: #ffffff;
    --black: #000000;

    --gray-light: #d3d3d3;
    --gray: #a9a9a9;
    --gray-dark: #696969;

    --page-bg: #f6f6f6;
    --surface: #ffffff;
    --surface-soft: #f8f8f8;

    --border: #d3d3d3;

    min-height: 100vh;
    min-width: 0;

    background: var(--page-bg);

    color: var(--black);

    font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;

    box-sizing: border-box;

    overflow-x: hidden;
}

.quiz-wrapper *,
.quiz-wrapper *::before,
.quiz-wrapper *::after {
    box-sizing: border-box;
}


/* =========================================================
   NAVBAR
========================================================= */

.quiz-navbar {
    position: sticky;
    top: 0;

    z-index: 100;

    background: rgba(255, 255, 255, 0.94);

    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);

    border-bottom: 1px solid var(--border);
}


/* =========================================================
   NAV CONTENT
========================================================= */

.nav-content {
    width: 100%;
    max-width: 680px;

    margin: 0 auto;

    padding: 11px 18px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 16px;
}


/* =========================================================
   NAV LEFT
========================================================= */

.nav-left {
    min-width: 0;

    display: flex;
    align-items: center;

    gap: 10px;
}


/* =========================================================
   EXIT BUTTON
========================================================= */

.btn-exit {
    width: 34px;
    height: 34px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 0;

    border: 1px solid var(--border);
    border-radius: 8px;

    background: var(--surface);

    color: var(--gray-dark);

    cursor: pointer;

    transition:
        background 0.18s ease,
        color 0.18s ease,
        border-color 0.18s ease,
        transform 0.12s ease;
}

.btn-exit:hover {
    background: var(--black);

    border-color: var(--black);

    color: var(--white);
}

.btn-exit:active {
    transform: scale(0.94);
}


/* =========================================================
   TITLE
========================================================= */

.title-stack {
    min-width: 0;

    display: flex;
    flex-direction: column;
}

.quiz-title {
    max-width: 300px;

    margin: 0;

    color: var(--black);

    font-size: 13px;

    font-weight: 750;

    line-height: 1.25;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.quiz-subtitle {
    margin-top: 2px;

    color: var(--gray-dark);

    font-size: 9px;

    font-weight: 600;

    letter-spacing: 0.03em;
}


/* =========================================================
   NAV RIGHT
========================================================= */

.nav-right {
    flex-shrink: 0;

    display: flex;
    align-items: center;

    gap: 13px;
}


/* =========================================================
   STATS
========================================================= */

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: flex-end;

    min-width: 42px;
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
    font-family:
        'JetBrains Mono',
        'Fira Code',
        Consolas,
        monospace;

    letter-spacing: 0.02em;
}


/* =========================================================
   VERTICAL DIVIDER
========================================================= */

.v-divider {
    width: 1px;
    height: 25px;

    background: var(--border);
}


/* =========================================================
   PROGRESS
========================================================= */

.progress-track {
    width: 100%;
    height: 3px;

    background: #e8e8e8;

    overflow: hidden;
}

.progress-fill {
    height: 100%;

    background: var(--black);

    transition: width 0.35s ease;
}


/* =========================================================
   MAIN
========================================================= */

.container {
    width: 100%;
    max-width: 600px;

    margin: 0 auto;

    padding:
        clamp(22px, 5vw, 40px) clamp(12px, 4vw, 20px) 50px;

    box-sizing: border-box;
}


/* =========================================================
   QUESTION CARD
========================================================= */

.question-card {
    width: 100%;

    background: var(--surface);

    border: 1px solid var(--border);

    border-radius: 14px;

    padding: clamp(18px, 4vw, 26px);

    box-shadow:
        0 5px 18px rgba(0, 0, 0, 0.045);
}


/* =========================================================
   QUESTION IMAGE
========================================================= */

.image-container {
    width: 100%;
}

.question-image {
    display: block;

    width: auto;
    max-width: 100%;
    max-height: 190px;

    margin: 0 auto 18px;

    object-fit: contain;

    border-radius: 8px;
}


/* =========================================================
   QUESTION TEXT
========================================================= */

.question-text {
    margin: 0 0 20px;

    color: var(--black);

    font-size: clamp(16px, 2.5vw, 19px);

    font-weight: 700;

    line-height: 1.45;

    text-align: left;

    overflow-wrap: anywhere;
}


/* =========================================================
   HINT
========================================================= */

.hint-box {
    display: flex;
    align-items: flex-start;

    gap: 8px;

    margin-bottom: 18px;

    padding: 10px 12px;

    background: #f4f4f4;

    border-left: 3px solid var(--gray-dark);

    border-radius: 6px;

    color: var(--gray-dark);

    font-size: 11px;

    line-height: 1.5;
}


/* =========================================================
   OPTIONS
========================================================= */

.options-grid {
    display: grid;

    gap: 9px;

    width: 100%;
}

.hidden-radio {
    position: absolute;

    opacity: 0;

    pointer-events: none;
}


/* =========================================================
   OPTION CARD
========================================================= */

.option-card {
    width: 100%;
    min-width: 0;

    display: flex;
    align-items: center;

    gap: 11px;

    padding: 11px 12px;

    background: var(--white);

    border: 1px solid var(--border);

    border-radius: 9px;

    cursor: pointer;

    transition:
        border-color 0.15s ease,
        background 0.15s ease,
        box-shadow 0.15s ease;
}

.option-card:hover {
    border-color: var(--gray-dark);

    background: #fafafa;
}


/* =========================================================
   OPTION LETTER
========================================================= */

.option-index {
    width: 27px;
    height: 27px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #eeeeee;

    border: 1px solid #e0e0e0;

    border-radius: 6px;

    color: var(--gray-dark);

    font-size: 10px;

    font-weight: 800;

    transition:
        background 0.15s ease,
        color 0.15s ease,
        border-color 0.15s ease;
}


/* =========================================================
   OPTION TEXT
========================================================= */

.option-content {
    min-width: 0;

    color: #303030;

    font-size: 12px;

    font-weight: 550;

    line-height: 1.45;

    overflow-wrap: anywhere;
}


/* =========================================================
   SELECTED OPTION
========================================================= */

.hidden-radio:checked+.option-card {
    background: #f2f2f2;

    border-color: var(--black);

    box-shadow:
        0 0 0 1px var(--black);
}

.hidden-radio:checked+.option-card .option-index {
    background: var(--black);

    border-color: var(--black);

    color: var(--white);
}

.hidden-radio:checked+.option-card .option-content {
    color: var(--black);

    font-weight: 650;
}


/* =========================================================
   ACTION BAR
========================================================= */

.action-bar {
    width: 100%;

    margin-top: 22px;

    padding-top: 17px;

    border-top: 1px solid #e8e8e8;

    display: flex;

    justify-content: flex-end;
}


/* =========================================================
   SUBMIT BUTTON
========================================================= */

.btn-submit {
    min-height: 38px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    gap: 7px;

    padding: 9px 16px;

    border: 1px solid var(--black);

    border-radius: 7px;

    background: var(--black);

    color: var(--white);

    font-family: inherit;

    font-size: 11px;

    font-weight: 700;

    cursor: pointer;

    transition:
        background 0.18s ease,
        border-color 0.18s ease,
        transform 0.12s ease;
}

.btn-submit:hover:not(:disabled) {
    background: var(--gray-dark);

    border-color: var(--gray-dark);
}

.btn-submit:active:not(:disabled) {
    transform: scale(0.97);
}

.btn-submit:disabled {
    background: #d3d3d3;

    border-color: #d3d3d3;

    color: #ffffff;

    cursor: not-allowed;
}


/* =========================================================
   STATE CARD
========================================================= */

.state-card {
    width: 100%;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    gap: 7px;

    padding: 40px 24px;

    background: var(--white);

    border: 1px solid var(--border);

    border-radius: 14px;

    text-align: center;

    box-shadow:
        0 5px 18px rgba(0, 0, 0, 0.04);
}

.state-card p {
    margin: 0;

    color: var(--gray-dark);

    font-size: 12px;
}

.state-card h2 {
    margin: 5px 0 0;

    color: var(--black);

    font-size: 16px;

    font-weight: 750;
}

.state-card small {
    margin-bottom: 8px;

    color: var(--gray-dark);

    font-size: 11px;
}


/* =========================================================
   ERROR
========================================================= */

.state-card.error {
    border-color: #d3d3d3;

    background: #fafafa;
}

.state-card.error .icon {
    width: 42px;
    height: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #eeeeee;

    border-radius: 50%;

    font-size: 18px;
}


/* =========================================================
   RETURN BUTTON
========================================================= */

.btn-primary {
    padding: 9px 14px;

    border: 1px solid var(--black);

    border-radius: 7px;

    background: var(--black);

    color: var(--white);

    font-family: inherit;

    font-size: 11px;

    font-weight: 700;

    cursor: pointer;

    transition:
        background 0.18s ease,
        transform 0.12s ease;
}

.btn-primary:hover {
    background: var(--gray-dark);
}

.btn-primary:active {
    transform: scale(0.97);
}


/* =========================================================
   LOADER
========================================================= */

.loader {
    width: 30px;
    height: 30px;

    margin-bottom: 5px;

    border: 3px solid #e5e5e5;

    border-top-color: var(--black);

    border-radius: 50%;

    animation: rotation 0.8s linear infinite;
}


/* =========================================================
   TRANSITIONS
========================================================= */

.slide-fade-enter-active {
    transition:
        opacity 0.22s ease,
        transform 0.22s ease;
}

.slide-fade-leave-active {
    transition:
        opacity 0.14s ease,
        transform 0.14s ease;
}

.slide-fade-enter-from {
    opacity: 0;

    transform: translateX(12px);
}

.slide-fade-leave-to {
    opacity: 0;

    transform: translateX(-12px);
}


.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}


@keyframes rotation {
    to {
        transform: rotate(360deg);
    }
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 768px) {

    .nav-content {
        padding: 10px 15px;
    }

    .container {
        max-width: 580px;
    }

    .question-card {
        border-radius: 12px;
    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 600px) {

    .nav-content {
        padding: 9px 12px;

        gap: 10px;
    }

    .nav-left {
        gap: 8px;
    }

    .btn-exit {
        width: 32px;
        height: 32px;
    }

    .quiz-title {
        max-width: 150px;

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
        padding:
            16px 10px 35px;
    }

    .question-card {
        padding: 16px;

        border-radius: 11px;
    }

    .question-image {
        max-height: 150px;

        margin-bottom: 14px;
    }

    .question-text {
        margin-bottom: 16px;

        font-size: 15px;

        line-height: 1.45;
    }

    .hint-box {
        margin-bottom: 15px;

        font-size: 10px;
    }

    .option-card {
        padding: 10px;

        gap: 9px;

        border-radius: 8px;
    }

    .option-index {
        width: 25px;
        height: 25px;

        font-size: 9px;
    }

    .option-content {
        font-size: 11px;
    }

    .action-bar {
        margin-top: 18px;

        padding-top: 14px;
    }

    .btn-submit {
        width: 100%;

        min-height: 40px;
    }

}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 400px) {

    .nav-content {
        padding-left: 10px;
        padding-right: 10px;
    }

    .quiz-title {
        max-width: 120px;

        font-size: 11px;
    }

    .stat-item {
        min-width: 35px;
    }

    .nav-right {
        gap: 6px;
    }

    .container {
        padding:
            12px 8px 30px;
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

}


/* =========================================================
   VERY SMALL DEVICES
========================================================= */

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

}


/* =========================================================
   REDUCED MOTION
========================================================= */

@media (prefers-reduced-motion: reduce) {

    .quiz-wrapper *,
    .quiz-wrapper *::before,
    .quiz-wrapper *::after {
        transition: none !important;
        animation: none !important;
    }

}
</style>