<template>
  <div class="profile-page">
    <header class="page-head">
      <div>
        <span class="eyebrow">Account</span>
        <h1>My profile</h1>
      </div>

      <button type="button" class="btn btn-outline" :disabled="status !== 'ready'" @click="showEditModal = true">
        <i class="fas fa-pen" aria-hidden="true"></i>
        Edit profile
      </button>
    </header>

    <!-- OFFLINE NOTICE -->
    <div v-if="!isOnline" class="notice" role="status">
      <i class="fas fa-wifi" aria-hidden="true"></i>
      <span>You're offline. Your profile will reload when the connection returns.</span>
    </div>

    <!-- SLOW NETWORK HINT -->
    <p v-if="status === 'loading' && slowNetwork" class="slow-note" role="status">
      <i class="fas fa-hourglass-half" aria-hidden="true"></i>
      Still loading. Your connection may be slow.
    </p>

    <!-- PAGE ERROR -->
    <section v-if="status === 'error'" class="card state-panel" role="alert">
      <span class="state-icon" :class="`tone-${currentError.tone}`" aria-hidden="true">
        <i :class="currentError.icon"></i>
      </span>

      <h2>{{ currentError.title }}</h2>
      <p>{{ currentError.text }}</p>

      <div class="state-actions">
        <button v-if="currentError.retry" type="button" class="btn btn-solid" :disabled="!isOnline"
          @click="loadProfile">
          <i class="fas fa-rotate-right" aria-hidden="true"></i>
          Try again
        </button>

        <button v-if="currentError.signIn" type="button" class="btn btn-solid" @click="goToLogin">
          <i class="fas fa-right-to-bracket" aria-hidden="true"></i>
          Sign in again
        </button>
      </div>
    </section>

    <!-- LOADING -->
    <template v-else-if="status === 'loading'">
      <span class="sr-only" role="status" aria-live="polite">Loading your profile</span>

      <div class="card profile-summary" aria-hidden="true">
        <span class="sk sk-avatar"></span>
        <div class="skeleton-copy">
          <span class="sk sk-line sk-w-40"></span>
          <span class="sk sk-line sk-w-60"></span>
          <span class="sk sk-pill"></span>
        </div>
      </div>

      <div class="card stats skeleton-stats" aria-hidden="true">
        <div class="stat">
          <span class="sk sk-line sk-w-40"></span>
          <span class="sk sk-line sk-w-60"></span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat">
          <span class="sk sk-line sk-w-40"></span>
          <span class="sk sk-line sk-w-60"></span>
        </div>
      </div>

      <div class="card details" aria-hidden="true">
        <span class="sk sk-line sk-w-40 section-skeleton"></span>
        <div class="details-grid">
          <div v-for="n in 4" :key="n" :class="{ wide: n === 3 }">
            <span class="sk sk-line sk-w-40"></span>
            <span class="sk sk-line sk-w-80 value-skeleton"></span>
          </div>
        </div>
      </div>
    </template>

    <!-- READY -->
    <template v-else>
      <!-- Profile summary -->
      <section class="card profile-summary">
        <div class="avatar-frame">
          <img v-if="displayAvatar" :src="displayAvatar" :alt="`${fullName} profile photo`" />
          <span v-else class="initials" aria-hidden="true">{{ initials }}</span>

          <button type="button" class="camera-btn" :disabled="uploading" aria-label="Choose a new profile photo"
            title="Change photo" @click="openFilePicker">
            <i class="fas fa-camera" aria-hidden="true"></i>
          </button>
        </div>

        <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hidden
          @change="onFileChange" />

        <div class="identity-copy">
          <h2>{{ fullName }}</h2>
          <p class="muted">{{ user.email }}</p>
          <span class="role-label">{{ roleLabel }}</span>

          <!-- Form validation (file type and size) -->
          <p v-if="photoError" class="field-error" role="alert">
            <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
            {{ photoError }}
          </p>

          <!-- Success state -->
          <p v-if="photoSuccess" class="success-note" role="status">
            <i class="fas fa-circle-check" aria-hidden="true"></i>
            Profile picture updated.
          </p>
        </div>
      </section>

      <!-- Compact stats -->
      <section class="card stats" aria-label="Profile summary">
        <div class="stat">
          <strong>{{ totalAssessments }}</strong>
          <span class="muted">Assessments</span>
        </div>

        <div class="stat-divider" aria-hidden="true"></div>

        <div class="stat">
          <strong>{{ memberSince }}</strong>
          <span class="muted">Member since</span>
        </div>
      </section>

      <!-- Pending photo -->
      <section v-if="hasPendingPhoto" class="card pending" role="region" aria-label="New profile photo">
        <div class="pending-copy">
          <strong>New photo selected</strong>
          <span class="muted">{{ pendingFile?.name }}</span>
        </div>

        <div class="pending-actions">
          <button type="button" class="btn btn-ghost" :disabled="uploading" @click="clearPending">
            Cancel
          </button>

          <button type="button" class="btn btn-solid" :disabled="uploading || !isOnline" @click="savePhoto">
            <i :class="uploading ? 'fas fa-spinner fa-spin' : 'fas fa-check'" aria-hidden="true"></i>
            {{ uploading ? 'Saving...' : 'Save photo' }}
          </button>
        </div>
      </section>

      <!-- Personal information -->
      <section class="card details">
        <h3>Personal information</h3>

        <dl class="details-grid">
          <div>
            <dt>First name</dt>
            <dd>{{ user.first_name || '-' }}</dd>
          </div>

          <div>
            <dt>Last name</dt>
            <dd>{{ user.last_name || '-' }}</dd>
          </div>

          <div class="wide">
            <dt>Email address</dt>
            <dd>{{ user.email || '-' }}</dd>
          </div>

          <div>
            <dt>Account type</dt>
            <dd>{{ roleLabel }}</dd>
          </div>
        </dl>
      </section>

      <!-- Delete account -->
      <section class="card danger">
        <div>
          <h3>Delete account</h3>
          <p class="muted small">
            This permanently removes your profile and records. It cannot be undone.
          </p>
        </div>

        <button type="button" class="btn btn-danger" :disabled="!isOnline" @click="showDeleteModal = true">
          <i class="fas fa-trash" aria-hidden="true"></i>
          Delete account
        </button>
      </section>
    </template>

    <EditProfileModal v-model="showEditModal" @notify="handleNotify" />

    <DeleteAccountModal v-model="showDeleteModal" @notify="handleNotify" @deleted="handleDeleted" />

    <ToastNotification :message="notification.message" :type="notification.type" />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import DeleteAccountModal from '@/components/ProfileModals/DeleteModal.vue'
