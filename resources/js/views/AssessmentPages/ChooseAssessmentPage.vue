<template>
  <div class="assessment-page" :class="{ 'is-inactive': isInactive }" :style="pageStyle">
    <div class="page-shell">
      <button type="button" class="back-btn" @click="goBack">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        <span>Back to COCs</span>
      </button>

      <!-- OFFLINE NOTICE -->
      <div v-if="!isOnline" class="notice notice-warn" role="status">
        <i class="fa-solid fa-wifi" aria-hidden="true"></i>
        <span>You're offline. The list will load again when your connection returns.</span>
      </div>

      <!-- SLOW NETWORK HINT -->
      <p v-if="status === 'loading' && slowNetwork" class="slow-note" role="status">
        <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
        Still loading. Your connection may be slow.
      </p>

      <!-- LOADING: skeleton hero and cards -->
      <template v-if="status === 'loading'">
        <span class="sr-only" role="status" aria-live="polite">Loading COC assessments</span>

        <header class="coc-hero skeleton-hero" aria-hidden="true">
          <span class="sk sk-hero-icon"></span>
          <div class="sk-hero-copy">
            <span class="sk sk-line sk-w-40"></span>
            <span class="sk sk-title"></span>
            <span class="sk sk-line"></span>
            <span class="sk sk-line sk-w-80"></span>
          </div>
          <span class="sk sk-readout"></span>
        </header>

        <div class="assessment-grid" aria-hidden="true">
          <div v-for="item in skeletonItems" :key="item" class="assessment-card skeleton-card">
            <div class="sk-row">
              <span class="sk sk-icon-sm"></span>
              <span class="sk sk-pill"></span>
            </div>
            <span class="sk sk-line sk-w-40"></span>
            <span class="sk sk-title sk-title-sm"></span>
            <span class="sk sk-line"></span>
            <span class="sk sk-line sk-w-80"></span>
            <span class="sk sk-preview"></span>
            <span class="sk sk-footer"></span>
          </div>
        </div>
      </template>

      <!-- ERROR STATES -->
      <div v-else-if="status === 'error'" class="state-card error-state" role="alert">
        <div class="state-icon" :class="`tone-${currentError.tone}`" aria-hidden="true">
          <i :class="currentError.icon"></i>
        </div>

        <div class="state-copy">
          <strong>{{ currentError.title }}</strong>
          <p>{{ currentError.text }}</p>
        </div>

        <div class="state-actions">
          <button v-if="currentError.retry" type="button" class="secondary-btn" @click="retry">
            <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
            Try again
          </button>

          <button v-if="currentError.signIn" type="button" class="secondary-btn" @click="goToLogin">
            <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
            Sign in again
          </button>
        </div>
      </div>

      <!-- READY -->
      <template v-else>
        <div v-if="usingCache" class="notice" role="status">
          <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
          <span>Showing your last saved list. It may be out of date.</span>
          <button type="button" class="notice-action" :disabled="!isOnline" @click="retry">
            Refresh
          </button>
        </div>

        <header class="coc-hero">
          <div class="hero-icon" aria-hidden="true"><i :class="cocIcon"></i></div>

          <div class="hero-copy">
            <div class="eyebrow-row">
              <span class="coc-label">COC {{ paddedCocNumber }}</span>
              <span class="qualification-label">Computer Systems Servicing NC II</span>
            </div>

            <h1>{{ coc.title || 'Certificate of Competency' }}</h1>

            <p class="hero-description">
              {{ coc.description || 'Choose an assessment activity for this competency.' }}
            </p>

            <div class="hero-meta">
              <span v-if="coc.category">
                <i class="fa-solid fa-layer-group" aria-hidden="true"></i>{{ coc.category }}
              </span>
              <span>
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>{{ coc.difficulty || 'Core' }} competency
              </span>
              <span>
                <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                {{ assessments.length }} {{ assessmentWord(assessments.length) }}
              </span>
            </div>
          </div>

          <div class="hero-readout">
            <strong>{{ availableCount }}</strong>
            <span>ready now</span>
          </div>
        </header>

        <section class="assessment-section">
          <div class="section-heading">
            <div class="section-intro">
              <span class="section-kicker">Practice options</span>
              <h2>Choose an Assessment</h2>
              <p>
                Available activities can be started now. Planned activities remain visible so you can see the
                coverage being prepared for this COC.
              </p>
            </div>

            <div v-if="assessments.length" class="section-tools">
              <div class="section-count">{{ availableCount }} of {{ assessments.length }} ready</div>

              <div class="view-toggle" role="group" aria-label="Assessment layout">
                <button type="button" class="view-btn" :class="{ active: viewMode === 'grid' }"
                  :aria-pressed="viewMode === 'grid'" @click="setViewMode('grid')" title="Grid view">
                  <i class="fa-solid fa-grip" aria-hidden="true"></i><span>Grid</span>
                </button>
                <button type="button" class="view-btn" :class="{ active: viewMode === 'flex' }"
                  :aria-pressed="viewMode === 'flex'" @click="setViewMode('flex')" title="Flex view">
                  <i class="fa-solid fa-bars" aria-hidden="true"></i><span>Flex</span>
                </button>
              </div>
            </div>
          </div>

          <!-- EMPTY -->
          <div v-if="!assessments.length" class="empty-state">
            <div class="empty-icon" aria-hidden="true"><i class="fa-solid fa-box-open"></i></div>
            <strong>No assessments have been added to this COC yet.</strong>
            <p>Check back later, or ask your instructor to add the assessments for this competency.</p>
            <button type="button" class="secondary-btn" @click="retry">
              <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
              Refresh
            </button>
          </div>

          <!-- LIST -->
          <div v-else class="assessment-grid" :class="{ 'flex-view': viewMode === 'flex' }">
            <article v-for="assessment in assessments" :key="assessment.id ?? `${assessment.type}-${assessment.title}`"
              class="assessment-card"
              :class="{ 'is-available': assessment.available, 'is-planned': !assessment.available }">
              <div class="card-top">
                <div class="type-icon" aria-hidden="true">
                  <i :class="assessment.icon || fallbackAssessmentIcon(assessment.type)"></i>
                </div>

                <span class="status-pill" :class="assessment.available ? 'ready' : 'planned'">
                  <i :class="assessment.available ? 'fa-solid fa-circle-check' : 'fa-regular fa-clock'"
                    aria-hidden="true"></i>
                  {{ assessment.available ? 'Ready' : 'Planned' }}
                </span>
              </div>

              <div class="card-body">
                <span class="type-label">{{ formatType(assessment.type) }}</span>
                <h3>{{ assessment.title }}</h3>
                <p class="card-description">
                  {{ assessment.description || 'Assessment details are being prepared.' }}
                </p>

                <div v-if="assessment.category || assessment.difficulty" class="tags">
                  <span v-if="assessment.category" class="tag">
                    <i class="fa-solid fa-layer-group" aria-hidden="true"></i>{{ assessment.category }}
                  </span>
                  <span v-if="assessment.difficulty" class="tag">
                    <i class="fa-solid fa-signal" aria-hidden="true"></i>{{ assessment.difficulty }}
                  </span>
                </div>

                <dl v-if="metaItems(assessment).length" class="meta-grid">
                  <div v-for="item in metaItems(assessment)" :key="item.label + item.value" class="meta-item">
                    <i :class="item.icon" aria-hidden="true"></i>
                    <div>
                      <dt>{{ item.label }}</dt>
                      <dd>{{ item.value }}</dd>
                    </div>
                  </div>
                </dl>

                <div v-if="focusItems(assessment).length" class="focus-block">
                  <span class="focus-label">Practice focus</span>
                  <div class="focus-list">
                    <span v-for="focus in focusItems(assessment)" :key="focus" class="focus-chip">{{ focus }}</span>
                  </div>
                </div>
              </div>

              <div class="card-footer">

                <button v-if="assessment.available" type="button" class="start-btn" :disabled="!assessment.path"
                  @click="startAssessment(assessment)">
                  Start <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>

                <span v-else class="planned-label">Coming soon</span>
              </div>
            </article>
          </div>
        </section>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { cocThemeStyle, isInactiveCoc } from '@/composables/cocTheme'
