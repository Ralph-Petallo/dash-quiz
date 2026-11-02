import { ref, computed } from "vue"
import api from "../bootstrap"

const user = ref(null)
const isLoading = ref(false)
const fetched = ref(false)

const authChannel = new BroadcastChannel("auth_channel")

authChannel.onmessage = (event) => {
    const { type, user: userData } = event.data || {}

    if (type === "LOGIN") {
        user.value = userData ?? null
        fetched.value = true
    } else if (type === "LOGOUT") {
        user.value = null
        fetched.value = false
    }
}

// Stores the user directly, e.g. straight after a successful login.
const setUser = (value) => {
    user.value = value ?? null
    fetched.value = true

    if (value) {
        authChannel.postMessage({ type: "LOGIN", user: value })
    }
}

// /api/me may return the user as `user`, `results`, or the object itself.
const extractUser = (data) => {
    if (!data) return null
    if (data.user) return data.user
    if (data.results) return data.results
    if (data.id) return data
    return null
}

let fetchPromise = null

const fetchUser = async (force = false) => {
    if (fetched.value && !force) return user.value
    if (fetchPromise && !force) return fetchPromise

    isLoading.value = true

    fetchPromise = (async () => {
        try {
            const { data } = await api.get("/api/me")
            const userData = extractUser(data)

            // Never replace a known user with an empty response.
            if (userData) {
                user.value = userData
                fetched.value = true
            }

            return userData ?? user.value
        } catch (error) {
            // 401 means logged out.
            if (error?.response?.status === 401) {
                user.value = null
                fetched.value = true
                return null
            }

            // Any other error: keep the current user and allow a retry later.
            console.error("fetchUser failed:", error)
            fetched.value = false
            return user.value
        } finally {
            isLoading.value = false
            fetchPromise = null
        }
    })()

    return fetchPromise
}

const clearUser = () => {
    user.value = null
    fetched.value = false
    authChannel.postMessage({ type: "LOGOUT" })
}

const userFullName = computed(() =>
    `${user.value?.first_name ?? ""} ${user.value?.last_name ?? ""}`.trim()
)

const userAvatar = computed(() =>
    user.value?.profile_photo
        ? `/storage/images/profiles/${user.value.profile_photo}`
        : "/storage/images/profiles/default.png"
)

const totalAssessments = computed(() =>
    Number(user.value?.statistics?.total_attempts ?? 0)
)

const memberSince = computed(() => user.value?.created_at ?? null)

export function useUser() {
    return {
        user,
        isLoading,
        userFullName,
        userAvatar,
        memberSince,
        totalAssessments,
        fetchUser,
        clearUser,
        setUser,
    }
}