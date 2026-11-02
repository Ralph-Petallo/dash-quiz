<template>
  <main class="page-wrapper">
    <section class="admin-section">
      <header class="page-header">
        <div class="header-text">
          <p class="eyebrow">ADMINISTRATION</p>
          <h1 class="section-title">Assessment Records</h1>
          <p class="section-subtitle">
            Review student results and saved answers.
          </p>
        </div>

        <div class="header-tools">
          <div class="record-count">
            <strong>{{ filteredRecords.length }}</strong>
            <span>{{ filteredRecords.length === 1 ? 'record' : 'records' }}</span>
          </div>

          <label class="search-wrap">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input v-model="searchQuery" type="search" placeholder="Search student or assessment"
              aria-label="Search student or assessment" />
          </label>

          <button class="icon-button" type="button" title="Refresh records" aria-label="Refresh records"
            :disabled="loading" @click="fetchRecords">
            <i class="fa-solid fa-rotate" :class="{ spinning: loading }" aria-hidden="true"></i>
          </button>
        </div>
      </header>

      <div v-if="filterUserId" class="user-filter-banner" role="status">
        <div class="user-filter-info">
          <i class="fa-regular fa-user" aria-hidden="true"></i>
          <span>
            Showing records for <strong>{{ filteredUserName }}</strong>
            <small>ID #{{ filterUserId }}</small>
          </span>
        </div>

        <button type="button" class="secondary-button" @click="clearUserFilter">
          Show all students
        </button>
      </div>

      <nav class="coc-tabs" aria-label="Filter records by COC">
        <button v-for="tab in cocTabs" :key="tab.value" type="button" class="coc-tab"
          :class="{ 'is-active': cocFilter === tab.value }" :aria-pressed="cocFilter === tab.value"
          @click="cocFilter = tab.value">
          <span class="coc-tab-label">{{ tab.label }}</span>
          <span class="coc-tab-count">{{ tab.count }}</span>
        </button>
      </nav>

      <section class="filter-panel" aria-label="Record filters">
        <div class="filter-heading">
          <span>Filters</span>
          <button type="button" class="text-button" @click="clearFilters">
            Clear filters
          </button>
        </div>

        <div class="filter-grid">
          <label class="filter-field">
            <span>Assessment type</span>
            <select v-model="typeFilter">
              <option value="">All types</option>
              <option v-for="type in assessmentTypes" :key="type" :value="type">
                {{ type }}
              </option>
            </select>
          </label>

          <label class="filter-field">
            <span>Assessment</span>
            <select v-model="assessmentFilter">
              <option value="">All assessments</option>
              <option v-for="assessment in assessments" :key="assessment.id" :value="String(assessment.id)">
                {{ assessment.title }}
              </option>
            </select>
          </label>

          <label class="filter-field">
            <span>Result</span>
            <select v-model="resultFilter">
              <option value="">All results</option>
              <option value="passed">Passed</option>
              <option value="failed">Failed</option>
              <option value="other">Other</option>
            </select>
          </label>

          <label class="filter-field">
            <span>Completed from</span>
            <input v-model="dateFrom" type="date" />
          </label>

          <label class="filter-field">
            <span>Completed to</span>
            <input v-model="dateTo" type="date" />
          </label>
        </div>

        <p class="filter-summary">
          Showing {{ filteredRecords.length }} of {{ records.length }} records
        </p>
      </section>

      <div v-if="loading && !records.length" class="state-card">
        <span class="spinner" aria-hidden="true"></span>
        <p>Loading assessment records…</p>
      </div>

      <div v-else-if="errorMessage" class="state-card error-state" role="alert">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <p>{{ errorMessage }}</p>
        <button class="secondary-button" type="button" @click="fetchRecords">
          Try again
        </button>
      </div>

      <section v-else class="table-card">
        <div class="table-toolbar">
          <div class="table-toolbar-text">
            <h2>Assessment activity</h2>
            <p>{{ filteredRecords.length }} matching records</p>
          </div>

          <div class="toolbar-actions">
            <label class="page-size">
              <span>Rows</span>
              <select v-model.number="perPage" aria-label="Rows per page">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>
            </label>

            <div ref="exportMenuRef" class="export-all">
              <button type="button" class="export-all-trigger" aria-haspopup="menu" :aria-expanded="exportAllOpen"
                :disabled="exportingAll" @click="toggleExportAll">
                <i :class="exportingAll ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-file-export'"
                  aria-hidden="true"></i>
                <span>{{ exportingAll ? 'Exporting…' : 'Export all' }}</span>
                <i class="fa-solid fa-chevron-down chevron" aria-hidden="true"></i>
              </button>

              <div v-if="exportAllOpen" class="export-all-menu" role="menu" aria-label="Export all records">
                <p class="export-all-hint">All {{ records.length }} records, ignoring filters</p>

                <button type="button" role="menuitem" @click="exportAll('pdf')">
                  <i class="fa-regular fa-file-pdf" aria-hidden="true"></i>
                  PDF document
                </button>

                <button type="button" role="menuitem" @click="exportAll('csv')">
                  <i class="fa-solid fa-file-csv" aria-hidden="true"></i>
                  CSV spreadsheet
                </button>
              </div>
            </div>
          </div>
        </div>

        <p v-if="exportError" class="export-error" role="alert">{{ exportError }}</p>

        <!-- Desktop / tablet table -->
        <div class="table-scroll">
          <table class="records-table">
            <thead>
              <tr>
                <th>
                  <button type="button" @click="sortBy('user_name')">
                    Student <i :class="sortIcon('user_name')" aria-hidden="true"></i>
                  </button>
                </th>
                <th>
                  <button type="button" @click="sortBy('quiz_title')">
                    Assessment <i :class="sortIcon('quiz_title')" aria-hidden="true"></i>
                  </button>
                </th>
                <th class="center">
                  <button type="button" @click="sortBy('attempt_count')">
                    Attempts <i :class="sortIcon('attempt_count')" aria-hidden="true"></i>
                  </button>
                </th>
                <th class="center">
                  <button type="button" @click="sortBy('percentage')">
                    Score <i :class="sortIcon('percentage')" aria-hidden="true"></i>
                  </button>
                </th>
                <th class="center">Result</th>
                <th class="right">
                  <button type="button" @click="sortBy('completed_at')">
                    Completed <i :class="sortIcon('completed_at')" aria-hidden="true"></i>
                  </button>
                </th>
                <th class="center action-column">View</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="paginatedRecords.length === 0">
                <td colspan="7">
                  <div class="empty-state">
                    <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
                    <strong>No records found</strong>
                    <span>Try changing your search or filters.</span>
                  </div>
                </td>
              </tr>

              <tr v-for="record in paginatedRecords" :key="record.id">
                <td>
                  <div class="student-cell">
                    <img class="avatar" :src="profileImageUrl(record.user)" :alt="userName(record)"
                      @error="useFallbackImage" />
                    <div class="cell-copy">
                      <strong class="student-name">{{ userName(record) }}</strong>
                      <span class="muted">
                        {{ record.user_email || `ID #${record.user_id ?? '—'}` }}
                      </span>
                    </div>
                  </div>
                </td>

                <td>
                  <div class="assessment-cell">
                    <div class="cell-copy">
                      <span v-if="cocLabel(record)" class="coc-badge" :class="cocBadgeClass(record)">
                        {{ cocLabel(record) }}
                      </span>
                      <strong class="assessment-name">{{ assessmentTitle(record) }}</strong>
                      <span class="muted">{{ assessmentType(record) }}</span>
                    </div>
                  </div>
                </td>

                <td class="center">
                  <span class="attempt-value">{{ record.attempt_count ?? 1 }}</span>
                </td>

                <td class="center">
                  <div class="score-cell">
                    <strong :class="scoreClass(record)">{{ formatScore(record) }}</strong>
                    <span class="muted">{{ formatPercentage(record) }}</span>
                  </div>
                </td>

                <td class="center">
                  <span class="status-badge" :class="resultClass(record)">
                    <span class="status-dot" aria-hidden="true"></span>
                    {{ resultLabel(record) }}
                  </span>
                </td>

                <td class="right date-cell">
                  {{ formatDate(completedDate(record)) }}
                </td>

                <td class="center action-column">
                  <button class="view-button" type="button" title="View record details"
                    :aria-label="`View record for ${userName(record)}`" @click="viewRecord(record)">
                    <i class="fa-regular fa-eye" aria-hidden="true"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile cards -->
        <div class="record-cards">
          <p v-if="paginatedRecords.length === 0" class="cards-empty">
            No records found. Try changing your search or filters.
          </p>

          <article v-for="record in paginatedRecords" :key="`card-${record.id}`" class="record-card">
            <div class="record-card-top">
              <div class="student-cell">
                <img class="avatar" :src="profileImageUrl(record.user)" :alt="userName(record)"
                  @error="useFallbackImage" />
                <div class="cell-copy">
                  <strong class="student-name">{{ userName(record) }}</strong>
                  <span class="muted">{{ record.user_email || `ID #${record.user_id ?? '—'}` }}</span>
                </div>
              </div>

              <span class="status-badge" :class="resultClass(record)">
                <span class="status-dot" aria-hidden="true"></span>
                {{ resultLabel(record) }}
              </span>
            </div>

            <div class="record-card-assessment">
              <span v-if="cocLabel(record)" class="coc-badge" :class="cocBadgeClass(record)">
                {{ cocLabel(record) }}
              </span>
              <strong class="assessment-name">{{ assessmentTitle(record) }}</strong>
              <span class="muted">{{ assessmentType(record) }}</span>
            </div>

            <div class="record-card-stats">
              <div>
                <span>Score</span>
                <strong :class="scoreClass(record)">{{ formatScore(record) }}</strong>
              </div>
              <div>
                <span>Percent</span>
                <strong>{{ formatPercentage(record) }}</strong>
              </div>
              <div>
                <span>Attempt</span>
                <strong>{{ record.attempt_count ?? 1 }}</strong>
              </div>
              <div>
                <span>Completed</span>
                <strong>{{ formatDate(completedDate(record)) }}</strong>
              </div>
            </div>

            <button class="mobile-view-button" type="button" :aria-label="`View record for ${userName(record)}`"
              @click="viewRecord(record)">
              View details
              <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </button>
          </article>
        </div>

        <footer class="pagination">
          <span class="pagination-info">
            {{ filteredRecords.length ? pageStart + 1 : 0 }}–{{
              Math.min(pageStart + perPage, filteredRecords.length)
            }}
            of {{ filteredRecords.length }}
          </span>

          <div class="pagination-controls">
            <button type="button" aria-label="Previous page" :disabled="currentPage <= 1" @click="currentPage--">
              <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>

            <span class="page-indicator">{{ currentPage }} / {{ totalPages }}</span>

            <button type="button" aria-label="Next page" :disabled="currentPage >= totalPages" @click="currentPage++">
              <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
          </div>
        </footer>
      </section>
    </section>

    <Teleport to="body">
      <div v-if="selectedRecord" class="modal-backdrop" @click.self="closeDetails" @keydown.esc="closeDetails">
        <section class="details-modal print-record" role="dialog" aria-modal="true" aria-labelledby="details-title">
          <header class="modal-header">
            <div class="modal-header-text">
              <p class="eyebrow">ASSESSMENT RECORD</p>
              <h2 id="details-title">Attempt details</h2>
              <p>Saved result and answer review.</p>
            </div>

            <div class="modal-header-actions">
              <div class="export-menu">
                <button class="secondary-button export-trigger" type="button" aria-haspopup="true"
                  :aria-expanded="exportMenuOpen" @click="exportMenuOpen = !exportMenuOpen">
                  <i class="fa-solid fa-download" aria-hidden="true"></i>
                  Export
                  <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                </button>

                <div v-if="exportMenuOpen" class="export-dropdown">
                  <button type="button" @click="downloadRecordCsv">
                    <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
                    Download CSV
                  </button>
                  <button type="button" @click="downloadRecordPdf">
                    <i class="fa-regular fa-file-pdf" aria-hidden="true"></i>
                    Download PDF
                  </button>
                </div>
              </div>

              <button class="icon-button modal-close" type="button" aria-label="Close details" @click="closeDetails">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
              </button>
            </div>
          </header>

          <div class="modal-content">
            <div class="student-summary">
              <img class="modal-avatar" :src="profileImageUrl(selectedRecord.user)" :alt="userName(selectedRecord)"
                @error="useFallbackImage" />
              <div class="cell-copy">
                <strong>{{ userName(selectedRecord) }}</strong>
                <span class="muted">ID #{{ selectedRecord.user_id ?? '—' }}</span>
              </div>
            </div>

            <div class="details-grid">
              <div class="detail-item">
                <span>Assessment</span>
                <strong>{{ assessmentTitle(selectedRecord) }}</strong>
                <small>{{ assessmentType(selectedRecord) }}</small>
                <span v-if="cocLabel(selectedRecord)" class="coc-badge" :class="cocBadgeClass(selectedRecord)">
                  {{ cocLabel(selectedRecord) }}
                </span>
              </div>

              <div class="detail-item">
                <span>Result</span>
                <span class="status-badge" :class="resultClass(selectedRecord)">
                  <span class="status-dot" aria-hidden="true"></span>
                  {{ resultLabel(selectedRecord) }}
                </span>
              </div>

              <div class="detail-item">
                <span>Score</span>
                <strong>{{ formatScore(selectedRecord) }}</strong>
              </div>

              <div class="detail-item">
                <span>Percentage</span>
                <strong>{{ formatPercentage(selectedRecord) }}</strong>
              </div>

              <div class="detail-item">
                <span>Passing score</span>
                <strong>{{ formatNumber(selectedRecord.passing_score) }}%</strong>
              </div>

              <div class="detail-item">
                <span>Attempts recorded</span>
                <strong>{{ selectedRecord.attempt_count ?? 1 }}</strong>
              </div>

              <div class="detail-item">
                <span>Completed</span>
                <strong>{{ formatDateTime(completedDate(selectedRecord)) }}</strong>
              </div>

              <div class="detail-item">
                <span>Time taken</span>
                <strong>{{ formatElapsedTime(selectedRecord.elapsed_time) }}</strong>
              </div>
            </div>

            <section class="answers-section">
              <div class="answers-heading">
                <h3>Answer review</h3>
                <span>{{ detailAnswers(selectedRecord).length }} questions</span>
              </div>

              <div v-if="detailAnswers(selectedRecord).length" class="answer-list">
                <article v-for="(answer, index) in detailAnswers(selectedRecord)" :key="answer.question_id ?? index"
                  class="answer-item">
                  <span class="answer-number">{{ index + 1 }}</span>

                  <div class="answer-body">
                    <strong>{{ answer.question || `Question ${index + 1}` }}</strong>

                    <p>
                      <span>Student answer</span>
                      {{ displayAnswer(answer.user_answer) }}
                    </p>

                    <p>
                      <span>Correct answer</span>
                      {{ displayAnswer(answer.correct_answer) }}
                    </p>

                    <span class="answer-result" :class="answer.is_correct ? 'answer-correct' : 'answer-wrong'">
                      {{ answer.is_correct ? 'Correct' : 'Incorrect' }}
                    </span>
                  </div>
                </article>
              </div>

              <div v-else class="answers-empty">
                Answer details are not available for this record.
              </div>
            </section>
          </div>

          <footer class="modal-footer">
            <button class="secondary-button" type="button" @click="closeDetails">
              Close
            </button>
          </footer>
        </section>
      </div>
    </Teleport>
  </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/bootstrap'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import { buildAllRecordsPdf, buildRecordsCsv, downloadBlob, sortRows } from '@/utils/pdf-allrecords'

