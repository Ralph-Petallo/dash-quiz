<template>
  <div class="leaderboard">

    <div class="greet-container">
      <div class="greet">
        {{ greetMessage }}, {{ userFullName }}!👋
      </div>
      <div class="greet-sub">
        See how you rank against other quiz takers.
      </div>
    </div>

    <div class="lb-header">
      <div class="lb-title-group">
        <div class="lb-icon">
          <i class="fas fa-trophy leaderboard-icon"></i>
        </div>
        <div>
          <h1 class="lb-title">Leaderboard</h1>
          <p class="lb-sub">TOP {{ displayedLeaderboard.length }} participants for this selection</p>
        </div>
      </div>


    </div>

    <div class="lb-search">
      <i class="fa-solid fa-magnifying-glass search-icon"></i>
      <input v-model="searchQuery" placeholder="Search participant..." class="search-input" />
      <div class="lb-filters-row">
        <div class="filter-label">Filter Results</div>
        <div class="select-wrapper">
          <select v-model="selectedQuiz" class="quiz-select">
            <option value="">All</option>
            <option v-for="quiz in availableQuizzes" :key="quiz" :value="quiz">
              {{ quiz }}
            </option>
          </select>
          <span class="select-arrow"></span>
        </div>
      </div>
      <div class="lb-controls">
        <div v-if="userPosition" class="you-pill">
          <span class="you-dot"></span>
          Current Rank #{{ userPosition }}
        </div>
      </div>
    </div>



    <div class="podium" v-if="!searchQuery && topThree.length">
      <div v-for="(entry, i) in podiumOrder" :key="entry.id" class="podium-card"
        :class="[`podium-${podiumPositions[i]}`, entry.isYou ? 'is-you' : '']">
        <div class="podium-avatar-wrap">
          <img :draggable="false" :src="`/storage/images/profiles/${entry.profile_photo || 'default.png'}`"
            class="podium-avatar" alt="user" />
          <div class="podium-medal">{{ ['🥇', '🥈', '🥉'][podiumPositions[i] - 1] }}</div>
        </div>
        <div class="podium-name">{{ entry.displayName }}<span v-if="entry.isYou" class="you-tag">You</span></div>
        <div class="podium-score">{{ entry.score }}/10</div>
        <div class="podium-bar-wrap">
          <div class="podium-bar" :style="{ height: podiumHeights[i] }"></div>
        </div>
      </div>
    </div>

    <div class="lb-list" ref="listRef">

      <div v-if="isLoading" class="lb-loading">
        <div class="spinner"></div>
      </div>

      <template v-else>
        <div v-for="(entry, index) in filteredList" :key="entry.id" class="lb-item"
          :class="[entry.isYou ? 'is-you' : '', index < 3 && !searchQuery ? 'is-top' : '']"
          :style="{ animationDelay: `${index * 20}ms` }">

          <div class="item-rank" :class="index < 3 && !searchQuery ? `rank-${index + 1}` : ''">
            <span v-if="index < 3 && !searchQuery" class="rank-medal">{{ ['🥇', '🥈', '🥉'][index] }}</span>

            <span v-else class="rank-num">{{ index + 1 }}</span>

          </div>

          <div class="item-avatar-wrap">
            <img :draggable="false" :src="`/storage/images/profiles/${entry.profile_photo || 'default.png'}`"
              class="item-avatar" alt="user" />
            <span v-if="entry.isYou" class="avatar-ring"></span>
          </div>

          <div class="item-info">
            <div class="item-name">
              {{ entry.displayName }}
              <span v-if="entry.isYou" class="you-tag">You</span>
            </div>
            <div class="item-quiz">{{ entry.quiz_title }}</div>
          </div>

          <div class="item-right">
            <div class="score-ring-wrap">
              <svg class="score-ring" viewBox="0 0 36 36">
                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f1f5f9" stroke-width="3" />
                <circle cx="18" cy="18" r="15.9" fill="none" :stroke="entry.score >= 7 ? '#6366f1' : '#f43f5e'"
                  stroke-width="3" stroke-linecap="round" stroke-dasharray="100"
                  :stroke-dashoffset="100 - (entry.score / 10 * 100)" transform="rotate(-10 18 18)" />
                <text x="18" y="22" text-anchor="middle" font-size="9" font-weight="700"
                  :fill="entry.score >= 7 ? '#6366f1' : '#f43f5e'">
                  {{ entry.score }}
                </text>
              </svg>
            </div>
            <div class="item-date">{{ formatDate(entry.completed_at) }}</div>
          </div>
        </div>

        <div v-if="!filteredList.length" class="lb-empty">
          <div class="empty-icon">📊</div>
          <p>No results found</p>
          <small>{{ searchQuery ? 'Try a different name' : 'No rankings yet' }}</small>
        </div>
      </template>

    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { ref, computed, onMounted } from 'vue'
