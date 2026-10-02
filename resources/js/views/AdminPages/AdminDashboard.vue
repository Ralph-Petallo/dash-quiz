<template>
  <div class="dashboard-wrapper">

    <!-- NOTIFICATION -->
    <Transition name="notification">
      <div v-if="notification" class="notification">
        <i class="fas fa-bell"></i>
        <span>{{ notification }}</span>
      </div>
    </Transition>

    <div v-if="stats">

      <!-- =====================================================
           DASHBOARD HEADER
      ====================================================== -->
      <header class="dashboard-header">
        <div>
          <span class="eyebrow">ADMINISTRATION</span>
          <h1>Dashboard</h1>
          <p>System overview and recent activity.</p>
        </div>

        <div class="header-status">
          <span class="status-dot"></span>
          System active
        </div>
      </header>


      <!-- =====================================================
           STATS
      ====================================================== -->
      <section class="stats-grid">

        <!-- USERS -->
        <article class="stat-card">
          <div class="stat-top">
            <div class="stat-icon">
              <i class="fas fa-users"></i>
            </div>

            <span class="stat-index">01</span>
          </div>

          <div class="stat-content">
            <span class="stat-label">Total Users</span>
            <strong class="stat-value">
              {{ stats.total_users || 0 }}
            </strong>
          </div>

          <div class="stat-footer">
            <span>Registered Dashers</span>
            <i class="fas fa-arrow-up"></i>
          </div>
        </article>


        <!-- QUIZZES -->
        <article class="stat-card">
          <div class="stat-top">
            <div class="stat-icon">
              <i class="fas fa-file-alt"></i>
            </div>

            <span class="stat-index">02</span>
          </div>

          <div class="stat-content">
            <span class="stat-label">Certificates of Competency</span>
            <strong class="stat-value">
              {{ stats.total_quizzes || 0 }}
            </strong>
          </div>

          <div class="stat-footer">
            <span>Published certificate of competency</span>
            <i class="fas fa-arrow-up"></i>
          </div>
        </article>


        <!-- ASSESSMENTS -->
        <article class="stat-card">
          <div class="stat-top">
            <div class="stat-icon">
              <i class="fas fa-layer-group"></i>
            </div>

            <span class="stat-index">03</span>
          </div>

          <div class="stat-content">
            <span class="stat-label">Assessments</span>
            <strong class="stat-value">
              {{ stats.total_assessments || 0 }}
            </strong>
          </div>

          <div class="stat-footer">
            <span>Competency assessments</span>
            <i class="fas fa-arrow-up"></i>
          </div>
        </article>


        <!-- ACTIVE USERS -->
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
            <strong class="stat-value">
              {{ stats.active_users || 0 }}
            </strong>
          </div>

          <div class="stat-footer">
            <span>Currently active</span>
            <span class="active-label">LIVE</span>
          </div>
        </article>

      </section>


      <!-- =====================================================
           MAIN CONTENT
      ====================================================== -->
      <div class="main-grid">

        <!-- ===================================================
             ANALYTICS
        ==================================================== -->
        <section class="card analytics-card">

          <div class="card-header">
            <div class="header-title">
              <div class="header-icon">
                <i class="fas fa-chart-column"></i>
              </div>

              <div>
                <span class="card-title">System Analytics</span>
                <span class="card-subtitle">
                  Current platform statistics
                </span>
              </div>
            </div>

            <div class="chart-legend">
              <span>
                <i class="legend-users"></i>
                Users
              </span>

              <span>
                <i class="legend-content"></i>
                Content
              </span>

              <span>
                <i class="legend-active"></i>
                Active
              </span>
            </div>
          </div>

          <div class="chart-wrapper">
            <canvas ref="chartCanvas"></canvas>
          </div>

        </section>


        <!-- ===================================================
             LEADERBOARD
        ==================================================== -->
        <section class="card leaderboard-section">

          <div class="card-header">
            <div class="header-title">
              <div class="header-icon">
                <i class="fas fa-trophy"></i>
              </div>

              <div>
                <span class="card-title">Top Dashers</span>
                <span class="card-subtitle">
                  Highest average scores
                </span>
              </div>
            </div>

            <span class="count-label">
              TOP 10
            </span>
          </div>


          <div v-if="stats.top_users?.length" class="leader-list">

            <div v-for="(user, index) in stats.top_users" :key="user.id" class="leader-row"
              :title="`${user.first_name} ${user.last_name}`">

              <div class="leader-rank">
                <span v-if="index === 0" class="first-rank">
                  01
                </span>

                <span v-else>
                  {{ String(index + 1).padStart(2, '0') }}
                </span>
              </div>


              <div class="leader-avatar">
                <img :src="`/storage/images/profiles/${user.profile_photo || 'default.png'}`"
                  :alt="`${user.first_name} ${user.last_name}`" draggable="false" class="user-avatar" />
              </div>


              <div class="leader-info">
                <span class="leader-name">
                  {{ user.first_name }} {{ user.last_name }}
                </span>

                <small>
                  Dasher
                </small>
              </div>


              <div class="leader-score">
                <span>{{ user.average_score }}%</span>
              </div>

            </div>

          </div>


          <div v-else class="empty-state">
            <i class="fas fa-users-slash"></i>
            <span>No leaderboard data yet.</span>
          </div>

        </section>

      </div>


      <!-- =====================================================
           RECENT ACTIVITY
      ====================================================== -->
      <section class="card logs-section">

        <div class="card-header">

          <div class="header-title">
            <div class="header-icon">
              <i class="fas fa-clock-rotate-left"></i>
            </div>

            <div>
              <span class="card-title">Recent Activity</span>
              <span class="card-subtitle">
                Latest system actions
              </span>
            </div>
          </div>


          <div class="filter-btns">

            <button :class="{ active: filterType === 'today' }" @click="filterType = 'today'">
              Today
            </button>

            <button :class="{ active: filterType === 'week' }" @click="filterType = 'week'">
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
              <p class="log-text">
                {{ log.description }}
              </p>

              <small class="log-meta">
                {{ formatDate(log.created_at) }}
                <span>·</span>
                Admin #{{ log.admin_id }}
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


    <!-- =======================================================
         LOADING
    ======================================================== -->
    <div v-else class="loading-state">

      <div class="loading-mark">
        <span></span>
      </div>

      <div>
        <strong>Loading dashboard</strong>
        <p>Please wait...</p>
      </div>

    </div>

  </div>
