<template>
  <div class="practical">
    <!-- HEADER -->
    <header class="card head">
      <button class="round-btn" type="button" aria-label="Go back" :disabled="busy" @click="goBack">
        <i class="fas fa-arrow-left" aria-hidden="true"></i>
      </button>

      <div class="head-text">
        <span class="kicker">CSS Prep · COC {{ quiz.coc_number ?? '—' }}</span>
        <h1>{{ assessment.title || 'Assembly and Disassembly' }}</h1>
      </div>

      <dl v-if="status === 'ready' || status === 'ended'" class="stats">
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

    <!-- STAGES -->
    <ol v-if="status === 'ready'" class="stages" aria-label="Practical stages">
      <li v-for="stage in stages" :key="stage.key" :class="stage.state"
        :aria-current="stage.state === 'current' ? 'step' : undefined">
        <span class="stage-dot">
          <i v-if="stage.state === 'done'" class="fas fa-check" aria-hidden="true"></i>
          <template v-else>{{ stage.number }}</template>
        </span>
        <span>{{ stage.label }}</span>
      </li>
    </ol>

    <!-- SAFETY NOTE -->
    <div class="notice notice-warn" role="note">
      <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
      <p><strong>Safety first.</strong> Power off, unplug the cord, and wear an anti-static strap before you touch any
        part.
      </p>
    </div>

    <p class="sr-only" aria-live="polite" aria-atomic="true">{{ announcement }}</p>

    <!-- CONNECTION -->
    <div v-if="!isOnline" class="notice notice-warn" role="status">
      <i class="fas fa-wifi" aria-hidden="true"></i>
      <p>You're offline. Moves are paused until the connection returns.</p>
    </div>
    <p v-if="status === 'loading' && slowNetwork" class="slow" role="status">
      <i class="fas fa-hourglass-half" aria-hidden="true"></i>
      Still loading. Your connection may be slow.
    </p>

    <!-- LOADING -->
    <section v-if="status === 'loading'" class="card state" role="status" aria-live="polite">
      <span class="spinner" aria-hidden="true"></span>
      <h2>Preparing the practical</h2>
      <p>Loading the chassis and starting your attempt.</p>
    </section>

    <!-- ERROR -->
    <section v-else-if="status === 'error'" class="card state" role="alert">
      <span class="state-icon" aria-hidden="true"><i :class="currentError.icon"></i></span>
      <h2>{{ currentError.title }}</h2>
      <p>{{ currentError.text }}</p>
      <div class="actions">
        <button v-if="currentError.retry" class="btn-primary" type="button" :disabled="!isOnline" @click="load">
          Try again
        </button>
        <button v-if="currentError.signIn" class="btn-primary" type="button" @click="goToLogin">Sign in again</button>
        <button class="btn-ghost" type="button" @click="goToAssessments">Back to assessments</button>
      </div>
    </section>

    <!-- EMPTY -->
    <section v-else-if="status === 'empty'" class="card state">
      <span class="state-icon" aria-hidden="true"><i class="fas fa-box-open"></i></span>
      <h2>No parts set up yet</h2>
      <p>This practical has no parts. Ask your instructor to add them, then try again.</p>
      <div class="actions">
        <button class="btn-ghost" type="button" @click="goToAssessments">Back to assessments</button>
      </div>
    </section>

    <!-- TIME UP -->
    <section v-else-if="status === 'ended'" class="card state" role="status">
      <span class="state-icon warn" aria-hidden="true"><i class="fas fa-clock"></i></span>
      <h2>{{ endedTitle }}</h2>
      <p>{{ endedReason }}</p>
      <div class="actions">
        <button class="btn-primary" type="button" @click="goToRecords">View records</button>
        <button class="btn-ghost" type="button" @click="goToAssessments">Back to assessments</button>
      </div>
    </section>

    <!-- PRACTICAL -->
    <template v-else>
      <div v-if="submitError" class="notice notice-error" role="alert">
        <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
        <p>{{ submitError }}</p>
      </div>

      <div class="layout">
        <!-- CHECKLIST -->
        <aside class="card side" aria-label="Checklist and progress">
          <span class="kicker">{{ phaseLabel }}</span>
          <h2>{{ phaseTitle }}</h2>
          <p class="muted">{{ phaseText }}</p>

          <div class="bar" role="progressbar" aria-label="Steps completed" :aria-valuemin="0"
            :aria-valuemax="totalActions" :aria-valuenow="completedActions">
            <span :style="{ width: progressPercent + '%' }"></span>
          </div>

          <div class="group">
            <p class="group-title">Before you start</p>
            <ul class="checks">
              <li v-for="step in prepSteps" :key="step.component_key">
                <button type="button" class="check" :class="{ done: isDone(step.component_key) }"
                  :disabled="checkDisabled(step.component_key, 'prepare')" @click="tapChecklist(step.component_key)">
                  <span class="box">
                    <i v-if="isDone(step.component_key)" class="fas fa-check" aria-hidden="true"></i>
                  </span>
                  <span>{{ step.display_name }}</span>
                </button>
              </li>
            </ul>
          </div>

          <div class="group">
            <p class="group-title">After assembly</p>
            <ul class="checks">
              <li v-for="step in finishSteps" :key="step.component_key">
                <button type="button" class="check" :class="{ done: isDone(step.component_key) }"
                  :disabled="checkDisabled(step.component_key, 'finish')" @click="tapChecklist(step.component_key)">
                  <span class="box">
                    <i v-if="isDone(step.component_key)" class="fas fa-check" aria-hidden="true"></i>
                  </span>
                  <span>{{ step.display_name }}</span>
                </button>
              </li>
            </ul>
          </div>

          <p v-if="message.text" class="message" :class="message.tone" role="status">{{ message.text }}</p>

          <button v-if="procedureComplete" class="btn-primary btn-block" type="button" :disabled="!canSubmit"
            @click="completeAssessment">
            <i :class="completing ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'" aria-hidden="true"></i>
            {{ completing ? 'Submitting…' : 'Submit assessment' }}
          </button>
        </aside>

        <!-- WORKSPACE -->
        <section class="card bench" aria-label="Workspace">
          <div v-if="workspaceLocked" class="locked">
            <i class="fas fa-lock" aria-hidden="true"></i>
            <h2>Workspace locked</h2>
            <p>Finish the steps under "Before you start" to open the chassis.</p>
          </div>

          <template v-else>
            <!-- HAND -->
            <div class="hand" :class="{ held: !!current }">
              <template v-if="current">
                <span class="hand-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" focusable="false">
                    <component v-for="(shape, i) in iconFor(current)" :key="i" :is="shape.tag" v-bind="shape.attrs" />
                  </svg>
                </span>
                <div class="hand-copy">
                  <span class="kicker">Holding</span>
                  <strong>{{ labelFor(current) }}</strong>
                </div>
                <span class="muted small">{{ holdingHint }}</span>
                <button class="btn-ghost btn-sm" type="button" @click="releaseHeld">Put down</button>
              </template>
              <span v-else class="muted small">Select a part to pick it up.</span>
            </div>

            <!-- CHASSIS -->
            <div class="chassis" role="group" aria-label="Computer chassis">
              <p class="chassis-title">Chassis</p>
              <div class="slots">
                <button v-for="slot in SLOTS" :key="slot.key" type="button" class="slot" :class="slotClasses(slot.key)"
                  :style="{ gridArea: slot.area }" :aria-label="slotLabel(slot.key)"
                  :draggable="String(isPartDraggable(slot.key))" @click="tapSlot(slot.key)"
                  @dragstart="onDragStart(slot.key, $event)" @dragend="onDragEnd"
                  @dragover="onDragOver($event, slotId(slot.key))" @dragleave="onDragLeave($event, slotId(slot.key))"
                  @drop.prevent="onDrop($event, slotId(slot.key))">
                  <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <component v-for="(shape, i) in iconFor(slot.key)" :key="i" :is="shape.tag" v-bind="shape.attrs" />
                  </svg>
                  <span class="slot-name">{{ labelFor(slot.key) }}</span>
                  <span class="slot-state">{{ slotStateText(slot.key) }}</span>
                </button>
              </div>
            </div>

            <!-- TRAYS -->
            <div class="trays">
              <section v-for="tray in TRAYS" :key="tray.key" class="tray" :class="trayClasses(tray.key)"
                :aria-label="tray.title" @dragover="onDragOver($event, zoneId(tray.key))"
                @dragleave="onDragLeave($event, zoneId(tray.key))" @drop.prevent="onDrop($event, zoneId(tray.key))">
                <header class="tray-head">
                  <h3>{{ tray.title }} <span class="count">{{ trayParts(tray.key).length }}</span></h3>
                  <button type="button" class="place-btn" :disabled="!canPlaceIn(tray.key)" @click="tapZone(tray.key)">
                    Place here
                  </button>
                </header>

                <p v-if="!trayParts(tray.key).length" class="muted small">Empty</p>

                <div v-else class="parts">
                  <button v-for="part in trayParts(tray.key)" :key="part.component_key" type="button" class="part"
                    :class="{ held: current === part.component_key, grab: isPartDraggable(part.component_key) }"
                    :aria-pressed="String(current === part.component_key)"
                    :disabled="!isPartDraggable(part.component_key)"
                    :draggable="String(isPartDraggable(part.component_key))" @click="pickPart(part.component_key)"
                    @dragstart="onDragStart(part.component_key, $event)" @dragend="onDragEnd">
                    <svg class="icon small" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                      <component v-for="(shape, i) in iconFor(part.component_key)" :key="i" :is="shape.tag"
                        v-bind="shape.attrs" />
                    </svg>
                    <span>{{ labelFor(part.component_key) }}</span>
                  </button>
                </div>
              </section>
            </div>

            <p class="muted small help">Tap a part, then tap where it goes. On desktop you can also drag. Press Esc to
              put a part
              down.</p>
          </template>
        </section>
      </div>
    </template>

    <!-- RESULT -->
    <div v-if="showResult" class="overlay">
      <section class="dialog" role="dialog" aria-modal="true" aria-labelledby="result-title">
        <span class="kicker">Practical result</span>
        <h2 id="result-title" ref="resultTitle" tabindex="-1">
          {{ result.passed ? 'Assessment passed' : 'Assessment completed' }}
        </h2>
        <p class="score" :class="{ bad: !result.passed }">{{ result.percentage }}%</p>

        <dl class="result-stats">
          <div>
            <dt>Steps</dt>
            <dd>{{ result.completed }}/{{ totalActions }}</dd>
          </div>
          <div>
            <dt>Mistakes</dt>
            <dd>{{ result.mistakes }}</dd>
          </div>
        </dl>

        <p class="muted small">{{ resultNote }}</p>

        <button class="btn-primary btn-block" type="button" @click="goToRecords">View records</button>
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
const SLOW_AFTER_MS = 4000
const PASSING_FALLBACK = 75