import api from '@/bootstrap'

const route = useRoute()
const router = useRouter()

/* =========================
   CONSTANTS
========================= */

const CACHE_TTL = 5 * 60 * 1000
const CACHE_PREFIX = 'cssprep:selected-assessment:v2:'
const VIEW_STORAGE_KEY = 'cssprep:assessment-view'
const SLOW_AFTER_MS = 4000
const skeletonItems = [1, 2, 3]

// Failures that may show saved data. Access problems never do.
const SAVED_DATA_TYPES = ['offline', 'network', 'timeout', 'server']

const ERROR_STATES = {
  offline: {
    icon: 'fa-solid fa-wifi',
    title: "You're offline",
    text: 'Check your internet connection. The list will load automatically once you are back online.',
    tone: 'danger',
    retry: true,
  },
  network: {
    icon: 'fa-solid fa-plug-circle-xmark',
    title: "Can't reach the server",
    text: 'The connection failed. Check your network and try again.',
    tone: 'danger',
    retry: true,
  },
  timeout: {
    icon: 'fa-solid fa-hourglass-half',
    title: 'The request took too long',
    text: 'Your network may be slow right now. Try again in a moment.',
    tone: 'danger',
    retry: true,
  },
  server: {
    icon: 'fa-solid fa-triangle-exclamation',
    title: 'Unable to load this competency',
    text: 'The server could not load the assessments. Please try again shortly.',
    tone: 'danger',
    retry: true,
  },
  notFound: {
    icon: 'fa-solid fa-circle-question',
    title: 'Competency not found',
    text: 'This COC may have been removed. Go back and choose another competency.',
    tone: 'neutral',
    retry: false,
  },
  permission: {
    icon: 'fa-solid fa-lock',
    title: 'Access denied',
    text: 'Your account does not have permission to view this competency. Contact your instructor if this is a mistake.',
    tone: 'neutral',
    retry: false,
  },
  session: {
    icon: 'fa-solid fa-user-clock',
    title: 'Your session has expired',
    text: 'For your security, please sign in again to continue.',
    tone: 'neutral',
    retry: false,
    signIn: true,
  },
}

