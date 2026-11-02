<template>
  <div class="dashboard-wrapper">
    <Transition name="notification">
      <div v-if="notification" class="notification">
        <i class="fas fa-bell"></i>
        <span>{{ notification }}</span>
      </div>
    </Transition>

    <div v-if="stats">
      <header class="dashboard-header">
        <div>
          <span class="eyebrow">ADMINISTRATION</span>
          <h1>Dashboard</h1>
          <p>System overview and recent activity.</p>
        </div>
      </header>

      <section class="stats-grid">
        <article class="stat-card">
          <div class="stat-top">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <span class="stat-index">01</span>
          </div>
          <div class="stat-content">
            <span class="stat-label">Total Users</span>
            <strong class="stat-value">{{ stats.total_users ?? 0 }}</strong>
          </div>
          <div class="stat-footer">
            <span>Registered Dashers</span>
            <i class="fas fa-arrow-up"></i>
          </div>
        </article>

        <article class="stat-card">
          <div class="stat-top">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <span class="stat-index">02</span>
          </div>
          <div class="stat-content">
            <span class="stat-label">Certificates of Competency</span>
            <strong class="stat-value">{{ stats.total_quizzes ?? 0 }}</strong>
          </div>
          <div class="stat-footer">
            <span>Published certificates</span>
            <i class="fas fa-arrow-up"></i>
          </div>
        </article>

        <article class="stat-card">
          <div class="stat-top">
            <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
            <span class="stat-index">03</span>
          </div>
          <div class="stat-content">
            <span class="stat-label">Assessments</span>
            <strong class="stat-value">{{ stats.total_assessments ?? 0 }}</strong>
          </div>
          <div class="stat-footer">
            <span>Competency assessments</span>
            <i class="fas fa-arrow-up"></i>
          </div>
        </article>

        <article class="stat-card stat-card-active">
          <div class="stat-top">
            <div class="stat-icon">
              <span class="live-indicator"></span>
              <i class="fas fa-user-check"></i>
            </div>
            <span class="stat-index">04</span>
          </div>
          <div class="stat-content">
            <span class="stat-label">Active Users</span>
            <strong class="stat-value">{{ stats.active_users ?? 0 }}</strong>
          </div>
          <div class="stat-footer">
            <span>Currently active</span>
            <span class="active-label">LIVE</span>
          </div>
        </article>
      </section>

      <div class="main-grid">
        <section class="card analytics-card">
          <div class="card-header">
            <div class="header-title">
              <div class="header-icon"><i class="fas fa-chart-column"></i></div>
              <div>
                <span class="card-title">System Analytics</span>
                <span class="card-subtitle">Current platform statistics</span>
              </div>
            </div>

            <div class="chart-legend">
              <span><i class="legend-users"></i>Users</span>
              <span><i class="legend-coc"></i>COCs</span>
              <span><i class="legend-assessments"></i>Assessments</span>
              <span><i class="legend-active"></i>Active</span>
            </div>
          </div>

          <div class="chart-wrapper">
            <canvas ref="chartCanvas"></canvas>
          </div>
        </section>

        <section class="card leaderboard-section">
          <div class="card-header leaderboard-header">
            <div class="header-title">
              <div class="header-icon"><i class="fas fa-trophy"></i></div>
              <div>
                <span class="card-title">Leaderboard</span>
                <span class="card-subtitle">
                  Ranked by average score · minimum 2 completed assessments
                </span>
              </div>
            </div>

            <span class="count-label">TOP 10</span>
          </div>

          <div class="leaderboard-mode-tabs" role="tablist" aria-label="Leaderboard type">
            <button type="button" role="tab" :aria-selected="leaderboardMode === 'coc'"
              :class="{ active: leaderboardMode === 'coc' }" @click="leaderboardMode = 'coc'">
              By COC
            </button>

            <button type="button" role="tab" :aria-selected="leaderboardMode === 'assessment'"
              :class="{ active: leaderboardMode === 'assessment' }" @click="leaderboardMode = 'assessment'">
              By assessment
            </button>
          </div>

          <div v-if="leaderboardTabs.length" class="leaderboard-tabs">
            <button v-for="tab in leaderboardTabs" :key="tab.key" type="button"
              :class="{ active: selectedLeaderboard === tab.key }" :aria-pressed="selectedLeaderboard === tab.key"
              @click="selectLeaderboard(tab.key)">
              {{ tab.label }}
            </button>
          </div>

          <div v-if="isAssessmentLoading && leaderboardMode === 'assessment'" class="empty-state">
            <span>Loading assessment leaderboard…</span>
          </div>

          <div v-else-if="selectedLeaderboardUsers.length" class="leader-list">
            <div v-for="(user, index) in selectedLeaderboardUsers" :key="user.id" class="leader-row"
              :title="`${user.first_name || ''} ${user.last_name || ''}`.trim()">
              <div class="leader-rank">
                <span :class="{ 'first-rank': index === 0 }">
                  {{ String(index + 1).padStart(2, '0') }}
                </span>
              </div>

              <div class="leader-avatar">
                <img :src="profilePhotoUrl(user.profile_photo)"
                  :alt="`${user.first_name || ''} ${user.last_name || ''}`.trim()" draggable="false" class="user-avatar"
                  @error="useDefaultAvatar" />
              </div>

              <div class="leader-info">
                <span class="leader-name">
                  {{ user.first_name }} {{ user.last_name }}
                </span>
                <small>
                  {{ user.completed_quizzes }}
                  {{ Number(user.completed_quizzes) === 1 ? 'assessment' : 'assessments' }}
                </small>
              </div>

              <div class="leader-score">
                {{ formatScore(user.average_score) }}%
              </div>
            </div>
          </div>

          <div v-else-if="leaderboardTabs.length" class="empty-state">
            <i class="fas fa-users-slash"></i>
            <span>No eligible scores for this {{ leaderboardMode }} yet.</span>
          </div>

          <div v-else class="empty-state">
            <i class="fas fa-trophy"></i>
            <span>No {{ leaderboardMode }} leaderboard data available.</span>
          </div>
        </section>
      </div>

      <section class="card logs-section">
        <div class="card-header">
          <div class="header-title">
            <div class="header-icon"><i class="fas fa-clock-rotate-left"></i></div>
            <div>
              <span class="card-title">Recent Activity</span>
              <span class="card-subtitle">Latest system actions</span>
            </div>
          </div>

          <div class="filter-btns">
            <button type="button" :class="{ active: filterType === 'today' }" @click="filterType = 'today'">
              Today
            </button>
            <button type="button" :class="{ active: filterType === 'week' }" @click="filterType = 'week'">
              7 Days
            </button>
          </div>
        </div>

        <div v-if="filteredLogs.length" class="logs-list">
          <div v-for="log in filteredLogs" :key="log.id" class="log-item">
            <div class="log-icon" :class="log.action_type?.toLowerCase()">
              <i v-if="log.action_type?.toLowerCase() === 'create'" class="fas fa-plus"></i>
              <i v-else-if="log.action_type?.toLowerCase() === 'update'" class="fas fa-pen"></i>
              <i v-else-if="log.action_type?.toLowerCase() === 'delete'" class="fas fa-trash"></i>
              <i v-else class="fas fa-circle-info"></i>
            </div>

            <div class="log-body">
              <p class="log-text">{{ log.description }}</p>
              <small class="log-meta">
                {{ formatDate(log.created_at) }}
                <span>·</span>
                Admin #{{ log.admin_id ?? 'N/A' }}
              </small>
            </div>

            <div class="log-status"></div>
          </div>
        </div>

        <div v-else class="empty-state">
          <i class="fas fa-inbox"></i>
          <span>No activity found.</span>
        </div>
      </section>
    </div>

    <div v-else class="loading-state">
      <div class="loading-mark"><span></span></div>
      <div>
        <strong>Loading dashboard</strong>
        <p>Please wait...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import Chart from 'chart.js/auto'
