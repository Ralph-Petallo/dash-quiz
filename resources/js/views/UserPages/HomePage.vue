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

      <div v-if="userPosition" class="rank-pill">
        <span class="rank-pill-dot"></span>
        Rank #{{ userPosition }}
      </div>
    </div>

    <div class="toolbar">
      <div class="search-box">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input v-model="searchQuery" placeholder="Search participant..." class="search-input" />
      </div>

      <div class="filter-box">
        <span class="filter-box-label">Quiz</span>
        <div class="select-wrapper">
          <select v-model="selectedQuiz" class="quiz-select">
            <option v-for="quiz in availableQuizzes" :key="quiz" :value="quiz">
              {{ quiz }}
            </option>
          </select>
          <span class="select-arrow"></span>
        </div>
      </div>
    </div>

    <div class="podium" v-if="!searchQuery && topThree.length">
      <div v-for="entry in podiumOrder" :key="entry.id" class="podium-card" :class="[
        `podium-${entry.rank}`,
        entry.isYou ? 'is-you' : ''
      ]">
        <div class="podium-avatar-wrap">
          <img :draggable="false" :src="photoPath(entry.profile_photo)" class="podium-avatar" alt="user" />

          <div class="podium-medal">
            {{ ['🥇', '🥈', '🥉'][entry.rank - 1] }}
          </div>
        </div>

        <div class="podium-name">
          {{ entry.displayName }}
          <span v-if="entry.isYou" class="you-tag">You</span>
        </div>

        <div class="podium-score">
          {{ entry.score }}/10
        </div>

        <div class="podium-bar-wrap">
          <div class="podium-bar" :style="{ height: podiumHeight(entry.rank) }"></div>
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
            <img :draggable="false" :src="photoPath(entry.profile_photo)" class="item-avatar" alt="user" />
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
                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#EDEDED" stroke-width="3" />
                <circle cx="18" cy="18" r="15.9" fill="none" :stroke="entry.score >= 7 ? '#000000' : '#A9A9A9'"
                  stroke-width="3" stroke-linecap="round" stroke-dasharray="100"
                  :stroke-dashoffset="100 - (entry.score / 10 * 100)" transform="rotate(-10 18 18)" />
                <text x="18" y="22" text-anchor="middle" font-size="9" font-weight="700"
                  :fill="entry.score >= 7 ? '#000000' : '#696969'">
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
import { ref, computed, onMounted } from 'vue'
import { useUser } from "@/composables/useUser"
import { useGreetMessages } from '@/composables/useGreetMessages'
import { useUserRequests } from '@/composables/useUserRequests'

const { greetMessage } = useGreetMessages()
const { getLeaderBoard, leaderboard } = useUserRequests()
const isLoading = ref(false)
const searchQuery = ref('')
const selectedQuiz = ref('') // Tracks active dropdown value
const { user, fetchUser, userFullName } = useUser()

onMounted(async () => {
  await fetchUser()
  getLeaderBoard()
})

// Dynamically grab all unique quiz titles from the raw dataset
const availableQuizzes = computed(() => {
  const titles = leaderboard.value.map(item => item.quiz_title).filter(Boolean)
  selectedQuiz.value = titles[0]; // maintain selected quiz when leaderboard updates
  return [...new Set(titles)]
})

// Sorts and filters dataset based on dropdown selection
const displayedLeaderboard = computed(() => {
  let list = [...leaderboard.value];

  // Filter by selected quiz first
  if (selectedQuiz.value) {
    list = list.filter(item => item.quiz_title === selectedQuiz.value);
  }

  // Keep only the highest score per user
  const uniqueUsers = new Map();

  list.forEach(item => {
    const existing = uniqueUsers.get(item.user_id);

    // If no existing entry or current score is higher, update the map
    if (!existing || item.score > existing.score) {
      uniqueUsers.set(item.user_id, item);
    }
  });

  list = Array.from(uniqueUsers.values());

  // Sort highest score first
  return list.sort((a, b) => b.score - a.score);
});

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

  if (t.length === 0) return []

  // Assign actual leaderboard positions
  const ranked = t.map((user, index) => ({
    ...user,
    rank: index + 1
  }))

  // Arrange visually: 2nd → 1st → 3rd
  if (ranked.length === 1) {
    return ranked
  }

  if (ranked.length === 2) {
    return [ranked[1], ranked[0]]
  }

  return [ranked[1], ranked[0], ranked[2]]
})

