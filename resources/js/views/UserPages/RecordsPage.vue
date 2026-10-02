<template>
  <div class="records-container">

    <!-- HEADER -->
    <div class="header-flex">
      <div class="title-wrap">
        <div class="icon-box">
          <i class="fas fa-clipboard-list"></i>
        </div>

        <div>
          <h3 class="records-title">
            Assessment Records
          </h3>

          <p class="subtitle">
            View your completed assessment attempts
          </p>
        </div>
      </div>

      <button class="filter-toggle" @click="showFilters = !showFilters"
        :aria-label="showFilters ? 'Hide filters' : 'Show filters'">
        <i class="fas fa-filter"></i>
      </button>
    </div>


    <!-- FILTERS -->
    <div class="filter-bar" :class="{ 'filters-open': showFilters }">

      <!-- SEARCH -->
      <div class="input-group">

        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>

        <input v-model="searchQuery" type="text" placeholder="Search assessment..." />

        <button v-if="searchQuery" class="clear-btn" @click="searchQuery = ''" aria-label="Clear search">
          <i class="fas fa-times"></i>
        </button>

      </div>


      <!-- TYPE -->
      <select v-model="typeFilter" class="select-input">
        <option value="">
          All Types
        </option>

        <option v-for="type in availableTypes" :key="type" :value="type">
          {{ formatLabel(type) }}
        </option>
      </select>


      <!-- CATEGORY -->
      <select v-model="categoryFilter" class="select-input">
        <option value="">
          All Categories
        </option>

        <option v-for="category in availableCategories" :key="category" :value="category">
          {{ formatLabel(category) }}
        </option>
      </select>


      <!-- DATE -->
      <input v-model="dateFilter" type="date" class="date-input" />

    </div>


    <!-- LOADING -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
    </div>


    <template v-else>

      <!-- SUMMARY -->
      <div class="records-summary">

        <div class="summary-item">
          <span class="summary-label">
            Records
          </span>

          <strong>
            {{ filteredRecords.length }}
          </strong>
        </div>

        <div class="summary-item">
          <span class="summary-label">
            Assessments
          </span>

          <strong>
            {{ uniqueAssessments }}
          </strong>
        </div>

      </div>


      <!-- DESKTOP TABLE -->
      <div v-if="filteredRecords.length" class="records-display">

        <div class="table-wrapper">

          <table class="records-table">

            <thead>
              <tr>

                <th class="col-date">
                  Date
                </th>

                <th class="col-coc">
                  COC
                </th>



                <th class="col-type">
                  Type
                </th>
                <th class="col-assessment">
                  Assessment
                </th>

                <th class="col-category">
                  Category
                </th>

                <th class="col-score">
                  Score
                </th>

                <th class="col-status">
                  Status
                </th>

                <th class="col-action">
                  View
                </th>

              </tr>
            </thead>


            <tbody>

              <tr v-for="record in filteredRecords" :key="record.id">

                <!-- DATE -->
                <td class="col-date">
                  {{ formatDate(
                    record.completed_at ||
                    record.created_at
                  ) }}
                </td>


                <!-- ASSESSMENT -->
                <td class="col-assessment">

                  <div class="assessment-info">

                    <div class="assessment-icon">
                      <i class="fas fa-file-alt"></i>
                    </div>

                    <div class="assessment-text">

                      <span class="assessment-name">
                        {{ record.coc_id }}
                      </span>

                    </div>

                  </div>

                </td>


                <!-- TYPE -->
                <td class="col-type">

                  <span class="data-tag">
                    {{ formatLabel(record.quiz_type) }}
                  </span>

                </td>


                <!-- CATEGORY -->
                <td class="col-category">

                  <span class="category-text">
                    {{ record.category || '—' }}
                  </span>

                </td>


                <!-- SCORE -->
                <td class="col-score">

                  <span class="score-display">
                    {{ displayScore(record) }}
                  </span>

                </td>


                <!-- STATUS -->
                <td class="col-status">

                  <span :class="[
                    'status-badge',
                    statusClass(record)
                  ]">
                    {{ getStatus(record) }}
                  </span>

                </td>


                <!-- ACTION -->
                <td class="col-action">

                  <button class="btn-icon" @click="openView(record)" title="View assessment record"
                    aria-label="View assessment record">
                    <i class="fas fa-eye"></i>
                  </button>

                </td>

              </tr>

            </tbody>

          </table>

        </div>


        <!-- MOBILE CARDS -->
        <div class="records-cards">

          <div v-for="record in filteredRecords" :key="record.id" class="record-card">

            <div class="card-header">

              <div class="card-title-wrap">

                <div class="assessment-icon" title="record.coc_title">
                  <i class="fas fa-file-alt"></i>
                </div>

                <div>

                  <h4>
                    {{ record.coc_title}}
                  </h4>

                  <p class="card-date">
                    {{ formatDate(
                      record.completed_at ||
                      record.created_at
                    ) }}
                  </p>

                </div>

              </div>


              <span :class="[
                'status-badge',
                statusClass(record)
              ]">
                {{ getStatus(record) }}
              </span>

            </div>


            <div class="card-body">

              <div class="card-row">

                <span class="card-label">
                  Score
                </span>

                <span class="card-value">
                  {{ displayScore(record) }}
                </span>

              </div>


              <div class="card-row">

                <span class="card-label">
                  Type
                </span>

                <span class="card-tag">
                  {{ formatLabel(record.quiz_type) }}
                </span>

              </div>


              <div v-if="record.category" class="card-row">

                <span class="card-label">
                  Category
                </span>

                <span class="card-value">
                  {{ record.category }}
                </span>

              </div>


              <div class="card-row">

                <span class="card-label">
                  Time
                </span>

                <span class="card-value">
                  {{ record.duration || '—' }}
                </span>

              </div>

            </div>


            <button class="btn-view-card" @click="openView(record)">
              <i class="fas fa-eye"></i>
              View Record
            </button>

          </div>

        </div>

      </div>


      <!-- EMPTY -->
      <div v-else class="empty">

        <div class="empty-icon">
          <i class="fas fa-clipboard-list"></i>
        </div>

        <p>
          No assessment records found.
        </p>

        <small>
          {{
            records.length
              ? 'Try adjusting your filters.'
              : 'Complete an assessment to see your records here.'
          }}
        </small>

      </div>


      <!-- MODAL -->
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
} from 'vue'