import { useAdminDashboard } from '@/composables/useAdminDashboard'

const {
  stats,
  fetchStats,
  fetchAssessmentLeaderboard,
  assessmentLeaderboard,
  isAssessmentLoading,
  startAutoRefresh,
  stopAutoRefresh,
} = useAdminDashboard()

const chartCanvas = ref(null)
const notification = ref('')
const filterType = ref('today')
const leaderboardMode = ref('coc')
const selectedLeaderboard = ref(null)

let chartInstance = null

const leaderboardTabs = computed(() => {
  if (leaderboardMode.value === 'assessment') {
    return (stats.value?.leaderboards?.assessmentTypes ?? []).map(type => ({
      key: type,
      label: type,
      users: [],
    }))
  }

  const groups = stats.value?.leaderboards?.coc ?? {}

  if (!groups || typeof groups !== 'object') {
    return []
  }

  return Object.entries(groups).map(([key, leaderboard]) => ({
    key,
    label: leaderboard?.label || key.replaceAll('_', ' ').toUpperCase(),
    users: Array.isArray(leaderboard?.users) ? leaderboard.users : [],
  }))
})

const selectedLeaderboardUsers = computed(() => {
  if (leaderboardMode.value === 'assessment') {
    return assessmentLeaderboard.value?.type === selectedLeaderboard.value
      ? assessmentLeaderboard.value.users ?? []
      : []
  }

  return leaderboardTabs.value.find(
    tab => tab.key === selectedLeaderboard.value
  )?.users ?? []
})