const route = useRoute()
const router = useRouter()

const FALLBACK_IMAGE = '/storage/images/profiles/default.png'
const EXPORT_PASSING_SCORE = 75

const records = ref([])
const loading = ref(false)
const errorMessage = ref('')
const selectedRecord = ref(null)

// Per-record export menu inside the detail modal
const exportMenuOpen = ref(false)

// Export-all menu in the table toolbar
const exportAllOpen = ref(false)
const exportingAll = ref(false)
const exportError = ref('')
const exportMenuRef = ref(null)

const currentPage = ref(1)
const perPage = ref(10)

const sortKey = ref('completed_at')
const sortOrder = ref('desc')

const cocFilter = ref('all')
const searchQuery = ref('')
const typeFilter = ref('')
const assessmentFilter = ref('')
const resultFilter = ref('')
const dateFrom = ref('')
const dateTo = ref('')

/* =========================================
   USER FILTER (from ?user_id=)
========================================= */

const filterUserId = computed(() => {
  const value = Number(route.query.user_id)
  return Number.isFinite(value) && value > 0 ? value : null
})

const filteredUserName = computed(() => {
  const match = records.value.find(
    (record) => Number(record.user_id) === filterUserId.value
  )

  return match ? userName(match) : `User #${filterUserId.value}`
})

