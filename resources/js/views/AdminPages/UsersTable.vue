<template>
  <main class="dashboard-wrapper">
    <section class="admin-section">
      <header class="page-header">
        <div>
          <p class="eyebrow">ADMINISTRATION</p>
          <h1 class="section-title">Registered students</h1>
          <p class="section-subtitle">
            Track student assessment progress across all COCs. Progress is
            based on each learner's best result per assessment.
          </p>
        </div>

        <div class="header-tools">
          <div class="user-count" aria-live="polite">
            <strong>{{ filteredUsers.length }}</strong>
            <span>{{ filteredUsers.length === 1 ? 'student' : 'students' }}</span>
          </div>

          <label class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input v-model="searchQuery" type="search" class="search-input" placeholder="Search name, email, or ID"
              aria-label="Search students" />
          </label>

          <button class="icon-button" type="button" aria-label="Refresh students" title="Refresh students"
            :disabled="loading" @click="fetchUsers">
            <i class="fa-solid fa-rotate" :class="{ spinning: loading }" aria-hidden="true"></i>
          </button>
        </div>
      </header>

      <section class="overview-grid" aria-label="Student progress overview">
        <article v-for="card in overviewCards" :key="card.label" class="overview-card">
          <span class="overview-icon" :class="card.tone">
            <i :class="card.icon" aria-hidden="true"></i>
          </span>
          <div>
            <span class="overview-label">{{ card.label }}</span>
            <strong>{{ card.value }}</strong>
          </div>
        </article>
      </section>

      <transition name="fade">
        <div v-if="successMessage" class="inline-alert" role="status">
          <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
          <span>{{ successMessage }}</span>
          <button type="button" aria-label="Dismiss notification" @click="successMessage = ''">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
          </button>
        </div>
      </transition>

      <div v-if="errorMessage" class="inline-alert error-alert" role="alert">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span>{{ errorMessage }}</span>
        <button type="button" class="retry-button" @click="fetchUsers">Retry</button>
      </div>

      <section v-if="loading && !users.length" class="table-card loading-card">
        <div class="loading-state">
          <span class="spinner" aria-hidden="true"></span>
          <strong>Loading students</strong>
          <span>Getting the latest student accounts…</span>
        </div>
      </section>

      <section v-else class="table-card">
        <div class="table-toolbar">
          <div>
            <h2>Student accounts</h2>
            <p>Showing {{ filteredUsers.length }} of {{ users.length }} students</p>
          </div>

          <div class="toolbar-controls">
            <label class="readiness-filter">
              <span>Progress</span>
              <select v-model="readinessFilter" aria-label="Filter by student progress">
                <option value="">All students</option>
                <option value="on_track">On track</option>
                <option value="needs_improvement">Needs improvement</option>
                <option value="getting_started">Getting started</option>
                <option value="not_started">Not started</option>
              </select>
            </label>

            <label class="page-size">
              <span>Rows</span>
              <select v-model.number="perPage" aria-label="Rows per page">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>
            </label>
          </div>
        </div>

        <!-- Desktop table -->
        <div class="table-scroll">
          <table class="users-table">
            <thead>
              <tr>
                <th>
                  <button type="button" @click="sortBy('id')">
                    ID <i :class="sortIcon('id')" aria-hidden="true"></i>
                  </button>
                </th>
                <th>
                  <button type="button" @click="sortBy('full_name')">
                    Student <i :class="sortIcon('full_name')" aria-hidden="true"></i>
                  </button>
                </th>
                <th>
                  <button type="button" @click="sortBy('email')">
                    Email <i :class="sortIcon('email')" aria-hidden="true"></i>
                  </button>
                </th>
                <th class="center">
                  <button type="button" @click="sortBy('quizzes_taken')">
                    Assessments <i :class="sortIcon('quizzes_taken')" aria-hidden="true"></i>
                  </button>
                </th>
                <th>
                  <button type="button" @click="sortBy('average_score')">
                    Progress <i :class="sortIcon('average_score')" aria-hidden="true"></i>
                  </button>
                </th>
                <th>
                  <button type="button" @click="sortBy('created_at')">
                    Joined <i :class="sortIcon('created_at')" aria-hidden="true"></i>
                  </button>
                </th>
                <th class="actions-heading">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="paginatedUsers.length === 0">
                <td colspan="7">
                  <div class="empty-state">
                    <span class="empty-icon">
                      <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
                    </span>
                    <strong>No students found</strong>
                    <span>Try adjusting your search or filters.</span>
                    <button v-if="searchQuery || readinessFilter" type="button" class="clear-search-button"
                      @click="clearFilters">
                      Clear filters
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-for="user in paginatedUsers" :key="user.id">
                <td><span class="id-value">#{{ user.id }}</span></td>

                <td>
                  <div class="student-cell">
                    <img class="student-avatar" :src="profileImageUrl(user)" :alt="fullName(user)"
                      @error="useFallbackImage" />
                    <div class="student-copy">
                      <strong>{{ fullName(user) }}</strong>
                      <span>Student account</span>
                    </div>
                  </div>
                </td>

                <td><span class="email-value">{{ user.email || '—' }}</span></td>

                <td class="center">
                  <span class="assessment-count">{{ Number(user.quizzes_taken ?? 0) }}</span>
                  <span class="assessment-caption">
                    {{ Number(user.quizzes_taken ?? 0) === 1 ? 'assessment' : 'assessments' }}
                  </span>
                </td>

                <td>
                  <span class="readiness-badge" :class="`readiness-${getReadinessStatus(user)}`"
                    :title="readinessDescription(user)">
                    <span class="readiness-dot"></span>
                    {{ readinessLabel(user) }}
                  </span>
                  <small class="average-score">
                    Avg. {{ formatAverage(user.average_score) }}
                    <template v-if="Number(user.quizzes_taken) > 0">
                      · {{ Number(user.passed ?? 0) }} passed
                    </template>
                  </small>
                </td>

                <td><span class="date-value">{{ formatDate(user.created_at) }}</span></td>

                <td>
                  <div class="row-actions">
                    <button class="action-button view-action" type="button" title="View records"
                      :aria-label="`View records for ${fullName(user)}`" @click="viewRecords(user)">
                      <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                    </button>

                    <button class="action-button edit-action" type="button" title="Edit student"
                      :aria-label="`Edit ${fullName(user)}`" @click="openEdit(user)">
                      <i class="fa-solid fa-pen" aria-hidden="true"></i>
                    </button>

                    <button class="action-button delete-action" type="button" title="Delete student"
                      :aria-label="`Delete ${fullName(user)}`" @click="openDeleteConfirmation(user)">
                      <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile cards -->
        <div class="student-cards">
          <p v-if="paginatedUsers.length === 0" class="cards-empty">No students found.</p>

          <article v-for="user in paginatedUsers" :key="`card-${user.id}`" class="student-card">
            <div class="student-card-head">
              <img class="student-avatar" :src="profileImageUrl(user)" :alt="fullName(user)"
                @error="useFallbackImage" />
              <div class="student-copy">
                <strong>{{ fullName(user) }}</strong>
                <span class="email-value">{{ user.email || '—' }}</span>
              </div>
              <span class="id-value">#{{ user.id }}</span>
            </div>

            <div class="student-card-meta">
              <span class="readiness-badge" :class="`readiness-${getReadinessStatus(user)}`">
                <span class="readiness-dot"></span>
                {{ readinessLabel(user) }}
              </span>
              <span class="card-stat">
                <strong>{{ Number(user.quizzes_taken ?? 0) }}</strong> assessments
              </span>
              <span class="card-stat">
                Avg <strong>{{ formatAverage(user.average_score) }}</strong>
              </span>
              <span class="card-stat">
                Joined <strong>{{ formatDate(user.created_at) }}</strong>
              </span>
            </div>

            <div class="row-actions card-actions">
              <button class="action-button view-action" type="button" :aria-label="`View records for ${fullName(user)}`"
                @click="viewRecords(user)">
                <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
              </button>
              <button class="action-button edit-action" type="button" :aria-label="`Edit ${fullName(user)}`"
                @click="openEdit(user)">
                <i class="fa-solid fa-pen" aria-hidden="true"></i>
              </button>
              <button class="action-button delete-action" type="button" :aria-label="`Delete ${fullName(user)}`"
                @click="openDeleteConfirmation(user)">
                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
              </button>
            </div>
          </article>
        </div>

        <footer class="table-footer">
          <span class="pagination-summary">
            <template v-if="filteredUsers.length">
              {{ pageStart + 1 }}–{{ Math.min(pageStart + perPage, filteredUsers.length) }}
              of {{ filteredUsers.length }} students
            </template>
            <template v-else>0 students</template>
          </span>

          <div class="pagination-controls">
            <button type="button" aria-label="Previous page" :disabled="currentPage <= 1" @click="currentPage--">
              <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>

            <span>Page <strong>{{ currentPage }}</strong> of {{ totalPages }}</span>

            <button type="button" aria-label="Next page" :disabled="currentPage >= totalPages" @click="currentPage++">
              <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
          </div>
        </footer>
      </section>
    </section>

    <ToastNotification :message="notification.message" :type="notification.type" @clear="notification.message = ''" />

    <EditUserModal :show="openEditModal" :user="selectedUser" :saving="saving" :error="saveError" :errors="saveErrors"
      @close="openEditModal = false" @save="handleSave" />

    <Teleport to="body">
      <div v-if="userToDelete" class="modal-backdrop" @click.self="closeDeleteConfirmation">
        <section class="confirm-modal" role="alertdialog" aria-modal="true" aria-labelledby="delete-title"
          aria-describedby="delete-description" @keydown.esc="closeDeleteConfirmation">
          <div class="confirm-icon">
            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
          </div>

          <button class="confirm-close" type="button" aria-label="Close confirmation" :disabled="deleting"
            @click="closeDeleteConfirmation">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
          </button>

          <p class="confirm-eyebrow">DELETE STUDENT</p>
          <h2 id="delete-title">Delete this account?</h2>

          <p id="delete-description" class="confirm-copy">
            You're about to delete
            <strong>{{ fullName(userToDelete) }}</strong>
            <span v-if="userToDelete.email">({{ userToDelete.email }})</span>.
            This action can't be undone.
          </p>

          <div class="confirm-warning">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span>The student account and its associated data may be permanently removed.</span>
          </div>

          <div class="confirm-actions">
            <button class="cancel-button" type="button" :disabled="deleting" @click="closeDeleteConfirmation">
              Cancel
            </button>
            <button class="confirm-delete-button" type="button" :disabled="deleting" @click="deleteUser">
              <span v-if="deleting" class="button-spinner" aria-hidden="true"></span>
              <i v-else class="fa-regular fa-trash-can" aria-hidden="true"></i>
              {{ deleting ? 'Deleting…' : 'Delete account' }}
            </button>
          </div>
        </section>
      </div>
    </Teleport>
  </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/bootstrap'