</template>


<script setup>
import {
  ref,
  computed,
  nextTick,
  onMounted,
  onUnmounted,
  watch
} from "vue"

import Chart from "chart.js/auto"

import { useAdminDashboard } from "@/composables/useAdminDashboard"


/* =========================================================
   DASHBOARD
========================================================= */

const {
  stats,
  fetchStats,
  startAutoRefresh,
  stopAutoRefresh
} = useAdminDashboard()


/* =========================================================
   STATE
========================================================= */

const chartCanvas = ref(null)
const notification = ref("")
const filterType = ref("today")

let chartInstance = null


/* =========================================================
   FILTER LOGS
========================================================= */

const filteredLogs = computed(() => {

  if (!stats.value?.logs?.length) {
    return []
  }

  const now = new Date()

  return stats.value.logs.filter(log => {

    const logDate = new Date(
      log.created_at
    )

    if (filterType.value === "today") {

      return (
        logDate.toDateString() ===
        now.toDateString()
      )
    }

    if (filterType.value === "week") {

      const weekAgo = new Date()

      weekAgo.setDate(
        now.getDate() - 7
      )

      return logDate >= weekAgo
    }

    return true
  })
})


/* =========================================================
   DATE
========================================================= */

const formatDate = (dateString) => {

  if (!dateString) {
    return "N/A"
  }

  const date = new Date(dateString)

  return date
    .toLocaleDateString("en-US", {
      day: "2-digit",
      month: "2-digit",
      year: "2-digit"
    })
    .replace(/ /g, "/")
}


/* =========================================================
   CHART
========================================================= */

