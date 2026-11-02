<template>
    <div class="lab">
        <!-- TOP BAR -->
        <header class="topbar">
            <button class="icon-btn" type="button" aria-label="Go back" :disabled="showResult" @click="goBack">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
            </button>

            <div class="title">
                <span class="kicker">CSS Prep · COC {{ quiz.coc_number ?? "—" }} · Practical</span>
                <h1>{{ assessment.title || "Assembly and Disassembly" }}</h1>
            </div>

            <dl class="stats" aria-label="Practical progress">
                <div :class="{ warn: timeWarning }">
                    <dt>Time</dt>
                    <dd>{{ formattedTime }}</dd>
                </div>
                <div>
                    <dt>Steps</dt>
                    <dd>{{ completedActions }}/{{ totalActions }}</dd>
                </div>
                <div :class="{ bad: mistakeCount > 0 }">
                    <dt>Mistakes</dt>
                    <dd>{{ mistakeCount }}</dd>
                </div>
            </dl>
        </header>

        <!-- STAGE RAIL -->
        <nav class="rail" aria-label="Practical stages">
            <ol>
                <li v-for="step in railSteps" :key="step.key" :class="step.state"
                    :aria-current="step.state === 'current' ? 'step' : undefined">
                    <span class="rail-dot">
                        <i v-if="step.state === 'done'" class="fas fa-check" aria-hidden="true"></i>
                        <template v-else>{{ step.number }}</template>
                    </span>
                    <span>{{ step.label }}</span>
                </li>
            </ol>
        </nav>

        <!-- SAFETY WARNING -->
        <div class="warning" role="note">
            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
            <p>
                <strong>Safety first.</strong>
                Power off, unplug the cord, and wear an anti-static wrist strap before you touch any part.
            </p>
        </div>

        <!-- Screen reader announcements -->
        <p class="sr-only" aria-live="polite" aria-atomic="true">{{ announcement }}</p>

        <!-- LOADING -->
        <section v-if="loading" class="state" role="status" aria-live="polite">
            <span class="spinner" aria-hidden="true"></span>
            <p class="state-title">Preparing the practical</p>
            <p class="state-text">Loading the chassis and starting your attempt.</p>
        </section>

        <!-- LOAD ERROR -->
        <section v-else-if="loadError" class="state" role="alert">
            <span class="state-icon" aria-hidden="true"><i class="fas fa-triangle-exclamation"></i></span>
            <p class="state-title">The practical could not start</p>
            <p class="state-text">{{ loadError }}</p>
            <div class="state-actions">
                <button class="btn btn-primary" type="button" @click="load">Try again</button>
                <button class="btn btn-ghost" type="button" @click="goToAssessments">Back to assessments</button>
            </div>
        </section>

        <template v-else>
            <div v-if="attemptEnded && !showResult" class="banner banner-warn" role="status">
                <i class="fas fa-clock" aria-hidden="true"></i>
                <span>{{ endedReason }}</span>
            </div>

            <div v-if="submitError" class="banner banner-error" role="alert">
                <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                <span>{{ submitError }}</span>
            </div>

            <main class="stage">
                <!-- BENCH -->
                <section class="bench" aria-label="Practical workspace">
                    <!-- HAND -->
                    <div class="hand" :class="{ held: !!currentComponent }">
                        <template v-if="currentComponent">
                            <span class="hand-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false">
                                    <component v-for="(shape, i) in iconShapes(currentComponent)" :key="i"
                                        :is="shape.tag" v-bind="shape.attrs" />
                                </svg>
                            </span>
                            <div class="hand-copy">
                                <span class="kicker">Holding</span>
                                <strong>{{ displayName(currentComponent) }}</strong>
                            </div>
                            <span class="hand-hint">{{ holdingHint() }}</span>
                            <button class="btn btn-ghost btn-sm" type="button" @click="releaseHeld">Put down</button>
                        </template>
                        <span v-else class="hand-empty">
                            <i class="fas fa-hand" aria-hidden="true"></i>
                            Hands free. Select a part to pick it up.
                        </span>
                        <span class="phase-tag">{{ phaseLabel }}</span>
                    </div>

                    <!-- CHASSIS -->
                    <div class="case" role="group" aria-label="Computer chassis">
                        <p class="case-title">Chassis</p>

                        <div class="case-grid">
                            <button v-for="slot in CASE_SLOTS" :key="slot.key" type="button" class="slot"
                                :class="slotClasses(slot.key)" :style="{ gridArea: slot.area }"
                                :aria-label="slotLabel(slot.key)"
                                :aria-disabled="slotDisabled(slot.key) ? 'true' : 'false'"
                                :draggable="canDragSlot(slot.key) ? 'true' : 'false'" @click="onSlotClick(slot.key)"
                                @dragstart="onDragStart(slot.key, $event)" @dragend="onDragEnd"
                                @dragover="onDragOver($event, slotId(slot.key))"
                                @dragleave="onDragLeave($event, slotId(slot.key))"
                                @drop.prevent="onDrop($event, slotId(slot.key))">
                                <span class="slot-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" focusable="false">
                                        <component v-for="(shape, i) in iconShapes(slot.key)" :key="i" :is="shape.tag"
                                            v-bind="shape.attrs" />
                                    </svg>
                                </span>
                                <span class="slot-name">{{ displayName(slot.key) }}</span>
                                <span class="slot-state">{{ slotStateText(slot.key) }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- TRAYS -->
                    <div class="trays">
                        <section class="tray" :class="zoneClasses('parts_tray')" aria-labelledby="table-heading"
                            @dragover="onDragOver($event, zoneId('parts_tray'))"
                            @dragleave="onDragLeave($event, zoneId('parts_tray'))"
                            @drop.prevent="onDrop($event, zoneId('parts_tray'))">
                            <header class="tray-head">
                                <h2 id="table-heading">Parts table <span class="count">{{ tableParts.length }}</span>
                                </h2>
                                <button type="button" class="place-btn" :class="{ ready: zoneAccepts('parts_tray') }"
                                    :aria-disabled="zoneAccepts('parts_tray') ? 'false' : 'true'"
                                    @click="placeInZone('parts_tray')">
                                    <i class="fas fa-arrow-down" aria-hidden="true"></i> Place here
                                </button>
                            </header>

                            <p v-if="!tableParts.length" class="tray-empty">Empty</p>
                            <ul v-else class="parts">
                                <li v-for="item in tableParts" :key="item.component_key">
                                    <button type="button" class="part" :class="partClasses(item.component_key)"
                                        :aria-label="partLabel(item.component_key)"
                                        :aria-pressed="currentComponent === item.component_key ? 'true' : 'false'"
                                        :aria-disabled="isDraggable(item.component_key) ? 'false' : 'true'"
                                        :draggable="isDraggable(item.component_key) ? 'true' : 'false'"
                                        @click="selectPart(item.component_key)"
                                        @dragstart="onDragStart(item.component_key, $event)" @dragend="onDragEnd">
                                        <span class="part-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" focusable="false">
                                                <component v-for="(shape, i) in iconShapes(item.component_key)" :key="i"
                                                    :is="shape.tag" v-bind="shape.attrs" />
                                            </svg>
                                        </span>
                                        <span class="part-name">{{ displayName(item.component_key) }}</span>
                                    </button>
                                </li>
                            </ul>
                        </section>

                        <section class="tray" :class="zoneClasses('cable_tray')" aria-labelledby="cable-heading"
                            @dragover="onDragOver($event, zoneId('cable_tray'))"
                            @dragleave="onDragLeave($event, zoneId('cable_tray'))"
                            @drop.prevent="onDrop($event, zoneId('cable_tray'))">
                            <header class="tray-head">
                                <h2 id="cable-heading">Cable tray <span class="count">{{ cableParts.length }}</span>
                                </h2>
                                <button type="button" class="place-btn" :class="{ ready: zoneAccepts('cable_tray') }"
                                    :aria-disabled="zoneAccepts('cable_tray') ? 'false' : 'true'"
                                    @click="placeInZone('cable_tray')">
                                    <i class="fas fa-arrow-down" aria-hidden="true"></i> Place here
                                </button>
                            </header>

                            <p v-if="!cableParts.length" class="tray-empty">Empty</p>
                            <ul v-else class="parts">
                                <li v-for="item in cableParts" :key="item.component_key">
                                    <button type="button" class="part" :class="partClasses(item.component_key)"
                                        :aria-label="partLabel(item.component_key)"
                                        :aria-pressed="currentComponent === item.component_key ? 'true' : 'false'"
                                        :aria-disabled="isDraggable(item.component_key) ? 'false' : 'true'"
                                        :draggable="isDraggable(item.component_key) ? 'true' : 'false'"
                                        @click="selectPart(item.component_key)"
                                        @dragstart="onDragStart(item.component_key, $event)" @dragend="onDragEnd">
                                        <span class="part-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" focusable="false">
                                                <component v-for="(shape, i) in iconShapes(item.component_key)" :key="i"
                                                    :is="shape.tag" v-bind="shape.attrs" />
                                            </svg>
                                        </span>
                                        <span class="part-name">{{ displayName(item.component_key) }}</span>
                                    </button>
                                </li>
                            </ul>
                        </section>
                    </div>

                    <p class="bench-help">
                        Select a part, then select where it goes. On desktop you can also drag parts.
                        Press Esc to put a part down.
                    </p>
                </section>

                <!-- SIDE -->
                <aside class="side" aria-label="Checklist, current step, and activity">
                    <section class="card">
                        <button class="accordion" type="button" :aria-expanded="showPrep ? 'true' : 'false'"
                            aria-controls="prep-list" @click="showPrep = !showPrep">
                            <span>
                                <span class="kicker">Safety check</span>
                                <strong class="card-title">
                                    {{ phase === "assemble" ? "Before assembly" : "Before disassembly" }}
                                </strong>
                            </span>
                            <span class="count" :class="{ ready: prepDone }">{{ prepCount }}/{{ prepItems.length
                                }}</span>
                        </button>

                        <ul id="prep-list" v-show="showPrep || !prepDone" class="tasks">
                            <li v-for="(item, index) in prepItems" :key="item">
                                <label class="task" :class="{ done: currentPrepChecked[index], muted: !editablePrep }">
                                    <input class="sr-check" type="checkbox" :checked="currentPrepChecked[index]"
                                        :disabled="!editablePrep" @change="togglePrep(index)" />
                                    <span class="box" aria-hidden="true">
                                        <i v-if="currentPrepChecked[index]" class="fas fa-check"></i>
                                    </span>
                                    <span>{{ item }}</span>
                                </label>
                            </li>
                        </ul>
                    </section>

                    <section class="card guide" aria-live="polite">
                        <span class="kicker">Current step</span>
                        <strong class="guide-title">{{ statusTitle }}</strong>
                        <p class="muted">{{ statusText }}</p>

                        <div class="progress" role="progressbar" aria-label="Steps completed" :aria-valuemin="0"
                            :aria-valuemax="totalActions" :aria-valuenow="completedActions">
                            <span :style="{ width: progressPercent + '%' }"></span>
                        </div>
                        <p class="muted small">{{ completedActions }} of {{ totalActions }} steps done</p>

                        <button v-if="procedureComplete && !attemptEnded" class="btn btn-primary btn-block"
                            type="button" :disabled="completing || busy" @click="completeAssessment">
                            <i :class="completing ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'"
                                aria-hidden="true"></i>
                            {{ completing ? "Submitting…" : "Submit assessment" }}
                        </button>
                    </section>

                    <section class="card log" aria-label="Activity">
                        <span class="kicker">Activity</span>
                        <ol v-if="logs.length">
                            <li v-for="entry in logs" :key="entry.id" :class="entry.type">
                                <i :class="iconForLog(entry.type)" aria-hidden="true"></i>
                                <span>{{ entry.text }}</span>
                                <time>{{ entry.time }}</time>
                            </li>
                        </ol>
                        <p v-else class="muted small">No moves yet.</p>
                    </section>
                </aside>
            </main>

            <!-- TOAST (visual; the live region announces it) -->
            <div v-if="lastFeedback" :key="lastFeedback.id" class="toast" :class="lastFeedback.correct ? 'ok' : 'bad'"
                aria-hidden="true">
                <i :class="lastFeedback.correct ? 'fas fa-circle-check' : 'fas fa-circle-exclamation'"></i>
                <span>{{ lastFeedback.message }}</span>
            </div>
        </template>

        <!-- RESULT -->
        <div v-if="showResult" class="overlay">
            <section class="dialog" role="dialog" aria-modal="true" aria-labelledby="result-title"
                aria-describedby="result-message">
                <span class="kicker">Practical result</span>
                <h2 id="result-title" ref="resultTitle" tabindex="-1">{{ resultHeading }}</h2>

                <p class="result-score" :class="{ bad: !resultPassed }">{{ resultPercentage }}%</p>

                <dl class="stat-row">
                    <div>
                        <dt>Steps</dt>
                        <dd>{{ resultCompleted }}/{{ totalActions }}</dd>
                    </div>
                    <div>
                        <dt>Mistakes</dt>
                        <dd>{{ resultMistakes }}</dd>
                    </div>
                </dl>

                <p id="result-message" class="result-message">{{ resultMessage }}</p>

                <button v-if="!resultTimedOut" class="btn btn-primary btn-block" type="button" @click="goToRecords">
                    View records
                </button>
                <button v-else class="btn btn-primary btn-block" type="button" @click="goToAssessments">
                    Back to assessments
                </button>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { hardwareProcedureRequest } from '@/assessmentrequests/hardwareProcedure'

/* =========================================================
   CONSTANTS
========================================================= */

const TOTAL_SECONDS = 10 * 60
const WARNING_SECONDS = 2 * 60
const PASSING_SCORE = 75
const LOG_LIMIT = 6

// Fixed slots inside the chassis. Each slot accepts only its own component.
const CASE_SLOTS = [
    { key: 'side_panel', area: 'cov' },
    { key: 'cpu_heatsink', area: 'cpu' },
    { key: 'ram', area: 'ram' },
    { key: 'system_fan', area: 'fan' },
    { key: 'motherboard', area: 'mb' },
    { key: 'psu', area: 'psu' },
    { key: 'expansion_card', area: 'exp' },
    { key: 'storage_drive', area: 'drv' },
    { key: 'internal_cables', area: 'cab' },
]

const DISASSEMBLY_PREP = [
    'Turn off the computer and switch off the power supply.',
    'Unplug the power cord from the wall outlet.',
    'Disconnect the monitor, keyboard, mouse, and network cable.',
    'Put on the anti-static wrist strap or ESD gloves.',
    'Clip the wrist strap to a grounded metal surface.',
    'Place the system unit on a clean, non-carpeted table.',
    'Prepare a Phillips screwdriver and a container for the screws.',
]

const ASSEMBLY_PREP = [
    'Put on the anti-static wrist strap or ESD gloves again.',
    'Check that every part is on the table and cables are in the cable tray.',
    'Keep the screw container within reach.',
    'Check for loose screws or tools inside the chassis before closing it.',
]

// Static icon shapes, rendered as Vue elements (no raw HTML).
const rect = (x, y, width, height, rx = 0) => ({ tag: 'rect', attrs: { x, y, width, height, rx } })
const circle = (cx, cy, r) => ({ tag: 'circle', attrs: { cx, cy, r } })
const path = (d) => ({ tag: 'path', attrs: { d } })

const ICONS = {
    side_panel: [rect(3, 3, 18, 18, 2), circle(6.5, 6.5, 0.7), circle(17.5, 6.5, 0.7), circle(6.5, 17.5, 0.7), circle(17.5, 17.5, 0.7)],
    motherboard: [rect(3, 3, 18, 18, 1.5), rect(6, 6, 5, 5), path('M14 5v9M16.5 5v9M19 5v9M6 15h12M6 18h5')],
    cpu_heatsink: [path('M4 4h16M4 7.5h16M4 11h16M4 14.5h16'), rect(4, 17, 16, 3, 1)],
    ram: [rect(2, 7, 20, 10, 1), path('M6 7v3M10 7v3M14 7v3M18 7v3M6 17v3M18 17v3')],
    expansion_card: [path('M4 4h10l4 4v12H4z'), path('M8 16h8M8 18.5h8M8 8h3v3H8z')],
    storage_drive: [rect(3, 6, 18, 12, 2), path('M7 10h5M7 14h8'), circle(17, 12, 1)],
    psu: [rect(3, 4, 18, 16, 2), circle(14, 12, 4), path('M11.5 12h5M14 9.5v5'), rect(5, 6, 3, 2, 0.4)],
    system_fan: [circle(12, 12, 9), circle(12, 12, 2), path('M12 10c0-3 1-5 3-5M14 12c3 0 5 1 5 3M10 14c0 3-1 5-3 5')],
    internal_cables: [rect(2, 6, 4, 12, 1), rect(18, 6, 4, 12, 1), path('M6 9h12M6 12h12M6 15h12')],
}

const FALLBACK_ICON = [rect(3, 3, 18, 18, 2)]

/* =========================================================
   ROUTER
========================================================= */

const route = useRoute()
const router = useRouter()

const quizId = computed(() => Number(route.params.quizId || route.params.id || route.query.quiz))

/* =========================================================
   STATE
========================================================= */

const quiz = ref({})
const assessment = ref({})
const catalog = ref([])
const passingScore = ref(PASSING_SCORE)

const attemptId = ref(null)
const installedState = ref({})

const completedActions = ref(0)
const totalActions = ref(0)
const mistakeCount = ref(0)

const phase = ref('disassemble')
const procedureComplete = ref(false)

const attemptEnded = ref(false)
const endedReason = ref('')
const submitted = ref(false)

const deadlineMs = ref(null)
const remainingSeconds = ref(TOTAL_SECONDS)
let timer = null

const loading = ref(true)
const loadError = ref('')
const actionPending = ref(false)
const completing = ref(false)
const submitError = ref('')

const currentComponent = ref(null)
const draggedKey = ref(null)
const activeTarget = ref(null)
const flashTarget = ref(null)

const showPrep = ref(true)
const prepChecked = ref({
    disassemble: DISASSEMBLY_PREP.map(() => false),
    assemble: ASSEMBLY_PREP.map(() => false),
})

const logs = ref([])
const lastFeedback = ref(null)
const announcement = ref('')
let feedbackTimer = null
let flashTimer = null
let logId = 0

const showResult = ref(false)
const resultPassed = ref(false)
const resultTimedOut = ref(false)
const resultPercentage = ref(0)
const resultCompleted = ref(0)
const resultMistakes = ref(0)
const resultTitle = ref(null)

/* =========================================================
   COMPUTED
========================================================= */

const busy = computed(() => actionPending.value || completing.value)

const prepKey = computed(() => (phase.value === 'assemble' ? 'assemble' : 'disassemble'))
const prepItems = computed(() => (prepKey.value === 'assemble' ? ASSEMBLY_PREP : DISASSEMBLY_PREP))
const currentPrepChecked = computed(() => prepChecked.value[prepKey.value])
const prepCount = computed(() => currentPrepChecked.value.filter(Boolean).length)
const prepDone = computed(() => prepCount.value === prepItems.value.length)
const editablePrep = computed(() => !loading.value && !attemptEnded.value && !procedureComplete.value)

const interactive = computed(() =>
    !loading.value &&
    !loadError.value &&
    !attemptEnded.value &&
    !submitted.value &&
    !showResult.value &&
    !procedureComplete.value &&
    !busy.value &&
    phase.value !== 'complete' &&
    prepDone.value
)

const phaseLabel = computed(() => {
    if (phase.value === 'disassemble') return 'Disassembly'
    if (phase.value === 'assemble') return 'Assembly'
    return 'Complete'
})

const railSteps = computed(() => {
    const finished = phase.value === 'complete'

    return [
        { key: 'disassemble', number: 1, label: 'Disassemble', state: phase.value === 'disassemble' ? 'current' : 'done' },
        { key: 'assemble', number: 2, label: 'Assemble', state: phase.value === 'assemble' ? 'current' : finished ? 'done' : '' },
        {
            key: 'submit',
            number: 3,
            label: 'Submit',
            state: submitted.value ? 'done' : finished ? 'current' : '',
        },
    ]
})

const progressPercent = computed(() =>
    totalActions.value ? Math.round((completedActions.value / totalActions.value) * 100) : 0
)

const timeWarning = computed(() => !attemptEnded.value && remainingSeconds.value <= WARNING_SECONDS)

const formattedTime = computed(() => {
    const seconds = Math.max(0, remainingSeconds.value)
    const minutes = Math.floor(seconds / 60)
    const remainder = seconds % 60

    return `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`
})

const tableParts = computed(() =>
    catalog.value.filter((item) => !componentVisible(item.component_key) && item.component_key !== 'internal_cables')
)

const cableParts = computed(() =>
    catalog.value.filter((item) => !componentVisible(item.component_key) && item.component_key === 'internal_cables')
)

const statusTitle = computed(() => {
    if (attemptEnded.value) return 'Attempt ended'
    if (!prepDone.value) return 'Complete the safety check'
    if (procedureComplete.value) return 'All parts handled'
    return phase.value === 'assemble' ? 'Assemble the system unit' : 'Disassemble the system unit'
})

const statusText = computed(() => {
    if (attemptEnded.value) return 'Start a new attempt from Assessments to try again.'
    if (!prepDone.value) return 'Tick every safety task to unlock the bench.'
    if (procedureComplete.value) return 'Submit to save this attempt to your records.'
    return phase.value === 'assemble'
        ? 'Pick up a part from the table and place it in its empty slot.'
        : 'Pick up a part from the chassis and place it in the correct tray.'
})

const resultHeading = computed(() => {
    if (resultTimedOut.value) return 'Time expired'
    return resultPassed.value ? 'Assessment passed' : 'Assessment completed'
})

const resultMessage = computed(() => {
    if (resultTimedOut.value) {
        return 'The 10-minute limit ended before every part was handled. This attempt was not submitted.'
    }

    const score = Number(resultPercentage.value)

    if (score >= 90) return 'Excellent. Precise handling with very few mistakes.'
    if (score >= passingScore.value) return 'Passed. Review your mistakes to improve your handling.'

    return 'Not passed yet. Review the disassembly and assembly order, then try again.'
})

/* =========================================================
   HELPERS
========================================================= */

function normalizeComponent(item) {
    return {
        component_key: item.component_key,
        display_name: item.display_name || item.component_key,
        item_type: item.item_type ?? null,
        quantity: Number(item.quantity ?? 1),
        image: item.image || '',
    }
}

function componentVisible(key) {
    return Boolean(installedState.value[key])
}

function displayName(key) {
    return catalog.value.find((item) => item.component_key === key)?.display_name || key
}

function iconShapes(key) {
    return ICONS[key] || FALLBACK_ICON
}

function holdingHint() {
    return phase.value === 'assemble' ? 'Choose an empty slot in the chassis.' : 'Choose a tray to place it in.'
}

function sourceZoneFor(key) {
    if (componentVisible(key)) return 'case'
    return key === 'internal_cables' ? 'cable_tray' : 'parts_tray'
}

function slotId(key) {
    return `slot:${key}`
}

function zoneId(key) {
    return `zone:${key}`
}

function parseTarget(id) {
    const [kind, key] = id.split(':')
    return { kind, key }
}

function errorText(error, fallback) {
    const status = error?.response?.status

    if (status === 401 || status === 419) return 'Your session has expired. Sign in again to continue.'
    return error?.response?.data?.message || fallback
}

// Disassembly: an installed part can be picked up. Assembly: a loose part can be picked up.
function isDraggable(key) {
    if (!interactive.value) return false

    return phase.value === 'disassemble' ? componentVisible(key) : !componentVisible(key)
}

// Disassembly: a held part goes to a tray. Assembly: a held part goes to an empty slot.
function targetAccepts(id) {
    if (!interactive.value || !currentComponent.value) return false

    const { kind, key } = parseTarget(id)
    const held = currentComponent.value

    if (phase.value === 'disassemble') {
        return kind === 'zone' && componentVisible(held)
    }

    return kind === 'slot' && !componentVisible(held) && !componentVisible(key)
}

function slotAccepts(key) {
    return targetAccepts(slotId(key))
}

function zoneAccepts(key) {
    return targetAccepts(zoneId(key))
}

function canDragSlot(key) {
    return componentVisible(key) && isDraggable(key)
}

function slotDisabled(key) {
    return !canDragSlot(key) && !slotAccepts(key)
}

function slotClasses(key) {
    return {
        occupied: componentVisible(key),
        grab: canDragSlot(key),
        accepts: slotAccepts(key),
        over: activeTarget.value === slotId(key),
        flash: flashTarget.value === slotId(key),
        held: currentComponent.value === key,
        done: procedureComplete.value && componentVisible(key),
        cover: key === 'side_panel',
    }
}

function zoneClasses(key) {
    return {
        accepts: zoneAccepts(key),
        over: activeTarget.value === zoneId(key),
        flash: flashTarget.value === zoneId(key),
    }
}

function partClasses(key) {
    return {
        grab: isDraggable(key),
        held: currentComponent.value === key,
    }
}

function slotStateText(key) {
    if (componentVisible(key)) return canDragSlot(key) ? 'Select to remove' : 'Installed'
    if (slotAccepts(key)) return 'Place here'
    return 'Empty'
}

function slotLabel(key) {
    const name = displayName(key)

    if (componentVisible(key)) {
        return `${name}, installed.${canDragSlot(key) ? ' Select to remove it.' : ''}`
    }

    if (slotAccepts(key)) {
        return `${name} slot, empty. Place ${displayName(currentComponent.value)} here.`
    }

    return `${name} slot, empty.`
}

function partLabel(key) {
    const name = displayName(key)

    if (currentComponent.value === key) return `${name}, held. Select to put it down.`
    if (isDraggable(key)) return `${name}. Select to pick it up.`
    return `${name}. Not available yet.`
}

function iconForLog(type) {
    if (type === 'ok') return 'fas fa-circle-check'
    if (type === 'bad') return 'fas fa-circle-exclamation'
    return 'fas fa-circle-info'
}

function announce(message) {
    announcement.value = message
}

function addLog(text, type = 'info') {
    const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })

    logs.value = [{ id: ++logId, text, type, time }, ...logs.value].slice(0, LOG_LIMIT)
}