import ToastNotification from '@/components/ToastNotification.vue'
import EditUserModal from '@/components/AdminSide/EditUserModal.vue'

const router = useRouter()

const PASSING_SCORE = 75
const FALLBACK_IMAGE = '/storage/images/profiles/default.png'

const users = ref([])
const loading = ref(true)
const errorMessage = ref('')
const successMessage = ref('')

const openEditModal = ref(false)
const selectedUser = ref(null)
const userToDelete = ref(null)
const deleting = ref(false)

// Edit state: the request is sent from here, so the modal only reports results.
const saving = ref(false)
const saveError = ref('')
const saveErrors = ref({})

const currentPage = ref(1)
const perPage = ref(10)

const sortKey = ref('created_at')
const sortOrder = ref('desc')

const searchQuery = ref('')
const readinessFilter = ref('')

const notification = ref({ message: '', type: 'success' })

const readinessLabels = {
  on_track: 'On track',
  needs_improvement: 'Needs improvement',
  getting_started: 'Getting started',
  not_started: 'Not started',
}

const readinessDescriptions = {
  on_track: `Two or more assessments taken, with an average score of at least ${PASSING_SCORE}%.`,
  needs_improvement: `Two or more assessments taken, with an average score below ${PASSING_SCORE}%.`,
  getting_started: 'One assessment taken so far.',
  not_started: 'No assessments taken yet.',
}

