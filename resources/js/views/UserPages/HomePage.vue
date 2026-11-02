<template>
  <div class="home">

    <!-- HEADER -->
    <header class="page-head">
      <div class="head-text">
        <p class="eyebrow">CSS Prep · NC II Readiness</p>
        <h1>{{ greetMessage }}, {{ userFullName || 'Learner' }}</h1>
        <p class="lede">{{ summaryText }}</p>
      </div>

      <div class="head-actions">
        <button type="button" class="btn btn-outline" @click="goToRecords">
          <i class="fas fa-clipboard-list" aria-hidden="true"></i>
          <span>My results</span>
        </button>

        <button type="button" class="btn btn-outline" @click="goToAssessments">
          <i class="fas fa-play" aria-hidden="true"></i>
          <span>Take an assessment</span>
        </button>

        <button type="button" class="btn btn-icon" :disabled="refreshing || !isOnline" aria-label="Refresh"
          title="Refresh" @click="refreshHome">
          <i class="fas fa-rotate" :class="{ spin: refreshing }" aria-hidden="true"></i>
        </button>
      </div>
    </header>

    <!-- OFFLINE NOTICE -->
    <div v-if="!isOnline" class="notice notice-warn" role="status">
      <i class="fas fa-wifi" aria-hidden="true"></i>
      <span>You're offline. Your results will refresh automatically when the connection returns.</span>
    </div>

    <!-- SLOW NETWORK HINT -->
    <p v-if="refreshing && slowNetwork && !hasLoaded" class="slow-note" role="status">
      <i class="fas fa-hourglass-half" aria-hidden="true"></i>
      Still loading. Your connection may be slow.
    </p>

    <!-- PAGE-LEVEL ERROR (session, permission, offline, network, server) -->
    <section v-if="pageError" class="state-panel" role="alert">
      <div class="state-icon" :class="`tone-${currentError.tone}`" aria-hidden="true">
        <i :class="currentError.icon"></i>
      </div>

      <h2>{{ currentError.title }}</h2>
      <p>{{ currentError.text }}</p>

      <div class="state-actions">
        <button v-if="currentError.retry" type="button" class="btn btn-solid btn-sm" :disabled="!isOnline"
          @click="refreshHome">
          <i class="fas fa-rotate" aria-hidden="true"></i>
          Try again
        </button>

        <button v-if="currentError.signIn" type="button" class="btn btn-solid btn-sm" @click="goToLogin">
          <i class="fas fa-right-to-bracket" aria-hidden="true"></i>
          Sign in again
        </button>
      </div>
    </section>

    <template v-else>
      <!-- SECTION ERROR: results -->
      <div v-if="dashboardErrorText" class="alert" role="alert">
        <span>{{ dashboardErrorText }}</span>
        <button type="button" @click="refreshDashboard">Try again</button>
      </div>

      <!-- OVERVIEW -->
      <section v-if="!hasLoaded" class="overview skeleton-overview" role="status" aria-live="polite">
        <span class="sr-only">Loading your results</span>

        <div class="card skeleton-latest" aria-hidden="true">
          <span class="sk sk-line sk-w-40"></span>
          <span class="sk sk-score"></span>
          <span class="sk sk-bar"></span>
          <span class="sk sk-line sk-w-80"></span>
        </div>

        <div class="stats" aria-hidden="true">
          <div v-for="n in 4" :key="n" class="card stat skeleton-stat">
            <span class="sk sk-line sk-w-40"></span>
            <span class="sk sk-value"></span>
            <span class="sk sk-bar"></span>
          </div>
        </div>
      </section>

      <section v-else class="overview">
        <!-- LATEST RESULT -->
        <article class="card latest" :class="latestStatus || 'empty'">
          <template v-if="latest">
            <div class="latest-top">
              <span class="kicker">Latest result</span>
              <span class="verdict" :class="latestStatus">
                <i :class="latestStatus === 'passed' ? 'fas fa-check' : 'fas fa-xmark'" aria-hidden="true"></i>
                {{ latestStatus === 'passed' ? 'Passed' : 'Not passed' }}
              </span>
            </div>

            <p class="latest-score">{{ formatPercentage(latest.percentage) }}</p>

            <div class="meter" role="progressbar" aria-label="Latest result score"
              :aria-valuenow="safePercentage(latest.percentage)" aria-valuemin="0" aria-valuemax="100">
              <span :style="{ width: safePercentage(latest.percentage) + '%' }"></span>
            </div>

            <p class="threshold">
              Passing score: {{ formatPercentage(latest.passing_score ?? PASSING_FALLBACK) }}
            </p>

            <div class="latest-foot">
              <div class="latest-info">
                <strong>{{ latest.quiz_title || 'Assessment' }}</strong>
                <small>
                  {{ metaLine(latest) }}
                  <template v-if="formatDate(latest.completed_at)"> · {{ formatDate(latest.completed_at) }}</template>
                </small>
              </div>

              <button type="button" class="btn btn-solid btn-sm" @click="goToRecords">
                View details
              </button>
            </div>
          </template>

          <div v-else class="empty-state">
            <i class="fas fa-flag-checkered" aria-hidden="true"></i>
            <strong>No results yet</strong>
            <p>Complete an assessment to see your result here.</p>
            <button type="button" class="btn btn-light btn-sm" @click="goToAssessments">
              Start an assessment
            </button>
          </div>
        </article>

        <!-- STATS -->
        <div class="stats">
          <article v-for="stat in stats" :key="stat.label" class="card stat">
            <div class="stat-top">
              <span class="stat-icon"><i :class="stat.icon" aria-hidden="true"></i></span>
              <span class="stat-label">{{ stat.label }}</span>
            </div>

            <strong class="stat-value">{{ stat.value }}</strong>

            <div v-if="stat.bar !== null" class="mini-bar" aria-hidden="true">
              <span :style="{ width: stat.bar + '%' }"></span>
            </div>

            <small class="stat-note">{{ stat.note }}</small>
          </article>
        </div>
      </section>

      <!-- RECENT RESULTS -->
      <section class="card">
        <header class="card-head">
          <div>
            <h2>Recent results</h2>
            <p>Your five latest assessment attempts</p>
          </div>

          <button type="button" class="link" @click="goToRecords">
            View all
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
          </button>
        </header>

        <ul v-if="!hasLoaded" class="list skeleton-list" aria-hidden="true">
          <li v-for="n in 3" :key="n" class="skeleton-row">
            <span class="sk sk-icon-sm"></span>
            <span class="skeleton-row-main">
              <span class="sk sk-line sk-w-60"></span>
              <span class="sk sk-line sk-w-40"></span>
            </span>
            <span class="sk sk-pill"></span>
          </li>
        </ul>

        <ul v-else-if="recentResults.length" class="list">
          <li v-for="record in recentResults" :key="record.id">
            <button type="button" class="row" @click="goToRecords">
              <span class="row-icon" aria-hidden="true">
                <i :class="activityIcon(record.quiz_type)"></i>
              </span>

              <span class="row-main">
                <strong>{{ record.quiz_title || 'Assessment' }}</strong>
                <small>{{ metaLine(record) }} · {{ formatDate(record.completed_at) || '-' }}</small>
              </span>

              <span class="pill" :class="statusClass(record)">
                {{ formatPercentage(record.percentage) }}
              </span>
            </button>
          </li>
        </ul>

        <div v-else class="empty-state small">
          <i class="fas fa-clock-rotate-left" aria-hidden="true"></i>
          <strong>No attempts yet</strong>
          <p>Your completed assessments will be listed here.</p>
        </div>
      </section>

      <!-- ASSESSMENT RANKING -->
      <section class="card ranking-card">
        <header class="card-head">
          <div>
            <h2>Assessment ranking</h2>
            <p>Each learner's best result for the selected assessment</p>
          </div>

          <span v-if="userPosition" class="rank-pill">Your rank: #{{ userPosition }}</span>
        </header>

        <div class="filters">
          <label class="field">
            <span>Competency</span>
            <select v-model="selectedCocId" aria-label="Choose competency" :disabled="!hasLoaded">
              <option v-for="coc in cocOptions" :key="coc.id" :value="String(coc.id)">
                {{ coc.label }}
              </option>
            </select>
          </label>

          <label class="field">
            <span>Assessment</span>
            <select v-model="selectedAssessmentId" aria-label="Choose assessment"
              :disabled="!hasLoaded || !visibleAssessments.length">
              <option v-for="a in visibleAssessments" :key="a.id" :value="String(a.id)">
                {{ a.title }}
              </option>
            </select>
          </label>
        </div>

        <!-- RANKING SECTION ERROR -->
        <div v-if="leaderboardErrorText" class="alert" role="alert">
          <span>{{ leaderboardErrorText }}</span>
          <button type="button" @click="refreshLeaderboard(true)">Try again</button>
        </div>

        <!-- LOADING -->
        <div v-if="!hasLoaded" class="podium skeleton-podium" aria-hidden="true">
          <div v-for="n in 3" :key="n" class="podium-slot">
            <span class="sk sk-avatar"></span>
            <span class="sk sk-line sk-w-60"></span>
            <span class="sk podium-sk-block"></span>
          </div>
        </div>

        <template v-else-if="entries.length">
          <!-- PODIUM -->
          <div class="podium" aria-label="Top three learners">
            <div v-for="slot in podiumSlots" :key="slot.rank" class="podium-slot"
              :class="[`place-${slot.rank}`, { me: slot.entry && isCurrentUser(slot.entry) }]">
              <div class="podium-person">
                <span v-if="slot.entry" class="crown" :class="`crown-${slot.rank}`"
                  :title="`${rankLabel(slot.rank)} place`" aria-hidden="true">
                  <i class="fas fa-crown"></i>
                </span>

                <img v-if="slot.entry" :src="photoPath(slot.entry.profile_photo)" :alt="entryDisplayName(slot.entry)"
                  class="avatar" draggable="false" loading="lazy" @error="handlePhotoError" />
                <span v-else class="avatar placeholder" aria-hidden="true">
                  <i class="fas fa-user"></i>
                </span>

                <strong class="podium-name">
                  {{ slot.entry ? entryDisplayName(slot.entry) : 'Open' }}
                </strong>

                <span v-if="slot.entry && isCurrentUser(slot.entry)" class="you">You</span>

                <span class="podium-score">
                  {{ slot.entry ? formatPercentage(slot.entry.percentage) : '-' }}
                </span>
              </div>

              <div class="podium-block">
                <span class="block-rank">{{ slot.rank }}</span>
                <span class="block-label">{{ rankLabel(slot.rank) }}</span>
              </div>
            </div>
          </div>

          <!-- REST OF THE RANKING -->
          <ol v-if="restOfRanking.length" class="ranking">
            <li v-for="entry in restOfRanking" :key="entry.id" :class="{ me: isCurrentUser(entry) }">
              <span class="rank">{{ entry.rank }}</span>

              <img :src="photoPath(entry.profile_photo)" :alt="entryDisplayName(entry)" class="avatar small"
                draggable="false" loading="lazy" @error="handlePhotoError" />

              <span class="rank-main">
                <strong>
                  {{ entryDisplayName(entry) }}
                  <em v-if="isCurrentUser(entry)" class="you">You</em>
                </strong>
                <span class="mini-bar" aria-hidden="true">
                  <span :style="{ width: safePercentage(entry.percentage) + '%' }"></span>
                </span>
              </span>

              <span class="score">{{ formatPercentage(entry.percentage) }}</span>
            </li>
          </ol>

          <p class="footnote">{{ activeFilterLabel }}</p>
        </template>

        <!-- EMPTY RANKING -->
        <div v-else-if="hasLoaded && !leaderboardErrorText" class="empty-state small">
          <i class="fas fa-trophy" aria-hidden="true"></i>
          <strong>
            {{ cocOptions.length ? 'No results for this assessment yet' : 'No rankings are available yet' }}
          </strong>
          <p>Rankings appear after learners complete an assessment.</p>
        </div>
      </section>
    </template>

  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useUser } from '@/composables/useUser'