function notify(correct, message) {
    lastFeedback.value = { id: Date.now(), correct, message }
    announce(message)
    addLog(message, correct ? 'ok' : 'bad')

    window.clearTimeout(feedbackTimer)
    feedbackTimer = window.setTimeout(() => {
        lastFeedback.value = null
    }, 2800)

    if (!correct) navigator.vibrate?.(60)
}

function flashAt(id) {
    flashTarget.value = id
    window.clearTimeout(flashTimer)
    flashTimer = window.setTimeout(() => {
        flashTarget.value = null
    }, 500)
}

function togglePrep(index) {
    const list = currentPrepChecked.value
    list[index] = !list[index]
}

/* =========================================================
   PICK UP, HOLD, PLACE
========================================================= */

function selectPart(key) {
    if (!interactive.value) {
        if (!prepDone.value && !attemptEnded.value) {
            announce('Finish the safety check before you start.')
        }
        return
    }

    if (!isDraggable(key)) {
        announce(`${displayName(key)} is not available yet.`)
        return
    }

    if (currentComponent.value === key) {
        releaseHeld()
        return
    }

    currentComponent.value = key
    announce(`Holding ${displayName(key)}. ${holdingHint()}`)
}

function releaseHeld() {
    if (!currentComponent.value) return

    announce(`${displayName(currentComponent.value)} put down.`)
    currentComponent.value = null
}

