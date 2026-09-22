<template>
    <div class="quiz-page">

        <!-- HEADER -->
        <header class="quiz-header">

            <button class="back-btn" type="button" title="Go back" @click="router.back()">
                <i class="fas fa-arrow-left"></i>
            </button>

            <div class="header-title">
                <span class="eyebrow">IMAGE IDENTIFICATION</span>
                <h1>Component Identification</h1>
            </div>

            <div class="progress-info">
                <span>{{ collectedAnswers.length }} / {{ items.length }}</span>

                <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: `${progress}%` }"></div>
                </div>
            </div>

        </header>


        <main class="quiz-container">

            <!-- QUESTION -->
            <section class="question">

                <div class="question-meta">
                    <span>
                        ITEM {{ selectedItemIndex + 1 }}
                    </span>

                    <span v-if="selectedItem">
                        {{ hasAnsweredQuestion(selectedItem.id) ? 'ANSWERED' : 'ANSWER' }}
                    </span>
                </div>

                <h2>
                    {{ selectedItem?.question_text ||
                        'Identify the component and choose the description that best explains its function.' }}
                </h2>

                <p>
                    Examine the component shown below and select the description
                    that best matches its function.
                </p>

            </section>


            <!-- QUIZ -->
            <section v-if="items.length" class="quiz-layout">

                <!-- ITEM NAVIGATION -->
                <aside class="item-list">

                    <div class="item-list-header">
                        <span>Assessment Items</span>
                        <small>{{ collectedAnswers.length }}/{{ items.length }}</small>
                    </div>

                    <button v-for="(item, index) in items" :key="item.id" type="button" class="item-row" :class="{
                        selected: selectedItem?.id === item.id,
                        complete: hasAnsweredQuestion(item.id)
                    }" @click="selectItem(item)">

                        <span class="item-number">
                            {{ String(index + 1).padStart(2, '0') }}
                        </span>

                        <span class="item-text">

                            <strong>
                                Item {{ index + 1 }}
                            </strong>

                            <small>
                                {{
                                    hasAnsweredQuestion(item.id)
                                        ? 'Answered'
                                        : 'Not answered yet'
                                }}
                            </small>

                        </span>

                        <i v-if="hasAnsweredQuestion(item.id)" class="fas fa-check"></i>

                    </button>

                </aside>


                <!-- DETAIL -->
                <section class="detail">

                    <!-- IMAGE -->
                    <div class="image-frame">

                        <div class="image-label">
                            <i class="fas fa-image"></i>
                            Component
                        </div>

                        <img v-if="selectedItem?.image_path" :src="imageUrl(selectedItem.image_path)"
                            :alt="selectedItem.question_text || 'Component image'" class="component-image" />

                        <div v-else class="image-fallback">
                            <i class="fas fa-microchip"></i>
                            <span>No image available</span>
                        </div>

                    </div>


                    <!-- OPTIONS -->
                    <div class="answer-header">

                        <div>
                            <span class="answer-label">
                                SELECT ONE ANSWER
                            </span>

                            <p>
                                Choose the description that matches the component.
                            </p>
                        </div>

                        <span class="option-count">
                            {{ selectedItem?.options?.length || 0 }} options
                        </span>

                    </div>


                    <div class="options">

                        <button v-for="(option, idx) in selectedItem?.options || []" :key="option.id" type="button"
                            class="option" :class="{
                                chosen: currentSelectedAnswer === option.id
                            }" :disabled="submitting" @click="selectAnswer(option)">

                            <span class="option-letter">
                                {{ String.fromCharCode(65 + idx) }}
                            </span>

                            <span class="option-text">
                                {{ option.description }}
                            </span>

                        </button>

                    </div>


                    <!-- CONFIRM FOR CURRENT QUESTION -->
                    <button v-if="!hasAnsweredQuestion(selectedItem?.id)" class="confirm-btn" type="button"
                        :disabled="!currentSelectedAnswer || submitting" @click="confirmCurrentAnswer">

                        <span v-if="!submitting">
                            Save Answer
                        </span>

                        <span v-else>
                            Saving...
                        </span>

                        <i v-if="!submitting" class="fas fa-arrow-right"></i>

                    </button>

                    <!-- NEXT -->
                    <button v-else-if="nextItem" class="confirm-btn" type="button" @click="selectItem(nextItem)">
                        Next Item
                        <i class="fas fa-arrow-right"></i>
                    </button>

                </section>

            </section>


            <!-- EMPTY -->
            <section v-else class="empty-state">

                <i class="fas fa-image"></i>

                <h2>No items to identify</h2>

                <p>
                    This assessment doesn't have any reference images yet.
                </p>

            </section>


            <!-- FOOTER -->
            <section v-if="items.length" class="quiz-actions">

                <div class="action-status">

                    <span class="status-dot"></span>

                    <span>
                        {{
                            collectedAnswers.length === items.length
                                ? 'All items answered'
                                : `${items.length - collectedAnswers.length} remaining`
                        }}
                    </span>

                </div>


                <div class="action-buttons">

                    <button class="reset-btn" type="button" @click="resetLab" :disabled="submitting">
                        Reset
                    </button>

                    <button class="submit-btn" type="button"
                        :disabled="collectedAnswers.length !== items.length || submitting" @click="finishLab">
                        {{ submitting ? 'Submitting...' : 'Finish Assessment' }}
                    </button>

                </div>

            </section>

        </main>


        <!-- RESULT -->
        <div v-if="finished" class="result-overlay" role="dialog" aria-modal="true">

            <div class="result-notice">

                <div class="result-icon">
                    <i class="fas fa-check"></i>
                </div>

                <span class="result-label">
                    ASSESSMENT COMPLETE
                </span>

                <h2>Image Identification</h2>

                <p>
                    You completed all {{ items.length }} items.
                </p>


                <div class="result-score">

                    <strong>{{ score }}</strong>

                    <span>
                        / {{ items.length }}
                    </span>

                </div>


                <div class="result-summary">

                    <div>
                        <strong>{{ score }}</strong>
                        <span>Correct</span>
                    </div>

                    <div>
                        <strong>{{ items.length - score }}</strong>
                        <span>Incorrect</span>
                    </div>

                </div>


                <button type="button" @click="router.push(`/user/quizzes/assessment/${quizId}`)">
                    Return to Assessment
                    <i class="fas fa-arrow-right"></i>
                </button>

            </div>

        </div>

    </div>