import {
  Doughnut,
  Line
} from 'vue-chartjs'
import api from '@/bootstrap'
import { useUser } from '@/composables/useUser'
import RecordModal
  from '@/components/UserSide/RecordModal.vue'

/* ========================================================
   CHART.JS
======================================================== */

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


const { user, fetchUser } = useUser()


/* ========================================================
   STATE
======================================================== */

const records = ref([])
const loading = ref(true)
const searchQuery = ref('')
const dateFilter = ref('')
const typeFilter = ref('')
const categoryFilter = ref('')
const selectedRecord = ref(null)
const showModal = ref(false)
const loadingModal = ref(false)
const showFilters = ref(false)


/* ========================================================
   OPEN RECORD DETAILS
======================================================== */

const openView = async (record) => {
  loadingModal.value = true

  try {
    const { data } = await api.get(
      `/api/quiz/result/${record.id}`
    )

    selectedRecord.value = {
      ...record,
      ...data,
      questions: data.questions || []
    }

    showModal.value = true

  } catch (error) {
    console.error(
      'Failed to fetch review:',
      error.response?.data || error
    )
  } finally {
    loadingModal.value = false
  }
}


/* ========================================================
   FORMATTERS
======================================================== */

const formatDate = (date) => {
  if (!date) return '—'

  const parsed = new Date(date)

  if (isNaN(parsed.getTime())) {
    return '—'
  }

  return parsed.toLocaleDateString('en-US', {
    month: '2-digit',
    day: '2-digit',
    year: '2-digit',
  })
}


