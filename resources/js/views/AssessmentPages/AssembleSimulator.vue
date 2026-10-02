<template>
    <div class="drag-drop-page">

        <!-- HEADER -->
        <header class="assessment-header">

            <div class="header-left">
                <button class="back-btn" @click="$router.back()">
                    <i class="fas fa-arrow-left"></i>
                </button>

                <div>
                    <span class="assessment-label">COC 1 • Field Simulation</span>
                    <h1>Virtual Hands-On</h1>
                </div>
            </div>

            <div class="progress-info">
                <span>Question {{ currentQuestion }} of {{ totalQuestions }}</span>

                <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: `${progress}%` }"></div>
                </div>
            </div>

        </header>


        <!-- QUESTION -->
        <main class="assessment-container">

            <section class="question-card scenario-card">

                <div class="question-meta">

                    <span class="category">
                        <i class="fas fa-layer-group"></i>
                        {{ question.category ?? 'General' }}
                    </span>

                    <span class="difficulty" :class="question.difficulty ?? 'easy'">
                        <span class="difficulty-dot"></span>
                        {{ question.difficulty }}
                    </span>

                </div>

                <div class="scenario-heading">
                    <span class="scenario-kicker"><i class="fas fa-triangle-exclamation"></i> Live service scenario</span>
                    <h2>{{ question.question_text || 'Prepare the workstation for the next installation step.' }}</h2>
                </div>

                <p class="question-help">
                    You are the technician on shift. Build the correct procedure on the workbench, then submit it for review.
                    You can reposition any step until you submit.
                </p>

                <div class="scenario-status">
                    <span><i class="fas fa-desktop"></i> Workstation offline</span>
                    <span><i class="fas fa-shield-halved"></i> Procedure check required</span>
                </div>

            </section>


            <!-- DRAG AREA -->
            <section class="workspace">

                <!-- AVAILABLE ITEMS -->
                <div class="items-panel">

                    <div class="panel-header">
                        <div>
                            <span class="panel-eyebrow">Technician tray</span>
                            <h3>Available steps</h3>
                            <p>Select a step or drag it to the workbench.</p>
                        </div>

                        <span class="item-count">
                            {{ availableItems.length }}
                        </span>
                    </div>


                    <div class="items-list">

                        <button v-for="item in availableItems" :key="item.id" class="drag-item" draggable="true"
                            type="button" @click="placeItem(item)" @dragstart="startDrag(item)" @dragend="clearDrag">

                            <span class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </span>

                            <span class="item-number">
                                {{ item.number }}
                            </span>

                            <span class="item-text">
                                {{ item.text }}
                            </span>

                            <span class="item-action">Add <i class="fas fa-arrow-right"></i></span>
                        </button>

                        <div v-if="availableItems.length === 0" class="empty-items">
                            <i class="fas fa-circle-check"></i>
                            <span>Tray is clear</span>
                        </div>

                    </div>

                </div>


                <!-- ANSWER AREA -->
                <div class="answer-panel">

                    <div class="panel-header">
                        <div>
                            <span class="panel-eyebrow">Procedure workbench</span>
                            <h3>Build the sequence</h3>
                            <p>Use the arrows for precise repositioning.</p>
                        </div>

                        <span class="answer-count">
                            {{ answerItems.length }}/{{ items.length }}
                        </span>
                    </div>


                    <div class="drop-zone" @dragover.prevent @drop="dropItem">

                        <!-- ANSWER ITEMS -->
                        <div v-for="(item, index) in answerItems" :key="item.id"
                            :class="{ 'right-answer': item.isCorrect }" class="answer-item" draggable="true"
                            @dragstart="startAnswerDrag(item, index)" @dragend="clearDrag" @dragover.prevent @drop.stop="moveItem(index)">

                            <span class="answer-number">
                                {{ index + 1 }}
                            </span>

                            <span class="answer-text">
                                {{ item.text }}
                            </span>

                            <span class="answer-controls">
                                <button type="button" title="Move step up" :disabled="isDisabled || index === 0"
                                    @click.stop="moveAnswer(index, -1)">
                                    <i class="fas fa-chevron-up"></i>
                                </button>
                                <button type="button" title="Move step down" :disabled="isDisabled || index === answerItems.length - 1"
                                    @click.stop="moveAnswer(index, 1)">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <button type="button" title="Return step to tray" :disabled="isDisabled"
                                    @click.stop="removeItem(index)">
                                    <i class="fas fa-rotate-left"></i>
                                </button>
                            </span>

                            <span class="answer-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </span>

                        </div>


                        <!-- EMPTY DROP -->
                        <div v-if="answerItems.length === 0" class="drop-placeholder">
                            <div class="drop-icon">
                                <i class="fas fa-hand-pointer"></i>
                            </div>

                            <strong>Place your first step</strong>

                            <span>
                                Tap Add or drag a step into the workbench.
                            </span>
                        </div>


                        <!-- DROP MORE -->
                        <div v-else class="drop-more" @dragover.prevent @drop="dropItem">
                            <i class="fas fa-plus"></i>
                            Drop here to add another step
                        </div>

                    </div>

                </div>

            </section>


            <!-- ACTIONS -->
            <section class="assessment-actions">

                <button class="reset-btn" @click="resetAnswer">
                    <i class="fas fa-rotate-left"></i>
                    Reset
                </button>

                <button v-if="!isDisabled" class="submit-btn" :disabled="answerItems.length !== items.length || isDisabled"
                    @click="submitAnswer">
                    Submit Procedure
                    <i class="fas fa-arrow-right"></i>
                </button>
                <button v-else class="submit-btn" @click="router.push(`/user/quizzes/assessment/${quizId}`)">
                    To Assessments
                </button>
            </section>
        </main>
    </div>
