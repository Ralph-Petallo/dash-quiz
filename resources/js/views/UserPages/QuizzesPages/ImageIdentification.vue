<template>
    <div class="image-identification-page">
        <header class="assessment-header">
            <div class="header-left">
                <button class="back-btn" type="button" title="Go back" @click="router.back()">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div>
                    <span class="assessment-label">COC 1 / Identification Lab</span>
                    <h1>Image Identification</h1>
                </div>
            </div>

            <div class="progress-info">
                <span>Identified {{ identifiedItems.size }} of {{ items.length }}</span>
                <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: `${progress}%` }"></div>
                </div>
            </div>
        </header>

        <main class="assessment-container">
            <section class="question-card">
                <div class="question-meta">
                    <span class="category"><i class="fas fa-microscope"></i> Components lab</span>
                    <span class="difficulty"><span class="difficulty-dot"></span> Practical</span>
                </div>
                <span class="scenario-kicker"><i class="fas fa-crosshairs"></i> Technician inspection</span>
                <h2>{{ question.question_text || 'Identify each component before installing it in the workstation.' }}
                </h2>
                <p class="question-help">Inspect the image, then choose the label that matches the component. Your
                    answer is recorded when you confirm it.</p>
            </section>

            <section v-if="items.length" class="lab-layout">
                <aside class="inspection-panel">
                    <div class="panel-heading">
                        <div>
                            <span class="panel-eyebrow">Inspection queue</span>
                            <h3>Reference images</h3>
                        </div>
                        <span class="item-count">{{ items.length }}</span>
                    </div>

                    <button v-for="(item, index) in items" :key="item.id" type="button" class="image-tab"
                        :class="{ selected: selectedItem?.id === item.id, complete: identifiedItems.has(item.id) }"
                        @click="selectItem(item)">
                        <span class="tab-number">{{ String(index + 1).padStart(2, '0') }}</span>
                        <span class="tab-label">{{ identifiedItems.has(item.id) ? item.text : 'Unidentified component'
                            }}</span>
                        <i :class="identifiedItems.has(item.id) ? 'fas fa-check' : 'fas fa-chevron-right'"></i>
                    </button>
                </aside>

                <section class="inspection-stage">
                    <div class="stage-topline">
                        <span>Specimen {{ selectedIndex + 1 }} / {{ items.length }}</span>
                        <span v-if="selectedItem && identifiedItems.has(selectedItem.id)" class="verified"><i
                                class="fas fa-check-circle"></i> Verified</span>
                    </div>

                    <div class="image-frame">
                        <img v-if="selectedItem?.image" :src="imageUrl(selectedItem.image)" :alt="selectedItem.text"
                            class="component-image">
                        <div v-else class="image-fallback">
                            <i class="fas fa-microchip"></i>
                            <span>Image pending</span>
                        </div>
                        <span class="scan-line"></span>
                    </div>

                    <div class="label-picker">
                        <span class="panel-eyebrow">Choose identification</span>
                        <div class="label-grid">
                            <button v-for="item in items" :key="item.id" type="button" class="label-option"
                                :class="{ chosen: selectedLabel === item.id }"
                                :disabled="identifiedItems.has(selectedItem?.id)" @click="selectedLabel = item.id">
                                {{ item.text }}
                            </button>
                        </div>
                        <button class="confirm-btn" type="button"
                            :disabled="!selectedLabel || identifiedItems.has(selectedItem?.id)"
                            @click="confirmIdentification">
                            Confirm identification <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </section>
            </section>

            <section v-else class="empty-state">
                <i class="fas fa-image"></i>
                <h2>No specimens loaded</h2>
                <p>This practical assessment has no reference images yet.</p>
            </section>

            <section v-if="items.length" class="assessment-actions">
                <button class="reset-btn" type="button" @click="resetLab"><i class="fas fa-rotate-left"></i> Reset
                    lab</button>
                <button class="submit-btn" type="button" :disabled="identifiedItems.size !== items.length"
                    @click="finishLab">
                    Finish inspection <i class="fas fa-arrow-right"></i>
                </button>
            </section>
        </main>

        <div v-if="finished" class="result-notice" role="status">
            <strong>Inspection complete</strong>
            <span>{{ score }} of {{ items.length }} identifications matched.</span>
            <button type="button" @click="router.push(`/user/quizzes/assessment/${quizId}`)">Return to
                assessments</button>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { imageIdentificationRequest } from '@/assessmentrequests/imageIdentification'
import axios from 'axios'

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

const selectedIndex = computed(() => Math.max(items.value.findIndex(item => item.id === selectedItem.value?.id), 0))
const progress = computed(() => items.value.length ? (identifiedItems.value.size / items.value.length) * 100 : 0)
const score = computed(() => correctItems.value.size)

