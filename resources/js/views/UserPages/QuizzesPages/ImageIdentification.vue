<template>
    <div class="quiz-page">
        <!-- HEADER -->
        <header class="quiz-header">
            <button class="back-btn" type="button" title="Go back" @click="router.back()">
                <i class="fas fa-arrow-left"></i>
            </button>

            <h1>Component Identification</h1>

            <div class="progress-info">
                <span>{{ identifiedItems.size }} / {{ items.length }}</span>
                <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: `${progress}%` }"></div>
                </div>
            </div>
        </header>

        <main class="quiz-container">

            <!-- QUESTION -->
            <section class="question">
                <h2>
                    {{ question.question_text ||
                        'Identify the component and choose the description that best explains its function.' }}
                </h2>
                <p>
                    Look at the image, then pick the description that matches what the
                    component does.
                </p>
            </section>

            <!-- QUIZ -->
            <section v-if="items.length" class="quiz-layout">

                <!-- LEFT / LIST -->
                <aside class="item-list">
                    <button v-for="(item, index) in items" :key="item.id" type="button" class="item-row" :class="{
                        selected: selectedItem?.id === item.id,
                        complete: identifiedItems.has(item.id)
                    }" @click="selectItem(item)">
                        <span class="item-number">{{ index + 1 }}</span>

                        <span class="item-text">
                            <strong>Item {{ index + 1 }}</strong>
                            <small>
                                {{
                                    identifiedItems.has(item.id)
                                        ? item.text
                                        : 'Not identified yet'
                                }}
                            </small>
                        </span>

                        <i v-if="identifiedItems.has(item.id)" class="fas fa-check"></i>
                    </button>
                </aside>

                <!-- RIGHT / DETAIL -->
                <section class="detail">

                    <!-- IMAGE -->
                    <div class="image-frame">
                        <img v-if="selectedItem?.image" :src="imageUrl(selectedItem.image)"
                            :alt="selectedItem.text || 'Component image'" class="component-image" />
                        <div v-else class="image-fallback">
                            <i class="fas fa-microchip"></i>
                            <span>No image available</span>
                        </div>
                    </div>

                    <!-- OPTIONS -->
                    <div class="options">
                        <button v-for="(item, idx) in items" :key="item.id" type="button" class="option"
                            :class="{ chosen: selectedLabel === item.id }"
                            :disabled="identifiedItems.has(selectedItem?.id)" @click="selectedLabel = item.id">
                            <span class="option-letter">{{ String.fromCharCode(65 + idx) }}</span>
                            <span class="option-text">{{ item.description }}</span>
                        </button>
                    </div>

                    <button class="confirm-btn" type="button"
                        :disabled="!selectedLabel || identifiedItems.has(selectedItem?.id)"
                        @click="confirmIdentification">
                        Confirm
                    </button>

                </section>
            </section>

            <!-- EMPTY -->
            <section v-else class="empty-state">
                <i class="fas fa-image"></i>
                <h2>No items to identify</h2>
                <p>This assessment doesn't have any reference images yet.</p>
            </section>

            <!-- FOOTER -->
            <section v-if="items.length" class="quiz-actions">
                <span class="action-status">
                    {{
                        identifiedItems.size === items.length
                            ? 'All items identified'
                            : `${items.length - identifiedItems.size} remaining`
                    }}
                </span>

                <div class="action-buttons">
                    <button class="reset-btn" type="button" @click="resetLab">Reset</button>
                    <button class="submit-btn" type="button" :disabled="identifiedItems.size !== items.length"
                        @click="finishLab">
                        Finish
                    </button>
                </div>
            </section>

        </main>

        <!-- RESULT -->
        <div v-if="finished" class="result-overlay" role="status">
            <div class="result-notice">
                <h2>Assessment finished</h2>
                <p>{{ score }} of {{ items.length }} correct</p>

                <div class="result-score">
                    <strong>{{ score }}</strong>
                    <span>/ {{ items.length }}</span>
                </div>

                <button type="button" @click="router.push(`/user/quizzes/assessment/${quizId}`)">
                    Return to assessment
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

const question = ref({})
const items = ref([])

const selectedItem = ref(null)
const selectedLabel = ref(null)

const identifiedItems = ref(new Set())
const correctItems = ref(new Set())

const finished = ref(false)

const progress = computed(() => {
    if (!items.value.length) return 0
    return (identifiedItems.value.size / items.value.length) * 100
})

const score = computed(() => correctItems.value.size)

