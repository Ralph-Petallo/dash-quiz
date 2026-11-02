<template>
  <div class="page">
    <header class="top">
      <router-link to="/" class="brand">
        <img :src="logoUrl" alt="" width="28" height="28" />
        <span>CSS Prep</span>
      </router-link>
    </header>

    <main class="main">
      <div class="stack">
        <!-- SENT -->
        <section v-if="view === 'sent'" class="panel result" aria-live="polite">
          <div class="icon ok" aria-hidden="true"><i class="fas fa-envelope-circle-check"></i></div>
          <h1>Check your inbox</h1>
          <p class="lede">If an account exists for</p>
          <p class="chip">{{ sentTo }}</p>
          <p class="lede">a reset link is on its way.</p>
          <p class="hint">The link expires in 60 minutes. Check your spam folder if you don't see it.</p>

          <div v-if="requestError" class="notice notice-error" role="alert">
            <i :class="requestError.icon" aria-hidden="true"></i>
            <div>
              <strong>{{ requestError.title }}</strong>
              <span>{{ requestError.text }}</span>
            </div>
          </div>

          <button class="btn-outline" type="button" :disabled="sending || cooldown > 0 || !isOnline" @click="resend">
            <span v-if="sending" class="loader dark" aria-hidden="true"></span>
            {{ resendLabel }}
          </button>

          <router-link to="/" class="back">Back to sign in</router-link>
        </section>

        <!-- FORM -->
        <section v-else class="panel" aria-labelledby="forgot-title">
          <header class="head">
            <h1 id="forgot-title">Forgot your password?</h1>
            <p>Enter your email and we'll send you a reset link.</p>
          </header>

          <!-- OFFLINE -->
          <div v-if="!isOnline" class="notice notice-warn" role="status">
            <i class="fas fa-wifi" aria-hidden="true"></i>
            <span>You're offline. Connect to the internet to request a link.</span>
          </div>

          <!-- SLOW NETWORK -->
          <p v-if="sending && slowNetwork" class="slow" role="status">
            <i class="fas fa-hourglass-half" aria-hidden="true"></i>
            Still sending. Your connection may be slow.
          </p>

          <form class="form" novalidate @submit.prevent="submit">
            <div class="field">
              <label for="email">Email address</label>
              <input id="email" v-model.trim="email" type="email" placeholder="you@email.com" autocomplete="email"
                :class="{ invalid: fieldMessage }" :aria-invalid="fieldMessage ? 'true' : 'false'"
                aria-describedby="email-error" :disabled="sending" />
              <small v-if="fieldMessage" id="email-error">{{ fieldMessage }}</small>
            </div>

            <div v-if="requestError" class="notice notice-error" role="alert">
              <i :class="requestError.icon" aria-hidden="true"></i>
              <div>
                <strong>{{ requestError.title }}</strong>
                <span>{{ requestError.text }}</span>
              </div>
            </div>

            <button class="btn-primary" type="submit" :disabled="sending || !isOnline">
              <span v-if="sending" class="loader" aria-hidden="true"></span>
              {{ sending ? 'Sending…' : 'Send reset link' }}
            </button>
          </form>

          <p class="switch">
            Remembered your password?
            <router-link to="/">Sign in</router-link>
          </p>
        </section>
      </div>
    </main>

    <footer class="footer">© {{ currentYear }} CSS Prep · SNSU Capstone Project</footer>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/bootstrap'
import logoUrl from '@/assets/lightning.png'

/* =========================================================
   CONSTANTS
========================================================= */

const RESEND_SECONDS = 60
const SLOW_AFTER_MS = 4000
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/
const currentYear = new Date().getFullYear()

const ERRORS = {
  offline: { icon: 'fas fa-wifi', title: "You're offline", text: 'Connect to the internet, then try again.' },
  network: { icon: 'fas fa-plug-circle-xmark', title: "Can't reach the server", text: 'Check your network and try again.' },
  timeout: { icon: 'fas fa-hourglass-half', title: 'The request took too long', text: 'Your network may be slow. Try again in a moment.' },
  throttled: { icon: 'fas fa-clock', title: 'Too many requests', text: 'Please wait a few minutes before requesting another link.' },
  server: { icon: 'fas fa-triangle-exclamation', title: "Couldn't send the link", text: 'Something went wrong on our side. Please try again shortly.' },
}

