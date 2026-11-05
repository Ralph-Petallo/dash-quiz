import { ref, onBeforeUnmount } from "vue"
import { useRouter, useRoute } from "vue-router"
import { useUser } from "./useUser"
import api from "../bootstrap"

const MAX_ATTEMPTS = 3
const LOCK_MS = 30000

export function useHandleLogin() {
    const loading = ref(false)
    const errors = ref({})
    const generalError = ref("")
    const isLocked = ref(false)
    const showPassword = ref(false)
    const attempts = ref(0)

    // Shown when the password is correct but the email is not confirmed yet.
    const needsVerification = ref(false)
    const verifyEmail = ref("")
    const resending = ref(false)
    const resendMessage = ref("")

    const router = useRouter()
    const route = useRoute()
    const { setUser, fetchUser } = useUser()

    let lockTimer = null

    const homeFor = (user) =>
        user?.role === "admin" ? { name: "admin-dashboard" } : { name: "user-home" }

    const clearVerificationNotice = () => {
        needsVerification.value = false
        verifyEmail.value = ""
        resendMessage.value = ""
    }

    const lockAccount = () => {
        isLocked.value = true
        generalError.value = "Too many failed attempts. Please try again in 30 seconds."

        clearTimeout(lockTimer)
        lockTimer = setTimeout(() => {
            attempts.value = 0
            isLocked.value = false
            generalError.value = ""
        }, LOCK_MS)
    }

    /**
     * Returns true when the learner is signed in, false otherwise.
     * Failures are reported through errors, generalError, or the verification notice.
     */
    const handleLogin = async (email, password) => {
        errors.value = {}
        generalError.value = ""
        clearVerificationNotice()

        if (isLocked.value) {
            generalError.value = "Too many failed attempts. Please try again later."
            return false
        }

        loading.value = true

        try {
            // Sets the Sanctum session and XSRF cookies.
            await api.get("/sanctum/csrf-cookie")

            const { data } = await api.post("/api/login", { email, password })

            const user = data?.user ?? data?.results ?? null

            if (!user) {
                throw new Error("Login response did not include a user.")
            }

            attempts.value = 0
            setUser(user)

            // Replace with the full profile (photo, stats, dates) before the page opens.
            await fetchUser(true)

            const redirect = route.query.redirect

            if (typeof redirect === "string" && redirect.startsWith("/")) {
                await router.push(redirect)
            } else {
                await router.push(homeFor(user))
            }

            return true
        } catch (error) {
            console.error("Login failed:", {
                url: error.config?.url,
                status: error.response?.status,
                data: error.response?.data,
            })

            const status = error.response?.status
            const body = error.response?.data

            if (status === 403) {
                // The backend checks the password before this status, so the email is known to be the learner's.
                needsVerification.value = true
                verifyEmail.value = body?.email ?? email
                return false
            }

            if (status === 422) {
                errors.value = body?.errors ?? {}
                generalError.value = body?.message ?? "Please check your information."
            } else if (status === 401) {
                attempts.value++
                generalError.value = body?.message ?? "Invalid email or password."

                if (attempts.value >= MAX_ATTEMPTS) lockAccount()
            } else if (status === 419) {
                generalError.value = "Your session expired. Please try again."
            } else if (status >= 500) {
                generalError.value = "Server error. Please try again later."
            } else {
                generalError.value =
                    error.message || "Unable to login. Please check your connection and try again."
            }

            return false
        } finally {
            loading.value = false
        }
    }

    const resendVerification = async () => {
        if (!verifyEmail.value || resending.value) return

        resending.value = true
        resendMessage.value = ""

        try {
            const { data } = await api.post("/api/email/resend", { email: verifyEmail.value })
            resendMessage.value =
                data?.message ?? "If the account needs verification, a new link has been sent."
        } catch (error) {
            resendMessage.value =
                error.response?.status === 429
                    ? "Please wait a few minutes before requesting another link."
                    : "Unable to send the link. Please try again."
        } finally {
            resending.value = false
        }
    }

    const togglePassword = () => {
        showPassword.value = !showPassword.value
    }

    // Relative path, so it works on localhost and on the deployed domain.
    const authGoogle = () => {
        location.href = "/auth/google/redirect"
    }

    onBeforeUnmount(() => clearTimeout(lockTimer))

    return {
        loading,
        errors,
        generalError,
        isLocked,
        showPassword,
        attempts,

        needsVerification,
        verifyEmail,
        resending,
        resendMessage,

        handleLogin,
        resendVerification,
        clearVerificationNotice,
        togglePassword,
        authGoogle,
    }
}