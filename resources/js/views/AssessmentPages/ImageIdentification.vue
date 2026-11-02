<template>
    <div class="quiz-page">
        <!-- HEADER -->
        <header class="quiz-header">
            <button class="back-btn" type="button" aria-label="Go back" @click="router.back()">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
            </button>

            <div class="header-title">
                <span class="eyebrow">Image identification</span>
                <h1>Component identification</h1>
            </div>

            <div class="progress-info" :aria-label="`${answeredCount} of ${items.length} answered`">
                <span class="progress-count">{{ answeredCount }}/{{ items.length }}</span>
                <div class="progress-bar" aria-hidden="true">
                    <div class="progress-fill" :style="{ width: progress + '%' }"></div>
                </div>
            </div>
        </header>

        <main class="quiz-container">
            <!-- LOADING -->
            <section v-if="loading" class="state-card" role="status" aria-live="polite">
                <span class="spinner" aria-hidden="true"></span>
                <p>Loading component identification…</p>
            </section>

            <!-- ERROR -->
            <section v-else-if="errorMessage" class="state-card state-error" role="alert">
                <span class="state-icon" aria-hidden="true">
                    <i class="fas fa-triangle-exclamation"></i>
                </span>
                <p>{{ errorMessage }}</p>
                <button type="button" class="btn btn-outline" @click="getQuizData(quizId)">
                    <i class="fas fa-rotate-right" aria-hidden="true"></i>
                    Try again
                </button>
            </section>

            <template v-else-if="items.length">
                <div class="layout">
                    <!-- IMAGE -->
                    <section class="card media-card" aria-label="Image to identify">
                        <div class="card-head">
                            <span class="label">Image {{ selectedItemIndex + 1 }}</span>
                            <span class="chip" :class="{ 'chip-done': answered }">
                                {{ answered ? "Answered" : "Unanswered" }}
                            </span>
                        </div>

                        <div class="image-frame">
                            <img v-if="selectedItem?.image_url" :src="selectedItem.image_url"
                                alt="Image to identify" class="component-image" />

                            <div v-else class="image-fallback">
                                <i class="fas fa-image" aria-hidden="true"></i>
                                <span>No image available</span>
                            </div>
                        </div>
                    </section>

                    <!-- ANSWER -->
                    <section class="card answer-card">
                        <div class="card-head">
                            <span class="label">Question {{ selectedItemIndex + 1 }} of {{ items.length }}</span>
                        </div>

                        <h2 class="question-title">Identify the purpose of the image shown.</h2>
                        <p class="question-help">Select the description that best matches the image.</p>

                        <div class="options" role="radiogroup" aria-label="Answer options">
                            <button v-for="(option, idx) in selectedItem?.options || []" :key="option.id" type="button"
                                class="option" role="radio"
                                :aria-checked="currentSelectedAnswer === option.id ? 'true' : 'false'"
                                :class="{ chosen: currentSelectedAnswer === option.id }"
                                :disabled="submitting || answered" @click="selectAnswer(option)">
                                <span class="option-letter" aria-hidden="true">
                                    {{ String.fromCharCode(65 + idx) }}
                                </span>
                                <span class="option-text">{{ option.description }}</span>
                            </button>
                        </div>

                        <div class="answer-footer">
                            <p class="answer-note">
                                <template v-if="!answered">Choose one answer to continue.</template>
                                <template v-else-if="nextItem">Answer saved.</template>
                                <template v-else>Answer saved. All items are answered.</template>
                            </p>

                            <button v-if="!answered" type="button" class="btn btn-primary"
                                :disabled="!currentSelectedAnswer || submitting" @click="confirmCurrentAnswer">
                                <span>{{ submitting ? "Saving…" : "Next question" }}</span>
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </button>

                            <button v-else-if="nextItem" type="button" class="btn btn-primary"
                                @click="selectItem(nextItem)">
                                <span>Next question</span>
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </button>
                        </div>
                    </section>
                </div>

                <!-- FINISH BAR -->
                <footer class="finish-bar">
                    <p class="finish-status">
                        <span class="status-dot" :class="{ 'status-dot-done': allAnswered }" aria-hidden="true"></span>
                        {{ allAnswered ? "All items answered" : `${remaining} remaining` }}
                    </p>

                    <button type="button" class="btn btn-primary" :disabled="!allAnswered || submitting"
                        @click="finishLab">
                        {{ submitting ? "Submitting…" : "Finish assessment" }}
                    </button>
                </footer>
            </template>

            <!-- EMPTY -->
            <section v-else class="state-card">
                <span class="state-icon" aria-hidden="true">
                    <i class="fas fa-image"></i>
                </span>
                <h2>No items to identify</h2>
                <p>This assessment doesn't have any reference images yet.</p>
            </section>
        </main>

        <!-- RESULT -->
        <div v-if="finished" class="result-overlay" role="dialog" aria-modal="true" aria-labelledby="result-title">
            <section class="result-dialog">
                <span class="result-icon" aria-hidden="true">
                    <i class="fas fa-check"></i>
                </span>

                <span class="eyebrow">Assessment complete</span>
                <h2 id="result-title">Image identification</h2>
                <p class="result-lede">You answered all {{ items.length }} items.</p>

                <div class="result-score">
                    <strong>{{ score }}</strong>
                    <span>/ {{ items.length }}</span>
                </div>

                <dl class="result-stats">
                    <div>
                        <dt>Correct</dt>
                        <dd>{{ score }}</dd>
                    </div>
                    <div>
                        <dt>Incorrect</dt>
                        <dd>{{ items.length - score }}</dd>
                    </div>
                </dl>

                <button type="button" class="btn btn-primary btn-block"
                    @click="router.push(`/user/assessments/assessment/${quizId}`)">
                    Return to assessment
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </button>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { imageIdentificationRequest } from '@/assessmentrequests/imageIdentification'

