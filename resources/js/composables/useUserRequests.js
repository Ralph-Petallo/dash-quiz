import { computed, ref } from "vue"
import { useUser } from "./useUser"
import api from "../bootstrap"

const makeEmptyDashboard = () => ({
    summary: {
        average_score: 0,
        best_score: 0,
        passed: 0,
        total_attempts: 0,
    },
    recent_activity: [],
})

const dashboard = ref(makeEmptyDashboard())
const dashboardError = ref(null)

const leaderboard = ref([])
const leaderboardMeta = ref({})
const leaderboardError = ref(null)

const activeRequests = ref(0)
const isLoading = computed(() => activeRequests.value > 0)

const DASHBOARD_TTL_MS = 60 * 1000
const LEADERBOARD_TTL_MS = 60 * 1000
const leaderboardCache = new Map()

let dashboardCache = {
    userId: null,
    expiresAt: 0,
    data: null,
}

const beginRequest = () => {
    activeRequests.value += 1
}

const endRequest = () => {
    activeRequests.value = Math.max(0, activeRequests.value - 1)
}

const normalizeLeaderboard = (items) => {
    const records = Array.isArray(items) ? items : []

    return records.map((item, index) => {
        const score = Number(item.score ?? 0)
        const percentage = Number(item.percentage ?? 0)

        return {
            id: Number(item.rank ?? index + 1),
            rank: Number(item.rank ?? index + 1),
            isYou: Boolean(item.is_you),
            name: item.name,
            profile_photo: item.profile_photo,
            score: Number.isFinite(score) ? score : 0,
            percentage: Number.isFinite(percentage) ? percentage : 0,
            passed: Boolean(item.passed),
            displayName: item.name?.trim() || "Unknown User",
        }
    })
}

export function useUserRequests() {
    const { user } = useUser()

    const getDashboard = async (force = false) => {
        const currentUserId = Number(user.value?.id ?? 0) || null
        const now = Date.now()

        dashboardError.value = null

        if (
            !force &&
            dashboardCache.data &&
            dashboardCache.userId === currentUserId &&
            dashboardCache.expiresAt > now
        ) {
            dashboard.value = dashboardCache.data
            return dashboard.value
        }

        beginRequest()

        try {
            const { data } = await api.get("/api/dashboard/overview")

            const nextDashboard = data?.data ?? makeEmptyDashboard()

            dashboard.value = nextDashboard
            dashboardCache = {
                userId: currentUserId,
                expiresAt: Date.now() + DASHBOARD_TTL_MS,
                data: nextDashboard,
            }

            return dashboard.value
        } catch (error) {
            dashboardError.value =
                error?.response?.data?.message ||
                error?.message ||
                "Unable to load dashboard."

            console.error("Failed to load dashboard:", error)
            throw error
        } finally {
            endRequest()
        }
    }

    /**
     * Load the leaderboard for one assessment.
     * Call with no assessment_id to receive only the filter options.
     */
    const getLeaderBoard = async (options = {}) => {
        const currentUserId = Number(user.value?.id ?? 0) || null

        const params = { limit: options.limit ?? 100 }

        if (options.assessment_id) {
            params.assessment_id = options.assessment_id
        }

        const cacheKey = JSON.stringify({ user_id: currentUserId, ...params })

        leaderboardError.value = null

        const cached = leaderboardCache.get(cacheKey)

        if (!options.force && cached && cached.expiresAt > Date.now()) {
            leaderboard.value = normalizeLeaderboard(cached.data)
            leaderboardMeta.value = cached.meta
            return leaderboard.value
        }

        beginRequest()

        try {
            const { data } = await api.get("/api/dashboard/leaderboard", { params })

            const rawRecords = Array.isArray(data?.data) ? data.data : []
            const meta = data?.meta ?? {}

            leaderboard.value = normalizeLeaderboard(rawRecords)
            leaderboardMeta.value = meta

            leaderboardCache.set(cacheKey, {
                expiresAt: Date.now() + LEADERBOARD_TTL_MS,
                data: rawRecords,
                meta,
            })

            return leaderboard.value
        } catch (error) {
            leaderboardError.value =
                error?.response?.data?.message ||
                error?.message ||
                "Unable to load leaderboard."

            console.error("Failed to load leaderboard:", error)
            throw error
        } finally {
            endRequest()
        }
    }

    const clearDashboardCache = () => {
        dashboardCache = { userId: null, expiresAt: 0, data: null }
        leaderboardCache.clear()
    }

    const updateProfile = async (formData) => {
        beginRequest()

        try {
            const { data } = await api.put("/api/profile/update", formData)

            if (data.data && user.value) {
                Object.assign(user.value, {
                    first_name: data.data.first_name,
                    last_name: data.data.last_name,
                    email: data.data.email,
                    profile_photo:
                        data.data.profile_photo ?? user.value.profile_photo,
                })
            }

            clearDashboardCache()

            return data
        } catch (error) {
            console.error("Profile update failed:", error)
            throw error
        } finally {
            endRequest()
        }
    }

    return {
        dashboard,
        dashboardError,
        getDashboard,
        clearDashboardCache,

        leaderboard,
        leaderboardMeta,
        leaderboardError,
        getLeaderBoard,

        isLoading,
        updateProfile,
    }
}