<template>
  <div class="home-page">
    <!-- ============================================================= -->
    <!-- WELCOME                                                       -->
    <!-- ============================================================= -->
    <section class="welcome-card">
      <div class="welcome-copy">
        <span class="eyebrow">CSS Prep · NC II Readiness</span>

        <h1>{{ greetMessage }}, {{ userFullName }}! 👋</h1>

        <p>
          Review your assessment performance, continue practicing, and keep
          track of your latest results.
        </p>
      </div>

      <div class="quick-actions">
        <button type="button" class="primary-btn" @click="goToAssessments">
          <i class="fas fa-play"></i>
          Take Assessment
        </button>

        <button type="button" class="secondary-btn" @click="goToRecords">
          <i class="fas fa-clipboard-list"></i>
          View Records
        </button>

        <button type="button" class="icon-btn" :disabled="pageLoading" title="Refresh dashboard"
          aria-label="Refresh dashboard" @click="refreshHome">
          <i class="fas fa-rotate" :class="{ spinning: pageLoading }"></i>
        </button>
      </div>
    </section>

    <!-- ============================================================= -->
    <!-- DASHBOARD ERROR                                               -->
    <!-- ============================================================= -->
    <div v-if="dashboardError" class="request-error">
      <div>
        <strong>Some dashboard data could not be loaded.</strong>
        <span>{{ dashboardError }}</span>
      </div>

      <button type="button" @click="refreshHome">
        Retry
      </button>
    </div>

    <!-- ============================================================= -->
    <!-- PERFORMANCE SUMMARY                                           -->
    <!-- ============================================================= -->
    <section class="summary-grid">
      <article class="summary-card">
        <div class="summary-icon">
          <i class="fas fa-chart-line"></i>
        </div>

        <div class="summary-copy">
          <span>Average Score</span>
          <strong>{{ formatPercentage(summary.average_score) }}</strong>
          <small>Average from recorded assessment attempts</small>
        </div>
      </article>

      <article class="summary-card">
        <div class="summary-icon">
          <i class="fas fa-medal"></i>
        </div>

        <div class="summary-copy">
          <span>Personal Best</span>
          <strong>{{ formatPercentage(summary.best_score) }}</strong>
          <small>Your highest recorded assessment result</small>
        </div>
      </article>

      <article class="summary-card">
        <div class="summary-icon">
          <i class="fas fa-circle-check"></i>
        </div>

        <div class="summary-copy">
          <span>Passed Attempts</span>
          <strong>{{ summary.passed }}</strong>
          <small>Assessment attempts that met the passing score</small>
        </div>
      </article>

      <article class="summary-card">
        <div class="summary-icon">
          <i class="fas fa-arrow-rotate-right"></i>
        </div>

        <div class="summary-copy">
          <span>Total Attempts</span>
          <strong>{{ summary.total_attempts }}</strong>
          <small>All assessment attempts recorded in CSS Prep</small>
        </div>
      </article>
    </section>

    <!-- ============================================================= -->
    <!-- RECENT ACTIVITY                                               -->
    <!-- ============================================================= -->
    <section class="panel recent-panel">
      <div class="panel-heading">
        <div>
          <span class="panel-kicker">Your latest work</span>
          <h2>Recent Activity</h2>
        </div>

        <button type="button" class="text-btn" @click="goToRecords">
          View all records
          <i class="fas fa-arrow-right"></i>
        </button>
      </div>

      <div v-if="recentActivity.length" class="recent-list">
        <article v-for="record in recentActivity" :key="record.id" class="recent-item">
          <div class="recent-icon">
            <i :class="activityIcon(record.quiz_type)"></i>
          </div>

          <div class="recent-info">
            <strong>{{ record.quiz_title || 'Assessment' }}</strong>
            <span>
              <template v-if="record.coc_number !== null && record.coc_number !== undefined">
                COC {{ record.coc_number }}
              </template>
              <template v-if="record.category">
                <span v-if="record.coc_number !== null && record.coc_number !== undefined"> · </span>
                {{ record.category }}
              </template>
            </span>
          </div>

          <div class="recent-result">
            <strong>{{ formatPercentage(record.percentage) }}</strong>
            <span class="status-badge" :class="statusClass(record)">
              {{ formatStatus(record.status) }}
            </span>
            <small>{{ formatDate(record.completed_at) }}</small>
          </div>
        </article>
      </div>

      <div v-else-if="pageLoading" class="panel-loading">
        <div class="spinner"></div>
      </div>

      <div v-else class="empty-state">
        <i class="fas fa-clock-rotate-left"></i>
        <strong>No assessment activity yet</strong>
        <span>Your latest assessment attempts will appear here.</span>
        <button type="button" class="small-primary-btn" @click="goToAssessments">
          Take your first assessment
        </button>
      </div>
    </section>

    <!-- ============================================================= -->
    <!-- LEADERBOARD                                                   -->
    <!-- ============================================================= -->
    <section class="panel leaderboard-panel">
      <div class="leaderboard-header">
        <div class="leaderboard-title-wrap">
          <div class="leaderboard-icon-box">
            <i class="fas fa-trophy"></i>
          </div>

          <div>
            <span class="panel-kicker">Community performance</span>
            <h2>Leaderboard</h2>
            <p>Best result per participant for the selected assessment.</p>
          </div>
        </div>

        <div v-if="userPosition" class="rank-pill">
          <span></span>
          Your rank #{{ userPosition }}
        </div>
      </div>

      <div class="leaderboard-toolbar">
        <div class="search-box">
          <i class="fas fa-magnifying-glass"></i>
          <input v-model="searchQuery" type="text" placeholder="Search participant or assessment..." />
        </div>

        <div class="filter-box">
          <label for="leaderboard-assessment">Assessment</label>

          <select id="leaderboard-assessment" v-model="selectedQuizId">
            <option value="">All assessments</option>
            <option v-for="quiz in availableQuizzes" :key="quiz.id" :value="String(quiz.id)">
              {{ quiz.title }}
            </option>
          </select>
        </div>
      </div>

      <div v-if="leaderboardError" class="request-error compact">
        <div>
          <strong>Unable to load leaderboard.</strong>
          <span>{{ leaderboardError }}</span>
        </div>

        <button type="button" @click="reloadLeaderboard(true)">
          Retry
        </button>
      </div>

      <!-- Top three -->
      <div v-if="!searchQuery && topThree.length" class="podium">
        <article v-for="entry in podiumOrder" :key="entry.id" class="podium-card" :class="[
          `podium-${entry.rank}`,
          isCurrentUser(entry) ? 'is-you' : '',
        ]">
          <div class="podium-avatar-wrap">
            <img :src="photoPath(entry.profile_photo)" :alt="entryDisplayName(entry)" class="podium-avatar"
              draggable="false" />

            <span class="podium-medal">
              {{ ['🥇', '🥈', '🥉'][entry.rank - 1] }}
            </span>
          </div>

          <div class="podium-name">
            {{ entryDisplayName(entry) }}
            <span v-if="isCurrentUser(entry)" class="you-tag">You</span>
          </div>

          <strong class="podium-score">
            {{ formatPercentage(entry.percentage) }}
          </strong>

          <span class="podium-raw-score">
            {{ formatRawScore(entry) }}
          </span>

          <div class="podium-bar" :style="{ height: podiumHeight(entry.rank) }"></div>
        </article>
      </div>

      <!-- Ranking list -->
      <div class="leaderboard-list">
        <div v-if="pageLoading && !leaderboard.length" class="panel-loading">
          <div class="spinner"></div>
        </div>

        <template v-else>
          <article v-for="(entry, index) in filteredList" :key="entry.id" class="leaderboard-item" :class="{
            'is-you': isCurrentUser(entry),
            'is-top': index < 3 && !searchQuery,
          }">
            <div class="rank-cell">
              <span v-if="index < 3 && !searchQuery" class="rank-medal">
                {{ ['🥇', '🥈', '🥉'][index] }}
              </span>
              <span v-else>{{ index + 1 }}</span>
            </div>

            <div class="user-avatar-wrap">
              <img :src="photoPath(entry.profile_photo)" :alt="entryDisplayName(entry)" class="user-avatar"
                draggable="false" />
            </div>

            <div class="leaderboard-user">
              <strong>
                {{ entryDisplayName(entry) }}
                <span v-if="isCurrentUser(entry)" class="you-tag">You</span>
              </strong>
              <span>{{ entry.quiz_title || 'Assessment' }}</span>
            </div>

            <div class="leaderboard-score">
              <strong>{{ formatPercentage(entry.percentage) }}</strong>
              <span>{{ formatRawScore(entry) }}</span>
              <small>{{ formatDate(entry.completed_at) }}</small>
            </div>
          </article>

          <div v-if="!filteredList.length" class="empty-state leaderboard-empty">
            <i class="fas fa-chart-column"></i>
            <strong>No leaderboard results found</strong>
            <span>
              {{
                searchQuery
                  ? 'Try a different participant or assessment.'
                  : 'Leaderboard results will appear after assessments are submitted.'
              }}
            </span>
          </div>
        </template>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useRouter } from "vue-router"