const selectLeaderboard = async key => {
  selectedLeaderboard.value = key

  if (leaderboardMode.value === 'assessment') {
    await fetchAssessmentLeaderboard(key)
  }
}

watch(
  [leaderboardMode, leaderboardTabs],
  async () => {
    const tabs = leaderboardTabs.value

    if (!tabs.some(tab => tab.key === selectedLeaderboard.value)) {
      selectedLeaderboard.value = tabs[0]?.key ?? null
    }

    if (leaderboardMode.value === 'assessment' && selectedLeaderboard.value) {
      await fetchAssessmentLeaderboard(selectedLeaderboard.value)
    }
  },
  { immediate: true }
)

const filteredLogs = computed(() => {
  const logs = stats.value?.logs

  if (!Array.isArray(logs)) return []

  const now = new Date()

  return logs.filter(log => {
    if (!log?.created_at) return false

    const logDate = new Date(log.created_at)
    if (Number.isNaN(logDate.getTime())) return false

    if (filterType.value === 'today') {
      return logDate.toDateString() === now.toDateString()
    }

    if (filterType.value === 'week') {
      const weekAgo = new Date(now)
      weekAgo.setDate(now.getDate() - 7)
      return logDate >= weekAgo && logDate <= now
    }

    return true
  })
})

const formatDate = dateString => {
  if (!dateString) return 'N/A'

  const date = new Date(dateString)
  if (Number.isNaN(date.getTime())) return 'N/A'

  return date.toLocaleDateString('en-US', {
    month: '2-digit',
    day: '2-digit',
    year: '2-digit',
  })
}

const formatScore = score => {
  const value = Number(score)
  return Number.isFinite(value) ? value.toFixed(1) : '0.0'
}

const profilePhotoUrl = photo => `/storage/images/profiles/${photo || 'default.png'}`

const useDefaultAvatar = event => {
  event.target.src = '/storage/images/profiles/default.png'
}

const renderChart = async () => {
  await nextTick()

  if (!chartCanvas.value || !stats.value) return

  chartInstance?.destroy()

  chartInstance = new Chart(chartCanvas.value, {
    type: 'bar',
    data: {
      labels: ['Users', 'COCs', 'Assessments', 'Active'],
      datasets: [{
        label: 'System Count',
        data: [
          Number(stats.value.total_users) || 0,
          Number(stats.value.total_quizzes) || 0,
          Number(stats.value.total_assessments) || 0,
          Number(stats.value.active_users) || 0,
        ],
        backgroundColor: ['#111111', '#555555', '#888888', '#bbbbbb'],
        borderRadius: 2,
        borderSkipped: false,
        barThickness: 70,
        maxBarThickness: 80,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 400 },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#111111',
          titleColor: '#ffffff',
          bodyColor: '#ffffff',
          padding: 12,
          cornerRadius: 6,
          displayColors: false,
        },
      },
      scales: {
        x: {
          grid: { display: false },
          border: { display: false },
          ticks: { color: '#333', font: { size: 11, weight: '600' } },
        },
        y: {
          beginAtZero: true,
          border: { display: false },
          grid: { color: '#eeeeee' },
          ticks: {
            color: '#999999',
            padding: 8,
            font: { size: 10 },
            precision: 0,
          },
        },
      },
    },
  })
}