const imageUrl = (path) => {
    if (!path) return ''
    return path.startsWith('http') || path.startsWith('/')
        ? path
        : `/storage/${path}`
}

const selectItem = (item) => {
    selectedItem.value = item
    selectedLabel.value = identifiedItems.value.has(item.id) ? item.id : null
}

const confirmIdentification = () => {
    if (!selectedItem.value || !selectedLabel.value || identifiedItems.value.has(selectedItem.value.id)) {
        return
    }

    /*
     * IMPORTANT:
     * selectedLabel = the ID of the description option
     * selectedItem = the component being identified
     * If they match, the description belongs to that component.
     */
    if (selectedLabel.value === selectedItem.value.id) {
        correctItems.value.add(selectedItem.value.id)
    }

    identifiedItems.value.add(selectedItem.value.id)

    const nextItem = items.value.find(item => !identifiedItems.value.has(item.id))
    if (nextItem) {
        selectItem(nextItem)
    }
}

const resetLab = () => {
    identifiedItems.value = new Set()
    correctItems.value = new Set()
    finished.value = false

    if (items.value[0]) {
        selectItem(items.value[0])
    }
}

const finishLab = () => {
    if (identifiedItems.value.size === items.value.length) {
        finished.value = true
    }
}

const getQuizData = async () => {
    try {
        const { data } = await imageIdentificationRequest.fetch(quizId)

        question.value = data.questions || {}

        items.value = (question.value || []).map(item => ({
            id: item.id,
            text: item.question_text,
            image: item.image_path,
            description:
                item.item_description ||
                item.description ||
                item.question_text ||
                ''
        }))

        if (items.value[0]) {
            selectItem(items.value[0])
        }
    } catch (error) {
        console.error('Error fetching image identification quiz:', error)
    }
}

onMounted(getQuizData)
</script>

<style scoped>
.quiz-page {
    --ink: #1a1a1a;
    --ink-soft: #6b6b6b;
    --border: #e2e2e2;
    --surface: #ffffff;
    --bg: #fafafa;
    --accent: #1a1a1a;

    min-height: 100vh;
    padding-bottom: 60px;
    color: var(--ink);
    background: var(--bg);
    font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
}

/* HEADER */
.quiz-header {
    position: sticky;
    top: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 24px;
    background: rgba(255, 255, 255, 0.9);
    border-bottom: 1px solid var(--border);
    backdrop-filter: blur(12px);
}

.back-btn {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    flex: 0 0 36px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--surface);
    color: var(--ink-soft);
    cursor: pointer;
    transition: border-color .15s ease, color .15s ease;
}

.back-btn:hover {
    border-color: var(--ink);
    color: var(--ink);
}

.quiz-header h1 {
    flex: 1;
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.progress-info {
    width: 160px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: var(--ink-soft);
}

.progress-bar {
    flex: 1;
    height: 4px;
    border-radius: 99px;
    background: var(--border);
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: var(--accent);
    transition: width .3s ease;
}

/* CONTAINER */
.quiz-container {
    width: min(1000px, calc(100% - 40px));
    margin: 32px auto 0;
}

/* QUESTION */
.question h2 {
    max-width: 720px;
    margin: 0 0 8px;
    font-size: 20px;
    line-height: 1.4;
    font-weight: 600;
}

.question p {
    max-width: 640px;
    margin: 0;
    color: var(--ink-soft);
    font-size: 14px;
    line-height: 1.6;
}

/* LAYOUT */
.quiz-layout {
    display: grid;
    grid-template-columns: minmax(220px, .7fr) minmax(0, 1.4fr);
    gap: 16px;
    margin-top: 24px;
}

/* ITEM LIST */
.item-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.item-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--surface);
    color: var(--ink-soft);
    text-align: left;
    cursor: pointer;
    font: inherit;
    transition: border-color .15s ease, color .15s ease;
}

.item-row:hover,
.item-row.selected {
    border-color: var(--ink);
    color: var(--ink);
}

.item-number {
    width: 26px;
    height: 26px;
    flex: 0 0 26px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: var(--bg);
    font-size: 12px;
    font-weight: 600;
}

.item-text {
    min-width: 0;
    display: grid;
    gap: 2px;
}

.item-text strong {
    font-size: 13px;
}

.item-text small {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 12px;
    color: var(--ink-soft);
}

.item-row i {
    margin-left: auto;
    font-size: 12px;
}

/* DETAIL */
.detail {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.image-frame {
    min-height: 280px;
    display: grid;
    place-items: center;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--surface);
}

.component-image {
    width: 100%;
    height: 280px;
    object-fit: contain;
    padding: 24px;
    box-sizing: border-box;
}