import { useUser } from "@/composables/useUser"
import { useGreetMessages } from "@/composables/useGreetMessages"
import { useUserRequests } from "@/composables/useUserRequests"

const router = useRouter()
const { greetMessage } = useGreetMessages()
const { user, userFullName, fetchUser } = useUser()

const {
    dashboard,
    dashboardError,
    getDashboard,
    leaderboard,
    leaderboardError,
    getLeaderBoard,
    isLoading,
} = useUserRequests()

const searchQuery = ref("")
const selectedQuizId = ref("")
const homeRefreshing = ref(false)

const pageLoading = computed(() =>
    Boolean(isLoading.value) || homeRefreshing.value
)

const numberValue = (value, fallback = 0) => {
    const number = Number(value)
    return Number.isFinite(number) ? number : fallback
}

const summary = computed(() => {
    const data = dashboard.value?.summary ?? {}

    return {
        average_score: numberValue(data.average_score),
        best_score: numberValue(data.best_score),
        passed: numberValue(data.passed),
        total_attempts: numberValue(data.total_attempts),
    }
})

const recentActivity = computed(() => {
    const items = dashboard.value?.recent_activity
    return Array.isArray(items) ? items : []
})

const reloadLeaderboard = async (force = false) => {
    try {
        return await getLeaderBoard({
            limit: 200,
            force,
        })
    } catch (error) {
        console.error("Failed to load leaderboard:", error)
        return []
    }
}

