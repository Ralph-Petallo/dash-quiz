<template>
  <div class="quiz-result-page">

    <!-- Top Bar -->
    <header class="top-bar">
      <div class="nav-content">
        <div class="brand">
          <span class="brand-text">Assessment Complete</span>
        </div>

        <router-link to="/user/profile" class="profile-link">
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


async function displayResult() {

  try {
    const recordId = parseInt(route.params.id)
    console.log(recordId)

    const { data } = await axios.get(`/api/quiz/multi-result/${recordId}`)

    console.log(data)

    record.value = {
      id: data.record_id,
      score: data.score,
      total_questions: data.total_questions || 0,
      elapsed_time: data.elapsed_time || 0,
      quiz_id: data.quiz_id || null
    }

  } catch (err) {
    console.error('Failed to load quiz result:', err)
  } finally {
    loading.value = false
  }
}

displayResult();

onMounted(async () => {
  await fetchUser()
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
  console.log('retype!')
}
</script>

<style scoped>
/* =========================================================
   FROSTED NOIR
   ========================================================= */

.quiz-result-page {
  --white: #ffffff;
  --black: #000000;
  --gray: #a9a9a9;
  --light-gray: #d3d3d3;
  --dark-gray: #696969;

  --surface: #ffffff;
  --surface-soft: #f7f7f7;
  --surface-muted: #eeeeee;

  --border: #d3d3d3;
  --text-primary: #000000;
  --text-secondary: #696969;
  --text-muted: #a9a9a9;

  min-height: 100dvh;
  width: 100%;

  background: var(--surface-soft);
  color: var(--text-primary);

  font-family:
    "Inter",
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;

  display: flex;
  flex-direction: column;

  box-sizing: border-box;
}

.quiz-result-page *,
.quiz-result-page *::before,
.quiz-result-page *::after {
  box-sizing: border-box;
}


/* =========================================================
   TOP BAR
   ========================================================= */

.top-bar {
  width: 100%;

  background: rgba(255, 255, 255, 0.94);

  border-bottom: 1px solid var(--border);

  position: sticky;
  top: 0;
  z-index: 100;

  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

.nav-content {
  width: 100%;
  max-width: 1100px;

  margin: 0 auto;

  padding:
    0.8rem clamp(0.85rem, 3vw, 1.5rem);

  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 1rem;
}


/* =========================================================
   BRAND
   ========================================================= */

.brand {
  min-width: 0;

  display: flex;
  align-items: center;
}

.brand-text {
  color: var(--black);

  font-size: 0.82rem;
  font-weight: 800;

  letter-spacing: 0.08em;
  text-transform: uppercase;

  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}


/* =========================================================
   PROFILE
   ========================================================= */

.profile-link {
  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  text-decoration: none;
}

.user-avatar {
  width: 38px;
  height: 38px;

  border-radius: 50%;

  border: 2px solid var(--light-gray);

  background: var(--surface-muted);

  object-fit: cover;

  transition:
    border-color 0.2s ease,
    transform 0.2s ease;
}

.profile-link:hover .user-avatar {
  border-color: var(--dark-gray);
  transform: scale(1.04);
}


/* =========================================================
   MAIN
   ========================================================= */

.container {
  flex: 1;

  width: 100%;
  max-width: 1100px;

  margin: 0 auto;

  padding:
    clamp(1.25rem, 4vw, 3rem) clamp(0.85rem, 4vw, 1.5rem) clamp(2rem, 5vw, 4rem);

  display: flex;
  align-items: center;
  justify-content: center;
}


/* =========================================================
   RESULT CARD
   ========================================================= */

.result-card {
  width: 100%;

  /*
   * Keeps the result card comfortable on desktop
   * while allowing it to shrink naturally on mobile.
   */
  max-width: 460px;

  background: var(--surface);

  border: 1px solid var(--border);
  border-radius: 18px;

  padding: clamp(1.75rem, 5vw, 2.5rem);

  text-align: center;

  box-shadow:
    0 8px 30px rgba(0, 0, 0, 0.05);

  animation: resultEnter 0.35s ease-out both;
}


/* =========================================================
   SCORE
   ========================================================= */

.score-summary {
  width: 100%;

  display: flex;
  flex-direction: column;
  align-items: center;
}

.score-number {
  margin: 0;

  color: var(--black);

  /*
   * Responsive without becoming excessively large.
   */
  font-size: clamp(2rem, 7vw, 2.9rem);

  line-height: 1;

  font-weight: 800;

  letter-spacing: -0.04em;

  overflow-wrap: anywhere;
}

.score-text {
  margin: 0.7rem 0 0;

  color: var(--dark-gray);

  font-size: clamp(0.78rem, 2vw, 0.88rem);

  font-weight: 500;

  line-height: 1.5;
}


/* =========================================================
   TIME
   ========================================================= */

.time-text {
  margin: 0.75rem 0 0;

  max-width: 100%;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  padding: 0.45rem 0.8rem;

  background: var(--surface-muted);

  border-radius: 7px;

  color: var(--dark-gray);

  font-size: clamp(0.7rem, 2vw, 0.78rem);

  font-weight: 700;

  white-space: nowrap;
}


/* =========================================================
   FEEDBACK
   ========================================================= */

.feedback-msg {
  width: 100%;

  margin:
    clamp(1.5rem, 5vw, 2rem) 0;

  padding: clamp(0.85rem, 3vw, 1rem);

  background: var(--surface-soft);

  border: 1px solid var(--border);
  border-radius: 10px;
}

.feedback-msg p {
  margin: 0;

  color: var(--dark-gray);

  font-size: clamp(0.76rem, 2vw, 0.85rem);

  line-height: 1.6;

  overflow-wrap: anywhere;
}


/* =========================================================
   ACTIONS
   ========================================================= */

.action-grid {
  width: 100%;

  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));

  gap: 0.75rem;

  margin-top: 0.5rem;
}

