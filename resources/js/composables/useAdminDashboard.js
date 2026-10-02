import { ref } from "vue"
import axios from "axios"

const POLL_INTERVAL = 15000
const MIN_INTERVAL = 5000

const stats = ref(null)
const isLoading = ref(false)

let lastFetchTime = 0
let poller = null

/* =========================================================
   NORMALIZE
========================================================= */

const normalizeStats = (data) => {
    return {
        total_users: data.total_users ?? 0,
        total_quizzes: data.total_quizzes ?? 0,
        total_assessments: data.total_assessments ?? 0,
        active_users: data.active_users ?? 0,

        logs: Array.isArray(data.logs)
            ? data.logs
            : [],

        top_users: Array.isArray(data.top_users)
            ? data.top_users
            : [],

        admin_name: data.admin_name ?? ""
    }
}

/* =========================================================
   FETCH DASHBOARD
========================================================= */

const fetchStats = async (force = false) => {
    const now = Date.now()

    /*
     * Prevent accidental duplicate requests.
     */
    if (
        !force &&
        stats.value &&
        now - lastFetchTime < MIN_INTERVAL
    ) {
        return stats.value
    }

    /*
     * Prevent multiple requests at the same time.
     */
    if (isLoading.value && !force) {
        return stats.value
    }

    isLoading.value = true

    try {
        const response = await axios.get(
            "/api/admin/dashboard"
        )

        if (response.data.status !== "success") {
            return stats.value
        }

        const newStats = normalizeStats(
            response.data.data
        )

        /*
         * Update only when the actual data changed.
         * This helps prevent unnecessary UI updates.
         */
        const changed =
            JSON.stringify(stats.value) !==
            JSON.stringify(newStats)

        if (changed) {
            stats.value = newStats
        }

        lastFetchTime = now

        return stats.value

    } catch (error) {

        console.error(
            "Dashboard error:",
            error
        )

        return stats.value

    } finally {

        isLoading.value = false
    }
}

/* =========================================================
   AUTO REFRESH
========================================================= */

const startAutoRefresh = () => {

    if (poller) {
        return
    }

    poller = setInterval(() => {
        fetchStats(true)
    }, POLL_INTERVAL)
}

/* =========================================================
   STOP AUTO REFRESH
========================================================= */

const stopAutoRefresh = () => {

    if (!poller) {
        return
    }

    clearInterval(poller)

    poller = null
}

/* =========================================================
   COMPOSABLE
========================================================= */

export function useAdminDashboard() {

    return {
        stats,
        isLoading,
        fetchStats,
        startAutoRefresh,
        stopAutoRefresh
    }
}