const loadHome = async (force = false) => {
    homeRefreshing.value = true

    try {
        const results = await Promise.allSettled([
            getDashboard(force),
            reloadLeaderboard(force),
        ])

        results.forEach((result) => {
            if (result.status === "rejected") {
                console.error("Home request failed:", result.reason)
            }
        })
    } finally {
        homeRefreshing.value = false
    }
}

const refreshHome = () => loadHome(true)

onMounted(async () => {
    try {
        if (!user.value) {
            await fetchUser()
        }

        await loadHome(false)
    } catch (error) {
        console.error("Failed to initialize Home page:", error)
    }
})

const goToAssessments = () => {
    router.push("/user/assessments")
}

const goToRecords = () => {
    router.push("/user/records")
}

const activityIcon = (type) => {
    const value = String(type ?? "").toLowerCase()

    if (value.includes("rj45") || value.includes("network")) {
        return "fas fa-network-wired"
    }

    if (
        value.includes("hardware") ||
        value.includes("assemble") ||
        value.includes("drag")
    ) {
        return "fas fa-screwdriver-wrench"
    }

    if (value.includes("image")) {
        return "fas fa-image"
    }

    return "fas fa-file-lines"
}

const statusClass = (record) => {
    const status = String(record?.status ?? "").toLowerCase()

    if (status.includes("pass")) {
        return "status-passed"
    }

    if (status.includes("fail") || status.includes("incomplete")) {
        return "status-failed"
    }

    const percentage = numberValue(record?.percentage)
    const passing = numberValue(record?.passing_score, 70)

    return percentage >= passing
        ? "status-passed"
        : "status-failed"
}