</template>


<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route = useRoute()

const quizId = route.params.id

/*
|--------------------------------------------------------------------------
| QUIZ DATA
|--------------------------------------------------------------------------
*/

const question = ref({})
const items = ref([])

const currentQuestion = ref(1)
const totalQuestions = ref(1)

/*
|--------------------------------------------------------------------------
| DRAG & DROP STATE
|--------------------------------------------------------------------------
*/

const answerItems = ref([])
const isSubmitted = ref(false)
const draggedItem = ref(null)
const isDisabled = ref(false)
const draggedAnswerIndex = ref(null)

/*
|--------------------------------------------------------------------------
| FETCH QUIZ
|--------------------------------------------------------------------------
*/

const getQuizData = async () => {
    try {
        const { data } = await axios.get(`/api/quiz/dragdrop/${quizId}`)

        question.value = data.question?.[0] || {}

        items.value = (data.items || []).map((item, index) => ({
            id: item.id,
            text: item.item_text,
            image: item.item_image_path,
            questionId: item.question_id,
            isCorrect: false,

            // Original item order
            number: index + 1
        }))

        totalQuestions.value = data.total_questions || 1

        /*
         * Correct order should be an array of IDs.
         *
         * Example:
         * [3, 1, 4, 2]
         */

        console.log('Question:', question.value)
        console.log('Items:', items.value)

    } catch (error) {
        console.error(
            'Error fetching drag & drop quiz:',
            error
        )
    }
}

/*
|--------------------------------------------------------------------------
| PROGRESS
|--------------------------------------------------------------------------
*/

const progress = computed(() => {
    if (!totalQuestions.value) {
        return 0
    }

    return (
        currentQuestion.value /
        totalQuestions.value
    ) * 100
})

/*
|--------------------------------------------------------------------------
| AVAILABLE ITEMS
|--------------------------------------------------------------------------
*/

const availableItems = computed(() => {
    const selectedIds = answerItems.value.map(
        item => item.id
    )

    return items.value.filter(
        item => !selectedIds.includes(item.id)
    )
})

/*
|--------------------------------------------------------------------------
| DRAG FROM AVAILABLE ITEMS
|--------------------------------------------------------------------------
*/

const startDrag = (item) => {
    if (isDisabled.value) {
        return
    }

    draggedItem.value = item
    draggedAnswerIndex.value = null
}

const clearDrag = () => {
    draggedItem.value = null
    draggedAnswerIndex.value = null
}

const placeItem = (item) => {
    if (isDisabled.value || answerItems.value.some(answer => answer.id === item.id)) {
        return
    }

    answerItems.value.push(item)
}

/*
|--------------------------------------------------------------------------
| DRAG EXISTING ANSWER
|--------------------------------------------------------------------------
*/

