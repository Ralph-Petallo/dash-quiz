<template>
  <section class="records-page">
    <header class="page-header">
      <div class="title-group">
        <div class="title-icon" aria-hidden="true">
          <i class="fas fa-clipboard-list"></i>
        </div>

        <div>
          <span class="eyebrow">CSS Prep · Performance History</span>
          <h1>Assessment Records</h1>
          <p>Review your saved assessment attempts and open any record for full details.</p>
        </div>
      </div>

      <div class="header-count" aria-label="Total assessment records">
        <strong>{{ pagination.total }}</strong>
        <span>{{ pagination.total === 1 ? 'record' : 'records' }}</span>
      </div>
    </header>

    <div class="toolbar-card">
      <div class="search-box">
        <i class="fas fa-search" aria-hidden="true"></i>
        <input v-model="searchQuery" type="search" placeholder="Search assessment records"
          aria-label="Search assessment records" />

        <button v-if="searchQuery" type="button" class="search-clear" aria-label="Clear search"
          @click="searchQuery = ''">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <button type="button" class="filter-btn" :class="{ active: showFilters || hasActiveFilters }"
        @click="showFilters = !showFilters">
        <i class="fas fa-sliders"></i>
        Filters
        <span v-if="activeFilterCount" class="filter-count">{{ activeFilterCount }}</span>
      </button>
    </div>

    <Transition name="filter-panel">
      <div v-if="showFilters" class="filter-panel">
        <label class="filter-field">
          <span>Assessment type</span>
          <select v-model="typeFilter">
            <option value="">All types</option>
            <option v-for="type in filterOptions.types" :key="type" :value="type">
              {{ formatLabel(type) }}
            </option>
          </select>
        </label>

        <label class="filter-field">
          <span>Category</span>
          <select v-model="categoryFilter">
            <option value="">All categories</option>
            <option v-for="category in filterOptions.categories" :key="category" :value="category">
              {{ formatLabel(category) }}
            </option>
          </select>
        </label>

        <label class="filter-field">
          <span>Completed date</span>
          <input v-model="dateFilter" type="date" />
        </label>

        <label class="filter-field compact-field">
          <span>Rows</span>
          <select v-model.number="perPage">
            <option :value="10">10</option>
            <option :value="15">15</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
        </label>

        <button v-if="hasActiveFilters" type="button" class="clear-filters" @click="clearFilters">
          Clear filters
        </button>
      </div>
    </Transition>

    <div v-if="errorMessage" class="notice error-notice">
      <div>
        <i class="fas fa-triangle-exclamation"></i>
        <span>{{ errorMessage }}</span>
      </div>

      <button type="button" @click="loadRecords(pagination.current_page || 1, true)">
        Retry
      </button>
    </div>

    <div v-if="loading" class="loading-state">
      <span class="spinner"></span>
      <strong>Loading assessment records</strong>
      <small>Getting your latest saved attempts.</small>
    </div>

    <template v-else>
      <div class="list-meta">
        <span>
          Showing
          <strong>{{ pagination.from || 0 }}-{{ pagination.to || 0 }}</strong>
          of <strong>{{ pagination.total }}</strong>
        </span>

        <span v-if="hasActiveFilters" class="filtered-label">
          <i class="fas fa-filter"></i>
          Filtered results
        </span>
      </div>

      <div v-if="records.length" class="records-content">
        <div class="table-shell">
          <table class="records-table">
            <thead>
              <tr>
                <th scope="col">Assessment</th>
                <th scope="col" class="result-column">Performance Result</th>
                <th scope="col" class="completed-column">Completed</th>
                <th scope="col" class="action-column">Details</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="record in records" :key="record.id">
                <td>
                  <div class="assessment-cell">
                    <div class="assessment-icon" aria-hidden="true">
                      <i :class="assessmentIcon(record)"></i>
                    </div>

                    <div class="assessment-copy">
                      <strong :title="record.quiz_title">
                        {{ record.quiz_title || 'Untitled Assessment' }}
                      </strong>

                      <div class="assessment-tags">
                        <span>{{ formatCoc(record.coc_number) }}</span>
                        <span v-if="record.quiz_type">{{ formatLabel(record.quiz_type) }}</span>
                      </div>
                    </div>
                  </div>
                </td>

                <td class="result-column">
                  <div class="result-cell">
                    <strong>{{ formatPercentage(record.percentage) }}</strong>
                    <span :class="['status-pill', statusClass(record)]">
                      {{ getStatus(record) }}
                    </span>
                    <!-- <small v-if="displayScore(record) !== '—'">
                      {{ displayScore(record) }}
                    </small> -->
                  </div>
                </td>

                <td class="completed-column">
                  <div class="completed-cell">
                    <strong>{{ formatDate(record.completed_at || record.created_at) }}</strong>
                    <!-- <small>Attempt #{{ record.attempt_count || 1 }}</small> -->
                  </div>
                </td>

                <td class="action-column">
                  <button type="button" class="view-btn"
                    :aria-label="`View details for ${record.quiz_title || 'assessment'}`" @click="openView(record)">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                    <span>View</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mobile-records">
          <article v-for="record in records" :key="`mobile-${record.id}`" class="record-card">
            <div class="record-card-top">
              <div class="assessment-cell">
                <div class="assessment-icon" aria-hidden="true">
                  <i :class="assessmentIcon(record)"></i>
                </div>

                <div class="assessment-copy">
                  <strong>{{ record.quiz_title || 'Untitled Assessment' }}</strong>
                  <div class="assessment-tags">
                    <span>{{ formatCoc(record.coc_number) }}</span>
                    <span v-if="record.quiz_type">{{ formatLabel(record.quiz_type) }}</span>
                  </div>
                </div>
              </div>

              <span :class="['status-pill', statusClass(record)]">
                {{ getStatus(record) }}
              </span>
            </div>

            <div class="record-card-result">
              <div>
                <span>Result</span>
                <strong>{{ formatPercentage(record.percentage) }}</strong>
              </div>

              <div>
                <span>Completed</span>
                <strong>{{ formatDate(record.completed_at || record.created_at) }}</strong>
              </div>

              <div>
                <span>Attempt</span>
                <strong>#{{ record.attempt_count || 1 }}</strong>
              </div>
            </div>

            <button type="button" class="mobile-view-btn" @click="openView(record)">
              View record details
              <i class="fas fa-arrow-right"></i>
            </button>
          </article>
        </div>
      </div>

      <div v-else class="empty-state">
        <div class="empty-icon">
          <i class="fas fa-clipboard-check"></i>
        </div>
        <h3>No assessment records found</h3>
        <p>
          {{
            hasActiveFilters
              ? 'Try clearing or adjusting your filters.'
              : 'Complete an assessment and your saved result will appear here.'
          }}
        </p>
      </div>

      <footer v-if="pagination.total_pages > 1" class="pagination-bar">
        <span>
          Page {{ pagination.current_page }} of {{ pagination.total_pages }}
        </span>

        <div class="pagination-controls">
          <button type="button" class="page-btn" :disabled="pagination.current_page <= 1 || loading"
            aria-label="Previous page" @click="goToPage(pagination.current_page - 1)">
            <i class="fas fa-chevron-left"></i>
          </button>

          <button v-for="page in visiblePages" :key="page" type="button" class="page-btn number"
            :class="{ active: page === pagination.current_page }" :disabled="loading" @click="goToPage(page)">
            {{ page }}
          </button>

          <button type="button" class="page-btn"
            :disabled="pagination.current_page >= pagination.total_pages || loading" aria-label="Next page"
            @click="goToPage(pagination.current_page + 1)">
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </footer>
    </template>

    <RecordModal v-model="showModal" :record="selectedRecord" />
  </section>
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