const route = useRoute()
const router = useRouter()

const items = ref([])
const selectedItem = ref(null)
const currentSelectedAnswer = ref(null)
const collectedAnswers = ref([])
const attemptToken = ref(null)
const score = ref(0)
const submitting = ref(false)
const finished = ref(false)
const loading = ref(true)
const errorMessage = ref('')
const startedAt = ref(null)

const quizId = computed(() => Number(route.params.id))

const selectedItemIndex = computed(() => {
    if (!selectedItem.value) return 0
    const index = items.value.findIndex(item => item.id === selectedItem.value.id)
    return index >= 0 ? index : 0
})

const answeredCount = computed(() => collectedAnswers.value.length)

const remaining = computed(() => items.value.length - answeredCount.value)

const allAnswered = computed(() => items.value.length > 0 && remaining.value === 0)

const progress = computed(() => {
    if (!items.value.length) return 0
    return (answeredCount.value / items.value.length) * 100
})

const hasAnsweredQuestion = (itemId) =>
    collectedAnswers.value.some(answer => answer.question_id === itemId)

const answered = computed(() =>
    selectedItem.value ? hasAnsweredQuestion(selectedItem.value.id) : false
)

const getAnswerForQuestion = (itemId) =>
    collectedAnswers.value.find(answer => answer.question_id === itemId)

const nextItem = computed(() =>
    items.value.find(item => !hasAnsweredQuestion(item.id))
)

const selectItem = (item) => {
    selectedItem.value = item
    const existingAnswer = getAnswerForQuestion(item.id)
    currentSelectedAnswer.value = existingAnswer?.answer_id ?? null
}

const selectAnswer = (option) => {
    if (!selectedItem.value || submitting.value || finished.value) return
    if (hasAnsweredQuestion(selectedItem.value.id)) return

    currentSelectedAnswer.value = option.id
}

const confirmCurrentAnswer = () => {
    if (!selectedItem.value || currentSelectedAnswer.value == null) return
    if (hasAnsweredQuestion(selectedItem.value.id)) return

    collectedAnswers.value = [
        ...collectedAnswers.value,
        {
            question_id: selectedItem.value.id,
            answer_id: currentSelectedAnswer.value,
        },
    ]

    if (nextItem.value) {
        selectItem(nextItem.value)
    }
}