const clearUserFilter = () => {
  router.replace({ path: route.path })
}

/* =========================================
   FETCH
========================================= */

const fetchRecords = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const { data } = await api.get('/api/admin/records')

    if (data?.status !== 'success' || !Array.isArray(data.data)) {
      throw new Error(data?.message || 'Invalid records response.')
    }

    records.value = data.data
  } catch (error) {
    console.error('Failed to fetch admin records:', error)
    errorMessage.value =
      error.response?.data?.message ||
      error.message ||
      'Unable to load assessment records.'
  } finally {
    loading.value = false
  }
}

/* =========================================
   HELPERS
========================================= */

const userName = (record) => {
  const name = [
    record?.user?.first_name,
    record?.user?.last_name
  ].filter(Boolean).join(' ').trim()

  return name || `User #${record?.user_id ?? '—'}`
}

const profileImageUrl = (user) => {
  const photo = user?.profile_photo

  if (!photo) return FALLBACK_IMAGE
  if (/^https?:\/\//i.test(photo)) return photo
  if (String(photo).startsWith('/')) return photo

  return `/storage/images/profiles/${String(photo).replace(/^\/+/, '')}`
}

const useFallbackImage = (event) => {
  event.target.onerror = null
  event.target.src = FALLBACK_IMAGE
}

const assessmentTitle = (record) =>
  record?.quiz_title ?? 'Untitled assessment'

const assessmentType = (record) =>
  record?.quiz_type ?? 'Type not specified'

const cocNumber = (record) => {
  const value =
    record?.coc_number ??
    record?.cocNumber ??
    record?.coc?.number ??
    record?.coc?.coc_number ??
    record?.coc_id ??
    record?.coc

  if (value === null || value === undefined || value === '') return null

  const match = String(value).match(/\d+/)
  return match ? Number(match[0]) : null
}

const cocLabel = (record) => {
  const number = cocNumber(record)
  return number ? `COC ${number}` : ''
}

const cocBadgeClass = (record) =>
  cocNumber(record) === 1 ? 'coc-badge-one' : 'coc-badge-two'

const completedDate = (record) =>
  record?.completed_at ?? record?.created_at ?? null

const timestamp = (value) => {
  if (!value) return 0
  const parsed = new Date(value).getTime()
  return Number.isFinite(parsed) ? parsed : 0
}

const formatDate = (value) => {
  if (!value) return '—'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '—'

  return new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: '2-digit',
    year: '2-digit'
  }).format(date)
}

