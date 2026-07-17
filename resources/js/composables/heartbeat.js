import axios from "axios"
import { useUser } from "./useUser"

const TIME = 1 * 60 * 1000 // 1 minute
let heartbeat = null

export const startHeartbeat = (router) => {
    const { user } = useUser()

    if (heartbeat) {
        clearInterval(heartbeat)
        heartbeat = null
    }

    heartbeat = setInterval(async () => {
        if (!user.value) return

        const guestRoutes = ['/', '/register', '/forgot', '/reset']
        if (guestRoutes.includes(router.currentRoute.value.path)) return

        try {
            const { data } = await axios.post("/api/heartbeat")
            console.log("Heartbeat sent:", data)

            /**
             * 🔥 NEW: server controls account status
             * active_status: 1 = allowed, 0 = blocked/logout
             */
            if (data?.active_status === 0) {
                clearInterval(heartbeat)
                heartbeat = null

                user.value = null

                // clear all DashQuiz cache
                Object.keys(localStorage).forEach(key => {
                    if (key.startsWith('dash-quiz_')) {
                        localStorage.removeItem(key)
                    }
                })

                router.push("/")
            }

        } catch (err) {
            if ([401, 403].includes(err?.response?.status)) {
                clearInterval(heartbeat)
                heartbeat = null

                user.value = null

                Object.keys(localStorage).forEach(key => {
                    if (key.startsWith('dash-quiz_')) {
                        localStorage.removeItem(key)
                    }
                })

                router.push("/")
            }
        }
    }, TIME)
}