<template>
  <div class="home">
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

    <div v-if="!isOnline" class="notice notice-warn" role="status">
      <i class="fas fa-wifi" aria-hidden="true"></i>
      <span>Your connection is offline. Results will refresh when you reconnect.</span>
    </div>

    <p v-if="refreshing && slowNetwork && !hasLoaded" class="slow-note" role="status">
      <i class="fas fa-hourglass-half" aria-hidden="true"></i>
      Still loading. Your connection may be slow.
    </p>

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
      <div v-if="dashboardErrorText" class="alert" role="alert">
        <span>{{ dashboardErrorText }}</span>
        <button type="button" @click="refreshDashboard">Try again</button>
      </div>

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
              <span :style="{ width: `${safePercentage(latest.percentage)}%` }"></span>
            </div>

            <p class="threshold">
              Passing score: {{ formatPercentage(latest.passing_score ?? PASSING_FALLBACK) }}
            </p>

            <div class="latest-foot">
              <div class="latest-info">
                <strong>{{ latest.quiz_title || 'Assessment' }}</strong>
                <small>
                  {{ metaLine(latest) }}
                  <template v-if="formatDate(latest.completed_at)">
                    · {{ formatDate(latest.completed_at) }}
                  </template>
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

        <div class="stats">
          <article v-for="stat in stats" :key="stat.label" class="card stat">
            <div class="stat-top">
              <span class="stat-icon">
                <i :class="stat.icon" aria-hidden="true"></i>
              </span>
              <span class="stat-label">{{ stat.label }}</span>
            </div>

            <strong class="stat-value">{{ stat.value }}</strong>

            <div v-if="stat.bar !== null" class="mini-bar" aria-hidden="true">
              <span :style="{ width: `${stat.bar}%` }"></span>
            </div>

            <small class="stat-note">{{ stat.note }}</small>
          </article>
        </div>
      </section>

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
                <small>
                  {{ metaLine(record) }} · {{ formatDate(record.completed_at) || '-' }}
                </small>
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

      <section class="card ranking-card">
        <header class="card-head">
          <div>
            <h2>Assessment ranking</h2>
            <p>Each learner’s best result for the selected assessment and period</p>
          </div>

          <span v-if="userPosition" class="rank-pill">
            Your rank: #{{ userPosition }}
          </span>
        </header>

        <div class="leaderboard-filters" aria-label="Leaderboard filters">
          <div class="period-filter">
            <span class="filter-label">Time period</span>

            <div class="period-options" role="group" aria-label="Time period">
              <button v-for="option in PERIOD_OPTIONS" :key="option.value" type="button" class="period-chip"
                :class="{ active: selectedPeriod === option.value }" :aria-pressed="selectedPeriod === option.value"
                :disabled="!hasLoaded || boardLoading" @click="setPeriod(option.value)">
                {{ option.label }}
              </button>
            </div>
          </div>

          <div class="select-filters">
            <label class="filter-field">
              <span>Competency</span>
              <select v-model="selectedCocId" :disabled="!hasLoaded || boardLoading || !cocOptions.length">
                <option value="" disabled>Select a competency</option>
                <option v-for="coc in cocOptions" :key="coc.id" :value="String(coc.id)">
                  {{ coc.label }}
                </option>
              </select>
            </label>

            <label class="filter-field">
              <span>Assessment</span>
              <select v-model="selectedAssessmentId"
                :disabled="!hasLoaded || boardLoading || !visibleAssessments.length">
                <option value="" disabled>
                  {{ visibleAssessments.length ? 'Select an assessment' : 'No assessments available' }}
                </option>
                <option v-for="assessment in visibleAssessments" :key="assessment.id" :value="String(assessment.id)">
                  {{ assessment.title }}
                </option>
              </select>
            </label>
          </div>
        </div>

        <div v-if="leaderboardErrorText" class="alert" role="alert">
          <span>{{ leaderboardErrorText }}</span>
          <button type="button" @click="refreshLeaderboard(true)">Try again</button>
        </div>

        <p class="sr-only" aria-live="polite">{{ boardStatus }}</p>

        <div class="board" :class="{ 'is-loading': boardLoading }" :aria-busy="boardLoading ? 'true' : 'false'">
          <div v-if="!hasLoaded" class="podium skeleton-podium" aria-hidden="true">
            <div v-for="n in 3" :key="n" class="podium-slot">
              <span class="sk sk-avatar"></span>
              <span class="sk sk-line sk-w-60"></span>
              <span class="sk podium-sk-block"></span>
            </div>
          </div>

          <template v-else-if="entries.length">
            <div v-if="podiumSlots.length" class="podium" aria-label="Top three learners">
              <div v-for="slot in podiumSlots" :key="slot.rank" class="podium-slot"
                :class="[`place-${slot.rank}`, { me: isCurrentUser(slot.entry) }]">
                <div class="podium-person">
                  <span class="crown" :class="`crown-${slot.rank}`" :title="`${rankLabel(slot.rank)} place`"
                    aria-hidden="true">
                    <i class="fas fa-crown"></i>
                  </span>

                  <img :src="photoPath(slot.entry.profile_photo)" :alt="entryDisplayName(slot.entry)" class="avatar"
                    draggable="false" loading="lazy" @error="handlePhotoError" />

                  <strong class="podium-name">
                    {{ entryDisplayName(slot.entry) }}
                  </strong>

                  <span v-if="isCurrentUser(slot.entry)" class="you">You</span>

                  <span class="podium-score">
                    {{ formatPercentage(slot.entry.percentage) }}
                  </span>
                </div>

                <div class="podium-block">
                  <span class="block-rank">{{ slot.rank }}</span>
                  <span class="block-label">{{ rankLabel(slot.rank) }}</span>
                </div>
              </div>
            </div>

            <ol v-if="restOfRanking.length" class="ranking">
              <li v-for="entry in restOfRanking" :key="entry.rank" :class="{ me: isCurrentUser(entry) }">
                <span class="rank">{{ entry.rank }}</span>

                <img :src="photoPath(entry.profile_photo)" :alt="entryDisplayName(entry)" class="avatar small"
                  draggable="false" loading="lazy" @error="handlePhotoError" />

                <span class="rank-main">
                  <strong>
                    {{ entryDisplayName(entry) }}
                    <em v-if="isCurrentUser(entry)" class="you">You</em>
                  </strong>

                  <span class="mini-bar" aria-hidden="true">
                    <span :style="{ width: `${safePercentage(entry.percentage)}%` }"></span>
                  </span>
                </span>

                <span class="score">{{ formatPercentage(entry.percentage) }}</span>
              </li>
            </ol>

            <p class="footnote">{{ activeFilterLabel }}</p>
          </template>

          <div v-else-if="!leaderboardErrorText" class="empty-state small">
            <i class="fas fa-trophy" aria-hidden="true"></i>
            <strong>{{ emptyBoardTitle }}</strong>
            <p>{{ emptyBoardHint }}</p>
          </div>
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
  dashboardError,
  getDashboard,
  leaderboard,
  leaderboardMeta,
  leaderboardError,
  getLeaderBoard,
} = useUserRequests()