const renderChart = async () => {

  if (!chartCanvas.value || !stats.value) {
    return
  }

  await nextTick()

  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }

  chartInstance = new Chart(
    chartCanvas.value,
    {
      type: "bar",

      data: {

        labels: [
          "Users",
          "COCs",
          "Assessments",
          "Active"
        ],

        datasets: [
          {
            label: "System Count",

            data: [
              stats.value.total_users ?? 0,
              stats.value.total_quizzes ?? 0,
              stats.value.total_assessments ?? 0,
              stats.value.active_users ?? 0
            ],

            backgroundColor: [
              "#111111",
              "#555555",
              "#888888",
              "#bbbbbb"
            ],

            borderRadius: 4,

            borderSkipped: false,

            barThickness: 34,

            maxBarThickness: 42
          }
        ]
      },

      options: {

        responsive: true,

        maintainAspectRatio: false,

        animation: {
          duration: 400
        },

        plugins: {

          legend: {
            display: false
          },

          tooltip: {

            backgroundColor: "#111111",

            titleColor: "#ffffff",

            bodyColor: "#ffffff",

            padding: 12,

            cornerRadius: 6,

            displayColors: false
          }
        },

        scales: {

          x: {

            grid: {
              display: false
            },

            border: {
              display: false
            },

            ticks: {

              color: "#777777",

              font: {
                size: 11,
                weight: "600"
              }
            }
          },

          y: {

            beginAtZero: true,

            border: {
              display: false
            },

            grid: {
              color: "#eeeeee"
            },

            ticks: {

              color: "#999999",

              padding: 8,

              font: {
                size: 10
              },

              precision: 0
            }
          }
        }
      }
    }
  )
}


/* =========================================================
   INITIAL LOAD
========================================================= */

const loadDashboard = async () => {

  const data = await fetchStats()

  if (!data) {
    return
  }

  await renderChart()
}


/* =========================================================
   WATCH FOR AUTO-REFRESH
========================================================= */

watch(
  stats,
  async () => {

    if (!stats.value) {
      return
    }

    await renderChart()
  },
  {
    deep: true
  }
)


/* =========================================================
   LIFECYCLE
========================================================= */

onMounted(async () => {

  await loadDashboard()

  startAutoRefresh()
})


onUnmounted(() => {

  stopAutoRefresh()

  if (chartInstance) {

    chartInstance.destroy()

    chartInstance = null
  }
})
</script>


<style scoped>
*,
*::before,
*::after {
  box-sizing: border-box;
}


/* =========================================================
   ROOT
========================================================= */

.dashboard-wrapper {
  width: 100%;

  display: flex;
  flex-direction: column;

  gap: 16px;

  color: #111;
}


/* =========================================================
   DASHBOARD HEADER
========================================================= */

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

.header-status {
  display: flex;
  align-items: center;

  gap: 7px;

  padding: 7px 10px;

  border: 1px solid #e5e5e5;
  border-radius: 6px;

  color: #666;

  background: #fff;

  font-size: 0.65rem;
  font-weight: 600;
}

.status-dot {
  width: 6px;
  height: 6px;

  border-radius: 50%;

  background: #111;
}


/* =========================================================
   NOTIFICATION
========================================================= */

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

  box-shadow:
    0 10px 30px rgba(0, 0, 0, 0.15);
}

.notification i {
  color: #bbb;
}


/* =========================================================
   STATS
========================================================= */

.stats-grid {
  width: 100%;

  display: grid;

  grid-template-columns:
    repeat(4, minmax(0, 1fr));

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

  transition:
    border-color 0.15s ease,
    transform 0.15s ease;
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
  color: #555;

  font-size: 0.58rem;
  font-weight: 800;

  letter-spacing: 0.08em;
}

.live-indicator {
  position: absolute;

  width: 5px;
  height: 5px;

  top: 6px;
  right: 6px;

  border-radius: 50%;

  background: #111;
}


/* =========================================================
   MAIN GRID
========================================================= */

.main-grid {
  width: 100%;
  margin:10px 0;
  display: grid;

  grid-template-columns:
    minmax(0, 1.7fr) minmax(300px, 0.9fr);

  gap: 10px;

  align-items: stretch;
}


/* =========================================================
   CARD
========================================================= */