const STAGES = [
  { key: 'prepare', label: 'Prepare' },
  { key: 'disassemble', label: 'Disassemble' },
  { key: 'assemble', label: 'Assemble' },
  { key: 'finish', label: 'Finish' },
]

const PHASE_TEXT = {
  prepare: ['Get the system ready', 'Complete the steps before you start. The workspace opens after this.'],
  disassemble: ['Take the chassis apart', 'Pick up an installed part and place it in the right tray.'],
  assemble: ['Put the chassis back together', 'Pick up a part from the trays and place it in an empty slot.'],
  finish: ['Finish and test', 'Complete the steps after assembly, then submit.'],
}

const SLOTS = [
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

const TRAYS = [
  { key: 'parts_tray', title: 'Parts table' },
  { key: 'cable_tray', title: 'Cable tray' },
]

const rect = (x, y, width, height, rx = 0) => ({ tag: 'rect', attrs: { x, y, width, height, rx } })
const circle = (cx, cy, r) => ({ tag: 'circle', attrs: { cx, cy, r } })
const path = (d) => ({ tag: 'path', attrs: { d } })

const ICONS = {
  side_panel: [rect(3, 3, 18, 18, 4), circle(7, 7, 0.8), circle(17, 7, 0.8), circle(7, 17, 0.8), circle(17, 17, 0.8)],
  motherboard: [rect(3, 3, 18, 18, 3), rect(6, 6, 5, 5, 1), path('M14 5v9M17 5v9M20 5v9')],
  cpu_heatsink: [path('M4 4h16M4 7.5h16M4 11h16M4 14.5h16'), rect(4, 17, 16, 3, 1.5)],
  ram: [rect(2, 7, 20, 10, 2), path('M6 7v3M10 7v3M14 7v3M18 7v3')],
  expansion_card: [path('M4 4h10l4 4v12H4z'), path('M8 16h8M8 8h3v3H8z')],
  storage_drive: [rect(3, 6, 18, 12, 3), path('M7 10h5M7 14h8'), circle(17, 12, 1)],
  psu: [rect(3, 4, 18, 16, 3), circle(14, 12, 4), path('M11.5 12h5M14 9.5v5')],
  system_fan: [circle(12, 12, 9), circle(12, 12, 2), path('M12 10c0-3 1-5 3-5M14 12c3 0 5 1 5 3M10 14c0 3-1 5-3 5')],
  internal_cables: [rect(2, 6, 4, 12, 2), rect(18, 6, 4, 12, 2), path('M6 9h12M6 12h12M6 15h12')],
}

const FALLBACK_ICON = [rect(3, 3, 18, 18, 4)]

const ERRORS = {
  offline: { icon: 'fas fa-wifi', title: "You're offline", text: 'Check your internet connection, then try again.', retry: true },
  network: { icon: 'fas fa-plug-circle-xmark', title: "Can't reach the server", text: 'Check your network and try again.', retry: true },
  timeout: { icon: 'fas fa-hourglass-half', title: 'The request took too long', text: 'Your network may be slow. Try again in a moment.', retry: true },
  server: { icon: 'fas fa-triangle-exclamation', title: 'The practical could not start', text: 'The server could not load this practical. Please try again shortly.', retry: true },
  notFound: { icon: 'fas fa-circle-question', title: 'Practical not found', text: 'This practical may have been removed. Go back and choose another assessment.', retry: false },
  permission: { icon: 'fas fa-lock', title: 'Access denied', text: 'Your account cannot take this practical. Contact your instructor if this is a mistake.', retry: false },
  session: { icon: 'fas fa-user-clock', title: 'Your session has expired', text: 'Sign in again to continue.', retry: false, signIn: true },
}

/* =========================================================
   ROUTE
========================================================= */

const route = useRoute()
const router = useRouter()

const quizId = computed(() => Number(route.params.quizId || route.params.id || route.query.quiz))
console.log(quizId)

/* =========================================================
   STATE
========================================================= */

// status: loading | ready | empty | ended | error
const status = ref('loading')
const errorType = ref('server')
const endedTitle = ref('')
const endedReason = ref('')
const isOnline = ref(navigator.onLine)
const slowNetwork = ref(false)
const announcement = ref('')

const quiz = ref({})
const assessment = ref({})
const parts = ref([])
const prepSteps = ref([])
const finishSteps = ref([])
const checklistDone = ref([])

const attemptId = ref(null)
const installed = ref({})
const phase = ref('prepare')
const procedureComplete = ref(false)
const completedActions = ref(0)
const totalActions = ref(0)
const mistakeCount = ref(0)

const remainingSeconds = ref(TOTAL_SECONDS)
const deadlineMs = ref(null)

const current = ref(null)
const activeTarget = ref(null)
const flashTarget = ref(null)
const draggedKey = ref(null)

const pending = ref(false)
const completing = ref(false)
const submitted = ref(false)
const submitError = ref('')
const message = ref({ tone: 'info', text: '' })

const showResult = ref(false)
const result = ref({ passed: false, percentage: 0, completed: 0, mistakes: 0 })
const resultTitle = ref(null)

let requestId = 0
let timer = null
let slowTimer = null
let flashTimer = null

/* =========================================================
   COMPUTED
========================================================= */

const currentError = computed(() => ERRORS[errorType.value] ?? ERRORS.server)
const busy = computed(() => pending.value || completing.value)

const canAct = computed(() =>
  status.value === 'ready' &&
  isOnline.value &&
  !busy.value &&
  !procedureComplete.value &&
  !submitted.value
)

const canSubmit = computed(() => procedureComplete.value && !busy.value && !submitted.value && isOnline.value)

const workspaceLocked = computed(() => phase.value === 'prepare')

const stages = computed(() => {
  const index = STAGES.findIndex((stage) => stage.key === phase.value)

  return STAGES.map((stage, i) => ({
    ...stage,
    number: i + 1,
    state: procedureComplete.value || i < index ? 'done' : i === index ? 'current' : '',
  }))
})

const phaseLabel = computed(() => STAGES.find((s) => s.key === phase.value)?.label ?? 'Complete')
const phaseTitle = computed(() => PHASE_TEXT[phase.value]?.[0] ?? 'All steps are done')
const phaseText = computed(() => PHASE_TEXT[phase.value]?.[1] ?? 'Submit to save this attempt to your records.')

const holdingHint = computed(() =>
  phase.value === 'assemble' ? 'Choose an empty slot in the chassis.' : 'Choose a tray to place it in.'
)

const progressPercent = computed(() =>
  totalActions.value ? Math.round((completedActions.value / totalActions.value) * 100) : 0
)

const timeWarning = computed(() => status.value === 'ready' && remainingSeconds.value <= WARNING_SECONDS)

const formattedTime = computed(() => {
  const seconds = Math.max(0, remainingSeconds.value)
  const minutes = Math.floor(seconds / 60)
  const rest = seconds % 60
  return `${String(minutes).padStart(2, '0')}:${String(rest).padStart(2, '0')}`
})

const resultNote = computed(() =>
  result.value.passed
    ? 'Good work. Review your mistakes in Records to improve.'
    : 'Not passed yet. Review the order of steps and try again.'
)

/* =========================================================
   HELPERS
========================================================= */

function labelFor(key) {
  return [...parts.value, ...prepSteps.value, ...finishSteps.value]
    .find((item) => item.component_key === key)?.display_name ?? key
}

function iconFor(key) {
  return ICONS[key] ?? FALLBACK_ICON
}

function isDone(key) {
  return checklistDone.value.includes(key)
}

function checkDisabled(key, group) {
  return !canAct.value || isDone(key) || phase.value !== group
}

function trayParts(trayKey) {
  return parts.value.filter((part) => {
    if (installed.value[part.component_key]) return false
    const isCable = part.component_key === 'internal_cables'
    return trayKey === 'cable_tray' ? isCable : !isCable
  })
}

function sourceZoneFor(key) {
  if (installed.value[key]) return 'case'
  return key === 'internal_cables' ? 'cable_tray' : 'parts_tray'
}

const slotId = (key) => `slot:${key}`
const zoneId = (key) => `zone:${key}`

function parseTarget(id) {
  const [kind, key] = id.split(':')
  return { kind, key }
}

function setMessage(tone, text) {
  message.value = { tone, text }
}

function announce(text) {
  announcement.value = text
}

function flashAt(id) {
  flashTarget.value = id
  clearTimeout(flashTimer)
  flashTimer = setTimeout(() => {
    flashTarget.value = null
  }, 500)
}

function errorText(error, fallback) {
  const code = error?.response?.status
  if (code === 401 || code === 419) return ERRORS.session.text
  return error?.response?.data?.message || fallback
}

function classifyError(error) {
  const code = error?.response?.status

  if (error?.isInvalidResponse) return 'server'
  if (code === 401 || code === 419) return 'session'
  if (code === 403) return 'permission'
  if (code === 404) return 'notFound'
  if (code >= 500 || error?.response) return 'server'
  if (error?.code === 'ECONNABORTED' || error?.code === 'ETIMEDOUT') return 'timeout'
  if (!navigator.onLine) return 'offline'

  return 'network'
}

function setError(type) {
  errorType.value = type
  status.value = 'error'
  stopTimer()
}

function startSlowTimer() {
  slowNetwork.value = false
  clearTimeout(slowTimer)
  slowTimer = setTimeout(() => {
    slowNetwork.value = true
  }, SLOW_AFTER_MS)
}

function stopSlowTimer() {
  clearTimeout(slowTimer)
  slowNetwork.value = false
}

/* =========================================================
   PARTS: PICK, HOLD, PLACE
========================================================= */

// Disassembly: installed parts can be picked up. Assembly: loose parts can be picked up.
function isPartDraggable(key) {
  if (!canAct.value) return false
  if (phase.value === 'disassemble') return Boolean(installed.value[key])
  if (phase.value === 'assemble') return !installed.value[key]
  return false
}

// Disassembly: a held installed part goes to a tray. Assembly: a held loose part goes to an empty slot.
function targetAccepts(id) {
  if (!canAct.value || !current.value) return false

  const { kind, key } = parseTarget(id)

  if (phase.value === 'disassemble') {
    return kind === 'zone' && Boolean(installed.value[current.value])
  }

  if (phase.value === 'assemble') {
    return kind === 'slot' && !installed.value[current.value] && !installed.value[key]
  }

  return false
}

function canPlaceIn(trayKey) {
  return targetAccepts(zoneId(trayKey))
}

function pickPart(key) {
  if (!canAct.value) return

  if (!isPartDraggable(key)) {
    setMessage('info', `${labelFor(key)} is not available right now.`)
    return
  }

  current.value = current.value === key ? null : key

  if (current.value) announce(`Holding ${labelFor(key)}. ${holdingHint.value}`)
}

function releaseHeld() {
  if (!current.value) return
  announce(`${labelFor(current.value)} put down.`)
  current.value = null
}

function tapSlot(key) {
  if (phase.value === 'disassemble' && installed.value[key]) {
    pickPart(key)
    return
  }

  tapTarget(slotId(key))
}

function tapZone(trayKey) {
  tapTarget(zoneId(trayKey))
}

function tapTarget(id) {
  if (!canAct.value) return

  if (!current.value) {
    setMessage('info', 'Pick a part first, then choose where it goes.')
    return
  }

  if (!targetAccepts(id)) {
    setMessage('info', 'That place does not fit this part right now.')
    return
  }

  placeOn(id)
}

function slotClasses(key) {
  return {
    occupied: Boolean(installed.value[key]),
    accepts: targetAccepts(slotId(key)),
    over: activeTarget.value === slotId(key),
    flash: flashTarget.value === slotId(key),
    held: current.value === key,
  }
}

function trayClasses(key) {
  return {
    accepts: targetAccepts(zoneId(key)),
    over: activeTarget.value === zoneId(key),
    flash: flashTarget.value === zoneId(key),
  }
}

function slotStateText(key) {
  if (installed.value[key]) return isPartDraggable(key) ? 'Select to remove' : 'Installed'
  if (targetAccepts(slotId(key))) return 'Place here'
  return 'Empty'
}

function slotLabel(key) {
  if (installed.value[key]) return `${labelFor(key)}, installed.`
  if (targetAccepts(slotId(key))) return `${labelFor(key)} slot, empty. Place the held part here.`
  return `${labelFor(key)} slot, empty.`
}

function placeOn(id) {
  const { key } = parseTarget(id)
  const part = current.value
  if (!part) return

  // A part in the wrong slot is sent as "wrong_slot", which the server records as a mistake.
  const destination = phase.value === 'disassemble'
    ? key
    : part === key
      ? 'case'
      : 'wrong_slot'

  performAction(part, destination, id)
}

/* =========================================================
   DRAG AND DROP (desktop)
========================================================= */

function onDragStart(key, event) {
  if (!isPartDraggable(key)) {
    event?.preventDefault()
    return
  }

  draggedKey.value = key
  current.value = key

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

  const key = draggedKey.value || event?.dataTransfer?.getData('text/plain')
  draggedKey.value = null

  if (!key || !isPartDraggable(key)) return

  current.value = key

  if (targetAccepts(id)) placeOn(id)
}

/* =========================================================
   CHECKLISTS (preparation and finish)
========================================================= */

function tapChecklist(key) {
  if (!canAct.value || isDone(key)) return
  performAction(key, 'none', null)
}

/* =========================================================
   PROGRESS
========================================================= */

function applyProgress(data) {
  completedActions.value = Number(data.completed_actions ?? completedActions.value)
  totalActions.value = Number(data.total_actions ?? totalActions.value)
  mistakeCount.value = Number(data.mistake_count ?? mistakeCount.value)
  phase.value = data.phase || phase.value
  procedureComplete.value = Boolean(data.procedure_complete)

  if (Array.isArray(data.checklist_done)) {
    checklistDone.value = data.checklist_done
  }

  if (Array.isArray(data.installed_components)) {
    installed.value = Object.fromEntries(data.installed_components.map((key) => [key, true]))
  }
}

/* =========================================================
   ACTIONS
========================================================= */

async function performAction(componentKey, destination, targetId) {
  if (!attemptId.value || !canAct.value) return

  const isChecklist = phase.value === 'prepare' || phase.value === 'finish'
  const isAssembly = phase.value === 'assemble'

  pending.value = true

  try {
    const { data } = await hardwareProcedureRequest.action(quizId.value, attemptId.value, {
      component_key: componentKey,
      performed_action: isChecklist ? 'perform' : isAssembly ? 'install' : 'remove',
      source_zone: isChecklist ? 'none' : sourceZoneFor(componentKey),
      destination_zone: destination,
    })

    if (!data?.success) throw new Error(data?.message || 'The server did not record this move.')

    const next = data.data ?? {}

    applyProgress(next)
    current.value = null

    if (next.allowed) {
      const text = isChecklist
        ? `${labelFor(componentKey)} done.`
        : `${labelFor(componentKey)} ${isAssembly ? 'installed' : 'removed'} correctly.`
      setMessage('ok', text)
      announce(text)
    } else if (next.procedure_complete) {
      setMessage('info', 'All steps are done. Submit the assessment.')
    } else {
      if (targetId) flashAt(targetId)
      setMessage('bad', 'Not the right move. It was recorded as a mistake.')
      announce('Not the right move. It was recorded as a mistake.')
      navigator.vibrate?.(60)
    }
  } catch (error) {
    console.error('Hardware action failed:', error)
    handleActionError(error)
  } finally {
    pending.value = false
  }
}

function handleActionError(error) {
  const code = error?.response?.status

  if (code === 410) {
    endAttempt('Time is up', 'This attempt was not submitted.')
    return
  }

  if (code === 409) {
    current.value = null
    endAttempt('Already submitted', 'This attempt was already submitted. Open Records to view it.')
    return
  }

  // No response: the move may or may not have been saved, so re-check the attempt.
  if (!error?.response) {
    setMessage('bad', 'Connection lost. This move was not confirmed.')
    resyncAfterError()
    return
  }

  setMessage('bad', errorText(error, 'Could not record this move. Try again.'))
}

async function resyncAfterError() {
  try {
    const { data } = await hardwareProcedureRequest.start(quizId.value)
    const attempt = data?.data

    if (!attempt) return

    if (Number(attempt.attempt_id) !== Number(attemptId.value)) {
      endAttempt('Attempt ended', 'This attempt has ended. Start a new attempt from Assessments.')
      return
    }

    applyProgress(attempt)
    current.value = null
  } catch {
    setMessage('bad', 'Still cannot reach the server. Check your connection and try again.')
  }
}

/* =========================================================
   LOAD
========================================================= */

async function load() {
  const id = ++requestId

  if (!navigator.onLine) {
    isOnline.value = false
    setError('offline')
    return
  }

  isOnline.value = true
  status.value = 'loading'
  startSlowTimer()

  try {
    const { data } = await hardwareProcedureRequest.get(quizId.value)

    if (!data?.success || !data?.data) {
      const invalid = new Error('Invalid hardware API response.')
      invalid.isInvalidResponse = true
      throw invalid
    }

    if (id !== requestId) return

    const hardware = data.data

    quiz.value = hardware.quiz ?? {}
    assessment.value = hardware.assessment ?? {}
    parts.value = hardware.components ?? []
    prepSteps.value = hardware.prep_steps ?? []
    finishSteps.value = hardware.finish_steps ?? []

    if (!parts.value.length) {
      status.value = 'empty'
      return
    }

    const { data: started } = await hardwareProcedureRequest.start(quizId.value)

    if (id !== requestId) return

    if (!started?.success || !started?.data) {
      const invalid = new Error('Unable to start the practical attempt.')
      invalid.isInvalidResponse = true
      throw invalid
    }

    const attempt = started.data

    attemptId.value = attempt.attempt_id
    totalActions.value = Number(attempt.total_actions ?? hardware.total_actions ?? 0)
    applyProgress(attempt)

    deadlineMs.value = Date.now() + Number(attempt.remaining_seconds ?? TOTAL_SECONDS) * 1000
    status.value = 'ready'
    setMessage('info', 'Finish the steps under "Before you start" to open the chassis.')
    startTimer()
  } catch (error) {
    if (id !== requestId) return

    console.error('Failed to load hardware practical:', error)
    setError(classifyError(error))
  } finally {
    if (id === requestId) stopSlowTimer()
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

function stopTimer() {
  clearInterval(timer)
  timer = null
}

function startTimer() {
  stopTimer()
  updateRemaining()

  timer = setInterval(() => {
    if (status.value !== 'ready') {
      stopTimer()
      return
    }

    updateRemaining()

    if (remainingSeconds.value <= 0) {
      endAttempt('Time is up', 'This attempt was not submitted.')
    }
  }, 1000)
}

function endAttempt(title, reason) {
  if (status.value === 'ended' || submitted.value) return

  stopTimer()
  current.value = null
  draggedKey.value = null
  activeTarget.value = null

  endedTitle.value = title
  endedReason.value = reason
  remainingSeconds.value = 0
  status.value = 'ended'
}

/* =========================================================
   SUBMIT
========================================================= */

async function completeAssessment() {
  if (!canSubmit.value || !attemptId.value) return

  completing.value = true
  submitError.value = ''

  try {
    const { data } = await hardwareProcedureRequest.complete(quizId.value, attemptId.value)

    if (!data?.success) throw new Error(data?.message || 'Unable to submit this attempt.')

    const next = data.data ?? {}

    result.value = {
      passed: Boolean(next.passed),
      percentage: Number(next.percentage ?? 0),
      completed: Number(next.completed_actions ?? completedActions.value),
      mistakes: Number(next.mistake_count ?? mistakeCount.value),
    }

    submitted.value = true
    stopTimer()
    showResult.value = true
  } catch (error) {
    console.error('Failed to submit practical:', error)

    const code = error?.response?.status

    if (code === 410) return endAttempt('Time is up', 'This attempt was not submitted.')
    if (code === 409) return endAttempt('Already submitted', 'This attempt was already submitted. Open Records to view it.')

    submitError.value = errorText(error, 'Could not submit this attempt. Please try again.')
  } finally {
    completing.value = false
  }
}

/* =========================================================
   NAVIGATION, KEYBOARD, CONNECTION
========================================================= */

function goBack() {
  if (!busy.value) router.back()
}

function goToRecords() {
  router.push({ name: 'user-records' })
}

function goToAssessments() {
  router.push({ path: '/user/assessments' })
}

function goToLogin() {
  router.push('/login')
}

function handleKeydown(event) {
  if (event.key === 'Escape' && current.value) releaseHeld()
}

function handleOnline() {
  isOnline.value = true

  if (status.value === 'error' && ['offline', 'network', 'timeout'].includes(errorType.value)) {
    load()
  }
}

function handleOffline() {
  isOnline.value = false
  current.value = null
}

watch(showResult, async (open) => {
  if (!open) return
  await nextTick()
  resultTitle.value?.focus()
})

/* =========================================================
   LIFECYCLE
========================================================= */

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)
  load()
})

