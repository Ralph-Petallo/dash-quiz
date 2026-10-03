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
        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>

        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search assessment..."
          aria-label="Search assessment records"
        />

        <button
          v-if="searchQuery"
          class="clear-btn"
          type="button"
          @click="searchQuery = ''"
          aria-label="Clear search"
        >
          <i class="fas fa-times"></i>
        </button>
      </div>

      <select v-model="typeFilter" class="select-input" aria-label="Filter by assessment type">
        <option value="">All Types</option>
        <option v-for="type in filterOptions.types" :key="type" :value="type">
          {{ formatLabel(type) }}
        </option>
      </select>

      <select v-model="categoryFilter" class="select-input" aria-label="Filter by category">
        <option value="">All Categories</option>
        <option v-for="category in filterOptions.categories" :key="category" :value="category">
          {{ formatLabel(category) }}
        </option>
      </select>

      <input v-model="dateFilter" type="date" class="date-input" aria-label="Filter by date" />

      <select v-model.number="perPage" class="select-input per-page-select" aria-label="Records per page">
        <option :value="10">10 / page</option>
        <option :value="15">15 / page</option>
        <option :value="25">25 / page</option>
        <option :value="50">50 / page</option>
      </select>

      <button
        v-if="hasActiveFilters"
        type="button"
        class="reset-btn"
        @click="clearFilters"
      >
        Clear
      </button>
    </div>

    <div v-if="errorMessage" class="error-box">
      <span>{{ errorMessage }}</span>
      <button type="button" @click="loadRecords(pagination.current_page || 1, true)">Retry</button>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <span>Loading records...</span>
    </div>

    <template v-else>
      <div class="records-summary">
        <div class="summary-item">
          <span class="summary-label">Total Records</span>
          <strong>{{ pagination.total }}</strong>
        </div>

        <div class="summary-item">
          <span class="summary-label">Page</span>
          <strong>{{ pagination.current_page }} / {{ pagination.total_pages }}</strong>
        </div>

        <div class="summary-item">
          <span class="summary-label">Showing</span>
          <strong>{{ records.length }}</strong>
        </div>
      </div>

      <div v-if="records.length" class="records-display">
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
              <tr v-for="record in records" :key="record.id">
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
                      <i class="fas fa-file-alt"></i>
                    </div>

                    <div class="assessment-text">
                      <span class="assessment-name" :title="record.quiz_title">
                        {{ record.quiz_title || 'Untitled Assessment' }}
                      </span>
                      <span v-if="record.attempt_count" class="assessment-subtext">
                        Attempt #{{ record.attempt_count }}
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
                  <span class="score-display">
                    {{ displayScore(record) }}
                  </span>
                  <span class="score-percent">
                    {{ formatPercentage(record.percentage) }}
                  </span>
                </td>

                <td class="col-status">
                  <span :class="['status-badge', statusClass(record)]">
                    {{ getStatus(record) }}
                  </span>
                </td>

                <td class="col-action">
                  <button
                    class="btn-icon"
                    type="button"
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
          <article v-for="record in records" :key="record.id" class="record-card">
            <div class="card-header">
              <div class="card-title-wrap">
                <div class="assessment-icon">
                  <i class="fas fa-file-alt"></i>
                </div>

                <div class="card-title-copy">
                  <h4>{{ record.quiz_title || 'Untitled Assessment' }}</h4>
                  <p class="card-date">
                    {{ formatDate(record.completed_at || record.created_at) }}
                  </p>
                </div>
              </div>

              <span :class="['status-badge', statusClass(record)]">
                {{ getStatus(record) }}
              </span>
            </div>

            <div class="card-meta-row">
              <span class="data-tag">{{ formatCoc(record.coc_number) }}</span>
              <span class="data-tag">{{ formatLabel(record.quiz_type) }}</span>
            </div>

            <div class="card-body">
              <div class="card-row">
                <span class="card-label">Category</span>
                <span class="card-value">{{ record.category || '—' }}</span>
              </div>

              <div class="card-row">
                <span class="card-label">Score</span>
                <span class="card-value strong">{{ displayScore(record) }}</span>
              </div>

              <div class="card-row">
                <span class="card-label">Percentage</span>
                <span class="card-value">{{ formatPercentage(record.percentage) }}</span>
              </div>

              <div class="card-row">
                <span class="card-label">Attempt</span>
                <span class="card-value">#{{ record.attempt_count || 1 }}</span>
              </div>

              <div class="card-row">
                <span class="card-label">Time</span>
                <span class="card-value">{{ formatTime(record.elapsed_time) }}</span>
              </div>
            </div>

            <button class="btn-view-card" type="button" @click="openView(record)">
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
          {{ hasActiveFilters ? 'Try adjusting your filters.' : 'Complete an assessment to see your records here.' }}
        </small>
      </div>

      <div v-if="pagination.total_pages > 1" class="pagination-wrap">
        <div class="pagination-info">
          Showing {{ pagination.from || 0 }}-{{ pagination.to || 0 }} of {{ pagination.total }}
        </div>

        <div class="pagination-controls">
          <button
            type="button"
            class="page-btn"
            :disabled="pagination.current_page <= 1 || loading"
            @click="goToPage(pagination.current_page - 1)"
          >
            <i class="fas fa-chevron-left"></i>
          </button>

          <button
            v-for="page in visiblePages"
            :key="page"
            type="button"
            class="page-btn number"
            :class="{ active: page === pagination.current_page }"
            :disabled="loading"
            @click="goToPage(page)"
          >
            {{ page }}
          </button>

          <button
            type="button"
            class="page-btn"
            :disabled="pagination.current_page >= pagination.total_pages || loading"
            @click="goToPage(pagination.current_page + 1)"
          >
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </template>

    <RecordModal v-model="showModal" :record="selectedRecord" />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useUser } from '@/composables/useUser'
