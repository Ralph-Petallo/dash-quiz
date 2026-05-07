<template>
  <div class="quiz-result-page">

    <!-- Top Bar -->
    <header class="top-bar">
      <div class="nav-content">
        <div class="brand">
          <span class="brand-text">Assessment Complete</span>
        </div>

        <router-link to="/profile" class="profile-link">
          <img :src="profileImageUrl" alt="Profile" class="user-avatar" />
        </router-link>
      </div>
    </header>

    <!-- Main -->
    <main class="container">

      <!-- Loading -->
      <div v-if="loading" class="result-card">
        <p>Loading result...</p>
      </div>

      <!-- Result -->
      <div v-else-if="record" class="result-card">

        <div class="celebration-icon">🎉</div>

        <!-- SCORE -->
        <div class="score-summary">
          <h2 class="score-number">
            {{ record.score }} / {{ record.total_questions }}
          </h2>

          <p class="score-text">
            You completed this quiz
          </p>

          <!-- TIME -->
          <p class="time-text">
            ⏱ Time: {{ formatTime(record.elapsed_time) }}
          </p>
        </div>

        <!-- FEEDBACK -->
        <div class="feedback-msg">
          <p v-if="record.score >= record.total_questions * 0.75">
            Excellent work! You have a solid grasp of this topic.
          </p>

          <p v-else-if="record.score >= record.total_questions * 0.5">
            Good effort! Keep practicing and you'll improve more.
          </p>

          <p v-else>
            Keep practicing. Consistency is the key to mastery!
          </p>
        </div>

        <!-- ACTIONS -->
        <div class="action-grid">
          <router-link to="/user/quizzes" class="btn-primary">
            Go to Dashboard
          </router-link>

          <button @click="reTake" class="btn-outline">
            Try Again
          </button>
        </div>

      </div>

    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useUser } from '@/composables/useUser'

const route = useRoute()
const router = useRouter()
const { userAvatar, fetchUser } = useUser()

const record = ref(null)
const loading = ref(true)

const profileImageUrl = computed(() => userAvatar.value)

onMounted(async () => {
  await fetchUser()

  try {
    const recordId = route.params.id

    const { data } = await axios.get(`/api/quiz/result/${recordId}`)

    record.value = {
      id: data.record_id,
      score: data.score,
      total_questions: data.questions?.length || 0,
      elapsed_time: data.elapsed_time || 0,
      quiz_id: data.quiz_id || null
    }

  } catch (err) {
    console.error('Failed to load quiz result:', err)
  } finally {
    loading.value = false
  }
})

const formatTime = (sec) => {
  if (!sec && sec !== 0) return '00:00'

  const m = Math.floor(sec / 60)
  const s = sec % 60
  return `${m}:${s.toString().padStart(2, '0')}`
}

const reTake = () => {
  if (!record.value?.quiz_id) return
  router.replace(`/quiz/${record.value.quiz_id}`)
}
</script>

<style scoped>
.quiz-result-page {
  min-height: 100vh;
  background: #f8fafc;
  font-family: "Inter", sans-serif;
  display: flex;
  flex-direction: column;
}

/* TOP BAR */
.top-bar {
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  padding: 0.75rem 0;
}

.nav-content {
  max-width: 800px;
  margin: 0 auto;
  padding: 0 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.brand-text {
  font-weight: 700;
  font-size: 0.9rem;
  color: #1e293b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 2px solid #6366f1;
  object-fit: cover;
}

/* MAIN */
.container {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem 1rem;
}

/* CARD */
.result-card {
  background: #fff;
  border-radius: 20px;
  padding: 3rem 2rem;
  text-align: center;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
  max-width: 420px;
  width: 100%;
  border: 1px solid #f1f5f9;
}

.celebration-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

/* SCORE */
.score-number {
  font-size: 2.5rem;
  font-weight: 800;
  color: #1e293b;
  margin: 0;
}

.score-text {
  color: #64748b;
  margin-top: 0.5rem;
}

/* TIME */
.time-text {
  margin-top: 0.5rem;
  font-size: 0.9rem;
  color: #6366f1;
  font-weight: 600;
}

/* FEEDBACK */
.feedback-msg {
  margin: 2rem 0;
  color: #64748b;
  font-style: italic;
  font-size: 0.95rem;
}

/* ACTIONS */
.action-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.btn-primary {
  background: #1e293b;
  color: #fff;
  text-decoration: none;
  padding: 1rem;
  border-radius: 12px;
  font-weight: 700;
  transition: 0.2s;
}

.btn-outline {
  background: transparent;
  border: 2px solid #e2e8f0;
  color: #64748b;
  padding: 1rem;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: 0.2s;
}

.btn-primary:hover,
.btn-outline:hover {
  transform: translateY(-2px);
}

.btn-outline:hover {
  border-color: #6366f1;
  color: #6366f1;
}
</style>