import EditProfileModal from '@/components/ProfileModals/EditModal.vue'
import ToastNotification from '@/components/ToastNotification.vue'

import { useUser } from '@/composables/useUser'
import api from '@/bootstrap'

const MAX_PHOTO_BYTES = 2 * 1024 * 1024
const ALLOWED_PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
const SLOW_AFTER_MS = 4000
const SUCCESS_VISIBLE_MS = 4000

const router = useRouter()
const { fetchUser, userAvatar, totalAssessments, user } = useUser()

/* =========================================================
   ERROR STATES
========================================================= */

const ERROR_STATES = {
  offline: {
    icon: 'fas fa-wifi',
    title: "You're offline",
    text: 'Check your internet connection. Your profile will load automatically once you are back online.',
    tone: 'danger',
    retry: true,
  },
  network: {
    icon: 'fas fa-plug-circle-xmark',
    title: "Can't reach the server",
    text: 'The connection failed. Check your network and try again.',
    tone: 'danger',
    retry: true,
  },
  timeout: {
    icon: 'fas fa-hourglass-half',
    title: 'The request took too long',
    text: 'Your network may be slow right now. Try again in a moment.',
    tone: 'danger',
    retry: true,
  },
  server: {
    icon: 'fas fa-triangle-exclamation',
    title: "Couldn't load your profile",
    text: 'The server could not load your account details. Please try again shortly.',
    tone: 'danger',
    retry: true,
  },
  permission: {
    icon: 'fas fa-lock',
    title: 'Access denied',
    text: 'Your account does not have permission to view this profile. Contact your instructor if this is a mistake.',
    tone: 'neutral',
    retry: false,
  },
  session: {
    icon: 'fas fa-user-clock',
    title: 'Your session has expired',
    text: 'For your security, please sign in again to view your profile.',
    tone: 'neutral',
    retry: false,
    signIn: true,
  },
}

