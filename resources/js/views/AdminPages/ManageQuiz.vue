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
            <span class="title-icon"><i class="fa-solid fa-layer-group"></i></span>
            Manage Quizzes
          </h3>
          <p class="section-subtitle">{{ quizzes.length }} quiz{{ quizzes.length === 1 ? '' : 'zes' }} total</p>
        </div>

        <div class="header-actions">
          <div class="sort-group">
            <button v-for="opt in sortOptions" :key="opt.key" class="sort-chip" :class="{ active: sortKey === opt.key }"
              @click="sortBy(opt.key)">
              {{ opt.label }}
              <i v-if="sortKey === opt.key" class="fas"
                :class="sortOrder === 'asc' ? 'fa-arrow-up-short-wide' : 'fa-arrow-down-wide-short'"></i>
            </button>
          </div>

          <button @click="goToAddQuiz" class="add-btn">
            <i class="fas fa-plus"></i> New Quiz
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading quizzes…</p>
      </div>

      <!-- GRID -->
      <div v-else-if="sortedQuizzes.length" class="quiz-grid">
        <article v-for="quiz in sortedQuizzes" :key="quiz.id" class="quiz-card">

          <header class="card-top">
            <div class="card-icon" :class="difficultyClass(quiz.difficulty)">
              <i class="fa-solid fa-brain"></i>
            </div>

            <div class="card-heading">
              <h4 class="card-title">{{ quiz.title }}</h4>
              <span class="id-tag">#{{ quiz.id }}</span>
            </div>


          </header>

          <p class="card-desc">{{ quiz.description || 'No description provided.' }}</p>

          <footer class="card-actions">
            <div>
              <span class="difficulty-badge" :class="difficultyClass(quiz.difficulty)">
                {{ quiz.difficulty || 'N/A' }}
              </span>

            </div>

            <div class="action-btn">
              <button class="icon-btn edit" title="Edit quiz" aria-label="Edit quiz" @click="goToEditQuiz(quiz.id)">
                <i class="fas fa-pen"></i>
              </button>

              <button class="icon-btn delete" title="Delete quiz" aria-label="Delete quiz"
                @click="deleteQuiz(quiz.id, quiz.title)">
                <i class="fas fa-trash"></i>
              </button>
            </div>

          </footer>
        </article>
      </div>

      <!-- EMPTY -->
      <div v-else class="empty-state">
        <i class="fas fa-inbox"></i>
        <p>No quizzes yet.</p>
        <button @click="goToAddQuiz" class="add-btn subtle">
          <i class="fas fa-plus"></i> Create your first quiz
        </button>
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

const sortOptions = [
  { key: 'id', label: 'ID' },
  { key: 'title', label: 'Title' },
  { key: 'difficulty', label: 'Difficulty' },
]

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

const difficultyClass = (difficulty) => {
  const d = (difficulty || '').toLowerCase()
  if (d === 'easy') return 'level-easy'
  if (d === 'medium') return 'level-medium'
  if (d === 'hard') return 'level-hard'
  return 'level-default'
}

const goToAddQuiz = () => router.push('/admin/quizzes/create')
const goToEditQuiz = (id) => router.push(`/admin/quizzes/${id}/edit`)

const deleteQuiz = async (id, title) => {
  if (!confirm(`Are you sure you want to delete "${title}"?`)) return

  try {
    await axios.delete(`/api/admin/quizzes/${id}`)
    successMessage.value = 'Quiz deleted successfully!'
    await fetchQuizzes()
    setTimeout(() => successMessage.value = '', 3000)
  } catch {
    alert('Error deleting quiz.')
  }
}

onMounted(fetchQuizzes)
</script>

<style scoped>
:root {
  
}

* {
  box-sizing: border-box;
  --ink: #16162a;
    --muted: #71718a;
    --line: #eceef4;
    --surface: #ffffff;
    --bg: #f6f7fb;
    --accent: #5b5bd6;
    --accent-soft: #eeeeff;
}


.page-wrapper {
  padding: clamp(1.25rem, 3vw, 2.5rem);
  background: var(--bg);
  min-height: 100vh;
  display: flex;
  justify-content: center;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.admin-section {
  width: 100%;
  max-width: 1180px;
}

/* HEADER */
.header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.75rem;
}

.section-title {
  font-size: clamp(1.2rem, 1.6vw, 1.45rem);
  font-weight: 700;
  color: var(--ink);
  display: flex;
  align-items: center;
  gap: 10px;
  letter-spacing: -0.01em;
}