const imageUrl = (path) => {
    if (!path) return ''
    return path.startsWith('http') || path.startsWith('/') ? path : `/storage/${path}`
}

const selectItem = (item) => {
    selectedItem.value = item
    selectedLabel.value = identifiedItems.value.has(item.id) ? item.id : null
}

const confirmIdentification = () => {
    if (!selectedItem.value || !selectedLabel.value || identifiedItems.value.has(selectedItem.value.id)) return

    if (selectedLabel.value === selectedItem.value.id) correctItems.value.add(selectedItem.value.id)
    identifiedItems.value.add(selectedItem.value.id)
    const nextItem = items.value.find(item => !identifiedItems.value.has(item.id))
    if (nextItem) selectItem(nextItem)
}

const resetLab = () => {
    identifiedItems.value = new Set()
    correctItems.value = new Set()
    finished.value = false
    if (items.value[0]) selectItem(items.value[0])
}

const finishLab = () => {
    if (identifiedItems.value.size === items.value.length) finished.value = true
}

const getQuizData = async () => {
    try {
        const { data } = await imageIdentificationRequest.fetch(quizId)
        console.log(data)
        question.value = data.question?.[0] || {}
        items.value = (data.items || []).map(item => ({
            id: item.id,
            text: item.item_text,
            image: item.item_image_path,
        }))
        if (items.value[0]) selectItem(items.value[0])
    } catch (error) {
        console.error('Error fetching image identification quiz:', error)
    }
}

onMounted(getQuizData)
</script>

<style scoped>
.image-identification-page {
    --black: #000;
    --white: #fff;
    --gray-100: #f7f7f7;
    --gray-200: #eee;
    --gray-300: #d3d3d3;
    --gray-400: #a9a9a9;
    --gray-500: #696969;
    min-height: 100vh;
    padding-bottom: 50px;
    background: linear-gradient(rgba(211, 211, 211, .22) 1px, transparent 1px), linear-gradient(90deg, rgba(211, 211, 211, .22) 1px, transparent 1px), #f4f4f2;
    background-size: 28px 28px;
    color: var(--black);
    font-family: Inter, ui-sans-serif, system-ui, sans-serif;
}

.assessment-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 30px;
    padding: 14px 24px;
    background: rgba(255, 255, 255, .9);
    border-bottom: 1px solid var(--gray-300);
    backdrop-filter: blur(18px);
}

.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.back-btn {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    flex: 0 0 36px;
    border: 1px solid var(--gray-300);
    border-radius: 9px;
    background: var(--white);
    color: var(--gray-500);
    cursor: pointer;
}

.back-btn:hover,
.confirm-btn:hover:not(:disabled),
.submit-btn:hover:not(:disabled) {
    background: var(--black);
    color: var(--white);
}

.assessment-label,
.panel-eyebrow,
.scenario-kicker {
    display: block;
    color: var(--gray-500);
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.header-left h1 {
    margin: 2px 0 0;
    font-size: 18px;
}

.progress-info {
    width: 210px;
    color: var(--gray-500);
    font-size: 10px;
    font-weight: 700;
}

.progress-bar {
    height: 4px;
    margin-top: 7px;
    overflow: hidden;
    border-radius: 999px;
    background: var(--gray-200);
}

.progress-fill {
    height: 100%;
    background: var(--black);
    transition: width .3s ease;
}

.assessment-container {
    width: min(980px, calc(100% - 40px));
    margin: 26px auto 0;
}

.question-card,
.inspection-panel,
.inspection-stage,
.empty-state {
    background: var(--white);
    border: 1px solid var(--gray-300);
    border-radius: 14px;
    box-shadow: 0 10px 24px rgba(0, 0, 0, .05);
}

.question-card {
    padding: 24px;
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
    gap: 6px;
    padding: 5px 9px;
    border-radius: 6px;
    font-size: 9px;
    font-weight: 800;
}

.category {
    background: var(--gray-200);
    color: var(--gray-500);
}

.difficulty {
    background: var(--black);
    color: var(--white);
}

.difficulty-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: currentColor;
}

.question-card h2 {
    max-width: 800px;
    margin: 8px 0 0;
    font-size: clamp(17px, 2vw, 22px);
    line-height: 1.4;
}

.question-help {
    max-width: 760px;
    margin: 9px 0 0;
    color: var(--gray-500);
    font-size: 11px;
    line-height: 1.6;
}

.lab-layout {
    display: grid;
    grid-template-columns: minmax(220px, .7fr) minmax(0, 1.3fr);
    gap: 12px;
    margin-top: 12px;
}

.inspection-panel,
.inspection-stage {
    padding: 17px;
}

