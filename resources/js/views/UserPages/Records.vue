<template>
  <main class="records-page">
    <!-- HEADER -->
    <header class="page-header">
      <div>
        <span class="eyebrow">YOUR LEARNING JOURNEY</span>
        <h1>My Records</h1>
        <p>Track your assessment progress and results.</p>
      </div>

      <button class="refresh-button" type="button" :disabled="status === 'loading' || !isOnline"
        aria-label="Refresh records" @click="fetchRecords">
        <i class="fas fa-rotate-right" :class="{ spinning: status === 'loading' }" aria-hidden="true"></i>
        <span>Refresh</span>
      </button>
    </header>

    <!-- OFFLINE NOTICE -->
    <div v-if="!isOnline" class="notice notice-warn" role="status">
      <i class="fas fa-wifi" aria-hidden="true"></i>
      <span>You're offline. Your records will load again when the connection returns.</span>
    </div>

    <!-- SLOW NETWORK HINT -->
    <p v-if="status === 'loading' && slowNetwork" class="slow-note" role="status">
      <i class="fas fa-hourglass-half" aria-hidden="true"></i>
      Still loading. Your connection may be slow.
    </p>

    <!-- PAGE ERROR (session, permission, offline, network, server) -->
    <section v-if="status === 'error'" class="state-panel" role="alert">
      <span class="state-icon" :class="`tone-${currentError.tone}`" aria-hidden="true">
        <i :class="currentError.icon"></i>
      </span>

      <h3>{{ currentError.title }}</h3>
      <p>{{ currentError.text }}</p>

      <div class="state-actions">
        <button v-if="currentError.retry" class="primary-button" type="button" :disabled="!isOnline"
          @click="fetchRecords">
          <i class="fas fa-rotate-right" aria-hidden="true"></i>
          Try again
        </button>

        <button v-if="currentError.signIn" class="primary-button" type="button" @click="goToLogin">
          <i class="fas fa-right-to-bracket" aria-hidden="true"></i>
          Sign in again
        </button>
      </div>
    </section>

    <template v-else>
      <!-- OVERVIEW -->
      <section v-if="status === 'loading'" class="overview" role="status" aria-live="polite">
        <span class="sr-only">Loading your records</span>

        <div v-for="n in 3" :key="n" class="stat-card skeleton-stat" aria-hidden="true">
          <span class="sk sk-icon"></span>
          <div class="skeleton-stat-copy">
            <span class="sk sk-line sk-w-50"></span>
            <span class="sk sk-value"></span>
          </div>
        </div>
      </section>

      <section v-else class="overview" aria-label="Assessment overview">
        <article class="stat-card">
          <span class="stat-icon"><i class="fas fa-book-open" aria-hidden="true"></i></span>
          <div>
            <span class="stat-label">Assessments</span>
            <strong>{{ latestRecords.length }}</strong>
            <small>Unique assessments</small>
          </div>
        </article>

        <article class="stat-card">
          <span class="stat-icon"><i class="fas fa-check" aria-hidden="true"></i></span>
          <div>
            <span class="stat-label">Passed</span>
            <strong>{{ passedCount }}</strong>
            <small>Latest results</small>
          </div>
        </article>

        <article class="stat-card">
          <span class="stat-icon"><i class="fas fa-chart-simple" aria-hidden="true"></i></span>
          <div>
            <span class="stat-label">Average score</span>
            <strong>{{ averageScore }}%</strong>
            <small>Across your latest results</small>
          </div>
        </article>
      </section>

      <!-- ASSESSMENT LIST -->
      <section class="records-section">
        <div class="section-heading">
          <div>
            <h2>Your assessments</h2>
            <p>One latest result per assessment.</p>
          </div>

          <span v-if="status === 'ready'" class="result-count">
            {{ filteredRecords.length }}
            {{ filteredRecords.length === 1 ? 'assessment' : 'assessments' }}
          </span>
        </div>

        <div class="toolbar">
          <label class="search-box">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input v-model="searchQuery" type="search" placeholder="Search assessments..."
              aria-label="Search assessments" :disabled="status !== 'ready'" />
            <button v-if="searchQuery" type="button" class="clear-search" aria-label="Clear search"
              @click="searchQuery = ''">
              <i class="fas fa-times" aria-hidden="true"></i>
            </button>
          </label>

          <select v-model="statusFilter" class="status-filter" aria-label="Filter by result"
            :disabled="status !== 'ready'">
            <option value="all">All results</option>
            <option value="passed">Passed</option>
            <option value="failed">Failed</option>
          </select>
        </div>

        <!-- LOADING: skeleton cards -->
        <div v-if="status === 'loading'" class="assessment-list" aria-hidden="true">
          <div v-for="n in 3" :key="n" class="assessment-card skeleton-card">
            <div class="card-main">
              <span class="sk sk-symbol"></span>
              <div class="skeleton-card-copy">
                <span class="sk sk-line sk-w-60"></span>
                <span class="sk sk-line sk-w-40"></span>
                <span class="sk sk-line sk-w-80"></span>
              </div>
            </div>
            <div class="skeleton-card-result">
              <span class="sk sk-line sk-w-40"></span>
              <span class="sk sk-value"></span>
              <span class="sk sk-bar"></span>
            </div>
          </div>
        </div>

        <!-- LIST -->
        <div v-else-if="filteredRecords.length" class="assessment-list">
          <article v-for="record in filteredRecords" :key="record._groupKey" class="assessment-card">
            <div class="card-main">
              <div class="assessment-symbol" aria-hidden="true">
                <i :class="assessmentIcon(record.quiz_type)"></i>
              </div>

              <div class="assessment-info">
                <div class="assessment-heading">
                  <h3>{{ record.quiz_title || 'Assessment' }}</h3>

                  <span class="status" :class="statusClass(record)">
                    <i :class="isPassed(record) ? 'fas fa-check-circle' : 'fas fa-circle-xmark'"
                      aria-hidden="true"></i>
                    {{ getStatus(record) }}
                  </span>
                </div>

                <div class="assessment-meta">
                  <span v-if="record.coc_number">{{ formatCoc(record.coc_number) }}</span>
                  <span v-if="record.quiz_type">{{ formatLabel(record.quiz_type) }}</span>
                  <span v-if="record.category">{{ record.category }}</span>
                </div>

                <p class="activity-date">
                  <i class="far fa-clock" aria-hidden="true"></i>
                  Last activity · {{ formatDate(record.completed_at || record.created_at) }}
                </p>
              </div>
            </div>

            <div class="card-result">
              <div class="score-info">
                <span>Latest score</span>
                <strong>{{ formatPercentage(record.percentage) }}</strong>
                <small>{{ displayScore(record) }}</small>
              </div>

              <div class="score-track" role="progressbar" :aria-valuenow="clampPercentage(record.percentage)"
                aria-valuemin="0" aria-valuemax="100" :aria-label="`Score: ${formatPercentage(record.percentage)}`">
                <span class="score-fill" :class="statusClass(record)"
                  :style="{ width: `${clampPercentage(record.percentage)}%` }"></span>
              </div>

              <button class="details-button" type="button" @click="openView(record)">
                View details
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
              </button>
            </div>
          </article>
        </div>

        <!-- NO RECORDS YET -->
        <div v-else-if="!latestRecords.length" class="state-panel">
          <span class="state-icon" aria-hidden="true"><i class="fas fa-folder-open"></i></span>
          <h3>Your journey starts here</h3>
          <p>Complete an assessment and your latest result will appear here.</p>
        </div>

        <!-- NO SEARCH RESULT -->
        <div v-else class="state-panel">
          <span class="state-icon" aria-hidden="true"><i class="fas fa-magnifying-glass"></i></span>
          <h3>No matching assessments</h3>
          <p>Try another search or change your result filter.</p>
          <button class="secondary-button" type="button" @click="clearFilters">
            Clear filters
          </button>
        </div>
      </section>
    </template>

    <RecordModal v-model="showModal" :record="selectedRecord" />
  </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/bootstrap'
