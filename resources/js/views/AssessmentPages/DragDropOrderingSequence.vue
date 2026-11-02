<template>
    <div class="ordering-container">
        <!-- HEADER -->
        <div class="assessment-header">
            <div class="header-left">
                <div class="header-icon">
                    <i class="fas fa-list-ol"></i>
                </div>

                <div>
                    <span class="eyebrow">Virtual Hands-On Assessment</span>
                    <h1 class="title">Ordering Sequence</h1>
                </div>
            </div>

            <div class="step-count">{{ sequenceSteps.length }} Steps</div>
        </div>

        <!-- INSTRUCTION -->
        <div class="instruction-box">
            <div class="instruction-icon">
                <i class="fas fa-info"></i>
            </div>

            <div>
                <span class="instruction-label">Instruction</span>
                <p>{{ instruction }}</p>
            </div>
        </div>

        <!-- SEQUENCE -->
        <section class="sequence-section">
            <div class="section-heading">
                <div>
                    <span class="section-kicker">01</span>
                    <h2>Your Sequence</h2>
                </div>

                <span class="section-hint">
                    Drag each step into the correct position
                </span>
            </div>

            <div class="sequence-grid">
                <div
                    v-for="(step, index) in sequenceSteps"
                    :key="`drop-${index}`"
                    class="sequence-slot"
                    :class="{
                        'has-item': step.item,
                        'drag-over': false,
                    }"
                    @dragover="dragover"
                    @drop="drop($event, index)"
                    @dragleave="dragleave"
                >
                    <!-- NUMBER -->
                    <div class="slot-number">
                        {{ String(index + 1).padStart(2, "0") }}
                    </div>

                    <!-- EMPTY -->
                    <div v-if="!step.item" class="slot-empty">
                        <i class="fas fa-arrow-down"></i>
                        <span>Drop step here</span>
                    </div>

                    <!-- FILLED -->
                    <div v-else class="dropped-item">
                        <div class="item-content">
                            <span class="item-label">
                                {{ step.item.label }}
                            </span>
                        </div>

                        <button
                            class="remove-btn"
                            @click="removeFromSequence(index)"
                            title="Remove step"
                            aria-label="Remove step"
                        >
                            <i class="fas fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- AVAILABLE STEPS -->
        <section class="available-section">
            <div class="section-heading">
                <div>
                    <span class="section-kicker">02</span>
                    <h2>Available Steps</h2>
                </div>

                <span class="section-hint">
                    Drag a step to the sequence above
                </span>
            </div>

            <div class="steps-container">
                <div
                    v-for="step in availableSteps"
                    :key="`step-${step.id}`"
                    class="step-item"
                    :class="{ 'is-used': isStepUsed(step.id) }"
                    draggable="true"
                    @dragstart="dragstart($event, step)"
                    @dragend="dragend"
                >
                    <span class="step-drag">
                        <i class="fas fa-grip-vertical"></i>
                    </span>

                    <span class="step-label">
                        {{ step.label }}
                    </span>

                    <span v-if="isStepUsed(step.id)" class="step-used">
                        <i class="fas fa-check"></i>
                    </span>
                </div>
            </div>
        </section>

        <!-- ACTION BAR -->
        <div class="action-bar">
            <button
                class="reset-btn"
                @click="resetSequence"
                title="Reset sequence"
            >
                <i class="fas fa-rotate-left"></i>
                <span>Reset</span>
            </button>

            <button class="check-btn" @click="checkAnswer">
                <i class="fas fa-check"></i>
                <span>Check Sequence</span>
            </button>
        </div>

        <!-- FEEDBACK -->
        <transition name="fade">
            <div v-if="feedback" :class="['feedback', feedback.type]">
                <div class="feedback-icon">
                    <i :class="feedback.icon"></i>
                </div>

                <div class="feedback-content">
                    <strong>
                        {{
                            feedback.type === "success"
                                ? "Correct Sequence"
                                : "Check Your Sequence"
                        }}
                    </strong>

                    <span>
                        {{ feedback.message }}
                    </span>
                </div>

                <button
                    class="feedback-close"
                    @click="feedback = null"
                    aria-label="Close feedback"
                >
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";