</template>


<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { imageIdentificationRequest } from '@/assessmentrequests/imageIdentification'

const route = useRoute()
const router = useRouter()

const quizId = route.params.id


// =========================================================
// DATA
// =========================================================

const items = ref([])

const selectedItem = ref(null)

const currentSelectedAnswer = ref(null)

const selectedItemIndex = computed(() => {
    if (!selectedItem.value) {
        return 0
    }

    const index = items.value.findIndex(
        item => item.id === selectedItem.value.id
    )

    return index >= 0 ? index : 0
})


// =========================================================
// ANSWERS - BATCH COLLECTION
// =========================================================

const collectedAnswers = ref([])

const hasAnsweredQuestion = (questionId) => {
    return collectedAnswers.value.some(answer => answer.question_id === questionId)
}

const getAnswerForQuestion = (questionId) => {
    return collectedAnswers.value.find(answer => answer.question_id === questionId)
}


// =========================================================
// PROGRESS
// =========================================================

const progress = computed(() => {
    if (!items.value.length) {
        return 0
    }

    return (
        (collectedAnswers.value.length / items.value.length) * 100
    )
})

const score = ref(0)


// =========================================================
// SUBMISSION STATE
// =========================================================

const submitting = ref(false)

const finished = ref(false)


// =========================================================
// NEXT ITEM
// =========================================================

const nextItem = computed(() => {
    return items.value.find(
        item => !hasAnsweredQuestion(item.id)
    )
})


// =========================================================
// IMAGE
// =========================================================

const imageUrl = (path) => {
    if (!path) {
        return ''
    }

    return path.startsWith('http') || path.startsWith('/')
        ? path
        : `/storage/${path}`
}


// =========================================================
// SELECT ITEM
// =========================================================

const selectItem = (item) => {

    selectedItem.value = item

    // Load answer if already answered
    const existingAnswer = getAnswerForQuestion(item.id)
    if (existingAnswer) {
        currentSelectedAnswer.value = existingAnswer.answer_id
    } else {
        currentSelectedAnswer.value = null
    }
}


// =========================================================
// SELECT ANSWER
// =========================================================