const startAnswerDrag = (item, index) => {
    if (isDisabled.value) {
        return
    }

    draggedItem.value = item
    draggedAnswerIndex.value = index
}

/*
|--------------------------------------------------------------------------
| DROP ITEM
|--------------------------------------------------------------------------
*/

const dropItem = () => {
    if (isDisabled.value) {
        return
    }

    if (!draggedItem.value) {
        return
    }

    /*
     * If dragging an existing answer,
     * don't add it again.
     */
    if (draggedAnswerIndex.value !== null) {
        clearDrag()

        return
    }

    /*
     * Add new item to answer.
     */
    const alreadyExists = answerItems.value.some(
        item => item.id === draggedItem.value.id
    )

    if (!alreadyExists) {
        placeItem(draggedItem.value)
    }

    clearDrag()
}

/*
|--------------------------------------------------------------------------
| MOVE EXISTING ITEM
|--------------------------------------------------------------------------
*/

const moveItem = (targetIndex) => {
    if (
        isDisabled.value ||
        draggedItem.value === null ||
        draggedAnswerIndex.value === null
    ) {
        return
    }

    const sourceIndex = draggedAnswerIndex.value

    if (sourceIndex === targetIndex) {
        clearDrag()

        return
    }

    const movedItem = answerItems.value.splice(
        sourceIndex,
        1
    )[0]

    answerItems.value.splice(
        targetIndex,
        0,
        movedItem
    )

    clearDrag()
}

const moveAnswer = (index, direction) => {
    if (isDisabled.value) {
        return
    }

    const targetIndex = index + direction

    if (targetIndex < 0 || targetIndex >= answerItems.value.length) {
        return
    }

    const movedItem = answerItems.value.splice(index, 1)[0]
    answerItems.value.splice(targetIndex, 0, movedItem)
}

const removeItem = (index) => {
    if (isDisabled.value) {
        return
    }

    answerItems.value.splice(index, 1)
}

/*
|--------------------------------------------------------------------------
| RESET
|--------------------------------------------------------------------------
*/

const resetAnswer = () => {
    if (isSubmitted.value) {
        return
    }

    answerItems.value = []

    draggedItem.value = null
    draggedAnswerIndex.value = null
}

/*
|--------------------------------------------------------------------------
| CHECK ANSWER
|--------------------------------------------------------------------------
|
| rightAnswer contains the correct IDs in the correct order.
|
| Example:
|
| rightAnswer = [3, 1, 4, 2]
|
| User answer:
|
| answerItems = [3, 1, 2, 4]
|
| Result:
|
| 3 -> correct
| 1 -> correct
| 2 -> wrong
| 4 -> wrong
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| SUBMIT
|--------------------------------------------------------------------------
*/

const submitAnswer = async () => {

    /*
     * Don't submit until every item
     * has been placed.
     */
    if (
        answerItems.value.length !==
        items.value.length
    ) {
        return
    }

    /*
     * Don't submit twice.
     */
    if (isSubmitted.value) {
        return
    }

    /*
     * Send only IDs to the backend.
     *
     * Example:
     * [3, 1, 4, 2]
     */
    const answer = answerItems.value.map(
        item => item.id
    )

    try {

        await axios.get('/sanctum/csrf-cookie')

        const { data } = await axios.post(
            '/api/quiz/dragdrop/answer',
            {
                question_id: question.value.id,
                answers: answer
            }
        )

        console.log('Server response:', data)

        if (data.status) {

            isSubmitted.value = true
            isDisabled.value = true

            const correctIds = data.correctList

            answerItems.value.forEach(item => {
                item.isCorrect = correctIds.includes(item.id)
            })
            console.log(
                'Answer submitted successfully:',
                data
            )

            alert(`Score: ${data.score}`)

        } else {

            console.error(
                'Error submitting answer:',
                data.message
            )
        }

    } catch (error) {

        console.error(
            'Error submitting answer:',
            error
        )
    }
}

/*
|--------------------------------------------------------------------------
| LOAD
|--------------------------------------------------------------------------
*/

onMounted(() => {
    getQuizData()
})
</script>


<style scoped>
/* =========================================================
   FROSTED NOIR
   #FFFFFF  White
   #000000  Black
   #A9A9A9  Silver
   #D3D3D3  Light Gray
   #696969  Dim Gray
========================================================= */