.card {
  min-width: 0;

  background: #fff;

  border: 1px solid #e4e4e4;

  border-radius: 8px;

  overflow: hidden;
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
  color: #aaa;

  font-size: 0.59rem;
  font-weight: 700;

  letter-spacing: 0.08em;
}


/* =========================================================
   CHART
========================================================= */

.chart-wrapper {
  position: relative;

  width: 100%;

  height: 300px;

  padding: 18px 20px 14px;
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

.legend-content {
  background: #777;
}

.legend-active {
  background: #bbb;
}


/* =========================================================
   LEADERBOARD
========================================================= */

.leader-list {
  width: 100%;

  max-height: 300px;

  overflow-y: auto;

  padding: 5px;
}

.leader-list::-webkit-scrollbar {
  width: 3px;
}

.leader-list::-webkit-scrollbar-thumb {
  background: #d5d5d5;

  border-radius: 4px;
}

.leader-row {
  min-height: 55px;

  display: grid;

  grid-template-columns:
    30px 34px minmax(0, 1fr) auto;

  align-items: center;

  gap: 8px;

  padding: 6px 8px;

  border-bottom: 1px solid #f1f1f1;
}

.leader-row:last-child {
  border-bottom: none;
}

.leader-row:hover {
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

.leader-avatar {
  width: 34px;
  height: 34px;
}

.user-avatar {
  width: 34px;
  height: 34px;

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


/* =========================================================
   LOGS
========================================================= */

.logs-section {
  width: 100%;
}

.filter-btns {
  display: flex;

  gap: 4px;
}

.filter-btns button {
  min-width: 58px;

  padding: 5px 9px;

  background: #fff;

  border: 1px solid #dedede;

  border-radius: 5px;

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
  background: #111;

  border-color: #111;

  color: #fff;
}

.logs-list {
  width: 100%;

  max-height: 330px;

  display: flex;
  flex-direction: column;

  padding: 5px;

  overflow-y: auto;
}

.logs-list::-webkit-scrollbar {
  width: 3px;
}

.logs-list::-webkit-scrollbar-thumb {
  background: #d5d5d5;

  border-radius: 4px;
}

.log-item {
  min-height: 58px;

  display: grid;

  grid-template-columns:
    34px minmax(0, 1fr) 6px;

  align-items: center;

  gap: 10px;

  padding: 8px 9px;

  border-bottom: 1px solid #f1f1f1;
}

.log-item:last-child {
  border-bottom: none;
}

.log-item:hover {
  background: #fafafa;
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


/* =========================================================
   EMPTY
========================================================= */

.empty-state {
  min-height: 150px;

  display: flex;
  flex-direction: column;

  align-items: center;
  justify-content: center;

  gap: 8px;

  color: #aaa;

  font-size: 0.7rem;
}

.empty-state i {
  color: #c5c5c5;

  font-size: 1rem;
}


/* =========================================================
   LOADING
========================================================= */

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

.loading-mark span {
  display: block;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}


/* =========================================================
   NOTIFICATION TRANSITION
========================================================= */

.notification-enter-active,
.notification-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.notification-enter-from,
.notification-leave-to {
  opacity: 0;

  transform:
    translateY(-5px);
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 1050px) {

  .stats-grid {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
  }

  .main-grid {
    grid-template-columns:
      minmax(0, 1.45fr) minmax(280px, 0.9fr);
  }

}


/* =========================================================
   SMALL TABLET
========================================================= */

@media (max-width: 850px) {

  .main-grid {
    grid-template-columns: 1fr;
  }

  .leader-list {
    max-height: none;
  }

  .chart-wrapper {
    height: 280px;
  }

}


/* =========================================================
   MOBILE
========================================================= */

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
    grid-template-columns:
      repeat(2, minmax(0, 1fr));

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
    height: 240px;

    padding: 14px;
  }

  .leader-row {
    grid-template-columns:
      27px 32px minmax(0, 1fr) auto;

    gap: 7px;
  }

}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 420px) {

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .stat-card {
    min-height: 115px;

    display: grid;

    grid-template-columns:
      auto 1fr;

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