.title-icon {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: linear-gradient(135deg, var(--accent), #8686e8);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  flex-shrink: 0;
}

.section-subtitle {
  font-size: 13px;
  color: var(--muted);
  margin-top: 4px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.sort-group {
  display: flex;
  gap: 6px;
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 4px;
}

.sort-chip {
  border: none;
  background: transparent;
  color: var(--muted);
  font-size: 0.75rem;
  font-weight: 600;
  padding: 6px 10px;
  border-radius: 7px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: 0.15s;
}

.sort-chip:hover {
  color: var(--ink);
}

.sort-chip.active {
  background: var(--accent-soft);
  color: var(--accent);
}

/* BUTTON */
.add-btn {
  background: var(--accent);
  color: white;
  border: none;
  padding: 9px 16px;
  border-radius: 10px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 7px;
  transition: 0.2s;
  white-space: nowrap;
}

.add-btn:hover {
  background: #4a4ac4;
  transform: translateY(-1px);
}

.add-btn.subtle {
  margin: 1rem auto 0;
}

/* ALERT */
.alert-success {
  background: #ecfdf5;
  color: #0f7a5f;
  padding: 11px 16px;
  border-radius: 10px;
  margin-bottom: 1.25rem;
  font-size: 0.85rem;
  display: flex;
  gap: 8px;
  align-items: center;
  border: 1px solid #b8f0da;
}

/* GRID */
.quiz-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

/* CARD */
.quiz-card {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: 16px;
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  transition: 0.2s ease;
}

.quiz-card:hover {
  border-color: #dcdfec;
  box-shadow: 0 8px 24px -12px rgba(30, 27, 75, 0.15);
  transform: translateY(-2px);
}

.card-top {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.card-icon {
  width: 38px;
  height: 38px;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  flex-shrink: 0;
}


.action-btn {
  display: flex;
  width:70px;
  justify-content: space-between;
}

.card-heading {
  flex: 1;
  min-width: 0;
}

.card-title {
  font-size: 0.75rem;
  font-weight: 650;
  color: var(--ink);
  margin: 0;
  overflow: hidden;
}

.id-tag {
  font-size: 0.68rem;
  color: var(--muted);
  font-weight: 600;
}

.difficulty-badge {
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: capitalize;
  padding: 4px 9px;
  border-radius: 999px;
  flex-shrink: 0;
}

.card-desc {
  font-size: 0.8rem;
  color: var(--muted);
  line-height: 1.5;
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  flex: 1;
}

/* LEVEL COLORS */
.level-easy {
  background: #e8faf0;
  color: #12805a;
}

.level-medium {
  background: #fff6e0;
  color: #b3760a;
}

.level-hard {
  background: #feecec;
  color: #d1372e;
}

.level-default {
  background: #f1f2f7;
  color: var(--muted);
}

.card-icon.level-easy {
  background: #e8faf0;
  color: #12805a;
}

.card-icon.level-medium {
  background: #fff6e0;
  color: #b3760a;
}

.card-icon.level-hard {
  background: #feecec;
  color: #d1372e;
}

.card-icon.level-default {
  background: var(--accent-soft);
  color: var(--accent);
}

/* ACTIONS */
.card-actions {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  padding-top: 10px;
  border-top: 1px solid var(--line);
}

.icon-btn {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  transition: 0.15s;
}

.icon-btn.edit {
  background: var(--accent-soft);
  color: var(--accent);
}

.icon-btn.edit:hover {
  background: var(--accent);
  color: white;
}

.icon-btn.delete {
  background: #feecec;
  color: #d1372e;
}

.icon-btn.delete:hover {
  background: #d1372e;
  color: white;
}

/* EMPTY */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: var(--muted);
  background: var(--surface);
  border: 1px dashed var(--line);
  border-radius: 16px;
}

.empty-state i {
  font-size: 1.8rem;
  margin-bottom: 10px;
  opacity: 0.6;
}

/* LOADING */
.loading-state {
  text-align: center;
  padding: 4rem;
}

.spinner {
  width: 34px;
  height: 34px;
  border: 3px solid var(--accent-soft);
  border-top: 3px solid var(--accent);
  border-radius: 50%;
  margin: auto;
  animation: spin 0.9s linear infinite;
}

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
@media (max-width: 560px) {
  .header-row {
    align-items: stretch;
  }

  .header-actions {
    justify-content: space-between;
  }

  .quiz-grid {
    grid-template-columns: 1fr;
  }
}
</style>