const activeFilterCount = computed(() => {
  return [
    searchQuery.value.trim(),
    dateFilter.value,
    typeFilter.value,
    categoryFilter.value,
  ].filter(Boolean).length
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
    console.error('Failed to fetch records:', error?.response?.data || error)
    records.value = []
    errorMessage.value =
      error?.response?.data?.message || 'Unable to load assessment records.'
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

const assessmentIcon = (record) => {
  const value = String(record?.quiz_type ?? '').toLowerCase()

  if (value.includes('rj45') || value.includes('network')) {
    return 'fas fa-network-wired'
  }

  if (
    value.includes('hardware') ||
    value.includes('procedure') ||
    value.includes('assembly') ||
    value.includes('drag')
  ) {
    return 'fas fa-screwdriver-wrench'
  }

  if (value.includes('server')) {
    return 'fas fa-server'
  }

  return 'fas fa-file-lines'
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

// const displayScore = (record) => {
//   const score = record?.correct_answers ?? record?.score
//   const total = Number(record?.total_questions ?? 0)

//   if (score !== null && score !== undefined && total > 0) {
//     return `${score} / ${total}`
//   }

//   return score !== null && score !== undefined ? String(score) : '—'
// }

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

.records-page {
  --ink: #111111;
  --text: #2a2a2a;
  --muted: #727272;
  --soft: #f7f7f7;
  --surface: #ffffff;
  --border: #e7e7e7;
  --border-strong: #d8d8d8;

  width: 100%;
  max-width: 1200px;
  min-width: 0;
  min-height: 100%;
  margin-inline: auto;
  padding: clamp(12px, 2vw, 24px);

  display: flex;
  flex-direction: column;
  gap: 16px;

  color: var(--text);
  background: transparent;
  overflow-wrap: anywhere;
}

.records-page button,
.records-page input,
.records-page select {
  max-width: 100%;
  font-family: inherit;
}

.records-page button {
  -webkit-tap-highlight-color: transparent;
}

.records-page input,
.records-page select {
  min-width: 0;
}

.records-page :focus-visible {
  outline: 2px solid #555;
  outline-offset: 3px;
}

/* Header */

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  min-width: 0;
  padding: 22px 24px;
  border: 1px solid var(--border);
  border-radius: 16px;
  background: var(--surface);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.035);
}

.title-group {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 14px;
}

.title-icon {
  width: 46px;
  height: 46px;
  flex: 0 0 46px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: var(--ink);
  color: #fff;
  font-size: 15px;
}

.eyebrow {
  display: block;
  margin-bottom: 4px;
  color: var(--muted);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.page-header h1 {
  margin: 0;
  color: var(--ink);
  font-size: clamp(20px, 2vw, 26px);
  line-height: 1.2;
  overflow-wrap: anywhere;
}

.page-header p {
  margin: 6px 0 0;
  color: var(--muted);
  font-size: 13px;
  line-height: 1.5;
}

.header-count {
  min-width: 92px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  padding-left: 22px;
  border-left: 1px solid var(--border);
}

.header-count strong {
  color: var(--ink);
  font-size: 28px;
  line-height: 1;
}

.header-count span {
  margin-top: 5px;
  color: var(--muted);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

/* Toolbar and search */

.toolbar-card {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: var(--surface);
}

.search-box {
  position: relative;
  flex: 1 1 auto;
  min-width: 0;
}

.search-box>i {
  position: absolute;
  left: 13px;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
  font-size: 12px;
  pointer-events: none;
}

.search-box input {
  width: 100%;
  height: 42px;
  padding: 0 38px;
  border: 1px solid transparent;
  border-radius: 10px;
  outline: none;
  background: var(--soft);
  color: var(--text);
  font-size: 13px;
  transition: border-color 0.18s ease, background 0.18s ease;
}

.search-box input:focus {
  border-color: var(--border-strong);
  background: #fff;
}

.search-box input::placeholder {
  color: #999;
}

.search-clear {
  position: absolute;
  right: 9px;
  top: 50%;
  width: 28px;
  height: 28px;
  transform: translateY(-50%);
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: #999;
  cursor: pointer;
}

.search-clear:hover {
  background: #ededed;
  color: var(--ink);
}

.filter-btn {
  min-height: 42px;
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 14px;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: #fff;
  color: var(--text);
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.18s ease, color 0.18s ease;
}

.filter-btn:hover {
  border-color: var(--ink);
}

.filter-btn.active {
  border-color: var(--ink);
  background: var(--ink);
  color: #fff;
}

.filter-count {
  min-width: 20px;
  height: 20px;
  display: inline-grid;
  place-items: center;
  padding: 0 5px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.16);
  font-size: 10px;
}

/* Filter panel */

.filter-panel {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr)) 100px auto;
  gap: 10px;
  align-items: end;
  min-width: 0;
  padding: 14px;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: var(--surface);
}