const fullName = (user) => {
  const name = [user?.first_name, user?.last_name].filter(Boolean).join(' ').trim()
  return name || `User #${user?.id ?? '—'}`
}

const profileImageUrl = (user) => {
  const photo = user?.profile_photo
  if (!photo) return FALLBACK_IMAGE
  if (/^https?:\/\//i.test(photo) || String(photo).startsWith('/')) return photo
  return `/storage/images/profiles/${String(photo).replace(/^\/+/, '')}`
}

const useFallbackImage = (event) => {
  event.target.onerror = null
  event.target.src = FALLBACK_IMAGE
}

// Trust the server's readiness_status. Fall back only if it's missing.
const getReadinessStatus = (user) => {
  const backendStatus = user?.readiness_status
  if (backendStatus && readinessLabels[backendStatus]) return backendStatus

  const count = Number(user?.quizzes_taken ?? 0)
  const average = user?.average_score == null ? null : Number(user.average_score)

  if (count === 0) return 'not_started'
  if (count === 1) return 'getting_started'
  if (average === null || !Number.isFinite(average)) return 'getting_started'
  return average < PASSING_SCORE ? 'needs_improvement' : 'on_track'
}

const readinessLabel = (user) => readinessLabels[getReadinessStatus(user)]
const readinessDescription = (user) => readinessDescriptions[getReadinessStatus(user)]

const formatAverage = (value) => {
  if (value === null || value === undefined || value === '') return '—'
  const number = Number(value)
  return Number.isFinite(number) ? `${number.toFixed(1)}%` : '—'
}

const formatDate = (value) => {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '—'
  return new Intl.DateTimeFormat('en', { day: '2-digit', month: 'short', year: 'numeric' }).format(date)
}

const statusCounts = computed(() => {
  const counts = { on_track: 0, needs_improvement: 0, getting_started: 0, not_started: 0 }
  for (const user of users.value) counts[getReadinessStatus(user)]++
  return counts
})

const overviewCards = computed(() => [
  { label: 'Total students', value: users.value.length, icon: 'fa-solid fa-users', tone: 'icon-neutral' },
  { label: 'On track', value: statusCounts.value.on_track, icon: 'fa-solid fa-arrow-trend-up', tone: 'icon-track' },
  { label: 'Needs improvement', value: statusCounts.value.needs_improvement, icon: 'fa-solid fa-arrows-rotate', tone: 'icon-improvement' },
  { label: 'Getting started', value: statusCounts.value.getting_started, icon: 'fa-solid fa-shoe-prints', tone: 'icon-started' },
  { label: 'Not started', value: statusCounts.value.not_started, icon: 'fa-regular fa-clock', tone: 'icon-pending' },
])

const fetchUsers = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await api.get('/api/admin/user')
    users.value = Array.isArray(response.data?.data) ? response.data.data : []
  } catch (error) {
    console.error('Error fetching users:', error)
    errorMessage.value = error.response?.data?.message || 'Unable to load student accounts. Please try again.'
  } finally {
    loading.value = false
  }
}

