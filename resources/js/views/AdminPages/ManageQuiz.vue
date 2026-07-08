<template>
  <div class="page-wrapper">
    <section class="admin-section">

      <!-- Success Alert -->
      <transition name="fade-slide">
        <div v-if="successMessage" class="alert-success">
          <i class="fas fa-check-circle"></i>
          {{ successMessage }}
        </div>
      </transition>

      <!-- Header -->
      <div class="header-row">
        <div>
          <h3 class="section-title">
            <i class="fa-solid fa-pen-to-square"></i>
            Manage Quizzes
          </h3>
          <p class="section-subtitle">
            Create, edit, or manage quizzes
          </p>
        </div>

        <button @click="goToAddQuiz" class="add-btn">
          <i class="fas fa-plus"></i> New Quiz
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading quizzes...</p>
      </div>

      <!-- TABLE -->
      <div v-else class="table-card">

        <!-- HEADER -->
        <div class="table-header">
          <span @click="sortBy('id')">
            <i class="fas fa-hashtag"></i>
          </span>

          <span @click="sortBy('title')">
            <i class="fas fa-heading"></i> Title
          </span>

          <span>
            <i class="fas fa-align-left"></i> Description
          </span>

          <span>
            <i class="fas fa-cogs"></i> Actions
          </span>
        </div>

        <!-- ROWS -->
        <div v-if="sortedQuizzes.length">
          <div v-for="quiz in sortedQuizzes" :key="quiz.id" class="table-row">
            <span class="id">{{ quiz.id }}
            </span>

            <span class="title">
              {{ quiz.title }}
            </span>

            <span class="desc">
              {{ quiz.description || 'No description' }}
            </span>

            <div class="actions">
              <button @click="goToEditQuiz(quiz.id)" class="btn edit">
                <i class="fas fa-pen"></i> Edit
              </button>

              <button @click="deleteQuiz(quiz.id, quiz.title)" class="btn delete">
                <i class="fas fa-trash"></i> Delete
              </button>
            </div>
          </div>
        </div>

        <!-- EMPTY -->
        <div v-else class="empty-state">
          <i class="fas fa-inbox"></i>
          <p>No quizzes yet.</p>
        </div>

      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const quizzes = ref([])
const loading = ref(true)
const successMessage = ref('')

const sortKey = ref('id')
const sortOrder = ref('asc')

const fetchQuizzes = async (retry = 0) => {
  try {
    const { data } = await axios.get('/api/admin/quizzes')
    quizzes.value = data.data || data
  } catch (e) {
    if (e.response?.status === 429 && retry < 3) {
      setTimeout(() => fetchQuizzes(retry + 1), 1000)
    } else {
      console.error(e)
    }
  } finally {
    loading.value = false
  }
}

const sortedQuizzes = computed(() => {
  return [...quizzes.value].sort((a, b) => {
    let A = a[sortKey.value] || ''
    let B = b[sortKey.value] || ''

    if (typeof A === 'string') A = A.toLowerCase()
    if (typeof B === 'string') B = B.toLowerCase()

    return sortOrder.value === 'asc' ? (A > B ? 1 : -1) : (A < B ? 1 : -1)
  })
})

const sortBy = (key) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = 'asc'
  }
}

const goToAddQuiz = () => router.push('/admin/quizzes/create')
const goToEditQuiz = (id) => router.push(`/admin/quizzes/${id}/edit`)

const deleteQuiz = async (id, title) => {
  if (!confirm(`Are you sure you want to delete "${title}"?`)) return

  try {
    await axios.delete(`/api/admin/quizzes/${id}`)
    successMessage.value = "Quiz deleted successfully!"
    await fetchQuizzes()
    setTimeout(() => successMessage.value = '', 3000)
  } catch {
    alert("Error deleting quiz.")
  }
}

onMounted(fetchQuizzes)
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

.section-title {
  font-size: clamp(1.1rem, 1.5vw, 1.3rem);
  font-weight: 600;
  color: #1e1b4b;
  display: flex;
  align-items: center;
  gap: 5px;
}

.section-subtitle {
  font-size: 12px;
  color: #64748b;
}

/* BUTTON */
.add-btn {
  background: #4f46e5;
  color: white;
  border: none;
  padding: 8px 14px;
  border-radius: 10px;
  font-size: 0.85rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: 0.2s;
}

.add-btn:hover {
  background: #6366f1;
}

/* ALERT */
.alert-success {
  background: #ecfeff;
  color: #0f766e;
  padding: 10px 14px;
  border-radius: 10px;
  margin-bottom: 1rem;
  font-size: 0.85rem;
  display: flex;
  gap: 8px;
  align-items: center;
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
  grid-template-columns: 80px 1.5fr 2fr 160px;
  padding: 12px 16px;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  background: #f9fafb;
}

.table-header span {
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
}

/* ROW */
.table-row {
  display: grid;
  grid-template-columns: 80px .5fr 2fr 160px;
  padding: 12px 16px;
  border-top: 1px solid #f1f5f9;
  align-items: center;
  gap: 5px;
  transition: 0.2s;
}

.table-row:hover {
  background: #f9fafb;
}

/* TEXT */
.id {
  color: #4f46e5;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 6px;
}

.title {
  font-weight: 500;
  display: flex;
  align-items: center;
  font-size: 0.85rem;
  gap: 6px;
}

.desc {
  color: #6b7280;
  font-size: 0.85rem;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

/* ACTIONS */
.actions {
  display: flex;
  gap: 6px;
  justify-content: flex-end;
}

.btn {
  font-size: 0.75rem;
  padding: 6px 10px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 5px;
}

.btn.edit {
  background: #eef2ff;
  color: #4f46e5;
}

.btn.edit:hover {
  background: #4f46e5;
  color: white;
}

.btn.delete {
  background: #fee2e2;
  color: #dc2626;
}

.btn.delete:hover {
  background: #dc2626;
  color: white;
}

/* EMPTY */
.empty-state {
  text-align: center;
  padding: 3rem;
  color: #9ca3af;
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

/* ANIMATION */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.fade-slide-enter-active {
  transition: all 0.25s ease;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(-5px);
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .table-header {
    display: none;
  }

  .table-row {
    grid-template-columns: 1fr;
    gap: 8px;
  }

  .actions {
    justify-content: flex-start;
  }
}
</style>