import { useGreetMessages } from '@/composables/useGreetMessages'
import { useUserRequests } from '@/composables/useUserRequests'

const router = useRouter()

const { greetMessage } = useGreetMessages()
const { user, userFullName, fetchUser } = useUser()

const {
  dashboard,
  getDashboard,
  leaderboard,
  leaderboardMeta,
  getLeaderBoard,
} = useUserRequests()

const PASSING_FALLBACK = 75
const DEFAULT_PHOTO = '/storage/images/profiles/default.png'
const SLOW_AFTER_MS = 4000

/* =========================================================
   ERROR STATES
========================================================= */

const ERROR_STATES = {
  offline: {
    icon: 'fas fa-wifi',
    title: "You're offline",
    text: 'Check your internet connection. Your results will load automatically once you are back online.',
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
    title: 'Something went wrong',
    text: 'Your dashboard could not be loaded. Please try again shortly.',
    tone: 'danger',
    retry: true,
  },
  permission: {
    icon: 'fas fa-lock',
    title: 'Access denied',
    text: 'Your account does not have permission to view this dashboard. Contact your instructor if this is a mistake.',
    tone: 'neutral',
    retry: false,
  },
  session: {
    icon: 'fas fa-user-clock',
    title: 'Your session has expired',
    text: 'For your security, please sign in again to continue.',
    tone: 'neutral',
    retry: false,
    signIn: true,
  },
}