.image-fallback {
    display: grid;
    place-items: center;
    gap: 8px;
    color: var(--ink-soft);
    font-size: 13px;
}

.image-fallback i {
    font-size: 32px;
}

/* OPTIONS */
.options {
    display: grid;
    gap: 8px;
}

.option {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 52px;
    padding: 12px 14px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--surface);
    color: var(--ink);
    text-align: left;
    cursor: pointer;
    font: inherit;
    transition: border-color .15s ease, background .15s ease, color .15s ease;
}

.option:hover:not(:disabled) {
    border-color: var(--ink);
}

.option.chosen {
    border-color: var(--ink);
    background: var(--ink);
    color: var(--surface);
}

.option:disabled {
    cursor: default;
    opacity: .6;
}

.option-letter {
    width: 22px;
    height: 22px;
    flex: 0 0 22px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: 1px solid currentColor;
    font-size: 11px;
    font-weight: 600;
}

.option-text {
    font-size: 14px;
    line-height: 1.5;
}

/* CONFIRM */
.confirm-btn {
    align-self: flex-start;
    min-width: 140px;
    min-height: 42px;
    padding: 0 20px;
    border: 1px solid var(--ink);
    border-radius: 8px;
    background: var(--ink);
    color: var(--surface);
    font: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s ease, color .15s ease;
}

.confirm-btn:hover:not(:disabled) {
    background: var(--surface);
    color: var(--ink);
}

.confirm-btn:disabled {
    opacity: .35;
    cursor: not-allowed;
}

/* ACTIONS */
.quiz-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-top: 24px;
}

.action-status {
    font-size: 13px;
    color: var(--ink-soft);
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.reset-btn,
.submit-btn {
    min-height: 40px;
    padding: 0 18px;
    border-radius: 8px;
    font: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.reset-btn {
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--ink-soft);
}

.reset-btn:hover {
    border-color: var(--ink);
    color: var(--ink);
}

.submit-btn {
    border: 1px solid var(--ink);
    background: var(--ink);
    color: var(--surface);
}

.submit-btn:hover:not(:disabled) {
    background: var(--surface);
    color: var(--ink);
}

.submit-btn:disabled {
    opacity: .35;
    cursor: not-allowed;
}

/* EMPTY */
.empty-state {
    margin-top: 24px;
    padding: 70px 20px;
    text-align: center;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--surface);
}

.empty-state i {
    font-size: 28px;
    color: var(--ink-soft);
}

.empty-state h2 {
    margin: 14px 0 4px;
    font-size: 16px;
}

.empty-state p {
    margin: 0;
    color: var(--ink-soft);
    font-size: 13px;
}

/* RESULT */
.result-overlay {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: grid;
    place-items: center;
    padding: 20px;
    background: rgba(0, 0, 0, .5);
}

.result-notice {
    width: min(360px, 100%);
    padding: 28px;
    border-radius: 14px;
    background: var(--surface);
    text-align: center;
}

.result-notice h2 {
    margin: 0 0 4px;
    font-size: 18px;
}

.result-notice p {
    margin: 0;
    color: var(--ink-soft);
    font-size: 13px;
}

.result-score {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 4px;
    margin: 20px 0;
}

.result-score strong {
    font-size: 38px;
    font-weight: 700;
}

.result-score span {
    color: var(--ink-soft);
    font-size: 14px;
}

.result-notice button {
    width: 100%;
    min-height: 42px;
    border: 1px solid var(--ink);
    border-radius: 8px;
    background: var(--ink);
    color: var(--surface);
    font: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.result-notice button:hover {
    background: var(--surface);
    color: var(--ink);
}

/* MOBILE */
@media (max-width: 720px) {
    .quiz-header {
        flex-wrap: wrap;
        padding: 14px 16px;
    }

    .progress-info {
        width: 100%;
        order: 3;
    }

    .quiz-container {
        width: calc(100% - 24px);
        margin-top: 20px;
    }

    .quiz-layout {
        grid-template-columns: 1fr;
    }

    .detail {
        order: 1;
    }

    .item-list {
        order: 2;
    }

    .image-frame,
    .component-image {
        min-height: 220px;
        height: 220px;
    }

    .quiz-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .action-buttons {
        width: 100%;
    }

    .reset-btn,
    .submit-btn {
        flex: 1;
    }
}

@media (prefers-reduced-motion: reduce) {

    .progress-fill,
    .item-row,
    .option,
    .confirm-btn {
        transition: none;
    }
}
</style>