.panel-heading,
.stage-topline {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.panel-heading h3 {
    margin: 4px 0 12px;
    font-size: 13px;
}

.item-count,
.tab-number {
    display: grid;
    place-items: center;
    border: 1px solid var(--gray-300);
    background: var(--gray-100);
    color: var(--gray-500);
    font-size: 9px;
    font-weight: 800;
}

.item-count {
    width: 26px;
    height: 26px;
    border-radius: 7px;
}

.image-tab {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 10px;
    margin-bottom: 7px;
    border: 1px solid var(--gray-200);
    border-radius: 9px;
    background: var(--gray-100);
    color: var(--gray-500);
    cursor: pointer;
    text-align: left;
    font: inherit;
    font-size: 10px;
}

.image-tab:hover,
.image-tab.selected {
    border-color: var(--black);
    background: var(--white);
    color: var(--black);
}

.image-tab.complete {
    border-color: var(--gray-400);
}

.image-tab i {
    margin-left: auto;
    font-size: 9px;
}

.tab-number {
    width: 25px;
    height: 25px;
    flex: 0 0 25px;
    border-radius: 6px;
}

.stage-topline {
    margin-bottom: 12px;
    color: var(--gray-500);
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
}

.verified {
    color: var(--black);
}

.image-frame {
    position: relative;
    min-height: 260px;
    display: grid;
    place-items: center;
    overflow: hidden;
    border: 1px solid var(--gray-300);
    border-radius: 10px;
    background: repeating-linear-gradient(0deg, transparent 0 27px, rgba(211, 211, 211, .35) 28px), var(--gray-100);
}

.component-image {
    width: 100%;
    height: 260px;
    object-fit: contain;
    padding: 20px;
    box-sizing: border-box;
}

.image-fallback {
    display: grid;
    gap: 9px;
    place-items: center;
    color: var(--gray-400);
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
}

.image-fallback i {
    font-size: 38px;
}

.scan-line {
    position: absolute;
    left: 8%;
    right: 8%;
    top: 50%;
    border-top: 1px dashed rgba(0, 0, 0, .18);
}

.label-picker {
    margin-top: 17px;
}

.label-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 7px;
    margin-top: 7px;
}

.label-option,
.confirm-btn,
.reset-btn,
.submit-btn,
.result-notice button {
    min-height: 38px;
    border-radius: 8px;
    font: inherit;
    font-size: 10px;
    font-weight: 800;
    cursor: pointer;
}

.label-option {
    padding: 8px;
    border: 1px solid var(--gray-300);
    background: var(--white);
    color: var(--gray-500);
    text-align: left;
}

.label-option:hover:not(:disabled),
.label-option.chosen {
    border-color: var(--black);
    color: var(--black);
}

.label-option:disabled {
    cursor: default;
    opacity: .55;
}

.confirm-btn,
.submit-btn {
    border: 1px solid var(--black);
    background: var(--black);
    color: var(--white);
    padding: 8px 14px;
}

.confirm-btn {
    width: 100%;
    margin-top: 9px;
}

.confirm-btn:disabled,
.submit-btn:disabled {
    opacity: .3;
    cursor: not-allowed;
}

.assessment-actions {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-top: 14px;
}

.reset-btn {
    padding: 8px 14px;
    border: 1px solid var(--gray-300);
    background: var(--white);
    color: var(--gray-500);
}

.empty-state {
    padding: 60px 20px;
    margin-top: 12px;
    text-align: center;
}

.empty-state i {
    color: var(--gray-400);
    font-size: 32px;
}

.empty-state h2 {
    margin: 14px 0 5px;
    font-size: 17px;
}

.empty-state p {
    margin: 0;
    color: var(--gray-500);
    font-size: 11px;
}

.result-notice {
    position: fixed;
    right: 20px;
    bottom: 20px;
    z-index: 10;
    display: grid;
    gap: 5px;
    max-width: 270px;
    padding: 16px;
    border: 1px solid var(--black);
    border-radius: 10px;
    background: var(--white);
    box-shadow: 0 12px 30px rgba(0, 0, 0, .16);
    font-size: 11px;
}

.result-notice span {
    color: var(--gray-500);
}

.result-notice button {
    padding: 8px;
    border: 1px solid var(--black);
    background: var(--black);
    color: var(--white);
}

@media (max-width: 720px) {
    .assessment-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
        padding: 13px 15px;
    }

    .progress-info {
        width: 100%;
    }

    .assessment-container {
        width: calc(100% - 20px);
        margin-top: 13px;
    }

    .lab-layout {
        grid-template-columns: 1fr;
    }

    .inspection-panel {
        order: 2;
    }

    .inspection-stage {
        order: 1;
    }

    .question-card {
        padding: 15px;
    }

    .assessment-actions {
        flex-direction: column-reverse;
    }

    .reset-btn,
    .submit-btn {
        width: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .progress-fill {
        transition: none;
    }
}
</style>