const selectAnswer = (option) => {

    if (!selectedItem.value) {
        return
    }

    if (submitting.value) {
        return
    }

    currentSelectedAnswer.value = option.id
}


// =========================================================
// CONFIRM CURRENT ANSWER
// =========================================================

const confirmCurrentAnswer = () => {

    if (!selectedItem.value) {
        return
    }

    if (!currentSelectedAnswer.value) {
        return
    }

    if (hasAnsweredQuestion(selectedItem.value.id)) {
        return
    }

    // Add to collected answers
    collectedAnswers.value.push({
        question_id: selectedItem.value.id,
        answer_id: currentSelectedAnswer.value
    })

    // Move to next item
    if (nextItem.value) {
        selectItem(nextItem.value)
    }
}


// =========================================================
// RESET
// =========================================================

const resetLab = () => {

    collectedAnswers.value = []

    currentSelectedAnswer.value = null

    finished.value = false

    score.value = 0

    if (items.value[0]) {
        selectItem(items.value[0])
    }
}


// =========================================================
// FINISH - SUBMIT ALL ANSWERS
// =========================================================

const finishLab = async () => {

    if (
        collectedAnswers.value.length !==
        items.value.length
    ) {
        return
    }

    submitting.value = true

    try {

        /*
         * IMPORTANT:
         *
         * Submit ALL answers at once.
         * Laravel validates on the server side.
         */

        const response =
            await imageIdentificationRequest.answer(
                collectedAnswers.value
            )

        const result = response.data

        // Update score from server response
        score.value = result.correct

        // Mark as finished
        finished.value = true


    } catch (error) {

        console.error(
            'Error submitting assessment:',
            error
        )

        // Optionally show error message to user
        alert('Error submitting assessment. Please try again.')

    } finally {

        submitting.value = false

    }
}


// =========================================================
// FETCH QUIZ
// =========================================================

const getQuizData = async () => {

    try {

        const { data } =
            await imageIdentificationRequest.fetch(
                quizId
            )


        items.value = (data.questions || []).map(item => ({

            id: item.id,

            question_text:
                item.question_text ||
                'Identify this component.',

            image_path: item.image_path,

            options: (item.options || []).map(option => ({
                id: option.id,
                description: option.description
            }))

        }))


        if (items.value.length) {
            selectItem(items.value[0])
        }

    } catch (error) {

        console.error(
            'Error fetching image identification quiz:',
            error
        )

    }
}


// =========================================================
// INITIALIZE
// =========================================================

onMounted(() => {
    getQuizData()
})
</script>

<style scoped>
/* =========================================================
   NOIR ASSESSMENT
   ========================================================= */

.quiz-page {
    --black: #111111;
    --black-soft: #222222;
    --text: #181818;
    --muted: #737373;
    --light-muted: #a3a3a3;
    --border: #e5e5e5;
    --soft-border: #f0f0f0;
    --surface: #ffffff;
    --background: #fafafa;
    --success: #111111;

    min-height: 100vh;
    padding-bottom: 48px;
    background: var(--background);
    color: var(--text);

    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}


/* =========================================================
   HEADER
   ========================================================= */

