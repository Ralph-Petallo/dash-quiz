<template>
    <div class="quiz-wrapper">
        <!-- Navbar -->
        <header class="quiz-navbar">
            <div class="nav-content">
                <div class="nav-left">
                    <button @click="confirmExit" class="btn-exit" title="Exit Quiz">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
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
                        <span class="stat-label">Questions</span>
                        <span class="stat-value">{{ currentIndex + 1 }}/{{ questions.length }}</span>
                    </div>
                    <div class="v-divider"></div>
                    <div class="stat-item timer">
                        <span class="stat-label">Elapsed</span>
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
                    <p>Preparing your questions...</p>
                </div>

                <div v-else-if="error" class="state-card error">
                    <div class="icon">⚠️</div>
                    <h2>Unable to load quiz</h2>
                    <small>You can ask the admins.</small>
                    <button @click="goBackToQuizzes" class="btn-primary">Return to Dashboard</button>
                </div>

                <div v-else-if="questions.length" class="active-quiz">
                    <transition name="slide-fade" mode="out-in">
                        <div class="question-card" :key="currentIndex">
                            <h2 class="question-text">{{ currentQuestion.text }}</h2>

                            <!-- Hint Box -->
                            <div v-if="currentQuestion.hint" class="hint-box">
                                💡 Hint: {{ currentQuestion.hint }}
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
                                        width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
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
const submitting = ref(false)

const currentIndex = ref(parseInt(localStorage.getItem('quiz_current_index')) || 0)
const selectedAnswer = ref(null)
const score = ref(parseInt(localStorage.getItem('quiz_score')) || 0)

const loading = ref(true)
const error = ref(null)

const timeElapsed = ref(0)
const answers = ref([]) // ✅ FIX: store ALL answers properly

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

    submitting.value = true

    try {
        const { data } = await axios.post('/api/quiz/answer', {
            question_id: currentQuestion.value.id,
            answer_id: selectedAnswer.value
        })

        // ✅ SAVE ANSWER (IMPORTANT FOR FINAL RESULT)
        answers.value.push({
            question_id: currentQuestion.value.id,
            answer_id: selectedAnswer.value
        })

        if (data.correct) {
            score.value++
            localStorage.setItem('quiz_score', score.value)
        }

        selectedAnswer.value = null

        if (currentIndex.value < questions.value.length - 1) {
            currentIndex.value++
            localStorage.setItem('quiz_current_index', currentIndex.value)
        } else {
            await submitQuizResult()
        }

    } finally {
        submitting.value = false
    }
}

// SUBMIT FINAL RESULT (ALIGNED TO CONTROLLER)
const submitQuizResult = async () => {
    try {
        const { data } = await axios.post('/api/quiz/result', {
            quiz_id: route.params.quiz_id,
            score: score.value,
            elapsed_time: timeElapsed.value,

            // ✅ REQUIRED BY CONTROLLER
            answers: answers.value
        })

        // cleanup
        localStorage.removeItem('quiz_current_index')
        localStorage.removeItem('quiz_score')
        localStorage.removeItem('quiz_id')

        // ✅ FIXED: controller returns record_id (NOT record.id)
        router.replace(`/quiz-result/${data.record_id}`)

    } catch (err) {
        console.error(err)
    }
}

// EXIT
const confirmExit = () => {
    if (confirm("Exit quiz?")) {
        localStorage.clear()
        router.push('/user/quizzes')
    }
}

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
/* Color Palette */
:root {
    --primary: #6366f1;
    --primary-soft: #eef2ff;
    --text-dark: #1e293b;
    --text-light: #64748b;
    --bg-page: #f8fafc;
    --white: #ffffff;
}

.quiz-wrapper {
    background-color: #f8fafc;
    min-height: 100vh;
    font-family: 'Inter', system-ui, sans-serif;
}

.btn-primary {
    border-radius: 10px;
    border: 1px solid;
    background-color: #4338ca;
    color: #fff;
    font-weight: 600;
    font-size: 16px;
    padding: 10px;
}

.btn-primary:hover {
    background-color: #6366f1;
}

/* Navbar & Progress */
.quiz-navbar {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(12px);
    /* Blurred glass effect */
    position: sticky;
    top: 0;
    z-index: 100;
    border-bottom: 1px solid #e2e8f0;
}

