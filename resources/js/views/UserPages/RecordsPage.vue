<template>
  <div class="records-container">
    <svg width="0" height="0" style="position: absolute;">
      <defs>
        <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#6366f1" />
          <stop offset="100%" stop-color="#8b5cf6" />
        </linearGradient>
      </defs>
    </svg>

    <!-- HEADER -->
    <div class="header-flex">
      <div class="title-wrap">
        <div class="icon-box">
          <svg class="icon" viewBox="0 0 24 24">
            <path d="M13 2L2 14h9l-1 8L22 10h-9l1-8z" />
          </svg>
        </div>
        <div>
          <h3 class="records-title">Performance Analytics</h3>
          <p class="subtitle">Track your quiz progress and accuracy</p>
        </div>
      </div>

      <div class="filter-bar">
        <div class="input-group">
          <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input v-model="searchQuery" placeholder="Search quiz..." />
          <button v-if="searchQuery" @click="searchQuery = ''" class="clear-btn">✕</button>
        </div>
        <input type="date" v-model="dateFilter" class="date-input" />
      </div>
    </div>

    <!-- LOADING -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
    </div>

    <template v-else>

      <!-- STATS + CHARTS -->
      <div v-if="filteredRecords.length" class="main-grid">

        <div class="stats-summary">
          <div class="stat-box">
            <label>Avg. Score</label>
            <strong>{{ averageScore }}%</strong>
          </div>
          <div class="stat-box">
            <label>Personal Best</label>
            <strong>{{ maxScore }}/10</strong>
          </div>
          <div class="stat-box">
            <label>Needs Review</label>
            <strong class="text-fail">{{ needsImprovement }}</strong>
          </div>
          <div class="stat-box">
            <label>Total Quizzes</label>
            <strong>{{ filteredRecords.length }}</strong>
          </div>
        </div>

        <div class="charts-wrapper">
          <div class="chart-card">
            <h4>Success Rate</h4>
            <div class="chart-container">
              <Doughnut :data="chartData" :options="chartOptions" />
            </div>
          </div>
          <div class="chart-card">
            <h4>Score Trend</h4>
            <div class="chart-container">
              <Line :data="lineData" :options="lineOptions" />
            </div>
          </div>
        </div>
      </div>

      <!-- TABLE -->
      <div v-if="filteredRecords.length" class="table-wrapper">
        <table class="records-table">
          <thead>
            <tr>
              <th class="col-date">Date</th>
              <th class="col-quiz">Quiz Name</th>
              <th class="col-score">Score</th>
              <th class="col-accuracy">Accuracy</th>
              <th class="col-time">Time</th>
              <th class="col-action"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in filteredRecords" :key="r.id">
              <td class="col-date date-cell">{{ formatDate(r.created_at) }}</td>
              <td class="col-quiz">
                <div class="quiz-info">
                  <span class="quiz-name">{{ r.quiz_title || 'Untitled Quiz' }}</span>
                  <span class="attempt-tag" v-if="r.attempt">Attempt #{{ r.attempt }}</span>
                </div>
              </td>
              <td class="col-score">
                <span :class="['badge', r.score >= 7 ? 'pass' : 'fail']">{{ r.score }} / 10</span>
              </td>
              <td class="col-accuracy accuracy-cell">{{ (r.score / 10 * 100).toFixed(0) }}%</td>
              <td class="col-time time-cell">{{ r.duration || '—' }}</td>
              <td class="col-action">
                <button class="btn-view" @click="openView(r)">Details</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- EMPTY -->
      <div v-if="!filteredRecords.length" class="empty">
        <div class="empty-icon">📊</div>
        <p>No records found.</p>
        <small>{{ records.length ? 'Try adjusting your filters.' : 'Complete a quiz to see your data here.' }}</small>
      </div>

      <RecordModal v-model="showModal" :record="selectedRecord" />
    </template>
  </div>
</template>
<script setup>
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Filler
} from 'chart.js'

import {
  ref,
  computed,
  onMounted
} from "vue"

import {
  Doughnut,
  Line
} from 'vue-chartjs'

import axios from "axios"

import { useUser } from "@/composables/useUser"

import RecordModal from "@/components/UserSide/RecordModal.vue"

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  ArcElement,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Filler
)

const { fetchUser } = useUser()

