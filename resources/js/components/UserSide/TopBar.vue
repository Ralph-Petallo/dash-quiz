<template>
  <header class="navbar">
    <div class="navbar-container">

      <div class="left-section">
        <button class="menu-trigger" @click="$emit('toggleSidebar')" v-if="isMobile">
          <i class="fas fa-bars"></i>
        </button>
        <h2 class="page-title">{{ currentPageTitle }}</h2>
      </div>
      <div v-if="currentPageTitle !== 'My Profile'">
        <div class="user-section">
          <div class="user-meta">
            <span class="user-name">{{ userFullName }}</span>
            <span class="user-status">Online</span>
          </div>

          <router-link to="/user/profile">
            <div class="avatar-container">
              <img :src="userAvatar" alt="avatar" class="avatar-img">
              <span class="status-indicator"></span>
            </div>
          </router-link>
        </div>
      </div>

    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useUser } from '@/composables/useUser.js'

defineProps({
  currentPageTitle: String,
})
defineEmits(['toggleSidebar'])

const { userAvatar, userFullName, fetchUser } = useUser()

const screenWidth = ref(window.innerWidth)
const updateWidth = () => { screenWidth.value = window.innerWidth }

onMounted(() => {
  window.addEventListener('resize', updateWidth)
  fetchUser()
})
onUnmounted(() => window.removeEventListener('resize', updateWidth))

const isMobile = computed(() => screenWidth.value <= 768)
</script>

<style scoped>
/* ── BASE ── */
.navbar {
  position: sticky;
  top: 0;
  z-index: 1000;
  height: 64px;
  background: rgba(255, 255, 255, 0.97);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(102, 126, 234, 0.1);
  display: flex;
  align-items: center;
  flex-shrink: 0;
}

.navbar-container {
  width: 100%;
  padding: 0 clamp(1rem, 2.5vw, 1.5rem);
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 100%;
}

/* ── LEFT ── */
.left-section {
  display: flex;
  align-items: center;
  gap: clamp(10px, 2vw, 18px);
  min-width: 0;
}

.page-title {
  font-size: clamp(1rem, 2.5vw, 1.3rem);
  font-weight: 700;
  background: black;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.menu-trigger {
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s, transform 0.2s;
}

.menu-trigger:hover {
  background: rgba(102, 126, 234, 0.2);
  transform: scale(1.05);
}

/* ── RIGHT ── */
.user-section {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

.user-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 1px;
}

.user-name {
  font-size: clamp(0.8rem, 2vw, 0.9rem);
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 120px;
}

.user-status {
  font-size: 0.7rem;
  color: #10b981;
  font-weight: 500;
}

.avatar-container {
  position: relative;
  padding: 2px;
  border: 2px solid rgba(102, 126, 234, 0.15);
  border-radius: 50%;
  transition: border-color 0.3s, box-shadow 0.3s;
  cursor: pointer;
  display: block;
}

.avatar-container:hover {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.avatar-img {
  width: clamp(34px, 5vw, 40px);
  height: clamp(34px, 5vw, 40px);
  border-radius: 50%;
  object-fit: cover;
  display: block;
}

.status-indicator {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 9px;
  height: 9px;
  background: #10b981;
  border: 2px solid white;
  border-radius: 50%;
}

/* ── SMALL MOBILE ── */
@media (max-width: 480px) {
  .user-meta {
    display: none;
  }
}

@media (max-width: 320px) {
  .navbar-container {
    padding: 0 0.5rem;
  }
}
</style>