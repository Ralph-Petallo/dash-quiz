import { ref } from 'vue'
import axios from 'axios'

const POLL_INTERVAL = 15_000
const MIN_INTERVAL = 5_000

const stats = ref(null)
const isLoading = ref(false)

const assessmentLeaderboard = ref(null)
const isAssessmentLoading = ref(false)

let lastFetchTime = 0
let poller = null
let inFlightRequest = null

const normalizeLeaderboardGroups = groups => {
    if (!groups || typeof groups !== 'object' || Array.isArray(groups)) {
        return {}
    }

    return Object.fromEntries(
        Object.entries(groups).map(([key, leaderboard]) => [
            key,
            {
                coc_number: leaderboard?.coc_number ?? null,
                assessment_id: leaderboard?.assessment_id ?? null,
                label: leaderboard?.label ?? key.replaceAll('_', ' ').toUpperCase(),
                users: Array.isArray(leaderboard?.users)
                    ? leaderboard.users.map(user => ({
                        ...user,
                        completed_quizzes: Number(user?.completed_quizzes) || 0,
                        average_score: Number(user?.average_score) || 0,
                    }))
                    : [],
            },
        ])
    )
}

const normalizeLeaderboards = leaderboards => {
    if (!leaderboards || typeof leaderboards !== 'object') {
        return {
            coc: {},
            assessmentTypes: [],
        }
    }

    // New API shape:
    // { coc: {...}, assessment_types: [...] }
    if (
        Object.hasOwn(leaderboards, 'coc') ||
        Object.hasOwn(leaderboards, 'assessment_types')
    ) {
        return {
            coc: normalizeLeaderboardGroups(leaderboards.coc),
            assessmentTypes: Array.isArray(leaderboards.assessment_types)
                ? leaderboards.assessment_types
                : [],
        }
    }

    // Backward compatibility with the old flat COC leaderboard response.
    return {
        coc: normalizeLeaderboardGroups(leaderboards),
        assessmentTypes: [],
    }
}

const normalizeStats = data => ({
    total_users: Number(data?.total_users) || 0,
    total_quizzes: Number(data?.total_quizzes) || 0,
    total_assessments: Number(data?.total_assessments) || 0,
    active_users: Number(data?.active_users) || 0,
    logs: Array.isArray(data?.logs) ? data.logs : [],
    leaderboards: normalizeLeaderboards(data?.leaderboards),
    admin_name: data?.admin_name ?? '',
})

const fetchStats = async (force = false) => {
    const now = Date.now()

    if (inFlightRequest) {
        return inFlightRequest
    }

    if (!force && stats.value && now - lastFetchTime < MIN_INTERVAL) {
        return stats.value
    }

    isLoading.value = true

    inFlightRequest = (async () => {
        try {
            const response = await axios.get('/api/admin/dashboard')

            if (response.data?.status !== 'success') {
                return stats.value
            }

            const newStats = normalizeStats(response.data?.data)

            if (JSON.stringify(stats.value) !== JSON.stringify(newStats)) {
                stats.value = newStats
            }

            lastFetchTime = Date.now()
            return stats.value
        } catch (error) {
            console.error('Dashboard error:', error)
            return stats.value
        } finally {
            isLoading.value = false
            inFlightRequest = null
        }
    })()

    return inFlightRequest
}

const fetchAssessmentLeaderboard = async type => {
    if (!type) {
        assessmentLeaderboard.value = null
        return null
    }

    isAssessmentLoading.value = true

    try {
        const response = await axios.get(
            `/api/admin/dashboard/assessment-leaderboard/${encodeURIComponent(type)}`
        )

        if (response.data?.status !== 'success') {
            return null
        }

        const data = response.data?.data

        console.log(data)

        assessmentLeaderboard.value = {
            type: data?.type ?? type,
            users: Array.isArray(data?.users)
                ? data.users.map(user => ({
                    ...user,
                    completed_quizzes: Number(user?.completed_quizzes) || 0,
                    average_score: Number(user?.average_score) || 0,
                }))
                : [],
        }

        return assessmentLeaderboard.value
    } catch (error) {
        console.error('Assessment leaderboard error:', error)
        return null
    } finally {
        isAssessmentLoading.value = false
    }
}

const startAutoRefresh = () => {
    if (poller) {
        return
    }

    poller = setInterval(() => {
        fetchStats(true)
    }, POLL_INTERVAL)
}

const stopAutoRefresh = () => {
    if (!poller) {
        return
    }

    clearInterval(poller)
    poller = null
}

export function useAdminDashboard() {
    return {
        stats,
        isLoading,
        assessmentLeaderboard,
        isAssessmentLoading,
        fetchStats,
        fetchAssessmentLeaderboard,
        startAutoRefresh,
        stopAutoRefresh,
    }
}