<template>
  <main class="io-lab">

    <!-- HEADER -->
    <header class="lab-header">
      <button type="button" class="back-btn" aria-label="Go back" @click="goBack">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        <span>Back</span>
      </button>

      <div class="brand">
        <span class="brand-icon" aria-hidden="true"><i class="fa-solid fa-microchip"></i></span>
        <div>
          <span class="eyebrow">CSS Prep · Hardware</span>
          <h1>Input &amp; Output Devices</h1>
        </div>
      </div>
    </header>

    <!-- LOADING / ERROR -->
    <div v-if="loading" class="status" role="status">
      <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
      Loading devices…
    </div>

    <div v-else-if="loadError" class="status error" role="alert">
      <p>{{ loadError }}</p>
      <button type="button" class="btn" @click="loadDevices">Try again</button>
    </div>

    <template v-else>

      <!-- PROGRESS + INSTRUCTION -->
      <section class="progress">
        <div class="progress-meta">
          <span class="instruction">
            <template v-if="isMobile">Tap a device, then choose Input or Output.</template>
            <template v-else>Drag each device into Input or Output.</template>
          </span>
          <strong>{{ placedCount }} of {{ total }} placed</strong>
        </div>
        <div class="track" role="progressbar" :aria-valuemin="0" :aria-valuemax="total" :aria-valuenow="placedCount">
          <div class="fill" :style="{ width: progressPercent + '%' }"></div>
        </div>
      </section>

      <!-- BOARD -->
      <section class="board">

        <!-- DEVICE BANK -->
        <div class="zone bank" :class="{ over: dropTarget === 'bank' }" @dragover.prevent="onDragOver($event, 'bank')"
          @dragleave="onDragLeave($event, 'bank')" @drop.prevent="onDrop($event, null)">
          <header class="zone-head">
            <div>
              <span class="eyebrow">Device bank</span>
              <h2>Unassigned</h2>
            </div>
            <span class="count">{{ unassigned.length }}</span>
          </header>

          <p v-if="!unassigned.length" class="zone-empty">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            Every device has a category.
          </p>

          <div v-else class="tiles">
            <button v-for="device in unassigned" :key="device.id" type="button" class="tile" :class="tileState(device)"
              :draggable="!isMobile && !isSubmitted" :disabled="isSubmitted" :aria-label="`${device.name}, unassigned`"
              @dragstart="startDrag($event, device)" @dragend="endDrag" @click="tapDevice(device)">
              <span class="tile-icon" aria-hidden="true">
                <img v-if="imageFor(device)" :src="imageFor(device)" :alt="''" draggable="false"
                  @error="brokenImages[device.id] = true" />
                <i v-else :class="iconFor(device)"></i>
              </span>
              <span class="tile-name">{{ device.name }}</span>
            </button>
          </div>
        </div>

        <!-- INPUT AND OUTPUT ZONES -->
        <div class="targets">
          <div v-for="category in CATEGORIES" :key="category.type" class="zone target"
            :class="[`is-${category.type}`, { over: dropTarget === category.type }]"
            @dragover.prevent="onDragOver($event, category.type)" @dragleave="onDragLeave($event, category.type)"
            @drop.prevent="onDrop($event, category.type)">
            <header class="zone-head">
              <div class="zone-title">
                <span class="zone-icon" aria-hidden="true"><i :class="category.icon"></i></span>
                <div>
                  <h2>{{ category.label }}</h2>
                  <small>{{ category.hint }}</small>
                </div>
              </div>
              <span class="count">{{ columns[category.type].length }}</span>
            </header>

            <p v-if="!columns[category.type].length" class="drop-hint">
              <i class="fa-solid fa-arrow-down-to-line" aria-hidden="true"></i>
              {{ isMobile ? 'Tap a device, then tap here' : 'Drop devices here' }}
            </p>

            <div v-else class="list">
              <button v-for="device in columns[category.type]" :key="device.id" type="button" class="chip"
                :class="tileState(device)" :draggable="!isMobile && !isSubmitted" :disabled="isSubmitted"
                :aria-label="`${device.name}, ${category.short}`" @dragstart="startDrag($event, device)"
                @dragend="endDrag" @click="tapDevice(device)">
                <span class="chip-icon" aria-hidden="true"><i :class="iconFor(device)"></i></span>
                <span class="chip-name">{{ device.name }}</span>
                <i v-if="!isMobile" class="fa-solid fa-grip-vertical grip" aria-hidden="true"></i>
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- PLACEMENT BAR: mobile only -->
      <Transition name="rise">
        <div v-if="isMobile && selectedDevice && !isSubmitted" class="place-bar" role="region"
          aria-label="Place the selected device">
          <div class="place-info">
            <span class="eyebrow">Selected</span>
            <strong>
              <i :class="iconFor(selectedDevice)" aria-hidden="true"></i>
              {{ selectedDevice.name }}
            </strong>
          </div>

          <div class="place-actions">
            <button v-for="category in CATEGORIES" :key="category.type" type="button" class="btn"
              :class="{ current: assignments[selectedDevice.id] === category.type }"
              @click="assign(selectedDevice.id, category.type)">
              <i :class="category.icon" aria-hidden="true"></i>
              {{ category.short }}
            </button>

            <button v-if="assignments[selectedDevice.id]" type="button" class="btn ghost"
              @click="assign(selectedDevice.id, null)">
              Unassign
            </button>

            <button type="button" class="btn ghost icon-only" aria-label="Clear selection" @click="selectedId = null">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
        </div>
      </Transition>

      <!-- SUBMIT -->
      <footer class="submit-bar">
        <div class="submit-copy">
          <p>{{ submitHint }}</p>
          <p v-if="notice" class="notice">{{ notice.message }}</p>
        </div>

        <button v-if="isSubmitted" type="button" class="btn" @click="showResult = true">
          <i class="fa-solid fa-chart-simple" aria-hidden="true"></i>
          View result
        </button>

        <button v-else type="button" class="btn primary" :disabled="!canSubmit" @click="submit">
          <i :class="submitting ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-paper-plane'" aria-hidden="true"></i>
          {{ submitting ? 'Submitting…' : 'Submit assessment' }}
        </button>
      </footer>
    </template>

    <!-- RESULT MODAL -->
    <Transition name="fade">
      <div v-if="showResult && result" class="overlay" @click.self="closeResult">
        <section class="modal" role="dialog" aria-modal="true" aria-labelledby="io-result-title">
          <header class="modal-head">
            <div>
              <span class="eyebrow">Assessment result</span>
              <h2 id="io-result-title">{{ resultPassed ? 'Passed' : 'Not yet passed' }}</h2>
            </div>
            <button type="button" class="btn ghost icon-only" aria-label="Close result" @click="closeResult">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </header>

          <div class="modal-body">
            <div class="summary">
              <div class="big-score" :class="resultPassed ? 'pass' : 'fail'">
                {{ result.accuracy }}<small>%</small>
              </div>

              <dl class="stats">
                <div>
                  <dt>Correct</dt>
                  <dd>{{ result.correct }} / {{ result.total }}</dd>
                </div>
                <div>
                  <dt>Time</dt>
                  <dd>{{ durationLabel }}</dd>
                </div>
                <div>
                  <dt>Attempt</dt>
                  <dd>#{{ result.attempt_count || 1 }}</dd>
                </div>
              </dl>
            </div>

            <h3 class="review-title">Device review</h3>

            <ul v-if="result.review?.length" class="review">
              <li v-for="item in result.review" :key="item.device_id">
                <span class="review-icon" aria-hidden="true">
                  <i class="fa-solid fa-circle-dot"></i>
                </span>
                <span class="review-device">
                  <i :class="iconFor(item)" aria-hidden="true"></i>
                  {{ item.name }}
                </span>
                <span class="review-detail">
                  Your answer: {{ labelFor(item.selected_type) }}
                </span>
              </li>
            </ul>

            <p v-else class="zone-empty">Device details are not included in this result.</p>
          </div>

          <footer class="modal-foot">
            <button type="button" class="btn ghost" @click="closeResult">Close</button>
            <button type="button" class="btn primary" @click="goToRecords">
              <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
              Go to records
            </button>
          </footer>
        </section>
      </div>
    </Transition>
  </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { inputOutputRequest } from '@/assessmentrequests/inputOutput'