const formatStatus = (status) => {
    const value = String(status ?? "").trim()

    if (!value) {
        return "Recorded"
    }

    return value.charAt(0).toUpperCase() + value.slice(1).toLowerCase()
}

const availableQuizzes = computed(() => {
    const map = new Map()

    leaderboard.value.forEach((item) => {
        const id = Number(item.quiz_id)
        const title = item.quiz_title

        if (!Number.isFinite(id) || !title) {
            return
        }

        if (!map.has(id)) {
            map.set(id, { id, title })
        }
    })

    return Array.from(map.values()).sort((a, b) =>
        a.title.localeCompare(b.title)
    )
})

watch(
    availableQuizzes,
    (quizzes) => {
        if (
            selectedQuizId.value &&
            !quizzes.some(
                (quiz) => String(quiz.id) === String(selectedQuizId.value)
            )
        ) {
            selectedQuizId.value = ""
        }
    },
    { immediate: true }
)

const elapsedForSort = (value) => {
    if (value === null || value === undefined || value === "") {
        return Number.POSITIVE_INFINITY
    }

    const number = Number(value)

    return Number.isFinite(number)
        ? number
        : Number.POSITIVE_INFINITY
}

const dateForSort = (value) => {
    const time = new Date(value ?? 0).getTime()
    return Number.isFinite(time) ? time : 0
}

const compareRecords = (a, b) => {
    const percentageDifference =
        numberValue(b.percentage) - numberValue(a.percentage)

    if (percentageDifference !== 0) {
        return percentageDifference
    }

    const scoreDifference =
        numberValue(b.score) - numberValue(a.score)

    if (scoreDifference !== 0) {
        return scoreDifference
    }

    const timeDifference =
        elapsedForSort(a.elapsed_time) - elapsedForSort(b.elapsed_time)

    if (timeDifference !== 0) {
        return timeDifference
    }

    return dateForSort(b.completed_at) - dateForSort(a.completed_at)
}

const displayedLeaderboard = computed(() => {
    let list = Array.isArray(leaderboard.value)
        ? [...leaderboard.value]
        : []

    if (selectedQuizId.value) {
        list = list.filter(
            (item) =>
                Number(item.quiz_id) === Number(selectedQuizId.value)
        )
    }

    const bestPerUser = new Map()

    list.forEach((item) => {
        const userId = Number(item.user_id)

        if (!Number.isFinite(userId)) {
            return
        }

        const existing = bestPerUser.get(userId)

        if (!existing || compareRecords(item, existing) < 0) {
            bestPerUser.set(userId, item)
        }
    })

    return Array.from(bestPerUser.values()).sort(compareRecords)
})

const entryDisplayName = (entry) =>
    entry?.displayName || entry?.name || "Unknown User"

const isCurrentUser = (entry) => {
    if (entry?.isYou === true) {
        return true
    }

    if (!user.value?.id) {
        return false
    }

    return Number(entry?.user_id) === Number(user.value.id)
}

const userPosition = computed(() => {
    if (!user.value?.id) {
        return null
    }

    const index = displayedLeaderboard.value.findIndex(
        (entry) => isCurrentUser(entry)
    )

    return index === -1 ? null : index + 1
})

const topThree = computed(() =>
    displayedLeaderboard.value.slice(0, 3)
)