.nav-content {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0.75rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.quiz-info {
    background: #000;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

/* Left Section */
.nav-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.quiz-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    line-height: 1.2;
}

.btn-exit {
    background: #f1f5f9;
    border: none;
    color: #64748b;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.quiz-subtitle {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
}

/* Right Section */
.nav-right {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.count-badge {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--primary);
    background: var(--primary-soft);
    padding: 4px 12px;
    border-radius: 20px;
}

.icon {
    font-size: 80px;
}

.stat-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
    font-weight: 600;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

/* Slimmed Progress Bar */
.progress-track {
    width: 100%;
    height: 3px;
    background: transparent;
}

.font-mono {
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
}

.progress-fill {
    height: 100%;
    background: #6366f1;
    transition: width 0.4s ease;
}

/* Main Layout */
.container {
    max-width: 800px;
    margin: 3rem auto;
    padding: 0 20px;
}

.question-text {
    font-size: 1.75rem;
    line-height: 1.4;
    margin-bottom: 1.5rem;
    font-weight: 600;
    text-align: center;
    background-color: #1e1b4b;
    color: white;
    border: 1px black solid;
    padding: 15px;
    border-radius: 10px;
}

/* Options Design */
.options-grid {
    display: grid;
    gap: 1rem;
}

.stat-value {
    font-size: 0.9rem;
    font-weight: 700;
    color: #334155;
}

.v-divider {
    width: 1px;
    height: 24px;
    background: #e2e8f0;
}

.hidden-radio {
    display: none;
}

.option-card {
    display: flex;
    align-items: center;
    padding: 1.25rem;
    background: var(--white);
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.option-index {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    color: var(--text-light);
    border-radius: 8px;
    font-weight: 700;
    margin-right: 1rem;
    transition: all 0.2s ease;
}

.option-content {
    font-size: 1.1rem;
    color: var(--text-dark);
    font-weight: 500;
}

.hidden-radio:checked+.option-card {
    border-color: var(--primary);
    background-color: var(--primary-soft);
}

.hidden-radio:checked+.option-card .option-index {
    background: var(--primary);
    color: var(--white);
}

.option-card:hover:not(.hidden-radio:checked + label) {
    border-color: #cbd5e1;
    transform: translateX(4px);
}

/* Action Bar */
.action-bar {
    margin-top: 1.5rem;
    display: flex;
    justify-content: flex-end;
}

.btn-submit {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--text-dark);
    color: var(--white);
    border: none;
    padding: 1rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-submit:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.btn-submit:hover:not(:disabled) {
    transform: scale(1.02);
    color: #6366f1;
}

/* Transitions */
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.2s ease-in;
}

.slide-fade-enter-from {
    transform: translateY(20px);
    opacity: 0;
}

.slide-fade-leave-to {
    transform: translateY(-20px);
    opacity: 0;
}

.state-card {
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    padding: 2.5rem;
    background: white;
    border-radius: 24px;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

.error {
    background-image: linear-gradient(0deg, #d9c700, #e4e100, #d9c700);
}

.state-card h2 {
    margin: 0;
}

.loader {
    width: 48px;
    height: 48px;
    border: 5px solid var(--primary-soft);
    border-bottom-color: var(--primary);
    border-radius: 50%;
    display: inline-block;
    animation: rotation 1s linear infinite;
    margin-bottom: 1rem;
}

/* Hint Box */
.hint-box {
    background: #eef2ff;
    color: #4338ca;
    font-size: 0.9rem;
    padding: 10px 14px;
    border-left: 4px solid #6366f1;
    border-radius: 8px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.hint-box:hover {
    background: #e0e7ff;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .question-text {
        font-size: 1.5rem;
        padding: 12px;
    }

    .option-card {
        padding: 1rem;
    }

    .btn-submit {
        padding: 0.75rem 1.5rem;
        font-size: 0.95rem;
    }
}

@media (max-width: 480px) {
    .nav-content {
        flex-direction: column;
        gap: 1rem;
    }

    .nav-right {
        justify-content: space-between;
        width: 100%;
    }

    .question-text {
        font-size: 1.3rem;
        padding: 10px;
    }

    .option-card {
        font-size: 0.95rem;
    }
}

@keyframes rotation {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}
</style>