import { useUser } from '@/composables/useUser'
import RecordModal from '@/components/UserSide/RecordModal.vue'

const router = useRouter()
const { user, fetchUser } = useUser()

const DEFAULT_PASSING_SCORE = 75
const SLOW_AFTER_MS = 4000

/* =========================================================
   ERROR STATES
========================================================= */

const ERROR_STATES = {
  offline: {
    icon: 'fas fa-wifi',
    title: "You're offline",
    text: 'Check your internet connection. Your records will load automatically once you are back online.',
    tone: 'danger',
    retry: true,
  },
  network: {
    icon: 'fas fa-plug-circle-xmark',
    title: "Can't reach the server",
    text: 'The connection failed. Check your network and try again.',
    tone: 'danger',
    retry: true,
  },
  timeout: {
    icon: 'fas fa-hourglass-half',
    title: 'The request took too long',
    text: 'Your network may be slow right now. Try again in a moment.',
    tone: 'danger',
    retry: true,
  },
  server: {
    icon: 'fas fa-triangle-exclamation',
    title: "Couldn't load your records",
    text: 'The server could not load your results. Please try again shortly.',
    tone: 'danger',
    retry: true,
  },
  permission: {
    icon: 'fas fa-lock',
    title: 'Access denied',
    text: 'Your account does not have permission to view these records. Contact your instructor if this is a mistake.',
    tone: 'neutral',
    retry: false,
  },
  session: {
    icon: 'fas fa-user-clock',
    title: 'Your session has expired',
    text: 'For your security, please sign in again to see your records.',
    tone: 'neutral',
    retry: false,
    signIn: true,
  },
}