const PASSING_FALLBACK = 75
const DEFAULT_PHOTO = '/storage/images/profiles/default.png'
const SLOW_AFTER_MS = 4000
const BOARD_LIMIT = 100

const PERIOD_OPTIONS = [
  { value: 'today', label: 'Today' },
  { value: '3d', label: '3 days' },
  { value: '7d', label: '7 days' },
  { value: 'all', label: 'All time' },
]

const PERIOD_PHRASES = {
  today: 'today',
  '3d': 'in the last 3 days',
  '7d': 'in the last 7 days',
  all: '',
}

const DEFAULT_PERIOD = '7d'

const ERROR_STATES = {
  offline: {
    icon: 'fas fa-wifi',
    title: "You're offline",
    text: 'Check your internet connection. Your results will load automatically once you reconnect.',
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

const isOnline = ref(typeof navigator === 'undefined' ? true : navigator.onLine)
const refreshing = ref(false)
const hasLoaded = ref(false)
const slowNetwork = ref(false)
const pageError = ref('')

const sectionErrors = reactive({
  dashboard: '',
  leaderboard: '',
})

const selectedCocId = ref('')
const selectedAssessmentId = ref('')
const selectedPeriod = ref(DEFAULT_PERIOD)

const boardPending = ref(0)
const boardLoading = computed(() => boardPending.value > 0)

let requestId = 0
let slowTimer = null
let syncingFilters = false
let leaderboardChain = Promise.resolve()

const currentError = computed(
  () => ERROR_STATES[pageError.value] ?? ERROR_STATES.server
)

function classifyError(error) {
  const status = error?.response?.status

  if (status === 401 || status === 419) return 'session'
  if (status === 403) return 'permission'
  if (error?.response) return 'server'

  if (error?.code === 'ECONNABORTED' || error?.code === 'ETIMEDOUT') {
    return 'timeout'
  }

  if (typeof navigator !== 'undefined' && !navigator.onLine) {
    return 'offline'
  }

  return 'network'
}

const dashboardErrorText = computed(() => {
  if (!sectionErrors.dashboard) return ''

  const state = ERROR_STATES[sectionErrors.dashboard]
  return state
    ? `Your results could not be loaded: ${state.title}.`
    : 'Your results could not be loaded.'
})

const leaderboardErrorText = computed(() => {
  if (!sectionErrors.leaderboard) return ''

  const state = ERROR_STATES[sectionErrors.leaderboard]
  return state
    ? `The ranking could not be loaded: ${state.title}.`
    : 'The ranking could not be loaded.'
})

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

  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

const formatLabel = (value) => {
  if (!value) return ''

  return String(value)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase())
}

const statusClass = (record) => {
  if (typeof record?.passed === 'boolean') {
    return record.passed ? 'passed' : 'failed'
  }

  const status = String(record?.status ?? '').toLowerCase()
  if (status.includes('pass')) return 'passed'
  if (status.includes('fail')) return 'failed'

  return safePercentage(record?.percentage) >=
    toNumber(record?.passing_score, PASSING_FALLBACK)
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

  if (value.includes('rj45') || value.includes('network')) {
    return 'fas fa-network-wired'
  }

  if (
    value.includes('hardware') ||
    value.includes('assemble') ||
    value.includes('drag')
  ) {
    return 'fas fa-screwdriver-wrench'
  }

  if (value.includes('image')) return 'fas fa-image'
  return 'fas fa-file-lines'
}

const photoPath = (image) => {
  if (!image) return DEFAULT_PHOTO

  const value = String(image)

  if (/^https?:\/\//i.test(value) || value.startsWith('/storage/')) {
    return value
  }

  return `/storage/images/profiles/${value}`
}

function handlePhotoError(event) {
  const image = event?.target

  if (image && !image.src.endsWith(DEFAULT_PHOTO)) {
    image.src = DEFAULT_PHOTO
  }
}

const rankLabel = (rank) => {
  const remainder = rank % 100

  if (remainder >= 11 && remainder <= 13) return `${rank}th`

  switch (rank % 10) {
    case 1:
      return `${rank}st`
    case 2:
      return `${rank}nd`
    case 3:
      return `${rank}rd`
    default:
      return `${rank}th`
  }
}

const entryDisplayName = (entry) =>
  entry?.displayName || entry?.name || 'Learner'

const isCurrentUser = (entry) =>
  entry?.is_you === true || entry?.isYou === true

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
  const items = Array.isArray(dashboard.value?.recent_activity)
    ? dashboard.value.recent_activity
    : []

  return [...items]
    .sort((a, b) => {
      const dateA = new Date(a?.completed_at ?? 0).getTime()
      const dateB = new Date(b?.completed_at ?? 0).getTime()
      return dateB - dateA
    })
    .slice(0, 5)
})