// Opens the records page filtered to this learner.
const viewRecords = (user) => {
  router.push({ path: '/admin/records', query: { user_id: user.id } })
}

const showToast = (message, type = 'success') => {
  notification.value = { message, type }
  window.setTimeout(() => {
    if (notification.value.message === message) notification.value.message = ''
  }, 3000)
}

/* =========================================================
   EDIT STUDENT
   The modal emits the form payload. This page sends the request,
   keeps the modal open on errors, and refreshes the list on success.
========================================================= */

const openEdit = (user) => {
  selectedUser.value = user
  saveError.value = ''
  saveErrors.value = {}
  openEditModal.value = true
}

const handleSave = async (payload) => {
  if (!selectedUser.value || saving.value) return

  saving.value = true
  saveError.value = ''
  saveErrors.value = {}

  try {
    await api.put(`/api/admin/user/update/${selectedUser.value.id}`, payload)

    openEditModal.value = false
    await fetchUsers()
    showToast('Student details updated.')
  } catch (error) {
    if (error.response?.status === 422) {
      saveErrors.value = error.response.data?.errors ?? {}
      saveError.value = error.response.data?.message || 'Please check the highlighted fields.'
    } else {
      console.error('Update user failed:', error)
      saveError.value = 'Could not save the changes. Please try again.'
    }
  } finally {
    saving.value = false
  }
}

/* =========================================================
   DELETE STUDENT
========================================================= */

const openDeleteConfirmation = (user) => {
  userToDelete.value = user
}

const closeDeleteConfirmation = () => {
  if (deleting.value) return
  userToDelete.value = null
}

const deleteUser = async () => {
  if (!userToDelete.value || deleting.value) return
  deleting.value = true

  try {
    const deletedName = fullName(userToDelete.value)
    await api.delete(`/api/admin/user/delete/${userToDelete.value.id}`)
    userToDelete.value = null
    await fetchUsers()
    showToast(`${deletedName} was deleted.`)
  } catch (error) {
    console.error('Delete user failed:', error)
    showToast(error.response?.data?.message || 'Failed to delete the student account.', 'error')
  } finally {
    deleting.value = false
  }
}

/* =========================================================
   FILTER, SORT, PAGINATE
========================================================= */