import { useUser } from "@/composables/useUser"

const greetMessage = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 18) return 'Good afternoon'
  return 'Good evening'
})

const LEADERBOARD_CACHE_KEY = 'dash-quiz_leaderboard_cache'
const LEADERBOARD_CACHE_TTL = 1000 * 60 * 5 // 5 minutes

const leaderboard = ref([])
const isLoading = ref(false)
const searchQuery = ref('')
const selectedQuiz = ref('') // Tracks active dropdown value
const { user, fetchUser, userFullName } = useUser()

// Cache getter with validation to prevent stale data usage and reduce server load
const getCachedLeaderboard = () => {
  try {
    const cached = JSON.parse(localStorage.getItem(LEADERBOARD_CACHE_KEY))
    if (!cached) {
      return null
    }
    if (Date.now() - cached.ts > LEADERBOARD_CACHE_TTL) {
      return null
    }
    return cached.value
  } catch {
    return null
  }
}

// Cache setter with timestamp to localStorage for quick retrieval and reduced server hits
const setLeaderboardCache = (value) => {
  localStorage.setItem(LEADERBOARD_CACHE_KEY, JSON.stringify({
    ts: Date.now(),
    value,
  })
  )
}

// Dynamically grab all unique quiz titles from the raw dataset
const availableQuizzes = computed(() => {
  const titles = leaderboard.value.map(item => item.quiz_title).filter(Boolean)
  return [...new Set(titles)]
})

// Sorts and filters dataset based on dropdown selection
const displayedLeaderboard = computed(() => {
  let list = [...leaderboard.value]

  // Slice down to only matching selected quiz title if picked
  if (selectedQuiz.value) {
    list = list.filter(item => item.quiz_title === selectedQuiz.value)
  }

  // Sort highest score first so rankings are true to the filtered subset
  return list.sort((a, b) => b.score - a.score)
})

const userPosition = computed(() => {
  if (!user.value?.id) return null
  const idx = displayedLeaderboard.value.findIndex(u => u.user_id === user.value.id)
  return idx !== -1 ? idx + 1 : null
})

// Top 3 for podium based on selected filters
const topThree = computed(() => displayedLeaderboard.value.slice(0, 3))

// Podium order layout mapping
const podiumOrder = computed(() => {
  const t = topThree.value
  if (t.length < 3) return t
  return [t[1], t[0], t[2]]
})

const podiumPositions = [2, 1, 3]
const podiumHeights = ['60px', '80px', '44px']

// Final displayed list after overlaying input text filter 
const filteredList = computed(() => {
  const q = searchQuery.value.toLowerCase().trim()
  if (!q) return displayedLeaderboard.value
  return displayedLeaderboard.value.filter(e =>
    (e.displayName || '').toLowerCase().includes(q) ||
    (e.quiz_title || '').toLowerCase().includes(q)
  )
})

const formatDate = (date) =>
  date ? new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : ''

const getLeaderBoard = async (force = false) => {
  const cached = !force ? getCachedLeaderboard() : null
  if (cached) {
    leaderboard.value = cached
    return
  }

  isLoading.value = true

  try {
    await axios.get('/sanctum/csrf-cookie')
    const { data } = await axios.get('/api/dashboard/leaderboard')
    const currentUserId = user.value?.id ?? null
    leaderboard.value = data.data.map(u => ({
      ...u,
      isYou: u.user_id === currentUserId,
      displayName: u.name,
    }))
    setLeaderboardCache(leaderboard.value)
  } catch (err) {
    console.error(err)
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await fetchUser()
  await getLeaderBoard()
})
</script>

<style scoped>
/* ── FONTS ── */
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@500&display=swap');

*,
*::before,
*::after {
  box-sizing: border-box;
}

