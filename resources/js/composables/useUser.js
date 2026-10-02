import { ref, computed } from "vue"
import api from "../bootstrap"

const user = ref(null)
const isLoading = ref(false)
const fetched = ref(false)

// Create and set up broadcast channel immediately
const authChannel = new BroadcastChannel("auth_channel")

// Listen for auth changes from OTHER tabs
authChannel.onmessage = (event) => {
    const { type, user: userData } = event.data

    console.log("📡 Received broadcast:", type, userData)

    if (type === "LOGIN") {
        user.value = userData
        fetched.value = true
    } else if (type === "LOGOUT") {
        user.value = null
        fetched.value = false
    }
}

const setUser = (value) => {
    user.value = value
    fetched.value = true

    if (value) {
        console.log("📡 Broadcasting LOGIN to other tabs")
        authChannel.postMessage({ type: "LOGIN", user: value })
    }
}

let fetchPromise = null

const fetchUser = async (force = false) => {
    // If already fetched and not forcing, return cached
    if (fetched.value && !force) {
        console.log("📦 Using cached user:", user.value)
        return user.value
    }

    // If already fetching and not forcing, wait for existing promise
    if (fetchPromise && !force) {
        return fetchPromise
    }

    console.log("🌐 Fetching user from API...")
    isLoading.value = true

    fetchPromise = api.get("/api/me")
        .then(({ data }) => {
            const userData = data?.results ?? data?.user ?? null
            user.value = userData
            fetched.value = true

            console.log("✅ Fetch successful:", userData)
            return userData
        })
        .catch((error) => {
            console.log("❌ Fetch failed:", error.message)
            user.value = null
            fetched.value = true
            return null
        })
        .finally(() => {
            isLoading.value = false
            fetchPromise = null
        })

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

const totalQuizzes = computed(() => {
    return user.value.statistics.quizzes_taken
})

export function useUser() {
    return {
        user,
        isLoading,
        userFullName,
        userAvatar,
        totalQuizzes,
        fetchUser,
        clearUser,
        setUser,
    }
}