.drag-drop-page {
    --black: #000000;
    --white: #ffffff;
    --gray-100: #f7f7f7;
    --gray-200: #eeeeee;
    --gray-300: #d3d3d3;
    --gray-400: #a9a9a9;
    --gray-500: #696969;

    --text-primary: #000000;
    --text-secondary: #696969;
    --text-muted: #a9a9a9;

    --border: #d3d3d3;
    --surface: #ffffff;
    --surface-soft: #f7f7f7;

    --success: #000000;
    --success-bg: #eeeeee;

    min-height: 100vh;
    padding-bottom: 50px;

    min-height: 100vh;
  
    color: var(--text-primary);

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

.assessment-header {
    position: sticky;
    top: 0;
    z-index: 20;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 30px;

    padding: 14px 24px;

    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);

    border-bottom: 1px solid var(--border);
}


/* =========================================================
   HEADER LEFT
========================================================= */

.header-left {
    min-width: 0;

    display: flex;
    align-items: center;

    gap: 12px;
}


.back-btn {
    width: 36px;
    height: 36px;

    flex: 0 0 36px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 1px solid var(--border);
    border-radius: 9px;

    background: var(--white);
    color: var(--text-secondary);

    cursor: pointer;

    transition:
        background 0.15s ease,
        color 0.15s ease,
        border-color 0.15s ease,
        transform 0.15s ease;
}


.back-btn:hover {
    background: var(--black);
    border-color: var(--black);
    color: var(--white);
}


.back-btn:active {
    transform: scale(0.95);
}


/* =========================================================
   TITLE
========================================================= */

.assessment-label {
    display: block;

    margin-bottom: 2px;

    color: var(--gray-500);

    font-size: 9px;
    font-weight: 700;

    letter-spacing: 0.12em;
    text-transform: uppercase;
}


.header-left h1 {
    margin: 0;

    color: var(--black);

    font-size: 18px;
    line-height: 1.2;
    font-weight: 750;

    letter-spacing: -0.025em;
}


/* =========================================================
   PROGRESS
========================================================= */

.progress-info {
    width: 210px;

    flex: 0 0 auto;

    color: var(--gray-500);

    font-size: 10px;
    font-weight: 600;
}


.progress-bar {
    width: 100%;
    height: 4px;

    margin-top: 7px;

    overflow: hidden;

    background: var(--gray-200);

    border-radius: 999px;
}


.progress-fill {
    height: 100%;

    background: var(--black);

    border-radius: inherit;

    transition: width 0.35s ease;
}


/* =========================================================
   MAIN
========================================================= */

.assessment-container {
    width: min(980px, calc(100% - 40px));

    margin: 26px auto 0;
}


/* =========================================================
   QUESTION
========================================================= */

.question-card {
    padding: 24px;

    background: var(--white);

    border: 1px solid var(--border);
    border-radius: 14px;
}


.question-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;

    gap: 7px;

    margin-bottom: 14px;
}


.category,
.difficulty {
    display: inline-flex;
    align-items: center;
    gap: 6px;

    padding: 5px 9px;

    border-radius: 6px;

    font-size: 9px;
    line-height: 1;

    font-weight: 700;
}


.category {
    background: var(--gray-200);
    color: var(--gray-500);
}


.difficulty {
    background: var(--black);
    color: var(--white);
}


.difficulty.medium {
    background: var(--gray-400);
    color: var(--black);
}


.difficulty.hard {
    background: var(--gray-500);
    color: var(--white);
}


.difficulty-dot {
    width: 5px;
    height: 5px;

    border-radius: 50%;

    background: currentColor;
}


.question-card h2 {
    max-width: 820px;

    margin: 0;

    color: var(--black);

    font-size: clamp(17px, 2vw, 21px);
    line-height: 1.45;

    font-weight: 700;

    letter-spacing: -0.02em;
}


.question-help {
    max-width: 760px;

    margin: 9px 0 0;

    color: var(--gray-500);

    font-size: 11px;
    line-height: 1.6;
}


/* =========================================================
   WORKSPACE
========================================================= */

.workspace {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr) minmax(0, 1fr);

    gap: 12px;

    margin-top: 12px;
}


