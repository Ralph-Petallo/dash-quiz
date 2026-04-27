<template>
  <div class="main-content">
    <div class="profile-card" v-if="user">
      <div class="profile-header">
        <div class="avatar-wrap">
          <div :class="['avatar', { 'uploading': loading }]">
            <img :src="preview || profileImageUrl" draggable="false" />
            <div v-if="loading" class="avatar-loader"></div>
          </div>
          <label class="avatar-camera-badge">
            <i class="fas fa-camera"></i>
            <input type="file" @change="onFileChange" accept="image/*" style="display:none" />
          </label>
        </div>

        <h2>{{ user.first_name }}</h2>
        <small>{{ user.email }}</small>
      </div>

      <div class="profile-details">

        <div class="detail-row">
          <span class="label">Date Joined</span>
          <span class="value">{{ formattedDate }}</span>
        </div>

        <div class="detail-row">
          <span class="label">Quizzes Taken</span>
          <span class="value">{{ quizzesCount || 'N/A' }}</span>
        </div>
      </div>

      <div class="profile-buttons">
        <button id="edit-profile" @click="showEditModal = true">
          <i class="fas fa-edit"></i>
          Edit Profile
        </button>
        <button id="delete" class="danger" @click="showDeleteModal = true">
          <i class="fas fa-trash"></i>
          Delete Account
        </button>
      </div>
    </div>
  </div>

  <ToastNotification :message="notification.message" :type="notification.type" @clear="notification.message = ''" />
  <EditProfileModal :show="showEditModal" :loading="loading" :user="user" @notify="handleNotify" @save="updateProfile"
    @close="showEditModal = false" />
  <DeleteAccountModal :show="showDeleteModal" @close="showDeleteModal = false" @deleted="handleDeleted" />
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import axios from "axios"
import { useRouter } from "vue-router"
import DeleteAccountModal from "@/components/ProfileModals/DeleteModal.vue"
import EditProfileModal from "@/components/ProfileModals/EditModal.vue"
import ToastNotification from "@/components/ToastNotification.vue"

import { useUser } from "@/composables/useUser"

const router = useRouter()

const { userFullName, fetchUser, userAvatar, user } = useUser()
const notification = ref({
  message: '',
  type: 'success'
})

const handleNotify = (payload) => {
  showToast(payload.message, payload.type)
}

const showEditModal = ref(false)
const showDeleteModal = ref(false)

const preview = ref(null)
const selectedFile = ref(null)
const loading = ref(false)

/*
|--------------------------------------------------------------------------
| Handle after deletion
|--------------------------------------------------------------------------
*/
const handleDeleted = () => {
  showDeleteModal.value = false
  router.push("/")
}
/*
|--------------------------------------------------------------------------
| Profile Computed
|--------------------------------------------------------------------------
*/
const profileImageUrl = computed(() => userAvatar.value)

const quizzesCount = computed(() => {
  return user.value?.quizzes_taken || 0
})

const showToast = (msg, type = 'success') => {
  notification.value.message = msg
  notification.value.type = type

  // Auto hide after 4 seconds
  setTimeout(() => {
    notification.value.message = ''
  }, 2000)
}

const formattedDate = computed(() => {
  if (!user.value?.created_at) return ""

  return new Date(user.value.created_at).toLocaleDateString()
})

/*
|--------------------------------------------------------------------------
| Update Profile
|--------------------------------------------------------------------------
*/
const updateProfile = async (formData) => {
  try {
    loading.value = true

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
/*
|--------------------------------------------------------------------------
| Upload Photo Logic
|--------------------------------------------------------------------------
*/
const onFileChange = async (e) => {
  const file = e.target.files[0]
  if (!file) return

  console.log("Selected file:", file)

  // Validate file type
  if (!file.type.startsWith("image/")) {
    showToast("Please select a valid image file.", "error")
    return
  }

  selectedFile.value = file
  preview.value = URL.createObjectURL(file)
  loading.value = true

  try {
    const formData = new FormData()

    // keep filename for better compatibility
    formData.append("photo", file, file.name)

    const { data } = await axios.post("/api/profile/photo", formData)

    console.log("Upload response:", data)

    // Update state safely
    if (data?.new_photo && user.value) {
      user.value.profile_photo = data.new_photo
    }

    preview.value = null
    showToast("Profile picture updated!", "success")

  } catch (error) {
    console.log("Upload error:", error.response?.data || error)

    preview.value = null
    showToast(
      error.response?.data?.message || "Failed to upload photo.",
      "error"
    )
  } finally {
    loading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/
// after updating user
onMounted(async () => {
  await fetchUser(true)
})

</script>

<style scoped>
.main-content {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 80vh;
}

/* Card */
.profile-card {
  width: 100%;
  max-width: 420px;
  background: #fff;
  border-radius: 24px;
  border: 0.5px solid rgba(0, 0, 0, 0.08);
  padding: 32px 28px 28px;
  display: flex;
  flex-direction: column;
  animation: fadeIn 0.4s ease-out both;
}

/* Header */
.profile-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding-bottom: 24px;
  border-bottom: 0.5px solid rgba(0, 0, 0, 0.08);
  margin-bottom: 0;
}

/* Avatar */
.avatar-wrap {
  position: relative;
  width: 100px;
  height: 100px;
  margin-bottom: 16px;
}

.avatar {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  overflow: hidden;
  border: 3px solid #4b32a8;
  background: #eeedfe;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.avatar-loader {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: rgba(75, 50, 168, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-camera-badge {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #4b32a8;
  border: 2px solid #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 11px;
  cursor: pointer;
}

.avatar-camera-badge:hover {
  background: #3e2983;
}

.profile-header h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1a1a1a;
  letter-spacing: -0.02em;
}

.profile-header small {
  color: #6b7280;
  font-size: 0.8rem;
  margin-top: 3px;
}

/* Change photo button */
.file-label {
  margin-top: 14px;
  font-size: 0.75rem;
  font-weight: 700;
  color: #4b32a8;
  cursor: pointer;
  padding: 7px 16px;
  border: 1.5px solid #4b32a8;
  border-radius: 50px;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: background 0.18s, color 0.18s;
}

.file-label:hover {
  background: #4b32a8;
  color: #fff;
}

.file-label input {
  display: none;
}

/* Details */
.profile-details {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 24px 0;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 13px 16px;
  background: #f9faff;
  border-radius: 12px;
  border: 1px solid #f0edff;
}

.detail-row .label {
  font-size: 0.7rem;
  font-weight: 700;
  color: #8e8eb2;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.detail-row .value {
  font-weight: 700;
  color: #1a1a1a;
  font-size: 0.9rem;
}

/* Buttons */
.profile-buttons {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

button {
  padding: 12px 10px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  border: none;
  transition: transform 0.12s, background 0.18s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  letter-spacing: 0.01em;
}

button:active {
  transform: scale(0.97);
}

#edit-profile {
  background: #4b32a8;
  color: #fff;
}

#edit-profile:hover {
  background: #3a2480;
}

#delete {
  background: #fff;
  color: #e53935;
  border: 1px solid #fee2e2;
}

#delete:hover {
  background: #fff5f5;
}

/* Animation */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(12px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 480px) {
  .profile-buttons {
    grid-template-columns: 1fr;
  }

  .profile-card {
    padding: 24px 20px 20px;
  }
}
</style>