const podiumOrder = computed(() => {
    const ranked = topThree.value.map((entry, index) => ({
        ...entry,
        rank: index + 1,
    }))

    if (ranked.length <= 1) {
        return ranked
    }

    if (ranked.length === 2) {
        return [ranked[1], ranked[0]]
    }

    return [ranked[1], ranked[0], ranked[2]]
})

const podiumHeight = (rank) => {
    if (rank === 1) return "72px"
    if (rank === 2) return "54px"
    return "38px"
}

const filteredList = computed(() => {
    const query = searchQuery.value.toLowerCase().trim()

    if (!query) {
        return displayedLeaderboard.value
    }

    return displayedLeaderboard.value.filter((entry) => {
        const name = entryDisplayName(entry).toLowerCase()
        const quiz = String(entry?.quiz_title ?? "").toLowerCase()
        const category = String(entry?.category ?? "").toLowerCase()

        return (
            name.includes(query) ||
            quiz.includes(query) ||
            category.includes(query)
        )
    })
})

const safePercentage = (value) => {
    const percentage = numberValue(value)
    return Math.min(100, Math.max(0, percentage))
}

const formatPercentage = (value) => {
    const percentage = safePercentage(value)

    return `${
        Number.isInteger(percentage)
            ? percentage
            : percentage.toFixed(1)
    }%`
}

const formatRawScore = (entry) => {
    const score = numberValue(
        entry?.score ?? entry?.correct_answers
    )

    const total = numberValue(entry?.total_questions)

    if (total <= 0) {
        return `${score}`
    }

    return `${score}/${total}`
}

const formatDate = (date) => {
    if (!date) {
        return ""
    }

    const parsed = new Date(date)

    if (Number.isNaN(parsed.getTime())) {
        return ""
    }

    return parsed.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    })
}

const photoPath = (img) => {
    if (!img) {
        return "/storage/images/profiles/default.png"
    }

    const value = String(img)

    if (
        value.startsWith("http://") ||
        value.startsWith("https://") ||
        value.startsWith("/storage/")
    ) {
        return value
    }

    return `/storage/images/profiles/${value}`
}
</script>


<style scoped>
*,
*::before,
*::after {
  box-sizing: border-box;
}

.home-page {
  --white: #ffffff;
  --black: #111111;
  --text: #252525;
  --muted: #707070;
  --light: #a1a1a1;
  --border: #e7e7e7;
  --soft-border: #f0f0f0;
  --surface: #fafafa;
  --surface-alt: #f4f4f4;

  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 16px;
  color: var(--text);
  font-family: "DM Sans", BlinkMacSystemFont, "Segoe UI", sans-serif;
}

/* =========================================================
   WELCOME
========================================================= */

.welcome-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  padding: 24px;
  border: 1px solid var(--border);
  border-radius: 16px;
  background: var(--white);
  box-shadow: 0 10px 28px rgb(0 0 0 / 4%);
}

.welcome-copy {
  min-width: 0;
}

.eyebrow,
.panel-kicker {
  color: var(--muted);
  font-size: 0.68rem;
  font-weight: 750;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.welcome-copy h1 {
  margin: 6px 0 7px;
  color: var(--black);
  font-size: clamp(1.35rem, 2.8vw, 2rem);
  line-height: 1.15;
  letter-spacing: -0.03em;
}

.welcome-copy p {
  max-width: 680px;
  margin: 0;
  color: var(--muted);
  font-size: 0.82rem;
  line-height: 1.55;
}

.quick-actions {
  display: flex;
  flex-shrink: 0;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
}

.primary-btn,
.secondary-btn,
.small-primary-btn,
.request-error button,
.text-btn,
.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  border-radius: 9px;
  cursor: pointer;
  font: inherit;
  font-size: 0.73rem;
  font-weight: 700;
}

.primary-btn,
.secondary-btn {
  min-height: 38px;
  padding: 8px 13px;
}

.primary-btn,
.small-primary-btn,
.request-error button {
  border: 1px solid var(--black);
  background: var(--black);
  color: var(--white);
}