.quiz-header {
    position: sticky;
    top: 0;
    z-index: 30;

    display: flex;
    align-items: center;
    gap: 14px;

    min-height: 64px;
    padding: 12px 28px;

    background: rgba(250, 250, 250, 0.88);
    border-bottom: 1px solid var(--border);

    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

.back-btn {
    width: 38px;
    height: 38px;

    display: grid;
    place-items: center;
    flex: 0 0 auto;

    border: 1px solid var(--border);
    border-radius: 10px;

    background: var(--surface);
    color: var(--muted);

    cursor: pointer;

    transition:
        color 0.18s ease,
        border-color 0.18s ease,
        background 0.18s ease;
}

.back-btn:hover:not(:disabled) {
    color: var(--black);
    border-color: var(--black);
    background: var(--surface);
}

.back-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.header-title {
    flex: 1;
    min-width: 0;
}

.header-title .eyebrow {
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
    color: var(--muted);
    text-transform: uppercase;
    margin-bottom: 2px;
}

.quiz-header h1 {
    min-width: 0;
    margin: 0;

    font-size: 15px;
    font-weight: 650;
    letter-spacing: -0.01em;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.progress-info {
    width: 190px;

    display: flex;
    align-items: center;
    gap: 10px;

    flex: 0 0 auto;

    color: var(--muted);
    font-size: 12px;
    font-weight: 600;
}

.progress-bar {
    flex: 1;
    height: 5px;

    overflow: hidden;

    border-radius: 999px;
    background: var(--border);
}

.progress-fill {
    height: 100%;

    border-radius: inherit;
    background: var(--black);

    transition: width 0.3s ease;
}


/* =========================================================
   MAIN CONTAINER
   ========================================================= */

.quiz-container {
    width: min(1180px, calc(100% - 48px));
    margin: 0 auto;
    padding: 42px 0 0;
}


/* =========================================================
   QUESTION HEADER
   ========================================================= */

.question {
    max-width: 760px;
}

.question-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;

    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: var(--muted);
    text-transform: uppercase;
}

.question h2 {
    margin: 0;

    font-size: clamp(20px, 2.2vw, 28px);
    line-height: 1.3;
    font-weight: 700;
    letter-spacing: -0.025em;
}

.question p {
    margin: 10px 0 0;

    color: var(--muted);

    font-size: 14px;
    line-height: 1.6;
}


/* =========================================================
   MAIN WORKSPACE
   ========================================================= */

.quiz-layout {
    display: grid;
    grid-template-columns: 270px minmax(0, 1fr);
    gap: 20px;

    margin-top: 30px;
}


/* =========================================================
   ITEM NAVIGATOR
   ========================================================= */

.item-list {
    align-self: start;
    position: sticky;
    top: 80px;

    display: flex;
    flex-direction: column;
    gap: 6px;

    padding: 8px;

    border: 1px solid var(--border);
    border-radius: 14px;

    background: var(--surface);
}

.item-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    padding: 10px;

    font-size: 12px;
    font-weight: 600;
    color: var(--muted);
}

.item-row {
    position: relative;

    width: 100%;
    min-height: 58px;

    display: flex;
    align-items: center;
    gap: 11px;

    padding: 9px 10px;

    border: 1px solid transparent;
    border-radius: 9px;

    background: transparent;
    color: var(--muted);

    text-align: left;
    font: inherit;

    cursor: pointer;

    transition:
        background 0.18s ease,
        color 0.18s ease,
        border-color 0.18s ease;
}

.item-row:hover:not(:disabled) {
    background: #f7f7f7;
    color: var(--text);
}

.item-row.selected {
    border-color: var(--black);
    background: var(--black);
    color: var(--surface);
}

.item-row.complete:not(.selected) {
    background: #f7f7f7;
    color: var(--text);
}

.item-row:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.item-number {
    width: 30px;
    height: 30px;

    display: grid;
    place-items: center;
    flex: 0 0 30px;

    border: 1px solid var(--border);
    border-radius: 8px;

    background: var(--surface);

    color: var(--muted);

    font-size: 11px;
    font-weight: 700;
}

.item-row.selected .item-number {
    border-color: rgba(255, 255, 255, 0.25);
    background: rgba(255, 255, 255, 0.12);
    color: var(--surface);
}

.item-text {
    min-width: 0;

    display: flex;
    flex-direction: column;
    gap: 3px;
}

.item-text strong {
    font-size: 12px;
    font-weight: 650;
}

.item-text small {
    min-width: 0;

    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;

    color: var(--light-muted);

    font-size: 11px;
}

.item-row.selected .item-text small {
    color: rgba(255, 255, 255, 0.65);
}

.item-row i {
    margin-left: auto;
    flex: 0 0 auto;

    font-size: 11px;
}


/* =========================================================
   DETAIL WORKSPACE
   ========================================================= */

.detail {
    min-width: 0;

    display: flex;
    flex-direction: column;
    gap: 16px;
}


/* =========================================================
   IMAGE
   ========================================================= */