/* =========================================================
   STATE
========================================================= */

// status: loading | ready | error
const status = ref('loading')
const errorType = ref('server')
const isOnline = ref(navigator.onLine)
const slowNetwork = ref(false)

const notification = ref({ message: '', type: 'success' })
const showEditModal = ref(false)
const showDeleteModal = ref(false)

const fileInput = ref(null)
const pendingFile = ref(null)
const previewUrl = ref(null)
const uploading = ref(false)

const photoError = ref('')
const photoSuccess = ref(false)

let requestId = 0
let slowTimer = null
let toastTimer = null
let successTimer = null

const currentError = computed(() => ERROR_STATES[errorType.value] ?? ERROR_STATES.server)
const hasPendingPhoto = computed(() => Boolean(pendingFile.value))
const displayAvatar = computed(() => previewUrl.value || userAvatar.value)

const fullName = computed(() =>
  `${user.value?.first_name ?? ''} ${user.value?.last_name ?? ''}`.trim() || 'Learner'
)

const initials = computed(() => {
  const first = user.value?.first_name?.charAt(0) ?? ''
  const last = user.value?.last_name?.charAt(0) ?? ''
  return (first + last).toUpperCase() || '?'
})

const roleLabel = computed(() => (user.value?.role === 'admin' ? 'Administrator' : 'Learner'))

const memberSince = computed(() => formatDate(user.value?.created_at))

/* =========================================================
   HELPERS
========================================================= */

function formatDate(value) {
  if (!value) return '-'

  const parsed = new Date(value)
  if (Number.isNaN(parsed.getTime())) return '-'

  return parsed.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

function showToast(message, type = 'success') {
  notification.value = { message, type }

  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => {
    notification.value.message = ''
  }, 2500)
}

function handleNotify(payload) {
  showToast(payload?.message ?? '', payload?.status ?? 'success')
}

function handleDeleted() {
  showDeleteModal.value = false
  router.push('/')
}

function goToLogin() {
  router.push('/login')
}

function classifyError(error) {
  const code = error?.response?.status

  if (error?.isInvalidResponse) return 'server'
  if (code === 401 || code === 419) return 'session'
  if (code === 403) return 'permission'
  if (code >= 500) return 'server'
  if (error?.response) return 'server'
  if (error?.code === 'ECONNABORTED' || error?.code === 'ETIMEDOUT') return 'timeout'
  if (!navigator.onLine) return 'offline'

  return 'network'
}

function startSlowTimer() {
  slowNetwork.value = false
  clearTimeout(slowTimer)

  slowTimer = setTimeout(() => {
    slowNetwork.value = true
  }, SLOW_AFTER_MS)
}

function stopSlowTimer() {
  clearTimeout(slowTimer)
  slowNetwork.value = false
}

/* =========================================================
   LOAD PROFILE
========================================================= */

async function loadProfile() {
  const id = ++requestId

  if (!navigator.onLine) {
    isOnline.value = false
    errorType.value = 'offline'
    status.value = 'error'
    return
  }

  isOnline.value = true
  status.value = 'loading'
  startSlowTimer()

  try {
    if (!user.value) await fetchUser()

    if (id !== requestId) return

    if (!user.value) {
      const missing = new Error('No user returned.')
      missing.response = { status: 401 }
      throw missing
    }

    status.value = 'ready'
  } catch (error) {
    if (id !== requestId) return

    console.error('Failed to load profile:', error)
    errorType.value = classifyError(error)
    status.value = 'error'
  } finally {
    if (id === requestId) stopSlowTimer()
  }
}

