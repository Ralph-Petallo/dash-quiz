<template>
  <div class="dashboard-wrapper">

    <!-- NOTIFICATION -->
    <div v-if="notification" class="notification">
      <i class="fas fa-bell"></i> {{ notification }}
    </div>

    <div v-if="stats">

      <!-- STATS -->
      <section class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon icon-users">
            <i class="fas fa-users"></i>
          </div>
          <div class="stat-body">
            <span class="stat-label">Total Users</span>
            <span class="stat-value">{{ stats.total_users }}</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon icon-quizzes">
            <i class="fas fa-file-alt"></i>
          </div>
          <div class="stat-body">
            <span class="stat-label">Total Quizzes</span>
            <span class="stat-value">{{ stats.total_quizzes }}</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon icon-active">
            <i class="fas fa-user-check"></i>
          </div>
          <div class="stat-body">
            <span class="stat-label">Active Users</span>
            <span class="stat-value">{{ stats.active_users }}</span>
          </div>
        </div>
      </section>

      <!-- CHART + LEADERBOARD -->
      <div class="main-grid">

        <!-- CHART -->
        <section class="card chart-section">
          <div class="card-header">
            <span class="card-title">
              <i class="fas fa-chart-bar"></i> System Analytics
            </span>
          </div>
          <div class="chart-wrapper">
            <canvas ref="chartCanvas"></canvas>
          </div>
        </section>

        <!-- LEADERBOARD -->
        <section class="card leaderboard-section">
          <div class="card-header">
            <span class="card-title">
              <i class="fas fa-trophy"></i> Top 10 Dashers
            </span>
          </div>

          <div v-if="stats.top_users?.length" class="leader-list">
            <div v-for="(user, index) in stats.top_users" :key="user.id" class="leader-row"
              :title="`${user.first_name} ${user.last_name}`">
              <div class="leader-rank">
                <i v-if="index === 0" class="fas fa-crown crown"></i>
                <span v-else class="rank-num">#{{ index + 1 }}</span>
              </div>

              <div class="leader-avatar">
                <img :draggable="false" :src="`/storage/images/profiles/${user.profile_photo || 'default.png'}`"
                  :width="32" :height="32" class="user-avatar" />
              </div>

              <div class="leader-info">
                <span class="leader-name">{{ user.first_name }} {{ user.last_name }}</span>
                <small class="leader-sub">Top performer</small>
              </div>

              <div class="leader-score">
                <i class="fas fa-star star"></i>
                {{ user.average_score }}%
              </div>
            </div>
          </div>

          <div v-else class="empty-state">No leaderboard data yet.</div>
        </section>

      </div>

      <!-- LOGS -->
      <section class="card logs-section">
        <div class="card-header">
          <span class="card-title">
            <i class="fas fa-clock"></i> Recent Activity
          </span>
          <div class="filter-btns">
            <button :class="{ active: filterType === 'today' }" @click="filterType = 'today'">Today</button>
            <button :class="{ active: filterType === 'week' }" @click="filterType = 'week'">Week</button>
          </div>
        </div>

        <div v-if="filteredLogs.length" class="logs-list">
          <div v-for="log in filteredLogs" :key="log.id" class="log-item">
            <div class="log-icon" :class="log.action_type.toLowerCase()">
            </div>
            <div class="log-body">
              <p class="log-text">{{ log.description }}</p>
              <small class="log-meta">{{ formatDate(log.created_at) }} · Admin #{{ log.admin_id }}</small>
            </div>
            <div class="log-dot"></div>
          </div>
        </div>

        <div v-else class="empty-state">
          <i class="fas fa-inbox"></i> No logs found.
        </div>
      </section>

    </div>

    <!-- LOADING -->
    <div v-else class="loading-state">
      <div class="spinner"></div>
      <p>Loading dashboard...</p>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from "vue"
import Chart from "chart.js/auto"
import { useAdminDashboard } from "@/composables/useAdminDashboard"