/* =========================
   STATE
========================= */

// status: loading | ready | error
const status = ref('loading')
const errorType = ref('server')
const usingCache = ref(false)
const isOnline = ref(navigator.onLine)
const slowNetwork = ref(false)

const coc = ref({})
const assessments = ref([])

const pendingRequests = new Map()
let requestVersion = 0
let slowTimer = null

const currentError = computed(() => ERROR_STATES[errorType.value] ?? ERROR_STATES.server)

/* Grid / Flex preference */
const readSavedView = () => {
  try {
    return sessionStorage.getItem(VIEW_STORAGE_KEY) === 'flex' ? 'flex' : 'grid'
  } catch {
    return 'grid'
  }
}

const viewMode = ref(readSavedView())

const setViewMode = (mode) => {
  if (!['grid', 'flex'].includes(mode)) return

  viewMode.value = mode

  try {
    sessionStorage.setItem(VIEW_STORAGE_KEY, mode)
  } catch (error) {
    console.warn('Unable to save assessment view preference:', error)
  }
}

const availableCount = computed(() =>
  assessments.value.filter((assessment) => Boolean(assessment.available)).length
)

const themeSource = computed(() => ({
  coc_number: coc.value?.coc_number,
  available_assessment_count: availableCount.value,
}))

const pageStyle = computed(() => cocThemeStyle(themeSource.value))
const isInactive = computed(() => isInactiveCoc(themeSource.value))

const paddedCocNumber = computed(() => {
  const number = Number(coc.value?.coc_number ?? 0)
  return String(Number.isFinite(number) ? number : 0).padStart(2, '0')
})

const cocIcon = computed(() => ({
  1: 'fa-solid fa-computer',
  2: 'fa-solid fa-network-wired',
  3: 'fa-solid fa-server',
  4: 'fa-solid fa-screwdriver-wrench',
}[Number(coc.value?.coc_number)] || 'fa-solid fa-layer-group'))

const assessmentWord = (count) => (Number(count) === 1 ? 'assessment' : 'assessments')

/* =========================
   CACHE
========================= */

const cacheKey = (id) => `${CACHE_PREFIX}${id}`

function readCache(id, allowExpired = false) {
  try {
    const raw = sessionStorage.getItem(cacheKey(id))
    if (!raw) return null

    const cached = JSON.parse(raw)

    if (!cached?.data || !cached?.timestamp) {
      sessionStorage.removeItem(cacheKey(id))
      return null
    }

    if (!allowExpired && Date.now() - cached.timestamp >= CACHE_TTL) return null

    return cached.data
  } catch (error) {
    console.warn('Unable to read assessment cache:', error)
    return null
  }
}

function writeCache(id, data) {
  try {
    sessionStorage.setItem(cacheKey(id), JSON.stringify({ timestamp: Date.now(), data }))
  } catch (error) {
    console.warn('Unable to save assessment cache:', error)
  }
}

function clearCache(id) {
  try {
    sessionStorage.removeItem(cacheKey(id))
  } catch (error) {
    console.warn('Unable to clear assessment cache:', error)
  }
}

/* =========================
   STATE HELPERS
========================= */