.filter-field {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.filter-field>span {
  color: var(--muted);
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.filter-field select,
.filter-field input {
  width: 100%;
  min-width: 0;
  height: 38px;
  padding: 0 10px;
  border: 1px solid var(--border);
  border-radius: 9px;
  outline: none;
  background: var(--soft);
  color: var(--text);
  font-size: 12px;
}

.filter-field select:focus,
.filter-field input:focus {
  border-color: var(--border-strong);
  background: #fff;
}

.clear-filters {
  min-height: 38px;
  padding: 0 13px;
  border: 0;
  border-radius: 9px;
  background: var(--soft);
  color: var(--muted);
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
  cursor: pointer;
  transition: background 0.18s ease, color 0.18s ease;
}

.clear-filters:hover {
  background: var(--ink);
  color: #fff;
}

.filter-panel-enter-active,
.filter-panel-leave-active {
  transition: opacity 0.16s ease, transform 0.16s ease;
}

.filter-panel-enter-from,
.filter-panel-leave-to {
  opacity: 0;
  transform: translateY(-5px);
}

/* Notices */

.notice {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 12px 14px;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--surface);
}

.notice>div {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  font-size: 12px;
}

.notice>div i {
  flex-shrink: 0;
}

.notice button {
  flex-shrink: 0;
  border: 0;
  border-radius: 8px;
  padding: 8px 12px;
  background: var(--ink);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
}

/* Loading state */

.loading-state {
  min-height: 280px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 24px;
  border: 1px solid var(--border);
  border-radius: 16px;
  background: var(--surface);
  text-align: center;
}

.spinner {
  width: 30px;
  height: 30px;
  margin-bottom: 4px;
  border: 2px solid var(--border);
  border-top-color: var(--ink);
  border-radius: 50%;
  animation: spin 0.75s linear infinite;
}

.loading-state strong {
  color: var(--ink);
  font-size: 13px;
}

.loading-state small {
  color: var(--muted);
  font-size: 11px;
}

/* Results metadata */

.list-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 0 4px;
  color: var(--muted);
  font-size: 11px;
}