.btn-primary,
.btn-outline {
  width: 100%;
  min-width: 0;
  min-height: 46px;

  padding: 0.75rem 1rem;

  border-radius: 9px;

  display: flex;
  align-items: center;
  justify-content: center;

  font-family: inherit;

  font-size: 0.82rem;
  font-weight: 700;

  line-height: 1.3;

  text-align: center;

  cursor: pointer;

  transition:
    background 0.2s ease,
    border-color 0.2s ease,
    color 0.2s ease,
    transform 0.2s ease,
    box-shadow 0.2s ease;
}


/* =========================================================
   PRIMARY BUTTON
   ========================================================= */

.btn-primary {
  background: var(--black);
  color: var(--white);

  border: 1px solid var(--black);

  text-decoration: none;
}

.btn-primary:hover {
  background: var(--dark-gray);

  transform: translateY(-1px);

  box-shadow:
    0 5px 15px rgba(0, 0, 0, 0.12);
}


/* =========================================================
   OUTLINE BUTTON
   ========================================================= */

.btn-outline {
  background: var(--white);
  color: var(--dark-gray);

  border: 1px solid var(--border);
}

.btn-outline:hover {
  background: var(--surface-muted);

  border-color: var(--gray);

  color: var(--black);

  transform: translateY(-1px);
}


/* =========================================================
   LOADING
   ========================================================= */

.result-card>p {
  margin: 0;

  color: var(--dark-gray);

  font-size: 0.85rem;
  font-weight: 600;

  line-height: 1.5;
}


/* =========================================================
   FOCUS
   ========================================================= */

.btn-primary:focus-visible,
.btn-outline:focus-visible,
.profile-link:focus-visible {
  outline: 2px solid var(--black);
  outline-offset: 3px;
}


/* =========================================================
   ANIMATION
   ========================================================= */

@keyframes resultEnter {
  from {
    opacity: 0;
    transform: translateY(12px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}


/* =========================================================
   TABLET
   ========================================================= */

@media (max-width: 768px) {
  .nav-content {
    padding:
      0.75rem 1rem;
  }

  .container {
    align-items: center;

    padding:
      1.5rem 1rem 2.5rem;
  }

  .result-card {
    max-width: 440px;
  }
}


/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 600px) {
  .top-bar {
    position: sticky;
  }

  .nav-content {
    padding:
      0.7rem 0.85rem;
  }

  .brand-text {
    max-width: calc(100vw - 5rem);

    font-size: 0.72rem;

    letter-spacing: 0.06em;
  }

  .user-avatar {
    width: 35px;
    height: 35px;
  }

  .container {
    align-items: flex-start;

    padding:
      1rem 0.75rem 2rem;
  }

  .result-card {
    width: 100%;

    margin-top: 0.5rem;

    padding:
      1.75rem 1rem;

    border-radius: 15px;
  }

  .score-number {
    font-size: clamp(2rem, 11vw, 2.4rem);
  }

  .score-text {
    font-size: 0.8rem;
  }

  .time-text {
    font-size: 0.72rem;
  }

  .feedback-msg {
    margin:
      1.5rem 0;

    padding:
      0.85rem;
  }

  .feedback-msg p {
    font-size: 0.78rem;
  }

  /*
   * Stack buttons on phones.
   */
  .action-grid {
    grid-template-columns: 1fr;

    gap: 0.6rem;
  }

  .btn-primary,
  .btn-outline {
    min-height: 45px;

    padding:
      0.7rem 0.85rem;

    font-size: 0.8rem;
  }
}


/* =========================================================
   SMALL PHONES
   ========================================================= */

@media (max-width: 400px) {
  .nav-content {
    padding:
      0.65rem 0.7rem;
  }

  .brand-text {
    font-size: 0.66rem;
  }

  .user-avatar {
    width: 33px;
    height: 33px;
  }

  .container {
    padding:
      0.75rem 0.5rem 1.5rem;
  }

  .result-card {
    margin-top: 0.35rem;

    padding:
      1.5rem 0.85rem;

    border-radius: 14px;
  }

  .score-number {
    font-size: 2rem;
  }

  .score-text {
    font-size: 0.76rem;
  }

  .time-text {
    padding:
      0.4rem 0.65rem;

    font-size: 0.68rem;
  }

  .feedback-msg {
    padding:
      0.75rem;
  }

  .feedback-msg p {
    font-size: 0.74rem;
    line-height: 1.55;
  }
}


/* =========================================================
   VERY SMALL DEVICES
   ========================================================= */

@media (max-width: 340px) {
  .brand-text {
    max-width: calc(100vw - 4.5rem);

    font-size: 0.6rem;
  }

  .result-card {
    padding:
      1.35rem 0.7rem;
  }

  .score-number {
    font-size: 1.85rem;
  }

  .feedback-msg {
    margin:
      1.25rem 0;
  }

  .btn-primary,
  .btn-outline {
    min-height: 43px;

    font-size: 0.76rem;
  }
}


/* =========================================================
   REDUCED MOTION
   ========================================================= */

@media (prefers-reduced-motion: reduce) {
  .result-card {
    animation: none;
  }

  .btn-primary,
  .btn-outline,
  .user-avatar {
    transition: none;
  }
}
</style>