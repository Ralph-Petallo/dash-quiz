<template>
  <div class="user-wrapper">

    <!-- Sidebar -->
    <UserSidebar :isSidebarOpen="isSidebarOpen" @logout="openLogout" @closeSidebar="closeSidebar" />

    <!-- Mobile overlay -->
    <div class="sidebar-overlay" :class="{ active: isSidebarOpen && isMobile }" @click="closeSidebar"></div>

    <!-- Main content -->
    <div class="main-wrapper">

      <!-- Topbar -->
      <UserTopbar :currentPageTitle="currentPageTitle" @toggleSidebar="toggleSidebar" />

      <!-- Page -->
      <main class="user-main">
        <router-view :class="{ active: isNavActive('assessments') }" />
      </main>

    </div>
  </div>

  <!-- Logout confirmation -->
  <Teleport to="body">
    <Transition name="logout">
      <div v-if="showLogoutModal" class="logout-backdrop" @click.self="closeLogout">
        <section ref="logoutDialog" class="logout-modal" role="alertdialog" aria-modal="true"
          aria-labelledby="logout-title" aria-describedby="logout-desc" tabindex="-1" @keydown.tab="trapFocus">
          <div class="logout-icon" aria-hidden="true">
            <i class="fas fa-right-from-bracket"></i>
          </div>

          <h2 id="logout-title">Log out of CSS Prep?</h2>
          <p id="logout-desc">
            You will need to sign in again to continue your assessments and view your records.
          </p>

          <div class="logout-actions">
            <button ref="cancelButton" class="btn btn-ghost" type="button" :disabled="isLoggingOut"
              @click="closeLogout">
              Stay signed in
            </button>

            <button class="btn btn-danger" type="button" :disabled="isLoggingOut" @click="confirmLogout">
              <span v-if="isLoggingOut" class="spinner" aria-hidden="true"></span>
              <i v-else class="fas fa-right-from-bracket" aria-hidden="true"></i>
              {{ isLoggingOut ? "Logging out…" : "Log out" }}
            </button>
          </div>

          <p class="sr-only" role="status" aria-live="polite">
            {{ isLoggingOut ? "Logging out, please wait." : "" }}
          </p>
        </section>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import UserSidebar from "@/components/UserSide/SideBar.vue"
import UserTopbar from "@/components/UserSide/TopBar.vue"

import { ref, computed, nextTick, watch, onMounted, onUnmounted } from "vue"
import { useRouter, useRoute } from "vue-router"

import { startHeartbeat } from "@/composables/heartbeat"
import { useUser } from "@/composables/useUser"

import api from "@/bootstrap"

const router = useRouter()
const route = useRoute()

const { user, fetchUser, clearUser } = useUser()

const isNavActive = (section) => route.meta?.navSection === section

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const isSidebarOpen = ref(false)
const windowWidth = ref(window.innerWidth)

const showLogoutModal = ref(false)
const isLoggingOut = ref(false)

const logoutDialog = ref(null)
const cancelButton = ref(null)
let lastFocused = null

const isMobile = computed(() => windowWidth.value <= 1024)

/*
|--------------------------------------------------------------------------
| Page titles
|--------------------------------------------------------------------------
*/

const pageTitles = {
  "/user": "Home",
  "/user/": "Home",
  "/user/records": "My Records",
  "/user/profile": "My Profile",
}