const toDateKey = (date) => {
  if (!date) return ''

  const parsed = new Date(date)

  if (isNaN(parsed.getTime())) {
    return ''
  }

  return [
    parsed.getFullYear(),
    String(parsed.getMonth() + 1).padStart(2, '0'),
    String(parsed.getDate()).padStart(2, '0')
  ].join('-')
}


const formatTime = (seconds) => {
  if (
    seconds === null ||
    seconds === undefined ||
    isNaN(seconds)
  ) {
    return '—'
  }

  const sec = Number(seconds)

  const hours = Math.floor(sec / 3600)

  const minutes = Math.floor(
    (sec % 3600) / 60
  )

  const remainingSeconds = sec % 60

  if (hours > 0) {
    return `${hours}h ${minutes}m`
  }

  return `${minutes}:${String(
    remainingSeconds
  ).padStart(2, '0')}`
}


const formatLabel = (value) => {
  if (!value) return '—'

  return String(value)
    .replace(/[_-]/g, ' ')
    .replace(/\b\w/g, char =>
      char.toUpperCase()
    )
}


/* ========================================================
   SCORE HELPERS
======================================================== */

const getPercentage = (record) => {
  if (
    record.percentage !== null &&
    record.percentage !== undefined &&
    !isNaN(record.percentage)
  ) {
    return Math.round(
      Number(record.percentage)
    )
  }

  if (
    record.score !== null &&
    record.score !== undefined &&
    record.total_questions
  ) {
    return Math.round(
      (Number(record.score) /
        Number(record.total_questions)) *
      100
    )
  }

  return 0
}


const displayScore = (record) => {
  const score = record.score
  const total = record.total_questions

  if (
    score !== null &&
    score !== undefined &&
    total
  ) {
    return `${score} / ${total}`
  }

  if (
    score !== null &&
    score !== undefined
  ) {
    return String(score)
  }

  return '—'
}


/* ========================================================
   STATUS
======================================================== */

const getStatus = (record) => {

  if (record.status) {
    return formatLabel(record.status)
  }

  const passingScore =
    Number(record.passing_score ?? 75)

  return getPercentage(record) >= passingScore
    ? 'Passed'
    : 'Failed'
}


const statusClass = (record) => {

  const status =
    String(record.status || '').toLowerCase()

  if (
    status.includes('fail') ||
    status.includes('review')
  ) {
    return 'status-fail'
  }

  if (
    status.includes('pass') ||
    status.includes('complete')
  ) {
    return 'status-pass'
  }

  const passingScore =
    Number(record.passing_score ?? 75)

  return getPercentage(record) >= passingScore
    ? 'status-pass'
    : 'status-fail'
}


/* ========================================================
   AVAILABLE FILTER VALUES
======================================================== */

const availableTypes = computed(() => {
  return [
    ...new Set(
      records.value
        .map(record => record.quiz_type)
        .filter(Boolean)
    )
  ]
})


const availableCategories = computed(() => {
  return [
    ...new Set(
      records.value
        .map(record => record.category)
        .filter(Boolean)
    )
  ]
})


/* ========================================================
   FILTERED RECORDS
======================================================== */

const filteredRecords = computed(() => {
  const search =
    searchQuery.value
      .toLowerCase()
      .trim()

  const date = dateFilter.value
  const type = typeFilter.value
  const category = categoryFilter.value

  return records.value.filter(record => {

    const title = String(
      record.coc_title || ''
    ).toLowerCase()

    const description = String(
      record.quiz_description || ''
    ).toLowerCase()

    const matchesSearch =
      !search ||
      title.includes(search) ||
      description.includes(search)

    const recordDate =
      record.completed_at ||
      record.created_at

    const matchesDate =
      !date ||
      toDateKey(recordDate) === date

    const matchesType =
      !type ||
      record.quiz_type === type

    const matchesCategory =
      !category ||
      record.category === category

    return (
      matchesSearch &&
      matchesDate &&
      matchesType &&
      matchesCategory
    )
  })
})

