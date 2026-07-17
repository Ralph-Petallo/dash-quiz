import { ref, computed } from "vue"
import axios from "axios"

const USER_CACHE_KEY = 'dash-quiz_user_cache'
const USER_CACHE_TTL = 1000 * 60 * 30 // 30 minutes

const user = ref(null)
const isLoading = ref(false)
const _fetched = ref(false)

let lastFetchTime = 0
const MIN_INTERVAL = 5000

//  safer cache reader
const getCachedUser = () => {
    try {
        const raw = localStorage.getItem(USER_CACHE_KEY)
        if (!raw) return null

        const cached = JSON.parse(raw)
        if (!cached?.ts || !cached?.value) return null

        // expired cache
        if (Date.now() - cached.ts > USER_CACHE_TTL) {
            localStorage.removeItem(USER_CACHE_KEY)
            return null
        }

        return cached.value
    } catch {
        localStorage.removeItem(USER_CACHE_KEY)
        return null
    }
}

// safer cache writer
const setCachedUser = (value) => {
    try {
        if (!value) {
            localStorage.removeItem(USER_CACHE_KEY)
            return
        }

        localStorage.setItem(USER_CACHE_KEY, JSON.stringify({
            ts: Date.now(),
            value,
        }))
    } catch {
        // ignore
    }
}

const verifySession = async () => {
    try {
        const { data } = await axios.get("/api/me")

        if (!data?.results) {
            return null
        }

        return data.results
    } catch {
        return null
    }
}

const fetchUser = async (force = false) => {
    const now = Date.now()

    // avoid spam calls
    if (!force && user.value && now - lastFetchTime < MIN_INTERVAL) {
        return user.value
    }

    // try cache first
    if (!force && user.value === null && !_fetched.value) {
        const cached = getCachedUser()

        if (cached) {
            user.value = cached
            _fetched.value = true
            lastFetchTime = now
            return user.value
        }
    }

    // already confirmed guest
    if (!force && _fetched.value && user.value === null) {
        return null
    }

    if (isLoading.value && !force) {
        return user.value
    }

    isLoading.value = true

    try {
        const sessionUser = await verifySession()

        user.value = sessionUser

        // cache only if logged in
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