const currentPageTitle = computed(() => {
  if (route.meta?.navSection === "assessments") return "Competences"
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

const updateWindowWidth = () => {
  windowWidth.value = window.innerWidth

  if (windowWidth.value > 1024 && isSidebarOpen.value) {
    isSidebarOpen.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Logout modal
|--------------------------------------------------------------------------
*/

const openLogout = () => {
  showLogoutModal.value = true
}

const closeLogout = () => {
  if (isLoggingOut.value) return
  showLogoutModal.value = false
}

// Focus the safe action when the dialog opens, and return focus to the trigger on close.
watch(showLogoutModal, async (open) => {
  if (open) {
    lastFocused = document.activeElement
    await nextTick()
    cancelButton.value?.focus()
  } else if (lastFocused instanceof HTMLElement) {
    lastFocused.focus()
    lastFocused = null
  }
})

// Keeps Tab inside the dialog so keyboard users can't reach the page behind it.
const trapFocus = (event) => {
  const focusable = logoutDialog.value?.querySelectorAll(
    'button:not([disabled]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
  )

  if (!focusable?.length) return

  const first = focusable[0]
  const last = focusable[focusable.length - 1]

  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}

const handleEscape = (event) => {
  if (event.key === "Escape" && showLogoutModal.value) closeLogout()
}

const confirmLogout = async () => {
  if (isLoggingOut.value) return

  isLoggingOut.value = true

  try {
    await api.get("/sanctum/csrf-cookie")
    await api.post("/api/logout")
  } catch (error) {
    console.error("Logout failed:", error)
  } finally {
    clearUser()

    Object.keys(localStorage).forEach((key) => {
      if (key.startsWith("dash-quiz") || key.startsWith("quiz")) {
        localStorage.removeItem(key)
      }
    })

    isLoggingOut.value = false
    showLogoutModal.value = false
    await router.push("/")
  }
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(async () => {
  window.addEventListener("resize", updateWindowWidth)
  window.addEventListener("keydown", handleEscape)

  // Login normally sets the user. This covers page refreshes.
  if (!user.value) {
    await fetchUser()
  }

  if (user.value) {
    startHeartbeat(router)
  }
})

onUnmounted(() => {
  window.removeEventListener("resize", updateWindowWidth)
  window.removeEventListener("keydown", handleEscape)
})
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

/* ── MAIN AREA (sits beside the fixed sidebar) ── */

.main-wrapper {
  flex: 1;
  margin-left: 240px;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  min-width: 0;
  transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ── PAGE CONTENT ── */

.user-main {
  flex: 1;
  width: 100%;
  padding: 0.2rem;
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
  z-index: 999;
  background: rgba(0, 0, 0, 0.5);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-overlay.active {
  opacity: 1;
  pointer-events: all;
}

/* ── LOGOUT MODAL: Frosted Noir ── */

.logout-backdrop {
  --ink: #000000;
  --dim: #696969;
  --light: #d3d3d3;
  --danger: #c81e2e;
  --danger-soft: #fdf1f2;

  position: fixed;
  inset: 0;
  z-index: 2000;
  display: grid;
  place-items: center;
  padding: 1rem;
  background: rgba(0, 0, 0, 0.5);
}

.logout-modal {
  width: min(100%, 380px);
  padding: 2rem 1.75rem 1.5rem;
  border: 1px solid rgba(169, 169, 169, 1);
  border-radius: 20px;
  background: rgba(255, 255, 255, 1);
  color: var(--ink);
  text-align: center;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18), inset 0 1px 0 #ffffff;
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
}

.logout-modal:focus {
  outline: none;
}

.logout-icon {
  display: grid;
  place-items: center;
  width: 56px;
  height: 56px;
  margin: 0 auto 1.1rem;
  border: 1px solid var(--light);
  border-radius: 16px;
  background: #ffffff;
  color: var(--ink);
  font-size: 1.15rem;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
}

.logout-modal h2 {
  margin: 0 0 0.45rem;
  font-size: 1.2rem;
  font-weight: 750;
  letter-spacing: -0.03em;
  line-height: 1.3;
}

.logout-modal p {
  margin: 0;
  color: var(--dim);
  font-size: 0.875rem;
  line-height: 1.6;
}

.logout-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.6rem;
  margin-top: 1.6rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  min-height: 44px;
  padding: 0 1rem;
  border: 1px solid transparent;
  border-radius: 12px;
  font: inherit;
  font-size: 0.85rem;
  font-weight: 650;
  cursor: pointer;
  transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease, transform 0.12s ease;
}

.btn:active:not(:disabled) {
  transform: scale(0.98);
}

.btn:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.btn:focus-visible {
  outline: 2px solid var(--ink);
  outline-offset: 3px;
}

.btn-ghost {
  border-color: var(--light);
  background: #ffffff;
  color: var(--ink);
}

.btn-ghost:hover:not(:disabled) {
  border-color: var(--ink);
}

.btn-danger {
  background: var(--danger);
  color: var(--danger-soft);
}

.btn-danger:hover:not(:disabled) {
  background: #262626;
}

.spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.35);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.75s linear infinite;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
  border: 0;
}

/* Enter and leave transition */

.logout-enter-active,
.logout-leave-active {
  transition: opacity 0.2s ease;
}

.logout-enter-active .logout-modal,
.logout-leave-active .logout-modal {
  transition: transform 0.22s cubic-bezier(0.2, 0.7, 0.2, 1), opacity 0.2s ease;
}

.logout-enter-from,
.logout-leave-to {
  opacity: 0;
}

.logout-enter-from .logout-modal,
.logout-leave-to .logout-modal {
  opacity: 0;
  transform: translateY(10px) scale(0.98);
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
}

/* ── PHONE: ≤420px. Buttons stack, the logout action goes first ── */

@media (max-width: 420px) {
  .logout-modal {
    padding: 1.6rem 1.25rem 1.25rem;
    border-radius: 18px;
  }

  .logout-actions {
    grid-template-columns: 1fr;
  }

  .logout-actions .btn-danger {
    order: -1;
  }
}

@media (prefers-reduced-motion: reduce) {

  .logout-enter-active,
  .logout-leave-active,
  .logout-enter-active .logout-modal,
  .logout-leave-active .logout-modal,
  .spinner,
  .main-wrapper,
  .sidebar-overlay {
    transition: none !important;
    animation: none !important;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>