/* ── ROOT ── */
.leaderboard {
  width: 100%;
  background: #ffffff;
  border-radius: 20px;
  padding: 24px;
  box-shadow:
    0 1px 3px rgba(0, 0, 0, 0.04),
    0 10px 30px rgba(0, 0, 0, 0.06);
  font-family: 'DM Sans', sans-serif;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* ── HEADER ── */
.lb-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.greet {
  font-size: 18px;
  font-family: BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  color: #1e293b;
  font-weight: 800;
}

.greet-sub {
  font-size: 12px;
  color: #64748b;
}

.lb-title-group {
  display: flex;
  align-items: center;
  gap: 12px;
}

.lb-icon {
  border-radius: 10px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.leaderboard-icon {
  color: #6366f1;
  font-size: 1.2rem;
}

.lb-title {
  font-size: 1.35rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  line-height: 1.2;
}

.lb-sub {
  font-size: 0.8rem;
  color: #94a3b8;
  margin: 2px 0 0;
}

/* YOU PILL */
.you-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #f1f0ff;
  color: #4f46e5;
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 700;
  border: 1px solid #e0e7ff;
}

.you-dot {
  width: 7px;
  height: 7px;
  background: #6366f1;
  border-radius: 50%;
  animation: pulse-dot 1.8s ease infinite;
}

@keyframes pulse-dot {

  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }

  50% {
    opacity: 0.5;
    transform: scale(0.75);
  }
}

/* ── SEARCH ── */
.lb-search {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: grey;
  pointer-events: none;
}

.search-input {
  width: 250px;
  padding: 10px 14px 10px 38px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.875rem;
  color: #1e293b;
  background: #f8fafc;
  transition: border-color 0.2s, background 0.2s;
  outline: none;
}

.search-input:focus {
  border-color: #6366f1;
  background: #fff;
}

/* ── NEW FILTERS ROW (SPACE-BETWEEN) ── */
.lb-filters-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8fafc;
  padding: 4px 14px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  gap: 12px;
}

.filter-label {
  font-size: 0.8rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.select-wrapper {
  position: relative;
  min-width: 180px;
}

.quiz-select {
  width: 100%;
  padding: 6px 32px 6px 12px;
  font-family: 'DM Sans', sans-serif;
  border-radius: 8px;
  cursor: pointer;
  border: none;
  outline: none;
  appearance: none;
  /* Removes native browser styles */
  transition: border-color 0.15s, box-shadow 0.15s;
}

.quiz-select:focus {
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
}

.select-arrow {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 0;
  height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 5px solid #64748b;
  pointer-events: none;
}

/* ── PODIUM ── */
.podium {
  display: flex;
  align-items: flex-end;
  justify-content: center;
  gap: 8px;
  padding: 16px 8px 0;
}

.podium-card {
  flex: 1;
  max-width: 110px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  animation: fadeUp 0.5s ease both;
}

.podium-card.podium-1 {
  animation-delay: 0.1s;
}

.podium-card.podium-2 {
  animation-delay: 0.2s;
}

.podium-card.podium-3 {
  animation-delay: 0.3s;
}

.podium-avatar-wrap {
  position: relative;
}

.podium-avatar {
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #e2e8f0;
}

.podium-1 .podium-avatar {
  width: 56px;
  height: 56px;
  border-color: #fbbf24;
}

.podium-2 .podium-avatar {
  width: 46px;
  height: 46px;
  border-color: #94a3b8;
}

.podium-3 .podium-avatar {
  width: 42px;
  height: 42px;
  border-color: #c97f4a;
}

.podium-medal {
  position: absolute;
  bottom: -6px;
  right: -4px;
  font-size: 1rem;
  line-height: 1;
}

.podium-name {
  font-size: 0.75rem;
  font-weight: 700;
  color: #1e293b;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
  display: flex;
  align-items: center;
  gap: 4px;
  flex-wrap: wrap;
  justify-content: center;
  margin-top: 8px;
}

.podium-score {
  font-size: 0.7rem;
  font-weight: 600;
  color: #6366f1;
  font-family: 'DM Mono', monospace;
}

.podium-bar-wrap {
  width: 100%;
  display: flex;
  justify-content: center;
}

.podium-bar {
  width: 100%;
  border-radius: 6px 6px 0 0;
  transition: height 0.6s ease;
}

.podium-1 .podium-bar {
  background: #f59e0b;
}

.podium-2 .podium-bar {
  background: #94a3b8;
}

.podium-3 .podium-bar {
  background: #c97f4a;
}

.podium-card.is-you .podium-bar {
  box-shadow: 0 0 12px rgba(99, 102, 241, 0.3);
}

/* ── LIST ── */
.lb-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-height: 420px;
  overflow-y: auto;
  padding-right: 2px;
}