const loadDashboard = async () => {
  const data = await fetchStats()
  if (data) await renderChart()
}

watch(
  stats,
  async value => {
    if (value) await renderChart()
  },
  { deep: true }
)

onMounted(async () => {
  await loadDashboard()
  startAutoRefresh()
})

onUnmounted(() => {
  stopAutoRefresh()
  chartInstance?.destroy()
  chartInstance = null
})
</script>

<style scoped>
*,
*::before,
*::after {
  box-sizing: border-box;
}

.dashboard-wrapper {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 16px;
  color: #111;
}

.dashboard-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
  padding: 2px 2px 4px;
}

.eyebrow {
  display: block;
  margin-bottom: 5px;
  color: #999;
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.14em;
}

.dashboard-header h1 {
  margin: 0;
  color: #111;
  font-size: 1.65rem;
  font-weight: 750;
  letter-spacing: -0.04em;
}

.dashboard-header p {
  margin: 5px 0 0;
  color: #888;
  font-size: 0.76rem;
}


.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: green;
}

.notification {
  position: fixed;
  top: 80px;
  right: 18px;
  z-index: 999;
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 10px 13px;
  background: #111;
  color: #fff;
  border: 1px solid #333;
  border-radius: 6px;
  font-size: 0.74rem;
  box-shadow: 0 10px 30px rgb(0 0 0 / 15%);
}

.notification i {
  color: #bbb;
}