/* =========================================================
   STATE
========================================================= */

// status: loading | ready | error
const status = ref('loading')
const errorType = ref('server')
const slowNetwork = ref(false)
const isOnline = ref(navigator.onLine)

const records = ref([])
const searchQuery = ref('')
const statusFilter = ref('all')
const selectedRecord = ref(null)
const showModal = ref(false)

let requestId = 0
let slowTimer = null

const currentError = computed(() => ERROR_STATES[errorType.value] ?? ERROR_STATES.server)

/* =========================================================
   ERROR CLASSIFICATION
========================================================= */

function classifyError(error) {
  const code = error?.response?.status

  if (error?.isInvalidResponse) return 'server'
  if (code === 401 || code === 419) return 'session'
  if (code === 403) return 'permission'
  if (code >= 500) return 'server'
  if (error?.response) return 'server'
  if (error?.code === 'ECONNABORTED' || error?.code === 'ETIMEDOUT') return 'timeout'
  if (!navigator.onLine) return 'offline'

  return 'network'
}

function showFailure(type) {
  records.value = []
  errorType.value = type
  status.value = 'error'
}

/* =========================================================
   HELPERS
========================================================= */

function getTimestamp(record) {
  const date = record.completed_at || record.created_at
  if (!date) return 0

  const timestamp = new Date(date).getTime()
  return Number.isFinite(timestamp) ? timestamp : 0
}

function toNumberOrNull(value) {
  if (value === null || value === undefined || value === '') return null

  const number = Number(value)
  return Number.isFinite(number) ? number : null
}

function normalizeRecord(record) {
  return {
    ...record,
    score: toNumberOrNull(record.score),
    correct_answers: toNumberOrNull(record.correct_answers),
    total_questions: toNumberOrNull(record.total_questions),
    percentage: toNumberOrNull(record.percentage),
    passing_score: toNumberOrNull(record.passing_score) ?? DEFAULT_PASSING_SCORE,
    elapsed_time: toNumberOrNull(record.elapsed_time),
    questions: Array.isArray(record.questions) ? record.questions : [],
  }
}

function clampPercentage(value) {
  const number = Number(value)
  if (!Number.isFinite(number)) return 0

  return Math.min(100, Math.max(0, number))
}

function formatPercentage(value) {
  const number = Number(value)
  if (!Number.isFinite(number)) return '0%'

  return `${Number.isInteger(number) ? number : number.toFixed(1)}%`
}