/* =========================================================
   PHOTO: VALIDATION, PREVIEW, SAVE
========================================================= */

function openFilePicker() {
  photoError.value = ''
  photoSuccess.value = false
  fileInput.value?.click()
}

function clearPending() {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)

  previewUrl.value = null
  pendingFile.value = null
}

// Client-side validation runs before the file is previewed or sent.
function validatePhoto(file) {
  if (!ALLOWED_PHOTO_TYPES.includes(file.type)) {
    return 'Use a JPG, PNG, WEBP, or GIF image.'
  }

  if (file.size > MAX_PHOTO_BYTES) {
    return 'The image must be 2 MB or smaller.'
  }

  return ''
}

function onFileChange(event) {
  const file = event.target.files?.[0]

  // Allow the same file to be chosen again after an error.
  event.target.value = ''

  if (!file) return

  const message = validatePhoto(file)

  if (message) {
    clearPending()
    photoError.value = message
    photoSuccess.value = false
    return
  }

  clearPending()
  photoError.value = ''
  photoSuccess.value = false
  pendingFile.value = file
  previewUrl.value = URL.createObjectURL(file)
}

// Turns server responses into text the learner can act on.
function photoErrorText(error) {
  const code = error?.response?.status

  if (code === 422) {
    const messages = Object.values(error.response.data?.errors ?? {}).flat()
    return messages[0] || 'This photo could not be accepted.'
  }

  if (code === 401 || code === 419) return 'Your session has expired. Sign in again to save a photo.'
  if (code === 403) return 'You do not have permission to change this photo.'
  if (!error?.response) return 'Connection problem. Check your internet and try again.'

  return error.response.data?.message || 'Failed to upload the photo. Please try again.'
}

async function savePhoto() {
  if (!pendingFile.value || uploading.value) return

  uploading.value = true
  photoError.value = ''
  photoSuccess.value = false

  try {
    const formData = new FormData()
    formData.append('photo', pendingFile.value, pendingFile.value.name)

    // The browser sets the multipart boundary automatically.
    const { data } = await api.post('/api/profile/photo', formData)

    if (data?.new_photo && user.value) {
      user.value.profile_photo = data.new_photo
    }

    clearPending()

    // Success state: shown inline, then hidden.
    photoSuccess.value = true
    clearTimeout(successTimer)
    successTimer = setTimeout(() => {
      photoSuccess.value = false
    }, SUCCESS_VISIBLE_MS)
  } catch (error) {
    console.error('Photo upload failed:', error.response?.data || error)
    photoError.value = photoErrorText(error)
  } finally {
    uploading.value = false
  }
}

/* =========================================================
   CONNECTION EVENTS
========================================================= */

function handleOnline() {
  isOnline.value = true

  if (status.value === 'error' && ['offline', 'network', 'timeout'].includes(errorType.value)) {
    loadProfile()
  }
}

function handleOffline() {
  isOnline.value = false
}

/* =========================================================
   LIFECYCLE
========================================================= */

onMounted(() => {
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)
  loadProfile()
})

onBeforeUnmount(() => {
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)

  clearPending()
  clearTimeout(toastTimer)
  clearTimeout(successTimer)
  clearTimeout(slowTimer)
})
</script>

<style scoped>
.profile-page {
  --ink: #111;
  --text: #2a2a2a;
  --muted: #747474;
  --soft: #f7f7f7;
  --white: #fff;
  --border: #e6e6e6;
  --border-strong: #d7d7d7;
  --danger: #b42318;
  --ok: #11813d;

  width: 100%;
  max-width: 820px;
  margin: 0 auto;
  padding: clamp(14px, 3vw, 28px) clamp(12px, 3vw, 24px) 40px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  color: var(--text);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  font-size: 13px;
  line-height: 1.5;
  box-sizing: border-box;
}

.profile-page *,
.profile-page *::before,
.profile-page *::after {
  box-sizing: border-box;
}