.stats-grid {
  width: 100%;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.stat-card {
  min-width: 0;
  min-height: 156px;
  display: flex;
  flex-direction: column;
  padding: 15px 16px 13px;
  background: #fff;
  border: 1px solid #e4e4e4;
  border-radius: 8px;
  transition: border-color 0.15s ease, transform 0.15s ease;
}

.stat-card:hover {
  border-color: #cfcfcf;
  transform: translateY(-1px);
}

.stat-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.stat-icon {
  position: relative;
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  background: #f3f3f3;
  border: 1px solid #e8e8e8;
  border-radius: 6px;
  color: #222;
  font-size: 0.78rem;
}

.stat-index {
  color: #c2c2c2;
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.08em;
}

.stat-content {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: auto;
}

.stat-label {
  color: #777;
  font-size: 0.66rem;
  font-weight: 650;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.stat-value {
  color: #111;
  font-size: 1.7rem;
  font-weight: 750;
  line-height: 1;
  letter-spacing: -0.04em;
}

.stat-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-top: 14px;
  padding-top: 10px;
  border-top: 1px solid #f0f0f0;
  color: #aaa;
  font-size: 0.61rem;
}

.stat-footer>i {
  color: #888;
  font-size: 0.58rem;
}

.active-label {
  color: green;
  font-size: 0.58rem;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.live-indicator {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #111;
}

.main-grid {
  width: 100%;
  display: grid;
  grid-template-columns: minmax(0, 1.7fr) minmax(300px, 0.9fr);
  gap: 10px;
  align-items: stretch;
  margin: 10px 0;
}

.card {
  min-width: 0;
  overflow: hidden;
  background: #fff;
  border: 1px solid #e4e4e4;
  border-radius: 8px;
}

.card-header {
  min-height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 15px;
  border-bottom: 1px solid #ededed;
}

.header-title {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.header-icon {
  width: 30px;
  height: 30px;
  flex: 0 0 30px;
  display: grid;
  place-items: center;
  background: #f4f4f4;
  border: 1px solid #e9e9e9;
  border-radius: 5px;
  color: #444;
  font-size: 0.7rem;
}

.card-title {
  display: block;
  color: #222;
  font-size: 0.78rem;
  font-weight: 700;
}

.card-subtitle {
  display: block;
  margin-top: 2px;
  color: #aaa;
  font-size: 0.62rem;
}

.count-label {
  flex: 0 0 auto;
  color: #aaa;
  font-size: 0.59rem;
  font-weight: 700;
  letter-spacing: 0.08em;
}

.chart-wrapper {
  position: relative;
  width: 100%;
  height: 350px;
  padding: 14px 18px 10px;
}

.chart-legend {
  display: flex;
  align-items: center;
  gap: 12px;
}

.chart-legend span {
  display: flex;
  align-items: center;
  gap: 5px;
  color: #999;
  font-size: 0.6rem;
}

.chart-legend i {
  width: 6px;
  height: 6px;
  display: block;
  border-radius: 1px;
}

.legend-users {
  background: #111;
}

.legend-coc {
  background: #555;
}

.legend-assessments {
  background: #888;
}

.legend-active {
  background: #bbb;
}

.leaderboard-header {
  flex-wrap: wrap;
}

.leaderboard-mode-tabs,
.leaderboard-tabs {
  display: flex;
  gap: 5px;
  padding: 9px 12px;
  overflow-x: auto;
  border-bottom: 1px solid #ededed;
  scrollbar-width: thin;
}

.leaderboard-mode-tabs {
  background: #fafafa;
}

.leaderboard-mode-tabs button,
.leaderboard-tabs button {
  flex: 0 0 auto;
  padding: 6px 10px;
  border: 1px solid #dedede;
  border-radius: 5px;
  background: #fff;
  color: #777;
  font-size: 0.62rem;
  font-weight: 650;
  white-space: nowrap;
  cursor: pointer;
}

.leaderboard-mode-tabs button:hover,
.leaderboard-tabs button:hover {
  background: #f5f5f5;
  color: #222;
}

.leaderboard-mode-tabs button.active,
.leaderboard-tabs button.active {
  border-color: #111;
  background: #111;
  color: #fff;
}

.leader-list {
  width: 100%;
  max-height: 300px;
  overflow-y: auto;
  padding: 5px;
}

.leader-list::-webkit-scrollbar,
.logs-list::-webkit-scrollbar {
  width: 3px;
}

.leader-list::-webkit-scrollbar-thumb,
.logs-list::-webkit-scrollbar-thumb {
  border-radius: 4px;
  background: #d5d5d5;
}

.leader-row {
  min-height: 55px;
  display: grid;
  grid-template-columns: 30px 34px minmax(0, 1fr) auto;
  align-items: center;
  gap: 8px;
  padding: 6px 8px;
  border-bottom: 1px solid #f1f1f1;
}

.leader-row:last-child {
  border-bottom: 0;
}

.leader-row:hover,
.log-item:hover {
  background: #fafafa;
}

.leader-rank {
  color: #aaa;
  font-size: 0.62rem;
  font-weight: 700;
  text-align: center;
}

.first-rank {
  color: #111;
}

.leader-avatar,
.user-avatar {
  width: 34px;
  height: 34px;
}

.user-avatar {
  display: block;
  object-fit: cover;
  background: #f3f3f3;
  border: 1px solid #ddd;
  border-radius: 50%;
}

.leader-info {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.leader-name {
  min-width: 0;
  overflow: hidden;
  color: #222;
  font-size: 0.72rem;
  font-weight: 650;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.leader-info small {
  color: #aaa;
  font-size: 0.6rem;
}

.leader-score {
  color: #111;
  font-size: 0.72rem;
  font-weight: 750;
  white-space: nowrap;
}

.logs-section {
  width: 100%;
}

.filter-btns {
  display: flex;
  flex: 0 0 auto;
  gap: 4px;
}

.filter-btns button {
  min-width: 58px;
  padding: 5px 9px;
  border: 1px solid #dedede;
  border-radius: 5px;
  background: #fff;
  color: #888;
  font-size: 0.62rem;
  font-weight: 650;
  cursor: pointer;
  transition: 0.15s ease;
}

.filter-btns button:hover {
  background: #f5f5f5;
  color: #222;
}

.filter-btns button.active {
  border-color: #111;
  background: #111;
  color: #fff;
}

.logs-list {
  width: 100%;
  max-height: 330px;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  padding: 5px;
}

.log-item {
  min-height: 58px;
  display: grid;
  grid-template-columns: 34px minmax(0, 1fr) 6px;
  align-items: center;
  gap: 10px;
  padding: 8px 9px;
  border-bottom: 1px solid #f1f1f1;
}

.log-item:last-child {
  border-bottom: 0;
}

.log-icon {
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  background: #f3f3f3;
  border: 1px solid #e9e9e9;
  border-radius: 5px;
  color: #555;
  font-size: 0.65rem;
}

.log-body {
  min-width: 0;
}

.log-text {
  margin: 0 0 3px;
  color: #333;
  font-size: 0.72rem;
  line-height: 1.4;
  overflow-wrap: anywhere;
}

.log-meta {
  display: flex;
  align-items: center;
  gap: 5px;
  color: #aaa;
  font-size: 0.6rem;
}

.log-status {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #c9c9c9;
}

.empty-state {
  min-height: 150px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 20px;
  color: #aaa;
  font-size: 0.7rem;
  text-align: center;
}

.empty-state i {
  color: #c5c5c5;
  font-size: 1rem;
}

.loading-state {
  min-height: 40vh;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #777;
}

.loading-state strong {
  display: block;
  color: #333;
  font-size: 0.75rem;
}

.loading-state p {
  margin: 3px 0 0;
  color: #aaa;
  font-size: 0.62rem;
}

.loading-mark {
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  border: 2px solid #e5e5e5;
  border-top-color: #111;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.notification-enter-active,
.notification-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.notification-enter-from,
.notification-leave-to {
  opacity: 0;
  transform: translateY(-5px);
}

@media (max-width: 1050px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .main-grid {
    grid-template-columns: minmax(0, 1.3fr) minmax(280px, 0.9fr);
  }
}

@media (max-width: 850px) {
  .main-grid {
    grid-template-columns: 1fr;
  }

  .leader-list {
    max-height: none;
  }

  .chart-wrapper {
    height: 240px;
  }
}

@media (max-width: 600px) {
  .dashboard-wrapper {
    gap: 10px;
  }

  .dashboard-header {
    align-items: flex-start;
    flex-direction: column;
    gap: 10px;
  }

  .dashboard-header h1 {
    font-size: 1.4rem;
  }

  .header-status {
    align-self: flex-start;
  }

  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
  }

  .stat-card {
    min-height: 145px;
    padding: 13px;
  }

  .stat-value {
    font-size: 1.45rem;
  }

  .stat-footer {
    font-size: 0.57rem;
  }

  .card-header {
    min-height: 58px;
    padding: 9px 12px;
  }

  .chart-legend {
    display: none;
  }

  .chart-wrapper {
    height: 220px;
    padding: 14px;
  }

  .leader-row {
    grid-template-columns: 27px 32px minmax(0, 1fr) auto;
    gap: 7px;
  }

  .leader-avatar,
  .user-avatar {
    width: 32px;
    height: 32px;
  }
}

@media (max-width: 420px) {

  .stat-card {
    min-height: 115px;
    display: grid;
    grid-template-columns: auto 1fr;
    column-gap: 12px;
    padding: 12px;
  }

  .stat-top {
    grid-row: span 2;
    align-items: flex-start;
  }

  .stat-index {
    display: none;
  }

  .stat-content {
    margin-top: 0;
  }

  .stat-footer {
    margin-top: 7px;
    padding-top: 7px;
  }

  .card-title {
    font-size: 0.72rem;
  }

  .card-subtitle {
    font-size: 0.58rem;
  }

  .filter-btns button {
    min-width: 50px;
    padding: 4px 7px;
  }
}
</style>