.list-meta strong {
  color: var(--ink);
}

.filtered-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* Records layout */

.records-content {
  width: 100%;
  min-width: 0;
  container-type: inline-size;
}

.table-shell {
  width: 100%;
  min-width: 0;
  overflow: hidden;
  border: 1px solid var(--border);
  border-radius: 16px;
  background: var(--surface);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.035);
}

.records-table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

.records-table th {
  padding: 13px 16px;
  border-bottom: 1px solid var(--border);
  background: #fafafa;
  color: var(--muted);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-align: center;
  text-transform: uppercase;
  overflow-wrap: anywhere;
}

.records-table td {
  padding: 15px 16px;
  border-bottom: 1px solid #f1f1f1;
  vertical-align: middle;
}

.records-table tbody tr:last-child td {
  border-bottom: 0;
}

.records-table tbody tr {
  transition: background 0.15s ease;
}

.records-table tbody tr:hover {
  background: #fafafa;
}

.result-column {
  width: 150px;
}

.completed-column {
  width: 155px;
}

.action-column {
  width: 90px;
  text-align: right !important;
}

/* Assessment details */

.assessment-cell {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.assessment-icon {
  width: 40px;
  height: 40px;
  flex: 0 0 40px;
  display: grid;
  place-items: center;
  border: 1px solid var(--border);
  border-radius: 11px;
  background: var(--soft);
  color: var(--ink);
  font-size: 13px;
}

.assessment-copy {
  flex: 1 1 auto;
  min-width: 0;
}

.assessment-copy>strong {
  display: block;
  overflow: hidden;
  color: var(--ink);
  font-size: 13px;
  font-weight: 750;
  line-height: 1.4;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.assessment-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  margin-top: 6px;
}

.assessment-tags span {
  max-width: 100%;
  padding: 3px 7px;
  border: 1px solid var(--border);
  border-radius: 999px;
  background: #fff;
  color: var(--muted);
  font-size: 9px;
  font-weight: 700;
  overflow-wrap: anywhere;
}

/* Result and score */

.result-cell,
.completed-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  min-width: 0;
  text-align: center;
}

.result-cell>strong {
  color: var(--ink);
  font-size: 17px;
  line-height: 1;
}

