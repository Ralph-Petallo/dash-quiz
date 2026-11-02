<template>
  <div class="page-wrapper">
    <section class="admin-section">
      <Transition name="toast">
        <div v-if="successMessage" class="toast toast-success" role="status">
          <span class="toast-icon">
            <i class="fa-solid fa-check"></i>
          </span>
          <span>{{ successMessage }}</span>
          <button class="toast-close" type="button" aria-label="Dismiss notification" @click="successMessage = ''">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </Transition>

      <Transition name="toast">
        <div v-if="errorMessage" class="toast toast-error" role="alert">
          <span class="toast-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </span>
          <span>{{ errorMessage }}</span>
          <button class="toast-close" type="button" aria-label="Dismiss notification" @click="errorMessage = ''">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </Transition>

      <header class="page-header">
        <div class="header-text">
          <p class="eyebrow">ADMINISTRATION</p>
          <h1 class="section-title">Manage Competences</h1>
          <p class="section-subtitle">
            View and manage competency assessments.
          </p>
        </div>

        <div class="header-tools">
          <button type="button" class="primary-button" @click="router.push('/admin/assessments/create')">
            <i class="fa-solid fa-plus"></i>
            <span>New COC</span>
          </button>
        </div>
      </header>

      <section class="overview-strip" aria-label="Assessment overview">
        <div class="overview-item">
          <span class="overview-icon">
            <i class="fa-solid fa-layer-group"></i>
          </span>
          <span class="overview-copy">
            <strong>{{ assessments.length }}</strong>
            <span>Total Competences</span>
          </span>
        </div>

        <span class="overview-divider"></span>

        <div class="overview-item">
          <span class="overview-icon overview-icon-green">
            <i class="fa-solid fa-circle-check"></i>
          </span>
          <span class="overview-copy">
            <strong>{{ publishedCount }}</strong>
            <span>Published</span>
          </span>
        </div>

        <span class="overview-divider"></span>

        <div class="overview-item">
          <span class="overview-icon overview-icon-amber">
            <i class="fa-solid fa-circle-pause"></i>
          </span>
          <span class="overview-copy">
            <strong>{{ unavailableCount }}</strong>
            <span>Unavailable</span>
          </span>
        </div>
      </section>

      <section class="content-panel">
        <div class="toolbar">
          <div class="toolbar-copy">
            <h2>All Competences</h2>
            <p>View, sort, and manage your certificates of competency
              library.</p>
          </div>

          <div class="toolbar-controls">
            <div class="export-controls">
              <select v-model="exportFormat" aria-label="Export format">
                <option value="csv">CSV</option>
                <option value="pdf">PDF</option>
              </select>

              <button type="button" class="export-button" :disabled="loading || !filteredAssessments.length"
                @click="exportAssessments">
                <i class="fa-solid fa-download"></i>
                <span>Export</span>
              </button>
            </div>

            <label class="sort-select-wrap">
              <i class="fa-solid fa-arrow-down-wide-short"></i>
              <select v-model="sortKey" aria-label="Sort assessments by" @change="sortOrder = 'asc'">
                <option v-for="option in sortOptions" :key="option.key" :value="option.key">
                  {{ option.label }}
                </option>
              </select>
            </label>

            <button type="button" class="sort-direction"
              :aria-label="sortOrder === 'asc' ? 'Sort ascending' : 'Sort descending'"
              :title="sortOrder === 'asc' ? 'Ascending' : 'Descending'"
              @click="sortOrder = sortOrder === 'asc' ? 'desc' : 'asc'">
              <i :class="sortOrder === 'asc'
                ? 'fa-solid fa-arrow-up-short-wide'
                : 'fa-solid fa-arrow-down-wide-short'"></i>
            </button>
          </div>
        </div>

        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <strong>Loading assessments</strong>
          <span>Getting your assessment library ready...</span>
        </div>

        <div v-else-if="errorMessage && assessments.length === 0" class="state-panel">
          <div class="state-icon state-icon-error">
            <i class="fa-solid fa-cloud-exclamation"></i>
          </div>
          <h3>We couldn’t load assessments</h3>
          <p>{{ errorMessage }}</p>
          <button type="button" class="secondary-button" @click="fetchAssessments">
            <i class="fa-solid fa-rotate-right"></i>
            Try again
          </button>
        </div>

        <div v-else-if="filteredAssessments.length" class="assessment-grid">
          <article v-for="assessment in filteredAssessments" :key="assessment.id" class="assessment-card">
            <div class="card-topline">
              <div class="assessment-icon" :class="typeClass(assessment.type)">
                <i :class="assessment.icon || cocIcon(assessment.coc_number)"></i>
              </div>

              <span class="status-badge" :class="assessment.available === false
                ? 'status-unavailable'
                : 'status-published'">
                <span class="status-dot"></span>
                {{ assessment.available === false ? 'Unavailable' : 'Published' }}
              </span>
            </div>

            <div class="card-heading">
              <span class="assessment-type">
                {{ formatType(assessment.type) }}
              </span>
              <h3>{{ assessment.title || 'Untitled assessment' }}</h3>
            </div>

            <p class="assessment-description">
              {{ assessment.description || 'No description provided.' }}
            </p>

            <div class="card-details">
              <div class="detail-item">
                <span class="detail-label">COC number</span>
                <strong>{{ assessment.coc_number ?? 'N/A' }}</strong>
              </div>

              <div class="detail-item">
                <span class="detail-label">Category</span>
                <strong class="detail-truncate">
                  {{ assessment.category || 'General' }}
                </strong>
              </div>

              <div class="detail-item">
                <span class="detail-label">Difficulty</span>
                <span class="difficulty-badge" :class="difficultyClass(assessment.difficulty)">
                  {{ assessment.difficulty || 'N/A' }}
                </span>
              </div>
            </div>

            <footer class="card-footer">
              <div class="card-actions">

                <button type="button" class="action-button" title="View assessment"
                  :aria-label="`View Assessments ${assessment.title || 'assessment'}`"
                  @click="router.push(`/admin/assessments-list/${assessment.id}`)">
                  <i class="fa-solid fa-eye"></i>
                  <span>View Assessments</span>
                </button>

                <button type="button" class="action-button action-delete" title="Delete assessment"
                  :aria-label="`Delete ${assessment.title || 'assessment'}`" :disabled="deletingId === assessment.id"
                  @click="openDeleteDialog(assessment)">
                  <i :class="deletingId === assessment.id
                    ? 'fa-solid fa-spinner fa-spin'
                    : 'fa-regular fa-trash-can'"></i>
                  <span>Delete</span>
                </button>
              </div>
            </footer>
          </article>
        </div>

        <div v-else class="state-panel">
          <div class="state-icon">
            <i class="fa-solid fa-layer-group"></i>
          </div>
          <h3>No assessments yet</h3>
          <p>Create your first competency assessment to get started.</p>
          <button type="button" class="primary-button" @click="router.push('/admin/assessments/create')">
            <i class="fa-solid fa-plus"></i>
            Create assessment
          </button>
        </div>
      </section>
    </section>

    <Teleport to="body">
      <Transition name="modal">
        <div v-if="assessmentToDelete" class="modal-backdrop" @click.self="closeDeleteDialog"
          @keydown.esc="closeDeleteDialog">
          <section class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="delete-dialog-title"
            aria-describedby="delete-dialog-description">
            <button type="button" class="dialog-close" aria-label="Close dialog" :disabled="isDeleting"
              @click="closeDeleteDialog">
              <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="dialog-icon">
              <i class="fa-regular fa-trash-can"></i>
            </div>

            <span class="dialog-eyebrow">Delete assessment</span>
            <h2 id="delete-dialog-title">Are you sure?</h2>
            <p id="delete-dialog-description">
              You’re about to delete
              <strong>“{{ assessmentToDelete.title || 'Untitled assessment' }}”</strong>.
              This action can’t be undone.
            </p>

            <div class="dialog-actions">
              <button type="button" class="cancel-button" :disabled="isDeleting" @click="closeDeleteDialog">
                Cancel
              </button>

              <button type="button" class="confirm-delete-button" :disabled="isDeleting" @click="confirmDelete">
                <i :class="isDeleting
                  ? 'fa-solid fa-spinner fa-spin'
                  : 'fa-regular fa-trash-can'"></i>
                {{ isDeleting ? 'Deleting...' : 'Delete assessment' }}
              </button>
            </div>
          </section>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { jsPDF } from 'jspdf'