import recordRequest from '@/composables/recordRequest'
import RecordModal from '@/components/UserSide/RecordModal.vue'

const { user, fetchUser } = useUser()
const recordsApi = recordRequest()

const records = ref([])
const loading = ref(true)
const errorMessage = ref('')

const searchQuery = ref('')
const dateFilter = ref('')
const typeFilter = ref('')
const categoryFilter = ref('')
const perPage = ref(10)
const showFilters = ref(false)

const selectedRecord = ref(null)
const showModal = ref(false)

const pagination = reactive({
  total: 0,
  count: 0,
  per_page: 10,
  current_page: 1,
  total_pages: 1,
  from: 0,
  to: 0,
  has_more: false,
})

const filterOptions = reactive({
  types: [],
  categories: [],
})

const hasActiveFilters = computed(() => {
  return Boolean(
    searchQuery.value.trim() ||
    dateFilter.value ||
    typeFilter.value ||
    categoryFilter.value
  )
})

const visiblePages = computed(() => {
  const total = Math.max(1, Number(pagination.total_pages || 1))
  const current = Math.max(1, Number(pagination.current_page || 1))
  const start = Math.max(1, current - 2)
  const end = Math.min(total, current + 2)

  return Array.from({ length: end - start + 1 }, (_, index) => start + index)
})

const loadRecords = async (page = 1, force = false) => {
  loading.value = true
  errorMessage.value = ''

  try {
    const payload = await recordsApi.fetch(
      {
        page,
        per_page: perPage.value,
        search: searchQuery.value.trim(),
        quiz_type: typeFilter.value,
        category: categoryFilter.value,
        date: dateFilter.value,
      },
      { force }
    )

    records.value = Array.isArray(payload?.data) ? payload.data : []

    Object.assign(pagination, {
      total: Number(payload?.pagination?.total ?? 0),
      count: Number(payload?.pagination?.count ?? records.value.length),
      per_page: Number(payload?.pagination?.per_page ?? perPage.value),
      current_page: Number(payload?.pagination?.current_page ?? page),
      total_pages: Math.max(1, Number(payload?.pagination?.total_pages ?? 1)),
      from: Number(payload?.pagination?.from ?? 0),
      to: Number(payload?.pagination?.to ?? 0),
      has_more: Boolean(payload?.pagination?.has_more),
    })

    filterOptions.types = Array.isArray(payload?.filter_options?.types)
      ? payload.filter_options.types
      : []

    filterOptions.categories = Array.isArray(payload?.filter_options?.categories)
      ? payload.filter_options.categories
      : []
  } catch (error) {
    console.error('Failed to fetch records:', error.response?.data || error)
    records.value = []
    errorMessage.value = error.response?.data?.message || 'Unable to load assessment records.'
  } finally {
    loading.value = false
  }
}

const goToPage = async (page) => {
  const target = Math.min(
    Math.max(1, Number(page)),
    Math.max(1, Number(pagination.total_pages || 1))
  )

  if (target === pagination.current_page) return

  await loadRecords(target)
}

const openView = (record) => {
  selectedRecord.value = recordsApi.getCached(record.id) || record
  showModal.value = true
}

const clearFilters = () => {
  searchQuery.value = ''
  dateFilter.value = ''
  typeFilter.value = ''
  categoryFilter.value = ''
}

const formatDate = (date) => {
  if (!date) return '—'

  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return '—'

  return parsed.toLocaleDateString('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
  })
}

const formatTime = (seconds) => {
  if (seconds === null || seconds === undefined || Number.isNaN(Number(seconds))) {
    return '—'
  }

  const totalSeconds = Math.max(0, Math.floor(Number(seconds)))
  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  const secs = totalSeconds % 60

  if (hours > 0) return `${hours}h ${minutes}m ${secs}s`
  return `${minutes}:${String(secs).padStart(2, '0')}`
}