const filteredUsers = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  const result = users.value.filter((user) => {
    const matchesSearch =
      !query ||
      fullName(user).toLowerCase().includes(query) ||
      String(user.email ?? '').toLowerCase().includes(query) ||
      String(user.id ?? '').includes(query)

    const matchesReadiness =
      !readinessFilter.value || getReadinessStatus(user) === readinessFilter.value

    return matchesSearch && matchesReadiness
  })

  const getSortValue = (user) => {
    switch (sortKey.value) {
      case 'full_name':
        return fullName(user).toLowerCase()
      case 'created_at': {
        const time = new Date(user.created_at).getTime()
        return Number.isFinite(time) ? time : 0
      }
      case 'quizzes_taken':
        return Number(user.quizzes_taken ?? 0)
      case 'average_score':
        return Number(user.average_score ?? -1)
      case 'id':
        return Number(user.id ?? 0)
      default:
        return String(user[sortKey.value] ?? '').toLowerCase()
    }
  }

  return result.sort((a, b) => {
    const left = getSortValue(a)
    const right = getSortValue(b)
    const comparison =
      typeof left === 'string' || typeof right === 'string'
        ? String(left).localeCompare(String(right))
        : Number(left) - Number(right)
    return sortOrder.value === 'asc' ? comparison : -comparison
  })
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredUsers.value.length / perPage.value)))
const pageStart = computed(() => (currentPage.value - 1) * perPage.value)
const paginatedUsers = computed(() =>
  filteredUsers.value.slice(pageStart.value, pageStart.value + perPage.value)
)

const sortBy = (key) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = key === 'created_at' ? 'desc' : 'asc'
  }
  currentPage.value = 1
}

const sortIcon = (key) => {
  if (sortKey.value !== key) return 'fa-solid fa-sort'
  return sortOrder.value === 'asc' ? 'fa-solid fa-sort-up' : 'fa-solid fa-sort-down'
}

const clearFilters = () => {
  searchQuery.value = ''
  readinessFilter.value = ''
  currentPage.value = 1
}

watch([searchQuery, readinessFilter, perPage], () => {
  currentPage.value = 1
})

watch(totalPages, (pages) => {
  if (currentPage.value > pages) currentPage.value = pages
})

onMounted(fetchUsers)
</script>

<style scoped>
*,
*::before,
*::after {
  box-sizing: border-box;
}

.dashboard-wrapper {
  min-height: 100vh;
  padding: 24px;
  background: #f7f8fa;
  color: #202329;
}

.admin-section {
  width: min(100%, 1440px);
  margin: 0 auto;
}

.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 20px;
}

.eyebrow {
  margin: 0 0 6px;
  color: #8b9099;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.12em;
}

.section-title {
  margin: 0;
  color: #202329;
  font-size: 24px;
  font-weight: 720;
  letter-spacing: -0.045em;
}

.section-subtitle {
  max-width: 680px;
  margin: 6px 0 0;
  color: #858b95;
  font-size: 13px;
  line-height: 1.5;
}

.header-tools {
  display: flex;
  align-items: center;
  gap: 9px;
}

.user-count,
.search-wrapper,
.icon-button {
  height: 40px;
  border: 1px solid #e4e7eb;
  border-radius: 8px;
  background: #fff;
}

.user-count {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 0 12px;
  white-space: nowrap;
}

.user-count strong {
  color: #252930;
  font-size: 13px;
}

.user-count span {
  color: #858b95;
  font-size: 11px;
}

.search-wrapper {
  width: 270px;
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 0 12px;
  color: #8b9099;
}

.search-wrapper:focus-within {
  border-color: #aeb5bf;
  box-shadow: 0 0 0 3px rgb(30 41 59 / 5%);
}

.search-input {
  width: 100%;
  min-width: 0;
  border: 0;
  outline: 0;
  background: transparent;
  color: #202329;
  font: inherit;
  font-size: 12px;
}

.search-input::placeholder {
  color: #a1a6ae;
}

.icon-button {
  width: 40px;
  display: grid;
  place-items: center;
  color: #5f6670;
  cursor: pointer;
}

.icon-button:hover {
  background: #f4f5f7;
}

.icon-button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.spinning {
  animation: spin 0.8s linear infinite;
}

/* OVERVIEW */

.overview-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.overview-card {
  min-height: 82px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 15px;
  border: 1px solid #e7e9ed;
  border-radius: 10px;
  background: #fff;
  min-width: 0;
}

.overview-icon {
  width: 38px;
  height: 38px;
  flex: 0 0 38px;
  display: grid;
  place-items: center;
  border-radius: 9px;
  font-size: 14px;
}

.icon-neutral {
  background: #f0f2f5;
  color: #59616c;
}

.icon-track {
  background: #edf7f1;
  color: #357b57;
}