function startLoading() {
  status.value = 'loading'
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

// Validates the response and shows it. Throws if the shape is wrong.
function applyData(data, fromCache = false) {
  if (data?.status !== 'success' || !data?.coc) {
    const invalid = new Error('Invalid assessment response.')
    invalid.isInvalidResponse = true
    throw invalid
  }

  coc.value = data.coc
  assessments.value = Array.isArray(data.assessments) ? data.assessments : []
  usingCache.value = fromCache
  status.value = 'ready'
}

function classifyError(error) {
  const code = error?.response?.status

  if (error?.isInvalidResponse) return 'server'
  if (code === 401 || code === 419) return 'session'
  if (code === 403) return 'permission'
  if (code === 404) return 'notFound'
  if (code >= 500) return 'server'
  if (error?.response) return 'server'
  if (error?.code === 'ECONNABORTED' || error?.code === 'ETIMEDOUT') return 'timeout'
  if (!navigator.onLine) return 'offline'

  return 'network'
}

function showFailure(type, id = Number(route.params.id)) {
  errorType.value = type

  if (SAVED_DATA_TYPES.includes(type)) {
    const saved = readCache(id, true)

    if (saved) {
      try {
        applyData(saved, true)
        return
      } catch {
        clearCache(id)
      }
    }
  } else {
    // Permission, session, and not-found problems must not keep showing old data.
    clearCache(id)
  }

  coc.value = {}
  assessments.value = []
  usingCache.value = false
  status.value = 'error'
}

/* =========================
   FETCH
========================= */

async function fetchAssessmentData({ force = false } = {}) {
  const id = Number(route.params.id)
  const version = ++requestVersion

  if (!Number.isFinite(id) || id <= 0) {
    showFailure('notFound', id)
    return
  }

  if (!force) {
    const cached = readCache(id)

    if (cached) {
      try {
        applyData(cached)
        return
      } catch {
        clearCache(id)
      }
    }
  }

  if (!navigator.onLine) {
    showFailure('offline', id)
    return
  }

  startLoading()

  try {
    let request = pendingRequests.get(id)

    if (!request) {
      request = api
        .get(`/api/selected-assessment/${id}`)
        .then(({ data }) => {
          if (data?.status === 'success' && data?.coc) writeCache(id, data)
          return data
        })
        .finally(() => pendingRequests.delete(id))

      pendingRequests.set(id, request)
    }

    const data = await request

    if (version !== requestVersion) return

    applyData(data)
  } catch (error) {
    if (version !== requestVersion) return

    console.error('Failed to load COC assessments:', error)
    showFailure(classifyError(error), id)
  } finally {
    if (version === requestVersion) stopSlowTimer()
  }
}

function retry() {
  fetchAssessmentData({ force: true })
}

function goToLogin() {
  router.push('/login')
}

/* =========================
   LABELS AND META
========================= */

const formatType = (type) => {
  const labels = {
    hardware_procedure: 'Virtual hardware practical',
    drag_drop: 'Input and output lab',
    input_output: 'Input and output lab',
    image_identification: 'Image identification',
    rj45_hands_on: 'RJ-45 practical',
    knowledge_check: 'Knowledge check',
    multiple_choice: 'Knowledge check',
  }

  const value = String(type ?? '').trim()

  if (labels[value]) return labels[value]
  if (!value) return 'Assessment'

  return value.replace(/[_-]+/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
}

const fallbackAssessmentIcon = (type) => {
  const value = String(type ?? '').toLowerCase()

  if (value.includes('rj45') || value.includes('network')) return 'fa-solid fa-network-wired'
  if (value.includes('hardware') || value.includes('procedure')) return 'fa-solid fa-screwdriver-wrench'
  if (value.includes('image')) return 'fa-solid fa-image'

  return 'fa-solid fa-list-check'
}

const isPlainObject = (value) => value !== null && typeof value === 'object' && !Array.isArray(value)

const isFilled = (value) =>
  value !== null && value !== undefined && value !== '' && !(Array.isArray(value) && value.length === 0)

const normalizedMeta = (assessment) => {
  const meta = assessment?.meta

  if (!meta) return {}

  if (typeof meta === 'string') {
    try {
      const parsed = JSON.parse(meta)
      return isPlainObject(parsed) ? parsed : {}
    } catch {
      return {}
    }
  }

  return isPlainObject(meta) ? meta : {}
}

const META_FIELDS = {
  assessment_format: { order: 1, label: 'Format', icon: 'fa-solid fa-display', format: (v) => String(v) },
  per_attempt: { order: 2, label: 'Per attempt', icon: 'fa-solid fa-list-check', format: (v) => `${v} items` },
  bank_size: { order: 3, label: 'Item bank', icon: 'fa-solid fa-database', format: (v) => `${v} items` },
  max_score: { order: 3, label: 'Maximum score', icon: 'fa-solid fa-star', format: (v) => `${v} points` },
  disassembly_actions: { order: 4, label: 'Disassembly', icon: 'fa-solid fa-arrow-down-wide-short', format: (v) => `${v} steps` },
  assembly_actions: { order: 5, label: 'Assembly', icon: 'fa-solid fa-arrow-up-wide-short', format: (v) => `${v} steps` },
  total_actions: { order: 6, label: 'Activity size', icon: 'fa-solid fa-list-ol', format: (v) => `${v} actions` },
  pins: { order: 4, label: 'Cable pins', icon: 'fa-solid fa-grip-lines-vertical', format: (v) => `${v} pins` },
  standards: { order: 5, label: 'Standards', icon: 'fa-solid fa-network-wired', format: (v) => (Array.isArray(v) ? v.join(' / ') : String(v)) },
  passing_score: { order: 99, label: 'Passing score', icon: 'fa-solid fa-bullseye', format: (v) => `${v}%` },
}

const HIDDEN_META_KEYS = new Set(['hidden_procedure', 'visual', 'mode', 'focus', 'display', 'items'])

const humanize = (key) =>
  String(key).replace(/[_-]+/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())

const metaItems = (assessment) => {
  const meta = normalizedMeta(assessment)
  const rows = []

  if (Array.isArray(meta.display)) {
    meta.display.filter(isFilled).forEach((text) => {
      rows.push({ order: 50, label: 'Details', value: String(text), icon: 'fa-solid fa-circle-info' })
    })
  }

  Object.entries(meta).forEach(([key, value]) => {
    if (HIDDEN_META_KEYS.has(key) || !isFilled(value)) return
    if (typeof value === 'object' && !Array.isArray(value)) return

    const field = META_FIELDS[key]

    rows.push(
      field
        ? { order: field.order, label: field.label, value: field.format(value), icon: field.icon }
        : { order: 60, label: humanize(key), value: String(value), icon: 'fa-solid fa-circle-info' }
    )
  })

  return rows
    .sort((a, b) => a.order - b.order)
    .map(({ label, value, icon }) => ({ label, value, icon }))
}

const focusItems = (assessment) => {
  const focus = normalizedMeta(assessment).focus

  if (Array.isArray(focus)) return focus.filter(isFilled).map(String).slice(0, 6)

  if (typeof focus === 'string') {
    return focus.split(',').map((item) => item.trim()).filter(Boolean).slice(0, 6)
  }

  return []
}

/* =========================
   NAVIGATION
========================= */

const startAssessment = (assessment) => {
  if (!assessment?.available || !assessment?.path || !assessment?.coc_id) return

  const path = String(assessment.path).replace(/\/+$/, '')
  router.push(`${path}/${encodeURIComponent(assessment.coc_id)}`)
}

const goBack = () => router.push('/user/assessments')

/* =========================
   CONNECTION EVENTS
========================= */

function handleOnline() {
  isOnline.value = true

  const waitingForNetwork =
    status.value === 'error' && ['offline', 'network', 'timeout'].includes(errorType.value)

  if (waitingForNetwork || usingCache.value) {
    fetchAssessmentData({ force: true })
  }
}

function handleOffline() {
  isOnline.value = false
}

/* =========================
   LIFECYCLE
========================= */

watch(
  () => route.params.id,
  () => fetchAssessmentData(),
  { immediate: true }
)

onMounted(() => {
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)
})

onBeforeUnmount(() => {
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
  clearTimeout(slowTimer)
})
</script>

<style scoped>
.assessment-page {
  --white: #fff;
  --black: #000;
  --gray-50: #fafafa;
  --gray-100: #f3f3f3;
  --gray-200: #e6e6e6;
  --gray-300: #d3d3d3;
  --gray-500: #7a7a7a;
  --gray-700: #4f4f4f;
  --coc-accent: #000;
  --coc-tint: #f2f2f2;
  min-height: 100vh;
  width: 100%;
  padding: clamp(16px, 3vw, 32px);
  color: var(--black);
  font-size: 13px;
  box-sizing: border-box;
}

.assessment-page *,
.assessment-page *::before,
.assessment-page *::after {
  box-sizing: border-box;
}

.assessment-page h1,
.assessment-page h2,
.assessment-page h3,
.assessment-page p,
.assessment-page dl,
.assessment-page dd {
  margin: 0;
}

.assessment-page :focus-visible {
  outline: 2px solid var(--coc-accent);
  outline-offset: 3px;
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

.page-shell {
  width: min(1180px, 100%);
  margin: 0 auto;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 14px;
  padding: 6px 0;
  border: 0;
  background: transparent;
  color: var(--gray-700);
  font: inherit;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.back-btn:hover {
  color: var(--black);
}

/* NOTICES */

.notice {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
  padding: 10px 13px;
  border: 1px solid var(--gray-300);
  border-radius: 12px;
  background: var(--white);
  font-size: 12px;
  line-height: 1.5;
}

.notice i {
  color: var(--gray-700);
}

.notice span {
  flex: 1 1 220px;
  min-width: 0;
}

.notice-warn {
  border-color: #e7c76a;
  background: #fffaf0;
}

.notice-action {
  min-height: 30px;
  padding: 0 12px;
  border: 1px solid var(--black);
  border-radius: 9px;
  background: var(--white);
  color: var(--black);
  font: inherit;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
}

.notice-action:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.slow-note {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-bottom: 12px;
  color: var(--gray-700);
  font-size: 12px;
}

/* HERO */

.coc-hero {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 18px;
  margin-bottom: 10px;
  padding: clamp(18px, 3vw, 26px);
  border: 1px solid var(--gray-300);
  border-top: 3px solid var(--coc-accent);
  border-radius: 16px;
  background: linear-gradient(180deg, var(--coc-tint) 0%, var(--white) 70%);
  box-shadow: 0 8px 24px #0000000a;
}

.hero-icon {
  width: 56px;
  height: 56px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border-radius: 14px;
  background: var(--coc-accent);
  color: var(--white);
  font-size: 20px;
  box-shadow: 0 4px 12px #0000001f;
}

.hero-copy {
  min-width: 0;
}

.eyebrow-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
}

.coc-label,
.qualification-label,
.section-kicker,
.type-label,
.focus-label,
.hero-readout span {
  font-size: 9.5px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.coc-label {
  padding: 4px 7px;
  border-radius: 6px;
  background: var(--coc-accent);
  color: var(--white);
}

.qualification-label {
  color: var(--gray-500);
}

.coc-hero h1 {
  font-size: clamp(20px, 2.8vw, 28px);
  line-height: 1.15;
  letter-spacing: -0.03em;
}

.hero-description {
  max-width: 760px;
  margin-top: 8px;
  color: var(--gray-700);
  font-size: 12px;
  line-height: 1.6;
}

.hero-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 12px;
}

.hero-meta span,
.tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  max-width: 100%;
  padding: 5px 8px;
  border: 1px solid var(--gray-200);
  border-radius: 7px;
  background: var(--white);
  color: var(--gray-700);
  font-size: 10px;
  font-weight: 700;
  overflow-wrap: anywhere;
}

.hero-meta i,
.tag i {
  color: var(--coc-accent);
}

.hero-readout {
  min-width: 80px;
  padding-left: 18px;
  border-left: 1px solid var(--gray-200);
  text-align: center;
}

.hero-readout strong {
  display: block;
  color: var(--coc-accent);
  font-size: 26px;
  line-height: 1;
}

.hero-readout span {
  display: block;
  margin-top: 5px;
  color: var(--gray-500);
}

/* SECTION */

.assessment-section {
  margin-top: 22px;
}

.section-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 14px;
  margin-bottom: 12px;
}

