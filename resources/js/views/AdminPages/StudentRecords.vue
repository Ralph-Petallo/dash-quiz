<template>
  <div class="page-wrapper">
    <section class="admin-section">

      <!-- Header -->
      <div class="header-row">
        <div>
          <h3 class="section-title">
            <i class="fa-solid fa-chart-line"></i>
            Dasher Records
          </h3>
          <p class="section-subtitle">
            Viewing all completed quiz attempts
          </p>
        </div>

        <div class="search-wrap">
          <i class="fas fa-search"></i>
          <input type="text" v-model="searchQuery" placeholder="Search..." class="search-input" />
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="table-card">
        <div class="loading-state">
          <div class="spinner"></div>
          <p>Loading records...</p>
        </div>
      </div>

      <!-- Table -->
      <div v-else class="table-card">

        <!-- Table Header -->
        <div class="table-header">
          <span @click="sortBy('user_name')">
            <i class="fas fa-user"></i> Dasher
          </span>


          <span @click="sortBy('quiz_title')">
            <i class="fas fa-book"></i> Quiz
          </span>

          <span @click="sortBy('score')">
            <i class="fas fa-star"></i> Score
          </span>

          <span>
            <i class="fas fa-user"></i> Status
          </span>

          <span @click="sortBy('created_at')">
            <i class="fas fa-calendar"></i> Date
          </span>

        </div>

        <!-- Rows -->
        <div v-if="paginatedRecords.length">
          <div v-for="rec in paginatedRecords" :key="rec.id" class="table-row">

            <span class="user">
              <img :src="profileImageUrl(rec.user)" :alt="rec.user?.first_name + ' ' + rec.user?.last_name"
                class="user-avatar" />
              {{ rec.user ? rec.user.first_name + ' ' + rec.user.last_name : 'User #' + rec.user_id }}
            </span>

            <span class="quiz">
              {{ rec.quiz ? rec.quiz.title : 'Deleted Quiz' }}
            </span>

            <span>
              <span class="score-badge" :class="getScoreClass(rec)">
                <i class="fas fa-chart-line"></i>
                {{ rec.score }} / {{ rec.total_questions || 10 }}
              </span>
            </span>
            <span :class="getStatus(status = false)">
              {{ rec.status || 'Not Ready' }}
            </span>
            <span class="date">
              {{ formatDate(rec.created_at) }}
            </span>

          </div>
        </div>

        <!-- Empty -->
        <div v-else class="empty-state">
          <i class="fas fa-inbox"></i>
          <p>No records found.</p>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination">
          <button :disabled="currentPage === 1" @click="currentPage--">
            <i class="fas fa-chevron-left"></i> Prev
          </button>

          <div class="number">Page {{ currentPage }} / {{ totalPages }}</div>

          <button :disabled="currentPage === totalPages" @click="currentPage++">
            Next <i class="fas fa-chevron-right"></i>
          </button>
        </div>

      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'

const records = ref([])
let loading = ref(false)

const currentPage = ref(1)
const perPage = 10
const sortKey = ref('id')
const sortOrder = ref('asc')
const searchQuery = ref('')

const fetchRecords = async () => {
  if (loading.value) return

  try {
    loading.value = true
    const { data } = await axios.get('/api/admin/records')
    records.value = data.data
    console.log('Fetched records:', records.value)
  } catch (error) {
    console.log(error)
  } finally {
    loading.value = false
  }
}

const getStatus = (status) => {
  if (!status) {
    return 'status-not-ready'
  }
  return 'status-ready'
}

const profileImageUrl = (user) => {
  const profilepath = `/storage/images/profiles`
  return user?.profile_photo
    ? `${profilepath}/${user.profile_photo}`
    : `${profilepath}/default.png`
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    day: '2-digit', month: '2-digit', year: '2-digit'
  }).replace(/ /g, '/')
}

const getScoreClass = (rec) => {
  if (!rec.total_questions) return 'neutral'
  const percent = (rec.score / rec.total_questions) * 100
  if (percent >= 80) return 'score-high'
  if (percent >= 50) return 'score-mid'
  return 'score-low'
}

const mappedRecords = computed(() =>
  records.value.map(r => ({
    ...r,
    user_name: r.user ? r.user.first_name + ' ' + r.user.last_name : '',
    quiz_title: r.quiz ? r.quiz.title : ''
  }))
)