const instruction = ref(
    "Arrange the steps for shutting down a computer properly.",
);

// Available steps to drag
const availableSteps = ref([
    { id: 1, label: "Click Start > Power" },
    { id: 2, label: "Close all programs" },
    { id: 3, label: "Turn off the monitor" },
    { id: 4, label: "Select Shut Down" },
]);

// Correct sequence
const correctSequence = [1, 2, 4, 3];

// Sequence slots
const sequenceSteps = ref([
    { item: null },
    { item: null },
    { item: null },
    { item: null },
]);

const draggedItem = ref(null);
const feedback = ref(null);
const svgWidth = ref(450);

const getCircleX = (index) => {
    const spacing = svgWidth.value / (sequenceSteps.value.length + 1);
    return spacing * (index + 1);
};

const dragstart = (event, step) => {
    draggedItem.value = step;
    event.dataTransfer.effectAllowed = "move";
};

const dragend = () => {
    draggedItem.value = null;
};

const dragover = (event) => {
    event.preventDefault();
    event.dataTransfer.dropEffect = "move";
    event.currentTarget.classList.add("drag-over");
};

const dragleave = (event) => {
    event.currentTarget.classList.remove("drag-over");
};

const drop = (event, index) => {
    event.preventDefault();
    event.currentTarget.classList.remove("drag-over");

    if (!draggedItem.value) return;

    // Remove item from other sequences if it exists
    sequenceSteps.value.forEach((step, i) => {
        if (step.item?.id === draggedItem.value.id) {
            sequenceSteps.value[i].item = null;
        }
    });

    // Add to this sequence
    sequenceSteps.value[index].item = draggedItem.value;
    draggedItem.value = null;
};

const removeFromSequence = (index) => {
    sequenceSteps.value[index].item = null;
    feedback.value = null;
};

const isStepUsed = (id) => {
    return sequenceSteps.value.some((step) => step.item?.id === id);
};

const resetSequence = () => {
    sequenceSteps.value = sequenceSteps.value.map(() => ({ item: null }));
    feedback.value = null;
};

const checkAnswer = () => {
    // Check if all slots are filled
    if (sequenceSteps.value.some((step) => !step.item)) {
        feedback.value = {
            type: "error",
            message: "Please fill all steps.",
            icon: "fas fa-exclamation-circle",
        };
        return;
    }

    // Check if sequence is correct
    const userSequence = sequenceSteps.value.map((step) => step.item.id);
    const isCorrect =
        JSON.stringify(userSequence) === JSON.stringify(correctSequence);

    if (isCorrect) {
        feedback.value = {
            type: "success",
            message: "Perfect! Your sequence is correct.",
            icon: "fas fa-check-circle",
        };
    } else {
        feedback.value = {
            type: "error",
            message: "Incorrect sequence. Try again!",
            icon: "fas fa-times-circle",
        };
    }
};
</script>

<style scoped>
* {
    box-sizing: border-box;
}

.ordering-container {
    width: 100%;
    max-width: 980px;
    margin: 0 auto;
    padding: 28px 24px 40px;

    color: #111111;
    background: #ffffff;

    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

/* =========================
   HEADER
========================= */

.assessment-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    padding-bottom: 20px;
    border-bottom: 1px solid #e5e5e5;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-icon {
    width: 42px;
    height: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 1px solid #111111;
    border-radius: 10px;

    background: #111111;
    color: #ffffff;

    font-size: 15px;
}

.eyebrow {
    display: block;

    margin-bottom: 3px;

    color: #737373;

    font-size: 10px;
    font-weight: 700;

    text-transform: uppercase;
    letter-spacing: 0.12em;
}

.title {
    margin: 0;

    color: #111111;

    font-size: 22px;
    line-height: 1.2;
    font-weight: 700;
    letter-spacing: -0.02em;
}