function onSlotClick(key) {
    if (componentVisible(key) && phase.value === 'disassemble') {
        selectPart(key)
        return
    }

    tapTarget(slotId(key))
}

function placeInZone(key) {
    tapTarget(zoneId(key))
}

function tapTarget(id) {
    if (!interactive.value) return

    if (!currentComponent.value) {
        announce('Select a part first, then choose where it goes.')
        return
    }

    if (!targetAccepts(id)) {
        announce('That place is not available for this part right now.')
        return
    }

    placeOn(id)
}

function onDragStart(key, event) {
    if (!isDraggable(key)) {
        event?.preventDefault()
        return
    }

    draggedKey.value = key
    currentComponent.value = key

    if (event?.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move'
        event.dataTransfer.setData('text/plain', key)
    }
}

function onDragEnd() {
    draggedKey.value = null
    activeTarget.value = null
}

function onDragOver(event, id) {
    if (!targetAccepts(id)) return

    event.preventDefault()
    activeTarget.value = id

    if (event.dataTransfer) event.dataTransfer.dropEffect = 'move'
}

function onDragLeave(event, id) {
    if (event.currentTarget?.contains(event.relatedTarget)) return
    if (activeTarget.value === id) activeTarget.value = null
}

function onDrop(event, id) {
    activeTarget.value = null

    const key = draggedKey.value || event?.dataTransfer?.getData('text/plain') || null
    draggedKey.value = null

    if (!key || !isDraggable(key)) return

    currentComponent.value = key

    if (targetAccepts(id)) placeOn(id)
}

