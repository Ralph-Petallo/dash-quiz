import { computed, ref } from 'vue'
import { useUser } from './useUser'
import api from '../bootstrap'

// Keep in sync with the periods accepted by the backend.
export const LEADERBOARD_PERIODS = ['today', '3d', '7d', 'all']

const DEFAULT_PERIOD = '7d'
const DEFAULT_LIMIT = 100
const DASHBOARD_TTL_MS = 60 * 1000
const LEADERBOARD_TTL_MS = 60 * 1000

const makeEmptyDashboard = () => ({
    summary: {
        average_score: 0,
        best_score: 0,
        passed: 0,
        total_attempts: 0,
    },
    recent_activity: [],
})

/* =========================================================
   SHARED STATE
========================================================= */

const dashboard = ref(makeEmptyDashboard())
const dashboardError = ref(null)

const leaderboard = ref([])
const leaderboardMeta = ref({})
const leaderboardError = ref(null)

const activeRequests = ref(0)
const isLoading = computed(() => activeRequests.value > 0)

const leaderboardCache = new Map()

let dashboardCache = {
    userId: null,
    expiresAt: 0,
    data: null,
}

// Only the most recently started leaderboard request may update shared state.
let leaderboardSeq = 0
let dashboardSeq = 0

const beginRequest = () => {
    activeRequests.value += 1
}

const endRequest = () => {
    activeRequests.value = Math.max(0, activeRequests.value - 1)
}

const normalizeDashboard = (data) => {
    const source = data && typeof data === 'object' ? data : {}
    const summary = source.summary ?? {}

    return {
        summary: {
            average_score: Number(summary.average_score) || 0,
            best_score: Number(summary.best_score) || 0,
            passed: Number(summary.passed) || 0,
            total_attempts: Number(summary.total_attempts) || 0,
        },
        recent_activity: Array.isArray(source.recent_activity)
            ? source.recent_activity
            : [],
    }
}

const normalizeLeaderboard = (items) => {
    if (!Array.isArray(items)) return []

    return items.map((item, index) => {
        const rank = Number(item?.rank ?? index + 1)
        const score = Number(item?.score ?? 0)
        const percentage = Number(item?.percentage ?? 0)
        const name = typeof item?.name === 'string' ? item.name.trim() : ''

        return {
            id: Number.isFinite(rank) ? rank : index + 1,
            rank: Number.isFinite(rank) ? rank : index + 1,
            is_you: Boolean(item?.is_you),
            isYou: Boolean(item?.is_you),
            name: name || 'Unknown User',
            displayName: name || 'Unknown User',
            profile_photo: item?.profile_photo ?? null,
            score: Number.isFinite(score) ? score : 0,
            percentage: Number.isFinite(percentage) ? percentage : 0,
            passed: Boolean(item?.passed),
        }
    })
}

const applyLeaderboard = (entry, seq) => {
    if (seq !== leaderboardSeq) return

    leaderboard.value = normalizeLeaderboard(entry.data)
    leaderboardMeta.value = entry.meta ?? {}
}

/* =========================================================
   COMPOSABLE
========================================================= */