const latest = computed(() => recentResults.value[0] ?? null)
const latestStatus = computed(() => (latest.value ? statusClass(latest.value) : ''))

const summaryText = computed(() => {
  if (!hasLoaded.value) return 'Loading your progress…'

  const { attempts, average } = summary.value

  if (!attempts) {
    return 'Take your first assessment to start tracking your results.'
  }

  return `You have completed ${attempts} ${attempts === 1 ? 'assessment attempt' : 'assessment attempts'
    } with an average score of ${formatPercentage(average)}.`
})

const passRate = computed(() => {
  const { attempts, passed } = summary.value
  return attempts ? Math.round((passed / attempts) * 100) : 0
})

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

const entries = computed(() =>
  Array.isArray(leaderboard.value) ? leaderboard.value : []
)

const cocOptions = computed(() =>
  Array.isArray(leaderboardMeta.value?.filter_options?.cocs)
    ? leaderboardMeta.value.filter_options.cocs
    : []
)

const assessmentOptions = computed(() =>
  Array.isArray(leaderboardMeta.value?.filter_options?.assessments)
    ? leaderboardMeta.value.filter_options.assessments
    : []
)

const visibleAssessments = computed(() =>
  assessmentOptions.value.filter(
    (item) => String(item.coc_id) === String(selectedCocId.value)
  )
)

