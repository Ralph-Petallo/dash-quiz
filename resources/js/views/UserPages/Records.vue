<template>
  <div class="records-container">
    <div class="header-flex">
      <div class="title-wrap">
        <div class="icon-box">
          <i class="fas fa-clipboard-list"></i>
        </div>

        <div>
          <h3 class="records-title">Assessment Records</h3>
          <p class="subtitle">View your completed assessment attempts</p>
        </div>
      </div>

      <button
        class="filter-toggle"
        type="button"
        @click="showFilters = !showFilters"
        :aria-label="showFilters ? 'Hide filters' : 'Show filters'"
      >
        <i class="fas fa-filter"></i>
      </button>
    </div>

    <div class="filter-bar" :class="{ 'filters-open': showFilters }">
      <div class="input-group">
        <i class="fas fa-search input-icon"></i>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search assessment..."
        />
        <button
          v-if="searchQuery"
          type="button"
          class="clear-btn"
          @click="searchQuery = ''"
          aria-label="Clear search"
        >
          <i class="fas fa-times"></i>
        </button>
      </div>

      <select v-model="typeFilter" class="select-input">
        <option value="">All Types</option>
        <option
          v-for="type in availableTypes"
          :key="type"
          :value="type"
        >
          {{ formatLabel(type) }}
        </option>
      </select>

      <select v-model="categoryFilter" class="select-input">
        <option value="">All Categories</option>
        <option
          v-for="category in availableCategories"
          :key="category"
          :value="category"
        >
          {{ category }}
        </option>
      </select>

      <input v-model="dateFilter" type="date" class="date-input" />
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
    </div>

    <template v-else>
      <div class="records-summary">
        <div class="summary-item">
          <span class="summary-label">Records</span>
          <strong>{{ filteredRecords.length }}</strong>
        </div>

        <div class="summary-item">
          <span class="summary-label">Assessments</span>
          <strong>{{ uniqueAssessments }}</strong>
        </div>
      </div>

      <div v-if="filteredRecords.length" class="records-display">
        <div class="table-wrapper">
          <table class="records-table">
            <thead>
              <tr>
                <th scope="col" class="col-date">Date</th>
                <th scope="col" class="col-coc">COC</th>
                <th scope="col" class="col-type">Type</th>
                <th scope="col" class="col-assessment">Assessment</th>
                <th scope="col" class="col-category">Category</th>
                <th scope="col" class="col-score">Score</th>
                <th scope="col" class="col-status">Status</th>
                <th scope="col" class="col-action">View</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="record in filteredRecords" :key="record.id">
                <td class="col-date">
                  {{ formatDate(record.completed_at || record.created_at) }}
                </td>

                <td class="col-coc">
                  <span class="data-tag">
                    {{ formatCoc(record.coc_number) }}
                  </span>
                </td>

                <td class="col-type">
                  <span class="data-tag">
                    {{ formatLabel(record.quiz_type) }}
                  </span>
                </td>

                <td class="col-assessment">
                  <div class="assessment-info">
                    <div class="assessment-icon">
                      <i :class="assessmentIcon(record.quiz_type)"></i>
                    </div>

                    <div class="assessment-text">
                      <span class="assessment-name">
                        {{ record.quiz_title || 'Assessment' }}
                      </span>
                    </div>
                  </div>
                </td>

                <td class="col-category">
                  <span class="category-text">
                    {{ record.category || '—' }}
                  </span>
                </td>

                <td class="col-score">
                  <div class="score-wrap">
                    <strong class="score-display">
                      {{ displayScore(record) }}
                    </strong>
                    <small>{{ formatPercentage(record.percentage) }}</small>
                  </div>
                </td>

                <td class="col-status">
                  <span :class="['status-badge', statusClass(record)]">
                    {{ getStatus(record) }}
                  </span>
                </td>

                <td class="col-action">
                  <button
                    type="button"
                    class="btn-icon"
                    @click="openView(record)"
                    title="View assessment record"
                    aria-label="View assessment record"
                  >
                    <i class="fas fa-eye"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="records-cards">
          <article
            v-for="record in filteredRecords"
            :key="`card-${record.id}`"
            class="record-card"
          >
            <div class="card-header">
              <div class="card-title-wrap">
                <div class="assessment-icon">
                  <i :class="assessmentIcon(record.quiz_type)"></i>
                </div>

                <div class="card-title-text">
                  <h4>{{ record.quiz_title || 'Assessment' }}</h4>
                  <p class="card-date">
                    {{ formatDate(record.completed_at || record.created_at) }}
                  </p>
                </div>
              </div>

              <span :class="['status-badge', statusClass(record)]">
                {{ getStatus(record) }}
              </span>
            </div>

            <div class="card-body">
              <div class="card-row">
                <span class="card-label">COC</span>
                <span class="card-value">{{ formatCoc(record.coc_number) }}</span>
              </div>

              <div class="card-row">
                <span class="card-label">Type</span>
                <span class="card-tag">{{ formatLabel(record.quiz_type) }}</span>
              </div>

              <div class="card-row">
                <span class="card-label">Category</span>
                <span class="card-value">{{ record.category || '—' }}</span>
              </div>

              <div class="card-row">
                <span class="card-label">Score</span>
                <span class="card-value">
                  {{ displayScore(record) }} · {{ formatPercentage(record.percentage) }}
                </span>
              </div>

              <div class="card-row">
                <span class="card-label">Time</span>
                <span class="card-value">{{ formatTime(record.elapsed_time) }}</span>
              </div>
            </div>

            <button
              type="button"
              class="btn-view-card"
              @click="openView(record)"
            >
              <i class="fas fa-eye"></i>
              View Record
            </button>
          </article>
        </div>
      </div>

      <div v-else class="empty">
        <div class="empty-icon">
          <i class="fas fa-clipboard-list"></i>
        </div>
        <p>No assessment records found.</p>
        <small>
          {{ records.length
            ? 'Try adjusting your filters.'
            : 'Complete an assessment to see your records here.' }}
        </small>
      </div>

      <RecordModal v-model="showModal" :record="selectedRecord" />
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/bootstrap'
import { useUser } from '@/composables/useUser'
import RecordModal from '@/components/UserSide/RecordModal.vue'