const route = useRoute()

/* =========================================================
   STATE
========================================================= */

const view = ref('form') // form | sent
const email = ref(String(route.query.email ?? ''))
const sentTo = ref('')
const fieldMessage = ref('')
const requestError = ref(null)
const sending = ref(false)
const slowNetwork = ref(false)
const isOnline = ref(navigator.onLine)
const cooldown = ref(0)

let cooldownTimer = null
let slowTimer = null

const resendLabel = computed(() =>
  cooldown.value > 0 ? `Resend in ${cooldown.value}s` : 'Send another link'
)

watch(email, () => {
  fieldMessage.value = ''
  requestError.value = null
})

/* =========================================================
   ERRORS AND SLOW NETWORK
========================================================= */

function classifyError(error) {
  const code = error?.response?.status

  if (code === 429) return ERRORS.throttled
  if (code >= 500 || error?.response) return ERRORS.server
  if (error?.code === 'ECONNABORTED' || error?.code === 'ETIMEDOUT') return ERRORS.timeout
  if (!navigator.onLine) return ERRORS.offline

  return ERRORS.network
}

function startSlowTimer() {
  slowNetwork.value = false
  clearTimeout(slowTimer)
  slowTimer = setTimeout(() => { slowNetwork.value = true }, SLOW_AFTER_MS)
}

function stopSlowTimer() {
  clearTimeout(slowTimer)
  slowNetwork.value = false
}

function startCooldown(seconds = RESEND_SECONDS) {
  cooldown.value = seconds
  clearInterval(cooldownTimer)

  cooldownTimer = setInterval(() => {
    cooldown.value -= 1

    if (cooldown.value <= 0) {
      cooldown.value = 0
      clearInterval(cooldownTimer)
    }
  }, 1000)
}

/* =========================================================
   SEND
========================================================= */

function validate() {
  if (!email.value) {
    fieldMessage.value = 'Enter your email address.'
    return false
  }

  if (!EMAIL_PATTERN.test(email.value)) {
    fieldMessage.value = 'Enter a valid email, like name@email.com.'
    return false
  }

  return true
}

async function send(address) {
  if (sending.value) return

  requestError.value = null

  if (!isOnline.value) {
    requestError.value = ERRORS.offline
    return
  }

  sending.value = true
  startSlowTimer()

  try {
    await api.post('/api/forgot-password', { email: address })

    sentTo.value = address
    view.value = 'sent'
    startCooldown()
  } catch (error) {
    const code = error?.response?.status

    if (code === 422) {
      fieldMessage.value =
        error.response.data?.errors?.email?.[0] || 'We could not find that email address.'
    } else {
      requestError.value = classifyError(error)
    }
  } finally {
    sending.value = false
    stopSlowTimer()
  }
}

function submit() {
  if (validate()) send(email.value)
}

function resend() {
  if (cooldown.value === 0) send(sentTo.value)
}

/* =========================================================
   CONNECTION EVENTS
========================================================= */

function handleOnline() {
  isOnline.value = true
  if (requestError.value === ERRORS.offline) requestError.value = null
}

function handleOffline() {
  isOnline.value = false
}

onMounted(() => {
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)
})

onBeforeUnmount(() => {
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
  clearInterval(cooldownTimer)
  clearTimeout(slowTimer)
})
</script>

<style scoped>
/* Same look as the login page. Keep these in sync or move them to a shared stylesheet. */

.page {
  --ink: #0a0a0a;
  --mute: #5f5f5f;
  --faint: #8c8c8c;
  --line: #dcdcdc;
  --paper: #f5f5f4;
  --white: #ffffff;
  --error: #b42318;
  --error-bg: #fdf2f1;
  --warn: #7a5200;
  --warn-bg: #fff8e6;
  --warn-line: #e0b451;

  min-height: 100vh;
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  background: var(--paper);
  color: var(--ink);
  font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  font-size: 15px;
  line-height: 1.5;
  text-align: left;
  box-sizing: border-box;
}

.page *,
.page *::before,
.page *::after {
  box-sizing: border-box;
}

.page h1,
.page p {
  margin: 0;
}

.page button,
.page input {
  font: inherit;
}

.page :focus-visible {
  outline: 2px solid var(--ink);
  outline-offset: 2px;
}

.top {
  padding: 18px clamp(16px, 4vw, 40px);
}