const formatLabel = (value) => {
  if (!value) return '—'

  return String(value)
    .replace(/[\_-]+/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

const formatCoc = (value) => {
  return value === null || value === undefined || value === ''
    ? 'COC —'
    : `COC ${value}`
}

const formatPercentage = (value) => {
  const percentage = Number(value)
  if (!Number.isFinite(percentage)) return '0%'
  return `${Number.isInteger(percentage) ? percentage : percentage.toFixed(1)}%`
}

const displayScore = (record) => {
  const score = record?.correct_answers ?? record?.score
  const total = Number(record?.total_questions ?? 0)

  if (score !== null && score !== undefined && total > 0) {
    return `${score} / ${total}`
  }

  return score !== null && score !== undefined ? String(score) : '—'
}

const getStatus = (record) => {
  if (record?.status) return formatLabel(record.status)

  return Number(record?.percentage ?? 0) >= Number(record?.passing_score ?? 70)
    ? 'Passed'
    : 'Failed'
}

const statusClass = (record) => {
  const status = String(getStatus(record)).toLowerCase()
  return status.includes('pass') || status.includes('complete')
    ? 'status-pass'
    : 'status-fail'
}

let filterTimer = null
watch(
  [searchQuery, dateFilter, typeFilter, categoryFilter, perPage],
  () => {
    window.clearTimeout(filterTimer)
    filterTimer = window.setTimeout(() => loadRecords(1), 300)
  }
)

onMounted(async () => {
  if (!user.value) {
    await fetchUser()
  }

  if (!user.value) {
    loading.value = false
    errorMessage.value = 'Unable to restore your authenticated session.'
    return
  }

  await loadRecords(1)
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
  min-width: 0;
  min-height: 100%;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1.5rem;
  background: var(--white);
  border-radius: 4px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03), 0 12px 32px rgba(0, 0, 0, 0.05);
}

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
  display: grid;
  place-items: center;
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
  flex-wrap: wrap;
  gap: 0.5rem;
}

.input-group {
  position: relative;
  width: min(260px, 100%);
}

.input-group input,
.select-input,
.date-input {
  height: 36px;
  border: 1px solid var(--border);
  border-radius: 0.45rem;
  background: var(--surface);
  color: var(--text);
  font-size: 0.76rem;
}

.input-group input {
  width: 100%;
  padding: 0 2rem;
}

.select-input,
.date-input {
  padding: 0 0.65rem;
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
  border: 0;
  background: none;
  color: var(--light);
  cursor: pointer;
}

.reset-btn {
  height: 36px;
  padding: 0 0.8rem;
  border: 1px solid var(--border);
  border-radius: 0.45rem;
  background: var(--white);
  color: var(--muted);
  font-size: 0.72rem;
  font-weight: 700;
  cursor: pointer;
}

.reset-btn:hover {
  background: var(--black);
  border-color: var(--black);
  color: var(--white);
}

.error-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 0.9rem;
  border: 1px solid var(--border);
  border-radius: 0.55rem;
  background: var(--surface);
  color: var(--muted);
  font-size: 0.75rem;
}

.error-box button {
  border: 0;
  border-radius: 0.4rem;
  padding: 0.4rem 0.7rem;
  background: var(--black);
  color: var(--white);
  font-size: 0.7rem;
  cursor: pointer;
}

.loading {
  min-height: 200px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.65rem;
  color: var(--muted);
  font-size: 0.72rem;
}