.image-frame {
    position: relative;

    min-height: 390px;

    display: grid;
    place-items: center;

    overflow: hidden;

    border: 1px solid var(--border);
    border-radius: 16px;

    background:
        linear-gradient(135deg,
            #ffffff 0%,
            #fafafa 50%,
            #f5f5f5 100%);
}

.image-frame::before {
    content: "";

    position: absolute;
    inset: 18px;

    border: 1px dashed var(--soft-border);
    border-radius: 11px;

    pointer-events: none;
}

.image-label {
    position: absolute;
    top: 14px;
    left: 14px;

    display: flex;
    align-items: center;
    gap: 6px;

    padding: 6px 10px;

    border-radius: 6px;

    background: rgba(255, 255, 255, 0.9);
    color: var(--muted);

    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;

    z-index: 2;
}

.image-label i {
    font-size: 13px;
}

.component-image {
    position: relative;
    z-index: 1;

    width: 100%;
    height: 390px;

    object-fit: contain;

    padding: 38px;

    box-sizing: border-box;

    transition: transform 0.25s ease;
}

.image-frame:hover .component-image {
    transform: scale(1.015);
}

.image-fallback {
    position: relative;
    z-index: 1;

    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;

    color: var(--muted);

    font-size: 13px;
}

.image-fallback i {
    font-size: 36px;
    color: var(--light-muted);
}


/* =========================================================
   ANSWER SECTION
   ========================================================= */

.answer-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
}

.answer-label {
    display: block;

    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: var(--muted);
    text-transform: uppercase;

    margin-bottom: 4px;
}

.answer-header p {
    margin: 0;

    color: var(--muted);
    font-size: 13px;
    line-height: 1.5;
}

.option-count {
    flex: 0 0 auto;

    font-size: 12px;
    font-weight: 600;
    color: var(--light-muted);
}


/* =========================================================
   OPTIONS
   ========================================================= */

.options {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.option {
    min-width: 0;
    min-height: 70px;

    display: flex;
    align-items: flex-start;
    gap: 12px;

    padding: 14px;

    border: 1px solid var(--border);
    border-radius: 11px;

    background: var(--surface);
    color: var(--text);

    text-align: left;
    font: inherit;

    cursor: pointer;

    transition:
        border-color 0.18s ease,
        background 0.18s ease,
        color 0.18s ease,
        transform 0.18s ease;
}

.option:hover:not(:disabled) {
    border-color: var(--black);
    transform: translateY(-1px);
}

.option.chosen {
    border-color: var(--black);
    background: var(--black);
    color: var(--surface);
}

.option:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.option-letter {
    width: 26px;
    height: 26px;

    display: grid;
    place-items: center;
    flex: 0 0 26px;

    border: 1px solid currentColor;
    border-radius: 7px;

    font-size: 11px;
    font-weight: 700;
}

.option.chosen .option-letter {
    background: var(--surface);
    color: var(--black);
}

.option-text {
    min-width: 0;

    padding-top: 2px;

    font-size: 13px;
    line-height: 1.5;
}


/* =========================================================
   CONFIRM
   ========================================================= */

.confirm-btn {
    align-self: flex-end;

    min-width: 145px;
    min-height: 44px;

    padding: 0 22px;

    border: 1px solid var(--black);
    border-radius: 9px;

    background: var(--black);
    color: var(--surface);

    font: inherit;
    font-size: 13px;
    font-weight: 650;

    cursor: pointer;

    transition:
        background 0.18s ease,
        color 0.18s ease,
        transform 0.18s ease;
}

.confirm-btn:hover:not(:disabled) {
    background: var(--surface);
    color: var(--black);
    transform: translateY(-1px);
}

.confirm-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}


/* =========================================================
   FOOTER ACTIONS
   ========================================================= */

.quiz-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;

    margin-top: 22px;
    padding-top: 18px;

    border-top: 1px solid var(--border);
}

.action-status {
    display: flex;
    align-items: center;
    gap: 8px;

    color: var(--muted);

    font-size: 12px;
    font-weight: 550;
}

.status-dot {
    width: 8px;
    height: 8px;

    border-radius: 50%;
    background: var(--black);
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.reset-btn,
.submit-btn {
    min-height: 40px;

    padding: 0 17px;

    border-radius: 8px;

    font: inherit;
    font-size: 12px;
    font-weight: 650;

    cursor: pointer;

    transition:
        border-color 0.18s ease,
        background 0.18s ease,
        color 0.18s ease;
}

.reset-btn {
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--muted);
}

.reset-btn:hover:not(:disabled) {
    border-color: var(--black);
    color: var(--black);
}

.reset-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.submit-btn {
    border: 1px solid var(--black);
    background: var(--black);
    color: var(--surface);
}