.section-intro {
  min-width: 0;
}

.section-kicker {
  color: var(--gray-500);
}

.section-heading h2 {
  margin-top: 3px;
  font-size: 18px;
  letter-spacing: -0.02em;
}

.section-heading p {
  max-width: 680px;
  margin-top: 5px;
  color: var(--gray-700);
  font-size: 11.5px;
  line-height: 1.55;
}

.section-tools {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 8px;
  flex-shrink: 0;
}

.section-count {
  padding: 6px 9px;
  border: 1px solid var(--gray-200);
  border-radius: 7px;
  background: var(--white);
  color: var(--gray-700);
  font-size: 10px;
  font-weight: 800;
  white-space: nowrap;
}

.view-toggle {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 3px;
  border: 1px solid var(--gray-300);
  border-radius: 9px;
  background: var(--gray-50);
}

.view-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-height: 30px;
  padding: 0 10px;
  border: 1px solid transparent;
  border-radius: 6px;
  background: transparent;
  color: var(--gray-700);
  font: inherit;
  font-size: 10px;
  font-weight: 800;
  cursor: pointer;
  transition: background 0.18s ease, color 0.18s ease;
}

.view-btn:hover {
  background: var(--white);
  color: var(--black);
}

.view-btn.active {
  border-color: var(--gray-300);
  background: var(--white);
  color: var(--black);
  box-shadow: 0 1px 3px #0000000f;
}

