import { ref } from "vue"
import { useUser } from "./useUser"
import api from "../bootstrap"

const { user } = useUser()

const leaderboard = ref([])
const isLoading = ref(false)

/*
|--------------------------------------------------------------------------
| Get Leaderboard
|--------------------------------------------------------------------------
*/

const getLeaderBoard = async () => {
    isLoading.value = true

    try {
        const { data } = await api.get(
            "/api/dashboard/leaderboard"
        )

        const currentUserId = user.value?.id ?? null

        leaderboard.value = (data.data ?? []).map(item => ({
            ...item,

            isYou:
                Number(item.user_id) ===
                Number(currentUserId),

            displayName:
                item.name ?? "Unknown User",
        }))

        return leaderboard.value

    } catch (error) {
        console.error(
            "Failed to load leaderboard:",
            error
        )

        throw error

    } finally {
        isLoading.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Update Profile
|--------------------------------------------------------------------------
*/

const updateProfile = async (formData) => {
    try {
        isLoading.value = true

        const { data } = await api.put(
            "/api/profile/update",
            formData
        )

        if (data.data && user.value) {
            Object.assign(user.value, {
                first_name: data.data.first_name,
                last_name: data.data.last_name,
                email: data.data.email,
            })
        }

        return data

    } catch (error) {
        console.error(
            "Profile update failed:",
            error
        )

        throw error

    } finally {
        isLoading.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Export
|--------------------------------------------------------------------------
*/

export function useUserRequests() {
    return {
        getLeaderBoard,
        leaderboard,
        isLoading,
        updateProfile,
    }
}