const formatDateTime = (value) => {
  if (!value) return '—'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '—'

  return new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

const formatNumber = (value) => {
  if (value === null || value === undefined || value === '') return '—'

  const number = Number(value)
  return Number.isFinite(number) ? number.toFixed(0) : '—'
}

// Server's percentage first; fall back to computing from counts.
const scorePercent = (record) => {
  const saved = record?.percentage ?? record?.accuracy

  if (saved !== null && saved !== undefined && saved !== '') {
    const number = Number(saved)
    return Number.isFinite(number) ? number : null
  }

  const correct = Number(record?.correct_answers ?? record?.score)
  const total = Number(record?.total_questions)

  if (!Number.isFinite(correct) || !Number.isFinite(total) || total <= 0) {
    return null
  }

  return (correct / total) * 100
}

const formatPercentage = (record) => {
  const percentage = scorePercent(record)
  return percentage === null ? '—' : `${percentage.toFixed(2)}%`
}

const formatScore = (record) => {
  const score = record?.correct_answers ?? record?.score
  const total = record?.total_questions

  if (score === null || score === undefined || score === '') return '—'
  if (total === null || total === undefined || total === '') return String(score)

  return `${score} / ${total}`
}

// The server sends `passed` on every record. Text and percentage are fallbacks only.
const resultLabel = (record) => {
  if (typeof record?.passed === 'boolean') return record.passed ? 'Passed' : 'Failed'

  const status = String(record?.status ?? '').toLowerCase()
  if (status === 'passed' || status === 'pass') return 'Passed'
  if (status === 'failed' || status === 'fail') return 'Failed'

  const percentage = scorePercent(record)
  const passing = Number(record?.passing_score ?? 75)

  if (percentage === null) return 'Not recorded'
  return percentage >= passing ? 'Passed' : 'Failed'
}

const resultClass = (record) => {
  const label = resultLabel(record)

  if (label === 'Passed') return 'status-passed'
  if (label === 'Failed') return 'status-failed'
  return 'status-unknown'
}

const scoreClass = (record) => {
  const label = resultLabel(record)

  if (label === 'Passed') return 'score-passed'
  if (label === 'Failed') return 'score-failed'
  return 'score-unknown'
}

const formatElapsedTime = (value) => {
  if (value === null || value === undefined || value === '') return '—'

  const seconds = Number(value)
  if (!Number.isFinite(seconds) || seconds < 0) return '—'

  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60

  if (minutes === 0) return `${remainingSeconds}s`
  if (remainingSeconds === 0) return `${minutes}m`

  return `${minutes}m ${remainingSeconds}s`
}

const detailAnswers = (record) =>
  Array.isArray(record?.questions) ? record.questions : []

const displayAnswer = (value) => {
  if (value === null || value === undefined || value === '') {
    return 'No answer recorded'
  }

  if (Array.isArray(value)) {
    return value.map(displayAnswer).join(', ')
  }

  if (typeof value === 'object') {
    return (
      value.option_text ??
      value.text ??
      value.label ??
      value.answer_text ??
      value.answer ??
      'Answer recorded'
    )
  }

  return String(value)
}

/* =========================================
   SCOPED + NORMALIZED RECORDS
========================================= */

// Only the selected student's records when ?user_id is set.
const scopedRecords = computed(() => {
  if (!filterUserId.value) return records.value

  return records.value.filter(
    (record) => Number(record.user_id) === filterUserId.value
  )
})

const normalizedRecords = computed(() =>
  scopedRecords.value.map((record, index) => ({
    ...record,
    _index: index,
    _userName: userName(record),
    _title: assessmentTitle(record),
    _type: assessmentType(record),
    _timestamp: timestamp(completedDate(record))
  }))
)

const cocTabs = computed(() => {
  const countFor = (value) =>
    normalizedRecords.value.filter((record) =>
      value === 'all' || String(cocNumber(record) ?? '') === value
    ).length

  return [
    { label: 'All COCs', value: 'all', count: countFor('all') },
    { label: 'COC 1', value: '1', count: countFor('1') },
    { label: 'COC 2', value: '2', count: countFor('2') }
  ]
})

const assessmentTypes = computed(() =>
  [...new Set(
    normalizedRecords.value
      .map((record) => record._type)
      .filter((type) => type && type !== 'Type not specified')
  )].sort((a, b) => a.localeCompare(b))
)

const assessments = computed(() => {
  const unique = new Map()

  for (const record of normalizedRecords.value) {
    const id = record.quiz_id ?? record.assessment_id ?? record._title

    if (!unique.has(String(id))) {
      unique.set(String(id), { id, title: record._title })
    }
  }

  return [...unique.values()].sort((a, b) =>
    a.title.localeCompare(b.title)
  )
})

const filteredRecords = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  const filtered = normalizedRecords.value.filter((record) => {
    const matchesCoc =
      cocFilter.value === 'all' ||
      String(cocNumber(record) ?? '') === cocFilter.value

    const matchesSearch =
      !query ||
      record._userName.toLowerCase().includes(query) ||
      record._title.toLowerCase().includes(query) ||
      String(record.user_id ?? '').includes(query) ||
      String(record.user_email ?? '').toLowerCase().includes(query)

    const matchesType =
      !typeFilter.value || record._type === typeFilter.value

    const assessmentId = String(
      record.quiz_id ?? record.assessment_id ?? record._title
    )

    const matchesAssessment =
      !assessmentFilter.value || assessmentId === assessmentFilter.value

    const label = resultLabel(record).toLowerCase()
    const matchesResult =
      !resultFilter.value ||
      (resultFilter.value === 'passed' && label === 'passed') ||
      (resultFilter.value === 'failed' && label === 'failed') ||
      (resultFilter.value === 'other' && !['passed', 'failed'].includes(label))

    const date = completedDate(record)
    const parsedDate = date ? new Date(date) : null
    const recordDate =
      parsedDate && !Number.isNaN(parsedDate.getTime())
        ? parsedDate.toISOString().slice(0, 10)
        : ''

    const matchesFrom = !dateFrom.value || recordDate >= dateFrom.value
    const matchesTo = !dateTo.value || recordDate <= dateTo.value

    return (
      matchesCoc &&
      matchesSearch &&
      matchesType &&
      matchesAssessment &&
      matchesResult &&
      matchesFrom &&
      matchesTo
    )
  })

  const valueFor = (record) => {
    if (sortKey.value === 'user_name') return record._userName
    if (sortKey.value === 'quiz_title') return record._title
    if (sortKey.value === 'percentage') return scorePercent(record) ?? -1
    if (sortKey.value === 'attempt_count') return Number(record.attempt_count ?? 1)
    if (sortKey.value === 'completed_at') return record._timestamp
    return record[sortKey.value]
  }

  return filtered.sort((a, b) => {
    const left = valueFor(a)
    const right = valueFor(b)

    const comparison =
      typeof left === 'string' || typeof right === 'string'
        ? String(left ?? '').localeCompare(String(right ?? ''))
        : Number(left ?? 0) - Number(right ?? 0)

    return sortOrder.value === 'asc' ? comparison : -comparison
  })
})

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredRecords.value.length / perPage.value))
)

const pageStart = computed(() =>
  (currentPage.value - 1) * perPage.value
)

const paginatedRecords = computed(() =>
  filteredRecords.value.slice(pageStart.value, pageStart.value + perPage.value)
)

/* =========================================
   SORT / FILTER ACTIONS
========================================= */

const sortBy = (key) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = key === 'completed_at' ? 'desc' : 'asc'
  }

  currentPage.value = 1
}

const sortIcon = (key) => {
  if (sortKey.value !== key) return 'fa-solid fa-sort'
  return sortOrder.value === 'asc'
    ? 'fa-solid fa-sort-up'
    : 'fa-solid fa-sort-down'
}

const clearFilters = () => {
  cocFilter.value = 'all'
  searchQuery.value = ''
  typeFilter.value = ''
  assessmentFilter.value = ''
  resultFilter.value = ''
  dateFrom.value = ''
  dateTo.value = ''
  currentPage.value = 1
}

const viewRecord = (record) => {
  selectedRecord.value = record
}

const closeDetails = () => {
  selectedRecord.value = null
  exportMenuOpen.value = false
}

/* =========================================
   EXPORT ALL RECORDS (PDF or CSV)
   Exports every record from the API. Filters, search, and the
   ?user_id scope are ignored on purpose.
========================================= */

// Maps one API record to the row shape used by recordsExport.js.
const toExportRow = (record) => {
  const label = resultLabel(record)

  return {
    userId: record.user_id ?? null,
    firstName: record?.user?.first_name ?? '',
    lastName: record?.user?.last_name ?? '',
    name: userName(record),
    email: record.user_email ?? record?.user?.email ?? '',
    assessment: assessmentTitle(record),
    coc: cocLabel(record) || '—',
    score: formatScore(record),
    percentage: formatPercentage(record),
    result: label === 'Passed' ? 'Passed' : label === 'Failed' ? 'Not passed' : '—',
    completedAt: completedDate(record),
    time: formatElapsedTime(record.elapsed_time)
  }
}

const fetchAllRecords = async () => {
  const { data } = await api.get('/api/admin/records')

  if (data?.status !== 'success' || !Array.isArray(data.data)) {
    throw new Error(data?.message || 'Invalid records response.')
  }

  return data.data
}

const toggleExportAll = () => {
  if (exportingAll.value) return
  exportAllOpen.value = !exportAllOpen.value
}

const exportAll = async (format) => {
  if (exportingAll.value) return

  exportAllOpen.value = false
  exportError.value = ''
  exportingAll.value = true

  try {
    const allRecords = await fetchAllRecords()

    if (!allRecords.length) {
      exportError.value = 'There are no assessment records to export yet.'
      return
    }

    // Newest attempts first, the same default as the student export.
    const rows = sortRows(allRecords.map(toExportRow), 'newest')
    const stamp = new Date().toISOString().slice(0, 10)

    if (format === 'pdf') {
      const doc = await buildAllRecordsPdf(rows, {
        orderKey: 'newest',
        passingScore: EXPORT_PASSING_SCORE
      })
      doc.save(`all-assessment-records-${stamp}.pdf`)
    } else {
      downloadBlob(
        buildRecordsCsv(rows),
        `all-assessment-records-${stamp}.csv`,
        'text/csv;charset=utf-8'
      )
    }
  } catch (error) {
    console.error('Export all records failed:', error)
    exportError.value = 'Could not export the records. Please try again.'
  } finally {
    exportingAll.value = false
  }
}