const selectedAssessment = computed(
  () =>
    assessmentOptions.value.find(
      (item) => String(item.id) === String(selectedAssessmentId.value)
    ) ?? null
)

const activePeriod = computed(
  () =>
    PERIOD_OPTIONS.find((option) => option.value === selectedPeriod.value) ??
    PERIOD_OPTIONS[2]
)

const sinceText = computed(() => {
  const since = leaderboardMeta.value?.since

  if (!since || selectedPeriod.value === 'all') return ''
  return formatDate(since)
})

const activeFilterLabel = computed(() => {
  const coc = cocOptions.value.find(
    (item) => String(item.id) === String(selectedCocId.value)
  )

  const parts = [
    coc?.label ?? 'No competency selected',
    selectedAssessment.value?.title ?? 'No assessment selected',
    activePeriod.value.label,
  ]

  if (sinceText.value) parts.push(`since ${sinceText.value}`)
  return parts.join(' · ')
})

const emptyBoardTitle = computed(() => {
  if (!cocOptions.value.length) return 'No rankings are available yet'
  if (selectedPeriod.value === 'all') return 'No results for this assessment yet'

  return `No results ${PERIOD_PHRASES[selectedPeriod.value]}`
})

const emptyBoardHint = computed(() =>
  selectedPeriod.value === 'all'
    ? 'Rankings appear after learners complete an assessment.'
    : 'Try a longer period to see more results.'
)

const boardStatus = computed(() => {
  if (!hasLoaded.value) return ''
  if (boardLoading.value) return 'Updating leaderboard.'

  return `${entries.value.length} ranked ${entries.value.length === 1 ? 'learner' : 'learners'
    } · ${activePeriod.value.label}`
})

const userPosition = computed(
  () => entries.value.find(isCurrentUser)?.rank ?? null
)

const podiumSlots = computed(() =>
  [2, 1, 3]
    .map((rank) => ({
      rank,
      entry: entries.value.find((entry) => Number(entry.rank) === rank) ?? null,
    }))
    .filter((slot) => slot.entry)
)

const restOfRanking = computed(() =>
  entries.value.filter((entry) => Number(entry.rank) > 3)
)

function syncFilters() {
  syncingFilters = true

  if (!cocOptions.value.some((item) => String(item.id) === String(selectedCocId.value))) {
    selectedCocId.value = cocOptions.value[0] ? String(cocOptions.value[0].id) : ''
  }

  if (
    !visibleAssessments.value.some(
      (item) => String(item.id) === String(selectedAssessmentId.value)
    )
  ) {
    selectedAssessmentId.value = visibleAssessments.value[0]
      ? String(visibleAssessments.value[0].id)
      : ''
  }

  syncingFilters = false
}

async function reloadLeaderboard(force = false) {
  // First request loads filter options and the ranking if an assessment is selected.
  await getLeaderBoard({
    limit: BOARD_LIMIT,
    assessment_id: selectedAssessmentId.value || undefined,
    period: selectedPeriod.value,
    force,
  })

  syncFilters()

  // If defaults were selected after options arrived, request that ranking as well.
  const selectedAssessmentIdNow = selectedAssessmentId.value || null
  const requestedAssessmentId = selectedAssessmentId.value || null

  if (selectedAssessmentIdNow && selectedAssessmentIdNow !== requestedAssessmentId) {
    await getLeaderBoard({
      limit: BOARD_LIMIT,
      assessment_id: selectedAssessmentIdNow,
      period: selectedPeriod.value,
      force: true,
    })
  } else if (selectedAssessmentIdNow && !entries.value.length) {
    // This fetches the selected board after the initial request only returned options.
    await getLeaderBoard({
      limit: BOARD_LIMIT,
      assessment_id: selectedAssessmentIdNow,
      period: selectedPeriod.value,
      force: true,
    })
  }
}

function runLeaderboard(force = false) {
  boardPending.value++

  const request = leaderboardChain
    .catch(() => { })
    .then(() => reloadLeaderboard(force))

  leaderboardChain = request

  return request.finally(() => {
    boardPending.value = Math.max(0, boardPending.value - 1)
  })
}