function formatDate(date) {
  if (!date) return '—'

  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return '—'

  return parsed.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

function formatLabel(value) {
  if (!value) return '—'

  return String(value)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

function formatCoc(value) {
  if (value === null || value === undefined || value === '') return ''
  return `COC ${value}`
}

function displayScore(record) {
  const score = record.correct_answers ?? record.score
  const total = record.total_questions

  if (score !== null && total !== null && total > 0) {
    return `${score} / ${total}`
  }

  if (score !== null) return `Score: ${score}`

  return 'Score unavailable'
}

/* =========================================================
   RESULTS
========================================================= */

// Show only the newest record for each quiz. Older attempts stay saved.
const latestRecords = computed(() => {
  const grouped = new Map()

  for (const record of records.value) {
    const key = record.quiz_id !== null && record.quiz_id !== undefined
      ? `quiz-${record.quiz_id}`
      : `record-${record.id}`

    const current = grouped.get(key)

    if (!current || getTimestamp(record) >= getTimestamp(current)) {
      grouped.set(key, { ...record, _groupKey: key })
    }
  }

  return [...grouped.values()].sort((a, b) => getTimestamp(b) - getTimestamp(a))
})

function isPassed(record) {
  const status = String(record.status || '').toLowerCase()

  if (status.includes('fail')) return false
  if (status.includes('pass')) return true

  return Number(record.percentage ?? 0) >= Number(record.passing_score ?? DEFAULT_PASSING_SCORE)
}

const passedCount = computed(() => latestRecords.value.filter(isPassed).length)

const averageScore = computed(() => {
  const scored = latestRecords.value.filter((record) => record.percentage !== null)

  if (!scored.length) return 0

  const total = scored.reduce((sum, record) => sum + clampPercentage(record.percentage), 0)

  return Math.round(total / scored.length)
})

const filteredRecords = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  return latestRecords.value.filter((record) => {
    const searchable = [
      record.quiz_title,
      record.quiz_description,
      record.quiz_type,
      record.category,
      record.coc_number,
    ].filter(Boolean).join(' ').toLowerCase()

    const matchesSearch = !query || searchable.includes(query)

    const matchesStatus =
      statusFilter.value === 'all' ||
      (statusFilter.value === 'passed' && isPassed(record)) ||
      (statusFilter.value === 'failed' && !isPassed(record))

    return matchesSearch && matchesStatus
  })
})

function getStatus(record) {
  return isPassed(record) ? 'Passed' : 'Needs improvement'
}

function statusClass(record) {
  return isPassed(record) ? 'is-passed' : 'is-failed'
}

function assessmentIcon(type) {
  const value = String(type || '').toLowerCase()

  if (value.includes('rj45')) return 'fas fa-network-wired'
  if (value.includes('hardware')) return 'fas fa-screwdriver-wrench'
  if (value.includes('image')) return 'fas fa-image'
  if (value.includes('multiple')) return 'fas fa-list-check'

  return 'fas fa-file-lines'
}

function clearFilters() {
  searchQuery.value = ''
  statusFilter.value = 'all'
}

function openView(record) {
  selectedRecord.value = { ...record }
  showModal.value = true
}

/* =========================================================
   LOADING
========================================================= */

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

async function fetchRecords() {
  const id = ++requestId

  if (!navigator.onLine) {
    isOnline.value = false
    showFailure('offline')
    return
  }

  isOnline.value = true
  status.value = 'loading'
  startSlowTimer()

  try {
    const { data } = await api.get('/api/records')

    if (id !== requestId) return

    if (!Array.isArray(data?.results)) {
      const invalid = new Error('Invalid records response.')
      invalid.isInvalidResponse = true
      throw invalid
    }

    records.value = data.results.map(normalizeRecord)
    status.value = 'ready'
  } catch (error) {
    if (id !== requestId) return

    console.error('Failed to fetch records:', error.response?.data || error)
    showFailure(classifyError(error))
  } finally {
    if (id === requestId) stopSlowTimer()
  }
}

function goToLogin() {
  router.push('/login')
}

/* =========================================================
   CONNECTION EVENTS
========================================================= */

