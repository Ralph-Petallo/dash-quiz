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

const CACHE_TTL_MS = 60 * 1000

let dashboardCache = {
    userId: null,
    expiresAt: 0,
    data: null,
}

let leaderboardCache = {
    key: null,
    expiresAt: 0,
    data: null,
    meta: null,
}

const beginRequest = () => {
    activeRequests.value += 1
}

const endRequest = () => {
    activeRequests.value = Math.max(0, activeRequests.value - 1)
}

const normalizeLeaderboard = (items, currentUserId) => {
    const records = Array.isArray(items) ? items : []

    return records.map((item) => {
        const score = Number(item.score ?? 0)
        const totalQuestions = Number(item.total_questions ?? 0)
        const percentage = Number(item.percentage ?? 0)
        const passingScore = Number(item.passing_score ?? 70)

        return {
            ...item,
            id: Number(item.id),
            record_id: Number(item.record_id ?? item.id),
            user_id: Number(item.user_id),
            quiz_id: Number(item.quiz_id),
            score: Number.isFinite(score) ? score : 0,
            correct_answers: Number(item.correct_answers ?? score),
            total_questions: Number.isFinite(totalQuestions)
                ? totalQuestions
                : 0,
            percentage: Number.isFinite(percentage)
                ? percentage
                : 0,
            passing_score: Number.isFinite(passingScore)
                ? passingScore
                : 70,
            attempt_count: Number(item.attempt_count ?? 0),
            elapsed_time:
                item.elapsed_time !== null &&
                    item.elapsed_time !== undefined
                    ? Number(item.elapsed_time)
                    : null,
            isYou:
                currentUserId !== null &&
                Number(item.user_id) === Number(currentUserId),
            displayName:
                item.name?.trim() || "Unknown User",
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
                expiresAt: Date.now() + CACHE_TTL_MS,
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

    const getLeaderBoard = async (options = {}) => {
        const currentUserId = Number(user.value?.id ?? 0) || null

        const params = {
            limit: options.limit ?? 200,
        }

        if (options.category) {
            params.category = options.category
        }

        if (options.quiz_id) {
            params.quiz_id = options.quiz_id
        }

        const force = Boolean(options.force)
        const cacheKey = JSON.stringify({
            user_id: currentUserId,
            ...params,
        })

        leaderboardError.value = null

        if (
            !force &&
            leaderboardCache.data &&
            leaderboardCache.key === cacheKey &&
            leaderboardCache.expiresAt > Date.now()
        ) {
            leaderboard.value = normalizeLeaderboard(
                leaderboardCache.data,
                currentUserId
            )
            leaderboardMeta.value = leaderboardCache.meta ?? {}
            return leaderboard.value
        }

        beginRequest()

        try {
            const { data } = await api.get(
                "/api/dashboard/leaderboard",
                { params }
            )

            const rawRecords = Array.isArray(data?.data)
                ? data.data
                : []

            leaderboard.value = normalizeLeaderboard(
                rawRecords,
                currentUserId
            )

            leaderboardMeta.value = data?.meta ?? {}

            leaderboardCache = {
                key: cacheKey,
                expiresAt: Date.now() + CACHE_TTL_MS,
                data: rawRecords,
                meta: data?.meta ?? {},
            }

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
        dashboardCache = {
            userId: null,
            expiresAt: 0,
            data: null,
        }

        leaderboardCache = {
            key: null,
            expiresAt: 0,
            data: null,
            meta: null,
        }
    }

    const updateProfile = async (formData) => {
        beginRequest()

        try {
            const { data } = await api.put(
                "/api/profile/update",
                formData
            )

            if (data.data && user.value) {
                Object.assign(user.value, {
                    first_name: data.data.first_name,
                    last_name: data.data.last_name,
                    email: data.data.email,
                    profile_photo:
                        data.data.profile_photo ??
                        user.value.profile_photo,
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