.lb-list::-webkit-scrollbar {
  width: 4px;
}

.lb-list::-webkit-scrollbar-track {
  background: transparent;
}

.lb-list::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 99px;
}

/* ── ITEM ── */
.lb-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
  background: #fff;
  transition: background 0.15s, border-color 0.15s, transform 0.15s;
  animation: fadeUp 0.4s ease both;
  cursor: default;
}

.lb-item:hover {
  background: #f8fafc;
  transform: translateX(2px);
}

.lb-item.is-top {
  background: #fafbff;
}

.lb-item.is-you {
  background: #f1f0ff;
  border-color: #c7d2fe;
}

.lb-item.is-you:hover {
  background: #eef2ff;
}

/* RANK */
.item-rank {
  width: 28px;
  flex-shrink: 0;
  text-align: center;
}

.rank-num {
  font-size: 0.8rem;
  font-weight: 700;
  color: #94a3b8;
  font-family: 'DM Mono', monospace;
}

.rank-medal {
  font-size: 1.1rem;
  line-height: 1;
}

/* AVATAR */
.item-avatar-wrap {
  position: relative;
  flex-shrink: 0;
}

.item-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e2e8f0;
  display: block;
}

.avatar-ring {
  position: absolute;
  inset: -3px;
  border-radius: 50%;
}

/* INFO */
.item-info {
  flex: 1;
  min-width: 0;
}

.item-name {
  font-size: 0.875rem;
  font-weight: 700;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-quiz {
  font-size: 0.72rem;
  color: #94a3b8;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 1px;
}

/* YOU TAG */
.you-tag {
  background: #6366f1;
  color: #fff;
  font-size: 0.6rem;
  padding: 1px 6px;
  border-radius: 999px;
  font-weight: 700;
  flex-shrink: 0;
}

/* RIGHT */
.item-right {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  flex-shrink: 0;
}

/* SVG RING */
.score-ring-wrap {
  width: 36px;
  height: 36px;
}

.score-ring {
  width: 36px;
  height: 36px;
  transform: rotate(0deg);
}

.score-ring circle:nth-child(2) {
  transition: stroke-dashoffset 0.6s ease;
}

.item-date {
  font-size: 0.65rem;
  color: #cbd5e1;
  font-family: 'DM Mono', monospace;
  white-space: nowrap;
}

/* ── LOADING ── */
.lb-loading {
  padding: 3rem;
  display: flex;
  justify-content: center;
}

.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #f1f5f9;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ── EMPTY ── */
.lb-empty {
  text-align: center;
  padding: 3rem 1rem;
  color: #94a3b8;
}

.empty-icon {
  font-size: 2rem;
  margin-bottom: 8px;
}

.lb-empty p {
  font-weight: 600;
  color: #64748b;
  margin: 0 0 4px;
}

.lb-empty small {
  font-size: 0.8rem;
}

/* ── ANIMATION ── */
@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ── RESPONSIVE ── */
@media (max-width: 640px) {
  .leaderboard {
    padding: 16px;
    gap: 16px;
    border-radius: 16px;
  }

  .lb-filters-row {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }

  .select-wrapper {
    min-width: 100%;
  }

  .podium {
    gap: 4px;
  }

  .podium-card {
    max-width: 90px;
  }

  .lb-item {
    padding: 10px 12px;
    gap: 10px;
  }

  .item-name {
    font-size: 0.82rem;
  }

  .item-quiz {
    font-size: 0.68rem;
  }
}

@media (max-width: 400px) {
  .lb-title {
    font-size: 1.15rem;
  }

  .podium-1 .podium-avatar {
    width: 46px;
    height: 46px;
  }

  .podium-2 .podium-avatar {
    width: 38px;
    height: 38px;
  }

  .podium-3 .podium-avatar {
    width: 34px;
    height: 34px;
  }

  .item-date {
    display: none;
  }
}
</style>