<template>
  <div class="user-wrapper">
    <UserSidebar :isSidebarOpen="isSidebarOpen" @logout="handleLogout" @closeSidebar="closeSidebar" />
    <div class="sidebar-overlay" :class="{ active: isSidebarOpen && isMobile }" @click="isSidebarOpen = false" />

    <div class="main-wrapper">
      <UserTopbar :currentPageTitle="currentPageTitle" @toggleSidebar="toggleSidebar" />
      <main class="user-main">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import UserSidebar from '@/components/UserSide/SideBar.vue'
import UserTopbar from '@/components/UserSide/TopBar.vue'
import axios from 'axios'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { startHeartbeat } from '@/composables/heartbeat'
import { useUser } from '@/composables/useUser'

const router = useRouter()
const route = useRoute()

const { user, fetchUser } = useUser()

const isSidebarOpen = ref(false)
const windowWidth = ref(window.innerWidth)

const updateWindowWidth = () => {
  windowWidth.value = window.innerWidth
  if (windowWidth.value > 1024 && isSidebarOpen.value) {
    isSidebarOpen.value = false
  }
}

onMounted(async () => {
  window.addEventListener('resize', updateWindowWidth)

  // Load user once
  if (!user.value) {
    try {
      await fetchUser()
    } catch {
      user.value = null
    }
  }

  // Start heartbeat only if logged in
  if (user.value) {
    startHeartbeat(router)
  }
})

onUnmounted(() => window.removeEventListener('resize', updateWindowWidth))

const isMobile = computed(() => windowWidth.value <= 1024)

const pageTitles = {
  '/user': 'Home',
  '/user/': 'Home',
  '/user/quizzes': 'Quizzes',
  '/user/records': 'My Records',
  '/user/profile': 'My Profile',
}

const currentPageTitle = computed(() => pageTitles[route.path] ?? 'Home')

const toggleSidebar = () => { isSidebarOpen.value = !isSidebarOpen.value }
const closeSidebar = () => { isSidebarOpen.value = false }

const handleLogout = async () => {
  try {
    if (confirm('Are you sure you want to logout?')) {
      await axios.post('/api/logout')
      user.value = null
      router.replace('/')
    }
  } catch (error) {
    console.error('Logout failed:', error)
  }
}
</script>

<style scoped>
*,
*::before,
*::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

/* ── WRAPPER ── */
.user-wrapper {
  display: flex;
  width: 100%;
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  overflow-x: hidden;
}

/* ── MAIN AREA (sits beside fixed sidebar) ── */
.main-wrapper {
  flex: 1;
  margin-left: 240px;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  min-width: 0;
}

/* ── PAGE CONTENT ── */
.user-main {
  flex: 1;
  width: 100%;
  padding: clamp(1rem, 3vw, 2rem);
  overflow-x: hidden;
}

.user-main>* {
  width: 100%;
  min-width: 0;
}

/* ── OVERLAY (mobile only) ── */
.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 999;
  backdrop-filter: blur(4px);
}

.sidebar-overlay.active {
  opacity: 1;
  pointer-events: all;
}

/* ── TABLET: 769px – 1024px ── */
@media (max-width: 1024px) and (min-width: 769px) {
  .main-wrapper {
    margin-left: 220px;
  }
}

/* ── MOBILE: ≤768px ── */
@media (max-width: 768px) {
  .main-wrapper {
    margin-left: 0;
  }

  .user-main {
    padding: clamp(0.875rem, 4vw, 1.5rem);
  }
}

/* ── SMALL MOBILE: ≤480px ── */
@media (max-width: 480px) {
  .user-main {
    padding: 0.875rem;
  }
}
</style>