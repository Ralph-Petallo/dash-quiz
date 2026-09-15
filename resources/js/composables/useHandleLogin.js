import { ref } from 'vue'
import { useUser } from './useUser'
import { useRouter } from 'vue-router'
import axios from 'axios'

export function useHandleLogin() {
    const maxAttempts = 3

    const loading = ref(false)
    const errors = ref({})
    const generalError = ref('')

    const isLocked = ref(false)
    const showPassword = ref(false)
    const attempts = ref(0)

    const { fetchUser } = useUser()
    const router = useRouter()

    const handleLogin = async (email, password) => {
        if (loading.value || isLocked.value) return

        errors.value = {}
        generalError.value = ''

        // Clean email
        email = email.trim()

        // Validate
        if (!email || !password) {
            generalError.value = 'Please enter your email and password.'
            return
        }

        if (!isValidEmail(email)) {
            generalError.value = 'Please enter a valid email address.'
            return
        }

        loading.value = true

        try {
            await axios.get('/sanctum/csrf-cookie')

            const { data } = await axios.post('/api/login', {
                email,
                password
            })

            // Reset attempts after successful login
            attempts.value = 0

            // Refresh authenticated user
            await fetchUser(true)

            // Redirect based on role
            router.push(data.role === 'admin' ? '/admin' : '/user')

        } catch (error) {
            const response = error?.response
            const status = response?.status
            const responseData = response?.data

            let shouldIncrementAttempts = false

            if (status === 422 && responseData?.errors) {
                errors.value = responseData.errors
                generalError.value =
                    responseData.message ||
                    'Please fix the highlighted fields.'

            } else if (status === 401) {
                shouldIncrementAttempts = true
                generalError.value =
                    responseData?.message ||
                    'Invalid email or password.'

            } else if (status === 429) {
                shouldIncrementAttempts = true

                const retryAfter = parseInt(
                    response?.headers?.['retry-after'],
                    10
                )

                if (retryAfter > 0) {
                    generalError.value =
                        `Too many login attempts. Please wait ${retryAfter} seconds and try again.`
                } else {
                    generalError.value =
                        responseData?.message ||
                        'Too many login attempts. Please try again later.'
                }

            } else {
                generalError.value =
                    responseData?.message ||
                    'Unable to login right now. Please try again later.'
            }

            if (shouldIncrementAttempts) {
                attempts.value++
            }

            if (attempts.value >= maxAttempts) {
                isLocked.value = true

                generalError.value =
                    'Too many failed attempts. Please wait 30 seconds.'

                timeout(30)
            }

        } finally {
            loading.value = false
        }
    }

    function timeout(seconds) {
        setTimeout(() => {
            attempts.value = 0
            isLocked.value = false
        }, seconds * 1000)
    }

    const togglePassword = () => {
        showPassword.value = !showPassword.value
    }

    function isValidEmail(email) {
        if (!email || typeof email !== 'string') {
            return false
        }

        email = email.trim()

        // Basic length limits
        if (email.length > 254) {
            return false
        }

        // Must contain exactly one @
        const parts = email.split('@')

        if (parts.length !== 2) {
            return false
        }

        const [local, domain] = parts

        // Local part checks
        if (!local || local.length > 64) {
            return false
        }

        if (local.startsWith('.') || local.endsWith('.')) {
            return false
        }

        if (local.includes('..')) {
            return false
        }

        // Domain checks
        if (!domain || domain.length > 253) {
            return false
        }

        if (domain.startsWith('.') || domain.endsWith('.')) {
            return false
        }

        if (domain.includes('..')) {
            return false
        }

        // Domain must have a valid extension
        const domainParts = domain.split('.')

        if (domainParts.length < 2) {
            return false
        }

        // Validate local part
        const localRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+$/

        if (!localRegex.test(local)) {
            return false
        }

        // Validate domain
        const domainRegex = /^[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/

        for (const part of domainParts) {
            if (!domainRegex.test(part)) {
                return false
            }
        }

        // TLD should contain letters and be at least 2 characters
        const tld = domainParts[domainParts.length - 1]

        if (!/^[a-zA-Z]{2,63}$/.test(tld)) {
            return false
        }

        return true
    }

    function authGoogle() {
        window.location.href = '/auth/google'
    }

    return {
        loading,
        errors,
        generalError,
        isLocked,
        showPassword,
        attempts,
        handleLogin,
        togglePassword,
        authGoogle
    }
}