.secondary-btn {
  border: 1px solid var(--border);
  background: var(--white);
  color: var(--black);
}

.primary-btn:hover,
.small-primary-btn:hover,
.request-error button:hover {
  background: #2b2b2b;
}

.secondary-btn:hover,
.icon-btn:hover {
  border-color: var(--black);
}

.icon-btn {
  width: 38px;
  height: 38px;
  border: 1px solid var(--border);
  background: var(--white);
  color: var(--muted);
}

.icon-btn:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.spinning {
  animation: spin 0.8s linear infinite;
}

/* =========================================================
   ERRORS
========================================================= */

.request-error {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 14px;
  border: 1px solid var(--border);
  border-radius: 11px;
  background: var(--surface);
  color: var(--muted);
  font-size: 0.72rem;
}

.request-error strong,
.request-error span {
  display: block;
}

.request-error strong {
  margin-bottom: 2px;
  color: var(--black);
}

.request-error button {
  flex-shrink: 0;
  padding: 7px 11px;
}

.request-error.compact {
  margin-top: 2px;
}

/* =========================================================
   SUMMARY
========================================================= */

.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.summary-card {
  min-width: 0;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 13px;
  background: var(--white);
}

.summary-icon {
  width: 36px;
  height: 36px;
  flex: 0 0 36px;
  display: grid;
  place-items: center;
  border-radius: 9px;
  background: var(--black);
  color: var(--white);
  font-size: 0.75rem;
}

.summary-copy {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.summary-copy>span {
  color: var(--muted);
  font-size: 0.65rem;
  font-weight: 650;
}

.summary-copy strong {
  margin-top: 3px;
  color: var(--black);
  font-size: 1.08rem;
}

.summary-copy small {
  margin-top: 3px;
  color: var(--light);
  font-size: 0.61rem;
  line-height: 1.35;
}

/* =========================================================
   PANELS
========================================================= */

.panel {
  min-width: 0;
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: var(--white);
  box-shadow: 0 8px 24px rgb(0 0 0 / 3%);
}

.panel-heading,
.leaderboard-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 15px;
}