/* ========================================================
   STATISTICS
======================================================== */

const averageScore = computed(() => {
  if (!filteredRecords.value.length) {
    return '0.0'
  }

  const total = filteredRecords.value.reduce((sum, record) => sum + getPercentage(record),0)

  return (total / filteredRecords.value.length).toFixed(1)
})


const personalBest = computed(() => {
  if (!filteredRecords.value.length) {
    return 0
  }

  return Math.max(
    ...filteredRecords.value.map(
      record => getPercentage(record)
    )
  )
})


const needsImprovement = computed(() => {
  return filteredRecords.value.filter(
    record => getPercentage(record) < 70
  ).length
})


/* ========================================================
   DOUGHNUT CHART
======================================================== */

const chartData = computed(() => {

  const passed =
    filteredRecords.value.filter(
      record => getPercentage(record) >= 70
    ).length

  const failed =
    filteredRecords.value.length -
    passed

  return {
    labels: [
      'Passed',
      'Needs Review'
    ],

    datasets: [
      {
        data: [
          passed,
          failed
        ],

        backgroundColor: [
          '#000000',
          '#A9A9A9'
        ],

        borderWidth: 0
      }
    ]
  }
})


/* ========================================================
   LINE CHART
======================================================== */

const lineData = computed(() => {

  const sorted =
    [...filteredRecords.value].sort(
      (a, b) =>
        new Date(
          a.completed_at ||
          a.created_at
        ) -
        new Date(
          b.completed_at ||
          b.created_at
        )
    )

  return {
    labels: sorted.map(record => {

      const date =
        record.completed_at ||
        record.created_at

      return new Date(
        date
      ).toLocaleDateString(
        'en-US',
        {
          month: 'short',
          day: 'numeric'
        }
      )
    }),

    datasets: [
      {
        label: 'Score %',

        data: sorted.map(
          record =>
            getPercentage(record)
        ),

        borderColor: '#000000',

        borderWidth: 2.5,

        backgroundColor:
          'rgba(0,0,0,0.06)',

        fill: true,

        tension: 0.4,

        pointRadius: 2
      }
    ]
  }
})


/* ========================================================
   CHART OPTIONS
======================================================== */

const chartOptions = {
  responsive: true,

  maintainAspectRatio: false,

  plugins: {
    legend: {
      position: 'bottom',

      labels: {
        usePointStyle: true,

        font: {
          size: 11
        }
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
        callback: value =>
          `${value}%`
      }
    }
  }
}


/* ========================================================
   FETCH RECORDS - UPDATED
======================================================== */

const fetchRecords = async () => {

  loading.value = true

  try {

    const { data } = await api.get('/api/records')

    records.value = (data.results || []).map(record => ({
      id: record.id,

      user_id: record.user_id,

      coc_id: record.coc_id,

      coc_title: record.quiz_title,

      quiz_description: record.quiz_description,

      score: record.score,

      correct_answers: record.correct_answers,

      total_questions: record.total_questions,

      percentage: record.percentage,

      passing_score: record.passing_score,

      status: record.status,

      attempt_count: record.attempt_count,

      quiz_type: record.quiz_type,

      category: record.category,

      elapsed_time: record.elapsed_time,

      completed_at: record.completed_at,

      created_at: record.created_at,

      updated_at: record.updated_at,

      duration: formatTime(record.elapsed_time),
    }))

  } catch (error) {

    console.error(
      'Failed to fetch records:',
      error.response?.data || error
    )

  } finally {

    loading.value = false
  }
}