.step-count {
    padding: 6px 10px;

    border: 1px solid #e5e5e5;
    border-radius: 6px;

    background: #fafafa;

    color: #737373;

    font-size: 11px;
    font-weight: 600;
}

/* =========================
   INSTRUCTION
========================= */

.instruction-box {
    display: flex;
    align-items: flex-start;
    gap: 12px;

    margin: 22px 0 30px;
    padding: 14px 16px;

    border: 1px solid #e5e5e5;
    border-radius: 10px;

    background: #fafafa;
}

.instruction-icon {
    flex: 0 0 auto;

    width: 24px;
    height: 24px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: #111111;
    color: #ffffff;

    font-size: 10px;
}

.instruction-label {
    display: block;

    margin-bottom: 3px;

    color: #737373;

    font-size: 10px;
    font-weight: 700;

    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.instruction-box p {
    margin: 0;

    color: #222222;

    font-size: 13px;
    line-height: 1.5;
}

/* =========================
   SECTION HEADING
========================= */

.sequence-section,
.available-section {
    margin-bottom: 30px;
}

.section-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;

    gap: 20px;

    margin-bottom: 12px;
}

.section-heading > div {
    display: flex;
    align-items: baseline;
    gap: 9px;
}

.section-kicker {
    color: #a3a3a3;

    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
}

.section-heading h2 {
    margin: 0;

    color: #222222;

    font-size: 13px;
    font-weight: 700;
}

.section-hint {
    color: #a3a3a3;

    font-size: 11px;
}

/* =========================
   SEQUENCE
========================= */

.sequence-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);

    gap: 10px;
}

.sequence-slot {
    position: relative;

    min-height: 118px;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 24px 12px 12px;

    border: 1px dashed #cfcfcf;
    border-radius: 10px;

    background: #fafafa;

    transition:
        border-color 0.18s ease,
        background 0.18s ease,
        transform 0.18s ease;
}

.sequence-slot:hover {
    border-color: #999999;
    background: #f7f7f7;
}

.sequence-slot.drag-over {
    border-color: #111111;
    background: #f2f2f2;
    transform: translateY(-2px);
}

.sequence-slot.has-item {
    border-style: solid;
    border-color: #111111;
    background: #ffffff;
}

/* SLOT NUMBER */

.slot-number {
    position: absolute;
    top: 9px;
    left: 11px;

    color: #a3a3a3;

    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.08em;
}

/* EMPTY */

.slot-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 7px;

    color: #b5b5b5;

    font-size: 10px;
    font-weight: 600;

    text-align: center;
}

.slot-empty i {
    font-size: 11px;
}

/* DROPPED ITEM */

.dropped-item {
    width: 100%;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 8px;
}

.item-content {
    flex: 1;
    min-width: 0;
}

.item-label {
    display: block;

    color: #111111;

    font-size: 12px;
    line-height: 1.35;
    font-weight: 600;
    text-align: center;
}

.remove-btn {
    flex: 0 0 auto;

    width: 24px;
    height: 24px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 0;
    border-radius: 6px;

    background: #f0f0f0;
    color: #737373;

    cursor: pointer;

    transition:
        background 0.18s ease,
        color 0.18s ease;
}

.remove-btn:hover {
    background: #111111;
    color: #ffffff;
}

/* =========================
   AVAILABLE STEPS
========================= */

.steps-container {
    display: grid;
    grid-template-columns: repeat(2, 1fr);

    gap: 8px;
}

.step-item {
    min-height: 48px;

    display: flex;
    align-items: center;

    gap: 10px;

    padding: 10px 12px;

    border: 1px solid #e5e5e5;
    border-radius: 8px;

    background: #ffffff;

    cursor: grab;
    user-select: none;

    transition:
        border-color 0.18s ease,
        background 0.18s ease,
        transform 0.18s ease,
        opacity 0.18s ease;
}

.step-item:hover {
    border-color: #999999;
    background: #fafafa;
    transform: translateY(-1px);
}

.step-item:active {
    cursor: grabbing;
    transform: scale(0.99);
}

