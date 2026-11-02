import axios from "axios"

const api = axios.create({
    // Same origin in every case (local, ngrok, or built Laravel), so cookies and CSRF work.
    baseURL: import.meta.env.VITE_API_URL || window.location.origin,

    withCredentials: true,
    withXSRFToken: true,

    // Fail after 20 seconds instead of spinning forever.
    timeout: 20000,

    headers: {
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
        // Free ngrok shows an HTML warning page unless this header is sent.
        // Without it, API calls receive HTML and the page never finishes loading.
        "ngrok-skip-browser-warning": "true",
    },
})

let csrfPromise = null

const refreshCsrfToken = () => {
    if (!csrfPromise) {
        csrfPromise = api
            .get("/sanctum/csrf-cookie")
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

        // CSRF token expired: refresh it once, then retry the same request.
        if (response.status === 419 && !request._csrfRetry) {
            request._csrfRetry = true

            try {
                await refreshCsrfToken()
                return api(request)
            } catch {
                return Promise.reject(error)
            }
        }

        // Auth redirects are handled by the router, not here.
        return Promise.reject(error)
    }
)

export default api