onBeforeUnmount(() => {
  stopTimer()
  stopSlowTimer()
  clearTimeout(flashTimer)
  window.removeEventListener('keydown', handleKeydown)
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
})
</script>

<style scoped>
/* Matches the assessment pages: white cards, soft borders, rounded corners, black actions. */

.practical {
  --ink: #000;
  --mute: #696969;
  --line: #e6e6e6;
  --soft: #f7f7f7;
  --white: #fff;
  --pass: #11813d;
  --fail: #c4161f;
  --warn-ink: #7a5200;
  --warn-bg: #fff8e6;
  --warn-line: #e7c76a;
  --error-bg: #fdf2f1;

  width: 100%;
  max-width: 1120px;
  margin: 0 auto;
  padding: clamp(14px, 3vw, 28px);
  display: flex;
  flex-direction: column;
  gap: 14px;
  color: var(--ink);
  font-family: "Inter", system-ui, -apple-system, "Segoe UI", sans-serif;
  font-size: 13px;
  line-height: 1.5;
  box-sizing: border-box;
}

.practical *,
.practical *::before,
.practical *::after {
  box-sizing: border-box;
}

.practical h1,
.practical h2,
.practical h3,
.practical p,
.practical dl,
.practical dd,
.practical ol,
.practical ul {
  margin: 0;
}

