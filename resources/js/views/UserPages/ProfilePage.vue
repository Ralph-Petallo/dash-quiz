<template>
  <div class="main-content">

    <div class="profile-card" v-if="user">

      <!-- =====================================
           PROFILE HEADER
      ====================================== -->
      <div class="profile-header">

        <div class="avatar-wrap">

          <div :class="['avatar', { uploading: loading }]">
            <img :src="preview || userAvatar" alt="Profile picture" draggable="false" />

            <div v-if="loading" class="avatar-loader">
              <i class="fas fa-spinner fa-spin"></i>
            </div>
          </div>

          <label class="avatar-camera-badge" title="Change profile picture">
            <i class="fas fa-camera"></i>

            <input type="file" @change="uploadPhoto" accept="image/*" />
          </label>

        </div>


        <div class="profile-heading">

          <h2>
            {{ user.first_name }}
          </h2>

          <small>
            {{ user.email }}
          </small>

        </div>

      </div>


      <!-- =====================================
           PROFILE DETAILS
      ====================================== -->
      <div class="profile-details">

        <div class="detail-row">

          <div class="detail-label">
            <i class="fas fa-calendar-alt"></i>

            <span>
              Date Joined
            </span>
          </div>

          <span class="detail-value">
            {{ formattedDate }}
          </span>

        </div>


        <div class="detail-row">

          <div class="detail-label">
            <i class="fas fa-clipboard-list"></i>

            <span>
              Quizzes Taken
            </span>
          </div>

          <span class="detail-value">
            {{ quizzesCount }}
          </span>

        </div>

      </div>


      <!-- =====================================
           ACTIONS
      ====================================== -->
      <div class="profile-buttons">

        <button id="edit-profile" @click="showEditModal = true">
          <i class="fas fa-pen"></i>

          <span>
            Edit Profile
          </span>
        </button>


        <button id="delete" class="danger" @click="showDeleteModal = true">
          <i class="fas fa-trash"></i>

          <span>
            Delete Account
          </span>
        </button>

      </div>

    </div>

  </div>


  <!-- =====================================
       NOTIFICATIONS / MODALS
  ====================================== -->

  <ToastNotification :message="notification.message" :type="notification.type" @clear="notification.message = ''" />

  <EditProfileModal :show="showEditModal" :loading="loading" :user="user" @notify="handleNotify" @save="updateProfile"
    @close="showEditModal = false" />

  <DeleteAccountModal :show="showDeleteModal" @close="showDeleteModal = false" @deleted="handleDeleted" />

</template>


<script setup>

import {
  ref,
  computed,
  onMounted
} from "vue"

import axios from "axios"

import { useRouter } from "vue-router"

import DeleteAccountModal
  from "@/components/ProfileModals/DeleteModal.vue"

import EditProfileModal
  from "@/components/ProfileModals/EditModal.vue"

import ToastNotification
  from "@/components/ToastNotification.vue"

import { useUser }
  from "@/composables/useUser"


/* =========================================
   USER
========================================= */

const router = useRouter()

const {
  fetchUser,
  userAvatar,
  user
} = useUser()

console.log(user.value)


/* =========================================
   STATE
========================================= */

const notification = ref({
  message: "",
  type: "success"
})

const showEditModal = ref(false)

const showDeleteModal = ref(false)

const preview = ref(null)

const selectedFile = ref(null)

const loading = ref(false)


/* =========================================
   NOTIFICATION
========================================= */

const showToast = (
  message, status = "success") => {
  notification.value.message = message
  notification.value.status = status
  setTimeout(() => {
    notification.value.message = ""
  }, 1500)
}

const handleNotify = (payload) => {
  showToast(payload.message, payload.status)
}

/* =========================================
   DELETE ACCOUNT
========================================= */

const handleDeleted = () => {
  showDeleteModal.value = false
  router.push("/")
}


/* =========================================
   COMPUTED
========================================= */

const quizzesCount = computed(() => {
  return user.value?.quizzes_taken || 0
})


const formattedDate = computed(() => {
  if (!user.value?.created_at) {
    return ""
  }
  return new Date(user.value.created_at).toLocaleDateString()
})


/* =========================================
   PROFILE PHOTO
========================================= */
const uploadPhoto = async (e) => {
  const file = e.target.files[0]
  // if not file, then just  return
  if (!file) return
  /* Validate image */

  if (!file.type.startsWith("image/")) {
    showToast(
      "Please select a valid image file.",
      "error"
    )
    return
  }
  selectedFile.value = file
  preview.value = URL.createObjectURL(file)
  loading.value = true

  try {
    const formData = new FormData()
    formData.append("photo", file, file.name)

    const { data } = await axios.post("/api/profile/photo", formData)
    if (data?.new_photo && user.value) {
      user.value.profile_photo =
        data.new_photo
    }

    preview.value = null
    // alert notification
    showToast("Profile picture updated!", "success")

  } catch (error) {

    console.log(
      "Upload error:",
      error.response?.data || error
    )


    preview.value = null

    showToast(
      error.response?.data?.message ||
      "Failed to upload photo.",
      "error"
    )

  } finally {

    loading.value = false

  }

}