function handleOnline() {
  isOnline.value = true

  if (status.value === 'error' && ['offline', 'network', 'timeout'].includes(errorType.value)) {
    fetchRecords()
  }
}

function handleOffline() {
  isOnline.value = false
}

/* =========================================================
   LIFECYCLE
========================================================= */

onMounted(async () => {
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)

  try {
    if (!user.value) await fetchUser()
  } catch (error) {
    showFailure(classifyError(error))
    return
  }

  if (!user.value) {
    showFailure('session')
    return
  }

  await fetchRecords()
})

onBeforeUnmount(() => {
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
  clearTimeout(slowTimer)
})
</script>

<style scoped>
.records-page {
  --ink: #171717;
  --muted: #777;
  --soft-muted: #999;
  --line: #eaeaea;
  --surface: #fafafa;
  --white: #fff;

  /* Centered layout with a maximum width */
  width: 100%;
  max-width: 68rem;
  min-width: 0;
  margin-inline: auto;
  padding: clamp(1rem, 2.5vw, 1.75rem) clamp(0.75rem, 2vw, 1.5rem) 3rem;

  display: flex;
  flex-direction: column;
  gap: 1.5rem;

  color: var(--ink);
  background: transparent;
  font-family: inherit;
  box-sizing: border-box;
}

.records-page *,
.records-page *::before,
.records-page *::after {
  box-sizing: border-box;
}

.records-page button,
.records-page input,
.records-page select {
  font: inherit;
}

.records-page button {
  -webkit-tap-highlight-color: transparent;
  cursor: pointer;
}

.records-page button:disabled {
  cursor: not-allowed;
  opacity: 0.65;
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

/* ================= HEADER ================= */

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  min-width: 0;
}

.page-header>div {
  min-width: 0;
}

.eyebrow {
  color: var(--muted);
  font-size: 0.67rem;
  font-weight: 700;
  letter-spacing: 0.13em;
}

.page-header h1 {
  margin: 0.35rem 0 0;
  font-size: clamp(1.45rem, 1.2rem + 0.8vw, 1.9rem);
  line-height: 1.2;
  letter-spacing: -0.045em;
  font-weight: 750;
  overflow-wrap: anywhere;
}

.page-header p,
.section-heading p {
  margin: 0.4rem 0 0;
  color: var(--muted);
  font-size: 0.85rem;
  line-height: 1.5;
}

.refresh-button,
.primary-button,
.secondary-button {
  min-height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  padding: 0.65rem 0.9rem;
  border-radius: 0.6rem;
  transition: background 0.18s ease, border-color 0.18s ease;
}

.refresh-button {
  flex-shrink: 0;
  border: 1px solid var(--line);
  background: var(--white);
  color: var(--ink);
  font-size: 0.8rem;
  font-weight: 600;
}

.refresh-button:hover,
.secondary-button:hover {
  background: #f3f3f3;
}

.spinning {
  animation: spin 0.8s linear infinite;
}

/* ================= NOTICES AND SLOW NETWORK ================= */

.notice {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.6rem;
  padding: 0.7rem 0.9rem;
  border: 1px solid var(--line);
  border-radius: 0.8rem;
  background: var(--white);
  font-size: 0.8rem;
}

.notice i {
  color: var(--muted);
}

.notice span {
  flex: 1 1 200px;
  min-width: 0;
}

.notice-warn {
  border-color: #e7c76a;
  background: #fffaf0;
}

.slow-note {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  color: var(--muted);
  font-size: 0.8rem;
}

/* ================= OVERVIEW ================= */

.overview {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.85rem;
  min-width: 0;
}

.stat-card {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 1rem;
  border: 1px solid var(--line);
  border-radius: 0.9rem;
  background: transparent;
}

.stat-icon,
.assessment-symbol,
.state-icon {
  flex-shrink: 0;
  display: grid;
  place-items: center;
  background: var(--surface);
  color: var(--ink);
  border: 1px solid var(--line);
  border-radius: 0.7rem;
}

.stat-icon {
  width: 42px;
  height: 42px;
  font-size: 0.95rem;
}