// Closes the export-all menu on an outside click or Escape.
const handleDocumentMouseDown = (event) => {
  if (exportAllOpen.value && exportMenuRef.value && !exportMenuRef.value.contains(event.target)) {
    exportAllOpen.value = false
  }
}

const handleDocumentKeydown = (event) => {
  if (event.key === 'Escape') exportAllOpen.value = false
}

/* =========================================
   PER-RECORD EXPORT (detail modal)
========================================= */

const csvEscape = (value) => {
  const text = String(value ?? '')
  return `"${text.replace(/"/g, '""')}"`
}

const downloadRecordCsv = () => {
  const record = selectedRecord.value
  if (!record) return

  const rows = [
    ['Assessment Record'],
    ['Student', userName(record)],
    ['User ID', record.user_id ?? ''],
    ['Assessment', assessmentTitle(record)],
    ['Assessment type', assessmentType(record)],
    ['COC', cocLabel(record) || '—'],
    ['Result', resultLabel(record)],
    ['Score', formatScore(record)],
    ['Percentage', formatPercentage(record)],
    ['Passing score', record.passing_score ?? ''],
    ['Attempts recorded', record.attempt_count ?? 1],
    ['Completed', formatDateTime(completedDate(record))],
    ['Time taken', formatElapsedTime(record.elapsed_time)],
    [],
    ['Question', 'Student answer', 'Correct answer', 'Result'],
    ...detailAnswers(record).map((answer, index) => [
      answer.question || `Question ${index + 1}`,
      displayAnswer(answer.user_answer),
      displayAnswer(answer.correct_answer),
      answer.is_correct ? 'Correct' : 'Incorrect'
    ])
  ]

  const csv = rows
    .map((row) => row.map(csvEscape).join(','))
    .join('\r\n')

  // The BOM lets Excel read accents correctly.
  downloadBlob(`\uFEFF${csv}`, `assessment-record-${record.id ?? 'export'}.csv`, 'text/csv;charset=utf-8;')

  exportMenuOpen.value = false
}

const downloadRecordPdf = () => {
  const record = selectedRecord.value
  if (!record) return

  const doc = new jsPDF()
  const margin = 14
  const pageWidth = doc.internal.pageSize.getWidth()
  const pageHeight = doc.internal.pageSize.getHeight()

  // Logo must be inside the public folder.
  // Example: public/lightning.png
  const logo = new Image()
  logo.src = '/lightning.png'

  let generated = false

  const drawPdf = () => {
    // Prevent duplicate generation when onload and the complete check both fire.
    if (generated) return
    generated = true

    try {
      if (logo.complete && logo.naturalWidth > 0) {
        doc.addImage(logo, 'PNG', margin, 12, 18, 18)
      }
    } catch (error) {
      console.warn('Could not add PDF logo:', error)
    }

    doc.setTextColor(0, 0, 0)
    doc.setFont('helvetica', 'bold')
    doc.setFontSize(16)
    doc.text('CSS PREP', margin + 23, 19)

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(9)
    doc.text('Competency Assessment and Performance Evaluation System', margin + 23, 25)

    doc.setFontSize(8)
    doc.setTextColor(100, 100, 100)
    doc.text('NC II Readiness Assessment Record', margin + 23, 30)
    doc.setTextColor(0, 0, 0)

    doc.setDrawColor(190, 190, 190)
    doc.line(margin, 36, pageWidth - margin, 36)

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(13)
    doc.text('Assessment Record', margin, 45)

    const summaryRows = [
      ['Student', userName(record)],
      ['User ID', record.user_id ?? '—'],
      ['Assessment', assessmentTitle(record)],
      ['COC', cocLabel(record) || '—'],
      ['Result', resultLabel(record)],
      ['Score', formatScore(record)],
      ['Percentage', formatPercentage(record)],
      ['Passing Score', record.passing_score ?? '—'],
      ['Attempts Recorded', record.attempt_count ?? 1],
      ['Completed', formatDateTime(completedDate(record))],
      ['Time Taken', formatElapsedTime(record.elapsed_time)]
    ]

    autoTable(doc, {
      startY: 50,
      head: [['DETAIL', 'INFORMATION']],
      body: summaryRows,
      theme: 'grid',
      margin: { left: margin, right: margin },
      styles: {
        font: 'helvetica',
        fontSize: 9,
        cellPadding: 3,
        lineColor: [220, 220, 220],
        lineWidth: 0.2,
        textColor: [35, 35, 35],
        overflow: 'linebreak'
      },
      headStyles: {
        fillColor: [30, 30, 30],
        textColor: [255, 255, 255],
        fontStyle: 'bold'
      },
      columnStyles: {
        0: { cellWidth: 48, fontStyle: 'bold' },
        1: { cellWidth: 'auto' }
      },
      didParseCell(data) {
        if (data.section === 'body' && data.column.index === 0) {
          data.cell.styles.fillColor = [245, 245, 245]
        }
      }
    })

    const answers = detailAnswers(record)

    if (answers.length) {
      let answerY = doc.lastAutoTable.finalY + 12

      if (answerY > pageHeight - 35) {
        doc.addPage()
        answerY = 20
      }

      doc.setFont('helvetica', 'bold')
      doc.setFontSize(13)
      doc.text('Answer Review', margin, answerY)

      const answerRows = answers.map((answer, index) => [
        index + 1,
        answer.question || `Question ${index + 1}`,
        displayAnswer(answer.user_answer),
        displayAnswer(answer.correct_answer),
        answer.is_correct ? 'Correct' : 'Incorrect'
      ])

      autoTable(doc, {
        startY: answerY + 5,
        head: [['#', 'Question', 'Your Answer', 'Correct Answer', 'Result']],
        body: answerRows,
        theme: 'grid',
        margin: { left: margin, right: margin, bottom: 18 },
        styles: {
          font: 'helvetica',
          fontSize: 8,
          cellPadding: 2.5,
          lineColor: [220, 220, 220],
          lineWidth: 0.2,
          overflow: 'linebreak',
          valign: 'middle'
        },
        headStyles: {
          fillColor: [30, 30, 30],
          textColor: [255, 255, 255],
          fontStyle: 'bold'
        },
        columnStyles: {
          0: { cellWidth: 8, halign: 'center' },
          1: { cellWidth: 48 },
          2: { cellWidth: 38 },
          3: { cellWidth: 38 },
          4: { cellWidth: 'auto', halign: 'center' }
        },
        didParseCell(data) {
          if (data.section === 'body' && data.column.index === 4) {
            const isCorrect = data.cell.raw === 'Correct'

            data.cell.styles.textColor = isCorrect ? [35, 110, 65] : [170, 45, 45]
            data.cell.styles.fontStyle = 'bold'
          }
        }
      })
    }

    const pageCount = doc.internal.getNumberOfPages()

    for (let page = 1; page <= pageCount; page++) {
      doc.setPage(page)

      const footerY = pageHeight - 8

      doc.setDrawColor(220, 220, 220)
      doc.line(margin, footerY - 5, pageWidth - margin, footerY - 5)

      doc.setFont('helvetica', 'normal')
      doc.setFontSize(8)
      doc.setTextColor(110, 110, 110)

      doc.text('CSS PREP · NC II Readiness', margin, footerY)
      doc.text(`Page ${page} of ${pageCount}`, pageWidth - margin, footerY, { align: 'right' })
    }

    doc.save(`assessment-record-${record.id ?? 'export'}.pdf`)
    exportMenuOpen.value = false
  }

  // Generate after the logo loads, or continue without it.
  logo.onload = drawPdf
  logo.onerror = drawPdf

  if (logo.complete) {
    drawPdf()
  }
}