/* =========================================================
   PANELS
========================================================= */

.items-panel,
.answer-panel {
    min-width: 0;

    padding: 17px;

    background: var(--white);

    border: 1px solid var(--border);
    border-radius: 14px;
}


/* =========================================================
   PANEL HEADER
========================================================= */

.panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;

    gap: 12px;

    margin-bottom: 12px;
}


.panel-header h3 {
    margin: 0;

    color: var(--black);

    font-size: 12px;
    font-weight: 750;
}


.panel-header p {
    margin: 3px 0 0;

    color: var(--gray-400);

    font-size: 9px;
    line-height: 1.4;
}


.item-count,
.answer-count {
    width: 26px;
    height: 26px;

    min-width: 26px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 1px solid var(--border);
    border-radius: 7px;

    background: var(--gray-100);

    color: var(--gray-500);

    font-size: 9px;
    font-weight: 700;
}


/* =========================================================
   ITEMS
========================================================= */

.items-list {
    display: flex;
    flex-direction: column;

    gap: 7px;
}


.drag-item {
    min-width: 0;

    display: flex;
    align-items: center;

    gap: 9px;

    padding: 10px;

    background: var(--gray-100);

    border: 1px solid var(--gray-200);
    border-radius: 9px;

    cursor: grab;

    font-size: 11px;

    transition:
        background 0.15s ease,
        border-color 0.15s ease,
        transform 0.15s ease;
}


.drag-item:hover {
    background: var(--white);

    border-color: var(--gray-400);

    transform: translateY(-1px);
}


.drag-item:active {
    cursor: grabbing;

    transform: scale(0.99);
}


.drag-handle {
    flex: 0 0 auto;

    color: var(--gray-400);

    font-size: 10px;
}


.item-number {
    width: 22px;
    height: 22px;

    flex: 0 0 22px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 1px solid var(--border);
    border-radius: 6px;

    background: var(--white);

    color: var(--gray-500);

    font-size: 9px;
    font-weight: 700;
}


.item-text {
    min-width: 0;
    flex: 1;

    color: var(--gray-500);

    line-height: 1.45;

    overflow-wrap: anywhere;
}


/* =========================================================
   DROP ZONE
========================================================= */

.drop-zone {
    min-height: 280px;

    padding: 8px;

    background: var(--gray-100);

    border: 1px dashed var(--gray-300);
    border-radius: 9px;

    transition:
        background 0.15s ease,
        border-color 0.15s ease;
}


.drop-zone:hover {
    background: var(--white);

    border-color: var(--gray-400);
}


/* =========================================================
   EMPTY DROP
========================================================= */

.drop-placeholder {
    min-height: 250px;

    padding: 20px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    text-align: center;
}


.drop-icon {
    width: 40px;
    height: 40px;

    margin-bottom: 10px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 1px solid var(--border);
    border-radius: 10px;

    background: var(--white);

    color: var(--gray-500);

    font-size: 12px;
}


.drop-placeholder strong {
    color: var(--gray-500);

    font-size: 11px;
    font-weight: 700;
}


.drop-placeholder span {
    max-width: 220px;

    margin-top: 4px;

    color: var(--gray-400);

    font-size: 9px;
    line-height: 1.5;
}


/* =========================================================
   ANSWER ITEMS
========================================================= */

.answer-item {
    min-width: 0;

    display: flex;
    align-items: center;

    gap: 8px;

    margin-bottom: 6px;
    padding: 10px;

    background: var(--white);

    border: 1px solid var(--border);
    border-radius: 9px;

    cursor: grab;

    font-size: 11px;

    transition:
        border-color 0.15s ease,
        background 0.15s ease,
        transform 0.15s ease;
}


.answer-item:last-child {
    margin-bottom: 0;
}


.answer-item:hover {
    border-color: var(--gray-500);

    transform: translateY(-1px);
}


.answer-item:active {
    cursor: grabbing;
}


/* CORRECT ANSWER */

.answer-item.right-answer {
    background: var(--gray-200);

    border-color: var(--gray-400);
}


.answer-number {
    width: 24px;
    height: 24px;

    flex: 0 0 24px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 6px;

    background: var(--black);

    color: var(--white);

    font-size: 9px;
    font-weight: 700;
}