.view-btn:focus-visible {
  outline: 2px solid var(--coc-accent);
  outline-offset: 2px;
}

/* CARDS */

.assessment-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 12px;
}

.assessment-card {
  position: relative;
  min-width: 0;
  display: flex;
  flex-direction: column;
  padding: 16px;
  border: 1px solid var(--gray-300);
  border-radius: 14px;
  background: var(--white);
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.assessment-card.is-available::before {
  content: '';
  position: absolute;
  top: -1px;
  left: 14px;
  right: 14px;
  height: 3px;
  border-radius: 0 0 3px 3px;
  background: var(--coc-accent);
}

.assessment-card.is-available {
  background: linear-gradient(180deg, var(--coc-tint) 0%, var(--white) 55%);
}

.assessment-card.is-available:hover {
  transform: translateY(-2px);
  border-color: var(--coc-accent);
  box-shadow: 0 10px 22px #00000012;
}

.assessment-card.is-planned {
  background: var(--gray-50);
}

.card-top,
.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.type-icon {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border: 1px solid var(--gray-300);
  border-radius: 10px;
  background: var(--white);
  color: var(--gray-500);
  font-size: 14px;
}

.is-available .type-icon {
  border-color: transparent;
  background: var(--coc-accent);
  color: var(--white);
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 7px;
  border-radius: 999px;
  font-size: 8.5px;
  font-weight: 800;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  white-space: nowrap;
}

.status-pill.ready {
  background: var(--coc-accent);
  color: var(--white);
}

.status-pill.planned {
  border: 1px solid var(--gray-300);
  background: var(--white);
  color: var(--gray-700);
}

.card-body {
  flex: 1;
  margin-top: 14px;
}

.assessment-card h3 {
  margin: 4px 0 6px;
  font-size: 15px;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.card-description {
  color: var(--gray-700);
  font-size: 11px;
  line-height: 1.55;
  overflow-wrap: anywhere;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  margin: 10px 0;
}

.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(118px, 1fr));
  gap: 6px;
  margin-top: 12px;
}

.meta-item {
  min-width: 0;
  display: flex;
  align-items: flex-start;
  gap: 7px;
  padding: 7px;
  border: 1px solid var(--gray-200);
  border-radius: 8px;
  background: var(--white);
}

.meta-item>i {
  width: 14px;
  margin-top: 2px;
  color: var(--coc-accent);
  font-size: 11px;
  text-align: center;
}