/* --------------------------------------------------------
| STATE
-------------------------------------------------------- */

const records = ref([])

const loading = ref(true)

const searchQuery = ref("")
const dateFilter = ref("")

const selectedRecord = ref(null)

const showModal = ref(false)

const loadingModal = ref(false)

/* --------------------------------------------------------
| OPEN MODAL + FETCH FULL REVIEW
-------------------------------------------------------- */

const openView = async (record) => {
  loadingModal.value = true

  try {

    /*
    |--------------------------------------------------------------------------
    | IMPORTANT:
    | fetch FULL review data from correct endpoint
    |--------------------------------------------------------------------------
    */

    const res = await axios.get(
      `/api/quiz/result/${record.id}`
    )

    selectedRecord.value = {
      ...record,
      ...res.data,
      questions: res.data.questions || []
    }

    showModal.value = true

  } catch (err) {

    console.error(
      'Failed to fetch review:',
      err
    )

  } finally {

    loadingModal.value = false
  }
}

/* --------------------------------------------------------
| FORMATTERS
-------------------------------------------------------- */

const formatDate = (d) =>
  new Date(d).toLocaleDateString(
    'en-US',
    {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    }
  )

const toDateKey = (d) => {
  const date = new Date(d)

  return `${date.getFullYear()}-${String(
    date.getMonth() + 1
  ).padStart(2, '0')}-${String(
    date.getDate()
  ).padStart(2, '0')}`
}

const formatTime = (sec) => {

  if (
    sec === null ||
    sec === undefined
  ) {
    return '0:00'
  }

  const m = Math.floor(sec / 60)

  const s = sec % 60

  return `${m}:${s
    .toString()
    .padStart(2, '0')}`
}

/* --------------------------------------------------------
| FILTER
-------------------------------------------------------- */

const filteredRecords = computed(() => {

  const q = searchQuery.value
    .toLowerCase()
    .trim()

  const df = dateFilter.value

  return records.value.filter(r => {

    const matchSearch =
      !q ||
      (r.quiz_title || '')
        .toLowerCase()
        .includes(q)

    const matchDate =
      !df ||
      toDateKey(r.created_at) === df

    return (
      matchSearch &&
      matchDate
    )
  })
})

/* --------------------------------------------------------
| STATS
-------------------------------------------------------- */

const averageScore = computed(() => {

  if (!filteredRecords.value.length) {
    return 0
  }

  const total = filteredRecords.value
    .reduce(
      (sum, r) =>
        sum + r.percentage,
      0
    )

  return (
    total /
    filteredRecords.value.length
  ).toFixed(1)
})

const maxScore = computed(() => {

  if (!filteredRecords.value.length) {
    return 0
  }

  return Math.max(
    ...filteredRecords.value.map(
      r => r.score
    )
  )
})

const needsImprovement = computed(() => {

  return filteredRecords.value
    .filter(r => r.percentage < 70)
    .length
})

/* --------------------------------------------------------
| CHARTS
-------------------------------------------------------- */

const chartData = computed(() => {

  const passed =
    filteredRecords.value.filter(
      r => r.percentage >= 70
    ).length

  const failed =
    filteredRecords.value.length -
    passed

  return {
    labels: [
      'Passed',
      'Needs Review'
    ],
    datasets: [{
      data: [
        passed,
        failed
      ],
      backgroundColor: [
        '#6366f1',
        '#f43f5e'
      ],

      borderWidth: 0
    }]
  }
})

const lineData = computed(() => {
  const sorted = [
    ...filteredRecords.value
  ].sort(
    (a, b) =>
      new Date(a.created_at) -
      new Date(b.created_at)
  )
  return {
    labels: sorted.map(r =>
      new Date(r.created_at)
        .toLocaleDateString(
          'en-US',
          {
            month: 'short',
            day: 'numeric'
          }
        )
    ),

    datasets: [{
      label: 'Score %',

      data: sorted.map(
        r => r.percentage
      ),
      borderColor: '#6366f1',
      borderWidth: 2.5,
      backgroundColor:
        'rgba(99,102,241,0.1)',
      fill: true,
      tension: 0.4,
      pointRadius: 2
    }]
  }
})
/* --------------------------------------------------------
| CHART OPTIONS
-------------------------------------------------------- */
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',

      labels: {
        usePointStyle: true,
        font: { size: 11 }
      }
    }
  }
}

const lineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: (ctx) =>
          `Score: ${ctx.parsed.y}%`
      }
    }
  },
  scales: {
    y: {
      min: 0,
      max: 100,
      ticks: {
        callback: v => v + '%'
      }
    }
  }
}
/* --------------------------------------------------------
| FETCH RECORDS
-------------------------------------------------------- */
const fetchRecords = async () => {
  loading.value = true
  try {
    const res = await axios.get(
      '/api/records'
    )
    records.value =
      (res.data.results || [])
        .map(r => ({

          id: r.id,
          quiz_id: r.quiz_id,
          quiz_title:
            r.quiz_title,
          quiz_description:
            r.quiz_description,
          score: r.score,
          total_questions:
            r.total_questions,
          elapsed_time:
            r.elapsed_time,
          percentage:
            r.percentage,
          created_at:
            r.created_at,
          duration:
            formatTime(
              r.elapsed_time
            )
        }))
  } catch (err) {

    console.error(
      'Failed to fetch records:',
      err
    )
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchUser()
  fetchRecords()
})
</script>
<style scoped>
*,
*::before,
*::after {
  box-sizing: border-box;
}

/* ── CONTAINER ── */
.records-container {
  width: 100%;
  min-height: 100%;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding: 1.5rem;
  background: #fff;
  border-radius: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 8px 24px rgba(0, 0, 0, 0.04);
}

/* ── HEADER ── */
.header-flex {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.title-wrap {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}

.icon-box {
  flex-shrink: 0;
}

.icon {
  width: 1.4rem;
  height: 1.4rem;
  fill: url(#grad);
}

.records-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  line-height: 1.2;
}

.subtitle {
  font-size: 0.8rem;
  color: #94a3b8;
  margin: 0.2rem 0 0;
}

/* ── FILTER BAR ── */
.filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
  justify-content: flex-end;
}

.input-group {
  position: relative;
  flex: 1 1 160px;
  min-width: 0;
  max-width: 220px;
}

.input-group input {
  width: 100%;
  padding: 0.45rem 2rem 0.45rem 2rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  font-size: 0.82rem;
  color: #334155;
  background: #f8fafc;
  transition: border-color 0.15s, background 0.15s;
}

.input-group input:focus {
  outline: none;
  border-color: #6366f1;
  background: #fff;
}

.input-icon {
  position: absolute;
  left: 0.6rem;
  top: 50%;
  transform: translateY(-50%);
  width: 0.9rem;
  height: 0.9rem;
  color: #cbd5e1;
  pointer-events: none;
}

.clear-btn {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #cbd5e1;
  cursor: pointer;
  font-size: 0.7rem;
  padding: 0;
  line-height: 1;
}

.date-input {
  padding: 0.45rem 0.6rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  color: #475569;
  font-size: 0.82rem;
  background: #f8fafc;
}

/* ── STATS ── */
.stats-summary {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.75rem;
  margin-bottom: 10px;
}

.stat-box {
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  border-radius: 0.75rem;
  padding: 0.875rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.stat-box label {
  font-size: 0.67rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #94a3b8;
}

.stat-box strong {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
}

.text-fail {
  color: #f43f5e;
}

/* ── CHARTS ── */
.charts-wrapper {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}

.chart-card {
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  border-radius: 0.75rem;
  padding: 1rem;
  min-width: 0;
}

.chart-card h4 {
  margin: 0 0 0.75rem;
  font-size: 0.78rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.chart-container {
  width: 100%;
  height: 180px;
  position: relative;
}

/* ── TABLE ── */
.table-wrapper {
  width: 100%;
  overflow-x: auto;
  border: 1px solid #f1f5f9;
  border-radius: 0.75rem;
  -webkit-overflow-scrolling: touch;
}

.table-wrapper::-webkit-scrollbar {
  height: 4px;
}

.table-wrapper::-webkit-scrollbar-track {
  background: #f8fafc;
}

.table-wrapper::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 99px;
}

.table-wrapper::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}

.records-table {
  width: 100%;
  min-width: 520px;
  border-collapse: collapse;
  table-layout: fixed;
}

/* Column widths */
.col-date {
  width: 80px;
}