.spinner {
  width: 28px;
  height: 28px;
  border: 2px solid var(--border);
  border-top-color: var(--black);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

.records-summary {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
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

.records-display,
.table-wrapper {
  width: 100%;
  min-width: 0;
}

.table-wrapper {
  overflow-x: auto;
  border: 1px solid var(--border);
  border-radius: 0.65rem;
}

.records-table {
  width: 100%;
  min-width: 900px;
  border-collapse: collapse;
  table-layout: fixed;
}

.records-table th {
  padding: 0.65rem 0.7rem;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  color: var(--muted);
  font-size: 0.62rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  text-align: center;
  white-space: nowrap;
}

.records-table td {
  padding: 0.7rem;
  border-bottom: 1px solid var(--soft-border);
  color: var(--text);
  font-size: 0.75rem;
  text-align: center;
  vertical-align: middle;
}

.records-table tbody tr:last-child td {
  border-bottom: 0;
}

.records-table tbody tr:hover {
  background: var(--surface);
}

.col-date { width: 110px; }
.col-coc { width: 82px; }
.col-type { width: 125px; }
.col-assessment { width: 240px; }
.col-category { width: 145px; }
.col-score { width: 105px; }
.col-status { width: 100px; }
.col-action { width: 65px; }

.assessment-info {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  min-width: 0;
  text-align: left;
}

.assessment-icon {
  width: 30px;
  height: 30px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 0.45rem;
  color: var(--black);
  font-size: 0.7rem;
}

.assessment-text,
.card-title-copy {
  min-width: 0;
}

.assessment-name {
  display: block;
  overflow: hidden;
  color: var(--black);
  font-size: 0.75rem;
  font-weight: 650;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.assessment-subtext {
  display: block;
  margin-top: 0.1rem;
  color: var(--light);
  font-size: 0.62rem;
}

.data-tag {
  display: inline-flex;
  align-items: center;
  padding: 0.22rem 0.45rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 0.3rem;
  color: var(--muted);
  font-size: 0.66rem;
  font-weight: 600;
}

.category-text {
  color: var(--muted);
  font-size: 0.72rem;
  overflow-wrap: anywhere;
}

.score-display {
  display: block;
  color: var(--black);
  font-weight: 750;
}

.score-percent {
  display: block;
  margin-top: 0.1rem;
  color: var(--light);
  font-size: 0.62rem;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.24rem 0.52rem;
  border-radius: 999px;
  font-size: 0.64rem;
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
  border-radius: 0.7rem;
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
  min-width: 0;
  display: flex;
  gap: 0.6rem;
}

.card-title-copy h4 {
  margin: 0;
  color: var(--black);
  font-size: 0.84rem;
  font-weight: 700;
  line-height: 1.35;
}

.card-date {
  margin: 0.18rem 0 0;
  color: var(--light);
  font-size: 0.67rem;
}

.card-meta-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  padding: 0.7rem 0.85rem 0;
}

.card-body {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
  padding: 0.8rem 0.85rem;
}

.card-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.card-label {
  color: var(--muted);
  font-size: 0.66rem;
  font-weight: 650;
  text-transform: uppercase;
}

.card-value {
  color: var(--text);
  font-size: 0.73rem;
  text-align: right;
  overflow-wrap: anywhere;
}

.card-value.strong {
  color: var(--black);
  font-weight: 750;
}

.btn-view-card {
  width: calc(100% - 1rem);
  margin: 0 0.5rem 0.5rem;
  min-height: 34px;
  border: 0;
  border-radius: 0.45rem;
  background: var(--black);
  color: var(--white);
  font-size: 0.7rem;
  font-weight: 700;
  cursor: pointer;
}

.btn-view-card i {
  margin-right: 0.35rem;
}

.empty {
  min-height: 220px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  text-align: center;
}

.empty-icon {
  width: 44px;
  height: 44px;
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
  font-size: 0.84rem;
  font-weight: 650;
}

.empty small {
  color: var(--light);
  font-size: 0.7rem;
}

.pagination-wrap {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.pagination-info {
  color: var(--muted);
  font-size: 0.7rem;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.page-btn {
  min-width: 32px;
  height: 32px;
  padding: 0 0.45rem;
  border: 1px solid var(--border);
  border-radius: 0.4rem;
  background: var(--white);
  color: var(--muted);
  font-size: 0.68rem;
  font-weight: 700;
  cursor: pointer;
}

.page-btn:hover:not(:disabled),
.page-btn.active {
  background: var(--black);
  border-color: var(--black);
  color: var(--white);
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 900px) {
  .filter-bar {
    justify-content: flex-start;
  }
}

@media (max-width: 768px) {
  .records-container {
    padding: 1rem;
  }

  .filter-toggle {
    display: flex;
  }

  .filter-bar {
    max-height: 0;
    overflow: hidden;
    flex-direction: column;
    align-items: stretch;
    flex-wrap: nowrap;
    transition: max-height 0.25s ease;
  }

  .filter-bar.filters-open {
    max-height: 380px;
  }

  .input-group,
  .select-input,
  .date-input,
  .per-page-select,
  .reset-btn {
    width: 100%;
  }
}

@media (max-width: 640px) {
  .records-container {
    padding: 0.85rem;
    gap: 0.75rem;
  }

  .records-summary {
    width: 100%;
  }

  .summary-item {
    flex: 1 1 calc(33.333% - 0.5rem);
    justify-content: space-between;
  }

  .table-wrapper {
    display: none;
  }

  .records-cards {
    display: flex;
  }

  .pagination-wrap {
    flex-direction: column;
    align-items: stretch;
  }

  .pagination-info {
    text-align: center;
  }

  .pagination-controls {
    justify-content: center;
    flex-wrap: wrap;
  }
}

@media (max-width: 480px) {
  .records-container {
    padding: 0.7rem;
  }

  .summary-item {
    flex: 1 1 100%;
  }

  .card-header {
    align-items: flex-start;
  }
}
</style>