// Correct placement is sent as the proper destination. A wrong slot is sent as "wrong_slot",
// which the server never accepts, so it is recorded as a mistake.
function placeOn(id) {
    const { key } = parseTarget(id)
    const held = currentComponent.value

    if (!held) return

    const destination = phase.value === 'disassemble'
        ? key
        : held === key
            ? 'case'
            : 'wrong_slot'

    performAction(held, destination, id)
}

/* =========================================================
   PROGRESS
========================================================= */

function updateInstalledComponents(components) {
    const next = {}

    components.forEach((component) => {
        const key = typeof component === 'string' ? component : component?.component_key
        if (key) next[key] = true
    })

    installedState.value = next
}

function applyProgress(data) {
    completedActions.value = Number(data.completed_actions ?? completedActions.value)
    totalActions.value = Number(data.total_actions ?? totalActions.value)
    mistakeCount.value = Number(data.mistake_count ?? mistakeCount.value)
    phase.value = data.phase || phase.value
    procedureComplete.value = Boolean(data.procedure_complete)

    if (Array.isArray(data.installed_components)) {
        updateInstalledComponents(data.installed_components)
    }
}

/* =========================================================
   ACTIONS
========================================================= */

async function performAction(componentKey, destinationZone, targetId) {
    if (!attemptId.value || !interactive.value) return

    actionPending.value = true

    const verb = phase.value === 'assemble' ? 'installed' : 'removed'
    const performed = phase.value === 'assemble' ? 'install' : 'remove'

    try {
        const { data } = await hardwareProcedureRequest.action(quizId.value, attemptId.value, {
            component_key: componentKey,
            performed_action: performed,
            source_zone: sourceZoneFor(componentKey),
            destination_zone: destinationZone,
        })

        if (!data?.success) {
            throw new Error(data?.message || 'The server did not record this move.')
        }

        const result = data.data ?? {}

        applyProgress(result)
        currentComponent.value = null

        if (result.allowed === true) {
            notify(true, `${displayName(componentKey)} ${verb} correctly.`)
        } else if (result.procedure_complete) {
            notify(false, 'All steps are already done. Submit the assessment.')
        } else {
            flashAt(targetId)
            notify(false, 'Not the correct move. It is recorded as a mistake.')
        }
    } catch (error) {
        console.error('Hardware action failed:', error)
        handleActionError(error)
    } finally {
        actionPending.value = false
    }
}