/* =========================================================
   STATE
========================================================= */

const isOnline = ref(navigator.onLine)
const refreshing = ref(false)
const hasLoaded = ref(false)
const slowNetwork = ref(false)

// Page-level error: replaces the whole body.
const pageError = ref('')

// Section-level errors: only that section shows an alert.
const sectionErrors = reactive({ dashboard: '', leaderboard: '' })

const selectedCocId = ref('')
const selectedAssessmentId = ref('')

let requestId = 0
let slowTimer = null

const currentError = computed(() => ERROR_STATES[pageError.value] ?? ERROR_STATES.server)

const sectionText = (label, type) =>
  type && ERROR_STATES[type] ? `${label}: ${ERROR_STATES[type].title}.` : ''

const dashboardErrorText = computed(() =>
  sectionText('Your results could not be loaded', sectionErrors.dashboard)
)

const leaderboardErrorText = computed(() =>
  sectionText('The ranking could not be loaded', sectionErrors.leaderboard)
)

/* =========================================================
   ERROR CLASSIFICATION AND SLOW NETWORK
========================================================= */

function classifyError(error) {
  const code = error?.response?.status

  if (code === 401 || code === 419) return 'session'
  if (code === 403) return 'permission'
  if (error?.response) return 'server'
  if (error?.code === 'ECONNABORTED' || error?.code === 'ETIMEDOUT') return 'timeout'
  if (!navigator.onLine) return 'offline'

  return 'network'
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
   HELPERS
========================================================= */

const toNumber = (value, fallback = 0) => {
  if (value === null || value === undefined || value === '') return fallback
  const number = Number(value)
  return Number.isFinite(number) ? number : fallback
}

const safePercentage = (value) => Math.min(100, Math.max(0, toNumber(value)))

const formatPercentage = (value) => {
  const percentage = safePercentage(value)
  return `${Number.isInteger(percentage) ? percentage : percentage.toFixed(1)}%`
}

const formatDate = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatLabel = (value) => {
  if (!value) return ''
  return String(value)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

// The server sends `passed` on every result. Text is only a fallback for older data.
const statusClass = (record) => {
  if (typeof record?.passed === 'boolean') return record.passed ? 'passed' : 'failed'

  const status = String(record?.status ?? '').toLowerCase()
  if (status.includes('pass')) return 'passed'
  if (status.includes('fail')) return 'failed'

  return safePercentage(record?.percentage) >= toNumber(record?.passing_score, PASSING_FALLBACK)
    ? 'passed'
    : 'failed'
}

const metaLine = (record) => {
  const parts = []

  if (record?.coc_number !== null && record?.coc_number !== undefined) {
    parts.push(`Competency ${record.coc_number}`)
  }

  parts.push(record?.category || formatLabel(record?.quiz_type) || 'Assessment')

  return parts.join(' · ')
}

const activityIcon = (type) => {
  const value = String(type ?? '').toLowerCase()

  if (value.includes('rj45') || value.includes('network')) return 'fas fa-network-wired'
  if (value.includes('hardware') || value.includes('assemble') || value.includes('drag')) {
    return 'fas fa-screwdriver-wrench'
  }
  if (value.includes('image')) return 'fas fa-image'

  return 'fas fa-file-lines'
}

const photoPath = (image) => {
  if (!image) return DEFAULT_PHOTO

  const value = String(image)
  if (/^https?:\/\//.test(value) || value.startsWith('/storage/')) return value

  return `/storage/images/profiles/${value}`
}

const handlePhotoError = (event) => {
  const image = event?.target
  if (image && !image.src.endsWith(DEFAULT_PHOTO)) image.src = DEFAULT_PHOTO
}

const rankLabel = (rank) => {
  if (rank === 1) return '1st'
  if (rank === 2) return '2nd'
  if (rank === 3) return '3rd'
  return `${rank}th`
}

const entryDisplayName = (entry) => entry?.displayName || entry?.name || 'Learner'

const isCurrentUser = (entry) => {
  return entry?.isYou === true
}

/* =========================================================
   RESULTS AND STATS
========================================================= */

const summary = computed(() => {
  const data = dashboard.value?.summary ?? {}

  return {
    average: toNumber(data.average_score),
    best: toNumber(data.best_score),
    passed: toNumber(data.passed),
    attempts: toNumber(data.total_attempts),
  }
})

const recentResults = computed(() => {
  const items = Array.isArray(dashboard.value?.recent_activity) ? dashboard.value.recent_activity : []

  return [...items]
    .sort((a, b) => new Date(b?.completed_at ?? 0) - new Date(a?.completed_at ?? 0))
    .slice(0, 5)
})

const latest = computed(() => recentResults.value[0] ?? null)
const latestStatus = computed(() => (latest.value ? statusClass(latest.value) : ''))

const summaryText = computed(() => {
  if (!hasLoaded.value) return 'Loading your progress…'

  const { attempts, average } = summary.value

  if (!attempts) return 'Take your first assessment to start tracking your results.'

  return `You have completed ${attempts} ${attempts === 1 ? 'assessment attempt' : 'assessment attempts'} with an average score of ${formatPercentage(average)}.`
})

const passRate = computed(() => {
  const { attempts, passed } = summary.value
  return attempts ? Math.round((passed / attempts) * 100) : 0
})

// Each card has a value, an optional progress bar (null hides it), and a note.
const stats = computed(() => [
  {
    label: 'Average score',
    value: formatPercentage(summary.value.average),
    icon: 'fas fa-chart-line',
    bar: safePercentage(summary.value.average),
    note: 'Across all your attempts',
  },
  {
    label: 'Best score',
    value: formatPercentage(summary.value.best),
    icon: 'fas fa-medal',
    bar: safePercentage(summary.value.best),
    note: 'Your highest result so far',
  },
  {
    label: 'Passed',
    value: summary.value.passed,
    icon: 'fas fa-circle-check',
    bar: passRate.value,
    note: summary.value.attempts ? `${passRate.value}% pass rate` : 'No attempts yet',
  },
  {
    label: 'Attempts',
    value: summary.value.attempts,
    icon: 'fas fa-layer-group',
    bar: null,
    note: latest.value?.completed_at
      ? `Last attempt ${formatDate(latest.value.completed_at)}`
      : 'Your attempts will appear here',
  },
])

/* =========================================================
   ASSESSMENT RANKING
   The server returns the filter options and the ranking.
   The page only displays them.
========================================================= */

const entries = computed(() => (Array.isArray(leaderboard.value) ? leaderboard.value : []))

const cocOptions = computed(() => leaderboardMeta.value?.filter_options?.cocs ?? [])
const assessmentOptions = computed(() => leaderboardMeta.value?.filter_options?.assessments ?? [])

const visibleAssessments = computed(() =>
  assessmentOptions.value.filter((item) => String(item.coc_id) === String(selectedCocId.value))
)

const selectedAssessment = computed(() =>
  assessmentOptions.value.find((item) => String(item.id) === String(selectedAssessmentId.value)) ?? null
)

const activeFilterLabel = computed(() => {
  const coc = cocOptions.value.find((item) => String(item.id) === String(selectedCocId.value))

  return `${coc?.label ?? 'No competency'} · ${selectedAssessment.value?.title ?? 'No assessment selected'}`
})

const userPosition = computed(() => {
  if (!user.value?.id) return null
  return entries.value.find(isCurrentUser)?.rank ?? null
})

// Podium order: 2nd (left), 1st (centre, tallest), 3rd (right).
// Empty places keep their block so the podium holds its shape.
const podiumSlots = computed(() =>
  [2, 1, 3].map((rank) => ({
    rank,
    entry: entries.value.find((entry) => entry.rank === rank) ?? null,
  }))
)

const restOfRanking = computed(() => entries.value.slice(3))

// Keep both dropdowns on valid values after the options arrive.
const syncFilters = () => {
  if (!cocOptions.value.some((item) => String(item.id) === String(selectedCocId.value))) {
    selectedCocId.value = cocOptions.value[0] ? String(cocOptions.value[0].id) : ''
  }

  if (!visibleAssessments.value.some((item) => String(item.id) === String(selectedAssessmentId.value))) {
    selectedAssessmentId.value = visibleAssessments.value[0] ? String(visibleAssessments.value[0].id) : ''
  }
}

// Throws on failure so the caller can classify the error.
const reloadLeaderboard = async (force = false) => {
  await getLeaderBoard({
    limit: 100,
    assessment_id: selectedAssessmentId.value || null,
    force,
  })

  syncFilters()
}

// Used by the section's retry button and when the assessment changes.
const refreshLeaderboard = async (force = false) => {
  try {
    await reloadLeaderboard(force)
    sectionErrors.leaderboard = ''
  } catch (error) {
    console.error('Failed to load assessment ranking:', error)
    sectionErrors.leaderboard = classifyError(error)
  }
}

const refreshDashboard = async () => {
  try {
    await getDashboard(true)
    sectionErrors.dashboard = ''
  } catch (error) {
    console.error('Failed to load dashboard:', error)
    sectionErrors.dashboard = classifyError(error)
  }
}

// Changing the competency selects its first assessment.
watch(selectedCocId, () => {
  if (!visibleAssessments.value.some((item) => String(item.id) === String(selectedAssessmentId.value))) {
    selectedAssessmentId.value = visibleAssessments.value[0] ? String(visibleAssessments.value[0].id) : ''
  }
})

// Changing the assessment loads its ranking.
watch(selectedAssessmentId, (value, previous) => {
  if (value !== previous && hasLoaded.value) refreshLeaderboard(false)
})

/* =========================================================
   LOADING
========================================================= */

const loadHome = async (force = false) => {
  const id = ++requestId

  if (!navigator.onLine) {
    isOnline.value = false
    pageError.value = 'offline'
    hasLoaded.value = true
    return
  }

  refreshing.value = true
  pageError.value = ''
  startSlowTimer()

  try {
    const [dashboardResult, leaderboardResult] = await Promise.allSettled([
      getDashboard(force),
      reloadLeaderboard(force),
    ])

    if (id !== requestId) return

    const rejected = [dashboardResult, leaderboardResult].filter((result) => result.status === 'rejected')
    const failureTypes = rejected.map((result) => classifyError(result.reason))

    // Session and permission problems block the whole page.
    const blocking = failureTypes.find((type) => type === 'session' || type === 'permission')

    if (blocking) {
      pageError.value = blocking
      return
    }

    // Every request failed the same way: show the page-level error.
    if (rejected.length === 2) {
      pageError.value = failureTypes[0]
      return
    }

    sectionErrors.dashboard = dashboardResult.status === 'rejected'
      ? classifyError(dashboardResult.reason)
      : ''

    sectionErrors.leaderboard = leaderboardResult.status === 'rejected'
      ? classifyError(leaderboardResult.reason)
      : ''
  } catch (error) {
    if (id !== requestId) return

    console.error('Failed to open the home page:', error)
    pageError.value = classifyError(error)
  } finally {
    if (id === requestId) {
      refreshing.value = false
      hasLoaded.value = true
      stopSlowTimer()
    }
  }
}

const refreshHome = () => loadHome(true)

/* =========================================================
   NAVIGATION
========================================================= */

const goToAssessments = () => router.push('/user/assessments')
const goToRecords = () => router.push('/user/records')
const goToLogin = () => router.push('/login')

/* =========================================================
   CONNECTION EVENTS
========================================================= */

function handleOnline() {
  isOnline.value = true

  // Reload when the page was waiting for the network or any section failed.
  const needsReload =
    ['offline', 'network', 'timeout'].includes(pageError.value) ||
    Object.values(sectionErrors).some(Boolean) ||
    !hasLoaded.value

  if (needsReload) loadHome(true)
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
    // The data requests below report authentication problems themselves.
    console.warn('Unable to load the user profile:', error)
  }

  await loadHome(false)
})

onBeforeUnmount(() => {
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
  clearTimeout(slowTimer)
})
</script>

<style scoped>
/* =========================================================
   CSS PREP HOME
   Frosted Noir: #FFFFFF #000000 #A9A9A9 #D3D3D3 #696969
   Medal colors are used only for the crowns and avatar rings.
========================================================= */

.home {
  --white: #ffffff;
  --ink: #000000;
  --dim: #696969;
  --silver: #a9a9a9;
  --light: #d3d3d3;
  --line: #ebebeb;
  --soft: #f6f6f6;
  --pass: #11813d;
  --fail: #c81e2e;

  --crown-gold: #c9a227;
  --crown-silver: #9ea7b0;
  --crown-bronze: #b07a4a;

  --radius-card: 16px;
  --radius-inner: 10px;
  --btn-h: 42px;
  --ease-out: cubic-bezier(0.2, 0.7, 0.2, 1);

  width: 100%;
  max-width: 72rem;
  margin: 0 auto;
  padding: clamp(1rem, 2.5vw, 1.75rem) clamp(0.75rem, 2vw, 1.25rem) 3rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  color: var(--ink);
  font-size: 1rem;
  line-height: 1.5;
  min-width: 0;
}

.home *,
.home *::before,
.home *::after {
  box-sizing: border-box;
}

.home button,
.home select {
  font: inherit;
  color: inherit;
}

.home button {
  cursor: pointer;
}

.home button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.home :focus-visible {
  outline: 2px solid var(--ink);
  outline-offset: 3px;
}

.home h1,
.home h2,
.home p,
.home ol,
.home ul {
  margin: 0;
  padding: 0;
}

.home ol,
.home ul {
  list-style: none;
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

/* =========================================================
   ENTRANCE ANIMATIONS
========================================================= */

@keyframes rise {
  from {
    opacity: 0;
    transform: translateY(12px);
  }

  to {
    opacity: 1;
    transform: none;
  }
}

@keyframes grow {
  from {
    width: 0;
  }
}

@keyframes shine {
  0% {
    transform: translateX(-120%);
  }

  60%,
  100% {
    transform: translateX(120%);
  }
}

@keyframes bob {

  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(-3px);
  }
}

@keyframes pulse-dot {
  0% {
    box-shadow: 0 0 0 0 rgba(17, 129, 61, 0.45);
  }

  70% {
    box-shadow: 0 0 0 7px rgba(17, 129, 61, 0);
  }

  100% {
    box-shadow: 0 0 0 0 rgba(17, 129, 61, 0);
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes shimmer {
  0% {
    background-position: 100% 0;
  }

  100% {
    background-position: -100% 0;
  }
}

.page-head,
.card,
.alert,
.notice,
.state-panel {
  animation: rise 0.55s var(--ease-out) both;
}

.card:nth-of-type(2) {
  animation-delay: 0.05s;
}

.card:nth-of-type(3) {
  animation-delay: 0.1s;
}

.card:nth-of-type(4) {
  animation-delay: 0.15s;
}

.meter span,
.mini-bar span {
  animation: grow 1s var(--ease-out) both;
}

/* =========================================================
   HEADER
========================================================= */

.page-head {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
}

.head-text {
  flex: 1 1 20rem;
  min-width: 0;
}

.eyebrow,
.kicker {
  display: block;
  margin-bottom: 0.35rem;
  color: var(--dim);
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.page-head h1 {
  font-size: clamp(1.4rem, 1.1rem + 1vw, 1.9rem);
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1.2;
  overflow-wrap: anywhere;
}

.lede {
  margin-top: 0.4rem;
  color: var(--dim);
  font-size: 0.8125rem;
}

.head-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

/* =========================================================
   NOTICES AND SLOW NETWORK
========================================================= */

.notice {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.6rem;
  padding: 0.7rem 0.9rem;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--white);
  font-size: 0.8125rem;
}

.notice i {
  color: var(--dim);
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
  color: var(--dim);
  font-size: 0.8125rem;
}

/* =========================================================
   PAGE-LEVEL STATE PANEL (session, permission, offline, server)
========================================================= */

.state-panel {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  min-height: 16rem;
  padding: 2rem 1.25rem;
  border: 1px dashed var(--light);
  border-radius: var(--radius-card);
  background: var(--white);
  text-align: center;
}

.state-panel h2 {
  font-size: 1rem;
  font-weight: 800;
}

.state-panel p {
  max-width: 28rem;
  color: var(--dim);
  font-size: 0.8125rem;
  line-height: 1.6;
}

.state-icon {
  width: 3rem;
  height: 3rem;
  display: grid;
  place-items: center;
  margin-bottom: 0.25rem;
  border: 1px solid var(--line);
  border-radius: 14px;
  background: var(--soft);
  font-size: 1.1rem;
}

.state-icon.tone-danger {
  border-color: #f3c4c0;
  background: #fff1f0;
  color: var(--fail);
}

.state-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

/* =========================================================
   BUTTONS
========================================================= */

.btn {
  min-height: var(--btn-h);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0 1.1rem;
  border: 1px solid transparent;
  border-radius: 999px;
  font-size: 0.8125rem;
  font-weight: 650;
  line-height: 1.2;
  text-align: center;
  white-space: nowrap;
  transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.15s ease,
    box-shadow 0.2s ease;
}

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
}

.btn:active:not(:disabled) {
  transform: scale(0.97);
}

.btn i {
  font-size: 0.75rem;
  transition: transform 0.2s ease;
}

.btn:hover:not(:disabled) i {
  transform: translateX(1px);
}

.btn-solid {
  background: var(--ink);
  color: var(--white);
}

.btn-solid:hover:not(:disabled) {
  background: #262626;
}

.btn-outline {
  border-color: var(--light);
  background: var(--white);
  color: var(--ink);
}

.btn-outline:hover:not(:disabled) {
  border-color: var(--ink);
}

.btn-light {
  background: var(--white);
  color: var(--ink);
}

.btn-light:hover:not(:disabled) {
  background: #eeeeee;
}

.btn-sm {
  min-height: 34px;
  padding: 0 0.85rem;
  font-size: 0.75rem;
}

.btn-icon {
  width: var(--btn-h);
  min-width: var(--btn-h);
  padding: 0;
  border-color: var(--light);
  background: var(--white);
  color: var(--dim);
}

.btn-icon:hover:not(:disabled) {
  border-color: var(--ink);
  color: var(--ink);
}

.btn-icon:hover:not(:disabled) i {
  transform: rotate(45deg);
}

.link {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0;
  border: 0;
  background: none;
  color: var(--dim);
  font-size: 0.75rem;
  font-weight: 650;
  transition: color 0.2s ease, gap 0.2s ease;
}

.link:hover {
  color: var(--ink);
  gap: 0.6rem;
}

.spin {
  animation: spin 0.8s linear infinite;
}

/* =========================================================
   SECTION ALERT
========================================================= */

.alert {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border: 1px solid var(--fail);
  border-radius: 12px;
  background: var(--white);
  color: var(--fail);
  font-size: 0.8125rem;
}

.alert button {
  border: 0;
  background: none;
  color: var(--ink);
  font-weight: 700;
  text-decoration: underline;
}

/* =========================================================
   CARDS
========================================================= */

.card {
  min-width: 0;
  padding: 1.25rem;
  border: 1px solid var(--line);
  border-radius: var(--radius-card);
  background: var(--white);
  transition: box-shadow 0.25s ease, border-color 0.25s ease;
}

.card:hover {
  box-shadow: 0 10px 26px rgba(0, 0, 0, 0.05);
}

.card-head {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.card-head h2 {
  font-size: 1rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.card-head p {
  margin-top: 0.2rem;
  color: var(--dim);
  font-size: 0.75rem;
}

/* =========================================================
   OVERVIEW
========================================================= */

.overview {
  display: grid;
  grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
  gap: 0.875rem;
  align-items: stretch;
}

.latest {
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
  background: var(--ink);
  border-color: var(--ink);
  color: var(--white);
}

/* A light sheen crosses the latest-result card every few seconds */
.latest::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(100deg, transparent 30%, rgba(255, 255, 255, 0.12) 50%, transparent 70%);
  transform: translateX(-120%);
  animation: shine 4.5s ease-in-out 1.2s infinite;
  pointer-events: none;
}

.latest.empty {
  background: var(--soft);
  border: 1px dashed var(--light);
  color: var(--dim);
}

.latest.empty::after {
  display: none;
}

.latest-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.latest .kicker {
  margin: 0;
  color: rgba(255, 255, 255, 0.65);
}

.verdict {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.3rem 0.65rem;
  border-radius: 999px;
  font-size: 0.6875rem;
  font-weight: 700;
}

.verdict.passed {
  background: rgba(155, 227, 189, 0.2);
  color: #9be3bd;
}

.verdict.failed {
  background: rgba(255, 179, 187, 0.2);
  color: #ffb3bb;
}

.latest-score {
  font-size: clamp(2.2rem, 2rem + 1vw, 3rem);
  font-weight: 800;
  letter-spacing: -0.05em;
  line-height: 1;
}

.meter {
  height: 0.4rem;
  overflow: hidden;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.16);
}

.meter span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: var(--white);
  transition: width 0.6s ease;
}

.threshold {
  color: rgba(255, 255, 255, 0.65);
  font-size: 0.72rem;
}

.latest-foot {
  margin-top: auto;
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 0.75rem;
  padding-top: 0.9rem;
  border-top: 1px solid rgba(255, 255, 255, 0.14);
}

.latest-info {
  min-width: 0;
}

.latest-info strong {
  display: block;
  font-size: 0.9rem;
  overflow-wrap: anywhere;
}

.latest-info small {
  color: rgba(255, 255, 255, 0.65);
  font-size: 0.72rem;
  overflow-wrap: anywhere;
}

/* STATS */

.stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}