.profile-page button {
  font: inherit;
  cursor: pointer;
}

.profile-page button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.profile-page :focus-visible {
  outline: 2px solid var(--ink);
  outline-offset: 2px;
}

.profile-page h1,
.profile-page h2,
.profile-page h3,
.profile-page p,
.profile-page dl,
.profile-page dd {
  margin: 0;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
  border: 0;
}

.card {
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--white);
}

.eyebrow {
  display: block;
  margin-bottom: 4px;
  color: var(--muted);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.muted {
  color: var(--muted);
}

.small {
  font-size: 11px;
}

/* HEADER */

.page-head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}

.page-head h1 {
  font-size: clamp(20px, 3vw, 26px);
  font-weight: 800;
  letter-spacing: -0.03em;
}

/* BUTTONS */

.btn {
  min-height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 13px;
  border-radius: 9px;
  font-size: 12px;
  font-weight: 700;
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.btn-outline {
  border: 1px solid var(--border-strong);
  background: var(--white);
  color: var(--ink);
}

.btn-outline:hover:not(:disabled) {
  border-color: var(--ink);
}

.btn-solid {
  border: 1px solid var(--ink);
  background: var(--ink);
  color: var(--white);
}

.btn-solid:hover:not(:disabled) {
  background: #333;
}

.btn-ghost {
  border: 1px solid transparent;
  background: transparent;
  color: var(--muted);
}

.btn-ghost:hover:not(:disabled) {
  background: var(--soft);
  color: var(--ink);
}

.btn-danger {
  border: 1px solid var(--danger);
  background: var(--white);
  color: var(--danger);
}

.btn-danger:hover:not(:disabled) {
  background: var(--danger);
  color: var(--white);
}

/* NOTICES */

.notice {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  padding: 10px 13px;
  border: 1px solid #e7c76a;
  border-radius: 10px;
  background: #fffaf0;
  font-size: 12px;
}

.notice span {
  flex: 1 1 220px;
  min-width: 0;
}

.slow-note {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: var(--muted);
  font-size: 12px;
}

/* STATE PANEL */

.state-panel {
  min-height: 220px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 28px 22px;
  border-style: dashed;
  text-align: center;
}

.state-panel h2 {
  font-size: 15px;
  font-weight: 800;
}

.state-panel p {
  max-width: 420px;
  color: var(--muted);
  font-size: 12.5px;
  line-height: 1.6;
}

.state-icon {
  width: 46px;
  height: 46px;
  display: grid;
  place-items: center;
  margin-bottom: 4px;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--soft);
  font-size: 17px;
}

.state-icon.tone-danger {
  border-color: #f3c4c0;
  background: #fff1f0;
  color: var(--danger);
}

.state-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
  margin-top: 6px;
}

/* SKELETON */