function handleActionError(error) {
    const status = error?.response?.status

    if (status === 410) {
        expireAttempt()
        return
    }

    if (status === 409) {
        currentComponent.value = null
        endAttempt('This attempt was already submitted. Open Records to view it.')
        return
    }

    // No response: the move may or may not have been saved, so check the attempt.
    if (!error?.response) {
        notify(false, 'Connection lost. This move was not confirmed.')
        resyncAfterError()
        return
    }

    notify(false, errorText(error, 'Could not record this move. Try again.'))
}

async function resyncAfterError() {
    try {
        const { data } = await hardwareProcedureRequest.start(quizId.value)
        const attempt = data?.data

        if (!attempt) return

        if (Number(attempt.attempt_id) !== Number(attemptId.value)) {
            endAttempt('This attempt has ended. Start a new attempt from Assessments.')
            return
        }

        applyProgress(attempt)
        currentComponent.value = null
    } catch {
        announce('Still cannot reach the server. Check your connection, then try again.')
    }
}

/* =========================================================
   LOAD
========================================================= */

function parseStart(value) {
    if (!value) return null

    // Strip microseconds so every browser can parse the timestamp.
    const normalized = String(value).replace(' ', 'T').replace(/\.\d+/, '')
    const time = Date.parse(normalized)

    return Number.isNaN(time) ? null : time
}

function computeDeadline(attempt) {
    if (typeof attempt.remaining_seconds === 'number') {
        return Date.now() + attempt.remaining_seconds * 1000
    }

    const started = parseStart(attempt.started_at)
    return (started ?? Date.now()) + TOTAL_SECONDS * 1000
}

async function load() {
    loading.value = true
    loadError.value = ''

    try {
        const { data } = await hardwareProcedureRequest.get(quizId.value)

        if (!data?.success || !data?.data) {
            throw new Error('Invalid hardware API response.')
        }

        const hardware = data.data

        quiz.value = hardware.quiz ?? {}
        assessment.value = hardware.assessment ?? {}
        catalog.value = (hardware.components ?? []).map(normalizeComponent)
        passingScore.value = Number(hardware.passing_score ?? PASSING_SCORE)

        const { data: started } = await hardwareProcedureRequest.start(quizId.value)

        if (!started?.success || !started?.data) {
            throw new Error('Unable to start the practical attempt.')
        }

        const attempt = started.data

        attemptId.value = attempt.attempt_id
        totalActions.value = Number(attempt.total_actions ?? hardware.total_actions ?? 0)
        applyProgress(attempt)

        deadlineMs.value = computeDeadline(attempt)
        startTimer()

        addLog('Practical started. Complete the safety check to begin.', 'info')
        announce(`Practical ready. Phase: ${phaseLabel.value}.`)
    } catch (error) {
        console.error('Failed to load hardware practical:', error)
        loadError.value = errorText(error, 'Unable to load this practical. Check your connection and try again.')
    } finally {
        loading.value = false
    }
}

/* =========================================================
   TIMER
========================================================= */

function updateRemaining() {
    if (!deadlineMs.value) {
        remainingSeconds.value = TOTAL_SECONDS
        return
    }

    remainingSeconds.value = Math.max(0, Math.ceil((deadlineMs.value - Date.now()) / 1000))
}

function startTimer() {
    window.clearInterval(timer)
    updateRemaining()

    if (remainingSeconds.value <= 0) {
        expireAttempt()
        return
    }

    timer = window.setInterval(() => {
        if (showResult.value || attemptEnded.value) {
            window.clearInterval(timer)
            return
        }

        updateRemaining()

        if (remainingSeconds.value <= 0) expireAttempt()
    }, 1000)
}

function expireAttempt() {
    endAttempt('Time is up. This attempt was not submitted.', { timedOut: true })
}

function endAttempt(reason, { timedOut = false } = {}) {
    if (attemptEnded.value) return

    attemptEnded.value = true
    endedReason.value = reason
    remainingSeconds.value = 0

    window.clearInterval(timer)

    currentComponent.value = null
    draggedKey.value = null
    activeTarget.value = null

    announce(reason)
    addLog(reason, 'bad')

    if (timedOut) {
        resultTimedOut.value = true
        resultPassed.value = false
        resultPercentage.value = 0
        resultCompleted.value = completedActions.value
        resultMistakes.value = mistakeCount.value
        showResult.value = true
    }
}

