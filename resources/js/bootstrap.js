import axios from "axios"

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || window.location.origin,

    withCredentials: true,
    withXSRFToken: true,

    headers: {
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
    },
})

let csrfPromise = null

const refreshCsrfToken = async () => {
    if (!csrfPromise) {
        csrfPromise = api.get("/sanctum/csrf-cookie")
            .finally(() => {
                csrfPromise = null
            })
    }
    return csrfPromise
}

api.interceptors.response.use(
    (response) => response,

    async (error) => {
        const response = error.response
        const request = error.config

        if (!response || !request) {
            return Promise.reject(error)
        }

        // CSRF token expired
        if (
            response.status === 419 &&
            !request._csrfRetry
        ) {
            request._csrfRetry = true

            try {
                await refreshCsrfToken()
                return api(request)
            } catch {
                return Promise.reject(error)
            }
        }

        // ❌ REMOVE THIS - Let router handle auth redirects
        // if (response.status === 401) { ... }

        return Promise.reject(error)
    }
)

export default api