async function refreshLeaderboard(force = false) {
  sectionErrors.leaderboard = ''

  try {
    await runLeaderboard(force)
  } catch (error) {
    console.error('Failed to load assessment ranking:', error)
    sectionErrors.leaderboard = classifyError(error)
  }
}

async function refreshDashboard() {
  try {
    await getDashboard(true)
    sectionErrors.dashboard = ''
  } catch (error) {
    console.error('Failed to load dashboard:', error)
    sectionErrors.dashboard = classifyError(error)
  }
}

function setPeriod(value) {
  if (value !== selectedPeriod.value) {
    selectedPeriod.value = value
  }
}

watch(selectedCocId, () => {
  if (syncingFilters) return

  if (
    !visibleAssessments.value.some(
      (item) => String(item.id) === String(selectedAssessmentId.value)
    )
  ) {
    selectedAssessmentId.value = visibleAssessments.value[0]
      ? String(visibleAssessments.value[0].id)
      : ''
  }
})

watch(
  [selectedCocId, selectedAssessmentId, selectedPeriod],
  ([cocId, assessmentId, period], previous) => {
    if (!hasLoaded.value || syncingFilters || !previous) return

    const [previousCocId, previousAssessmentId, previousPeriod] = previous

    if (
      cocId !== previousCocId ||
      assessmentId !== previousAssessmentId ||
      period !== previousPeriod
    ) {
      refreshLeaderboard(false)
    }
  }
)

async function loadHome(force = false) {
  const id = ++requestId

  if (typeof navigator !== 'undefined' && !navigator.onLine) {
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
      runLeaderboard(force),
    ])

    if (id !== requestId) return

    const rejected = [dashboardResult, leaderboardResult].filter(
      (result) => result.status === 'rejected'
    )

    const failureTypes = rejected.map((result) => classifyError(result.reason))
    const blockingError = failureTypes.find(
      (type) => type === 'session' || type === 'permission'
    )

    if (blockingError) {
      pageError.value = blockingError
      return
    }

    if (rejected.length === 2) {
      pageError.value = failureTypes[0]
      return
    }

    sectionErrors.dashboard =
      dashboardResult.status === 'rejected'
        ? classifyError(dashboardResult.reason)
        : ''

    sectionErrors.leaderboard =
      leaderboardResult.status === 'rejected'
        ? classifyError(leaderboardResult.reason)
        : ''
  } catch (error) {
    if (id !== requestId) return

    console.error('Failed to load the home page:', error)
    pageError.value = classifyError(error)
  } finally {
    if (id === requestId) {
      refreshing.value = false
      hasLoaded.value = true
      stopSlowTimer()
    }
  }
}

function refreshHome() {
  return loadHome(true)
}

const goToAssessments = () => router.push('/user/assessments')
const goToRecords = () => router.push('/user/records')
const goToLogin = () => router.push('/')

function handleOnline() {
  isOnline.value = true

  const needsReload =
    ['offline', 'network', 'timeout'].includes(pageError.value) ||
    Object.values(sectionErrors).some(Boolean) ||
    !hasLoaded.value

  if (needsReload) loadHome(true)
}

function handleOffline() {
  isOnline.value = false
}

onMounted(async () => {
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)

  try {
    if (!user.value) await fetchUser()
  } catch (error) {
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
.home {
  --white: #fff;
  --ink: #000;
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
  min-width: 0;
  margin: 0 auto;
  padding: clamp(1rem, 2.5vw, 1.75rem) clamp(0.75rem, 2vw, 1.25rem) 3rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  color: var(--ink);
  font-size: 1rem;
  line-height: 1.5;
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

/* Header */
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

/* Notices */
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

/* Page state */
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

/* Buttons */
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
  transition:
    background 0.2s ease,
    border-color 0.2s ease,
    color 0.2s ease,
    transform 0.15s ease,
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
}

.btn-solid {
  background: var(--ink);
  color: var(--white) !important;
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
  background: #eee;
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
}

.link:hover {
  color: var(--ink);
}

.spin {
  animation: spin 0.8s linear infinite;
}

/* Alerts */
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

/* Cards */
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

/* Overview */
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
  border-color: var(--ink);
  background: var(--ink);
  color: var(--white);
}

.latest::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(100deg,
      transparent 30%,
      rgba(255, 255, 255, 0.12) 50%,
      transparent 70%);
  transform: translateX(-120%);
  animation: shine 4.5s ease-in-out 1.2s infinite;
  pointer-events: none;
}