.result-cell small,
.completed-cell small {
  color: #9a9a9a;
  font-size: 10px;
}

.completed-cell>strong {
  color: var(--text);
  font-size: 12px;
  font-weight: 700;
  line-height: 1.4;
}

/* Status pills */

.status-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  max-width: 100%;
  padding: 4px 8px;
  border-radius: 999px;
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  overflow-wrap: anywhere;
}

.status-pass {
  background: var(--ink);
  color: #fff;
}

.status-fail {
  border: 1px solid var(--border-strong);
  background: #fff;
  color: var(--muted);
}

/* Desktop view button */

.view-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  min-height: 34px;
  padding: 0 10px;
  border: 1px solid var(--border);
  border-radius: 9px;
  background: #fff;
  color: var(--ink);
  font-size: 10px;
  font-weight: 800;
  white-space: nowrap;
  cursor: pointer;
  transition: border-color 0.16s ease, background 0.16s ease,
    color 0.16s ease;
}

.view-btn:hover {
  border-color: var(--ink);
  background: var(--ink);
  color: #fff;
}

/* Mobile records */

.mobile-records {
  display: none;
  flex-direction: column;
  gap: 10px;
  min-width: 0;
}

.record-card {
  min-width: 0;
  overflow: hidden;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: var(--surface);
}

.record-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  min-width: 0;
  padding: 14px;
}

.record-card-top .assessment-cell {
  flex: 1 1 auto;
}

.record-card-top .status-pill {
  flex-shrink: 0;
  margin-top: 2px;
}

.record-card-result {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  border-top: 1px solid #f0f0f0;
  border-bottom: 1px solid #f0f0f0;
  background: #fafafa;
}

.record-card-result>div {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 11px 12px;
  border-right: 1px solid #ededed;
}

.record-card-result>div:last-child {
  border-right: 0;
}

.record-card-result span {
  color: var(--muted);
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
}

.record-card-result strong {
  overflow: hidden;
  color: var(--ink);
  font-size: 12px;
  line-height: 1.4;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mobile-view-btn {
  width: calc(100% - 20px);
  min-height: 38px;
  margin: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 8px 12px;
  border: 0;
  border-radius: 10px;
  background: var(--ink);
  color: #fff;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
  transition: background 0.16s ease;
}

.mobile-view-btn:hover {
  background: #333;
}

/* Empty state */

.empty-state {
  min-height: 280px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 30px;
  border: 1px dashed var(--border-strong);
  border-radius: 16px;
  background: var(--surface);
  text-align: center;
}

.empty-icon {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  margin-bottom: 12px;
  border-radius: 13px;
  background: var(--soft);
  color: var(--muted);
}

.empty-state h3 {
  margin: 0;
  color: var(--ink);
  font-size: 14px;
}

.empty-state p {
  max-width: 420px;
  margin: 7px 0 0;
  color: var(--muted);
  font-size: 11px;
  line-height: 1.6;
}

/* Pagination */

.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  min-width: 0;
  padding: 4px 2px 0;
  color: var(--muted);
  font-size: 11px;
}

.pagination-controls {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 5px;
}

.page-btn {
  min-width: 34px;
  height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 9px;
  border: 1px solid var(--border);
  border-radius: 9px;
  background: #fff;
  color: var(--muted);
  font-size: 10px;
  font-weight: 800;
  cursor: pointer;
  transition: border-color 0.16s ease, background 0.16s ease,
    color 0.16s ease;
}

.page-btn:hover:not(:disabled),
.page-btn.active {
  border-color: var(--ink);
  background: var(--ink);
  color: #fff;
}

.page-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

/* Animations */

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Container-based responsive table-to-card switch */

@container (max-width: 760px) {
  .table-shell {
    display: none;
  }

  .mobile-records {
    display: flex;
  }
}