const podiumHeight = (rank) => {
  if (rank === 1) return '80px'
  if (rank === 2) return '60px'
  return '40px'
}

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

const photoPath = function (img) {
  return `/storage/images/profiles/${img || 'default.png'}`
}




</script>

<style scoped>
/* ── FONTS ── */
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@500&display=swap');

*,
*::before,
*::after {
  box-sizing: border-box;
}

/* ── ROOT / FROSTED NOIR PALETTE ── */
.leaderboard {
  --noir-white: #FFFFFF;
  --noir-black: #000000;
  --noir-dark-gray: #696969;
  --noir-mid-gray: #A9A9A9;
  --noir-light-gray: #D3D3D3;
  --noir-surface: #FAFAFA;
  --noir-surface-alt: #F2F2F2;

  width: 100%;
  background: var(--noir-white);
  border-radius: 3px;
  padding: 28px;
  box-shadow:
    0 1px 2px rgba(0, 0, 0, 0.03),
    0 16px 40px rgba(0, 0, 0, 0.06);
  border: 1px solid var(--noir-surface-alt);
  font-family: 'DM Sans', sans-serif;
  display: flex;
  flex-direction: column;
  gap: 22px;
  color: var(--noir-black);
}

/* ── GREETING ── */
.greet {
  font-size: 18px;
  font-family: BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  color: var(--noir-black);
  font-weight: 800;
}

.greet-sub {
  font-size: 12.5px;
  color: var(--noir-dark-gray);
  margin-top: 2px;
}

/* ── HEADER ── */
.lb-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.lb-title-group {
  display: flex;
  align-items: center;
  gap: 14px;
}

.lb-icon {
  width: 44px;
  height: 44px;
  background: linear-gradient(160deg, #1a1a1a, var(--noir-black));
  border-radius: 12px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.leaderboard-icon {
  color: var(--noir-white);
  font-size: 1.15rem;
}

.lb-title {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--noir-black);
  margin: 0;
  line-height: 1.2;
  letter-spacing: -0.01em;
}

.lb-sub {
  font-size: 0.8rem;
  color: var(--noir-mid-gray);
  margin: 3px 0 0;
}

/* RANK PILL */
.rank-pill {
  display: flex;
  align-items: center;
  gap: 7px;
  background: var(--noir-black);
  color: var(--noir-white);
  padding: 8px 16px;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.01em;
}

.rank-pill-dot {
  width: 6px;
  height: 6px;
  background: var(--noir-white);
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
    opacity: 0.45;
    transform: scale(0.7);
  }
}

/* ── TOOLBAR ── */
.toolbar {
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--noir-surface);
  border: 1px solid var(--noir-surface-alt);
  border-radius: 14px;
  padding: 6px;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 0;
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  width: 14px;
  height: 14px;
  color: var(--noir-mid-gray);
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 10px 14px 10px 38px;
  border: 1px solid var(--noir-light-gray);
  border-radius: 10px;
  font-size: 0.86rem;
  background: transparent;
  color: var(--noir-black);
  outline: none;
  font-family: inherit;
}

.search-input::placeholder {
  color: var(--noir-mid-gray);
}

.search-input:focus {
  background: var(--noir-white);
  box-shadow: inset 0 0 0 1px var(--noir-light-gray);
}

.filter-box {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
  border-left: 1px solid var(--noir-light-gray);
  padding-left: 10px;
}

.filter-box-label {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--noir-dark-gray);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
}

.select-wrapper {
  position: relative;
  min-width: 150px;
}

.quiz-select {
  width: 100%;
  padding: 8px 30px 8px 12px;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  border: none;
  outline: none;
  background: var(--noir-white);
  color: var(--noir-black);
  appearance: none;
  transition: box-shadow 0.15s;
}

.quiz-select:focus {
  box-shadow: 0 0 0 2px var(--noir-black);
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
  border-top: 5px solid var(--noir-dark-gray);
  pointer-events: none;
}

/* ── PODIUM ── */
.podium {
  display: flex;
  align-items: flex-end;
  justify-content: center;
  gap: 10px;
  padding: 20px 8px 0;
}