.practical ol,
.practical ul {
  padding: 0;
  list-style: none;
}

.practical button {
  font: inherit;
  cursor: pointer;
}

.practical button:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.practical :focus-visible {
  outline: 2px solid var(--ink);
  outline-offset: 2px;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
  border: 0;
}

.kicker {
  display: block;
  color: var(--mute);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.muted {
  color: var(--mute);
}

.small {
  font-size: 11.5px;
}

.card {
  padding: 16px;
  border: 1px solid var(--line);
  border-radius: 16px;
  background: var(--white);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
}

/* HEADER */

.head {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 14px;
}

.round-btn {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border: 1px solid var(--line);
  border-radius: 50%;
  background: var(--white);
}

.round-btn:hover:not(:disabled) {
  border-color: var(--ink);
}

.head-text {
  flex: 1;
  min-width: 0;
}

.head-text h1 {
  font-size: 18px;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.stats {
  display: flex;
  gap: 8px;
}

.stats>div {
  min-width: 70px;
  padding: 6px 10px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--soft);
  text-align: center;
}

.stats dt {
  color: var(--mute);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}

.stats dd {
  font-size: 15px;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
}

.stats .warn dd {
  color: var(--warn-ink);
}

.stats .bad dd {
  color: var(--fail);
}

/* STAGES */

.stages {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 8px;
}

.stages li {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border: 1px solid var(--line);
  border-radius: 999px;
  background: var(--white);
  color: var(--mute);
  font-size: 12px;
  font-weight: 600;
}

.stages li.current {
  border-color: var(--ink);
  color: var(--ink);
  font-weight: 700;
}

.stages li.done {
  border-color: #b7dcc5;
  color: var(--pass);
}

.stage-dot {
  width: 22px;
  height: 22px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--soft);
  font-size: 11px;
  font-weight: 700;
}

