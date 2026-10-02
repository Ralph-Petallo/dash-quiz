import api from "../bootstrap"
import { useUser } from "./useUser"

const INTERVAL = 60 * 1000
const GUEST_ROUTES = ["/", "/register", "/forgot", "/reset"]

let heartbeat = null

const clearSession = (router, user) => {
    if (heartbeat) {
        clearInterval(heartbeat)
        heartbeat = null
    }

    user.value = null

    Object.keys(localStorage).forEach((key) => {
        if (key.startsWith("dash-quiz_")) {
            localStorage.removeItem(key)
        }
    })

    router.push("/")
}

export const startHeartbeat = (router) => {
    const { user } = useUser()

    if (heartbeat) {
        clearInterval(heartbeat)
    }

    heartbeat = setInterval(async () => {
        const path = router.currentRoute.value.path

        if (!user.value || GUEST_ROUTES.includes(path)) return

        try {
            const { data } = await api.post("/api/heartbeat")

            if (data?.active_status === 0) {
                clearSession(router, user)
            }
        } catch (error) {
            if ([401, 403].includes(error?.response?.status)) {
                clearSession(router, user)
            }
        }
    }, INTERVAL)
}