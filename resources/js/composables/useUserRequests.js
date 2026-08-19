import axios from 'axios'
import { ref, computed } from 'vue'
import { useUser } from './useUser'

const { user, fetchUser, userFullName } = useUser()
const leaderboard = ref([])
const isLoading = ref(false)

const getLeaderBoard = async (force = false) => {

    isLoading.value = true

    try {
        await axios.get('/sanctum/csrf-cookie')
        const { data } = await axios.get('/api/dashboard/leaderboard')
        const currentUserId = user.value?.id ?? null
        leaderboard.value = data.data.map(u => ({
            ...u,
            isYou: u.user_id === currentUserId,
            displayName: u.name,
        }))
    } catch (err) {
        console.error(err)
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

        const { data } = await axios.put('/api/profile/update', formData)

        if (data.data && user.value) {
            Object.assign(user.value, {
                first_name: data.data.first_name,
                last_name: data.data.last_name,
                email: data.data.email,
            })
        }

        showEditModal.value = false
        showToast(data.message ?? 'Profile updated successfully!', 'success')

    } catch (err) {
        showToast(err.response?.data?.message ?? 'Update failed.', 'error')
    } finally {
        loading.value = false
    }
}

export function useUserRequests() {
    return {
        getLeaderBoard,
        leaderboard,
        updateProfile
    }
}