import { ref } from "vue"
import { useUser } from "./useUser"
import { useRouter } from "vue-router"
import api from "../bootstrap"

export function useHandleLogin() {
    const maxAttempts = 3

    const loading = ref(false)
    const errors = ref({})
    const generalError = ref("")

    const isLocked = ref(false)
    const showPassword = ref(false)
    const attempts = ref(0)

    // ✅ Move useUser() and useRouter() to top level
    const router = useRouter()

    const handleLogin = async (email, password) => {
        const { fetchUser } = useUser()
        errors.value = {}
        generalError.value = ""

        // Basic validation
        if (!email) {
            errors.value.email = "Email is required."
        }

        if (!password) {
            errors.value.password = "Password is required."
        }

        if (Object.keys(errors.value).length > 0) {
            return
        }

        if (isLocked.value) {
            generalError.value = "Too many failed attempts. Please try again later."
            return
        }

        loading.value = true

        try {
            // Get Sanctum CSRF cookie first
            await api.get("/sanctum/csrf-cookie")

            // Login
            const { data } = await api.post("/api/login", {
                email,
                password,
            })

            // Login successful
            attempts.value = 0

            await fetchUser(true) // Force fetch user data after login

            // Redirect according to role
            if (data.role === "admin") {
                await router.push("/admin")
            } else {
                await router.push("/user")
            }

        } catch (error) {
            console.error("Login failed:", error)

            const status = error.response?.status
            const responseData = error.response?.data

            // Laravel validation errors
            if (status === 422) {
                errors.value = responseData?.errors ?? {}
                generalError.value = responseData?.message ?? "Please check your information."
                attempts.value++

                // Unauthorized login
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

                // CSRF problem
            } else if (status === 419) {
                generalError.value = "Your session expired. Please try again."

                // Server error
            } else if (status >= 500) {
                generalError.value = "Server error. Please try again later."

                // Network / unknown error
            } else {
                generalError.value = "Unable to login. Please check your connection and try again."
            }

        } finally {
            loading.value = false
        }
    }

    const togglePassword = () => {
        showPassword.value = !showPassword.value
    }

    const authGoogle = () => location.href = 'http://127.0.0.1:8000/auth/google/redirect'

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