.stat-card>div {
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.12rem;
}

.stat-label {
  color: var(--muted);
  font-size: 0.76rem;
  font-weight: 500;
}

.stat-card strong {
  font-size: 1.55rem;
  line-height: 1.25;
  letter-spacing: -0.04em;
  font-weight: 750;
}

.stat-card small {
  color: var(--soft-muted);
  font-size: 0.68rem;
  line-height: 1.4;
  overflow-wrap: anywhere;
}

.skeleton-stat {
  align-items: center;
}

.skeleton-stat-copy {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* ================= ASSESSMENT SECTION ================= */

.records-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  min-width: 0;
  width: 100%;
}

.section-heading {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  min-width: 0;
}

.section-heading>div {
  min-width: 0;
}

.section-heading h2 {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 750;
  letter-spacing: -0.025em;
}

.section-heading p {
  font-size: 0.78rem;
}

.result-count {
  flex-shrink: 0;
  padding: 0.35rem 0.6rem;
  border: 1px solid var(--line);
  border-radius: 999px;
  color: var(--muted);
  font-size: 0.7rem;
  font-weight: 600;
  white-space: nowrap;
}

/* ================= SEARCH AND FILTER ================= */

.toolbar {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 0.65rem;
  width: 100%;
  min-width: 0;
}

.search-box {
  min-width: 0;
  height: 43px;
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0 0.85rem;
  border: 1px solid var(--line);
  border-radius: 0.7rem;
  background: transparent;
  color: var(--soft-muted);
  transition: border-color 0.18s ease;
}

.search-box:focus-within {
  border-color: #777;
}

.search-box input {
  width: 100%;
  min-width: 0;
  height: 100%;
  padding: 0;
  border: 0;
  outline: none;
  background: transparent;
  color: var(--ink);
  font-size: 0.82rem;
}

.search-box input:disabled {
  cursor: not-allowed;
}

.search-box input::placeholder {
  color: #a0a0a0;
}

.clear-search {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 28px;
  height: 28px;
  border: 0;
  border-radius: 0.4rem;
  background: transparent;
  color: var(--muted);
}

.clear-search:hover {
  background: var(--surface);
}

.status-filter {
  width: auto;
  min-width: 135px;
  max-width: 100%;
  height: 43px;
  padding: 0 0.8rem;
  border: 1px solid var(--line);
  border-radius: 0.7rem;
  outline: none;
  background: transparent;
  color: var(--ink);
  font-size: 0.8rem;
}

.status-filter:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ================= ASSESSMENT CARDS ================= */

.assessment-list {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  min-width: 0;
  width: 100%;
}

.assessment-card {
  min-width: 0;
  width: 100%;
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(130px, 170px);
  align-items: center;
  gap: 1.25rem;
  padding: 1.1rem;
  border: 1px solid var(--line);
  border-radius: 0.9rem;
  background: transparent;
  transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.assessment-card:hover {
  border-color: #d4d4d4;
  box-shadow: 0 5px 20px rgb(0 0 0 / 3%);
}

.skeleton-card:hover {
  border-color: var(--line);
  box-shadow: none;
}

.card-main {
  min-width: 0;
  display: flex;
  align-items: flex-start;
  gap: 0.9rem;
}

.assessment-symbol {
  width: 44px;
  height: 44px;
  font-size: 1rem;
}

.assessment-info {
  min-width: 0;
  flex: 1;
}

.assessment-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.7rem;
  min-width: 0;
}

.assessment-heading h3 {
  min-width: 0;
  margin: 0;
  overflow-wrap: anywhere;
  font-size: 0.9rem;
  line-height: 1.5;
  font-weight: 700;
}

.status {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.32rem 0.55rem;
  border: 1px solid var(--line);
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 650;
  white-space: nowrap;
}

.is-passed {
  color: #222;
}

.status.is-passed {
  border-color: #dedede;
  background: #f5f5f5;
}

.is-failed {
  color: #666;
}

.status.is-failed {
  background: transparent;
}

.assessment-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-top: 0.45rem;
}