.podium-card {
  flex: 1;
  max-width: 112px;
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
  border: 3px solid var(--noir-light-gray);
  background: var(--noir-surface);
}

.podium-1 .podium-avatar {
  width: 60px;
  height: 60px;
  border-color: var(--noir-black);
  box-shadow: 0 0 0 4px var(--noir-surface-alt), 0 8px 20px -6px rgba(0, 0, 0, 0.4);
}

.podium-2 .podium-avatar {
  width: 48px;
  height: 48px;
  border-color: var(--noir-dark-gray);
}

.podium-3 .podium-avatar {
  width: 44px;
  height: 44px;
  border-color: var(--noir-mid-gray);
}

.podium-medal {
  position: absolute;
  bottom: -6px;
  right: -4px;
  font-size: 1rem;
  line-height: 1;
  filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.25));
}

.podium-name {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--noir-black);
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
  color: var(--noir-dark-gray);
  font-family: 'DM Mono', monospace;
}

.podium-bar-wrap {
  width: 100%;
  display: flex;
  justify-content: center;
}

.podium-bar {
  width: 100%;
  border-radius: 8px 8px 0 0;
  transition: height 0.6s ease;
}

.podium-1 .podium-bar {
  background: linear-gradient(180deg, #1a1a1a, var(--noir-black));
}

.podium-2 .podium-bar {
  background: var(--noir-dark-gray);
}

.podium-3 .podium-bar {
  background: var(--noir-mid-gray);
}

.podium-card.is-you .podium-bar {
  box-shadow: 0 0 14px rgba(0, 0, 0, 0.3);
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
  background: var(--noir-light-gray);
  border-radius: 99px;
}

/* ── ITEM ── */
.lb-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid transparent;
  background: var(--noir-white);
  transition: background 0.15s, border-color 0.15s, transform 0.15s, box-shadow 0.15s;
  animation: fadeUp 0.4s ease both;
  cursor: default;
}

.lb-item:hover {
  background: var(--noir-surface);
  border-color: var(--noir-surface-alt);
  transform: translateX(2px);
  box-shadow: 0 4px 14px -8px rgba(0, 0, 0, 0.2);
}

.lb-item.is-top {
  background: var(--noir-surface);
}

.lb-item.is-you {
  background: var(--noir-black);
  border-color: var(--noir-black);
}

.lb-item.is-you:hover {
  background: #1a1a1a;
}

.lb-item.is-you .item-name,
.lb-item.is-you .rank-num {
  color: var(--noir-white);
}

.lb-item.is-you .item-quiz,
.lb-item.is-you .item-date {
  color: var(--noir-mid-gray);
}

.lb-item.is-you .you-tag {
  background: var(--noir-white);
  color: var(--noir-black);
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
  color: var(--noir-mid-gray);
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
  width: 38px;
  height: 38px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--noir-light-gray);
  display: block;
}

.avatar-ring {
  position: absolute;
  inset: -3px;
  border-radius: 50%;
  border: 2px solid var(--noir-white);
}

/* INFO */
.item-info {
  flex: 1;
  min-width: 0;
}

.item-name {
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--noir-black);
  display: flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-quiz {
  font-size: 0.72rem;
  color: var(--noir-mid-gray);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 1px;
}

/* YOU TAG */
.you-tag {
  background: var(--noir-black);
  color: var(--noir-white);
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
  color: var(--noir-mid-gray);
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
  border: 3px solid var(--noir-light-gray);
  border-top-color: var(--noir-black);
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
  color: var(--noir-mid-gray);
}

.empty-icon {
  font-size: 2rem;
  margin-bottom: 8px;
}

.lb-empty p {
  font-weight: 600;
  color: var(--noir-dark-gray);
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
    padding: 18px;
    gap: 18px;
    border-radius: 18px;
  }

  .lb-header {
    gap: 10px;
  }
}

@media (max-width: 600px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
    padding: 8px;
    gap: 8px;
  }

  .filter-box {
    border-left: none;
    border-top: 1px solid var(--noir-light-gray);
    padding-left: 0;
    padding-top: 8px;
    justify-content: space-between;
  }

  .select-wrapper {
    min-width: 60%;
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