const { user, fetchUser } = useUser()

const records = ref([])
const loading = ref(true)
const searchQuery = ref('')
const dateFilter = ref('')
const typeFilter = ref('')
const categoryFilter = ref('')
const selectedRecord = ref(null)
const showModal = ref(false)
const showFilters = ref(false)

const openView = (record) => {
  selectedRecord.value = { ...record }
  showModal.value = true
}

const formatDate = (date) => {
  if (!date) return '—'

  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return '—'

  return parsed.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

const toDateKey = (date) => {
  if (!date) return ''

  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return ''

  return [
    parsed.getFullYear(),
    String(parsed.getMonth() + 1).padStart(2, '0'),
    String(parsed.getDate()).padStart(2, '0'),
  ].join('-')
}

const formatTime = (seconds) => {
  if (seconds === null || seconds === undefined || Number.isNaN(Number(seconds))) {
    return '—'
  }

  const totalSeconds = Math.max(0, Math.floor(Number(seconds)))
  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  const secs = totalSeconds % 60

  if (hours > 0) {
    return `${hours}h ${minutes}m ${secs}s`
  }

  return `${minutes}:${String(secs).padStart(2, '0')}`
}

const formatLabel = (value) => {
  if (!value) return '—'

  return String(value)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

const formatCoc = (value) => {
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  return `COC ${value}`
}

const formatPercentage = (value) => {
  const percentage = Number(value)
  if (!Number.isFinite(percentage)) return '0%'

  return `${Number.isInteger(percentage) ? percentage : percentage.toFixed(1)}%`
}

const displayScore = (record) => {
  const score = record.correct_answers ?? record.score
  const total = record.total_questions

  if (score !== null && score !== undefined && Number(total) > 0) {
    return `${score} / ${total}`
  }

  if (score !== null && score !== undefined) {
    return String(score)
  }

  return '—'
}

const getStatus = (record) => {
  if (record.status) {
    return formatLabel(record.status)
  }

  const percentage = Number(record.percentage ?? 0)
  const passingScore = Number(record.passing_score ?? 70)

  return percentage >= passingScore ? 'Passed' : 'Failed'
}

const statusClass = (record) => {
  const status = getStatus(record).toLowerCase()

  return status.includes('pass') || status.includes('complete')
    ? 'status-pass'
    : 'status-fail'
}

const assessmentIcon = (type) => {
  const value = String(type || '').toLowerCase()

  if (value.includes('rj45')) return 'fas fa-network-wired'
  if (value.includes('hardware')) return 'fas fa-screwdriver-wrench'
  if (value.includes('image')) return 'fas fa-image'
  if (value.includes('multiple')) return 'fas fa-list-check'

  return 'fas fa-file-alt'
}

const availableTypes = computed(() => [
  ...new Set(records.value.map((record) => record.quiz_type).filter(Boolean)),
])

const availableCategories = computed(() => [
  ...new Set(records.value.map((record) => record.category).filter(Boolean)),
])

const filteredRecords = computed(() => {
  const search = searchQuery.value.toLowerCase().trim()

  return records.value.filter((record) => {
    const title = String(record.quiz_title || '').toLowerCase()
    const description = String(record.quiz_description || '').toLowerCase()
    const type = String(record.quiz_type || '').toLowerCase()
    const category = String(record.category || '').toLowerCase()
    const date = record.completed_at || record.created_at

    const matchesSearch =
      !search ||
      title.includes(search) ||
      description.includes(search) ||
      type.includes(search) ||
      category.includes(search)

    const matchesDate = !dateFilter.value || toDateKey(date) === dateFilter.value
    const matchesType = !typeFilter.value || record.quiz_type === typeFilter.value
    const matchesCategory = !categoryFilter.value || record.category === categoryFilter.value

    return matchesSearch && matchesDate && matchesType && matchesCategory
  })
})

const uniqueAssessments = computed(() => {
  return new Set(
    filteredRecords.value
      .map((record) => record.quiz_id)
      .filter((id) => id !== null && id !== undefined)
  ).size
})

const fetchRecords = async () => {
  loading.value = true

  try {
    const { data } = await api.get('/api/records')

    records.value = Array.isArray(data?.results)
      ? data.results.map((record) => ({
          ...record,
          score: record.score !== null && record.score !== undefined
            ? Number(record.score)
            : null,
          correct_answers: record.correct_answers !== null && record.correct_answers !== undefined
            ? Number(record.correct_answers)
            : null,
          total_questions: record.total_questions !== null && record.total_questions !== undefined
            ? Number(record.total_questions)
            : 0,
          percentage: record.percentage !== null && record.percentage !== undefined
            ? Number(record.percentage)
            : 0,
          passing_score: record.passing_score !== null && record.passing_score !== undefined
            ? Number(record.passing_score)
            : 70,
          elapsed_time: record.elapsed_time !== null && record.elapsed_time !== undefined
            ? Number(record.elapsed_time)
            : null,
          questions: Array.isArray(record.questions) ? record.questions : [],
        }))
      : []
  } catch (error) {
    console.error('Failed to fetch records:', error.response?.data || error)
    records.value = []
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  if (!user.value) {
    await fetchUser()
  }

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

.records-container {
  --black: #111111;
  --white: #ffffff;
  --text: #222222;
  --muted: #737373;
  --light: #a3a3a3;
  --border: #e5e5e5;
  --soft-border: #f0f0f0;
  --surface: #fafafa;

  width: 100%;
  min-height: 100%;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1.5rem;
  background: var(--white);
  border-radius: 4px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03), 0 12px 32px rgba(0, 0, 0, 0.05);
}

.header-flex,
.title-wrap,
.records-summary,
.assessment-info,
.card-title-wrap,
.card-row {
  display: flex;
  align-items: center;
}

.header-flex {
  justify-content: space-between;
  gap: 1rem;
}

.title-wrap {
  gap: 0.75rem;
  min-width: 0;
}

.icon-box {
  width: 2.25rem;
  height: 2.25rem;
  display: grid;
  place-items: center;
  flex-shrink: 0;
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
}

.subtitle {
  margin: 0.2rem 0 0;
  color: var(--muted);
  font-size: 0.75rem;
}

.filter-toggle {
  display: none;
  width: 2.25rem;
  height: 2.25rem;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 0.55rem;
  background: var(--black);
  color: var(--white);
  cursor: pointer;
}

.filter-bar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.5rem;
}

.input-group {
  position: relative;
  width: 240px;
}

.input-group input,
.select-input,
.date-input {
  height: 36px;
  border: 1px solid var(--border);
  border-radius: 0.45rem;
  background: var(--surface);
  color: var(--text);
  font-size: 0.78rem;
}

.input-group input {
  width: 100%;
  padding: 0 2rem;
}

.select-input,
.date-input {
  padding: 0 0.7rem;
}

.input-group input:focus,
.select-input:focus,
.date-input:focus {
  outline: none;
  border-color: var(--black);
  background: var(--white);
}

.input-icon,
.clear-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
}

.input-icon {
  left: 0.7rem;
  color: var(--light);
  font-size: 0.75rem;
}

.clear-btn {
  right: 0.6rem;
  border: 0;
  background: transparent;
  color: var(--light);
  cursor: pointer;
}

.records-summary {
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

.table-wrapper {
  width: 100%;
  overflow-x: auto;
  border: 1px solid var(--border);
  border-radius: 0.65rem;
}

.records-table {
  width: 100%;
  min-width: 920px;
  border-collapse: collapse;
  table-layout: fixed;
}

.records-table th {
  padding: 0.7rem 0.75rem;
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
  padding: 0.75rem;
  border-bottom: 1px solid var(--soft-border);
  color: var(--text);
  font-size: 0.78rem;
  text-align: center;
  vertical-align: middle;
}

.records-table tbody tr:last-child td {
  border-bottom: 0;
}

.records-table tbody tr:hover {
  background: var(--surface);
}

.col-date { width: 105px; }
.col-coc { width: 85px; }
.col-type { width: 135px; }
.col-assessment { width: 230px; }
.col-category { width: 150px; }
.col-score { width: 105px; }
.col-status { width: 100px; }
.col-action { width: 65px; }

.assessment-info {
  gap: 0.6rem;
  text-align: left;
}

.assessment-icon {
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 0.4rem;
  color: var(--black);
  font-size: 0.72rem;
}

.assessment-text,
.card-title-text {
  min-width: 0;
}

.assessment-name {
  display: block;
  overflow: hidden;
  color: var(--black);
  font-size: 0.78rem;
  font-weight: 650;
  text-overflow: ellipsis;
  white-space: nowrap;
}

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

.score-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  align-items: center;
}

.score-display {
  color: var(--black);
  font-weight: 700;
}

.score-wrap small {
  color: var(--light);
  font-size: 0.64rem;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 58px;
  padding: 0.25rem 0.55rem;
  border-radius: 999px;
  font-size: 0.65rem;
  font-weight: 700;
  white-space: nowrap;
}

.status-pass {
  background: var(--black);
  color: var(--white);
}

.status-fail {
  background: var(--surface);
  border: 1px solid var(--border);
  color: var(--muted);
}

.btn-icon {
  width: 30px;
  height: 30px;
  display: inline-grid;
  place-items: center;
  border: 1px solid var(--border);
  border-radius: 0.4rem;
  background: var(--white);
  color: var(--black);
  cursor: pointer;
}

.btn-icon:hover {
  background: var(--black);
  border-color: var(--black);
  color: var(--white);
}

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
  padding: 0.85rem;
  border-bottom: 1px solid var(--soft-border);
}

.card-title-wrap {
  gap: 0.6rem;
  min-width: 0;
}

.card-header h4 {
  margin: 0;
  color: var(--black);
  font-size: 0.85rem;
  font-weight: 650;
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
  padding: 0.85rem;
}

.card-row {
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
  height: 34px;
  border: 0;
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

.loading,
.empty {
  display: flex;
  align-items: center;
  justify-content: center;
}

.loading {
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

.empty {
  flex-direction: column;
  padding: 3rem 1rem;
  text-align: center;
}

.empty-icon {
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  margin-bottom: 0.75rem;
  border: 1px solid var(--border);
  border-radius: 0.6rem;
  background: var(--surface);
  color: var(--muted);
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

@keyframes spin {
  to { transform: rotate(360deg); }
}

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
}

@media (max-width: 640px) {
  .table-wrapper {
    display: none;
  }

  .records-cards {
    display: flex;
  }

  .records-summary {
    width: 100%;
  }

  .summary-item {
    flex: 1;
  }
}
</style>