.panel-heading h2,
.leaderboard-header h2 {
  margin: 3px 0 0;
  color: var(--black);
  font-size: 1rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.text-btn {
  padding: 0;
  border: 0;
  background: transparent;
  color: var(--muted);
}

.text-btn:hover {
  color: var(--black);
}

/* =========================================================
   RECENT ACTIVITY
========================================================= */

.recent-list {
  display: flex;
  flex-direction: column;
}

.recent-item {
  display: grid;
  grid-template-columns: 38px minmax(0, 1fr) auto;
  align-items: center;
  gap: 11px;
  padding: 11px 0;
  border-bottom: 1px solid var(--soft-border);
}

.recent-item:first-child {
  padding-top: 0;
}

.recent-item:last-child {
  padding-bottom: 0;
  border-bottom: 0;
}

.recent-icon {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  border: 1px solid var(--border);
  border-radius: 9px;
  background: var(--surface);
  color: var(--black);
  font-size: 0.7rem;
}

.recent-info {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.recent-info strong {
  overflow: hidden;
  color: var(--black);
  font-size: 0.75rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.recent-info span {
  margin-top: 2px;
  overflow: hidden;
  color: var(--light);
  font-size: 0.61rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.recent-result {
  display: grid;
  grid-template-columns: auto auto auto;
  align-items: center;
  justify-content: end;
  gap: 8px;
}

.recent-result>strong {
  color: var(--black);
  font-size: 0.73rem;
}

.recent-result small {
  color: var(--light);
  font-size: 0.58rem;
  white-space: nowrap;
}

.status-badge {
  padding: 3px 7px;
  border-radius: 999px;
  font-size: 0.56rem;
  font-weight: 750;
}

.status-passed {
  background: var(--black);
  color: var(--white);
}

.status-failed {
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--muted);
}

/* =========================================================
   LEADERBOARD
========================================================= */

.leaderboard-panel {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.leaderboard-header {
  margin-bottom: 0;
}

.leaderboard-title-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
}

.leaderboard-icon-box {
  width: 40px;
  height: 40px;
  flex: 0 0 40px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  background: var(--black);
  color: var(--white);
}

.leaderboard-header p {
  margin: 3px 0 0;
  color: var(--light);
  font-size: 0.67rem;
}

.rank-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 11px;
  border-radius: 999px;
  background: var(--black);
  color: var(--white);
  font-size: 0.67rem;
  font-weight: 700;
}

.rank-pill>span {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--white);
}

.leaderboard-toolbar {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 9px;
  padding: 6px;
  border: 1px solid var(--soft-border);
  border-radius: 11px;
  background: var(--surface);
}

.search-box {
  position: relative;
}

.search-box>i {
  position: absolute;
  top: 50%;
  left: 12px;
  color: var(--light);
  font-size: 0.7rem;
  transform: translateY(-50%);
}

.search-box input,
.filter-box select {
  height: 36px;
  border: 1px solid var(--border);
  border-radius: 8px;
  outline: none;
  background: var(--white);
  color: var(--black);
  font: inherit;
  font-size: 0.72rem;
}

.search-box input {
  width: 100%;
  padding: 0 12px 0 34px;
}

.search-box input:focus,
.filter-box select:focus {
  border-color: var(--black);
}

.filter-box {
  display: flex;
  align-items: center;
  gap: 7px;
}

.filter-box label {
  color: var(--muted);
  font-size: 0.58rem;
  font-weight: 700;
  text-transform: uppercase;
}

.filter-box select {
  min-width: 180px;
  padding: 0 9px;
}

/* PODIUM */

.podium {
  display: flex;
  align-items: flex-end;
  justify-content: center;
  gap: 10px;
  padding: 12px 8px 0;
}

.podium-card {
  max-width: 112px;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
}

.podium-avatar-wrap {
  position: relative;
}

.podium-avatar {
  display: block;
  border: 3px solid var(--border);
  border-radius: 50%;
  object-fit: cover;
}

.podium-1 .podium-avatar {
  width: 58px;
  height: 58px;
  border-color: var(--black);
}

.podium-2 .podium-avatar {
  width: 48px;
  height: 48px;
  border-color: var(--muted);
}

.podium-3 .podium-avatar {
  width: 44px;
  height: 44px;
  border-color: var(--light);
}

.podium-medal {
  position: absolute;
  right: -5px;
  bottom: -5px;
  font-size: 0.9rem;
}

.podium-name {
  max-width: 100%;
  overflow: hidden;
  color: var(--black);
  font-size: 0.68rem;
  font-weight: 700;
  text-align: center;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.you-tag {
  margin-left: 4px;
  padding: 1px 5px;
  border-radius: 999px;
  background: var(--black);
  color: var(--white);
  font-size: 0.51rem;
  vertical-align: middle;
}

.podium-score {
  color: var(--black);
  font-size: 0.67rem;
}

.podium-raw-score {
  color: var(--light);
  font-family: monospace;
  font-size: 0.58rem;
}

.podium-bar {
  width: 100%;
  margin-top: 2px;
  border-radius: 7px 7px 0 0;
}

.podium-1 .podium-bar {
  background: var(--black);
}

.podium-2 .podium-bar {
  background: var(--muted);
}

.podium-3 .podium-bar {
  background: var(--light);
}

/* LIST */

.leaderboard-list {
  display: flex;
  max-height: 430px;
  flex-direction: column;
  gap: 6px;
  overflow-y: auto;
}

.leaderboard-item {
  display: grid;
  grid-template-columns: 30px 38px minmax(0, 1fr) auto;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border: 1px solid transparent;
  border-radius: 11px;
  background: var(--white);
}

.leaderboard-item:hover,
.leaderboard-item.is-top {
  border-color: var(--soft-border);
  background: var(--surface);
}

.leaderboard-item.is-you {
  border-color: var(--black);
}

.rank-cell {
  width: 30px;
  color: var(--light);
  font-family: monospace;
  font-size: 0.7rem;
  font-weight: 700;
  text-align: center;
}

.rank-medal {
  font-size: 1rem;
}

.user-avatar-wrap {
  flex-shrink: 0;
}

.user-avatar {
  display: block;
  width: 36px;
  height: 36px;
  border: 2px solid var(--border);
  border-radius: 50%;
  object-fit: cover;
}

.leaderboard-user {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.leaderboard-user strong {
  overflow: hidden;
  color: var(--black);
  font-size: 0.75rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.leaderboard-user>span {
  margin-top: 2px;
  overflow: hidden;
  color: var(--light);
  font-size: 0.61rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.leaderboard-score {
  min-width: 75px;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.leaderboard-score strong {
  color: var(--black);
  font-size: 0.73rem;
}

.leaderboard-score span,
.leaderboard-score small {
  color: var(--light);
  font-family: monospace;
  font-size: 0.56rem;
}

/* =========================================================
   STATES
========================================================= */

.panel-loading {
  display: flex;
  min-height: 110px;
  align-items: center;
  justify-content: center;
}

.spinner {
  width: 26px;
  height: 26px;
  border: 3px solid var(--border);
  border-top-color: var(--black);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.empty-state {
  min-height: 145px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 5px;
  color: var(--light);
  text-align: center;
}

.empty-state>i {
  margin-bottom: 3px;
  font-size: 1.25rem;
}

.empty-state strong {
  color: var(--muted);
  font-size: 0.75rem;
}

.empty-state span {
  max-width: 360px;
  font-size: 0.62rem;
  line-height: 1.45;
}

.small-primary-btn {
  min-height: 34px;
  margin-top: 8px;
  padding: 6px 11px;
}

.leaderboard-empty {
  min-height: 130px;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1050px) {
  .summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {

  .welcome-card,
  .panel-heading,
  .leaderboard-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .quick-actions {
    width: 100%;
    justify-content: flex-start;
  }

  .leaderboard-toolbar {
    grid-template-columns: 1fr;
  }

  .filter-box {
    justify-content: space-between;
  }

  .filter-box select {
    min-width: 60%;
  }

  .recent-result {
    grid-template-columns: auto;
    gap: 2px;
    justify-items: end;
  }
}

@media (max-width: 560px) {
  .home-page {
    gap: 12px;
  }

  .welcome-card,
  .panel {
    padding: 14px;
    border-radius: 12px;
  }

  .summary-grid {
    grid-template-columns: 1fr 1fr;
    gap: 8px;
  }

  .summary-card {
    padding: 12px;
  }

  .summary-icon {
    width: 32px;
    height: 32px;
    flex-basis: 32px;
  }

  .quick-actions .primary-btn,
  .quick-actions .secondary-btn {
    flex: 1;
  }

  .recent-item {
    grid-template-columns: 34px minmax(0, 1fr) auto;
  }

  .recent-result small,
  .leaderboard-score small {
    display: none;
  }

  .podium {
    gap: 4px;
  }
}

@media (max-width: 420px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }

  .quick-actions {
    display: grid;
    grid-template-columns: 1fr 1fr 38px;
  }

  .quick-actions .primary-btn,
  .quick-actions .secondary-btn {
    width: 100%;
  }

  .leaderboard-item {
    grid-template-columns: 26px 34px minmax(0, 1fr) auto;
    gap: 8px;
    padding: 9px 8px;
  }

  .user-avatar {
    width: 32px;
    height: 32px;
  }

  .podium-1 .podium-avatar {
    width: 48px;
    height: 48px;
  }

  .podium-2 .podium-avatar {
    width: 40px;
    height: 40px;
  }

  .podium-3 .podium-avatar {
    width: 36px;
    height: 36px;
  }
}
</style>