/* ========================================================
   INITIALIZE
======================================================== */

onMounted(async () => {

  // Only ask Laravel for /me if
  // the shared user state is empty.
  if (!user.value) {
    await fetchUser()
  }

  // Don't fetch records if authentication
  // could not be restored.
  if (!user.value) {
    loading.value = false
    return
  }

  await fetchRecords()
})
</script>

<style scoped>
*,
*::before,
*::after {
  box-sizing: border-box;
}


/* ========================================================
   PALETTE
======================================================== */

.records-container {
  --black: #111111;
  --white: #ffffff;
  --text: #222222;
  --muted: #737373;
  --light: #a3a3a3;
  --border: #e5e5e5;
  --soft-border: #f0f0f0;
  --surface: #fafafa;
}


/* ========================================================
   CONTAINER
======================================================== */

.records-container {
  width: 100%;
  min-height: 100%;
  display: flex;
  flex-direction: column;
  gap: 1rem;

  padding: 1.5rem;

  background: var(--white);

  border-radius: 4px;

  box-shadow:
    0 1px 2px rgba(0, 0, 0, 0.03),
    0 12px 32px rgba(0, 0, 0, 0.05);
}


/* ========================================================
   HEADER
======================================================== */

.header-flex {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.title-wrap {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}

.icon-box {
  width: 2.25rem;
  height: 2.25rem;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  background: var(--black);
  color: var(--white);

  border-radius: 0.55rem;

  font-size: 0.85rem;
}

.records-title {
  margin: 0;

  color: var(--black);

  font-size: 1rem;
  font-weight: 700;

  line-height: 1.2;
}

.subtitle {
  margin: 0.2rem 0 0;

  color: var(--muted);

  font-size: 0.75rem;
}


/* ========================================================
   FILTER TOGGLE
======================================================== */

.filter-toggle {
  display: none;

  width: 2.25rem;
  height: 2.25rem;

  align-items: center;
  justify-content: center;

  border: none;
  border-radius: 0.55rem;

  background: var(--black);
  color: var(--white);

  cursor: pointer;
}


/* ========================================================
   FILTERS
======================================================== */

.filter-bar {
  display: flex;
  align-items: center;
  justify-content: flex-end;

  gap: 0.5rem;
}

.input-group {
  position: relative;

  width: 220px;
}

.input-group input,
.select-input,
.date-input {
  width: 100%;

  height: 34px;

  border: 1px solid var(--border);
  border-radius: 0.45rem;

  background: var(--surface);
  color: var(--text);

  font-size: 0.78rem;

  transition:
    border-color 0.15s,
    background 0.15s;
}

.input-group input {
  padding: 0 2rem;
}

.select-input,
.date-input {
  width: auto;
  padding: 0 0.6rem;
}

.input-group input:focus,
.select-input:focus,
.date-input:focus {
  outline: none;

  border-color: var(--black);
  background: var(--white);
}

.input-icon {
  position: absolute;

  left: 0.65rem;
  top: 50%;

  width: 0.85rem;
  height: 0.85rem;

  transform: translateY(-50%);

  color: var(--light);

  pointer-events: none;
}

.clear-btn {
  position: absolute;

  right: 0.6rem;
  top: 50%;

  transform: translateY(-50%);

  border: none;
  background: none;

  color: var(--light);

  cursor: pointer;

  font-size: 0.65rem;
}


/* ========================================================
   SUMMARY
======================================================== */

.records-summary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;

  padding: 0.45rem 0.7rem;

  background: var(--surface);

  border: 1px solid var(--border);
  border-radius: 0.45rem;
}

.summary-label {
  color: var(--muted);

  font-size: 0.68rem;
  font-weight: 600;
}

.summary-item strong {
  color: var(--black);

  font-size: 0.78rem;
}


/* ========================================================
   TABLE
======================================================== */