export function useUserRequests() {
    const { user } = useUser()

    const getDashboard = async (force = false) => {
        const currentUserId = Number(user.value?.id ?? 0) || null
        const now = Date.now()
        const seq = ++dashboardSeq

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
            const { data } = await api.get('/api/dashboard/overview')

            // Ignore an older response if a newer dashboard request has started.
            if (seq !== dashboardSeq) return dashboard.value

            const nextDashboard = normalizeDashboard(data?.data)

            dashboard.value = nextDashboard
            dashboardCache = {
                userId: currentUserId,
                expiresAt: Date.now() + DASHBOARD_TTL_MS,
                data: nextDashboard,
            }

            return dashboard.value
        } catch (error) {
            if (seq === dashboardSeq) {
                dashboardError.value =
                    error?.response?.data?.message ||
                    error?.message ||
                    'Unable to load dashboard.'
            }

            console.error('Failed to load dashboard:', error)
            throw error
        } finally {
            endRequest()
        }
    }

    /**
     * Loads leaderboard data and filter options.
     *
     * Options:
     *   assessment_id  assessment to rank; omit it to retrieve filter options
     *   period         'today' | '3d' | '7d' | 'all'
     *   limit          number of learners, between 1 and 200
     *   force          skip cached response
     */
    const getLeaderBoard = async (options = {}) => {
        const currentUserId = Number(user.value?.id ?? 0) || null

        const period = LEADERBOARD_PERIODS.includes(options.period)
            ? options.period
            : DEFAULT_PERIOD

        const requestedLimit = Number(options.limit ?? DEFAULT_LIMIT)
        const limit = Number.isFinite(requestedLimit)
            ? Math.min(200, Math.max(1, Math.floor(requestedLimit)))
            : DEFAULT_LIMIT

        const params = { limit, period }

        if (options.assessment_id !== null &&
            options.assessment_id !== undefined &&
            options.assessment_id !== '') {
            params.assessment_id = options.assessment_id
        }

        const cacheKey = JSON.stringify({
            user_id: currentUserId,
            ...params,
        })

        const seq = ++leaderboardSeq
        leaderboardError.value = null

        const cached = leaderboardCache.get(cacheKey)

        if (!options.force && cached && cached.expiresAt > Date.now()) {
            applyLeaderboard(cached, seq)
            return leaderboard.value
        }

        beginRequest()

        try {
            const { data } = await api.get('/api/dashboard/leaderboard', {
                params,
            })

            const entry = {
                expiresAt: Date.now() + LEADERBOARD_TTL_MS,
                data: Array.isArray(data?.data) ? data.data : [],
                meta: data?.meta ?? {},
            }

            leaderboardCache.set(cacheKey, entry)
            applyLeaderboard(entry, seq)

            return leaderboard.value
        } catch (error) {
            if (seq === leaderboardSeq) {
                leaderboardError.value =
                    error?.response?.data?.message ||
                    error?.message ||
                    'Unable to load leaderboard.'
            }

            console.error('Failed to load leaderboard:', error)
            throw error
        } finally {
            endRequest()
        }
    }

    const clearDashboardCache = () => {
        dashboardSeq++
        leaderboardSeq++

        dashboardCache = {
            userId: null,
            expiresAt: 0,
            data: null,
        }

        leaderboardCache.clear()
    }

    // Call after an assessment submission or change that affects dashboard data.
    const afterSubmit = async () => {
        clearDashboardCache()
        await getDashboard(true)
    }

    const updateProfile = async (formData) => {
        beginRequest()

        try {
            const { data } = await api.put('/api/profile/update', formData)

            const updatedUser = data?.data ?? data?.results

            if (updatedUser && user.value) {
                Object.assign(user.value, {
                    first_name: updatedUser.first_name ?? user.value.first_name,
                    last_name: updatedUser.last_name ?? user.value.last_name,
                    email: updatedUser.email ?? user.value.email,
                    profile_photo:
                        updatedUser.profile_photo ?? user.value.profile_photo,
                })
            }

            clearDashboardCache()

            return data
        } catch (error) {
            console.error('Profile update failed:', error)
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
        afterSubmit,

        leaderboard,
        leaderboardMeta,
        leaderboardError,
        getLeaderBoard,

        isLoading,
        updateProfile,
    }
}

/**
 * Clear shared state on logout so the next user cannot see the previous
 * user's dashboard or leaderboard.
 */
export const resetUserRequests = () => {
    dashboardSeq++
    leaderboardSeq++

    dashboard.value = makeEmptyDashboard()
    dashboardError.value = null

    leaderboard.value = []
    leaderboardMeta.value = {}
    leaderboardError.value = null

    dashboardCache = {
        userId: null,
        expiresAt: 0,
        data: null,
    }

    leaderboardCache.clear()
}