/* =========================================
   WATCHERS AND LIFECYCLE
========================================= */

watch(
  [
    filterUserId,
    cocFilter,
    searchQuery,
    typeFilter,
    assessmentFilter,
    resultFilter,
    dateFrom,
    dateTo,
    perPage
  ],
  () => {
    currentPage.value = 1
  }
)

watch(totalPages, (pages) => {
  if (currentPage.value > pages) currentPage.value = pages
})

onMounted(() => {
  fetchRecords()
  document.addEventListener('mousedown', handleDocumentMouseDown)
  document.addEventListener('keydown', handleDocumentKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleDocumentMouseDown)
  document.removeEventListener('keydown', handleDocumentKeydown)
})
</script>

<style scoped>
*,
*::before,
*::after {
  box-sizing: border-box;
}

.page-wrapper {
  min-height: 100vh;
  padding: 24px;
  background: #f8f9fb;
  color: #17191c;
  overflow-x: hidden;
  overflow-wrap: anywhere;
}

.admin-section {
  width: min(100%, 1440px);
  margin: 0 auto;
  min-width: 0;
}

/* HEADER */

.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px 20px;
  margin-bottom: 20px;
}

.header-text {
  min-width: 0;
  flex: 1 1 320px;
}

.eyebrow {
  margin: 0 0 6px;
  color: #858a93;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.12em;
}

.section-title {
  margin: 0;
  font-size: 23px;
  font-weight: 700;
  letter-spacing: -0.04em;
  line-height: 1.2;
}

.section-subtitle {
  margin: 6px 0 0;
  color: #858a93;
  font-size: 13px;
  line-height: 1.5;
}

.header-tools {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 9px;
  min-width: 0;
}

.record-count,
.search-wrap,
.icon-button {
  height: 40px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
}

.record-count {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 0 12px;
  white-space: nowrap;
}

.record-count strong {
  font-size: 13px;
}

.record-count span {
  color: #858a93;
  font-size: 12px;
}

.search-wrap {
  width: 270px;
  max-width: 100%;
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 0 12px;
  color: #858a93;
}

.search-wrap:focus-within {
  border-color: #aeb4bd;
}

.search-wrap input {
  width: 100%;
  min-width: 0;
  border: 0;
  outline: 0;
  color: #17191c;
  background: transparent;
  font: inherit;
  font-size: 12px;
}

.icon-button {
  width: 40px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  color: #555b64;
  cursor: pointer;
}

.icon-button:hover {
  background: #f4f5f7;
}

.icon-button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.spinning {
  animation: spin 0.8s linear infinite;
}

/* USER FILTER BANNER */

.user-filter-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px 14px;
  margin-bottom: 14px;
  padding: 10px 12px 10px 14px;
  border: 1px solid #dce6f5;
  border-radius: 10px;
  background: #f1f6ff;
}

.user-filter-info {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  color: #496da8;
  font-size: 12px;
}

.user-filter-info i {
  flex-shrink: 0;
}

.user-filter-info strong {
  color: #2f4f85;
  overflow-wrap: anywhere;
}

.user-filter-info small {
  margin-left: 6px;
  color: #7f96c2;
  font-size: 10px;
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}

.user-filter-banner .secondary-button {
  background: #fff;
  border-color: #c9d6ee;
  color: #2f4f85;
}

.user-filter-banner .secondary-button:hover {
  background: #f8fbff;
}

/* COC TABS */

.coc-tabs {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 14px;
  padding: 5px;
  overflow-x: auto;
  scrollbar-width: none;
  border: 1px solid #e7e9ed;
  border-radius: 10px;
  background: #fff;
}

.coc-tabs::-webkit-scrollbar {
  display: none;
}

.coc-tab {
  min-height: 36px;
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 12px;
  border: 1px solid transparent;
  border-radius: 7px;
  background: transparent;
  color: #737984;
  font: inherit;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.coc-tab:hover {
  background: #f6f7f8;
  color: #30343a;
}

.coc-tab.is-active {
  border-color: #e5e7eb;
  background: #f7f8fa;
  color: #202329;
  box-shadow: 0 1px 2px rgb(16 24 40 / 5%);
}

.coc-tab-count {
  min-width: 22px;
  height: 20px;
  display: inline-grid;
  place-items: center;
  padding: 0 6px;
  border-radius: 999px;
  background: #eceef1;
  color: #626873;
  font-size: 10px;
  font-weight: 700;
}

.coc-tab.is-active .coc-tab-count {
  background: #e3e6eb;
  color: #343941;
}

/* FILTER PANEL */

.filter-panel,
.table-card,
.state-card {
  border: 1px solid #e7e9ed;
  border-radius: 10px;
  background: #fff;
  min-width: 0;
}

.filter-panel {
  margin-bottom: 16px;
  padding: 15px;
}

.filter-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
  font-size: 12px;
  font-weight: 700;
}

.text-button {
  padding: 4px 6px;
  border: 0;
  background: transparent;
  color: #737984;
  font: inherit;
  font-size: 11px;
  cursor: pointer;
}

.text-button:hover {
  color: #17191c;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 10px;
}

.filter-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}

.filter-field>span {
  color: #777d87;
  font-size: 10px;
  font-weight: 600;
}

.filter-field input,
.filter-field select,
.page-size select {
  width: 100%;
  height: 36px;
  min-width: 0;
  padding: 0 9px;
  border: 1px solid #e1e4e8;
  border-radius: 7px;
  outline: none;
  background: #fff;
  color: #333840;
  font: inherit;
  font-size: 11px;
}

.filter-field input:focus,
.filter-field select:focus,
.page-size select:focus {
  border-color: #aeb4bd;
}

.filter-summary {
  margin: 10px 0 0;
  color: #858a93;
  font-size: 10px;
}

/* TABLE CARD */

.table-card {
  overflow: visible;
}

.table-card>.table-toolbar:first-child {
  border-radius: 10px 10px 0 0;
}

.table-card>.pagination:last-child {
  border-radius: 0 0 10px 10px;
}

.table-toolbar {
  min-height: 57px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px 12px;
  padding: 10px 15px;
  border-bottom: 1px solid #eceef1;
}

.table-toolbar-text {
  min-width: 0;
}

.table-toolbar h2 {
  margin: 0;
  font-size: 12px;
  font-weight: 700;
}

.table-toolbar p {
  margin: 4px 0 0;
  color: #858a93;
  font-size: 10px;
}

.toolbar-actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.page-size {
  display: flex;
  align-items: center;
  gap: 7px;
  color: #858a93;
  font-size: 10px;
}

.page-size select {
  width: 65px;
  height: 30px;
}

/* EXPORT ALL */

.export-all {
  position: relative;
}

.export-all-trigger {
  height: 30px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 0 10px;
  border: 1px solid #e2e4e8;
  border-radius: 7px;
  background: #fff;
  color: #333840;
  font: inherit;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease;
}