.submit-btn:hover:not(:disabled) {
    background: var(--surface);
    color: var(--black);
}

.submit-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}


/* =========================================================
   EMPTY STATE
   ========================================================= */

.empty-state {
    margin-top: 30px;
    padding: 90px 24px;

    text-align: center;

    border: 1px solid var(--border);
    border-radius: 16px;

    background: var(--surface);
}

.empty-state i {
    font-size: 30px;
    color: var(--light-muted);
}

.empty-state h2 {
    margin: 16px 0 5px;

    font-size: 17px;
    font-weight: 650;
}

.empty-state p {
    margin: 0;

    color: var(--muted);

    font-size: 13px;
}


/* =========================================================
   RESULT
   ========================================================= */

.result-overlay {
    position: fixed;
    inset: 0;
    z-index: 100;

    display: grid;
    place-items: center;

    padding: 20px;

    background: rgba(0, 0, 0, 0.55);

    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
}

.result-notice {
    width: min(390px, 100%);

    padding: 34px;

    border: 1px solid var(--border);
    border-radius: 18px;

    background: var(--surface);

    text-align: center;

    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.18);

    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

.result-icon {
    width: 60px;
    height: 60px;

    margin: 0 auto 16px;

    display: grid;
    place-items: center;

    border-radius: 50%;
    background: var(--black);
    color: var(--surface);

    font-size: 28px;
}

.result-label {
    display: block;

    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    color: var(--muted);
    text-transform: uppercase;

    margin-bottom: 8px;
}

.result-notice h2 {
    margin: 0 0 5px;

    font-size: 20px;
    font-weight: 700;
    letter-spacing: -0.02em;
}

.result-notice p {
    margin: 0;

    color: var(--muted);

    font-size: 13px;
}

.result-score {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 5px;

    margin: 22px 0;
}

.result-score strong {
    font-size: 48px;
    line-height: 1;
    font-weight: 750;
    letter-spacing: -0.05em;
}

.result-score span {
    color: var(--muted);
    font-size: 14px;
}

.result-summary {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;

    margin: 24px 0;
}

.result-summary>div {
    padding: 12px;

    border-radius: 10px;
    background: var(--background);
}

.result-summary strong {
    display: block;

    font-size: 18px;
    margin-bottom: 4px;
}

