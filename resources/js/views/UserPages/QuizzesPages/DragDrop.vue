<template>
    <div class="drag-drop-page">

        <!-- HEADER -->
        <header class="assessment-header">

            <div class="header-left">
                <button class="back-btn" @click="$router.back()">
                    <i class="fas fa-arrow-left"></i>
                </button>

                <div>
                    <span class="assessment-label">COC 1 Assessment</span>
                    <h1>Drag & Drop</h1>
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

            <section class="question-card">

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

                <h2>{{ question.question_text }}</h2>

                <p class="question-help">
                    Drag the items into the correct order. It can be changed later before submission. Once you submit,
                    you cannot change your answer.
                </p>

            </section>


            <!-- DRAG AREA -->
            <section class="workspace">

                <!-- AVAILABLE ITEMS -->
                <div class="items-panel">

                    <div class="panel-header">
                        <div>
                            <h3>Items</h3>
                            <p>Drag an item to the answer area.</p>
                        </div>

                        <span class="item-count">
                            {{ availableItems.length }}
                        </span>
                    </div>


                    <div class="items-list">

                        <div v-for="item in availableItems" :key="item.id" class="drag-item" draggable="true"
                            @dragstart="startDrag(item)" @dragend="draggedItem = null">

                            <span class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </span>

                            <span class="item-number">
                                {{ item.number }}
                            </span>

                            <span class="item-text">
                                {{ item.text }}
                            </span>

                        </div>

                        <div v-if="availableItems.length === 0" class="empty-items">
                            <i class="fas fa-check-circle"></i>
                            <span>All items placed</span>
                        </div>

                    </div>

                </div>


                <!-- ANSWER AREA -->
                <div class="answer-panel">

                    <div class="panel-header">
                        <div>
                            <h3>Your Answer</h3>
                            <p>Place the steps in the correct order.</p>
                        </div>

                        <span class="answer-count">
                            {{ answerItems.length }}/{{ items.length }}
                        </span>
                    </div>


                    <div class="drop-zone" @dragover.prevent @drop="dropItem">

                        <!-- ANSWER ITEMS -->
                        <div v-for="(item, index) in answerItems" :key="item.id"
                            :class="{ 'right-answer': item.isCorrect }" class="answer-item" draggable="true"
                            @dragstart="startAnswerDrag(item, index)" @dragover.prevent @drop.stop="moveItem(index)">

                            <span class="answer-number">
                                {{ index + 1 }}
                            </span>

                            <span class="answer-text">
                                {{ item.text }}
                            </span>

                            <span class="answer-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </span>

                        </div>


                        <!-- EMPTY DROP -->
                        <div v-if="answerItems.length === 0" class="drop-placeholder">
                            <div class="drop-icon">
                                <i class="fas fa-arrow-down"></i>
                            </div>

                            <strong>Drop items here</strong>

                            <span>
                                Drag the steps from the left into this area.
                            </span>
                        </div>


                        <!-- DROP MORE -->
                        <div v-else class="drop-more" @dragover.prevent @drop="dropItem">
                            <i class="fas fa-plus"></i>
                            Drop here to add
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
                    Submit Answer
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
        draggedItem.value = null
        draggedAnswerIndex.value = null

        return
    }

    /*
     * Add new item to answer.
     */
    const alreadyExists = answerItems.value.some(
        item => item.id === draggedItem.value.id
    )

    if (!alreadyExists) {
        answerItems.value.push(draggedItem.value)
    }

    draggedItem.value = null
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
        draggedItem.value = null
        draggedAnswerIndex.value = null

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

    draggedItem.value = null
    draggedAnswerIndex.value = null
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
   PAGE
========================================================= */

.drag-drop-page {
    min-height: 100vh;
    background: #f8fafc;
    color: #1e293b;
    padding-bottom: 40px;
}


/* =========================================================
   HEADER
========================================================= */

.assessment-header {
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    padding: 18px clamp(18px, 4vw, 50px);

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 30px;
}


.header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}


.back-btn {
    width: 36px;
    height: 36px;

    border: 1px solid #e5e7eb;
    background: #ffffff;
    border-radius: 9px;

    color: #64748b;
    cursor: pointer;
}


.back-btn:hover {
    color: #4f46e5;
    border-color: #c7d2fe;
}


.assessment-label {
    display: block;
    color: #6366f1;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 2px;
}


.header-left h1 {
    margin: 0;
    font-size: 18px;
    font-weight: 650;
}


.progress-info {
    width: 190px;
    color: #64748b;
    font-size: 11px;
}


.progress-bar {
    width: 100%;
    height: 5px;
    background: #e5e7eb;
    border-radius: 10px;
    margin-top: 7px;
    overflow: hidden;
}


.progress-fill {
    height: 100%;
    background: #6366f1;
    border-radius: inherit;
    transition: width .3s ease;
}


/* =========================================================
   CONTAINER
========================================================= */

.assessment-container {
    width: min(1000px, calc(100% - 30px));
    margin: 28px auto;
}


/* =========================================================
   QUESTION
========================================================= */

.question-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 22px;
}