/* =========================================
   INITIAL LOAD
========================================= */

onMounted(async () => {

  await fetchUser(true)

})

</script>


<style scoped>
/* =========================================================
   DASHQUIZ PROFILE
   FROSTED NOIR — RESPONSIVE
========================================================= */

.main-content {
  --white: #ffffff;
  --black: #000000;

  --gray-light: #d3d3d3;
  --gray: #a9a9a9;
  --gray-dark: #696969;

  --surface: #ffffff;
  --surface-soft: #f7f7f7;

  --border: #d3d3d3;

  --danger: #dc2626;
  --danger-soft: #fff5f5;

  width: 100%;
  min-width: 0;

  min-height: 100%;

  padding: clamp(16px, 3vw, 32px);
  display: flex;
  justify-content: center;
  align-items: flex-start;
  box-sizing: border-box;
  overflow-x: hidden;
}


/* =========================================================
   PROFILE CARD
========================================================= */

.profile-card {
  width: min(100%, 440px);

  background: var(--surface);

  border: 1px solid var(--border);

  border-radius: 3px;

  padding: clamp(20px, 4vw, 32px);

  box-sizing: border-box;

  display: flex;
  flex-direction: column;

  box-shadow:
    0 6px 20px rgba(0, 0, 0, 0.05);

  animation: profileFadeIn 0.35s ease-out both;
}


/* =========================================================
   PROFILE HEADER
========================================================= */

.profile-header {
  width: 100%;

  display: flex;
  flex-direction: column;

  align-items: center;

  text-align: center;

  padding-bottom: 24px;

  border-bottom: 1px solid var(--border);

  box-sizing: border-box;
}


/* =========================================================
   AVATAR
========================================================= */

.avatar-wrap {
  position: relative;

  width: 96px;
  height: 96px;

  margin-bottom: 14px;

  flex-shrink: 0;
}

.avatar {
  width: 90px;
  height: 90px;

  border-radius: 50%;

  overflow: hidden;

  background: var(--surface-soft);

  border: 2px solid var(--black);

  display: flex;
  align-items: center;
  justify-content: center;

  box-sizing: border-box;
}

.avatar img {
  display: block;

  width: 100%;
  height: 100%;

  object-fit: cover;

  border-radius: 50%;
}

.avatar.uploading {
  opacity: 0.7;
}


/* =========================================================
   AVATAR LOADER
========================================================= */

.avatar-loader {
  position: absolute;

  top: 0;
  left: 0;

  width: 90px;
  height: 90px;

  border-radius: 50%;

  background: rgba(0, 0, 0, 0.55);

  color: var(--white);

  display: flex;
  align-items: center;
  justify-content: center;

  font-size: 16px;
}


/* =========================================================
   CAMERA BUTTON
========================================================= */

.avatar-camera-badge {
  position: absolute;

  right: 1px;
  bottom: 1px;

  width: 28px;
  height: 28px;

  border-radius: 50%;

  background: var(--black);

  border: 2px solid var(--white);

  color: var(--white);

  display: flex;
  align-items: center;
  justify-content: center;

  cursor: pointer;

  font-size: 11px;

  box-sizing: border-box;

  transition:
    background 0.2s ease,
    transform 0.2s ease;
}

.avatar-camera-badge:hover {
  background: var(--gray-dark);

  transform: scale(1.06);
}

.avatar-camera-badge:active {
  transform: scale(0.96);
}

.avatar-camera-badge input {
  display: none;
}


/* =========================================================
   PROFILE NAME / EMAIL
========================================================= */

.profile-heading {
  width: 100%;

  min-width: 0;
}

.profile-heading h2 {
  margin: 0;

  color: var(--black);

  font-size: clamp(1.15rem, 3vw, 1.35rem);

  font-weight: 700;

  line-height: 1.3;

  overflow-wrap: anywhere;
}

.profile-heading small {
  display: block;

  margin-top: 5px;

  color: var(--gray-dark);

  font-size: clamp(0.72rem, 2vw, 0.8rem);

  line-height: 1.4;

  overflow-wrap: anywhere;
}


/* =========================================================
   DETAILS
========================================================= */

.profile-details {
  width: 100%;

  padding: 22px 0;

  display: flex;
  flex-direction: column;

  gap: 9px;

  box-sizing: border-box;
}

.detail-row {
  width: 100%;

  min-width: 0;

  padding: 13px 14px;

  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 12px;

  background: var(--surface-soft);

  border: 1px solid var(--border);

  border-radius: 10px;

  box-sizing: border-box;

  transition:
    background 0.18s ease,
    border-color 0.18s ease;
}

.detail-row:hover {
  background: #eeeeee;

  border-color: var(--gray);
}


/* =========================================================
   DETAIL LABEL
========================================================= */

.detail-label {
  min-width: 0;

  display: flex;
  align-items: center;

  gap: 8px;

  color: var(--gray-dark);

  font-size: 0.68rem;

  font-weight: 700;

  letter-spacing: 0.04em;

  text-transform: uppercase;

  line-height: 1.3;
}