import { autoTable } from 'jspdf-autotable'
import api from '../../bootstrap'

const router = useRouter()

const assessments = ref([])
const loading = ref(true)
const deletingId = ref(null)
const assessmentToDelete = ref(null)
const searchQuery = ref('')
const successMessage = ref('')
const errorMessage = ref('')
const sortKey = ref('id')
const sortOrder = ref('asc')
const exportFormat = ref('csv')

let messageTimeout

const sortOptions = [
  { key: 'id', label: 'Assessment ID' },
  { key: 'title', label: 'Title' },
  { key: 'type', label: 'Type' },
  { key: 'difficulty', label: 'Difficulty' },

]

const cocIcon = (number) => {
  const icons = {
    1: "fa-solid fa-computer",
    2: "fa-solid fa-network-wired",
    3: "fa-solid fa-server",
    4: "fa-solid fa-screwdriver-wrench",
  }

  return icons[Number(number)] || "fa-solid fa-layer-group"
}

const isDeleting = computed(() => deletingId.value !== null)

const publishedCount = computed(() =>
  assessments.value.filter(item => item.available !== false).length
)

const unavailableCount = computed(() =>
  assessments.value.filter(item => item.available === false).length
)

const filteredAssessments = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  const results = assessments.value.filter(assessment => {
    if (!query) return true

    const searchable = [
      assessment.title,
      assessment.description,
      assessment.type,
      assessment.category,
      assessment.coc_number,
      assessment.difficulty,
      assessment.id,
    ]
      .filter(value => value !== null && value !== undefined)
      .join(' ')
      .toLowerCase()

    return searchable.includes(query)
  })

  return results.sort((a, b) => {
    let valueA = a?.[sortKey.value] ?? ''
    let valueB = b?.[sortKey.value] ?? ''

    if (typeof valueA === 'string') valueA = valueA.toLowerCase()
    if (typeof valueB === 'string') valueB = valueB.toLowerCase()

    if (valueA === valueB) return 0

    const result = valueA > valueB ? 1 : -1
    return sortOrder.value === 'asc' ? result : -result
  })
})