.question-meta {
    display: flex;
    gap: 7px;
    margin-bottom: 14px;
}


.category,
.difficulty {
    display: inline-flex;
    align-items: center;
    gap: 5px;

    padding: 5px 9px;

    border-radius: 7px;

    font-size: 10px;
    font-weight: 600;
}


.category {
    background: #f1f5f9;
    color: #475569;
}


.difficulty {
    background: #eef2ff;
    color: #4f46e5;
}


.difficulty.medium {
    background: #fff7ed;
    color: #ea580c;
}

.right-answer {
    background: #ecfdf5;
    color: #059669;
}


.difficulty.hard {
    background: #fef2f2;
    color: #dc2626;
}


.difficulty-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: currentColor;
}


.question-card h2 {
    margin: 0;
    font-size: 19px;
    line-height: 1.45;
}


.question-help {
    margin: 7px 0 0;
    font-size: 12px;
    color: #64748b;
}


/* =========================================================
   WORKSPACE
========================================================= */

.workspace {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;

    margin-top: 14px;
}


.items-panel,
.answer-panel {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 18px;
}


.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    margin-bottom: 14px;
}


.panel-header h3 {
    margin: 0;
    font-size: 13px;
}


.panel-header p {
    margin: 3px 0 0;
    font-size: 10px;
    color: #94a3b8;
}


.item-count,
.answer-count {
    min-width: 25px;
    height: 25px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #f1f5f9;
    color: #64748b;

    border-radius: 7px;

    font-size: 10px;
    font-weight: 600;
}


/* =========================================================
   DRAG ITEMS
========================================================= */

.items-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}


.drag-item {
    display: flex;
    align-items: center;
    gap: 10px;

    padding: 11px;

    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 9px;

    cursor: grab;

    font-size: 12px;

    transition: .15s ease;
}


.drag-item:hover {
    border-color: #c7d2fe;
    background: #f5f7ff;
}


.drag-item:active {
    cursor: grabbing;
}


.drag-handle,
.answer-handle {
    color: #94a3b8;
}


.item-number {
    width: 22px;
    height: 22px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 6px;

    background: #ffffff;
    color: #64748b;

    font-size: 9px;
    font-weight: 600;
}


.item-text {
    flex: 1;
}


/* =========================================================
   DROP ZONE
========================================================= */

.drop-zone {
    min-height: 280px;

    padding: 10px;

    border: 1.5px dashed #cbd5e1;
    border-radius: 10px;

    background: #f8fafc;
}


.drop-placeholder {
    min-height: 250px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    text-align: center;

    color: #94a3b8;
}


.drop-icon {
    width: 38px;
    height: 38px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 10px;

    background: #eef2ff;
    color: #6366f1;

    margin-bottom: 10px;
}


.drop-placeholder strong {
    font-size: 12px;
    color: #64748b;
}


.drop-placeholder span {
    font-size: 10px;
    margin-top: 3px;
}


/* =========================================================
   ANSWER ITEM
========================================================= */

.answer-item {
    display: flex;
    align-items: center;
    gap: 9px;

    padding: 11px;

    margin-bottom: 7px;

    background: #ffffff;

    border: 1px solid #e2e8f0;
    border-radius: 9px;

    cursor: grab;

    font-size: 12px;

    transition: .15s ease;
}


.answer-item:hover {
    border-color: #c7d2fe;
    box-shadow: 0 2px 7px rgba(15, 23, 42, .05);
}


.answer-number {
    width: 24px;
    height: 24px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 6px;

    background: #eef2ff;
    color: #4f46e5;

    font-size: 10px;
    font-weight: 700;
}


.answer-text {
    flex: 1;
}


.answer-handle {
    cursor: grab;
}


/* =========================================================
   DROP MORE
========================================================= */

.drop-more {
    border: 1px dashed #cbd5e1;
    border-radius: 7px;

    padding: 7px;

    text-align: center;

    color: #94a3b8;

    font-size: 9px;
}


/* =========================================================
   EMPTY
========================================================= */

.empty-items {
    min-height: 100px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    gap: 5px;

    color: #10b981;

    font-size: 11px;
}


/* =========================================================
   ACTIONS
========================================================= */

.assessment-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;

    margin-top: 16px;
}


.reset-btn,
.submit-btn {
    border: none;
    border-radius: 9px;

    padding: 9px 14px;

    font-size: 11px;

    cursor: pointer;

    display: flex;
    align-items: center;
    gap: 7px;
}


.reset-btn {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    color: #64748b;
}


.reset-btn:hover {
    color: #334155;
    background: #f8fafc;
}


.submit-btn {
    background: #4f46e5;
    color: #ffffff;
}


.submit-btn:hover:not(:disabled) {
    background: #4338ca;
}


.submit-btn:disabled {
    opacity: .45;
    cursor: not-allowed;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 700px) {

    .assessment-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .progress-info {
        width: 100%;
    }

    .workspace {
        grid-template-columns: 1fr;
    }

    .question-card h2 {
        font-size: 16px;
    }

    .assessment-container {
        width: min(100% - 20px, 1000px);
    }

}
</style>