const finishLab = async () => {
    if (submitting.value || !allAnswered.value) return

    submitting.value = true
    errorMessage.value = ''

    const elapsedTime = startedAt.value
        ? Math.round((Date.now() - startedAt.value) / 1000)
        : 0

    try {
        const response = await imageIdentificationRequest.answer(
            quizId.value,
            attemptToken.value,
            collectedAnswers.value,
            elapsedTime
        )

        score.value = response.data.correct
        finished.value = true
    } catch (error) {
        console.error('Error submitting assessment:', error)
        errorMessage.value = 'Error submitting assessment. Please try again.'
    } finally {
        submitting.value = false
    }
}

const getQuizData = async (id) => {
    if (!Number.isFinite(id) || id <= 0) {
        items.value = []
        selectedItem.value = null
        loading.value = false
        errorMessage.value = 'Invalid quiz ID.'
        return
    }

    loading.value = true
    errorMessage.value = ''
    items.value = []
    selectedItem.value = null
    currentSelectedAnswer.value = null
    collectedAnswers.value = []
    attemptToken.value = null
    score.value = 0
    finished.value = false

    try {
        const { data } = await imageIdentificationRequest.fetch(id)
        attemptToken.value = data.attempt_token

        items.value = (data.questions || []).map(item => ({
            id: item.id,
            question_text: 'Identify the use of the image shown.',
            image_url: item.image?.image_url ?? item.image_url ?? '',
            options: (item.options || []).map(option => ({
                id: option.id,
                description: option.description,
            })),
        }))

        if (items.value.length) {
            startedAt.value = Date.now()
            selectItem(items.value[0])
        }
    } catch (error) {
        console.error('Error fetching image identification quiz:', error)
        errorMessage.value = 'Error fetching the quiz. Please try again.'
    } finally {
        loading.value = false
    }
}

watch(
    () => route.params.id,
    id => getQuizData(Number(id)),
    { immediate: true }
)
</script>

<style scoped>
.quiz-page {
    --ink: #111111;
    --muted: #6b6f76;
    --line: #e3e5e8;
    --surface: #ffffff;
    --canvas: #f5f6f8;
    --soft: #fafbfc;
    --success: #1f7a4d;

    min-height: 100vh;
    padding-bottom: 96px;
    background: var(--canvas);
    color: var(--ink);
    font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    font-size: 14px;
    line-height: 1.5;
}

.quiz-page *,
.quiz-page *::before,
.quiz-page *::after {
    box-sizing: border-box;
}

.quiz-page h1,
.quiz-page h2,
.quiz-page p,
.quiz-page dl,
.quiz-page dd {
    margin: 0;
}

.quiz-page button {
    font-family: inherit;
}

/* HEADER */

.quiz-header {
    position: sticky;
    top: 0;
    z-index: 30;
    display: flex;
    align-items: center;
    gap: 14px;
    min-height: 64px;
    padding: 10px 24px;
    border-bottom: 1px solid var(--line);
    background: rgb(255 255 255 / 92%);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
}

.back-btn {
    width: 36px;
    height: 36px;
    flex: 0 0 36px;
    display: grid;
    place-items: center;
    border: 1px solid var(--line);
    border-radius: 9px;
    background: var(--surface);
    color: var(--muted);
    cursor: pointer;
    transition: border-color 0.15s ease, color 0.15s ease;
}

.back-btn:hover {
    border-color: var(--ink);
    color: var(--ink);
}

.header-title {
    min-width: 0;
    flex: 1;
}