.detail-label i {
  width: 16px;

  flex-shrink: 0;

  text-align: center;

  color: var(--gray-dark);

  font-size: 0.75rem;
}


/* =========================================================
   DETAIL VALUE
========================================================= */

.detail-value {
  min-width: 0;

  color: var(--black);

  font-size: 0.85rem;

  font-weight: 700;

  text-align: right;

  overflow-wrap: anywhere;
}


/* =========================================================
   BUTTON CONTAINER
========================================================= */

.profile-buttons {
  width: 100%;

  display: grid;

  grid-template-columns:
    repeat(2, minmax(0, 1fr));

  gap: 9px;

  box-sizing: border-box;
}


/* =========================================================
   BUTTON BASE
========================================================= */

.profile-buttons button {
  width: 100%;
  min-width: 0;

  min-height: 44px;

  padding: 10px 12px;

  border-radius: 9px;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 7px;

  font-family: inherit;

  font-size: 0.82rem;

  font-weight: 700;

  line-height: 1.2;

  cursor: pointer;

  box-sizing: border-box;

  transition:
    background 0.18s ease,
    color 0.18s ease,
    border-color 0.18s ease,
    transform 0.12s ease;
}

.profile-buttons button:active {
  transform: scale(0.97);
}


/* =========================================================
   EDIT PROFILE
========================================================= */

#edit-profile {
  background: var(--black);

  color: var(--white);

  border: 1px solid var(--black);
}

#edit-profile:hover {
  background: var(--gray-dark);

  border-color: var(--gray-dark);
}


/* =========================================================
   DELETE ACCOUNT
========================================================= */

#delete {
  background: var(--white);

  color: var(--danger);

  border: 1px solid #fecaca;
}

#delete:hover {
  background: var(--danger-soft);

  border-color: #fca5a5;
}


/* =========================================================
   LARGE TABLET
========================================================= */

@media (max-width: 1024px) {

  .main-content {
    padding: 24px;
  }

  .profile-card {
    max-width: 430px;
  }
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 768px) {

  .main-content {
    padding: 20px 16px;
  }

  .profile-card {
    width: 100%;

    max-width: 440px;

    border-radius: 14px;
  }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 600px) {

  .main-content {
    padding: 16px 12px;
  }

  .profile-card {
    width: 100%;

    padding: 22px 18px;

    border-radius: 12px;
  }

  .profile-header {
    padding-bottom: 20px;
  }

  .profile-details {
    padding: 18px 0;
  }

}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 480px) {

  .main-content {
    padding: 12px;
  }

  .profile-card {
    padding: 20px 15px;
  }

  .avatar-wrap {
    width: 86px;
    height: 86px;

    margin-bottom: 12px;
  }

  .avatar {
    width: 82px;
    height: 82px;
  }

  .avatar-loader {
    width: 82px;
    height: 82px;
  }

  .avatar-camera-badge {
    width: 27px;
    height: 27px;

    right: 0;
    bottom: 0;
  }

  .profile-heading h2 {
    font-size: 1.15rem;
  }

  .profile-heading small {
    font-size: 0.72rem;
  }

  .profile-details {
    gap: 8px;

    padding: 18px 0;
  }

  .detail-row {
    padding: 12px;

    gap: 8px;
  }

  .detail-label {
    font-size: 0.62rem;

    gap: 6px;
  }

  .detail-label i {
    width: 14px;

    font-size: 0.7rem;
  }

  .detail-value {
    font-size: 0.78rem;
  }

  .profile-buttons {
    grid-template-columns: 1fr;

    gap: 8px;
  }

  .profile-buttons button {
    min-height: 44px;
  }
}


/* =========================================================
   VERY SMALL PHONES
========================================================= */

@media (max-width: 360px) {

  .main-content {
    padding: 10px;
  }

  .profile-card {
    padding: 18px 12px;

    border-radius: 10px;
  }

  .detail-row {
    padding: 10px;
  }

  .detail-label {
    font-size: 0.58rem;
  }

  .detail-value {
    font-size: 0.72rem;
  }

}


/* =========================================================
   EXTRA SMALL — 320px
========================================================= */

@media (max-width: 330px) {

  .main-content {
    padding: 8px;
  }

  .profile-card {
    padding: 16px 10px;
  }

  .profile-header {
    padding-bottom: 18px;
  }

  .profile-details {
    padding: 16px 0;
  }

  .detail-row {
    padding: 9px;
  }

  .detail-label span {
    max-width: 100px;
  }

}


/* =========================================================
   REDUCED MOTION
========================================================= */

@media (prefers-reduced-motion: reduce) {

  .profile-card {
    animation: none;
  }

  .avatar-camera-badge,
  .detail-row,
  .profile-buttons button {
    transition: none;
  }

}


/* =========================================================
   ANIMATION
========================================================= */

@keyframes profileFadeIn {

  from {
    opacity: 0;

    transform:
      translateY(10px);
  }

  to {
    opacity: 1;

    transform:
      translateY(0);
  }

}
</style>