.col-quiz {
  width: auto;
}

.col-score {
  width: 90px;
}

.col-accuracy {
  width: 80px;
}

.fade-enter-active,
.fade-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.fade-enter-from .modal-content,
.fade-leave-to .modal-content {
  transform: scale(0.96);
}

.col-time {
  width: 65px;
}

.col-action {
  width: 80px;
}

/* THEAD */
.records-table thead th {
  background: #f8fafc;
  color: #64748b;
  padding: 0.65rem 0.875rem;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  text-align: center;
  white-space: nowrap;
  border-bottom: 1px solid #e2e8f0;
  position: sticky;
  top: 0;
  z-index: 1;
}

.records-table thead th.col-date,
.records-table thead th.col-quiz {
  text-align: left;
}

/* TBODY */
.records-table td {
  padding: 0.65rem 0.875rem;
  text-align: center;
  border-bottom: 1px solid #f8fafc;
  color: #334155;
  font-size: 0.84rem;
}

.records-table tr:last-child td {
  border-bottom: none;
}

.records-table tbody tr {
  transition: background 0.12s;
}

.records-table tbody tr:hover td {
  background: #fafafe;
}

.records-table td.col-date,
.records-table td.col-quiz {
  text-align: left;
}

/* Cells */
.quiz-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.quiz-name {
  font-weight: 500;
  color: #1e293b;
  font-size: 0.84rem;
}

.attempt-tag {
  font-size: 0.68rem;
  color: #94a3b8;
}

.date-cell {
  white-space: nowrap;
  font-size: 0.8rem;
  color: #64748b;
}

.accuracy-cell,
.time-cell {
  white-space: nowrap;
  color: #475569;
  font-size: 0.82rem;
}

/* ── BADGE ── */
.badge {
  display: inline-block;
  padding: 0.25rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 700;
  white-space: nowrap;
}

.pass {
  background: #ecfdf5;
  color: #10b981;
}

.fail {
  background: #fff1f2;
  color: #f43f5e;
}

/* ── BUTTON ── */
.btn-view {
  background: #6366f1;
  color: #fff;
  border: none;
  padding: 0.35rem 0.75rem;
  border-radius: 0.4rem;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.15s;
}

.btn-view:hover {
  background: #4f46e5;
}

/* ── LOADING ── */
.loading {
  padding: 3rem;
  display: flex;
  justify-content: center;
}

.spinner {
  width: 2rem;
  height: 2rem;
  border: 2.5px solid #f1f5f9;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

/* ── EMPTY ── */
.empty {
  text-align: center;
  padding: 3rem 1rem;
  color: #64748b;
}

.empty-icon {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
}

.empty p {
  margin: 0 0 0.25rem;
  font-weight: 600;
  color: #334155;
}

.empty small {
  font-size: 0.8rem;
  color: #94a3b8;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ── RESPONSIVE ── */
@media (max-width: 991.98px) {
  .records-container {
    padding: 1.25rem;
  }

  .chart-container {
    height: 165px;
  }
}

@media (max-width: 767.98px) {
  .records-container {
    padding: 1rem;
    gap: 1rem;
  }

  .header-flex {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-bar {
    justify-content: flex-start;
  }

  .input-group {
    flex: 1 1 auto;
    max-width: 100%;
  }

  .date-input {
    width: 100%;
  }

  .stats-summary {
    grid-template-columns: repeat(2, 1fr);
  }

  .charts-wrapper {
    grid-template-columns: repeat(2, 1fr);
  }

  .chart-container {
    height: 160px;
  }
}

@media (max-width: 575.98px) {
  .records-container {
    padding: 0.875rem;
    gap: 0.875rem;
  }

  .filter-bar {
    flex-direction: column;
  }

  .input-group,
  .date-input {
    width: 100%;
    max-width: 100%;
  }

  .charts-wrapper {
    grid-template-columns: 1fr;
  }

  .chart-container {
    height: 180px;
  }

  .stat-box {
    padding: 0.7rem 0.75rem;
  }

  .stat-box strong {
    font-size: 1.1rem;
  }
}

@media (max-width: 399.98px) {
  .records-container {
    padding: 0.75rem;
  }

  .stat-box strong {
    font-size: 1rem;
  }

  .chart-container {
    height: 160px;
  }
}
</style>