const fetchAssessments = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    let response

    for (let attempt = 0; attempt < 4; attempt += 1) {
      try {
        response = await api.get('/api/admin/quizzes')
        break
      } catch (error) {
        if (error.response?.status !== 429 || attempt === 3) {
          throw error
        }

        await new Promise(resolve => setTimeout(resolve, 1000))
      }
    }

    const payload = response?.data
    const rows = payload?.result ?? payload?.data ?? payload
    assessments.value = Array.isArray(rows) ? rows : []
  } catch (error) {
    console.error('Failed to load assessments:', error)
    errorMessage.value =
      error.response?.data?.message || 'Failed to load assessments.'
  } finally {
    loading.value = false
  }
}

const formatType = type => {
  if (!type) return 'Certificate of Competence'

  return String(type)
    .replace(/_/g, ' ')
    .replace(/\b\w/g, character => character.toUpperCase())
}

const typeClass = type => {
  const value = String(type || '').toLowerCase()

  if (value.includes('image')) return 'type-image'
  if (value.includes('drag')) return 'type-drag'
  if (value.includes('scenario')) return 'type-scenario'
  if (value.includes('virtual')) return 'type-virtual'
  if (value.includes('multiple')) return 'type-multiple'

  return 'type-default'
}

const typeIcon = type => {
  const value = String(type || '').toLowerCase()

  if (value.includes('image')) return 'fa-solid fa-image'
  if (value.includes('drag')) return 'fa-solid fa-arrows-up-down-left-right'
  if (value.includes('scenario')) return 'fa-solid fa-comments'
  if (value.includes('virtual')) return 'fa-solid fa-laptop-code'
  if (value.includes('multiple')) return 'fa-solid fa-list-check'

  return 'fa-solid fa-layer-group'
}