@container (max-width: 420px) {
  .record-card-top {
    flex-direction: column;
    gap: 10px;
  }

  .record-card-top .status-pill {
    align-self: flex-start;
  }

  .record-card-result {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .record-card-result>div:nth-child(2) {
    border-right: 0;
  }

  .record-card-result>div:nth-child(3) {
    grid-column: 1 / -1;
    border-top: 1px solid #ededed;
    border-right: 0;
  }
}

/* Tablet and smaller desktop */

@media (max-width: 980px) {
  .filter-panel {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .clear-filters {
    width: 100%;
  }

  .result-column {
    width: 125px;
  }

  .completed-column {
    width: 140px;
  }

  .action-column {
    width: 82px;
  }

  .records-table th {
    padding: 12px 10px;
  }

  .records-table td {
    padding: 13px 10px;
  }
}

/* Tablet */

@media (max-width: 760px) {
  .records-page {
    width: 100%;
    padding: 12px;
    gap: 12px;
  }

  .page-header {
    align-items: flex-start;
    padding: 16px;
    gap: 16px;
  }

  .header-count {
    min-width: auto;
    padding-left: 12px;
  }

  .header-count strong {
    font-size: 24px;
  }

  .toolbar-card {
    align-items: stretch;
  }

  .filter-panel {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .records-table th,
  .records-table td {
    padding: 12px 10px;
  }

  .pagination-bar {
    align-items: center;
  }
}

/* Mobile */

@media (max-width: 520px) {
  .records-page {
    padding: 8px;
    gap: 10px;
  }

  .page-header {
    flex-direction: column;
    gap: 14px;
    padding: 14px;
    border-radius: 13px;
  }

  .title-group {
    align-items: flex-start;
    gap: 10px;
    width: 100%;
  }

  .title-icon {
    width: 38px;
    height: 38px;
    flex-basis: 38px;
    border-radius: 10px;
  }

  .eyebrow {
    font-size: 9px;
  }

  .page-header h1 {
    font-size: 21px;
  }

  .page-header p {
    font-size: 12px;
  }

  .header-count {
    width: 100%;
    flex-direction: row;
    align-items: baseline;
    gap: 6px;
    padding: 12px 0 0;
    border-left: 0;
    border-top: 1px solid var(--border);
  }

  .header-count strong {
    font-size: 20px;
  }

  .header-count span {
    margin-top: 0;
  }

  .toolbar-card {
    flex-direction: column;
    padding: 8px;
    border-radius: 12px;
  }

  .search-box,
  .search-box input {
    width: 100%;
  }

  .filter-btn {
    width: 100%;
    justify-content: center;
  }

  .filter-panel {
    grid-template-columns: minmax(0, 1fr);
    padding: 12px;
  }

  .notice {
    align-items: flex-start;
    flex-direction: column;
  }

  .notice button {
    align-self: flex-end;
  }

  .list-meta {
    align-items: flex-start;
    flex-direction: column;
    gap: 6px;
    padding-inline: 2px;
  }

  .record-card-top {
    padding: 12px;
  }

  .record-card-result>div {
    padding: 10px 8px;
  }

  .record-card-result strong {
    font-size: 11px;
  }

  .empty-state {
    min-height: 230px;
    padding: 22px 14px;
  }

  .pagination-bar {
    align-items: center;
    flex-direction: column;
    gap: 10px;
    padding-top: 8px;
  }

  .pagination-controls {
    justify-content: center;
    max-width: 100%;
  }

  .page-btn {
    min-width: 32px;
    height: 32px;
    padding-inline: 7px;
  }
}

/* Very small phones */

@media (max-width: 360px) {
  .records-page {
    padding: 5px;
    gap: 8px;
  }

  .page-header {
    padding: 12px;
  }

  .title-group {
    gap: 8px;
  }

  .title-icon {
    width: 34px;
    height: 34px;
    flex-basis: 34px;
    font-size: 12px;
  }

  .page-header h1 {
    font-size: 19px;
  }

  .page-header p {
    font-size: 11px;
  }

  .assessment-cell {
    gap: 8px;
  }

  .assessment-icon {
    width: 34px;
    height: 34px;
    flex-basis: 34px;
    border-radius: 9px;
  }

  .assessment-copy>strong {
    font-size: 12px;
  }

  .record-card-result span {
    font-size: 8px;
  }

  .record-card-result strong {
    font-size: 10px;
  }

  .pagination-controls {
    gap: 3px;
  }

  .page-btn {
    min-width: 29px;
    height: 30px;
    padding-inline: 5px;
  }
}

/* Reduced motion accessibility */

@media (prefers-reduced-motion: reduce) {

  *,
  *::before,
  *::after {
    scroll-behavior: auto !important;
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>