.meta-item dt {
  color: var(--gray-500);
  font-size: 8.5px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.meta-item dd {
  margin-top: 1px;
  font-size: 10.5px;
  font-weight: 700;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.focus-block {
  margin-top: 12px;
}

.focus-label {
  display: block;
  margin-bottom: 6px;
  color: var(--gray-500);
}

.focus-list {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.focus-chip {
  max-width: 100%;
  padding: 4px 7px;
  border: 1px solid var(--gray-200);
  border-radius: 6px;
  background: var(--white);
  color: var(--gray-700);
  font-size: 9px;
  font-weight: 700;
  overflow-wrap: anywhere;
}

.card-footer {
  flex-wrap: wrap;
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px solid var(--gray-200);
}

.record-note {
  min-width: 0;
  flex: 1 1 140px;
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--gray-500);
  font-size: 9px;
  line-height: 1.35;
}

.record-note i {
  flex-shrink: 0;
  color: var(--coc-accent);
}

.start-btn,
.secondary-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border-radius: 8px;
  font: inherit;
  font-weight: 800;
  cursor: pointer;
}

.start-btn {
  flex-shrink: 0;
  min-height: 32px;
  padding: 0 11px;
  border: 1px solid var(--coc-accent);
  background: var(--coc-accent);
  color: var(--white);
  font-size: 10.5px;
}

.start-btn:hover:not(:disabled) {
  filter: brightness(1.15);
}

.start-btn:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

.secondary-btn {
  min-height: 36px;
  padding: 0 12px;
  border: 1px solid var(--black);
  background: var(--white);
  color: var(--black);
  font-size: 11px;
}

.secondary-btn:hover {
  background: var(--gray-100);
}

.planned-label {
  flex-shrink: 0;
  color: var(--gray-500);
  font-size: 9.5px;
  font-weight: 800;
  text-transform: uppercase;
}

/* FLEX VIEW */

.assessment-grid.flex-view {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.assessment-grid.flex-view .assessment-card {
  display: grid;
  grid-template-columns: 150px minmax(0, 1fr) 180px;
  align-items: start;
  gap: 16px;
  padding: 15px;
}

.assessment-grid.flex-view .card-top {
  flex-direction: column;
  align-items: flex-start;
  justify-content: flex-start;
  gap: 10px;
}

.assessment-grid.flex-view .card-body {
  min-width: 0;
  margin-top: 0;
}

.assessment-grid.flex-view .card-footer {
  flex-direction: column;
  align-items: stretch;
  justify-content: space-between;
  align-self: stretch;
  gap: 12px;
  margin-top: 0;
  padding: 0 0 0 14px;
  border-top: 0;
  border-left: 1px solid var(--gray-200);
}

.assessment-grid.flex-view .record-note {
  flex: 0 0 auto;
}

.assessment-grid.flex-view .start-btn {
  width: 100%;
}

.assessment-grid.flex-view .planned-label {
  text-align: center;
}

.assessment-page.is-inactive .coc-label,
.assessment-page.is-inactive .hero-icon,
.assessment-page.is-inactive .status-pill.ready,
.assessment-page.is-inactive .start-btn {
  background: var(--coc-accent);
}

.assessment-page.is-inactive .coc-hero {
  border-top-color: var(--coc-accent);
}

/* STATES */

.state-card,
.empty-state {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 18px;
  border: 1px solid var(--gray-300);
  border-radius: 14px;
  background: var(--white);
}

.state-copy p,
.empty-state p {
  margin-top: 3px;
  color: var(--gray-700);
  font-size: 11.5px;
  line-height: 1.5;
}

.error-state {
  flex-wrap: wrap;
}

.error-state .state-copy {
  flex: 1 1 220px;
  min-width: 0;
}

.state-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-left: auto;
}

.state-icon,
.empty-icon {
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border-radius: 10px;
  background: var(--gray-100);
  font-size: 15px;
}

.state-icon.tone-danger {
  border: 1px solid #f3c4c0;
  background: #fff1f0;
  color: #b42318;
}

.empty-state {
  flex-direction: column;
  justify-content: center;
  min-height: 190px;
  text-align: center;
}

.empty-state strong {
  font-size: 13px;
}

/* SKELETON */

.sk {
  display: block;
  border-radius: 6px;
  background: linear-gradient(90deg, #efefef 25%, #f8f8f8 37%, #efefef 63%);
  background-size: 400% 100%;
  animation: shimmer 1.4s ease infinite;
}

.skeleton-hero .sk-hero-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
}

.sk-hero-copy {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 0;
}

.sk-readout {
  width: 60px;
  height: 36px;
}

.sk-line {
  height: 11px;
}

.sk-title {
  width: 70%;
  height: 20px;
}

.sk-title-sm {
  height: 16px;
}

.sk-w-40 {
  width: 40%;
}

.sk-w-80 {
  width: 80%;
}

.sk-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.sk-icon-sm {
  width: 36px;
  height: 36px;
  border-radius: 10px;
}

.sk-pill {
  width: 60px;
  height: 20px;
  border-radius: 999px;
}

.sk-preview {
  height: 40px;
  border-radius: 9px;
}

.sk-footer {
  height: 32px;
  margin-top: 6px;
  border-radius: 8px;
}

.skeleton-card {
  gap: 10px;
}

@keyframes shimmer {
  0% {
    background-position: 100% 0;
  }

  100% {
    background-position: -100% 0;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* TABLET */

@media (max-width: 900px) {
  .coc-hero {
    grid-template-columns: auto minmax(0, 1fr);
  }

  .hero-readout {
    grid-column: 1 / -1;
    padding: 10px 0 0;
    border-top: 1px solid var(--gray-200);
    border-left: 0;
    text-align: left;
  }

  .hero-readout strong {
    display: inline;
    font-size: 20px;
  }

  .hero-readout span {
    display: inline;
    margin: 0 0 0 6px;
  }

  .section-heading {
    align-items: flex-start;
  }

  .assessment-grid.flex-view .assessment-card {
    grid-template-columns: 110px minmax(0, 1fr);
  }

  .assessment-grid.flex-view .card-footer {
    grid-column: 1 / -1;
    flex-direction: row;
    align-items: center;
    padding: 10px 0 0;
    border-top: 1px solid var(--gray-200);
    border-left: 0;
  }

  .assessment-grid.flex-view .start-btn {
    width: auto;
  }
}

/* PHONE */

@media (max-width: 600px) {
  .assessment-page {
    padding: 12px 10px 20px;
    font-size: 12px;
  }

  .back-btn {
    font-size: 11px;
  }

  .coc-hero {
    grid-template-columns: minmax(0, 1fr);
    gap: 12px;
    padding: 14px;
    border-radius: 14px;
  }

  .skeleton-hero {
    grid-template-columns: auto minmax(0, 1fr);
  }

  .hero-icon {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    font-size: 16px;
  }

  .coc-hero h1 {
    font-size: 18px;
  }

  .hero-description {
    font-size: 11px;
  }

  .hero-meta span {
    font-size: 9px;
    padding: 4px 6px;
  }

  .hero-readout {
    padding-top: 10px;
  }

  .assessment-section {
    margin-top: 16px;
  }

  .section-heading {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .section-heading h2 {
    font-size: 16px;
  }

  .section-heading p {
    font-size: 10.5px;
  }

  .section-tools {
    justify-content: space-between;
  }

  .section-count {
    font-size: 9px;
  }

  .assessment-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
  }

  .assessment-card {
    padding: 11px;
    border-radius: 12px;
  }

  .type-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    font-size: 12px;
  }

  .status-pill {
    padding: 3px 5px;
    font-size: 7.5px;
    gap: 3px;
  }

  .card-body {
    margin-top: 10px;
  }

  .assessment-card h3 {
    font-size: 13px;
    margin: 3px 0 4px;
  }

  .type-label {
    font-size: 8px;
  }

  .card-description {
    font-size: 10px;
    line-height: 1.45;
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
    line-clamp: 3;
  }

  .tags {
    gap: 4px;
    margin: 10px 0;
  }

  .tag {
    padding: 3px 5px;
    font-size: 8.5px;
  }

  .meta-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 4px;
    margin-top: 8px;
  }

  .meta-item {
    padding: 5px;
    gap: 5px;
    border-radius: 6px;
  }

  .meta-item>i {
    width: 11px;
    font-size: 9px;
  }

  .meta-item dt {
    font-size: 7px;
  }

  .meta-item dd {
    font-size: 9px;
  }

  .focus-block {
    margin-top: 8px;
  }

  .focus-label {
    font-size: 7.5px;
    margin-bottom: 4px;
  }

  .focus-chip {
    padding: 3px 5px;
    font-size: 8px;
  }

  .card-footer {
    flex-direction: column;
    align-items: stretch;
    gap: 7px;
    margin-top: 10px;
    padding-top: 9px;
  }

  .record-note {
    flex: none;
    font-size: 8px;
  }

  .start-btn {
    width: 100%;
    min-height: 30px;
    font-size: 10px;
  }

  .planned-label {
    text-align: center;
    font-size: 8px;
  }

  .assessment-grid.flex-view .assessment-card {
    grid-template-columns: minmax(0, 1fr);
    gap: 10px;
    padding: 12px;
  }

  .assessment-grid.flex-view .card-top {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  .assessment-grid.flex-view .card-body {
    margin-top: 0;
  }

  .assessment-grid.flex-view .card-footer {
    flex-direction: column;
    align-items: stretch;
    padding: 10px 0 0;
    border-top: 1px solid var(--gray-200);
    border-left: 0;
  }

  .assessment-grid.flex-view .start-btn {
    width: 100%;
  }

  .state-card,
  .empty-state {
    padding: 14px;
  }

  .state-actions {
    width: 100%;
    margin-left: 0;
  }

  .state-actions .secondary-btn {
    flex: 1 1 140px;
  }

  .notice {
    font-size: 11px;
  }
}

/* VERY SMALL PHONES */

@media (max-width: 360px) {
  .assessment-grid {
    gap: 6px;
  }

  .assessment-card {
    padding: 9px;
  }

  .meta-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .assessment-card h3 {
    font-size: 12px;
  }

  .view-btn span {
    display: none;
  }

  .view-btn {
    width: 32px;
    padding: 0;
  }
}

@media (prefers-reduced-motion: reduce) {

  .assessment-card,
  .view-btn,
  .sk {
    transition: none;
    animation: none;
  }
}
</style>