.icon-improvement {
  background: #fff1ef;
  color: #b65345;
}

.icon-started {
  background: #eef4ff;
  color: #496da8;
}

.icon-pending {
  background: #fff6e9;
  color: #a66b1e;
}

.overview-card>div {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.overview-label {
  color: #858b95;
  font-size: 11px;
  line-height: 1.3;
}

.overview-card strong {
  color: #252930;
  font-size: 20px;
  line-height: 1;
}

/* ALERTS */

.inline-alert {
  display: flex;
  align-items: center;
  gap: 9px;
  margin-bottom: 14px;
  padding: 11px 13px;
  border: 1px solid #dcece2;
  border-radius: 8px;
  background: #f3faf5;
  color: #347650;
  font-size: 12px;
}

.inline-alert>span {
  flex: 1;
}

.inline-alert>button {
  border: 0;
  background: transparent;
  color: inherit;
  cursor: pointer;
}

.error-alert {
  border-color: #f0dada;
  background: #fff6f6;
  color: #a44747;
}

.retry-button {
  padding: 5px 9px;
  border: 1px solid #e9caca;
  border-radius: 6px;
  background: #fff;
  color: inherit;
  font: inherit;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
}

/* TABLE CARD */

.table-card {
  overflow: hidden;
  border: 1px solid #e6e8ec;
  border-radius: 10px;
  background: #fff;
}

.table-toolbar {
  min-height: 68px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px 16px;
  padding: 12px 16px;
  border-bottom: 1px solid #eceef1;
}

.table-toolbar h2 {
  margin: 0;
  color: #292d33;
  font-size: 13px;
  font-weight: 700;
}

.table-toolbar p {
  margin: 5px 0 0;
  color: #8b9099;
  font-size: 11px;
}

.toolbar-controls {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 14px;
}

.readiness-filter,
.page-size {
  display: flex;
  align-items: center;
  gap: 7px;
  color: #858b95;
  font-size: 11px;
}

.readiness-filter select,
.page-size select {
  height: 32px;
  padding: 0 9px;
  border: 1px solid #e2e5e9;
  border-radius: 7px;
  outline: none;
  background: #fff;
  color: #3b4149;
  font: inherit;
  font-size: 11px;
}

.readiness-filter select:focus,
.page-size select:focus {
  border-color: #aeb5bf;
}

.page-size select {
  width: 62px;
}

/* DESKTOP TABLE */

.table-scroll {
  width: 100%;
  overflow-x: auto;
}

.users-table {
  width: 100%;
  min-width: 930px;
  border-collapse: collapse;
  table-layout: fixed;
}

.users-table th {
  height: 42px;
  padding: 0 13px;
  border-bottom: 1px solid #eceef1;
  background: #fafbfc;
  color: #7e848e;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.055em;
  text-align: left;
  text-transform: uppercase;
  white-space: nowrap;
}

.users-table th button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  letter-spacing: inherit;
  text-transform: inherit;
  cursor: pointer;
}

.users-table th button:hover {
  color: #252930;
}

.users-table th button i {
  font-size: 9px;
}

.users-table th:nth-child(1) {
  width: 75px;
}

.users-table th:nth-child(2) {
  width: 22%;
}

.users-table th:nth-child(3) {
  width: 24%;
}

.users-table th:nth-child(4) {
  width: 13%;
}

.users-table th:nth-child(5) {
  width: 17%;
}

.users-table th:nth-child(6) {
  width: 12%;
}

.users-table th:nth-child(7) {
  width: 120px;
}

.users-table td {
  height: 68px;
  padding: 10px 13px;
  border-bottom: 1px solid #f0f1f3;
  color: #343941;
  font-size: 12px;
  vertical-align: middle;
}

.users-table tbody tr:last-child td {
  border-bottom: 0;
}

.users-table tbody tr:hover {
  background: #fcfcfd;
}

.center {
  text-align: center !important;
}

.id-value {
  color: #858b95;
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: 11px;
}