const difficultyClass = difficulty => {
  const value = String(difficulty || '').toLowerCase()

  if (value === 'easy') return 'difficulty-easy'
  if (value === 'medium') return 'difficulty-medium'
  if (value === 'hard') return 'difficulty-hard'

  return 'difficulty-default'
}

const exportAssessments = () => {
  const rows = filteredAssessments.value

  if (!rows.length) return

  if (exportFormat.value === 'pdf') {
    const pdf = new jsPDF({ orientation: 'landscape' })

    pdf.setFontSize(16)
    pdf.text('Competence', 14, 16)

    autoTable(pdf, {
      startY: 23,
      head: [[
        'ID',
        'Title',
        'Type',
        'COC number',
        'Category',
        'Difficulty',
        'Status',
        'Description',
      ]],
      body: rows.map(assessment => [
        assessment.id ?? '',
        assessment.title || 'Untitled assessment',
        formatType(assessment.type),
        assessment.coc_number ?? 'N/A',
        assessment.category || 'General',
        assessment.difficulty || 'N/A',
        assessment.available === false ? 'Unavailable' : 'Published',
        assessment.description || '',
      ]),
      styles: {
        fontSize: 8,
        overflow: 'linebreak',
      },
      headStyles: {
        fillColor: [59, 92, 204],
      },
      columnStyles: {
        7: { cellWidth: 70 },
      },
    })

    pdf.save('assessments.pdf')
    return
  }

  const headers = [
    'ID',
    'Title',
    'Type',
    'COC number',
    'Category',
    'Difficulty',
    'Status',
    'Description',
  ]

  const csvCell = value =>
    `"${String(value ?? '').replace(/"/g, '""')}"`

  const csv = [
    headers.map(csvCell).join(','),
    ...rows.map(assessment => [
      assessment.id,
      assessment.title || 'Untitled assessment',
      formatType(assessment.type),
      assessment.coc_number ?? 'N/A',
      assessment.category || 'General',
      assessment.difficulty || 'N/A',
      assessment.available === false ? 'Unavailable' : 'Published',
      assessment.description || '',
    ].map(csvCell).join(',')),
  ].join('\r\n')

  const blob = new Blob(['\uFEFF', csv], {
    type: 'text/csv;charset=utf-8;',
  })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.download = 'assessments.csv'
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}

const showSuccess = message => {
  successMessage.value = message
  errorMessage.value = ''

  clearTimeout(messageTimeout)
  messageTimeout = setTimeout(() => {
    successMessage.value = ''
  }, 3500)
}

const openDeleteDialog = assessment => {
  assessmentToDelete.value = assessment
  errorMessage.value = ''
}

const closeDeleteDialog = () => {
  if (deletingId.value !== null) return
  assessmentToDelete.value = null
}

const confirmDelete = async () => {
  const assessment = assessmentToDelete.value
  if (!assessment) return

  deletingId.value = assessment.id
  errorMessage.value = ''

  try {
    await api.delete(`/api/admin/assessments/${assessment.id}`)
    assessments.value = assessments.value.filter(
      item => item.id !== assessment.id
    )
    assessmentToDelete.value = null
    showSuccess('COC deleted successfully.')
  } catch (error) {
    console.error('Failed to delete assessment:', error)
    assessmentToDelete.value = null
    errorMessage.value =
      error.response?.data?.message ||
      'Could not delete the assessment. Please try again.'
  } finally {
    deletingId.value = null
  }
}

const onKeydown = event => {
  if (event.key === 'Escape' && assessmentToDelete.value) {
    closeDeleteDialog()
  }
}