.right-answer .answer-number {
    background: var(--white);

    border: 1px solid var(--gray-400);

    color: var(--black);
}


.answer-text {
    min-width: 0;
    flex: 1;

    color: var(--gray-500);

    line-height: 1.45;

    overflow-wrap: anywhere;
}


.right-answer .answer-text {
    color: var(--black);

    font-weight: 600;
}


.answer-handle {
    flex: 0 0 auto;

    color: var(--gray-400);

    font-size: 10px;
}


/* =========================================================
   DROP MORE
========================================================= */

.drop-more {
    margin-top: 7px;

    padding: 7px;

    border: 1px dashed var(--gray-300);
    border-radius: 7px;

    text-align: center;

    color: var(--gray-400);

    font-size: 9px;

    transition:
        color 0.15s ease,
        border-color 0.15s ease,
        background 0.15s ease;
}


.drop-more:hover {
    color: var(--black);

    border-color: var(--gray-500);

    background: var(--white);
}


/* =========================================================
   EMPTY ITEMS
========================================================= */

.empty-items {
    min-height: 100px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    gap: 6px;

    color: var(--gray-500);

    font-size: 10px;
    font-weight: 600;
}


/* =========================================================
   ACTIONS
========================================================= */

.assessment-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 12px;

    margin-top: 14px;
}


.reset-btn,
.submit-btn {
    min-height: 39px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    gap: 7px;

    padding: 8px 14px;

    border-radius: 8px;

    font-size: 10px;
    font-weight: 700;

    cursor: pointer;

    transition:
        background 0.15s ease,
        color 0.15s ease,
        border-color 0.15s ease,
        transform 0.15s ease,
        opacity 0.15s ease;
}


/* RESET */

.reset-btn {
    background: var(--white);

    border: 1px solid var(--border);

    color: var(--gray-500);
}


.reset-btn:hover {
    background: var(--gray-200);

    border-color: var(--gray-400);

    color: var(--black);
}


/* SUBMIT */

.submit-btn {
    border: 1px solid var(--black);

    background: var(--black);

    color: var(--white);
}


.submit-btn:hover:not(:disabled) {
    background: var(--gray-500);

    border-color: var(--gray-500);

    transform: translateY(-1px);
}


.submit-btn:active:not(:disabled) {
    transform: scale(0.98);
}


.submit-btn:disabled {
    opacity: 0.35;

    cursor: not-allowed;
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 800px) {

    .assessment-header {
        padding: 14px 20px;
    }


    .assessment-container {
        width: min(100% - 28px, 980px);

        margin-top: 20px;
    }


    .workspace {
        grid-template-columns: 1fr;
    }


    .items-panel,
    .answer-panel {
        padding: 15px;
    }


    .drop-zone {
        min-height: 240px;
    }


    .drop-placeholder {
        min-height: 210px;
    }
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 600px) {

    .drag-drop-page {
        padding-bottom: 25px;
    }


    /* HEADER */

    .assessment-header {
        position: relative;

        padding: 13px 15px;

        flex-direction: column;
        align-items: stretch;

        gap: 12px;
    }


    .header-left {
        width: 100%;
    }


    .back-btn {
        width: 34px;
        height: 34px;

        flex-basis: 34px;
    }


    .assessment-label {
        font-size: 8px;
    }


    .header-left h1 {
        font-size: 16px;
    }


    .progress-info {
        width: 100%;

        font-size: 9px;
    }


    .progress-bar {
        height: 4px;
    }


    /* CONTAINER */

    .assessment-container {
        width: calc(100% - 20px);

        margin: 13px auto 0;
    }


    /* QUESTION */

    .question-card {
        padding: 15px;

        border-radius: 12px;
    }


    .question-meta {
        margin-bottom: 11px;
    }


    .category,
    .difficulty {
        padding: 5px 8px;

        font-size: 8px;
    }


    .question-card h2 {
        font-size: 15px;
    }


    .question-help {
        font-size: 10px;
    }


    /* WORKSPACE */

    .workspace {
        gap: 9px;

        margin-top: 9px;
    }


    .items-panel,
    .answer-panel {
        padding: 13px;

        border-radius: 12px;
    }


    .panel-header {
        margin-bottom: 10px;
    }


    .panel-header h3 {
        font-size: 11px;
    }


    .panel-header p {
        font-size: 8px;
    }


    /* ITEMS */

    .drag-item {
        padding: 9px;

        gap: 7px;

        font-size: 10px;
    }


    .item-number {
        width: 21px;
        height: 21px;

        flex-basis: 21px;
    }


    /* DROP */

    .drop-zone {
        min-height: 210px;

        padding: 6px;
    }


    .drop-placeholder {
        min-height: 190px;

        padding: 14px;
    }


    .drop-icon {
        width: 36px;
        height: 36px;
    }


    /* ANSWERS */

    .answer-item {
        padding: 9px;

        gap: 7px;

        font-size: 10px;
    }


    .answer-number {
        width: 22px;
        height: 22px;

        flex-basis: 22px;
    }


    /* ACTIONS */

    .assessment-actions {
        flex-direction: column-reverse;

        align-items: stretch;

        gap: 7px;

        margin-top: 11px;
    }


    .reset-btn,
    .submit-btn {
        width: 100%;

        min-height: 40px;
    }
}