.latest.empty {
  border: 1px dashed var(--light);
  background: var(--soft);
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
  animation: grow 1s var(--ease-out) both;
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

/* Stats */
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
  animation: grow 1s var(--ease-out) both;
  transition: width 0.6s ease;
}

/* Skeletons */
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

/* Empty states */
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

/* Recent results */
.list li+li,
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
}

.pill.passed {
  background: rgba(17, 129, 61, 0.1);
  color: var(--pass);
}

.pill.failed {
  background: rgba(200, 30, 46, 0.08);
  color: var(--fail);
}

/* Leaderboard */
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

/* Redesigned filters */
.leaderboard-filters {
  display: grid;
  gap: 1rem;
  margin-bottom: 1.25rem;
  padding: 1rem;
  border: 1px solid var(--line);
  border-radius: 14px;
  background: var(--soft);
}

.period-filter {
  display: grid;
  gap: 0.5rem;
}

.filter-label,
.filter-field>span {
  color: var(--dim);
  font-size: 0.68rem;
  font-weight: 750;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.period-options {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.period-chip {
  min-height: 36px;
  padding: 0 0.85rem;
  border: 1px solid var(--line);
  border-radius: 999px;
  background: var(--white);
  color: var(--dim);
  font-size: 0.78rem;
  font-weight: 650;
  transition: background 0.2s, color 0.2s, border-color 0.2s;
}

.period-chip:hover:not(:disabled) {
  border-color: var(--ink);
  color: var(--ink);
}

.period-chip.active {
  border-color: var(--ink);
  background: var(--ink);
  color: var(--white);
}

.period-chip:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.select-filters {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}

.filter-field {
  min-width: 0;
  display: grid;
  gap: 0.4rem;
}

.filter-field select {
  width: 100%;
  min-width: 0;
  height: 44px;
  padding: 0 0.8rem;
  border: 1px solid var(--line);
  border-radius: 10px;
  background: var(--white);
  font-size: 0.85rem;
}

.filter-field select:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.filter-field select:focus-visible {
  outline: 2px solid var(--ink);
  outline-offset: 2px;
}

.board {
  transition: opacity 0.25s ease, filter 0.25s ease;
}

.board.is-loading {
  opacity: 0.55;
}

/* Podium */
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
  border: 2px solid var(--white);
  border-radius: 50%;
  background: var(--soft);
  object-fit: cover;
  box-shadow: 0 0 0 1px var(--light);
}

.avatar.small {
  width: 2rem;
  height: 2rem;
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

.crown {
  position: absolute;
  top: -0.9rem;
  font-size: 0.9rem;
  animation: bob 2.6s ease-in-out infinite;
}

.crown-1 {
  color: var(--crown-gold);
}

.crown-2 {
  color: var(--crown-silver);
}

.crown-3 {
  color: var(--crown-bronze);
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
}

.block-label {
  font-size: 0.6rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  opacity: 0.85;
}

/* Remaining ranking */
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
  padding: 0.5rem;
  border-radius: var(--radius-inner);
  transition: background 0.2s ease, transform 0.25s var(--ease-out);
}

.ranking li+li {
  border-top: 1px solid var(--line);
}

.ranking li:hover {
  background: var(--soft);
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
  font-size: 0.85rem;
  font-weight: 800;
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

@media (min-width: 861px) {
  .ranking-card {
    width: 100%;
    max-width: 52rem;
    margin: 0 auto;
  }
}

@media (max-width: 860px) {
  .overview {
    grid-template-columns: minmax(0, 1fr);
  }
}

@media (max-width: 600px) {
  .home {
    gap: 0.875rem;
    padding: 1rem 0.75rem 2rem;
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

  .stat,
  .skeleton-stat {
    min-height: 8.25rem;
    padding: 0.85rem;
  }

  .stat-value {
    font-size: 1.3rem;
  }

  .stat-note {
    font-size: 0.64rem;
  }

  .select-filters {
    grid-template-columns: minmax(0, 1fr);
    gap: 0.6rem;
  }

  .period-options {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .podium {
    --block-1: 6.5rem;
    --block-2: 5rem;
    --block-3: 4rem;

    gap: 0.4rem;
    padding: 1rem 0.5rem 0;
  }

  .podium-name {
    font-size: 0.68rem;
  }

  .crown {
    top: -0.8rem;
    font-size: 0.8rem;
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