.stages li.current .stage-dot {
  background: var(--ink);
  color: var(--white);
}

.stages li.done .stage-dot {
  background: var(--pass);
  color: var(--white);
}

/* NOTICES */

.notice {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 11px 14px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--white);
  font-size: 12.5px;
  line-height: 1.5;
}

.notice i {
  margin-top: 2px;
}

.notice p {
  margin: 0;
}

.notice-warn {
  border-color: var(--warn-line);
  background: var(--warn-bg);
  color: var(--warn-ink);
}

.notice-error {
  border-color: #efc2be;
  background: var(--error-bg);
  color: var(--fail);
}

.slow {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: var(--mute);
  font-size: 12px;
}

/* STATE CARDS */

.state {
  min-height: 300px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-align: center;
}

.state h2 {
  font-size: 16px;
  font-weight: 800;
}

.state p {
  max-width: 420px;
  color: var(--mute);
}

.state-icon {
  width: 52px;
  height: 52px;
  display: grid;
  place-items: center;
  margin-bottom: 4px;
  border: 1px solid var(--line);
  border-radius: 50%;
  background: var(--soft);
  font-size: 18px;
}

.state-icon.warn {
  background: var(--warn-bg);
  border-color: var(--warn-line);
  color: var(--warn-ink);
}

.actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
  margin-top: 6px;
}