.stat {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  min-height: 9.5rem;
  padding: 1rem;
  transition: transform 0.25s var(--ease-out), border-color 0.25s ease, box-shadow 0.25s ease;
}

.stat:hover {
  transform: translateY(-3px);
  border-color: #cfcfcf;
  box-shadow: 0 12px 26px rgba(0, 0, 0, 0.06);
}

.stat:nth-child(1) {
  animation-delay: 0.05s;
}

.stat:nth-child(2) {
  animation-delay: 0.1s;
}

.stat:nth-child(3) {
  animation-delay: 0.15s;
}

.stat:nth-child(4) {
  animation-delay: 0.2s;
}

.stat-top {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.stat-icon {
  width: 2rem;
  height: 2rem;
  flex: 0 0 2rem;
  display: grid;
  place-items: center;
  border-radius: 0.6rem;
  background: var(--ink);
  color: var(--white);
  font-size: 0.75rem;
  transition: transform 0.3s var(--ease-out);
}

.stat:hover .stat-icon {
  transform: rotate(-8deg) scale(1.08);
}

.stat-label {
  color: var(--dim);
  font-size: 0.72rem;
  font-weight: 650;
}

.stat-value {
  font-size: 1.6rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1.1;
}

.stat-note {
  margin-top: auto;
  color: var(--dim);
  font-size: 0.68rem;
  line-height: 1.4;
}

.mini-bar {
  display: block;
  height: 0.3rem;
  overflow: hidden;
  border-radius: 999px;
  background: var(--line);
}

.mini-bar span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: var(--ink);
  transition: width 0.6s ease;
}

