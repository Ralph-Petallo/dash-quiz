import { ref } from "vue"
import axios from "axios"

const CACHE_KEY = "dash-quiz_admin_dashboard"
const CACHE_TTL = 1000 * 60 * 10 // 10 minutes
const POLL_INTERVAL = 15000 // 15 seconds auto refresh

const stats = ref(null)
const isLoading = ref(false)

let lastFetchTime = 0
let poller = null

const MIN_INTERVAL = 5000

/* =========================
   CACHE
========================= */
const getCachedStats = () => {
    try {
        const cached = JSON.parse(localStorage.getItem(CACHE_KEY))

        if (!cached || typeof cached !== "object") return null

        if (Date.now() - cached.ts > CACHE_TTL) {
            localStorage.removeItem(CACHE_KEY)
            return null
        }

        return cached.value
    } catch {
        return null
    }
}

const setCachedStats = (value) => {
    try {
        localStorage.setItem(CACHE_KEY, JSON.stringify({
            ts: Date.now(),
            value
        }))
    } catch { }
}

/* =========================
   NORMALIZER (IMPORTANT)
========================= */
const normalizeStats = (data) => {
    return {
        total_users: data.total_users ?? 0,
        total_quizzes: data.total_quizzes ?? 0,
        active_users: data.active_users ?? 0,
        logs: data.logs ?? [],
        top_users: data.top_users ?? [],
        admin_name: data.admin_name ?? ""
    }
}

/* =========================
   FETCH
========================= */
const fetchStats = async (force = false) => {
    const now = Date.now()

    if (!force && stats.value && now - lastFetchTime < MIN_INTERVAL) {
        return stats.value
    }

    if (!force && !stats.value) {
        const cached = getCachedStats()
        if (cached) {
            stats.value = cached
            lastFetchTime = now
        }
    }

    if (isLoading.value && !force) return stats.value

    isLoading.value = true

    try {
        const res = await axios.get("/api/admin/dashboard")

        if (res.data.status !== "success") return stats.value

        const newStats = normalizeStats(res.data.data)

        /* 🔥 smart update (prevents UI flicker) */
        const changed =
            JSON.stringify(stats.value) !== JSON.stringify(newStats)

        if (changed) {
            stats.value = newStats
            setCachedStats(newStats)
        }

        lastFetchTime = now

        return stats.value

    } catch (err) {
        console.error("Dashboard error:", err)

        if (!stats.value) {
            stats.value = getCachedStats()
        }

        return stats.value

    } finally {
        isLoading.value = false
    }
}

/* =========================
   AUTO REFRESH (NEW DATA)
========================= */
const startAutoRefresh = () => {
    if (poller) return

    poller = setInterval(() => {
        fetchStats(true) // force refresh
    }, POLL_INTERVAL)
}

const stopAutoRefresh = () => {
    if (poller) {
        clearInterval(poller)
        poller = null
    }
}

/* =========================
   EXPORT
========================= */
export function useAdminDashboard() {
    return {
        stats,
        isLoading,
        fetchStats,
        startAutoRefresh,
        stopAutoRefresh
    }
}