/* =========================================================
   SUBMIT
========================================================= */

async function completeAssessment() {
    if (!attemptId.value || attemptEnded.value || submitted.value || busy.value) return

    completing.value = true
    submitError.value = ''

    try {
        const { data } = await hardwareProcedureRequest.complete(quizId.value, attemptId.value)

        if (!data?.success) {
            throw new Error(data?.message || 'Unable to submit this attempt.')
        }

        const result = data.data ?? {}

        resultTimedOut.value = false
        resultPassed.value = Boolean(result.passed)
        resultPercentage.value = Number(result.percentage ?? 0)
        resultCompleted.value = Number(result.completed_actions ?? completedActions.value)
        resultMistakes.value = Number(result.mistake_count ?? mistakeCount.value)

        submitted.value = true
        window.clearInterval(timer)
        showResult.value = true

        addLog(resultPassed.value ? 'Assessment passed.' : 'Assessment completed.', resultPassed.value ? 'ok' : 'info')
        announce(resultPassed.value ? 'Assessment passed.' : 'Assessment completed.')
    } catch (error) {
        console.error('Failed to submit practical:', error)

        const status = error?.response?.status

        if (status === 410) {
            expireAttempt()
            return
        }

        if (status === 409) {
            endAttempt('This attempt was already submitted. Open Records to view it.')
            return
        }

        submitError.value = errorText(error, 'Could not submit this attempt. Please try again.')
        announce(submitError.value)
    } finally {
        completing.value = false
    }
}

/* =========================================================
   NAVIGATION, KEYBOARD, WATCHERS
========================================================= */

function goBack() {
    if (!showResult.value) router.back()
}

function goToRecords() {
    router.push({ name: 'user-records' })
}

function goToAssessments() {
    router.push({ path: '/user/assessments' })
}

function handleKeydown(event) {
    if (event.key !== 'Escape' || showResult.value) return
    if (currentComponent.value) releaseHeld()
}

// Show the checklist again when a new stage starts, collapse it once it is done.
watch(phase, () => {
    showPrep.value = true
})

watch(prepDone, (done) => {
    if (done) {
        showPrep.value = false
        addLog('Safety check complete. The bench is open.', 'ok')
    }
})

watch(showResult, async (open) => {
    if (!open) return

    await nextTick()
    resultTitle.value?.focus()
})

/* =========================================================
   LIFECYCLE
========================================================= */