/* =========================================================
   VERY SMALL PHONES
========================================================= */

@media (max-width: 380px) {

    .assessment-container {
        width: calc(100% - 16px);
    }


    .question-card {
        padding: 13px;
    }


    .items-panel,
    .answer-panel {
        padding: 11px;
    }


    .question-card h2 {
        font-size: 14px;
    }


    .drag-item,
    .answer-item {
        font-size: 9.5px;
    }


    .panel-header p {
        display: none;
    }
}

/* =========================================================
   REDUCED MOTION
========================================================= */

.drag-drop-page {
        background: linear-gradient(rgba(211, 211, 211, 0.8) 1px, transparent 1px),
            linear-gradient(90deg, rgba(211, 211, 211, 0.8) 1px, transparent 1px), #f4f4f2;
        background-size: 28px 28px;
}

.assessment-header {
    background: rgba(244, 244, 242, 0.92);
}

.scenario-card {
    position: relative;
    overflow: hidden;
    background: #101010;
    border-color: #101010;
    color: var(--white);
    box-shadow: 0 18px 36px rgba(0, 0, 0, 0.12);
}

.scenario-card::after {
    position: absolute;
    right: -42px;
    bottom: -58px;
    width: 180px;
    height: 180px;
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 50%;
    content: '';
}

.scenario-card h2,
.scenario-card .question-help {
    position: relative;
    z-index: 1;
}

.scenario-card h2 {
    color: var(--white);
}

.scenario-card .question-help {
    color: #b9b9b9;
}

.scenario-kicker,
.panel-eyebrow {
    display: block;
    color: #8e8e8e;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.scenario-kicker {
    margin-bottom: 8px;
    color: #d3d3d3;
}

.scenario-status {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    gap: 8px 18px;
    margin-top: 16px;
    color: #d3d3d3;
    font-size: 10px;
    font-weight: 700;
}

.scenario-status i {
    margin-right: 5px;
    color: #a9a9a9;
}

.items-panel,
.answer-panel {
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.05);
}

.panel-eyebrow {
    margin-bottom: 4px;
    color: var(--gray-400);
    font-size: 8px;
}

.drag-item {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid var(--gray-200);
    font-family: inherit;
    text-align: left;
}

.item-action {
    flex: 0 0 auto;
    color: var(--gray-400);
    font-size: 8px;
    font-weight: 800;
    text-transform: uppercase;
}

.item-action i {
    margin-left: 3px;
}

.answer-controls {
    display: flex;
    flex: 0 0 auto;
    gap: 3px;
}

.answer-controls button {
    width: 25px;
    height: 25px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    background: var(--gray-100);
    color: var(--gray-500);
    cursor: pointer;
}

.answer-controls button:hover:not(:disabled) {
    border-color: var(--black);
    background: var(--black);
    color: var(--white);
}

.answer-controls button:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.answer-item.right-answer .answer-controls button {
    background: var(--white);
}

@media (max-width: 600px) {
    .scenario-status {
        gap: 7px 12px;
        font-size: 9px;
    }

    .answer-controls button {
        width: 27px;
        height: 27px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .drag-item,
    .answer-item,
    .submit-btn,
    .reset-btn,
    .back-btn,
    .progress-fill {
        transition: none;
    }
}
</style>