import { useUserRequests } from '@/composables/useUserRequests'

const router = useRouter()
const { afterSubmit } = useUserRequests()

const CATEGORIES = [
  {
    type: 'input',
    label: 'Input devices',
    short: 'Input',
    hint: 'Send data into the computer',
    icon: 'fa-solid fa-arrow-right-to-bracket',
  },
  {
    type: 'output',
    label: 'Output devices',
    short: 'Output',
    hint: 'Show or deliver data from the computer',
    icon: 'fa-solid fa-arrow-right-from-bracket',
  },
]

// Icon per device name (lowercase). Add a line here when a new device is seeded.
const DEVICE_ICONS = {
  'keyboard': 'fa-solid fa-keyboard',
  'mouse': 'fa-solid fa-computer-mouse',
  'microphone': 'fa-solid fa-microphone',
  'webcam': 'fa-solid fa-video',
  'scanner': 'fa-solid fa-file-image',
  'joystick': 'fa-solid fa-gamepad',
  'graphics tablet': 'fa-solid fa-tablet-screen-button',
  'barcode scanner': 'fa-solid fa-barcode',
  'monitor': 'fa-solid fa-desktop',
  'speaker': 'fa-solid fa-volume-high',
  'printer': 'fa-solid fa-print',
  'headphones': 'fa-solid fa-headphones',
  'projector': 'fa-solid fa-photo-film',
  'plotter': 'fa-solid fa-ruler-combined',
  'led display': 'fa-solid fa-tv',
  'braille embosser': 'fa-solid fa-braille',
}