.step-item.is-used {
    background: #f5f5f5;
    opacity: 0.5;
}

.step-drag {
    flex: 0 0 auto;

    color: #b0b0b0;

    font-size: 11px;
}

.step-label {
    flex: 1;

    color: #222222;

    font-size: 12px;
    line-height: 1.35;
    font-weight: 500;
}

.step-used {
    flex: 0 0 auto;

    color: #111111;

    font-size: 10px;
}

/* =========================
   ACTION BAR
========================= */

.action-bar {
    display: flex;
    align-items: center;
    justify-content: flex-end;

    gap: 8px;

    padding-top: 18px;

    border-top: 1px solid #e5e5e5;
}

.reset-btn,
.check-btn {
    height: 38px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    gap: 7px;

    padding: 0 14px;

    border-radius: 7px;

    font-size: 11px;
    font-weight: 700;

    cursor: pointer;

    transition:
        background 0.18s ease,
        border-color 0.18s ease,
        transform 0.18s ease;
}

.reset-btn {
    border: 1px solid #e5e5e5;

    background: #ffffff;
    color: #555555;
}

.reset-btn:hover {
    border-color: #999999;
    background: #fafafa;
}

.check-btn {
    border: 1px solid #111111;

    background: #111111;
    color: #ffffff;
}

.check-btn:hover {
    background: #333333;
}

.reset-btn:active,
.check-btn:active {
    transform: scale(0.98);
}

/* =========================
   FEEDBACK
========================= */

.feedback {
    display: flex;
    align-items: center;

    gap: 11px;

    margin-top: 14px;
    padding: 12px 14px;

    border: 1px solid;
    border-radius: 8px;
}

.feedback-icon {
    flex: 0 0 auto;

    width: 28px;
    height: 28px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 7px;

    font-size: 11px;
}

.feedback-content {
    flex: 1;

    display: flex;
    flex-direction: column;

    gap: 2px;
}

.feedback-content strong {
    font-size: 11px;
    font-weight: 700;
}

.feedback-content span {
    font-size: 11px;
    line-height: 1.4;
}

.feedback-close {
    width: 26px;
    height: 26px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 0;
    border-radius: 6px;

    background: transparent;

    color: inherit;

    cursor: pointer;
}

.feedback-close:hover {
    background: rgba(0, 0, 0, 0.06);
}

.feedback.success {
    border-color: #b8b8b8;
    background: #f7f7f7;
    color: #111111;
}

.feedback.success .feedback-icon {
    background: #111111;
    color: #ffffff;
}

.feedback.error {
    border-color: #d5d5d5;
    background: #fafafa;
    color: #333333;
}

.feedback.error .feedback-icon {
    background: #333333;
    color: #ffffff;
}

/* =========================
   ANIMATION
========================= */

.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-5px);
}

/* =========================
   TABLET
========================= */

@media (max-width: 700px) {
    .ordering-container {
        padding: 22px 18px 32px;
    }

    .section-heading {
        align-items: flex-start;
        flex-direction: column;
        gap: 5px;
    }

    .section-hint {
        padding-left: 22px;
    }

    .sequence-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .steps-container {
        grid-template-columns: 1fr;
    }
}

/* =========================
   MOBILE
========================= */

@media (max-width: 480px) {
    .ordering-container {
        padding: 18px 14px 28px;
    }

    .assessment-header {
        align-items: flex-start;
    }

    .header-icon {
        width: 38px;
        height: 38px;
    }

    .title {
        font-size: 18px;
    }

    .step-count {
        font-size: 10px;
        padding: 5px 8px;
    }

    .instruction-box {
        margin-top: 18px;
        margin-bottom: 24px;
    }

    .sequence-grid {
        grid-template-columns: 1fr;
    }

    .sequence-slot {
        min-height: 72px;
    }

    .item-label {
        font-size: 11px;
    }

    .action-bar {
        justify-content: stretch;
    }

    .reset-btn,
    .check-btn {
        flex: 1;
    }
}
</style>
