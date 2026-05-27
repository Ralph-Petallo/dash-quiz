<template>
  <header class="navbar">
    <div class="navbar-container">

      <div class="left-section">
        <button class="menu-trigger" @click="$emit('toggleSidebar')" v-if="isMobile" aria-label="Toggle Menu">
          <i class="fa fa-bars-staggered"></i>
        </button>
        <h2 class="page-title">{{ currentPageTitle }}</h2>
      </div>
      <div v-if="route.path !== '/admin/settings'">
      <div class="user-section">
        <div class="user-meta" v-if="showUserMeta">
          <span class="user-name">{{ userFullName }}</span>
          <span class="user-status">Online</span>
        </div>


        <router-link to="/admin/settings">
          <div class="avatar-container">
            <img :src="userAvatar" class="avatar-img" alt="avatar" />
            <span class="pulse-indicator"></span>
          </div>
        </router-link>
      </div>
    </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useUser } from '@/composables/useUser'
import { useRoute } from 'vue-router'

defineProps({
  currentPageTitle: {
    type: String,
    default: 'Dashboard'
  }
})
defineEmits(['toggleSidebar'])

const route = useRoute();
const { fetchUser, userFullName, userAvatar } = useUser()
const windowWidth = ref(window.innerWidth)
const updateWidth = () => { windowWidth.value = window.innerWidth }

onMounted(() => {
  window.addEventListener('resize', updateWidth)
  fetchUser()
})
onUnmounted(() => window.removeEventListener('resize', updateWidth))

const isMobile = computed(() => windowWidth.value <= 768)
const showUserMeta = computed(() => windowWidth.value > 640)
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
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  flex-shrink: 0;
  width: 100%;
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
  font-size: clamp(1rem, 2.5vw, 1.25rem);
  font-weight: 700;
  color: #1e1b4b;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.menu-trigger {
  flex-shrink: 0;
  width: 42px;
  height: 42px;
  background: #f1f5f9;
  border: none;
  border-radius: 10px;
  color: #1e293b;
  cursor: pointer;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s, color 0.2s, transform 0.2s;
}

.menu-trigger:hover {
  background: #e2e8f0;
  color: #6366f1;
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
  max-width: 130px;
}

.user-status {
  font-size: 0.7rem;
  color: #10b981;
  font-weight: 500;
}

.avatar-container {
  position: relative;
  padding: 2px;
  border: 2px solid #e2e8f0;
  border-radius: 50%;
  transition: border-color 0.3s, transform 0.3s;
  cursor: pointer;
}

.avatar-container:hover {
  border-color: #6366f1;
  transform: scale(1.05);
}

.avatar-img {
  width: clamp(36px, 5vw, 42px);
  height: clamp(36px, 5vw, 42px);
  border-radius: 50%;
  object-fit: cover;
  display: block;
}

.pulse-indicator {
  position: absolute;
  bottom: 1px;
  right: 1px;
  width: 10px;
  height: 10px;
  background: #10b981;
  border: 2px solid white;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
  }

  70% {
    box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
  }

  100% {
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
  }
}

/* ── SMALL MOBILE ── */
@media (max-width: 480px) {
  .navbar-container {
    padding: 0 0.75rem;
  }
}
</style>