const FALLBACK_ICON = 'fa-solid fa-microchip'

const devices = ref([])
const assignments = ref({})
const selectedId = ref(null)
const draggingId = ref(null)
const dropTarget = ref(null)
const loading = ref(true)
const loadError = ref('')
const submitting = ref(false)
const notice = ref(null)
const result = ref(null)
const showResult = ref(false)
const sessionUuid = ref('')
const brokenImages = reactive({})
const assessmentId = ref(null)
const startedAt = ref(null)
const durationMs = ref(null)

/* =========================================================
   MOBILE DETECTION
   The placement bar and tap-to-place only apply on phones.
   Desktop and laptops use drag and drop.
========================================================= */

const MOBILE_QUERY = '(max-width: 768px)'
const mediaQuery = typeof window !== 'undefined' ? window.matchMedia(MOBILE_QUERY) : null
const isMobile = ref(mediaQuery?.matches ?? false)

const onMediaChange = (event) => {
  isMobile.value = event.matches
  // Drop any tap selection when switching to desktop, so no stray bar remains.
  if (!event.matches) selectedId.value = null
}

/* =========================================================
   COMPUTED
========================================================= */

const total = computed(() => devices.value.length)

const deviceById = computed(() =>
  Object.fromEntries(devices.value.map((device) => [device.id, device]))
)

const selectedDevice = computed(() => deviceById.value[selectedId.value] ?? null)
const isSubmitted = computed(() => result.value !== null)

const unassigned = computed(() =>
  devices.value.filter((device) => !assignments.value[device.id])
)

const columns = computed(() => ({
  input: devices.value.filter((device) => assignments.value[device.id] === 'input'),
  output: devices.value.filter((device) => assignments.value[device.id] === 'output'),
}))

const placedCount = computed(() => total.value - unassigned.value.length)

const progressPercent = computed(() =>
  total.value ? (placedCount.value / total.value) * 100 : 0
)

const allPlaced = computed(() => total.value > 0 && unassigned.value.length === 0)

const canSubmit = computed(() => allPlaced.value && !submitting.value && !isSubmitted.value)

const resultPassed = computed(() => result.value?.passed === true)