.eyebrow {
    display: block;
    color: var(--muted);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.quiz-header h1 {
    overflow: hidden;
    font-size: 15px;
    font-weight: 650;
    letter-spacing: -0.01em;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.progress-info {
    width: 170px;
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    gap: 10px;
}

.progress-count {
    min-width: 36px;
    color: var(--muted);
    font-size: 12px;
    font-weight: 650;
    font-variant-numeric: tabular-nums;
    text-align: right;
    white-space: nowrap;
}

.progress-bar {
    height: 4px;
    flex: 1;
    overflow: hidden;
    border-radius: 999px;
    background: var(--line);
}

.progress-fill {
    height: 100%;
    border-radius: inherit;
    background: var(--ink);
    transition: width 0.3s ease;
}

/* LAYOUT */

.quiz-container {
    width: min(1040px, calc(100% - 40px));
    margin: 0 auto;
    padding-top: 28px;
}

.layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    align-items: stretch;
    gap: 18px;
}

.card {
    min-width: 0;
    display: flex;
    flex-direction: column;
    padding: 18px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: var(--surface);
    box-shadow: 0 1px 2px rgb(0 0 0 / 3%);
}

.card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 14px;
}

.label {
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.chip {
    padding: 3px 9px;
    border: 1px solid var(--line);
    border-radius: 999px;
    color: var(--muted);
    font-size: 11px;
    font-weight: 650;
    white-space: nowrap;
}

.chip-done {
    border-color: #bfe3cf;
    background: #f0faf4;
    color: var(--success);
}

/* MEDIA */

.media-card {
    padding-bottom: 14px;
}

.image-frame {
    position: relative;
    flex: 1;
    min-height: 300px;
    display: grid;
    place-items: center;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: var(--soft);
}

.component-image {
    width: 100%;
    height: 100%;
    max-height: 380px;
    padding: 24px;
    object-fit: contain;
}

.image-fallback {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    font-size: 12px;
}

.image-fallback i {
    color: #b3b7bd;
    font-size: 28px;
}

/* ANSWER */

.answer-card {
    padding: 22px;
}

.question-title {
    font-size: clamp(18px, 2vw, 22px);
    font-weight: 700;
    line-height: 1.3;
    letter-spacing: -0.01em;
}

.question-help {
    margin-top: 6px;
    color: var(--muted);
    font-size: 13px;
}

.options {
    display: grid;
    gap: 8px;
    margin-top: 18px;
}

.option {
    position: relative;
    width: 100%;
    min-height: 54px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: var(--surface);
    color: var(--ink);
    font-size: 13px;
    text-align: left;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}

.option:hover:not(:disabled) {
    border-color: #9da2a9;
    background: var(--soft);
}

.option.chosen {
    border-color: var(--ink);
    background: var(--soft);
    box-shadow: inset 0 0 0 1px var(--ink);
}

.option:disabled {
    cursor: default;
}

.option:disabled:not(.chosen) {
    opacity: 0.55;
}

.option-letter {
    width: 26px;
    height: 26px;
    flex: 0 0 26px;
    display: grid;
    place-items: center;
    border: 1px solid var(--line);
    border-radius: 7px;
    background: var(--surface);
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.option.chosen .option-letter {
    border-color: var(--ink);
    background: var(--ink);
    color: #ffffff;
}

.option-text {
    min-width: 0;
    flex: 1;
    line-height: 1.45;
    overflow-wrap: anywhere;
}

.option-check {
    flex: 0 0 auto;
    color: var(--ink);
    font-size: 12px;
}

.answer-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid var(--line);
}

.answer-note {
    min-width: 0;
    color: var(--muted);
    font-size: 12px;
}

/* BUTTONS */

.btn {
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 16px;
    border: 1px solid var(--ink);
    border-radius: 9px;
    font-size: 13px;
    font-weight: 650;
    white-space: nowrap;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease, transform 0.12s ease;
}

.btn:active:not(:disabled) {
    transform: scale(0.98);
}

.btn:disabled {
    cursor: not-allowed;
    opacity: 0.4;
}

.btn-primary {
    background: var(--ink);
    color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
    background: #333639;
    border-color: #333639;
}

.btn-outline {
    background: var(--surface);
    color: var(--ink);
}

.btn-outline:hover:not(:disabled) {
    background: var(--soft);
}

.btn-block {
    width: 100%;
}

/* FINISH BAR */

.finish-bar {
    position: fixed;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 40;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 12px 24px;
    border-top: 1px solid var(--line);
    background: rgb(255 255 255 / 94%);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
}

.finish-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    font-size: 12px;
    font-weight: 600;
}

