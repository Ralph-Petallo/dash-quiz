import { ref, computed } from "vue"
import axios from "axios"

const USER_CACHE_KEY = 'dash-quiz_user_cache'
const USER_CACHE_TTL = 1000 * 60 * 30 // 30 minutes

const user = ref(null)
const isLoading = ref(false)
const _fetched = ref(false)

let lastFetchTime = 0
const MIN_INTERVAL = 5000

const getCachedUser = () => {
    try {
        const cached = JSON.parse(localStorage.getItem(USER_CACHE_KEY))
        if (!cached || typeof cached !== 'object') return null
        if (Date.now() - cached.ts > USER_CACHE_TTL) {
            localStorage.removeItem(USER_CACHE_KEY)
            return null
        }
        return cached.value
    } catch {
        return null
    }
}

const setCachedUser = (value) => {
    try {
        localStorage.setItem(USER_CACHE_KEY, JSON.stringify({
            ts: Date.now(),
            value,
        }))
    } catch {
        // ignore storage failures
    }
}

const fetchUser = async (force = false) => {
    const now = Date.now()

    // Already cached and within cooldown
    if (!force && user.value && now - lastFetchTime < MIN_INTERVAL) {
        return user.value
    }

    // Try localStorage cache first
    if (!force && user.value === null && !_fetched.value) {
        const cached = getCachedUser()
        if (cached !== null) {
            user.value = cached
            _fetched.value = true
            lastFetchTime = now
            return user.value
        }
    }

    // Already know they're a guest — don't re-hit the server
    if (!force && _fetched.value && user.value === null) {
        return null
    }

    // Prevent concurrent fetches
    if (isLoading.value && !force) {
        return user.value
    }

    isLoading.value = true

    try {
        const { data } = await axios.get("/api/me")
        user.value = data.results
        setCachedUser(user.value)
        lastFetchTime = now
    } catch {
        user.value = null
        setCachedUser(null)
    } finally {
        isLoading.value = false
        _fetched.value = true
    }
}

const userFullName = computed(() =>
    `${user.value?.first_name ?? ""} ${user.value?.last_name ?? ""}`.trim()
)

// Builds path consistently — backend stores only filename
const userAvatar = computed(() =>
    `/storage/images/profiles/${user.value?.profile_photo ?? "default.png"}`
)

export function useUser() {
    return {
        user,
        isLoading,
        userFullName,
        userAvatar,
        fetchUser,
    }
}