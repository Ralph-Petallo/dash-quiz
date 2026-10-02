<template>
    <div class="admin-wrapper">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" :class="{ active: isSidebarOpen && isMobile }" @click="closeSidebar" />

        <!-- Sidebar -->
        <AdminSidebar :isSidebarOpen="isSidebarOpen" @logout="handleLogout" @closeSidebar="closeSidebar" />

        <!-- Main Content -->
        <div class="main-wrapper">
            <AdminTopbar :currentPageTitle="currentPageTitle" @toggleSidebar="toggleSidebar" />

            <main class="admin-main">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup>
import AdminSidebar from '@/components/AdminSide/AdminSideBar.vue'
import AdminTopbar from '@/components/AdminSide/AdminTopBar.vue'
import axios from 'axios'
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUser } from '@/composables/useUser'

const router = useRouter()
const route = useRoute()

const { user } = useUser()

// ── SIDEBAR STATE ──
const isSidebarOpen = ref(false)
const windowWidth = ref(window.innerWidth)

const isMobile = computed(() => windowWidth.value <= 768)

// ── RESPONSIVE WINDOW WIDTH ──
const updateWindowWidth = () => {
    windowWidth.value = window.innerWidth

    if (windowWidth.value > 1024) {
        isSidebarOpen.value = false
    }
}

onMounted(() => {
    window.addEventListener('resize', updateWindowWidth)
})

onUnmounted(() => {
    window.removeEventListener('resize', updateWindowWidth)
})

// ── PAGE TITLES ──
const pageTitles = {
    '/admin': 'Dashboard',
    '/admin/dashboard': 'Dashboard',
    '/admin/manage-quizzes': 'Assessments',
    '/admin/users': 'Users',
    '/admin/records': 'Records',
    '/admin/settings': 'Admin Settings',
}

const currentPageTitle = computed(() => {
    return pageTitles[route.path] ?? 'Dashboard'
})

// ── SIDEBAR CONTROLS ──
const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value
}

const closeSidebar = () => {
    isSidebarOpen.value = false
}

// ── LOGOUT ──
const handleLogout = async () => {
    if (!confirm('Are you sure you want to logout?')) return

    try {
        await axios.post('/api/logout')

        // Clear frontend authentication state
        user.value = null

        // Clear stored tokens/session data
        localStorage.clear()

        // Remove global Axios authorization header
        delete axios.defaults.headers.common['Authorization']

        router.replace('/')
    } catch (err) {
        console.error('Logout failed:', err)

        // Fallback logout
        user.value = null
        localStorage.clear()

        delete axios.defaults.headers.common['Authorization']

        router.replace('/')
    }
}

// ── BODY SCROLL LOCK ──
watch(
    [isSidebarOpen, isMobile],
    ([sidebarOpen, mobile]) => {
        document.body.classList.toggle(
            'sidebar-open',
            sidebarOpen && mobile
        )
    },
    { immediate: true }
)

// ── CLEANUP ──
onUnmounted(() => {
    document.body.classList.remove('sidebar-open')
})
</script>

<style scoped>
/* ── GLOBAL BOX SIZING ── */
*,
*::before,
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

/* ── ROOT WRAPPER ── */
.admin-wrapper {
    display: flex;
    width: 100%;
    min-height: 100vh;
    background: #f8fafc;

    position: relative;
    isolation: isolate;

    overflow-x: clip;
}

/* ── MAIN AREA ── */
.main-wrapper {
    flex: 1;
    margin-left: 240px;

    display: flex;
    flex-direction: column;

    min-height: 100vh;
    min-width: 0;

    position: relative;

    transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ── PAGE CONTENT ── */
.admin-main {
    flex: 1;
    width: 100%;
    min-width: 0;

    padding: clamp(1rem, 3vw, 2rem);

    position: relative;

    overflow-x: clip;
}

.admin-main>* {
    width: 100%;
    min-width: 0;
}

/* ── SIDEBAR OVERLAY ── */
.sidebar-overlay {
    position: fixed;
    inset: 0;

    background: rgba(0, 0, 0, 0.5);

    opacity: 0;
    visibility: hidden;
    pointer-events: none;

    transition:
        opacity 0.3s ease,
        visibility 0.3s ease;

    z-index: 100;
}

.sidebar-overlay.active {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
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

    .admin-main {
        padding: clamp(0.875rem, 4vw, 1.5rem);
    }
}

/* ── SMALL MOBILE ── */
@media (max-width: 480px) {
    .admin-main {
        padding: 0.875rem;
    }
}

/* ── BODY SCROLL LOCK ── */
:global(body.sidebar-open) {
    overflow: hidden;
}
</style>