.assessment-meta span {
  color: var(--muted);
  font-size: 0.69rem;
  overflow-wrap: anywhere;
}

.assessment-meta span+span::before {
  content: "·";
  margin-right: 0.4rem;
  color: #b5b5b5;
}

.activity-date {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.4rem;
  margin: 0.7rem 0 0;
  color: var(--soft-muted);
  font-size: 0.72rem;
  overflow-wrap: anywhere;
}

/* ================= SCORE ================= */

.card-result {
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 0.65rem;
}

.score-info {
  min-width: 0;
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.score-info>span {
  width: 100%;
  color: var(--muted);
  font-size: 0.69rem;
}

.score-info strong {
  font-size: 1.4rem;
  line-height: 1.25;
  letter-spacing: -0.04em;
}

.score-info small {
  color: var(--muted);
  font-size: 0.7rem;
  overflow-wrap: anywhere;
}

.score-track {
  width: 100%;
  height: 5px;
  overflow: hidden;
  border-radius: 999px;
  background: #eee;
}

.score-fill {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: #262626;
  transition: width 0.3s ease;
}

.score-fill.is-failed {
  background: #a3a3a3;
}

.details-button {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  width: 100%;
  min-height: 35px;
  padding: 0.45rem 0;
  border: 0;
  background: transparent;
  color: var(--ink);
  font-size: 0.76rem;
  font-weight: 650;
  text-align: left;
}

.details-button i {
  flex-shrink: 0;
  font-size: 0.7rem;
  transition: transform 0.18s ease;
}

.details-button:hover i {
  transform: translateX(3px);
}

/* ================= STATE PANELS (loading, empty, error) ================= */

.state-panel {
  min-width: 0;
  min-height: 230px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  text-align: center;
  border: 1px dashed #dedede;
  border-radius: 0.9rem;
  background: transparent;
}

.state-panel h3 {
  margin: 0.8rem 0 0;
  font-size: 0.95rem;
  font-weight: 700;
  overflow-wrap: anywhere;
}

.state-panel p {
  max-width: 360px;
  margin: 0.45rem 0 0;
  color: var(--muted);
  font-size: 0.8rem;
  line-height: 1.6;
  overflow-wrap: anywhere;
}

.state-icon {
  width: 44px;
  height: 44px;
  font-size: 1rem;
}

.state-icon.tone-danger {
  border-color: #f3c4c0;
  background: #fff1f0;
  color: #b42318;
}

.state-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

.primary-button {
  margin-top: 0.75rem;
  border: 1px solid var(--ink);
  background: var(--ink);
  color: var(--white);
  font-size: 0.78rem;
  font-weight: 600;
}

.primary-button:hover:not(:disabled) {
  background: #333;
}

.primary-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.secondary-button {
  margin-top: 1rem;
  border: 1px solid var(--line);
  background: transparent;
  color: var(--ink);
  font-size: 0.78rem;
  font-weight: 600;
}

/* ================= SKELETON (shimmering placeholders) ================= */

.sk {
  display: block;
  border-radius: 6px;
  background: linear-gradient(90deg, #efefef 25%, #f8f8f8 37%, #efefef 63%);
  background-size: 400% 100%;
  animation: shimmer 1.4s ease infinite;
}

.sk-line {
  height: 0.7rem;
}

.sk-w-40 {
  width: 40%;
}

.sk-w-50 {
  width: 50%;
}

.sk-w-60 {
  width: 60%;
}

.sk-w-80 {
  width: 80%;
}

.sk-icon {
  width: 42px;
  height: 42px;
  border-radius: 0.7rem;
}

.sk-value {
  width: 45%;
  height: 1.4rem;
}

.sk-bar {
  height: 0.3rem;
  border-radius: 999px;
}

.sk-symbol {
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  border-radius: 0.7rem;
}

.skeleton-card-copy {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.skeleton-card-result {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
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

/* ================= TABLET ================= */

@media (max-width: 760px) {
  .records-page {
    gap: 1.35rem;
    padding: 1.25rem 1rem 2.5rem;
  }

  .overview {
    gap: 0.6rem;
  }

  .stat-card {
    align-items: flex-start;
    flex-direction: column;
    gap: 0.65rem;
    padding: 0.85rem;
  }

  .skeleton-stat {
    align-items: flex-start;
  }

  .stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 0.6rem;
  }

  .stat-card strong {
    font-size: 1.35rem;
  }

  .assessment-card {
    grid-template-columns: minmax(0, 1fr);
    gap: 0.95rem;
  }

  .skeleton-card {
    grid-template-columns: minmax(0, 1fr);
  }

  .card-result {
    padding-top: 0.85rem;
    border-top: 1px solid var(--line);
  }

  .score-info>span {
    width: auto;
    margin-right: auto;
  }

  .score-track {
    height: 4px;
  }

  .details-button {
    padding: 0.4rem 0 0;
  }
}

/* ================= MOBILE ================= */

@media (max-width: 480px) {
  .records-page {
    padding: 1rem 0.75rem 2rem;
    gap: 1.2rem;
  }

  .page-header {
    align-items: flex-start;
    gap: 0.65rem;
  }

  .page-header h1 {
    font-size: 1.5rem;
  }

  .page-header p {
    max-width: 230px;
    font-size: 0.77rem;
  }

  .refresh-button {
    width: 39px;
    height: 39px;
    min-height: 39px;
    padding: 0;
  }

  .refresh-button span {
    display: none;
  }

  .overview {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.4rem;
  }

  .stat-card {
    padding: 0.65rem 0.45rem;
    gap: 0.5rem;
    border-radius: 0.7rem;
  }

  .stat-icon {
    width: 30px;
    height: 30px;
    font-size: 0.78rem;
  }

  .stat-label {
    font-size: 0.63rem;
  }

  .stat-card strong {
    font-size: 1.1rem;
  }

  .stat-card small {
    font-size: 0.58rem;
    line-height: 1.35;
  }

  .skeleton-stat {
    padding: 0.65rem 0.45rem;
  }

  .sk-icon {
    width: 30px;
    height: 30px;
  }

  .sk-value {
    height: 1.1rem;
  }

  .section-heading {
    align-items: flex-start;
    gap: 0.5rem;
  }

  .section-heading h2 {
    font-size: 0.98rem;
  }

  .section-heading p {
    font-size: 0.72rem;
  }

  .result-count {
    font-size: 0.63rem;
    padding: 0.3rem 0.45rem;
  }

  .toolbar {
    grid-template-columns: minmax(0, 1fr);
    gap: 0.55rem;
  }

  .search-box,
  .status-filter {
    width: 100%;
    min-width: 0;
    height: 42px;
  }

  .assessment-card {
    padding: 0.85rem;
    border-radius: 0.75rem;
  }

  .card-main {
    gap: 0.7rem;
  }

  .assessment-symbol,
  .sk-symbol {
    width: 38px;
    height: 38px;
    font-size: 0.85rem;
  }

  .assessment-heading {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.45rem;
  }

  .assessment-heading h3 {
    font-size: 0.85rem;
  }

  .activity-date {
    font-size: 0.68rem;
  }

  .score-info strong {
    font-size: 1.3rem;
  }

  .state-panel {
    min-height: 200px;
    padding: 1.5rem 1rem;
  }

  .state-actions {
    width: 100%;
  }

  .state-actions .primary-button {
    flex: 1 1 140px;
  }
}

/* ================= EXTRA SMALL SCREENS ================= */

@media (max-width: 340px) {
  .records-page {
    padding-inline: 0.5rem;
  }

  .overview {
    grid-template-columns: 1fr;
  }

  .stat-card {
    flex-direction: row;
    align-items: center;
    padding: 0.75rem;
  }

  .section-heading {
    flex-direction: column;
    align-items: flex-start;
  }

  .card-main {
    gap: 0.55rem;
  }
}

/* ================= ACCESSIBILITY ================= */

@media (prefers-reduced-motion: reduce) {

  .records-page *,
  .records-page *::before,
  .records-page *::after {
    animation: none !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
</style>