onMounted(() => {
  fetchAssessments()
  window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  clearTimeout(messageTimeout)
  window.removeEventListener('keydown', onKeydown)
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

.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px 20px;
  margin-bottom: 20px;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 9px;
  margin-bottom: 16px;
  color: #8a95a5;
  font-size: 12px;
  font-weight: 550;
}

.breadcrumb i {
  color: #b4bdc9;
  font-size: 9px;
}

.breadcrumb-current {
  color: #526075;
}

.heading-row {
  display: flex;
  align-items: center;
  gap: 14px;
}

.heading-icon {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  border: 1px solid #e1e7f0;
  border-radius: 14px;
  background: #fff;
  color: var(--accent);
  box-shadow: 0 3px 8px rgb(27 43 70 / 4%);
  font-size: 18px;
}

.heading-row h1 {
  margin: 0;
  color: var(--ink);
  font-size: clamp(23px, 2.5vw, 30px);
  font-weight: 750;
  letter-spacing: -0.045em;
}

.heading-row p {
  margin: 6px 0 0;
  color: var(--muted);
  font-size: 13px;
}

.primary-button,
.secondary-button {
  min-height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  padding: 0 16px;
  border: 1px solid transparent;
  border-radius: 9px;
  font-size: 13px;
  font-weight: 650;
  cursor: pointer;
  transition:
    background 0.18s ease,
    border-color 0.18s ease,
    transform 0.18s ease,
    box-shadow 0.18s ease;
}

.header-text {
  min-width: 0;
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

.eyebrow {
  margin: 0 0 6px;
  color: #858a93;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.12em;
}

.header-tools {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 9px;
}

.record-count {
  height: 40px;
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 0 12px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  white-space: nowrap;
}

.record-count strong {
  font-size: 13px;
}

.record-count span {
  color: #858a93;
  font-size: 12px;
}

.primary-button {
  min-height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 14px;
  border: 1px solid #252a32;
  border-radius: 8px;
  background: #252a32;
  color: #fff;
  font-size: 11px;
  font-weight: 650;
  cursor: pointer;
  transition: background 0.16s ease, transform 0.16s ease;
}

.primary-button:hover {
  transform: translateY(-1px);
  background: #11151b;
}

.overview-strip {
  display: flex;
  align-items: center;
  gap: 28px;
  margin-bottom: 16px;
  padding: 15px;
  border: 1px solid #e7e9ed;
  border-radius: 10px;
  background: #fff;
}

.overview-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.overview-icon {
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  border-radius: 11px;
  background: #eef2ff;
  color: #526ed0;
  font-size: 14px;
}

.overview-icon-green {
  background: #eaf7f0;
  color: #278457;
}

.overview-icon-amber {
  background: #fff5e8;
  color: #b87922;
}

.overview-copy {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.overview-copy strong {
  color: var(--ink);
  font-size: 17px;
  line-height: 1;
}

.overview-copy>span {
  color: var(--muted);
  font-size: 11px;
}

.overview-divider {
  width: 1px;
  height: 34px;
  background: var(--line);
}

.content-panel {
  overflow: hidden;
  border: 1px solid var(--line);
  border-radius: 13px;
  background: var(--surface);
  box-shadow: 0 2px 8px rgb(29 43 67 / 2%);
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 19px 21px;
  border-bottom: 1px solid var(--line);
}

.toolbar-copy h2 {
  margin: 0;
  color: var(--ink);
  font-size: 15px;
  font-weight: 700;
}

.toolbar-copy p {
  margin: 5px 0 0;
  color: var(--muted);
  font-size: 11px;
}

.toolbar-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.export-controls {
  display: flex;
  align-items: center;
  gap: 6px;
}

.export-controls select,
.export-button {
  height: 37px;
  border: 1px solid #e3e8ef;
  border-radius: 8px;
  background: #fff;
  color: #526075;
  font: inherit;
  font-size: 11px;
}

.export-controls select {
  padding: 0 9px;
  cursor: pointer;
}

.export-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 0 11px;
  cursor: pointer;
  transition: background 0.16s ease, border-color 0.16s ease, color 0.16s ease;
}

.export-button:hover:not(:disabled) {
  border-color: #d5dce7;
  background: #f6f8fb;
  color: var(--accent);
}

.export-button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.search-box,
.sort-select-wrap {
  height: 37px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 11px;
  border: 1px solid #e3e8ef;
  border-radius: 8px;
  background: #fff;
  color: #8792a2;
}

.search-box {
  width: 220px;
}

.search-box>i,
.sort-select-wrap>i {
  flex: 0 0 auto;
  font-size: 12px;
}

.search-box input {
  width: 100%;
  min-width: 0;
  border: 0;
  outline: 0;
  background: transparent;
  color: var(--ink);
  font: inherit;
  font-size: 11px;
}

.search-box input::placeholder {
  color: #a2acb9;
}

.clear-search {
  display: grid;
  place-items: center;
  padding: 3px;
  border: 0;
  background: transparent;
  color: #8994a3;
  cursor: pointer;
}

.sort-select-wrap select {
  max-width: 125px;
  border: 0;
  outline: 0;
  background: transparent;
  color: #526075;
  font: inherit;
  font-size: 11px;
  cursor: pointer;
}

.sort-direction {
  width: 37px;
  height: 37px;
  display: grid;
  place-items: center;
  border: 1px solid #e3e8ef;
  border-radius: 8px;
  background: #fff;
  color: #657287;
  cursor: pointer;
  transition: background 0.16s ease, color 0.16s ease;
}

.sort-direction:hover {
  background: #f4f6fa;
  color: var(--accent);
}

.assessment-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  padding: 18px;
}

.assessment-card {
  min-width: 0;
  display: flex;
  flex-direction: column;
  padding: 17px;
  border: 1px solid #e8edf3;
  border-radius: 11px;
  background: #fff;
  transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
}

.assessment-card:hover {
  transform: translateY(-2px);
  border-color: #d6deeb;
  box-shadow: 0 8px 22px rgb(36 52 78 / 7%);
}

.card-topline {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.assessment-icon {
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  font-size: 16px;
}

.type-default {
  background: #e6eaf0;
  color: #252a32;
}

.type-image {
  background: #f1edff;
  color: #7557bd;
}

.type-drag {
  background: #eaf5ff;
  color: #3477a8;
}

.type-scenario {
  background: #fff3e9;
  color: #bf7534;
}

.type-virtual {
  background: #eaf7f0;
  color: #31845b;
}

.type-multiple {
  background: #fff0f1;
  color: #bf5966;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 8px;
  border-radius: 20px;
  font-size: 10px;
  font-weight: 650;
  white-space: nowrap;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.status-published {
  background: #edf8f1;
  color: #32845a;
}

.status-unavailable {
  background: #fff5e9;
  color: #a86a1c;
}

.card-heading {
  margin-top: 14px;
}

.assessment-type {
  color: #8190a3;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.09em;
  text-transform: uppercase;
}

.card-heading h3 {
  display: -webkit-box;
  min-height: 42px;
  margin: 6px 0 0;
  overflow: hidden;
  color: #202b3b;
  font-size: 14px;
  font-weight: 700;
  line-height: 1.5;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.assessment-description {
  display: -webkit-box;
  min-height: 37px;
  margin: 9px 0 16px;
  overflow: hidden;
  color: #7d8999;
  font-size: 11px;
  line-height: 1.65;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.card-details {
  display: grid;
  grid-template-columns: 0.8fr 1.1fr 0.9fr;
  gap: 8px;
  margin-top: auto;
  padding: 12px 0;
  border-top: 1px solid #edf0f4;
  border-bottom: 1px solid #edf0f4;
}

.detail-item {
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
}

.detail-label {
  color: #9aa4b2;
  font-size: 9px;
  font-weight: 650;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.detail-item strong {
  max-width: 100%;
  overflow: hidden;
  color: #4d5a6d;
  font-size: 10px;
  font-weight: 650;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.detail-truncate {
  display: block;
}

.difficulty-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 7px;
  border-radius: 5px;
  font-size: 9px;
  font-weight: 700;
  text-transform: capitalize;
}

.difficulty-easy {
  background: #eaf7f0;
  color: #32845a;
}

.difficulty-medium {
  background: #fff5e8;
  color: #a86a1c;
}

.difficulty-hard {
  background: #fff0f1;
  color: #b84d5a;
}

.difficulty-default {
  background: #f0f2f5;
  color: #7c8796;
}

.card-footer {
  display: flex;
  align-items: center;
  justify-content: end;
  gap: 8px;
  padding-top: 12px;
}

.assessment-reference {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  overflow: hidden;
  color: #98a2b0;
  font-size: 9px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.card-actions {
  display: flex;
  align-items: center;
  gap: 5px;
}

.action-button {
  min-height: 29px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 0 8px;
  border: 1px solid #e6eaf0;
  border-radius: 6px;
  background: #fff;
  color: #637084;
  font-size: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.16s ease, border-color 0.16s ease, color 0.16s ease;
}

.action-button:hover:not(:disabled) {
  border-color: #d5dce7;
  background: #f6f8fb;
  color: var(--accent);
}

.action-delete:hover:not(:disabled) {
  border-color: #f1d3d5;
  background: #fff5f5;
  color: #bd4b55;
}

.action-button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.state-panel,
.loading-state {
  min-height: 310px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px;
  text-align: center;
}

.loading-state {
  gap: 9px;
}

.loading-state strong {
  margin-top: 5px;
  color: #354256;
  font-size: 13px;
}

.loading-state>span {
  color: #8b96a5;
  font-size: 11px;
}

.loading-spinner {
  width: 31px;
  height: 31px;
  border: 3px solid #e9edf4;
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

.state-icon {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  margin-bottom: 13px;
  border-radius: 14px;
  background: #eef2ff;
  color: #6077c8;
  font-size: 17px;
}

.state-icon-error {
  background: #fff0f1;
  color: #bd4b55;
}

.state-panel h3 {
  margin: 0;
  color: #273448;
  font-size: 15px;
}

.state-panel p {
  max-width: 340px;
  margin: 7px 0 16px;
  color: #8490a0;
  font-size: 11px;
  line-height: 1.6;
}

.secondary-button {
  min-height: 36px;
  border-color: #e1e6ee;
  background: #fff;
  color: #526075;
  font-size: 11px;
}

.secondary-button:hover {
  background: #f6f8fb;
}

.toast {
  position: fixed;
  z-index: 20;
  top: 22px;
  right: 22px;
  max-width: min(420px, calc(100vw - 32px));
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 12px 14px;
  border: 1px solid #e3e9e5;
  border-radius: 10px;
  background: #fff;
  color: #354256;
  box-shadow: 0 12px 34px rgb(24 34 48 / 13%);
  font-size: 12px;
}

.toast-success .toast-icon {
  background: #eaf7f0;
  color: #32845a;
}

.toast-error .toast-icon {
  background: #fff0f1;
  color: #bd4b55;
}

.toast-icon {
  width: 27px;
  height: 27px;
  flex: 0 0 27px;
  display: grid;
  place-items: center;
  border-radius: 8px;
  font-size: 11px;
}

.toast-close {
  width: 25px;
  height: 25px;
  display: grid;
  place-items: center;
  margin-left: auto;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: #8b96a5;
  cursor: pointer;
}

.toast-close:hover {
  background: #f2f4f7;
}

.modal-backdrop {
  position: fixed;
  z-index: 50;
  inset: 0;
  display: grid;
  place-items: center;
  padding: 20px;
  background: rgb(20 29 43 / 48%);
  backdrop-filter: blur(3px);
}

.confirm-dialog {
  position: relative;
  width: min(100%, 430px);
  padding: 30px;
  border: 1px solid rgb(255 255 255 / 70%);
  border-radius: 17px;
  background: #fff;
  box-shadow: 0 24px 80px rgb(13 24 42 / 24%);
  text-align: center;
}

.dialog-close {
  position: absolute;
  top: 13px;
  right: 13px;
  width: 31px;
  height: 31px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: #8b96a5;
  cursor: pointer;
}

.dialog-close:hover:not(:disabled) {
  background: #f2f4f7;
  color: #526075;
}

.dialog-close:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.dialog-icon {
  width: 54px;
  height: 54px;
  display: grid;
  place-items: center;
  margin: 0 auto 16px;
  border: 1px solid #f3dcdd;
  border-radius: 16px;
  background: #fff1f1;
  color: #c0525c;
  font-size: 20px;
}

.dialog-eyebrow {
  color: #c0525c;
  font-size: 10px;
  font-weight: 750;
  letter-spacing: 0.11em;
  text-transform: uppercase;
}

.confirm-dialog h2 {
  margin: 8px 0 0;
  color: #202b3b;
  font-size: 22px;
  font-weight: 750;
  letter-spacing: -0.035em;
}

.confirm-dialog p {
  margin: 10px auto 0;
  color: #758194;
  font-size: 12px;
  line-height: 1.7;
}

.confirm-dialog p strong {
  color: #364256;
  font-weight: 700;
}

.dialog-actions {
  display: flex;
  justify-content: center;
  gap: 9px;
  margin-top: 25px;
}

.cancel-button,
.confirm-delete-button {
  min-height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 15px;
  border: 1px solid transparent;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 650;
  cursor: pointer;
  transition: background 0.16s ease, border-color 0.16s ease, transform 0.16s ease;
}

.cancel-button {
  border-color: #e1e6ee;
  background: #fff;
  color: #526075;
}

.cancel-button:hover:not(:disabled) {
  background: #f6f8fb;
}

.confirm-delete-button {
  background: #bd4b55;
  color: #fff;
}

.confirm-delete-button:hover:not(:disabled) {
  transform: translateY(-1px);
  background: #a93d47;
}

.cancel-button:disabled,
.confirm-delete-button:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.toast-enter-active,
.toast-leave-active,
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .confirm-dialog,
.modal-leave-to .confirm-dialog {
  transform: translateY(10px) scale(0.98);
}

.confirm-dialog {
  transition: transform 0.18s ease;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 980px) {
  .assessment-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .toolbar {
    align-items: flex-start;
    flex-direction: column;
  }

  .toolbar-controls {
    width: 100%;
    flex-wrap: wrap;
  }

  .search-box {
    flex: 1;
    width: auto;
  }
}

@media (max-width: 640px) {
  .page-wrapper {
    padding: 14px;
  }

  .page-header {
    align-items: stretch;
    flex-direction: column;
    gap: 18px;
  }

  .header-tools {
    width: 100%;
    justify-content: space-between;
  }

  .header-tools .primary-button {
    flex: 1;
  }

  .overview-strip {
    justify-content: space-between;
    gap: 10px;
    padding: 14px 12px;
  }

  .overview-item {
    gap: 8px;
    min-width: 0;
  }

  .overview-icon {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    font-size: 12px;
    flex: 0 0 auto;
  }

  .overview-copy strong {
    font-size: 15px;
  }

  .overview-copy>span {
    font-size: 7px;
    line-height: 1.2;
  }

  .overview-divider {
    height: 29px;
    flex: 0 0 1px;
  }

  .toolbar {
    padding: 16px 14px;
  }

  .toolbar-controls {
    flex-wrap: wrap;
  }

  .search-box {
    flex: 1 0 100%;
  }

  .export-controls,
  .sort-select-wrap {
    flex: 1;
    min-width: 0;
  }

  .export-controls select,
  .sort-select-wrap select {
    min-width: 0;
    max-width: none;
    flex: 1;
  }

  .assessment-grid {
    grid-template-columns: 1fr;
    padding: 12px;
  }

  .assessment-card {
    padding: 15px;
  }

  .toast {
    top: 12px;
    right: 12px;
    left: 12px;
    width: auto;
  }
}

@media (max-width: 390px) {
  .page-wrapper {
    padding: 10px;
  }

  .overview-strip {
    gap: 6px;
    padding: 12px 8px;
  }

  .overview-item {
    gap: 5px;
  }

  .overview-copy>span {
    max-width: 76px;
    font-size: 10px;
  }

  .card-footer {
    align-items: stretch;
    flex-direction: column;
  }

  .card-actions {
    width: 100%;
  }

  .action-button {
    flex: 1;
  }

  .dialog-actions {
    flex-direction: column-reverse;
  }

  .dialog-actions button {
    width: 100%;
  }
}

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