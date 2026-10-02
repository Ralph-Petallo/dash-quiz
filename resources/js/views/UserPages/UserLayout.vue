<template>
  <div class="user-wrapper">

    <!-- Sidebar -->
    <UserSidebar :isSidebarOpen="isSidebarOpen" @logout="handleLogout" @closeSidebar="closeSidebar" />

    <!-- Mobile overlay -->
    <div class="sidebar-overlay" :class="{ active: isSidebarOpen && isMobile }" @click="isSidebarOpen = false"></div>

    <!-- Main content -->
    <div class="main-wrapper">

      <!-- Topbar -->
      <UserTopbar :currentPageTitle="currentPageTitle" @toggleSidebar="toggleSidebar" />

      <!-- Page -->
      <main class="user-main">
        <router-view />
      </main>

    </div>
  </div>
</template>

<script setup>
import UserSidebar from "@/components/UserSide/SideBar.vue"
import UserTopbar from "@/components/UserSide/TopBar.vue"

import { ref, computed, onMounted, onUnmounted } from "vue"
import { useRouter, useRoute } from "vue-router"

import { startHeartbeat } from "@/composables/heartbeat"
import { useUser } from "@/composables/useUser"

import api from "@/bootstrap"

const router = useRouter()
const route = useRoute()

const {
  user,
  fetchUser,
  clearUser,
} = useUser()

const isSidebarOpen = ref(false)
const windowWidth = ref(window.innerWidth)

/*
|--------------------------------------------------------------------------
| Window resize
|--------------------------------------------------------------------------
*/

const updateWindowWidth = () => {
  windowWidth.value = window.innerWidth

  if (windowWidth.value > 1024 && isSidebarOpen.value) {
    isSidebarOpen.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Mounted
|--------------------------------------------------------------------------
*/

onMounted(async () => {
  window.addEventListener("resize", updateWindowWidth)

  /*
  |--------------------------------------------------------------------------
  | Make sure user exists
  |--------------------------------------------------------------------------
  |
  | Normally login already sets user.value.
  | This fetch is mainly useful when the page is refreshed.
  |
  */

  if (!user.value) {
    await fetchUser()
  }

  /*
  |--------------------------------------------------------------------------
  | Start heartbeat
  |--------------------------------------------------------------------------
  */

  if (user.value) {
    startHeartbeat(router)
  }
})

/*
|--------------------------------------------------------------------------
| Unmounted
|--------------------------------------------------------------------------
*/

onUnmounted(() => {
  window.removeEventListener("resize", updateWindowWidth)
})

/*
|--------------------------------------------------------------------------
| Responsive
|--------------------------------------------------------------------------
*/

const isMobile = computed(() => {
  return windowWidth.value <= 1024
})

/*
|--------------------------------------------------------------------------
| Page titles
|--------------------------------------------------------------------------
*/

const pageTitles = {
  "/user": "Home",
  "/user/": "Home",
  "/user/quizzes": "Assessments",
  "/user/records": "My Records",
  "/user/profile": "My Profile",
}

const currentPageTitle = computed(() => {
  return pageTitles[route.path] ?? "Home"
})

/*
|--------------------------------------------------------------------------
| Sidebar
|--------------------------------------------------------------------------
*/

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

const closeSidebar = () => {
  isSidebarOpen.value = false
}

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

const handleLogout = async () => {
  if (!confirm("Are you sure you want to logout?")) {
    return
  }

  try {
    // Make sure Sanctum has a valid CSRF token
    await api.get("/sanctum/csrf-cookie")

    // Logout from Laravel
    await api.post("/api/logout")

    // Clear Vue auth state
    clearUser()

    /*
    |--------------------------------------------------------------------------
    | Clear quiz-related local cache
    |--------------------------------------------------------------------------
    |
    | This is only done during logout.
    | We do NOT clear localStorage when UserLayout mounts.
    |
    */

    Object.keys(localStorage).forEach((key) => {
      if (
        key.startsWith("dash-quiz") ||
        key.startsWith("quiz")
      ) {
        localStorage.removeItem(key)
      }
    })

    // Go back to login
    await router.push("/")

  } catch (error) {
    console.error("Logout failed:", error)

    /*
    |--------------------------------------------------------------------------
    | Even if the backend logout fails,
    | clear the frontend auth state.
    |--------------------------------------------------------------------------
    */

    clearUser()

    await router.push("/")
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

.hideBox {
  display: none;
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
  padding: .2rem;
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
    padding: .2rem;
  }
}

/* ── SMALL MOBILE: ≤480px ── */
@media (max-width: 480px) {
  .user-main {
    padding: .2rem;
  }
}
</style>