onMounted(() => {
    load()
    window.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
    window.clearInterval(timer)
    window.clearTimeout(feedbackTimer)
    window.clearTimeout(flashTimer)
    window.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
/* REVERSE FROSTED NOIR (light)
   Surfaces: translucent white with blur. Ink: #07080a. Sharp corners.
   Pass #11813D, fail #C4161F, warning text #5C3D00 on #FFF8E6. */

.lab {
  --bg: #eef0f3;
  --bg-soft: #e3e6eb;
  --surface: rgba(255, 255, 255, 0.55);
  --surface-strong: rgba(255, 255, 255, 0.8);
  --ink: #07080a;
  --muted: #4b5563;
  --line: rgba(7, 8, 10, 0.12);
  --line-strong: rgba(7, 8, 10, 0.35);
  --grid: rgba(7, 8, 10, 0.07);
  --pass: #11813d;
  --fail: #c4161f;
  --warn-ink: #5c3d00;
  --warn-bg: rgba(255, 248, 230, 0.92);
  --warn-line: #c99a2e;
  --shadow: 0 18px 40px rgba(15, 23, 42, 0.14), inset 0 1px 0 rgba(255, 255, 255, 0.8);
  --shadow-sm: 0 8px 20px rgba(15, 23, 42, 0.1);
  --blur: blur(18px) saturate(120%);

  position: relative;
  isolation: isolate;
  min-height: 100dvh;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  background:
    radial-gradient(circle at 18% 0%, rgba(7, 8, 10, 0.05), transparent 45%),
    radial-gradient(circle at 90% 100%, rgba(7, 8, 10, 0.04), transparent 40%),
    var(--bg);
  color: var(--ink);
  font-family: "Inter", system-ui, -apple-system, "Segoe UI", sans-serif;
  font-size: 13px;
  line-height: 1.45;
}

/* Background grid, same treatment as the portfolio */
.lab::before {
  content: "";
  position: fixed;
  inset: 0;
  z-index: -1;
  pointer-events: none;
  background-image:
    linear-gradient(to right, var(--grid) 1px, transparent 1px),
    linear-gradient(to bottom, var(--grid) 1px, transparent 1px);
  background-size: 48px 48px;
  -webkit-mask-image: radial-gradient(ellipse at center, #000 30%, transparent 80%);
  mask-image: radial-gradient(ellipse at center, #000 30%, transparent 80%);
}

.lab *,
.lab *::before,
.lab *::after {
  box-sizing: border-box;
  border-radius: 0;
}

.lab h1,
.lab h2,
.lab p,
.lab dl,
.lab dd,
.lab ol,
.lab ul {
  margin: 0;
}

.lab ul,
.lab ol {
  padding: 0;
  list-style: none;
}

.lab button {
  cursor: pointer;
  font: inherit;
}

.lab button:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.lab :focus-visible {
  outline: 2px solid var(--ink);
  outline-offset: 2px;
}

.lab .case :focus-visible {
  outline-color: #ffffff;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
  border: 0;
}

.kicker {
  display: block;
  color: var(--muted);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.muted {
  color: var(--muted);
}

.small {
  font-size: 11.5px;
}

/* Frosted surface shared by cards and panels */
.card,
.topbar,
.rail,
.hand,
.tray,
.state,
.toast,
.dialog {
  background: var(--surface);
  backdrop-filter: var(--blur);
  -webkit-backdrop-filter: var(--blur);
  border: 1px solid var(--line);
  box-shadow: var(--shadow);
}

/* TOP BAR */

.topbar {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 10px 14px;
}

.icon-btn {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border: 1px solid var(--line);
  background: var(--surface-strong);
  box-shadow: var(--shadow-sm);
}

.icon-btn:hover:not(:disabled) {
  border-color: var(--ink);
}

.title {
  flex: 1;
  min-width: 0;
}

.title h1 {
  overflow: hidden;
  font-size: 16px;
  font-weight: 700;
  letter-spacing: -0.01em;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.stats {
  display: flex;
  gap: 16px;
}

.stats > div {
  text-align: right;
}

.stats dt {
  color: var(--muted);
  font-size: 10px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.stats dd {
  font-size: 16px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.stats .warn dd {
  color: var(--warn-ink);
}

.stats .bad dd {
  color: var(--fail);
}

/* STAGE RAIL */

.rail {
  flex-shrink: 0;
  padding: 8px;
}

.rail ol {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
}

.rail li {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 10px;
  border: 1px solid var(--line);
  color: var(--muted);
  font-size: 12px;
  font-weight: 500;
  background: rgba(255, 255, 255, 0.4);
}

.rail li.current {
  border-color: var(--ink);
  color: var(--ink);
  font-weight: 700;
  box-shadow: var(--shadow-sm);
}

.rail li.done {
  border-color: #b7dcc5;
  color: var(--pass);
  font-weight: 600;
}

.rail-dot {
  width: 22px;
  height: 22px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  background: var(--bg-soft);
  color: var(--muted);
  font-size: 11px;
  font-weight: 700;
}

.rail li.current .rail-dot {
  background: var(--ink);
  color: #ffffff;
}

.rail li.done .rail-dot {
  background: var(--pass);
  color: #ffffff;
}

/* WARNING */

.warning {
  flex-shrink: 0;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 14px;
  border: 1px solid var(--warn-line);
  background: var(--warn-bg);
  color: var(--warn-ink);
  box-shadow: var(--shadow-sm);
  font-size: 12.5px;
}

.warning i {
  margin-top: 2px;
  font-size: 15px;
}

.warning p {
  line-height: 1.5;
}

.banner {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 12px;
  border: 1px solid;
  font-size: 12px;
  box-shadow: var(--shadow-sm);
}

.banner-warn {
  border-color: var(--warn-line);
  background: var(--warn-bg);
  color: var(--warn-ink);
}

.banner-error {
  border-color: var(--fail);
  background: #fff1f0;
  color: #8a0f16;
}

/* LOADING / ERROR */

.state {
  flex: 1;
  min-height: 320px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 24px;
  text-align: center;
}

.state-title {
  font-size: 15px;
  font-weight: 700;
}

.state-text {
  max-width: 420px;
  color: var(--muted);
}

.state-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
  margin-top: 6px;
}

.state-icon {
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  background: #fff1f0;
  color: var(--fail);
  font-size: 18px;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--line);
  border-top-color: var(--ink);
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
}

/* BUTTONS */

.btn {
  min-height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 16px;
  border: 1px solid var(--ink);
  font-weight: 600;
  text-align: center;
  box-shadow: var(--shadow-sm);
  transition: transform 0.15s, background 0.15s, box-shadow 0.15s;
}

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: var(--shadow);
}

.btn-primary {
  background: var(--ink);
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background: #2a2d33;
}

.btn-ghost {
  border-color: var(--line-strong);
  background: var(--surface-strong);
  color: var(--ink);
}

.btn-sm {
  min-height: 34px;
  padding: 0 12px;
  font-size: 12px;
}

.btn-block {
  width: 100%;
}

/* MAIN STAGE */

.stage {
  flex: 1;
  min-height: 0;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 290px;
  gap: 12px;
  align-items: start;
}

.bench {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.side {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.card {
  padding: 14px;
}

.card-title {
  display: block;
  margin-top: 2px;
  font-size: 14px;
  font-weight: 700;
}

.count {
  min-width: 28px;
  padding: 2px 8px;
  border: 1px solid var(--line);
  font-size: 11px;
  font-weight: 700;
  text-align: center;
  font-variant-numeric: tabular-nums;
  background: var(--surface-strong);
}

.count.ready {
  border-color: var(--pass);
  color: var(--pass);
}

/* HAND */

.hand {
  flex-shrink: 0;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  min-height: 56px;
  padding: 8px 12px;
}

.hand.held {
  border: 1px solid var(--ink);
}

.hand-empty {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  font-size: 12px;
}

.hand-copy {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.hand strong {
  font-size: 14px;
  font-weight: 700;
}

.hand-hint {
  color: var(--muted);
  font-size: 12px;
}

.hand-icon {
  width: 30px;
  height: 30px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
}

.phase-tag {
  margin-left: auto;
  color: var(--muted);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

/* ICONS */

.slot-icon svg,
.part-icon svg,
.hand-icon svg {
  width: 22px;
  height: 22px;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.7;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.part-icon svg {
  width: 18px;
  height: 18px;
}

.hand-icon svg {
  width: 24px;
  height: 24px;
}

/* CHASSIS */

.case {
  min-height: 340px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 12px;
  border: 1px solid var(--ink);
  background: linear-gradient(180deg, #111318, #07080a);
  color: #ffffff;
  box-shadow: var(--shadow);
}

.case-title {
  color: #9ca3af;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.case-grid {
  flex: 1;
  min-height: 0;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  grid-template-rows: 44px minmax(0, 1fr) minmax(0, 1.2fr) minmax(0, 1fr);
  grid-template-areas:
    "cov cov cov"
    "cpu ram fan"
    "mb mb psu"
    "exp drv cab";
  gap: 6px;
}

/* SLOTS */

.slot {
  min-width: 0;
  min-height: 62px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  padding: 6px;
  border: 1px dashed #4b5563;
  background: rgba(255, 255, 255, 0.04);
  color: #9ca3af;
  text-align: center;
  transition: border-color 0.15s, background 0.15s, transform 0.15s;
}

.slot.occupied {
  border: 1px solid #d1d5db;
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
}

.slot.grab {
  cursor: grab;
}

.slot.grab:hover {
  border-color: #ffffff;
}

.slot.cover {
  border-style: solid;
}

.slot.held {
  outline: 2px solid #ffffff;
  outline-offset: 2px;
}

.slot.accepts {
  border: 2px dashed #ffffff;
  background: rgba(255, 255, 255, 0.16);
  color: #ffffff;
}

.slot.over {
  border-style: solid;
  background: #ffffff;
  color: var(--ink);
}

.slot.done {
  border-color: #7fd39c;
}

.slot.flash {
  animation: shake 0.45s ease;
  border-color: #ff6b6b;
}

.slot[aria-disabled="true"] {
  cursor: default;
}

.slot-name {
  max-width: 100%;
  display: -webkit-box;
  overflow: hidden;
  font-size: 12px;
  font-weight: 600;
  line-height: 1.25;
  overflow-wrap: anywhere;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.slot-state {
  font-size: 9.5px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.slot.over .slot-name,
.slot.over .slot-state {
  color: var(--ink);
}

.slot.accepts .slot-state {
  color: #ffffff;
  font-weight: 700;
}

/* TRAYS */

.trays {
  flex-shrink: 0;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.tray {
  min-width: 0;
  min-height: 150px;
  display: flex;
  flex-direction: column;
  padding: 12px;
  border-style: dashed;
  transition: border-color 0.15s, background 0.15s;
}

.tray.accepts {
  border: 2px dashed var(--ink);
}

.tray.over {
  border: 2px solid var(--ink);
  background: rgba(255, 255, 255, 0.85);
}

.tray.flash {
  animation: shake 0.45s ease;
}

.tray-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.tray-head h2 {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 700;
}

.place-btn {
  min-height: 32px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0 10px;
  border: 1px solid var(--line-strong);
  background: var(--surface-strong);
  color: var(--muted);
  font-size: 12px;
  font-weight: 600;
}

.place-btn.ready {
  border-color: var(--ink);
  color: var(--ink);
  box-shadow: var(--shadow-sm);
}

.place-btn[aria-disabled="true"] {
  cursor: not-allowed;
}

.tray-empty {
  margin: auto 0;
  padding: 8px;
  color: var(--muted);
  font-size: 12px;
  text-align: center;
}

.parts {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

/* PARTS */

.part {
  max-width: 100%;
  min-height: 42px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 6px 10px;
  border: 1px solid var(--line-strong);
  background: rgba(255, 255, 255, 0.9);
  color: var(--ink);
  font-size: 12px;
  font-weight: 600;
  text-align: left;
  box-shadow: var(--shadow-sm);
  transition: transform 0.15s, border-color 0.15s, box-shadow 0.15s;
}

.part.grab {
  cursor: grab;
}

.part.grab:hover {
  transform: translateY(-2px);
  border-color: var(--ink);
  box-shadow: var(--shadow);
}

.part[aria-disabled="true"] {
  cursor: default;
  color: var(--muted);
  background: rgba(255, 255, 255, 0.5);
  box-shadow: none;
}

.part.held {
  border: 2px solid var(--ink);
  padding: 5px 9px;
  transform: translateY(-2px);
}

.part-name {
  min-width: 0;
  display: -webkit-box;
  overflow: hidden;
  overflow-wrap: anywhere;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.bench-help {
  color: var(--muted);
  font-size: 11.5px;
  line-height: 1.5;
}

/* SAFETY CHECK */

.accordion {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 0;
  border: 0;
  background: none;
  color: var(--ink);
  text-align: left;
}

.tasks {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: 10px;
}

.task {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 7px 9px;
  border: 1px solid var(--line);
  background: rgba(255, 255, 255, 0.5);
  font-size: 12px;
  line-height: 1.35;
  cursor: pointer;
}

.task:hover {
  border-color: var(--line-strong);
}

.task.done {
  border-color: var(--ink);
  background: rgba(255, 255, 255, 0.9);
}

.task.muted {
  color: var(--muted);
  cursor: not-allowed;
}

.sr-check {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: 0;
  opacity: 0;
}

.sr-check:focus-visible + .box {
  outline: 2px solid var(--ink);
  outline-offset: 2px;
}

.box {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  margin-top: 1px;
  border: 1px solid var(--muted);
  color: #ffffff;
  font-size: 9px;
}

.task.done .box {
  background: var(--ink);
  border-color: var(--ink);
}

/* CURRENT STEP */

.guide {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.guide-title {
  font-size: 15px;
  font-weight: 700;
}

.progress {
  height: 6px;
  margin: 6px 0 2px;
  overflow: hidden;
  background: rgba(7, 8, 10, 0.1);
}

.progress span {
  display: block;
  height: 100%;
  background: var(--ink);
  transition: width 0.3s ease;
}

/* ACTIVITY LOG */

.log ol {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: 8px;
}

.log li {
  display: grid;
  grid-template-columns: 16px minmax(0, 1fr) auto;
  align-items: start;
  gap: 8px;
  padding-bottom: 6px;
  border-bottom: 1px solid var(--line);
  font-size: 12px;
  line-height: 1.35;
}

.log li:last-child {
  border-bottom: 0;
}

.log li.ok i {
  color: var(--pass);
}

.log li.bad i {
  color: var(--fail);
}

.log li.info i {
  color: var(--muted);
}

.log time {
  color: var(--muted);
  font-size: 10.5px;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}

/* TOAST */

.toast {
  position: fixed;
  left: 50%;
  bottom: 18px;
  transform: translateX(-50%);
  z-index: 1100;
  display: flex;
  align-items: center;
  gap: 8px;
  max-width: min(420px, calc(100vw - 32px));
  padding: 9px 14px;
  font-size: 12px;
  font-weight: 600;
  animation: toast-in 0.2s ease-out;
}

.toast.ok {
  color: var(--pass);
}

.toast.bad {
  color: var(--fail);
}

/* RESULT */

.overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: grid;
  place-items: center;
  padding: 16px;
  background: rgba(7, 8, 10, 0.45);
}

.dialog {
  width: min(380px, 100%);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 24px;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25);
  text-align: center;
}

.dialog h2 {
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.dialog h2:focus {
  outline: none;
}

.result-score {
  font-size: 46px;
  font-weight: 700;
  line-height: 1;
  letter-spacing: -0.03em;
  color: var(--pass);
}

.result-score.bad {
  color: var(--fail);
}

.stat-row {
  width: 100%;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.stat-row > div {
  padding: 8px;
  border: 1px solid var(--line);
  background: var(--surface-strong);
}

.stat-row dt {
  color: var(--muted);
  font-size: 10px;
  text-transform: uppercase;
}

.stat-row dd {
  font-size: 16px;
  font-weight: 700;
}

.result-message {
  color: var(--muted);
  font-size: 12px;
  line-height: 1.5;
}

/* RESPONSIVE */

/* Tablet: the side panel moves under the bench. The current-step card sticks to the bottom. */
@media (max-width: 900px) {
  .stage {
    grid-template-columns: minmax(0, 1fr);
  }

  .guide {
    position: sticky;
    bottom: 8px;
    z-index: 5;
    box-shadow: 0 -6px 20px rgba(0, 0, 0, 0.08), var(--shadow);
  }

  .case {
    min-height: 360px;
  }
}

/* Phone: single-column trays, wrapping top bar, larger tap targets. */
@media (max-width: 600px) {
  .lab {
    padding: 10px;
    gap: 10px;
  }

  .topbar {
    flex-wrap: wrap;
    gap: 10px;
    padding: 8px 10px;
  }

  .title {
    flex-basis: calc(100% - 52px);
  }

  .title h1 {
    font-size: 14px;
  }

  .stats {
    width: 100%;
    justify-content: space-between;
    gap: 8px;
  }

  .stats > div {
    text-align: left;
  }

  .stats dd {
    font-size: 15px;
  }

  .rail li {
    padding: 6px;
    font-size: 11px;
    gap: 6px;
  }

  .rail li > span:last-child {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .warning {
    font-size: 12px;
    padding: 9px 10px;
  }

  .case {
    min-height: 0;
    padding: 10px;
  }

  .case-grid {
    grid-template-rows: 36px minmax(0, 1fr) minmax(0, 1.2fr) minmax(0, 1fr);
    gap: 5px;
  }

  .slot {
    min-height: 66px;
    padding: 5px 4px;
  }

  .slot-name {
    font-size: 11px;
  }

  .slot-state {
    font-size: 9px;
  }

  .trays {
    grid-template-columns: minmax(0, 1fr);
  }

  .tray {
    min-height: 0;
  }

  .part {
    min-height: 46px;
  }

  .place-btn {
    min-height: 40px;
  }

  .hand {
    padding: 8px 10px;
  }

  .phase-tag {
    width: 100%;
    margin-left: 0;
  }

  .toast {
    bottom: 96px;
  }
}

/* Very small phones */
@media (max-width: 360px) {
  .rail li > span:last-child {
    display: none;
  }

  .case-grid {
    gap: 4px;
  }

  .slot-name {
    font-size: 10px;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes toast-in {
  from {
    opacity: 0;
    transform: translate(-50%, 6px);
  }

  to {
    opacity: 1;
    transform: translate(-50%, 0);
  }
}

@keyframes shake {
  0%, 100% {
    transform: translateX(0);
  }

  20% {
    transform: translateX(-5px);
  }

  40% {
    transform: translateX(5px);
  }

  60% {
    transform: translateX(-3px);
  }

  80% {
    transform: translateX(3px);
  }
}

@media (prefers-reduced-motion: reduce) {
  .lab *,
  .lab *::before,
  .lab *::after {
    animation: none !important;
    transition: none !important;
  }
}
</style>