.export-all-trigger:hover:not(:disabled) {
  border-color: #cfd3d9;
  background: #f5f6f7;
}

.export-all-trigger:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.export-all-trigger .chevron {
  color: #858a93;
  font-size: 9px;
}

.export-all-menu {
  position: absolute;
  top: calc(100% + 6px);
  right: 0;
  z-index: 5;
  width: 210px;
  padding: 5px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 8px 24px rgb(0 0 0 / 12%);
}

.export-all-hint {
  margin: 0 0 4px;
  padding: 6px 9px 8px;
  border-bottom: 1px solid #eceef1;
  color: #858a93;
  font-size: 10px;
}

.export-all-menu button {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 9px;
  border: 0;
  border-radius: 5px;
  background: transparent;
  color: #333840;
  font: inherit;
  font-size: 11px;
  text-align: left;
  cursor: pointer;
}

.export-all-menu button:hover {
  background: #f5f6f7;
}

.export-all-menu i {
  width: 14px;
  color: #555b64;
  text-align: center;
}

.export-error {
  margin: 0;
  padding: 9px 15px;
  border-bottom: 1px solid #f3dede;
  background: #fff6f6;
  color: #a44747;
  font-size: 11px;
}

/* DESKTOP TABLE */

.table-scroll {
  width: 100%;
  overflow-x: auto;
}

.records-table {
  width: 100%;
  min-width: 860px;
  table-layout: fixed;
  border-collapse: collapse;
}

.records-table th {
  height: 42px;
  padding: 0 12px;
  border-bottom: 1px solid #eceef1;
  background: #fbfbfc;
  color: #777d87;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.045em;
  text-align: left;
  text-transform: uppercase;
  white-space: nowrap;
}

.records-table th button {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 0;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  letter-spacing: inherit;
  text-transform: inherit;
  cursor: pointer;
}

.records-table th button:hover {
  color: #17191c;
}

.records-table th:nth-child(1) {
  width: 22%;
}

.records-table th:nth-child(2) {
  width: 26%;
}

.records-table th:nth-child(3) {
  width: 9%;
}

.records-table th:nth-child(4) {
  width: 12%;
}

.records-table th:nth-child(5) {
  width: 11%;
}

.records-table th:nth-child(6) {
  width: 13%;
}

.records-table th:nth-child(7) {
  width: 7%;
}

.records-table td {
  height: 66px;
  padding: 9px 12px;
  border-bottom: 1px solid #f0f1f3;
  color: #30343a;
  font-size: 12px;
  vertical-align: middle;
}

.records-table tbody tr:last-child td {
  border-bottom: 0;
}

.records-table tbody tr:hover {
  background: #fcfcfd;
}

.center {
  text-align: center !important;
}

.right {
  text-align: right !important;
}

.student-cell,
.assessment-cell {
  display: flex;
  align-items: center;
  gap: 9px;
  min-width: 0;
}

.avatar {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  border: 1px solid #e5e7eb;
  border-radius: 50%;
  background: #f4f5f6;
  object-fit: cover;
}

.cell-copy {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
  min-width: 0;
}

.student-name,
.assessment-name {
  max-width: 100%;
  overflow: hidden;
  color: #262a30;
  font-size: 11px;
  font-weight: 650;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.muted {
  max-width: 100%;
  overflow: hidden;
  color: #8a9099;
  font-size: 10px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.coc-badge {
  width: fit-content;
  max-width: 100%;
  display: inline-flex;
  align-items: center;
  padding: 3px 7px;
  border: 1px solid transparent;
  border-radius: 999px;
  font-size: 9px;
  font-weight: 700;
  line-height: 1;
  white-space: nowrap;
}

.coc-badge-one {
  border-color: #dce8ff;
  background: #f0f5ff;
  color: #3d63a8;
}

.coc-badge-two {
  border-color: #d9eee5;
  background: #eef8f3;
  color: #34775a;
}

.attempt-value {
  color: #343941;
  font-size: 11px;
  font-weight: 600;
}

.score-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.score-cell strong {
  font-size: 11px;
  font-weight: 700;
}

.score-passed {
  color: #26734d;
}

.score-failed {
  color: #ad4a4a;
}

.score-unknown {
  color: #858a93;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  white-space: nowrap;
  font-size: 10px;
  font-weight: 600;
}

.status-dot {
  width: 6px;
  height: 6px;
  flex: 0 0 6px;
  border-radius: 50%;
  background: currentColor;
}

.status-passed {
  color: #26734d;
}

.status-failed {
  color: #ad4a4a;
}

.status-unknown {
  color: #858a93;
}

.date-cell {
  color: #636973 !important;
  font-size: 10px !important;
  white-space: nowrap;
}

.view-button {
  width: 30px;
  height: 30px;
  display: inline-grid;
  place-items: center;
  border: 1px solid #e2e4e8;
  border-radius: 7px;
  background: #fff;
  color: #555b64;
  cursor: pointer;
}

.view-button:hover {
  border-color: #c7cbd1;
  background: #f5f6f7;
  color: #17191c;
}

/* MOBILE RECORD CARDS (hidden on desktop) */

.record-cards {
  display: none;
}

.record-card {
  padding: 13px 14px;
  border-bottom: 1px solid #f0f1f3;
  min-width: 0;
}

.record-card:last-child {
  border-bottom: 0;
}

.record-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  min-width: 0;
}

.record-card-top .student-cell {
  flex: 1;
  min-width: 0;
}

.record-card-assessment {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
  margin: 11px 0;
  min-width: 0;
}

.record-card-stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  border: 1px solid #eceef1;
  border-radius: 8px;
  background: #fbfbfc;
  overflow: hidden;
}

.record-card-stats>div {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 9px 10px;
  border-right: 1px solid #eceef1;
  border-bottom: 1px solid #eceef1;
}

.record-card-stats>div:nth-child(2n) {
  border-right: 0;
}

.record-card-stats>div:nth-last-child(-n + 2) {
  border-bottom: 0;
}