.table-wrapper {
  width: 100%;

  overflow-x: auto;

  border: 1px solid var(--border);
  border-radius: 0.65rem;
}

.records-table {
  width: 100%;

  min-width: 760px;

  border-collapse: collapse;

  table-layout: fixed;
}

.records-table th {
  padding: 0.65rem 0.75rem;

  background: var(--surface);

  border-bottom: 1px solid var(--border);

  color: var(--muted);

  font-size: 0.64rem;
  font-weight: 700;

  text-transform: uppercase;
  letter-spacing: 0.05em;

  text-align: center;
  white-space: nowrap;
}

.records-table td {
  padding: 0.65rem 0.75rem;

  border-bottom: 1px solid var(--soft-border);

  color: var(--text);

  font-size: 0.78rem;

  text-align: center;
  vertical-align: middle;
}

.records-table tbody tr:last-child td {
  border-bottom: none;
}

.records-table tbody tr {
  transition: background 0.12s;
}

.records-table tbody tr:hover {
  background: var(--surface);
}


/* COLUMN WIDTHS */

.col-date {
  width: 90px;
}

.col-assessment {
  width: auto;
}

.col-type {
  width: 110px;
}

.col-category {
  width: 110px;
}

.col-score {
  width: 90px;
}

.col-status {
  width: 100px;
}

.col-action {
  width: 60px;
}


/* ========================================================
   ASSESSMENT
======================================================== */

.assessment-info {
  display: flex;
  align-items: center;

  gap: 0.6rem;

  text-align: left;
}

.assessment-icon {
  width: 28px;
  height: 28px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  background: var(--surface);

  border: 1px solid var(--border);
  border-radius: 0.4rem;

  color: var(--black);

  font-size: 0.7rem;
}

.assessment-text {
  min-width: 0;
}

.assessment-name {
  display: block;

  overflow: hidden;

  color: var(--black);

  font-size: 0.78rem;
  font-weight: 600;

  text-overflow: ellipsis;
  white-space: nowrap;
}


/* ========================================================
   TAGS
======================================================== */

.data-tag,
.card-tag {
  display: inline-flex;
  align-items: center;

  padding: 0.22rem 0.45rem;

  background: var(--surface);

  border: 1px solid var(--border);
  border-radius: 0.3rem;

  color: var(--muted);

  font-size: 0.68rem;
  font-weight: 600;
}

.category-text {
  color: var(--muted);

  font-size: 0.75rem;
}

.score-display {
  color: var(--black);

  font-weight: 700;
}


/* ========================================================
   STATUS
======================================================== */

.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  padding: 0.22rem 0.5rem;

  border-radius: 999px;

  font-size: 0.65rem;
  font-weight: 700;

  white-space: nowrap;
}

.status-complete {
  background: var(--surface);

  border: 1px solid var(--border);

  color: var(--black);
}

.status-review {
  background: var(--black);

  color: var(--white);
}


/* ========================================================
   ACTION
======================================================== */

.btn-icon {
  width: 30px;
  height: 30px;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  border: 1px solid var(--border);
  border-radius: 0.4rem;

  background: var(--white);
  color: var(--black);

  cursor: pointer;

  transition:
    background 0.15s,
    color 0.15s,
    border-color 0.15s;
}

.btn-icon:hover {
  background: var(--black);
  border-color: var(--black);
  color: var(--white);
}

.btn-icon:active {
  transform: scale(0.94);
}


/* ========================================================
   MOBILE CARDS
======================================================== */

.records-cards {
  display: none;

  flex-direction: column;

  gap: 0.75rem;
}

.record-card {
  overflow: hidden;

  background: var(--white);

  border: 1px solid var(--border);
  border-radius: 0.65rem;
}

.card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;

  gap: 0.75rem;

  padding: 0.8rem;

  border-bottom: 1px solid var(--soft-border);
}

.card-title-wrap {
  display: flex;

  gap: 0.6rem;

  min-width: 0;
}