const durationLabel = computed(() =>
  durationMs.value === null ? '—' : formatDuration(durationMs.value)
)

const submitHint = computed(() => {
  if (isSubmitted.value) return 'Your answers are saved to your records.'

  if (!allPlaced.value) {
    const left = unassigned.value.length
    return `${left} ${left === 1 ? 'device still needs' : 'devices still need'} a category.`
  }

  return 'Every device is placed. Submit when you are ready.'
})

/* =========================================================
   HELPERS
========================================================= */

function iconFor(device) {
  const key = String(device?.name ?? '').trim().toLowerCase()
  return DEVICE_ICONS[key] ?? FALLBACK_ICON
}

function imageFor(device) {
  if (!device.image_key || brokenImages[device.id]) return ''

  const key = device.image_key
  if (/^https?:\/\//i.test(key) || key.startsWith('/')) return key

  return `/images/input_output/${encodeURIComponent(key)}`
}

function tileState(device) {
  return {
    selected: isMobile.value && selectedId.value === device.id,
    dragging: draggingId.value === device.id,
  }
}

function labelFor(type) {
  return type === 'input' ? 'Input' : 'Output'
}

function formatDuration(ms) {
  const totalSeconds = Math.max(0, Math.round(ms / 1000))
  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  const seconds = totalSeconds % 60
  const mmss = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`

  return hours > 0 ? `${hours}:${mmss}` : mmss
}

function createSessionUuid() {
  if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID()

  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
    const random = (Math.random() * 16) | 0
    return (char === 'x' ? random : (random & 0x3) | 0x8).toString(16)
  })
}

/* =========================================================
   LOAD
========================================================= */

async function loadDevices() {
  loading.value = true
  loadError.value = ''

  try {
    const response = await inputOutputRequest.fetch()
    const body = response.data

    if (!Array.isArray(body?.data)) {
      throw new Error('The server returned an invalid device list.')
    }

    devices.value = body.data

    // Adjust these fields to match your actual API response.
    assessmentId.value =
      body.assessment_id
      ?? body.assessment?.id
      ?? body.assessment?.assessment_id
      ?? null

    if (!assessmentId.value) {
      throw new Error('The API response did not include an assessment ID.')
    }

    assignments.value = {}
    selectedId.value = null
    draggingId.value = null
    dropTarget.value = null
    notice.value = null
    result.value = null
    durationMs.value = null
    showResult.value = false
    sessionUuid.value = createSessionUuid()
    startedAt.value = Date.now()

    Object.keys(brokenImages).forEach((key) => {
      delete brokenImages[key]
    })
  } catch (error) {
    console.error('Failed to load input/output devices:', error)

    const status = error.response?.status
    loadError.value =
      status === 401 || status === 403
        ? 'Your session may have expired. Please sign in again.'
        : error.response?.data?.message
        || error.message
        || 'Could not load devices. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

/* =========================================================
   PLACEMENT
========================================================= */

function assign(id, type) {
  if (isSubmitted.value || !deviceById.value[id]) return

  const next = { ...assignments.value }

  if (type) next[id] = type
  else delete next[id]

  assignments.value = next
  selectedId.value = null
}

// Mobile: tap to select, tap again to deselect. Desktop: taps do nothing (drag instead).
function tapDevice(device) {
  if (!isMobile.value || isSubmitted.value) return
  selectedId.value = selectedId.value === device.id ? null : device.id
  notice.value = null
}

function startDrag(event, device) {
  if (isSubmitted.value || isMobile.value) {
    event.preventDefault()
    return
  }

  draggingId.value = device.id

  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', String(device.id))
  }
}

function endDrag() {
  draggingId.value = null
  dropTarget.value = null
}

function onDragOver(event, target) {
  event.preventDefault()
  dropTarget.value = target

  if (event.dataTransfer) event.dataTransfer.dropEffect = 'move'
}

function onDragLeave(event, target) {
  if (event.currentTarget.contains(event.relatedTarget)) return
  if (dropTarget.value === target) dropTarget.value = null
}

function onDrop(event, type) {
  const id = Number(event.dataTransfer?.getData('text/plain')) || draggingId.value

  dropTarget.value = null
  draggingId.value = null

  if (id) assign(id, type)
}

/* =========================================================
   SUBMIT
========================================================= */

async function submit() {
  if (!canSubmit.value || !assessmentId.value) return

  submitting.value = true
  notice.value = null

  const elapsed = startedAt.value ? Date.now() - startedAt.value : null

  const placements = devices.value.map((device) => ({
    device_id: device.id,
    selected_type: assignments.value[device.id],
  }))

  let body

  try {
    const response = await inputOutputRequest.submit({
      assessment_id: assessmentId.value,
      session_uuid: sessionUuid.value,
      duration_ms: elapsed,
      placements,
    })

    body = response.data
    
  } catch (error) {
    notice.value = {
      message: error.response?.data?.message || 'Could not submit the assessment. Please try again.',
    }
    submitting.value = false
    return
  }

  if (!body?.success || !body.data) {
    notice.value = { message: 'The server returned an invalid response.' }
    submitting.value = false
    return
  }

  durationMs.value = elapsed
  result.value = body.data
  showResult.value = true
  submitting.value = false

  try {
    afterSubmit()
  } catch (error) {
    console.warn('Cache refresh failed after submit:', error)
  }
}

/* =========================================================
   NAVIGATION AND KEYBOARD
========================================================= */

function closeResult() {
  showResult.value = false
}

function goToRecords() {
  router.push('/user/records')
}

function goBack() {
  router.replace('/user/assessments/assessment/1')
}

function handleKeydown(event) {
  if (event.key !== 'Escape') return

  if (showResult.value) closeResult()
  else if (selectedId.value) selectedId.value = null
}

onMounted(() => {
  loadDevices()
  window.addEventListener('keydown', handleKeydown)
  mediaQuery?.addEventListener('change', onMediaChange)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown)
  mediaQuery?.removeEventListener('change', onMediaChange)
})
</script>

<style scoped>
.io-lab {
  --ink: #000000;
  --text: #2a2a2a;
  --muted: #696969;
  --silver: #a9a9a9;
  --light: #d3d3d3;
  --line: #e8e8e8;
  --soft: #f7f7f7;
  --white: #ffffff;
  --ok: #11813d;
  --bad: #c81e2e;
  --tap: 44px;

  width: 100%;
  max-width: 1100px;
  margin: 0 auto;
  padding: 16px clamp(12px, 3vw, 24px) calc(24px + env(safe-area-inset-bottom, 0px));
  color: var(--text);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  font-size: 13px;
  line-height: 1.45;
  overflow-x: hidden;
}

.io-lab *,
.io-lab *::before,
.io-lab *::after {
  box-sizing: border-box;
}

.io-lab button {
  font: inherit;
  color: inherit;
  cursor: pointer;
  touch-action: manipulation;
}

.io-lab button:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

.io-lab :focus-visible {
  outline: 2px solid var(--ink);
  outline-offset: 2px;
}

.io-lab h1,
.io-lab h2,
.io-lab h3,
.io-lab p {
  margin: 0;
}

/* HEADER */

.lab-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--line);
}

.back-btn {
  display: inline-flex;
  min-height: var(--tap);
  flex: 0 0 auto;
  align-items: center;
  gap: 7px;
  padding: 0 12px;
  border: 1px solid var(--light);
  border-radius: 10px;
  background: var(--white);
  font-size: 12px;
  font-weight: 700;
}

.back-btn:hover {
  border-color: var(--ink);
}

.brand {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: 11px;
}

.brand-icon {
  width: 40px;
  height: 40px;
  flex: 0 0 40px;
  display: grid;
  place-items: center;
  border-radius: 11px;
  background: var(--ink);
  color: var(--white);
  font-size: 15px;
}

.brand h1 {
  font-size: clamp(16px, 2.4vw, 21px);
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1.2;
}

.eyebrow {
  display: block;
  margin-bottom: 3px;
  color: var(--muted);
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

/* STATUS */

.status {
  padding: 36px 12px;
  color: var(--muted);
  text-align: center;
}

.status.error p {
  margin-bottom: 12px;
  color: var(--text);
}

/* PROGRESS */

.progress {
  margin-top: 16px;
}

.progress-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  justify-content: space-between;
  gap: 4px 12px;
  margin-bottom: 8px;
  color: var(--muted);
  font-size: 12px;
}

.progress-meta strong {
  color: var(--ink);
  font-weight: 800;
}

.track {
  height: 6px;
  overflow: hidden;
  border-radius: 99px;
  background: #ececec;
}

.fill {
  height: 100%;
  border-radius: inherit;
  background: var(--ink);
  transition: width 0.25s ease;
}

/* BOARD: bank on the left, Input and Output side by side on the right */

.board {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.3fr);
  align-items: start;
  gap: 14px;
  margin-top: 16px;
}

.zone {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 14px;
  border: 1px solid var(--line);
  border-radius: 14px;
  background: var(--white);
  transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}

.zone.over {
  border: 2px dashed var(--ink);
  background: var(--soft);
  padding: 13px;
}

.zone-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--line);
}

.zone-head h2 {
  font-size: 14px;
  font-weight: 800;
  letter-spacing: -0.01em;
}

.zone-head small {
  display: block;
  margin-top: 2px;
  color: var(--muted);
  font-size: 11px;
}

.zone-title {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: 10px;
}

.zone-icon {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  background: var(--ink);
  color: var(--white);
  font-size: 13px;
}

.count {
  min-width: 26px;
  padding: 3px 8px;
  border: 1px solid var(--line);
  border-radius: 999px;
  background: var(--soft);
  color: var(--muted);
  font-size: 11px;
  font-weight: 800;
  text-align: center;
}

.zone-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 18px 8px;
  color: var(--muted);
  font-size: 12px;
  text-align: center;
}

/* BANK TILES: icon above name, a grid of cards */

.tiles {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
  max-height: 420px;
  overflow-y: auto;
  padding: 2px;
  overscroll-behavior: contain;
}

.tile {
  min-width: 0;
  min-height: 84px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 10px 6px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--white);
  text-align: center;
  user-select: none;
  cursor: grab;
  transition: border-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}

.tile:hover:not(:disabled) {
  border-color: var(--ink);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgb(0 0 0 / 6%);
}

.tile:active {
  cursor: grabbing;
}

.tile.dragging {
  opacity: 0.4;
}

.tile.selected {
  border-color: var(--ink);
  box-shadow: inset 0 0 0 1px var(--ink);
}

.tile-icon {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  background: var(--soft);
  color: var(--ink);
  font-size: 16px;
  overflow: hidden;
}

.tile-icon img {
  width: 100%;
  height: 100%;
  padding: 3px;
  object-fit: contain;
  pointer-events: none;
}

.tile-name {
  width: 100%;
  overflow: hidden;
  font-size: 11px;
  font-weight: 650;
  line-height: 1.3;
  text-overflow: ellipsis;
}

/* TARGET ZONES: Input and Output */

.targets {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  min-width: 0;
}

.target {
  min-height: 100%;
}

.target.is-input .zone-icon {
  background: var(--ink);
}

.target.is-output .zone-icon {
  background: #3a3a3a;
}

.drop-hint {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 150px;
  padding: 16px;
  border: 1px dashed var(--light);
  border-radius: 12px;
  background: var(--soft);
  color: var(--muted);
  font-size: 12px;
  text-align: center;
}

.drop-hint i {
  font-size: 18px;
  color: var(--ink);
}

.list {
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-height: 380px;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.chip {
  width: 100%;
  min-height: 44px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 10px 6px 6px;
  border: 1px solid var(--line);
  border-radius: 10px;
  background: var(--white);
  text-align: left;
  user-select: none;
  cursor: grab;
  transition: border-color 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}

.chip:hover:not(:disabled) {
  border-color: var(--ink);
}

.chip:active {
  cursor: grabbing;
}

.chip.dragging {
  opacity: 0.4;
}

.chip.selected {
  border-color: var(--ink);
  box-shadow: inset 0 0 0 1px var(--ink);
}

.chip-icon {
  width: 32px;
  height: 32px;
  flex: 0 0 32px;
  display: grid;
  place-items: center;
  border-radius: 8px;
  background: var(--soft);
  color: var(--ink);
  font-size: 13px;
}

.chip-name {
  min-width: 0;
  flex: 1;
  font-size: 12px;
  font-weight: 650;
  overflow-wrap: anywhere;
}

.grip {
  color: var(--silver);
  font-size: 11px;
}

/* BUTTONS */

.btn {
  min-height: var(--tap);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 0 14px;
  border: 1px solid var(--light);
  border-radius: 10px;
  background: var(--white);
  font-size: 12px;
  font-weight: 700;
  text-align: center;
  white-space: nowrap;
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.btn:hover:not(:disabled) {
  border-color: var(--ink);
}

.btn.primary,
.btn.current {
  border-color: var(--ink);
  background: var(--ink);
  color: var(--white);
}

.btn.primary:hover:not(:disabled),
.btn.current:hover:not(:disabled) {
  background: #262626;
}

.btn.ghost {
  border-color: transparent;
  background: transparent;
  color: var(--muted);
}

.btn.ghost:hover:not(:disabled) {
  background: var(--soft);
  color: var(--ink);
}

.btn.icon-only {
  width: var(--tap);
  padding: 0;
}

/* PLACEMENT BAR (mobile only) */

.place-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 50;
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 12px 14px calc(12px + env(safe-area-inset-bottom, 0px));
  border-top: 1px solid var(--ink);
  border-radius: 16px 16px 0 0;
  background: var(--white);
  box-shadow: 0 -10px 30px rgb(0 0 0 / 12%);
}

.place-info strong {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.place-info i {
  color: var(--muted);
}

.place-actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}

.place-actions .btn {
  width: 100%;
}

.place-actions .btn.icon-only {
  width: 100%;
}

/* SUBMIT */

.submit-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid var(--line);
}

.submit-copy {
  flex: 1 1 240px;
  min-width: 0;
}

.submit-copy p {
  color: var(--muted);
  font-size: 12px;
  line-height: 1.5;
}

.submit-copy .notice {
  margin-top: 4px;
  color: var(--bad);
  font-weight: 700;
}

/* RESULT MODAL */

.overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: grid;
  place-items: center;
  padding: 14px;
  background: rgb(0 0 0 / 40%);
  backdrop-filter: blur(3px);
}

.modal {
  width: min(520px, 100%);
  max-height: min(88vh, 680px);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid var(--line);
  border-radius: 16px;
  background: var(--white);
  box-shadow: 0 20px 50px rgb(0 0 0 / 20%);
}

.modal-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  padding: 16px 18px;
  border-bottom: 1px solid var(--line);
}

.modal-head h2 {
  font-size: 18px;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.modal-body {
  min-height: 0;
  padding: 14px 18px;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.summary {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--soft);
}

.big-score {
  flex: 0 0 auto;
  font-size: 38px;
  font-weight: 800;
  letter-spacing: -0.04em;
  line-height: 1;
}

.big-score small {
  margin-left: 2px;
  font-size: 17px;
}

.big-score.pass {
  color: var(--ok);
}

.big-score.fail {
  color: var(--bad);
}

.stats {
  display: grid;
  flex: 1;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
  min-width: 0;
}

.stats dt {
  color: var(--muted);
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.stats dd {
  margin-top: 3px;
  font-size: 13px;
  font-weight: 800;
}

.review-title {
  margin: 16px 0 8px;
  color: var(--muted);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.review {
  display: flex;
  max-height: 240px;
  flex-direction: column;
  overflow-y: auto;
  border: 1px solid var(--line);
  border-radius: 11px;
  list-style: none;
}

.review li {
  display: grid;
  grid-template-columns: 22px minmax(0, 1fr);
  align-items: center;
  gap: 3px 10px;
  padding: 9px 11px;
  border-bottom: 1px solid var(--line);
}

.review li:last-child {
  border-bottom: 0;
}

.review-icon {
  width: 22px;
  height: 22px;
  grid-row: span 2;
  display: grid;
  place-items: center;
  border-radius: 50%;
  color: var(--white);
  font-size: 10px;
}

.review li.ok .review-icon {
  background: var(--ok);
}

.review li.bad .review-icon {
  background: var(--bad);
}

.review-device {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 12px;
  font-weight: 700;
}

.review-device i {
  color: var(--muted);
}

.review-detail {
  color: var(--muted);
  font-size: 11px;
}

.review li.bad .review-detail {
  color: var(--bad);
}

.modal-foot {
  display: flex;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 8px;
  padding: 12px 18px;
  border-top: 1px solid var(--line);
}

/* TRANSITIONS */

.fade-enter-active,
.fade-leave-active,
.rise-enter-active,
.rise-leave-active {
  transition: opacity 0.16s ease, transform 0.16s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.rise-enter-from,
.rise-leave-to {
  opacity: 0;
  transform: translateY(100%);
}

/* TABLET */

@media (max-width: 960px) {
  .tiles {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 820px) {
  .board {
    grid-template-columns: minmax(0, 1fr);
  }

  .tiles {
    grid-template-columns: repeat(5, minmax(0, 1fr));
    max-height: none;
  }
}

/* PHONE: tap-to-place, placement bar at the bottom */

@media (max-width: 768px) {
  .io-lab {
    padding-top: 12px;
    padding-bottom: 150px;
  }

  .lab-header {
    padding-bottom: 11px;
  }

  .brand-icon {
    width: 34px;
    height: 34px;
    flex-basis: 34px;
    font-size: 13px;
  }

  .board {
    gap: 10px;
    margin-top: 12px;
  }

  .zone {
    padding: 10px;
    border-radius: 12px;
  }

  .zone-head {
    padding-bottom: 8px;
  }

  .zone-icon {
    width: 28px;
    height: 28px;
    flex-basis: 28px;
    font-size: 11px;
  }

  .zone-head h2 {
    font-size: 12px;
  }

  .zone-head small {
    font-size: 9px;
  }

  .tiles {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 6px;
  }

  .tile {
    min-height: 72px;
    padding: 8px 4px;
  }

  .tile-icon {
    width: 30px;
    height: 30px;
    font-size: 13px;
  }

  .tile-name {
    font-size: 10px;
  }

  .targets {
    gap: 8px;
  }

  .drop-hint {
    min-height: 110px;
    padding: 12px 8px;
    font-size: 11px;
  }

  .chip {
    min-height: 42px;
    padding: 5px 8px 5px 5px;
    gap: 7px;
  }

  .chip-icon {
    width: 28px;
    height: 28px;
    flex-basis: 28px;
    font-size: 11px;
  }

  .chip-name {
    font-size: 11px;
  }

  .submit-bar {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .submit-bar>.btn {
    width: 100%;
  }

  .overlay {
    padding: 8px;
    align-items: end;
  }

  .modal {
    border-radius: 16px 16px 0 0;
    max-height: 90vh;
  }

  .modal-foot .btn {
    flex: 1 1 120px;
  }

  .big-score {
    font-size: 32px;
  }
}

@media (max-width: 380px) {
  .io-lab {
    padding-right: 8px;
    padding-left: 8px;
  }

  .tiles {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .back-btn span {
    display: none;
  }

  .back-btn {
    padding: 0;
    min-width: var(--tap);
    justify-content: center;
  }

  .stats dt {
    font-size: 8px;
  }

  .stats dd {
    font-size: 11px;
  }
}

@media (hover: none) {

  .tile:hover:not(:disabled),
  .chip:hover:not(:disabled) {
    border-color: var(--line);
    transform: none;
    box-shadow: none;
  }
}

@media (prefers-reduced-motion: reduce) {

  .io-lab *,
  .io-lab *::before,
  .io-lab *::after {
    transition-duration: 0.01ms !important;
    animation-duration: 0.01ms !important;
  }
}
</style>