/* =========================================================
   SKELETON (loading placeholders)
========================================================= */

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

.sk-w-60 {
  width: 60%;
}

.sk-w-80 {
  width: 80%;
}

.sk-score {
  width: 40%;
  height: 2.6rem;
  margin: 0.4rem 0;
}

.sk-value {
  width: 55%;
  height: 1.6rem;
}

.sk-bar {
  height: 0.3rem;
  border-radius: 999px;
}

.sk-icon-sm {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 0.625rem;
}

.sk-pill {
  width: 3.5rem;
  height: 1.4rem;
  border-radius: 999px;
}

.sk-avatar {
  width: 2.4rem;
  height: 2.4rem;
  border-radius: 50%;
}

.skeleton-latest,
.skeleton-stat {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.skeleton-stat {
  min-height: 9.5rem;
}

.skeleton-row {
  display: grid;
  grid-template-columns: 2.25rem minmax(0, 1fr) auto;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 0.5rem;
}

.skeleton-row-main {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.podium-sk-block {
  width: 100%;
  height: 4.5rem;
  border-radius: 0.75rem 0.75rem 0 0;
}

.skeleton-podium .podium-slot {
  align-items: center;
  gap: 0.5rem;
}

/* =========================================================
   EMPTY STATES
========================================================= */

.empty-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  min-height: 11rem;
  padding: 1rem;
  text-align: center;
}

.empty-state.small {
  min-height: 8rem;
}

.empty-state>i {
  color: var(--silver);
  font-size: 1.4rem;
  animation: bob 3s ease-in-out infinite;
}

.empty-state strong {
  font-size: 0.875rem;
}

.empty-state p {
  color: var(--dim);
  font-size: 0.75rem;
}

.latest .empty-state strong {
  color: var(--ink);
}

/* =========================================================
   RECENT RESULTS
========================================================= */

.list li+li {
  border-top: 1px solid var(--line);
}

.skeleton-list li+li {
  border-top: 1px solid var(--line);
}

.row {
  width: 100%;
  min-width: 0;
  display: grid;
  grid-template-columns: 2.25rem minmax(0, 1fr) auto;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 0.5rem;
  border: 0;
  border-radius: var(--radius-inner);
  background: transparent;
  text-align: left;
  transition: background 0.2s ease, transform 0.25s var(--ease-out);
}

.row:hover {
  background: var(--soft);
  transform: translateX(4px);
}

.row-icon {
  width: 2.25rem;
  height: 2.25rem;
  display: grid;
  place-items: center;
  border-radius: 0.625rem;
  background: var(--soft);
  font-size: 0.8125rem;
  transition: background 0.2s ease, transform 0.25s var(--ease-out);
}

.row:hover .row-icon {
  background: var(--ink);
  color: var(--white);
  transform: scale(1.06);
}

.row-main {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.row-main strong {
  overflow: hidden;
  font-size: 0.8125rem;
  font-weight: 650;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.row-main small {
  overflow: hidden;
  color: var(--dim);
  font-size: 0.6875rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pill {
  padding: 0.25rem 0.65rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 750;
  white-space: nowrap;
  transition: transform 0.2s var(--ease-out);
}

.row:hover .pill {
  transform: scale(1.06);
}

.pill.passed {
  background: rgba(17, 129, 61, 0.1);
  color: var(--pass);
}

.pill.failed {
  background: rgba(200, 30, 46, 0.08);
  color: var(--fail);
}

/* =========================================================
   ASSESSMENT RANKING
========================================================= */

.rank-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.4rem 0.8rem;
  border-radius: 999px;
  background: var(--ink);
  color: var(--white);
  font-size: 0.72rem;
  font-weight: 700;
  white-space: nowrap;
}

.rank-pill::before {
  content: "";
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #9be3bd;
  animation: pulse-dot 2s ease-out infinite;
}

.filters {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.625rem;
  margin-bottom: 1.25rem;
}

.field {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.field>span {
  color: var(--dim);
  font-size: 0.625rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.field select {
  width: 100%;
  min-width: 0;
  height: var(--btn-h);
  padding: 0 0.875rem;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--soft);
  font-size: 0.8125rem;
  outline: none;
  transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
}

.field select:hover:not(:disabled) {
  border-color: #cfcfcf;
}

.field select:focus {
  border-color: var(--ink);
  background: var(--white);
  box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.06);
}

.field select:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* PODIUM: 1st is tallest and centred, 2nd left, 3rd right */

.podium {
  --block-1: 7.5rem;
  --block-2: 5.5rem;
  --block-3: 4.5rem;

  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  align-items: end;
  gap: 0.6rem;
  max-width: 34rem;
  margin: 0 auto 1rem;
  padding: 1rem 1rem 0;
  border: 1px solid var(--line);
  border-radius: var(--radius-card);
  background: linear-gradient(180deg, var(--soft) 0%, var(--white) 70%);
}

.podium-slot {
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  animation: rise 0.6s var(--ease-out) both;
}

.podium-slot.place-2 {
  animation-delay: 0.15s;
}

.podium-slot.place-1 {
  animation-delay: 0.25s;
}

.podium-slot.place-3 {
  animation-delay: 0.35s;
}

.podium-person {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  width: 100%;
  min-width: 0;
  margin-bottom: 0.5rem;
  text-align: center;
}

.avatar {
  width: 2.4rem;
  height: 2.4rem;
  border-radius: 50%;
  object-fit: cover;
  background: var(--soft);
  border: 2px solid var(--white);
  box-shadow: 0 0 0 1px var(--light);
  transition: transform 0.3s var(--ease-out), box-shadow 0.3s ease;
}

.podium-slot:hover .avatar {
  transform: translateY(-3px);
}

.avatar.small {
  width: 2rem;
  height: 2rem;
  box-shadow: 0 0 0 1px var(--line);
}

.avatar.placeholder {
  display: grid;
  place-items: center;
  color: var(--silver);
  font-size: 0.85rem;
}

.place-1 .avatar {
  width: 2.9rem;
  height: 2.9rem;
  box-shadow: 0 0 0 2px var(--crown-gold);
}

.place-2 .avatar {
  box-shadow: 0 0 0 2px var(--crown-silver);
}

.place-3 .avatar {
  box-shadow: 0 0 0 2px var(--crown-bronze);
}

/* CROWNS: gold, silver, bronze. They bob slowly so the podium feels alive. */

.crown {
  position: absolute;
  top: -0.9rem;
  font-size: 0.9rem;
  filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.15));
  animation: bob 2.6s ease-in-out infinite;
}

.crown-1 {
  color: var(--crown-gold);
}

.crown-2 {
  color: var(--crown-silver);
  animation-delay: 0.4s;
}

.crown-3 {
  color: var(--crown-bronze);
  animation-delay: 0.8s;
}

.podium-name {
  max-width: 100%;
  overflow: hidden;
  font-size: 0.78rem;
  font-weight: 750;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.podium-score {
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.place-1 .podium-score {
  font-size: 0.9rem;
}

.you {
  display: inline-flex;
  padding: 0.05rem 0.4rem;
  border-radius: 999px;
  background: var(--ink);
  color: var(--white);
  font-size: 0.56rem;
  font-style: normal;
  font-weight: 750;
}

.podium-block {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.15rem;
  padding-top: 0.6rem;
  border-radius: 0.75rem 0.75rem 0 0;
  transition: filter 0.3s ease;
}

.podium-slot:hover .podium-block {
  filter: brightness(1.08);
}

.place-1 .podium-block {
  height: var(--block-1);
  background: var(--crown-gold);
  color: var(--white);
}

.place-2 .podium-block {
  height: var(--block-2);
  background: var(--crown-silver);
  color: var(--white);
}

.place-3 .podium-block {
  height: var(--block-3);
  background: var(--crown-bronze);
  color: var(--white);
}

.block-rank {
  font-size: 1.25rem;
  font-weight: 850;
  line-height: 1;
  letter-spacing: -0.04em;
}

.block-label {
  font-size: 0.6rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  opacity: 0.85;
}

/* REST OF THE RANKING */

.ranking {
  max-height: 22rem;
  overflow-y: auto;
  overscroll-behavior: contain;
  scrollbar-width: thin;
  scrollbar-color: var(--light) transparent;
  padding-right: 0.25rem;
}

.ranking li {
  display: grid;
  grid-template-columns: 1.6rem 2rem minmax(0, 1fr) auto;
  align-items: center;
  gap: 0.65rem;
  padding: 0.5rem 0.5rem;
  border-radius: var(--radius-inner);
  transition: background 0.2s ease, transform 0.25s var(--ease-out);
  animation: rise 0.45s var(--ease-out) both;
}

.ranking li:nth-child(1) {
  animation-delay: 0.05s;
}

.ranking li:nth-child(2) {
  animation-delay: 0.1s;
}

.ranking li:nth-child(3) {
  animation-delay: 0.15s;
}

.ranking li:nth-child(4) {
  animation-delay: 0.2s;
}

.ranking li+li {
  border-top: 1px solid var(--line);
}

.ranking li:hover {
  background: var(--soft);
  transform: translateX(4px);
}

.ranking li.me {
  background: var(--soft);
  box-shadow: inset 3px 0 0 var(--ink);
}

.rank {
  color: var(--dim);
  font-size: 0.8rem;
  font-weight: 750;
  text-align: center;
  font-variant-numeric: tabular-nums;
}

.rank-main {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.rank-main strong {
  overflow: hidden;
  font-size: 0.8125rem;
  font-weight: 650;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.score {
  font-weight: 800;
  font-size: 0.85rem;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
}

.footnote {
  margin-top: 0.9rem;
  padding-top: 0.75rem;
  border-top: 1px solid var(--line);
  color: var(--dim);
  font-size: 0.7rem;
}

/* =========================================================
   WIDE SCREENS
========================================================= */

@media (min-width: 861px) {
  .ranking-card {
    max-width: 52rem;
    width: 100%;
    margin: 0 auto;
  }
}

/* =========================================================
   TABLET
========================================================= */

@media (max-width: 860px) {
  .overview {
    grid-template-columns: minmax(0, 1fr);
  }
}

/* =========================================================
   PHONE
========================================================= */

@media (max-width: 600px) {
  .home {
    padding: 1rem 0.75rem 2rem;
    gap: 0.875rem;
  }

  .card {
    padding: 1rem;
  }

  .head-actions {
    width: 100%;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr) var(--btn-h);
    gap: 0.4rem;
  }

  .head-actions .btn {
    min-width: 0;
    padding: 0.4rem 0.5rem;
    font-size: 0.75rem;
    white-space: normal;
  }

  .stats {
    gap: 0.5rem;
  }

  .stat {
    min-height: 8.25rem;
    padding: 0.85rem;
  }

  .skeleton-stat {
    min-height: 8.25rem;
  }

  .stat-value {
    font-size: 1.3rem;
  }

  .stat-note {
    font-size: 0.64rem;
  }

  .filters {
    grid-template-columns: minmax(0, 1fr);
    gap: 0.5rem;
  }

  .podium {
    --block-1: 6.5rem;
    --block-2: 5rem;
    --block-3: 4rem;

    gap: 0.4rem;
    padding: 1rem 0.5rem 0;
  }

  .avatar {
    width: 2.4rem;
    height: 2.4rem;
  }

  .place-1 .avatar {
    width: 2.9rem;
    height: 2.9rem;
  }

  .crown {
    top: -0.8rem;
    font-size: 0.8rem;
  }

  .podium-name {
    font-size: 0.68rem;
  }

  .podium-score {
    font-size: 0.8rem;
  }

  .place-1 .podium-score {
    font-size: 0.9rem;
  }

  .block-rank {
    font-size: 1.25rem;
  }

  .block-label {
    font-size: 0.55rem;
  }

  .row,
  .skeleton-row {
    grid-template-columns: 2rem minmax(0, 1fr) auto;
    gap: 0.5rem;
  }

  .row-icon,
  .sk-icon-sm {
    width: 2rem;
    height: 2rem;
  }

  .ranking {
    max-height: 18rem;
  }

  .ranking li {
    grid-template-columns: 1.5rem 1.85rem minmax(0, 1fr) auto;
    gap: 0.5rem;
    padding: 0.6rem 0.4rem;
  }

  .avatar.small {
    width: 1.85rem;
    height: 1.85rem;
  }

  .state-panel {
    min-height: 13rem;
    padding: 1.5rem 1rem;
  }

  .state-actions {
    width: 100%;
  }

  .state-actions .btn {
    flex: 1 1 140px;
  }
}

@media (max-width: 360px) {
  .podium {
    --block-1: 5.5rem;
    --block-2: 4.5rem;
    --block-3: 3.75rem;
  }

  .podium-name {
    max-width: 4.5rem;
  }
}

@media (prefers-reduced-motion: reduce) {

  .home *,
  .home *::before,
  .home *::after {
    transition: none !important;
    animation: none !important;
  }
}
</style>