.result-summary span {
    display: block;

    font-size: 11px;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.result-notice button {
    width: 100%;
    min-height: 44px;

    border: 1px solid var(--black);
    border-radius: 9px;

    background: var(--black);
    color: var(--surface);

    font: inherit;
    font-size: 13px;
    font-weight: 650;

    cursor: pointer;

    transition:
        background 0.18s ease,
        color 0.18s ease;
}

.result-notice button:hover {
    background: var(--surface);
    color: var(--black);
}


/* =========================================================
   TABLET (900px)
   ========================================================= */

@media (max-width: 900px) {

    .quiz-container {
        width: min(100% - 32px, 760px);
    }

    .quiz-layout {
        grid-template-columns: 200px minmax(0, 1fr);
        gap: 16px;
    }

    .image-frame,
    .component-image {
        min-height: 330px;
        height: 330px;
    }

    .options {
        grid-template-columns: 1fr;
    }

    .item-list {
        top: 76px;
    }
}


/* =========================================================
   TABLET SMALL (768px)
   ========================================================= */

@media (max-width: 768px) {

    .quiz-header {
        min-height: 60px;
        padding: 10px 16px;
    }

    .progress-info {
        width: 170px;
    }

    .quiz-container {
        width: calc(100% - 24px);
        padding-top: 32px;
    }

    .quiz-layout {
        grid-template-columns: 160px minmax(0, 1fr);
        gap: 14px;
    }

    .image-frame,
    .component-image {
        min-height: 300px;
        height: 300px;
    }

    .confirm-btn {
        min-width: 120px;
    }
}


/* =========================================================
   MOBILE (700px)
   ========================================================= */

@media (max-width: 700px) {

    .quiz-page {
        padding-bottom: 80px;
    }

    /* Header */

    .quiz-header {
        min-height: 58px;

        flex-wrap: wrap;

        gap: 10px;

        padding: 10px 14px;
    }

    .back-btn {
        width: 34px;
        height: 34px;
        font-size: 14px;
    }

    .header-title {
        flex: 1;
    }

    .header-title .eyebrow {
        font-size: 9px;
        margin-bottom: 1px;
    }

    .quiz-header h1 {
        font-size: 14px;
    }

    .progress-info {
        width: 100%;

        order: 3;

        gap: 8px;
        padding-left: 14px;
    }

    /* Main */

    .quiz-container {
        width: calc(100% - 24px);

        padding-top: 24px;
    }

    .question h2 {
        font-size: 20px;
        line-height: 1.35;
    }

    .question p {
        margin-top: 8px;
        font-size: 13px;
    }

    /* Layout */

    .quiz-layout {
        display: flex;
        flex-direction: column;

        gap: 16px;

        margin-top: 22px;
    }

    /* Detail first */

    .detail {
        order: 1;
    }

    /* Item list second */

    .item-list {
        order: 2;

        display: flex;
        flex-direction: row;

        overflow-x: auto;

        padding: 6px;

        border-radius: 12px;

        scrollbar-width: none;

        position: static;
        top: auto;
    }

    .item-list::-webkit-scrollbar {
        display: none;
    }

    .item-list-header {
        display: none;
    }

    .item-row {
        width: 48px;
        min-width: 48px;
        height: 48px;
        min-height: 48px;

        justify-content: center;

        padding: 5px;

        border-radius: 9px;
    }

    .item-row .item-number {
        width: 30px;
        height: 30px;
    }

    .item-text {
        display: none;
    }

    .item-row i {
        display: none;
    }

    .item-row.complete:not(.selected)::after {
        content: "";

        position: absolute;
        right: 5px;
        top: 5px;

        width: 6px;
        height: 6px;

        border-radius: 50%;

        background: var(--black);
    }

    /* Image */

    .image-frame {
        min-height: 260px;

        border-radius: 13px;
    }

    .image-frame::before {
        inset: 12px;
    }

    .image-label {
        font-size: 10px;
        padding: 5px 8px;
    }

    .component-image {
        width: 100%;
        height: 260px;

        padding: 28px;
    }

    /* Answer section */

    .answer-header {
        flex-direction: column;
        gap: 8px;
    }

    /* Options */

    .options {
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .option {
        min-height: 62px;

        padding: 12px;

        border-radius: 10px;
    }

    .option-text {
        font-size: 13px;
    }

    /* Confirm */

    .confirm-btn {
        width: 100%;
        min-height: 44px;

        align-self: center;
    }

    /* Footer */

    .quiz-actions {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;

        z-index: 25;

        margin: 0;

        padding: 10px 12px;

        border-top: 1px solid var(--border);

        background: rgba(250, 250, 250, 0.94);

        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }

    .action-status {
        font-size: 11px;

        flex: 1;
    }

    .action-buttons {
        gap: 6px;
    }

    .reset-btn,
    .submit-btn {
        min-height: 38px;

        padding: 0 14px;

        font-size: 11px;
    }

    /* Result */

    .result-notice {
        padding: 28px 22px;
        border-radius: 15px;
    }

    .result-score strong {
        font-size: 42px;
    }
}


/* =========================================================
   SMALL PHONES (380px)
   ========================================================= */

@media (max-width: 380px) {

    .quiz-container {
        width: calc(100% - 18px);
    }

    .question-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }

    .question h2 {
        font-size: 18px;
    }

    .image-frame,
    .component-image {
        min-height: 225px;
        height: 225px;
    }

    .component-image {
        padding: 20px;
    }

    .action-status {
        display: none;
    }

    .quiz-actions {
        justify-content: flex-end;
    }

    .reset-btn,
    .submit-btn {
        flex: 1;
    }

    .answer-label {
        font-size: 10px;
    }

    .result-score strong {
        font-size: 36px;
    }

    .result-summary {
        grid-template-columns: 1fr;
        gap: 10px;
    }
}


/* =========================================================
   ACCESSIBILITY
   ========================================================= */

button:focus-visible {
    outline: 2px solid var(--black);
    outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {

    .progress-fill,
    .item-row,
    .option,
    .confirm-btn,
    .component-image,
    .back-btn,
    .reset-btn,
    .submit-btn,
    .result-notice,
    .result-notice button {
        transition: none;
    }

    .result-notice {
        animation: none;
    }
}
</style>