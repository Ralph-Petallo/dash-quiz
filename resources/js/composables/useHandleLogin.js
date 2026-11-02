import { ref } from "vue"
import { useRouter, useRoute } from "vue-router"
import { useUser } from "./useUser"
import api from "../bootstrap"

export function useHandleLogin() {
    const maxAttempts = 3

    const loading = ref(false)
    const errors = ref({})
    const generalError = ref("")
    const isLocked = ref(false)
    const showPassword = ref(false)
    const attempts = ref(0)

    const router = useRouter()
    const route = useRoute()
    const { setUser, fetchUser } = useUser()

    const homeFor = function (user) {
        if (user?.role === "admin") {
            return { name: "admin-dashboard" }
        } else {
            return { name: "user-home" }
        }
    }


    const handleLogin = async (email, password) => {

        errors.value = {}
        generalError.value = ""

        if (!email) errors.value.email = "Email is required."
        if (!password) errors.value.password = "Password is required."

        if (Object.keys(errors.value).length > 0) return

        if (isLocked.value) {
            generalError.value = "Too many failed attempts. Please try again later."
            return
        }

        loading.value = true

        try {
            // Sets the Sanctum session and XSRF cookies.
            await api.get("/sanctum/csrf-cookie")

            const { data } = await api.post("/api/login", { email, password })

            // Accept the user wherever the controller returns it.
            const user = data?.user ?? data?.results ?? null

            if (!user) {
                throw new Error("Login response did not include a user.")
            }

            attempts.value = 0
            setUser(user)

            // Replace it with the full profile (photo, stats, dates) before the page opens.
            await fetchUser(true)

            const redirect = route.query.redirect

            if (typeof redirect === "string" && redirect.startsWith("/")) {
                await router.push(redirect)
            } else {
                await router.push(homeFor(user))
            }
        } catch (error) {
            console.error("Login failed:", {
                url: error.config?.url,
                status: error.response?.status,
                data: error.response?.data,
            })

            const status = error.response?.status
            const responseData = error.response?.data

            if (status === 422) {
                errors.value = responseData?.errors ?? {}
                generalError.value = responseData?.message ?? "Please check your information."
                attempts.value++
            } else if (status === 403) {
                // Account exists but the email is not confirmed. Send them to the resend step.
                await router.push({
                    name: "register",
                    query: { step: "resend", email: responseData?.email ?? email },
                })
            } else if (status === 401) {
                attempts.value++
                generalError.value = responseData?.message ?? "Invalid email or password."

                if (attempts.value >= maxAttempts) {
                    isLocked.value = true
                    generalError.value = "Too many failed attempts. Please try again later."

                    setTimeout(() => {
                        attempts.value = 0
                        isLocked.value = false
                        generalError.value = ""
                    }, 30000)
                }
            } else if (status === 419) {
                generalError.value = "Your session expired. Please try again."
            } else if (status >= 500) {
                generalError.value = "Server error. Please try again later."
            } else {
                generalError.value = error.message || "Unable to login. Please check your connection and try again."
            }
        } finally {
            loading.value = false
        }
    }

    const togglePassword = () => showPassword.value = !showPassword.value

    // Relative path, so it works on localhost and on the ngrok domain.
    const authGoogle = () => {
        location.href = "/auth/google/redirect"
    }

    return {
        loading,
        errors,
        generalError,
        isLocked,
        showPassword,
        authGoogle,
        attempts,
        handleLogin,
        togglePassword,
    }
}