.spinner {
  width: 30px;
  height: 30px;
  border: 3px solid var(--line);
  border-top-color: var(--ink);
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
}

/* BUTTONS */

.btn-primary,
.btn-ghost {
  min-height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 18px;
  border: 1px solid var(--ink);
  border-radius: 10px;
  font-weight: 700;
  transition: background 0.15s, color 0.15s;
}

.btn-primary {
  background: var(--ink);
  color: var(--white);
}

.btn-primary:hover:not(:disabled) {
  background: #222;
}

.btn-ghost {
  background: var(--white);
  color: var(--ink);
}

.btn-ghost:hover:not(:disabled) {
  background: var(--soft);
}

.btn-sm {
  min-height: 34px;
  padding: 0 12px;
  font-size: 12px;
}

.btn-block {
  width: 100%;
}

/* LAYOUT */

.layout {
  display: grid;
  grid-template-columns: 300px minmax(0, 1fr);
  gap: 14px;
  align-items: start;
}

.side {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.side h2 {
  font-size: 16px;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.bar {
  height: 8px;
  overflow: hidden;
  border-radius: 999px;
  background: var(--soft);
}

.bar span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: var(--ink);
  transition: width 0.3s ease;
}

.group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.group-title {
  color: var(--mute);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.checks {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.check {
  width: 100%;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 9px 12px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--white);
  color: var(--ink);
  font-size: 12.5px;
  text-align: left;
}

.check:hover:not(:disabled) {
  border-color: var(--ink);
}

.check.done {
  background: var(--soft);
}

.box {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border: 1px solid var(--mute);
  border-radius: 6px;
  color: var(--white);
  font-size: 9px;
}

.check.done .box {
  background: var(--ink);
  border-color: var(--ink);
}

.message {
  padding: 9px 12px;
  border-radius: 10px;
  background: var(--soft);
  font-size: 12px;
}

.message.ok {
  color: var(--pass);
}

.message.bad {
  color: var(--fail);
}

/* WORKSPACE */

.bench {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
}

.locked {
  min-height: 300px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: var(--mute);
  text-align: center;
}

.locked i {
  font-size: 22px;
}

.locked h2 {
  color: var(--ink);
  font-size: 15px;
  font-weight: 800;
}

.hand {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  min-height: 52px;
  padding: 10px 14px;
  border: 1px dashed var(--line);
  border-radius: 14px;
  background: var(--soft);
}

.hand.held {
  border: 1px solid var(--ink);
  background: var(--white);
}

.hand-copy {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.hand strong {
  font-size: 14px;
  font-weight: 800;
}

.hand-icon {
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
}

.chassis {
  padding: 14px;
  border-radius: 16px;
  background: linear-gradient(180deg, #1c1f26, #0b0c10);
  color: var(--white);
}

.chassis-title {
  margin-bottom: 10px;
  color: #9ca3af;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.slots {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  grid-template-rows: 44px 84px 96px 84px;
  grid-template-areas:
    "cov cov cov"
    "cpu ram fan"
    "mb mb psu"
    "exp drv cab";
  gap: 8px;
}

.slot {
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;
  padding: 6px;
  border: 1px dashed #4b5563;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.04);
  color: #9ca3af;
  text-align: center;
  transition: border-color 0.15s, background 0.15s;
}

.slot.occupied {
  border: 1px solid #d1d5db;
  background: rgba(255, 255, 255, 0.14);
  color: var(--white);
}

.slot.held {
  outline: 2px solid var(--white);
  outline-offset: 2px;
}

.slot.accepts {
  border: 2px dashed var(--white);
  background: rgba(255, 255, 255, 0.18);
  color: var(--white);
}

.slot.over {
  border-style: solid;
  background: var(--white);
  color: var(--ink);
}

.slot.flash {
  animation: shake 0.45s ease;
  border-color: #ff7a7a;
}

.slot[draggable="true"] {
  cursor: grab;
}

.slot-name {
  max-width: 100%;
  overflow: hidden;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.2;
  overflow-wrap: anywhere;
}

.slot-state {
  font-size: 9.5px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.slot.over .slot-name,
.slot.over .slot-state {
  color: var(--ink);
}

.slot.accepts .slot-state {
  color: var(--white);
  font-weight: 700;
}

.icon {
  width: 22px;
  height: 22px;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.7;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.icon.small {
  width: 18px;
  height: 18px;
}

/* TRAYS */

.trays {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.tray {
  min-width: 0;
  min-height: 150px;
  display: flex;
  flex-direction: column;
  padding: 12px;
  border: 1px dashed var(--line);
  border-radius: 14px;
  background: var(--white);
  transition: border-color 0.15s, background 0.15s;
}

.tray.accepts {
  border: 2px dashed var(--ink);
}

.tray.over {
  border: 2px solid var(--ink);
  background: var(--soft);
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

.tray-head h3 {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 800;
}

.count {
  min-width: 24px;
  padding: 1px 8px;
  border-radius: 999px;
  background: var(--soft);
  font-size: 11px;
  text-align: center;
}

.place-btn {
  min-height: 32px;
  padding: 0 12px;
  border: 1px solid var(--line);
  border-radius: 999px;
  background: var(--white);
  font-size: 12px;
  font-weight: 700;
}

.place-btn:not(:disabled) {
  border-color: var(--ink);
}

.parts {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.part {
  max-width: 100%;
  min-height: 40px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 6px 12px;
  border: 1px solid var(--line);
  border-radius: 999px;
  background: var(--white);
  color: var(--ink);
  font-size: 12px;
  font-weight: 700;
  text-align: left;
  transition: transform 0.15s, border-color 0.15s;
}

.part.grab:hover:not(:disabled) {
  transform: translateY(-2px);
  border-color: var(--ink);
}

.part:disabled {
  color: var(--mute);
  background: var(--soft);
}

.part.held {
  border: 2px solid var(--ink);
  padding: 5px 11px;
}

.help {
  line-height: 1.5;
}

/* RESULT */

.overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: grid;
  place-items: center;
  padding: 16px;
  background: rgba(0, 0, 0, 0.35);
}

.dialog {
  width: min(380px, 100%);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 26px;
  border: 1px solid var(--line);
  border-radius: 18px;
  background: var(--white);
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18);
  text-align: center;
}

.dialog h2 {
  font-size: 18px;
  font-weight: 800;
}

.dialog h2:focus {
  outline: none;
}

.score {
  font-size: 46px;
  font-weight: 800;
  line-height: 1;
  color: var(--pass);
}

.score.bad {
  color: var(--fail);
}

.result-stats {
  width: 100%;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.result-stats>div {
  padding: 8px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--soft);
}

.result-stats dt {
  color: var(--mute);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}

.result-stats dd {
  font-size: 16px;
  font-weight: 800;
}

/* ANIMATION */

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes shake {

  0%,
  100% {
    transform: translateX(0);
  }

  25% {
    transform: translateX(-5px);
  }

  75% {
    transform: translateX(5px);
  }
}

/* RESPONSIVE */

@media (max-width: 900px) {
  .layout {
    grid-template-columns: minmax(0, 1fr);
  }

  .stages {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 600px) {
  .practical {
    padding: 12px;
    gap: 12px;
  }

  .card {
    padding: 14px;
    border-radius: 14px;
  }

  .stats {
    width: 100%;
    justify-content: space-between;
  }

  .stats>div {
    flex: 1;
    min-width: 0;
    padding: 6px;
  }

  .head-text h1 {
    font-size: 16px;
  }

  .slots {
    grid-template-rows: 40px 74px 84px 74px;
    gap: 6px;
  }

  .slot {
    padding: 4px;
  }

  .slot-name {
    font-size: 11px;
  }

  .trays {
    grid-template-columns: minmax(0, 1fr);
  }

  .tray {
    min-height: 0;
  }

  .part {
    min-height: 44px;
  }

  .hand {
    padding: 10px;
  }
}

@media (max-width: 360px) {
  .slot-state {
    display: none;
  }

  .stages li {
    font-size: 11px;
  }
}

@media (prefers-reduced-motion: reduce) {

  .practical *,
  .practical *::before,
  .practical *::after {
    animation: none !important;
    transition: none !important;
  }
}
</style>