const filteredRecords = computed(() => {
  let filtered = mappedRecords.value.filter(r =>
    r.user_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    r.quiz_title.toLowerCase().includes(searchQuery.value.toLowerCase())
  )

  filtered.sort((a, b) => {
    let A = a[sortKey.value] || ''
    let B = b[sortKey.value] || ''

    if (sortKey.value === 'created_at') {
      A = new Date(A)
      B = new Date(B)
    }

    if (sortKey.value === 'score') {
      A = A || 0
      B = B || 0
    }

    return sortOrder.value === 'asc'
      ? A > B ? 1 : -1
      : A < B ? 1 : -1
  })

  return filtered
})

const totalPages = computed(() =>
  Math.ceil(filteredRecords.value.length / perPage)
)

const paginatedRecords = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return filteredRecords.value.slice(start, start + perPage)
})

watch([filteredRecords, currentPage], () => {
  if (currentPage.value > totalPages.value) {
    currentPage.value = totalPages.value || 1
  }
})

const sortBy = (key) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = 'asc'
  }
}

onMounted(fetchRecords)
</script>

<style scoped>
/* WRAPPER */
.page-wrapper {
  padding: clamp(1rem, 2vw, 1.75rem);
  background: #f8fafc;
  min-height: 100vh;
  display: flex;
  justify-content: center;
}

.admin-section {
  width: 100%;
  max-width: 1100px;
}

/* HEADER */
.header-row {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.2rem;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1.5px solid #6366f1;
  object-fit: cover;
}

.section-title {
  font-size: clamp(1.1rem, 1.5vw, 1.3rem);
  font-weight: 600;
  color: #1e1b4b;
  display: flex;
  align-items: center;
  gap: 6px;
}

.section-subtitle {
  font-size: 0.85rem;
  color: #64748b;
}

/* SEARCH */
.search-wrap {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #fff;
  padding: 6px 10px;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
}

.search-input {
  border: none;
  outline: none;
  font-size: 0.85rem;
}

/* CARD */
.table-card {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

/* HEADER */
.table-header {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
  padding: 12px 16px;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  background: #f9fafb;
}

.table-header span {
  cursor: pointer;
  text-align: center;
  gap: 6px;
}

/* ROW */
.table-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
  padding: 12px 16px;
  align-items: center;
  border-top: 1px solid #f1f5f9;
  transition: 0.2s;
}

.table-row span {
  text-align: center;
}

.table-row:hover {
  background: #f9fafb;
}

/* CELLS */
.user {
  display: flex;
  align-items: flex-start;
  align-items: center;
  gap: 6px;
  font-size: 0.85rem;
}

.quiz{
  font-size: 0.85rem;
}


.quiz {
  color: #4f46e5;
}

.status-ready {
  color: green;
}

.status-not-ready {
  color: brown;
}

.date {
  color: #6b7280;
}

/* SCORE */
.score-badge {
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 0.75rem;
  display: inline-flex;
  gap: 5px;
  align-items: center;
}

.score-high {
  background: #dcfce7;
  color: #166534;
}

.score-mid {
  background: #fef9c3;
  color: #854d0e;
}

.score-low {
  background: #fee2e2;
  color: #991b1b;
}

.neutral {
  background: #f1f5f9;
}

/* EMPTY */
.empty-state {
  text-align: center;
  padding: 2rem;
  color: #94a3b8;
}

/* LOADING */
.loading-state {
  text-align: center;
  padding: 3rem;
}

.spinner {
  width: 35px;
  height: 35px;
  border: 3px solid #e0e7ff;
  border-top: 3px solid #6366f1;
  border-radius: 50%;
  margin: auto;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* PAGINATION */
.pagination {
  display: flex;
  justify-content: center;
  gap: 10px;
  padding: 14px;
  flex-wrap: wrap;
}

.pagination .number {
  display: flex;
  align-items: center;
  font-size: 12px;
}

.pagination button {
  padding: 6px 12px;
  border: 1px solid #e5e7eb;
  background: white;
  border-radius: 8px;
  cursor: pointer;
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .table-header {
    display: none;
  }

  .table-row {
    grid-template-columns: 1fr;
    gap: 6px;
  }
}
</style>