.sk {
  display: block;
  border-radius: 6px;
  background: linear-gradient(90deg, #efefef 25%, #f8f8f8 37%, #efefef 63%);
  background-size: 400% 100%;
  animation: shimmer 1.4s ease infinite;
}

.sk-avatar {
  width: 72px;
  height: 72px;
  flex: 0 0 72px;
  border-radius: 50%;
}

.sk-line {
  height: 11px;
}

.sk-w-40 {
  width: 40%;
}

.sk-w-60 {
  width: 60%;
}

.sk-w-80 {
  width: 80%;
}

.sk-pill {
  width: 90px;
  height: 18px;
  margin-top: 6px;
  border-radius: 999px;
}

.profile-summary.card {
  display: flex;
  align-items: center;
  gap: 16px;
}

.skeleton-copy {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.section-skeleton {
  width: 30%;
  margin-bottom: 14px;
}

.value-skeleton {
  margin-top: 6px;
}

@keyframes shimmer {
  0% {
    background-position: 100% 0;
  }

  100% {
    background-position: -100% 0;
  }
}

/* PROFILE SUMMARY */

.avatar-frame {
  position: relative;
  width: 72px;
  height: 72px;
  flex: 0 0 72px;
  border: 1px solid #555;
  border-radius: 50%;
  background: var(--soft);
}

.avatar-frame img {
  display: block;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.initials {
  display: grid;
  width: 100%;
  height: 100%;
  place-items: center;
  border-radius: 50%;
  background: var(--ink);
  color: var(--white);
  font-size: 22px;
  font-weight: 700;
}

.camera-btn {
  position: absolute;
  right: -2px;
  bottom: -2px;
  width: 27px;
  height: 27px;
  display: grid;
  place-items: center;
  border: 2px solid var(--white);
  border-radius: 50%;
  background: var(--ink);
  color: var(--white);
  font-size: 10px;
}

.camera-btn:hover:not(:disabled) {
  background: #333;
}

.identity-copy {
  min-width: 0;
}

.identity-copy h2 {
  font-size: 17px;
  font-weight: 750;
  letter-spacing: -0.02em;
  overflow-wrap: anywhere;
}

.identity-copy>p {
  margin-top: 2px;
  overflow-wrap: anywhere;
}

.role-label {
  display: inline-block;
  margin-top: 7px;
  color: var(--muted);
  font-size: 11px;
}

/* FORM VALIDATION AND SUCCESS */

.field-error,
.success-note {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  margin-top: 8px;
  font-size: 12px;
  font-weight: 600;
}

.field-error {
  color: var(--danger);
}

.success-note {
  color: var(--ok);
}

.field-error i,
.success-note i {
  margin-top: 2px;
}

/* STATS */

.stats {
  display: flex;
  align-items: center;
  gap: 20px;
}

.stat {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat strong {
  color: var(--ink);
  font-size: 14px;
  font-weight: 750;
}

.stat span {
  font-size: 11px;
}

.stat-divider {
  width: 1px;
  height: 30px;
  background: var(--border);
}

.skeleton-stats .stat {
  min-width: 90px;
}

/* PENDING PHOTO */

.pending {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  border-style: dashed;
  border-color: var(--border-strong);
  background: var(--soft);
}

.pending-copy {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.pending-copy strong {
  font-size: 13px;
}

.pending-copy span {
  max-width: 320px;
  overflow: hidden;
  font-size: 11px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pending-actions {
  display: flex;
  gap: 8px;
}

/* DETAILS */

.details h3,
.danger h3 {
  margin-bottom: 12px;
  font-size: 14px;
  font-weight: 750;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 9px;
}

.details-grid>div {
  min-width: 0;
  padding: 10px 11px;
  border: 1px solid var(--border);
  border-radius: 9px;
  background: var(--soft);
}

.details-grid .wide {
  grid-column: 1 / -1;
}

.details-grid dt {
  color: var(--muted);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.details-grid dd {
  margin-top: 3px;
  color: var(--ink);
  font-weight: 600;
  overflow-wrap: anywhere;
}

/* DANGER */

.danger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  border-color: #f1d5d1;
}

.danger p {
  max-width: 480px;
  margin-top: 2px;
}

/* RESPONSIVE */

@media (max-width: 640px) {
  .page-head .btn {
    width: 100%;
  }

  .pending {
    flex-direction: column;
    align-items: stretch;
  }

  .pending-copy span {
    max-width: 100%;
  }

  .pending-actions {
    width: 100%;
  }

  .pending-actions .btn {
    flex: 1;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }

  .details-grid .wide {
    grid-column: auto;
  }

  .danger .btn {
    width: 100%;
  }

  .state-actions {
    width: 100%;
  }

  .state-actions .btn {
    flex: 1 1 140px;
  }
}

@media (max-width: 360px) {
  .profile-summary.card {
    gap: 12px;
  }

  .avatar-frame,
  .sk-avatar {
    width: 60px;
    height: 60px;
    flex-basis: 60px;
  }

  .initials {
    font-size: 19px;
  }

  .stats {
    gap: 12px;
  }

  .stat strong {
    font-size: 12px;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {

  .profile-page *,
  .profile-page *::before,
  .profile-page *::after {
    animation: none !important;
    transition: none !important;
  }
}
</style>