const {
  stats,
  fetchStats,
  startAutoRefresh,
  stopAutoRefresh,
  isLoading
} = useAdminDashboard()

const chartCanvas = ref(null)
const notification = ref("")
const filterType = ref("today")

let chartInstance = null

/* =========================
   FILTER LOGS
========================= */
const filteredLogs = computed(() => {
  if (!stats.value?.logs?.length) return []
  console.log(stats.value?.logs?.length)
  const now = new Date()

  return stats.value.logs.filter(log => {
    const logDate = new Date(log.created_at)

    if (filterType.value === "today") {
      return logDate.toDateString() === now.toDateString()
    }

    if (filterType.value === "week") {
      const weekAgo = new Date()
      weekAgo.setDate(now.getDate() - 7)
      console.log(weekAgo)
      return logDate >= weekAgo
    }

    return true
  })
})

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    day: '2-digit', month: '2-digit', year: '2-digit'
  }).replace(/ /g, '/')
}

/* =========================
   CHART
========================= */
const renderChart = () => {
  if (!chartCanvas.value || !stats.value) return

  if (chartInstance) chartInstance.destroy()

  requestAnimationFrame(() => {
    chartInstance = new Chart(chartCanvas.value, {
      type: "bar",
      data: {
        labels: ["System"],
        datasets: [
          {
            label: "Users",
            data: [stats.value.total_users || 0]
          },
          {
            label: "Quizzes",
            data: [stats.value.total_quizzes || 0]
          },
          {
            label: "Active",
            data: [stats.value.active_users || 0]
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false
      }
    })
  })
}

/* =========================
   FETCH + RENDER
========================= */
const loadDashboard = async () => {
  const data = await fetchStats()
  if (!data) return

  await nextTick()
  renderChart()
}

/* =========================
   LIFECYCLE
========================= */
onMounted(async () => {
  await loadDashboard()
  startAutoRefresh()   // 🔥 LIVE UPDATES START
})

onUnmounted(() => {
  stopAutoRefresh()    // prevent memory leak
})
</script>

<style scoped>
*,
*::before,
*::after {
  box-sizing: border-box;
}

/* ── WRAPPER ── */
.dashboard-wrapper {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* ── NOTIFICATION ── */
.notification {
  position: fixed;
  top: 80px;
  right: 16px;
  z-index: 999;
  background: #ecfdf5;
  color: #065f46;
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 0.85rem;
  border: 1px solid #a7f3d0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* ── STATS ── */
.stats-grid {
  display: grid;
  margin-bottom: 10px;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.stat-card {
  background: #fff;
  border-radius: 14px;
  padding: 1.25rem;
  border: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.icon-users {
  background: rgba(139, 92, 246, 0.1);
  color: #8b5cf6;
}

.icon-quizzes {
  background: rgba(22, 163, 74, 0.1);
  color: #16a34a;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1.5px solid #6366f1;
  object-fit: cover;
}

.icon-active {
  background: rgba(75, 60, 209, 0.1);
  color: #4b3cd1;
}

.stat-body {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #94a3b8;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
}

/* ── MAIN GRID ── */
.main-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1rem;
  align-items: start;
}

/* ── CARD BASE ── */
.card {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #f1f5f9;
  gap: 8px;
  flex-wrap: wrap;
}

.card-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-title i {
  color: #6366f1;
}

/* ── CHART ── */
.chart-section {
  text-align: center;
}

.chart-wrapper {
  width: 100%;
  height: 280px;
  padding: 1rem 1.25rem 1.25rem;
  position: relative;
}

/* ── LEADERBOARD ── */
.leaderboard-section {
  margin-bottom: 10px;
}


.leader-list::-webkit-scrollbar {
  width: 4px;
}

.leader-list::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 4px;
}

.leader-row {
  display: grid;
  grid-template-columns: 32px 36px 1fr auto;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  border-radius: 10px;
  transition: background 0.15s;
  cursor: default;
}

.leader-row:hover {
  background: #f8fafc;
}

.leader-rank {
  text-align: center;
  font-size: 0.78rem;
  font-weight: 700;
  color: #94a3b8;
}

.crown {
  color: #fbbf24;
  font-size: 1rem;
}

.rank-num {
  font-size: 0.75rem;
  font-weight: 700;
  color: #94a3b8;
}


.leader-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.leader-name {
  font-size: 0.83rem;
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.leader-sub {
  font-size: 0.68rem;
  color: #94a3b8;
}

.leader-score {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.82rem;
  font-weight: 700;
  color: #16a34a;
  white-space: nowrap;
  flex-shrink: 0;
}

.star {
  color: #facc15;
  font-size: 0.75rem;
}

/* ── LOGS ── */
.logs-section {
  margin-top: 0;
}

.filter-btns {
  display: flex;
  gap: 6px;
}

.filter-btns button {
  padding: 4px 12px;
  font-size: 0.75rem;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  background: #f9fafb;
  cursor: pointer;
  font-weight: 500;
  transition: 0.2s;
  color: #64748b;
}

.filter-btns button.active {
  background: #6366f1;
  color: white;
  border-color: #6366f1;
}

.logs-list {
  display: flex;
  flex-direction: column;
  gap: 2px;
  max-height: 340px;
  overflow-y: auto;
  padding: 0.5rem;
}

.logs-list::-webkit-scrollbar {
  width: 4px;
}

.logs-list::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 4px;
}

.log-item {
  display: grid;
  grid-template-columns: 36px 1fr 8px;
  gap: 10px;
  align-items: start;
  padding: 10px 12px;
  border-radius: 10px;
  transition: background 0.15s;
}

.log-item:hover {
  background: #f8fafc;
}

.log-icon {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: grid;
  place-items: center;
  font-size: 0.82rem;
  flex-shrink: 0;
}

.log-delete {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.log-create {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.log-update {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.log-info {
  background: rgba(100, 116, 139, 0.1);
  color: #64748b;
}

.log-body {
  min-width: 0;
}

.log-text {
  font-size: 0.84rem;
  color: #1e293b;
  margin: 0 0 2px;
  line-height: 1.4;
}

.log-meta {
  font-size: 0.7rem;
  color: #94a3b8;
}

.log-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #e2e8f0;
  margin-top: 14px;
  justify-self: center;
}

/* ── EMPTY / LOADING ── */
.empty-state {
  text-align: center;
  padding: 2rem 1rem;
  color: #94a3b8;
  font-size: 0.85rem;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 40vh;
  gap: 1rem;
  color: #64748b;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #f1f5f9;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ── RESPONSIVE ── */

/* LG: ≤1024px — stack chart/leaderboard */
@media (max-width: 1024px) {
  .main-grid {
    grid-template-columns: 1fr;
  }

  .chart-wrapper {
    height: 260px;
  }
}

/* MD: ≤768px */
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .chart-wrapper {
    height: 220px;
  }

  .leader-row {
    grid-template-columns: 28px 32px 1fr auto;
    padding: 8px 10px;
    gap: 6px;
  }
}

/* SM: ≤576px */
@media (max-width: 576px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .stat-card {
    padding: 1rem;
  }

  .stat-value {
    font-size: 1.5rem;
  }

  .chart-wrapper {
    height: 200px;
  }

  .leader-row {
    grid-template-columns: 28px 32px 1fr;
    gap: 6px;
  }

  .leader-score {
    grid-column: 3;
    justify-content: flex-end;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
  }
}

/* XS: ≤400px */
@media (max-width: 400px) {
  .stat-icon {
    width: 40px;
    height: 40px;
    font-size: 1rem;
  }

  .stat-value {
    font-size: 1.35rem;
  }
}
</style>