.record-card-stats span {
  color: #858a93;
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.record-card-stats strong {
  overflow: hidden;
  color: #262a30;
  font-size: 11px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mobile-view-button {
  width: 100%;
  min-height: 36px;
  margin-top: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1px solid #e2e4e8;
  border-radius: 8px;
  background: #fff;
  color: #333840;
  font: inherit;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
}

.mobile-view-button:hover {
  background: #f5f6f7;
}

.cards-empty {
  padding: 24px;
  color: #858a93;
  font-size: 12px;
  text-align: center;
}

/* EMPTY / STATE */

.empty-state {
  min-height: 190px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 16px;
  color: #858a93;
  text-align: center;
}

.empty-state i {
  margin-bottom: 4px;
  font-size: 20px;
}

.empty-state strong {
  color: #464b53;
  font-size: 12px;
}

.empty-state span {
  font-size: 11px;
}

.state-card {
  min-height: 230px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 20px;
  color: #858a93;
  font-size: 12px;
  text-align: center;
}

.state-card p {
  margin: 0;
  overflow-wrap: anywhere;
}

.error-state>i {
  color: #ad4a4a;
  font-size: 20px;
}

.spinner {
  width: 26px;
  height: 26px;
  border: 2px solid #e7e9ed;
  border-top-color: #535962;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.secondary-button {
  min-height: 34px;
  padding: 0 12px;
  border: 1px solid #e0e3e7;
  border-radius: 7px;
  background: #fff;
  color: #333840;
  font: inherit;
  font-size: 11px;
  white-space: nowrap;
  cursor: pointer;
}

.secondary-button:hover {
  background: #f5f6f7;
}

/* PAGINATION */

.pagination {
  min-height: 52px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px 12px;
  padding: 9px 15px;
  border-top: 1px solid #eceef1;
}

.pagination-info,
.pagination-controls {
  color: #858a93;
  font-size: 10px;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 10px;
}

.page-indicator {
  min-width: 42px;
  text-align: center;
}

.pagination-controls button {
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  border: 1px solid #e2e4e8;
  border-radius: 7px;
  background: #fff;
  color: #555b64;
  cursor: pointer;
}

.pagination-controls button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.pagination-controls button:hover:not(:disabled) {
  background: #f5f6f7;
}

/* DETAILS MODAL */

.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 18px;
  background: rgb(18 20 24 / 48%);
}

.details-modal {
  width: min(720px, 100%);
  max-height: calc(100dvh - 36px);
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow: hidden;
  border: 1px solid #e7e9ed;
  border-radius: 11px;
  background: #fff;
  box-shadow: 0 20px 70px rgb(0 0 0 / 16%);
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  padding: 18px 20px;
  border-bottom: 1px solid #eceef1;
}

.modal-header-text {
  min-width: 0;
}

.modal-header h2 {
  margin: 0;
  font-size: 18px;
  letter-spacing: -0.03em;
  overflow-wrap: anywhere;
}

.modal-header-text>p:last-child {
  margin: 5px 0 0;
  color: #858a93;
  font-size: 11px;
}

.modal-header-actions {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.modal-close {
  flex: 0 0 36px;
}

.export-menu {
  position: relative;
}

.export-trigger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.export-trigger .fa-chevron-down {
  font-size: 9px;
}

.export-dropdown {
  position: absolute;
  top: calc(100% + 6px);
  right: 0;
  z-index: 2;
  width: 170px;
  padding: 5px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 8px 24px rgb(0 0 0 / 12%);
}

.export-dropdown button {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 9px;
  border: 0;
  border-radius: 5px;
  background: transparent;
  color: #333840;
  font: inherit;
  font-size: 11px;
  text-align: left;
  cursor: pointer;
}

.export-dropdown button:hover {
  background: #f5f6f7;
}

.modal-content {
  min-height: 0;
  padding: 18px 20px;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.student-summary {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  padding-bottom: 15px;
  border-bottom: 1px solid #eceef1;
}

.modal-avatar {
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  border: 1px solid #e5e7eb;
  border-radius: 50%;
  object-fit: cover;
}

.student-summary strong {
  color: #262a30;
  font-size: 12px;
  overflow-wrap: anywhere;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 9px;
  padding: 15px 0 18px;
}

.detail-item {
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  padding: 11px;
  border: 1px solid #eceef1;
  border-radius: 7px;
  background: #fcfcfd;
}

.detail-item>span:not(.coc-badge):not(.status-badge) {
  color: #858a93;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.detail-item>strong {
  overflow-wrap: anywhere;
  color: #30343a;
  font-size: 11px;
}

.detail-item small {
  color: #858a93;
  font-size: 10px;
  overflow-wrap: anywhere;
}

.answers-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 10px;
}

.answers-heading h3 {
  margin: 0;
  font-size: 12px;
}

.answers-heading>span {
  color: #858a93;
  font-size: 10px;
}

.answer-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.answer-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  min-width: 0;
  padding: 11px;
  border: 1px solid #eceef1;
  border-radius: 7px;
}

.answer-number {
  width: 25px;
  height: 25px;
  flex: 0 0 25px;
  display: grid;
  place-items: center;
  border-radius: 6px;
  background: #f1f2f4;
  color: #555b64;
  font-size: 10px;
  font-weight: 700;
}

.answer-body {
  min-width: 0;
}

.answer-body>strong {
  color: #30343a;
  font-size: 11px;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

.answer-body p {
  margin: 7px 0 0;
  color: #555b64;
  font-size: 10px;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

.answer-body p span {
  display: block;
  margin-bottom: 2px;
  color: #858a93;
  font-size: 9px;
  font-weight: 600;
}

.answer-result {
  display: inline-block;
  margin-top: 7px;
  font-size: 10px;
  font-weight: 650;
}

.answer-correct {
  color: #26734d;
}

.answer-wrong {
  color: #ad4a4a;
}

.answers-empty {
  padding: 20px;
  border: 1px dashed #dfe2e6;
  border-radius: 7px;
  color: #858a93;
  font-size: 11px;
  text-align: center;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  padding: 12px 20px;
  border-top: 1px solid #eceef1;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* PRINT */

@media print {
  body * {
    visibility: hidden !important;
  }

  .print-record,
  .print-record * {
    visibility: visible !important;
  }

  .modal-backdrop {
    position: static !important;
    display: block !important;
    padding: 0 !important;
    background: #fff !important;
  }

  .details-modal {
    width: 100% !important;
    max-height: none !important;
    overflow: visible !important;
    border: 0 !important;
    box-shadow: none !important;
  }

  .modal-header-actions,
  .modal-footer {
    display: none !important;
  }

  .modal-content {
    overflow: visible !important;
  }

  .answer-item,
  .detail-item {
    break-inside: avoid;
  }
}

/* RESPONSIVE */

@media (max-width: 1280px) {
  .filter-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 1024px) {

  .records-table th:nth-child(4),
  .records-table td:nth-child(4) {
    display: none;
  }

  .records-table {
    min-width: 760px;
  }
}

@media (max-width: 900px) {
  .search-wrap {
    flex: 1 1 220px;
    width: auto;
  }
}

/* Switch to cards below 760px */
@media (max-width: 760px) {
  .table-scroll {
    display: none;
  }

  .record-cards {
    display: block;
  }
}

@media (max-width: 650px) {
  .page-wrapper {
    padding: 14px;
  }

  .header-tools {
    width: 100%;
  }

  .record-count {
    flex: 0 0 auto;
  }

  .search-wrap {
    flex: 1 1 100%;
    order: 3;
  }

  .icon-button {
    margin-left: auto;
  }

  .filter-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .user-filter-banner {
    flex-direction: column;
    align-items: stretch;
  }

  .user-filter-banner .secondary-button {
    width: 100%;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 420px) {
  .page-wrapper {
    padding: 10px;
  }

  .section-title {
    font-size: 20px;
  }

  .filter-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .filter-heading {
    flex-wrap: wrap;
    gap: 6px;
  }

  .table-toolbar {
    align-items: flex-start;
    flex-direction: column;
  }

  .toolbar-actions {
    width: 100%;
    justify-content: space-between;
  }

  .export-all-menu {
    right: auto;
    left: 0;
  }

  .record-card-stats {
    grid-template-columns: minmax(0, 1fr);
  }

  .record-card-stats>div {
    border-right: 0;
  }

  .record-card-stats>div:nth-last-child(-n + 2) {
    border-bottom: 1px solid #eceef1;
  }

  .record-card-stats>div:last-child {
    border-bottom: 0;
  }

  .pagination {
    justify-content: center;
    text-align: center;
  }

  .pagination-info {
    width: 100%;
  }

  .modal-backdrop {
    align-items: flex-start;
    padding: 8px;
  }

  .details-modal {
    max-height: calc(100dvh - 16px);
  }

  .modal-header,
  .modal-content {
    padding: 14px;
  }

  .modal-footer {
    padding: 10px 14px;
  }

  .modal-footer .secondary-button {
    width: 100%;
  }
}
</style>