.brand {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: var(--ink);
  font-size: 15px;
  font-weight: 800;
  text-decoration: none;
}

.brand img {
  width: 28px;
  height: 28px;
  object-fit: contain;
}

.main {
  flex: 1;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: clamp(8px, 3vw, 40px) clamp(16px, 4vw, 24px) 48px;
}

.stack {
  width: 100%;
  max-width: 420px;
}

.panel {
  padding: clamp(24px, 4vw, 34px);
  border: 1px solid var(--line);
  border-radius: 14px;
  background: var(--white);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.head {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.head h1 {
  font-size: 1.4rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.head p,
.lede {
  color: var(--mute);
  font-size: 14px;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field label {
  font-size: 13px;
  font-weight: 600;
}

.field input {
  width: 100%;
  min-height: 46px;
  padding: 11px 14px;
  border: 1px solid var(--line);
  border-radius: 9px;
  background: var(--white);
  color: var(--ink);
  font-size: 16px;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.field input::placeholder {
  color: var(--faint);
}

.field input:hover:not(:disabled) {
  border-color: var(--faint);
}

.field input:focus {
  outline: none;
  border-color: var(--ink);
  box-shadow: 0 0 0 3px rgba(10, 10, 10, 0.08);
}

.field input:disabled {
  background: var(--paper);
  opacity: 0.7;
}

.field input.invalid {
  border-color: var(--error);
}

.field small {
  color: var(--error);
  font-size: 12.5px;
  line-height: 1.4;
}

.notice {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 11px 13px;
  border: 1px solid var(--line);
  border-radius: 9px;
  font-size: 13px;
  line-height: 1.45;
  text-align: left;
}

.notice i {
  margin-top: 2px;
}

.notice div {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.notice strong {
  font-weight: 700;
}

.notice-warn {
  border-color: var(--warn-line);
  background: var(--warn-bg);
  color: var(--warn);
}

.notice-error {
  border-color: #efc2be;
  background: var(--error-bg);
  color: var(--error);
}

.slow {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--mute);
  font-size: 13px;
}

.btn-primary,
.btn-outline {
  width: 100%;
  min-height: 46px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 9px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease;
}

.btn-primary {
  border: 1px solid var(--ink);
  background: var(--ink);
  color: var(--white);
}

.btn-primary:hover:not(:disabled) {
  background: #262626;
}

.btn-outline {
  border: 1px solid var(--line);
  background: var(--white);
  color: var(--ink);
  font-weight: 600;
}

.btn-outline:hover:not(:disabled) {
  border-color: var(--ink);
}

.btn-primary:disabled,
.btn-outline:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.loader {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: var(--white);
  border-radius: 50%;
  animation: spin 0.75s linear infinite;
}

.loader.dark {
  border-color: var(--line);
  border-top-color: var(--ink);
}

.switch {
  text-align: center;
  color: var(--mute);
  font-size: 14px;
}

.switch a,
.back {
  color: var(--ink);
  font-weight: 700;
  text-decoration: none;
  text-align: center;
}

.switch a:hover,
.back:hover {
  text-decoration: underline;
}

/* SENT STATE */

.result {
  align-items: center;
  text-align: center;
}

.result h1 {
  font-size: 1.3rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.result .notice {
  width: 100%;
}

.icon {
  width: 56px;
  height: 56px;
  display: grid;
  place-items: center;
  border: 1px solid var(--line);
  border-radius: 50%;
  background: var(--paper);
  font-size: 20px;
}

.icon.ok {
  border-color: var(--ink);
  background: var(--ink);
  color: var(--white);
}

.chip {
  max-width: 100%;
  padding: 6px 12px;
  border: 1px solid var(--line);
  border-radius: 999px;
  background: var(--paper);
  font-size: 14px;
  font-weight: 700;
  overflow-wrap: anywhere;
}

.hint {
  color: var(--mute);
  font-size: 13px;
  line-height: 1.6;
}

.footer {
  padding: 18px 16px;
  border-top: 1px solid var(--line);
  color: var(--mute);
  font-size: 12.5px;
  text-align: center;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 480px) {
  .panel {
    padding: 22px 18px;
    border-radius: 12px;
  }
}

@media (prefers-reduced-motion: reduce) {

  .page *,
  .page *::before,
  .page *::after {
    animation: none !important;
    transition: none !important;
  }
}
</style>