.card-header h4 {
  margin: 0;

  color: var(--black);

  font-size: 0.85rem;
  font-weight: 650;

  line-height: 1.3;
}

.card-date {
  margin: 0.2rem 0 0;

  color: var(--light);

  font-size: 0.68rem;
}

.card-body {
  display: flex;
  flex-direction: column;

  gap: 0.55rem;

  padding: 0.8rem;
}

.card-row {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 1rem;
}

.card-label {
  color: var(--muted);

  font-size: 0.68rem;
  font-weight: 600;

  text-transform: uppercase;
}

.card-value {
  color: var(--black);

  font-size: 0.76rem;
  font-weight: 600;

  text-align: right;
}

.btn-view-card {
  width: calc(100% - 1rem);

  margin: 0 0.5rem 0.5rem;

  height: 32px;

  border: none;
  border-radius: 0.4rem;

  background: var(--black);
  color: var(--white);

  font-size: 0.72rem;
  font-weight: 600;

  cursor: pointer;
}

.btn-view-card i {
  margin-right: 0.35rem;
}


/* ========================================================
   LOADING
======================================================== */

.loading {
  display: flex;
  align-items: center;
  justify-content: center;

  min-height: 180px;
}

.spinner {
  width: 26px;
  height: 26px;

  border: 2px solid var(--border);
  border-top-color: var(--black);

  border-radius: 50%;

  animation: spin 0.7s linear infinite;
}


/* ========================================================
   EMPTY
======================================================== */

.empty {
  display: flex;
  flex-direction: column;
  align-items: center;

  padding: 3rem 1rem;

  text-align: center;
}

.empty-icon {
  width: 42px;
  height: 42px;

  display: flex;
  align-items: center;
  justify-content: center;

  margin-bottom: 0.75rem;

  border: 1px solid var(--border);
  border-radius: 0.6rem;

  background: var(--surface);

  color: var(--muted);

  font-size: 0.9rem;
}

.empty p {
  margin: 0 0 0.25rem;

  color: var(--black);

  font-size: 0.85rem;
  font-weight: 600;
}

.empty small {
  color: var(--light);

  font-size: 0.72rem;
}


/* ========================================================
   ANIMATION
======================================================== */

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}


/* ========================================================
   RESPONSIVE
======================================================== */

@media (max-width: 768px) {

  .records-container {
    padding: 1rem;
  }

  .filter-toggle {
    display: flex;
  }

  .filter-bar {
    flex-direction: column;
    align-items: stretch;

    max-height: 0;

    overflow: hidden;

    transition: max-height 0.25s ease;
  }

  .filter-bar.filters-open {
    max-height: 300px;
  }

  .input-group,
  .select-input,
  .date-input {
    width: 100%;
  }

  .records-table {
    min-width: 700px;
  }

}


@media (max-width: 640px) {

  .records-container {
    padding: 0.85rem;
    gap: 0.75rem;
  }

  .subtitle {
    font-size: 0.7rem;
  }

  .records-summary {
    width: 100%;
  }

  .summary-item {
    flex: 1;
  }

  .table-wrapper {
    display: none;
  }

  .records-cards {
    display: flex;
  }

}


@media (max-width: 480px) {

  .records-container {
    padding: 0.7rem;
  }

  .icon-box {
    width: 2rem;
    height: 2rem;
  }

  .records-title {
    font-size: 0.9rem;
  }

  .summary-item {
    padding: 0.4rem 0.55rem;
  }

  .summary-label {
    font-size: 0.6rem;
  }

  .summary-item strong {
    font-size: 0.72rem;
  }

}


@media (max-width: 360px) {

  .records-container {
    padding: 0.5rem;
  }

  .records-title {
    font-size: 0.85rem;
  }

  .summary-item {
    flex-direction: column;
    align-items: flex-start;

    gap: 0.15rem;
  }

}
</style>