.student-cell {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.student-avatar {
  width: 36px;
  height: 36px;
  flex: 0 0 36px;
  border: 1px solid #e5e7eb;
  border-radius: 50%;
  background: #f4f5f6;
  object-fit: cover;
}

.student-copy {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.student-copy strong {
  overflow: hidden;
  color: #292d33;
  font-size: 11px;
  font-weight: 650;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.student-copy span {
  color: #9297a0;
  font-size: 10px;
}

.email-value {
  display: block;
  overflow: hidden;
  color: #626873;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.assessment-count {
  color: #343941;
  font-size: 12px;
  font-weight: 700;
}

.assessment-caption {
  display: block;
  margin-top: 3px;
  color: #969ba3;
  font-size: 9px;
}

.readiness-badge {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 5px 8px;
  border: 1px solid transparent;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 650;
  white-space: nowrap;
}

.readiness-on_track {
  border-color: #dceee3;
  background: #f0f8f3;
  color: #347650;
}

.readiness-needs_improvement {
  border-color: #f0d9d5;
  background: #fff4f2;
  color: #a94c40;
}

.readiness-getting_started {
  border-color: #dce6f5;
  background: #f1f6ff;
  color: #496da8;
}

.readiness-not_started {
  border-color: #e6e8ec;
  background: #f5f6f8;
  color: #727984;
}

.readiness-dot {
  width: 6px;
  height: 6px;
  flex: 0 0 6px;
  border-radius: 50%;
  background: currentColor;
}

.average-score {
  display: block;
  margin-top: 5px;
  color: #858b95;
  font-size: 9px;
}

.date-value {
  color: #707680;
  font-size: 11px;
  white-space: nowrap;
}

.actions-heading {
  text-align: center !important;
}

.row-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.action-button {
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  border: 1px solid #e3e5e9;
  border-radius: 7px;
  background: #fff;
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.action-button i {
  font-size: 11px;
}

.view-action {
  color: #496da8;
}

.view-action:hover {
  border-color: #c9d6ee;
  background: #f1f6ff;
  color: #2f4f85;
}

.edit-action {
  color: #626873;
}

.edit-action:hover {
  border-color: #c9cdd3;
  background: #f4f5f7;
  color: #252930;
}

.delete-action {
  color: #a24d4d;
}

.delete-action:hover {
  border-color: #edcece;
  background: #fff3f3;
  color: #a13d3d;
}

/* MOBILE CARDS (hidden on desktop) */

.student-cards {
  display: none;
}

.student-card {
  padding: 13px 14px;
  border-bottom: 1px solid #f0f1f3;
}

.student-card:last-child {
  border-bottom: 0;
}

.student-card-head {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.student-card-head .student-copy {
  flex: 1;
}

.student-card-head .email-value {
  font-size: 10px;
  color: #858b95;
}

.student-card-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px 14px;
  margin: 10px 0;
  color: #858b95;
  font-size: 10px;
}

.student-card-meta strong {
  color: #343941;
}

.card-actions {
  justify-content: flex-end;
}

.cards-empty {
  padding: 24px;
  color: #858b95;
  font-size: 12px;
  text-align: center;
}

/* FOOTER */

.table-footer {
  min-height: 54px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding: 9px 15px;
  border-top: 1px solid #eceef1;
}

.pagination-summary {
  color: #858b95;
  font-size: 10px;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #858b95;
  font-size: 10px;
}

.pagination-controls strong {
  color: #343941;
}

.pagination-controls button {
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  border: 1px solid #e2e5e9;
  border-radius: 7px;
  background: #fff;
  color: #59616b;
  cursor: pointer;
}

.pagination-controls button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.pagination-controls button:hover:not(:disabled) {
  background: #f5f6f7;
}

/* EMPTY / LOADING */

.empty-state {
  min-height: 230px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 7px;
  text-align: center;
}

.empty-icon {
  width: 40px;
  height: 40px;
  display: grid;
  place-items: center;
  margin-bottom: 3px;
  border-radius: 11px;
  background: #f3f4f6;
  color: #858b95;
  font-size: 16px;
}

.empty-state strong {
  color: #343941;
  font-size: 12px;
}

.empty-state>span:last-of-type {
  color: #858b95;
  font-size: 11px;
}

.clear-search-button {
  margin-top: 5px;
  padding: 6px 10px;
  border: 1px solid #e1e4e8;
  border-radius: 6px;
  background: #fff;
  color: #4e555f;
  font: inherit;
  font-size: 10px;
  cursor: pointer;
}

.loading-card {
  min-height: 280px;
}

.loading-state {
  min-height: 280px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #858b95;
}

.loading-state strong {
  color: #454b54;
  font-size: 12px;
}

.loading-state>span:last-child {
  font-size: 11px;
}

.spinner,
.button-spinner {
  width: 25px;
  height: 25px;
  border: 2px solid #e5e7eb;
  border-top-color: #515862;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  transform: translateY(-4px);
  opacity: 0;
}

/* DELETE MODAL */

.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 18px;
  background: rgb(19 22 27 / 48%);
  backdrop-filter: blur(2px);
}

.confirm-modal {
  position: relative;
  width: min(440px, 100%);
  padding: 26px;
  border: 1px solid #e8eaee;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 24px 80px rgb(0 0 0 / 18%);
  text-align: center;
}

.confirm-icon {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  margin: 0 auto 15px;
  border-radius: 14px;
  background: #fff1f1;
  color: #b34c4c;
  font-size: 17px;
}

.confirm-close {
  position: absolute;
  top: 13px;
  right: 13px;
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: #858b95;
  cursor: pointer;
}

.confirm-close:hover {
  background: #f4f5f7;
  color: #343941;
}

.confirm-close:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.confirm-eyebrow {
  margin: 0 0 6px;
  color: #a64b4b;
  font-size: 9px;
  font-weight: 750;
  letter-spacing: 0.12em;
}

.confirm-modal h2 {
  margin: 0;
  color: #252930;
  font-size: 20px;
  letter-spacing: -0.035em;
}

.confirm-copy {
  margin: 10px 0 0;
  color: #737984;
  font-size: 12px;
  line-height: 1.6;
  overflow-wrap: anywhere;
}

.confirm-copy strong {
  color: #343941;
}

.confirm-warning {
  display: flex;
  align-items: flex-start;
  gap: 9px;
  margin-top: 18px;
  padding: 11px;
  border: 1px solid #f1e6d3;
  border-radius: 8px;
  background: #fffaf1;
  color: #8c6a36;
  font-size: 10px;
  line-height: 1.5;
  text-align: left;
}

.confirm-warning i {
  margin-top: 1px;
}

.confirm-actions {
  display: flex;
  justify-content: center;
  gap: 9px;
  margin-top: 22px;
}

.cancel-button,
.confirm-delete-button {
  min-height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 14px;
  border-radius: 7px;
  font: inherit;
  font-size: 11px;
  font-weight: 650;
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease;
}

.cancel-button {
  border: 1px solid #e0e3e7;
  background: #fff;
  color: #4f5660;
}

.cancel-button:hover {
  background: #f5f6f7;
}

.confirm-delete-button {
  border: 1px solid #a94343;
  background: #ad4848;
  color: #fff;
}

.confirm-delete-button:hover:not(:disabled) {
  border-color: #963a3a;
  background: #963a3a;
}

.cancel-button:disabled,
.confirm-delete-button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.button-spinner {
  width: 13px;
  height: 13px;
  border-width: 2px;
  border-color: rgb(255 255 255 / 40%);
  border-top-color: #fff;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* RESPONSIVE */

@media (max-width: 1280px) {
  .overview-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 1024px) {

  .users-table th:nth-child(3),
  .users-table td:nth-child(3) {
    display: none;
  }

  .users-table {
    min-width: 780px;
  }
}

@media (max-width: 900px) {
  .page-header {
    align-items: stretch;
    flex-direction: column;
  }

  .header-tools {
    width: 100%;
  }

  .search-wrapper {
    flex: 1;
    width: auto;
  }
}

/* Switch to cards below 760px */
@media (max-width: 760px) {
  .table-scroll {
    display: none;
  }

  .student-cards {
    display: block;
  }
}

@media (max-width: 650px) {
  .dashboard-wrapper {
    padding: 14px;
  }

  .overview-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
  }

  .overview-card {
    min-height: 74px;
    gap: 8px;
    padding: 10px;
  }

  .overview-icon {
    width: 32px;
    height: 32px;
    flex-basis: 32px;
    font-size: 12px;
  }

  .overview-label {
    font-size: 10px;
  }

  .overview-card strong {
    font-size: 17px;
  }

  .toolbar-controls {
    width: 100%;
    justify-content: space-between;
  }
}

@media (max-width: 420px) {
  .dashboard-wrapper {
    padding: 10px;
  }

  .section-title {
    font-size: 21px;
  }

  .header-tools {
    flex-wrap: wrap;
  }

  .user-count {
    flex: 1;
    justify-content: center;
  }

  .search-wrapper {
    order: 3;
    flex-basis: 100%;
  }

  .icon-button {
    flex-shrink: 0;
  }

  .table-footer {
    align-items: flex-start;
    flex-direction: column;
  }

  .confirm-modal {
    padding: 22px 18px;
  }

  .confirm-actions {
    flex-direction: column-reverse;
  }

  .cancel-button,
  .confirm-delete-button {
    width: 100%;
  }
}
</style>