.status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #b3b7bd;
}

.status-dot-done {
    background: var(--success);
}

/* STATES */

.state-card {
    margin-top: 24px;
    min-height: 240px;
    padding: 28px 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: var(--surface);
    color: var(--muted);
    text-align: center;
}

.state-card h2 {
    color: var(--ink);
    font-size: 16px;
    font-weight: 700;
}

.state-card p {
    max-width: 360px;
    font-size: 13px;
}

.state-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: var(--soft);
    color: var(--muted);
    font-size: 16px;
}

.state-error .state-icon {
    background: #fdf1f2;
    color: #b42318;
}

.spinner {
    width: 26px;
    height: 26px;
    border: 3px solid var(--line);
    border-top-color: var(--ink);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* RESULT */

.result-overlay {
    position: fixed;
    inset: 0;
    z-index: 100;
    display: grid;
    place-items: center;
    padding: 18px;
    background: rgb(0 0 0 / 45%);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

.result-dialog {
    width: min(380px, 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 28px 24px 24px;
    border: 1px solid var(--line);
    border-radius: 16px;
    background: var(--surface);
    box-shadow: 0 20px 60px rgb(0 0 0 / 18%);
    text-align: center;
}

.result-icon {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    margin-bottom: 8px;
    border-radius: 50%;
    background: var(--ink);
    color: #ffffff;
    font-size: 18px;
}

.result-dialog h2 {
    font-size: 19px;
    font-weight: 700;
    letter-spacing: -0.02em;
}

.result-lede {
    color: var(--muted);
    font-size: 13px;
}

.result-score {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 6px;
    margin: 14px 0 6px;
}

.result-score strong {
    font-size: 44px;
    font-weight: 750;
    letter-spacing: -0.04em;
    line-height: 1;
}

.result-score span {
    color: var(--muted);
    font-size: 14px;
}

.result-stats {
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin: 12px 0 16px;
}

.result-stats > div {
    padding: 10px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: var(--soft);
}

.result-stats dt {
    color: var(--muted);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.result-stats dd {
    margin-top: 2px;
    font-size: 16px;
    font-weight: 700;
}

/* RESPONSIVE */

@media (max-width: 860px) {
    .layout {
        grid-template-columns: minmax(0, 1fr);
    }

    .image-frame {
        min-height: 240px;
    }

    .component-image {
        max-height: 260px;
    }
}

@media (max-width: 600px) {
    .quiz-header {
        min-height: 58px;
        flex-wrap: wrap;
        gap: 10px;
        padding: 10px 14px;
    }

    .progress-info {
        width: 100%;
        order: 3;
    }

    .quiz-container {
        width: calc(100% - 24px);
        padding-top: 18px;
    }

    .card {
        padding: 14px;
    }

    .answer-card {
        padding: 16px;
    }

    .answer-footer {
        flex-direction: column;
        align-items: stretch;
    }

    .answer-footer .btn {
        width: 100%;
    }

    .answer-note {
        text-align: center;
    }

    .finish-bar {
        padding: 10px 14px;
    }

    .finish-status {
        font-size: 11px;
    }

    .finish-bar .btn {
        min-height: 38px;
        padding: 0 12px;
    }
}

@media (max-width: 380px) {
    .quiz-container {
        width: calc(100% - 18px);
    }

    .question-title {
        font-size: 17px;
    }

    .option {
        padding: 9px 11px;
    }

    .finish-status {
        display: none;
    }

    .finish-bar {
        justify-content: flex-end;
    }
}

button:focus-visible {
    outline: 2px solid var(--ink);
    outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {

    .quiz-page *,
    .quiz-page *::before,
    .quiz-page *::after {
        animation: none !important;
        transition: none !important;
        scroll-behavior: auto !important;
    }
}
</style>