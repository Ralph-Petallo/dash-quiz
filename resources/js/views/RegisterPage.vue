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
        <!-- PROGRESS -->
        <ol class="steps" aria-label="Sign-up progress">
          <li v-for="(step, index) in steps" :key="step" :class="stepState(index)">
            <span class="dot">
              <i v-if="stepState(index) === 'done'" class="fas fa-check" aria-hidden="true"></i>
              <template v-else>{{ index + 1 }}</template>
            </span>
            <span>{{ step }}</span>
          </li>
        </ol>

        <!-- FORM -->
        <section v-if="view === 'form'" class="panel" aria-labelledby="register-title">
          <header class="head">
            <h1 id="register-title">Create your account</h1>
            <p>Join CSS Prep. It's free.</p>
          </header>

          <!-- OFFLINE -->
          <div v-if="!isOnline" class="notice notice-warn" role="status">
            <i class="fas fa-wifi" aria-hidden="true"></i>
            <span>You're offline. Connect to the internet to create your account.</span>
          </div>

          <!-- SLOW NETWORK -->
          <p v-if="loading && slowNetwork" class="slow" role="status">
            <i class="fas fa-hourglass-half" aria-hidden="true"></i>
            Still working. Your connection may be slow.
          </p>

          <form class="form" novalidate @submit.prevent="handleRegister">
            <div class="row">
              <div class="field">
                <label for="first_name">First name</label>
                <input id="first_name" v-model.trim="form.first_name" type="text" placeholder="Juan"
                  autocomplete="given-name" :class="{ invalid: err('first_name') }" :disabled="loading" />
                <small v-if="err('first_name')">{{ err('first_name') }}</small>
              </div>

              <div class="field">
                <label for="last_name">Last name</label>
                <input id="last_name" v-model.trim="form.last_name" type="text" placeholder="Dela Cruz"
                  autocomplete="family-name" :class="{ invalid: err('last_name') }" :disabled="loading" />
                <small v-if="err('last_name')">{{ err('last_name') }}</small>
              </div>
            </div>

            <div class="field">
              <label for="email">Email address</label>
              <input id="email" v-model.trim="form.email" type="email" placeholder="you@email.com" autocomplete="email"
                :class="{ invalid: err('email') }" :disabled="loading" />
              <small v-if="err('email')">{{ err('email') }}</small>
            </div>

            <div class="field">
              <label for="password">Password</label>
              <div class="input-wrap">
                <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'"
                  :placeholder="`At least ${MIN_PASSWORD} characters`" autocomplete="new-password"
                  :class="{ invalid: err('password') }" :disabled="loading" />
                <button type="button" class="toggle" :aria-label="showPassword ? 'Hide password' : 'Show password'"
                  :aria-pressed="showPassword" :disabled="loading" @click="showPassword = !showPassword">
                  <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'" aria-hidden="true"></i>
                </button>
              </div>
              <small v-if="err('password')">{{ err('password') }}</small>
            </div>

            <div class="field">
              <label for="password_confirmation">Confirm password</label>
              <input id="password_confirmation" v-model="form.password_confirmation" type="password"
                placeholder="Repeat your password" autocomplete="new-password"
                :class="{ invalid: err('password_confirmation') }" :disabled="loading" />
              <small v-if="err('password_confirmation')">{{ err('password_confirmation') }}</small>
            </div>

            <!-- TERMS AND PRIVACY (required) -->
            <div class="field consent" :class="{ invalid: err('accept_terms') }">
              <label class="check">
                <input type="checkbox" v-model="form.accept_terms" :disabled="loading" />
                <span>
                  I have read and agree to the
                  <router-link to="/terms" target="_blank">Terms of Use</router-link>
                  and
                  <router-link to="/privacy" target="_blank">Privacy Policy</router-link>.
                </span>
              </label>
              <small v-if="err('accept_terms')">{{ err('accept_terms') }}</small>
            </div>

            <!-- GENERAL ERROR (server, network, timeout) -->
            <div v-if="requestError" class="notice notice-error" role="alert">
              <i :class="requestError.icon" aria-hidden="true"></i>
              <div>
                <strong>{{ requestError.title }}</strong>
                <span>{{ requestError.text }}</span>
              </div>
            </div>

            <button class="btn-primary" type="submit" :disabled="loading || !isOnline">
              <span v-if="loading" class="loader" aria-hidden="true"></span>
              {{ loading ? 'Creating account…' : 'Create account' }}
            </button>
          </form>

          <p class="switch">
            Already have an account?
            <router-link to="/">Sign in</router-link>
          </p>

          <!-- COOKIE NOTICE -->
          <div v-if="showCookieNotice" class="cookie-notice" role="note">
            <p>
              We use essential cookies to keep you signed in and browser storage to save your progress on this device.
              We don't use advertising cookies.
              <router-link to="/privacy">Privacy Policy</router-link>
            </p>
            <button type="button" @click="dismissCookieNotice">Got it</button>
          </div>
        </section>

        <!-- PENDING: link sent -->
        <section v-else-if="view === 'pending'" class="panel result" aria-live="polite">
          <div class="icon" aria-hidden="true"><i class="fas fa-envelope-circle-check"></i></div>
          <h1>Check your inbox</h1>
          <p class="lede">We sent a confirmation link to</p>
          <p class="chip">{{ registeredEmail }}</p>
          <p class="hint">The link expires in 60 minutes. Check your spam folder if you don't see it.</p>

          <button class="btn-outline" type="button" :disabled="resending || cooldown > 0 || !isOnline"
            @click="resend(registeredEmail)">
            <span v-if="resending" class="loader dark" aria-hidden="true"></span>
            {{ resendLabel }}
          </button>

          <p v-if="resendMessage" class="notice notice-ok" role="status">{{ resendMessage }}</p>
          <p v-if="resendError" class="notice notice-error" role="alert">{{ resendError }}</p>

          <router-link to="/" class="back">Back to sign in</router-link>
        </section>

        <!-- SUCCESS: link clicked -->
        <section v-else-if="view === 'success'" class="panel result" aria-live="polite">
          <div class="icon ok" aria-hidden="true"><i class="fas fa-check"></i></div>
          <h1>Email confirmed</h1>
          <p class="lede">Your account is ready.</p>

          <template v-if="redirectTarget">
            <p class="hint">{{ redirectMessage }}</p>
            <div class="bar" aria-hidden="true">
              <span :style="{ width: redirectProgress + '%' }"></span>
            </div>
          </template>

          <button class="btn-primary" type="button" :disabled="!redirectTarget" @click="goNow">
            Continue
          </button>
        </section>

        <!-- INVALID or RESEND: request a new link -->
        <section v-else class="panel result" aria-live="polite">
          <div class="icon warn" aria-hidden="true"><i class="fas fa-link-slash"></i></div>
          <h1>{{ view === 'invalid' ? 'This link is no longer valid' : 'Confirm your email' }}</h1>
          <p class="lede">
            {{ view === 'invalid'
              ? 'It may have expired or already been used. Enter your email to get a new link.'
              : 'Your email is not confirmed yet. Enter it below to get a new link.' }}
          </p>

          <div class="field left">
            <label for="resend_email">Email address</label>
            <input id="resend_email" v-model.trim="resendEmail" type="email" placeholder="you@email.com"
              autocomplete="email" :disabled="resending" />
          </div>

          <button class="btn-primary" type="button" :disabled="resending || cooldown > 0 || !resendEmail || !isOnline"
            @click="resend(resendEmail)">
            <span v-if="resending" class="loader" aria-hidden="true"></span>
            {{ resendLabel }}
          </button>

          <p v-if="resendMessage" class="notice notice-ok" role="status">{{ resendMessage }}</p>
          <p v-if="resendError" class="notice notice-error" role="alert">{{ resendError }}</p>

          <router-link to="/" class="back">Back to sign in</router-link>
        </section>
      </div>
    </main>

    <footer class="footer">© {{ currentYear }} CSS Prep · SNSU Capstone Project</footer>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/bootstrap'
import { useUser } from '@/composables/useUser'
import logoUrl from '@/assets/lightning.png'

/* =========================================================
   CONSTANTS
========================================================= */

const MIN_PASSWORD = 8
const RESEND_SECONDS = 60
const REDIRECT_SECONDS = 3
const SLOW_AFTER_MS = 4000
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/
const COOKIE_KEY = 'cookieNoticeDismissed'
const currentYear = new Date().getFullYear()

const steps = ['Create account', 'Confirm email', 'Sign in']

const route = useRoute()
const router = useRouter()
const { fetchUser } = useUser()

/* =========================================================
   STATE
========================================================= */

// view: form | pending | resend | success | invalid
const view = ref('form')
const isOnline = ref(navigator.onLine)
const slowNetwork = ref(false)
const showPassword = ref(false)
const showCookieNotice = ref(!localStorage.getItem(COOKIE_KEY))

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  accept_terms: false,
})

const errors = ref({})
const loading = ref(false)
const requestError = ref(null)

const registeredEmail = ref('')
const resendEmail = ref('')
const resending = ref(false)
const resendMessage = ref('')
const resendError = ref('')
const cooldown = ref(0)

const redirectTarget = ref(null)
const redirectCountdown = ref(REDIRECT_SECONDS)

let cooldownTimer = null
let redirectTimer = null
let slowTimer = null

const resendLabel = computed(() =>
  cooldown.value > 0 ? `Resend in ${cooldown.value}s` : 'Send a new link'
)

const redirectMessage = computed(() => {
  const destination = redirectTarget.value?.name === 'login' ? 'the sign-in page' : 'your dashboard'
  return `Taking you to ${destination} in ${redirectCountdown.value}s…`
})

const redirectProgress = computed(() => (redirectCountdown.value / REDIRECT_SECONDS) * 100)

const err = (field) => errors.value?.[field]?.[0] || ''

// Clear a field's message as soon as the learner edits it.
watch(form, () => {
  if (Object.keys(errors.value).length) errors.value = {}
}, { deep: true })

const stepState = (index) => {
  const active = { form: 0, pending: 1, resend: 1, invalid: 1, success: 2 }[view.value]
  if (index < active) return 'done'
  if (index === active) return 'current'
  return ''
}

/* =========================================================
   COOKIE NOTICE
========================================================= */

function dismissCookieNotice() {
  localStorage.setItem(COOKIE_KEY, '1')
  showCookieNotice.value = false
}

/* =========================================================
   ERRORS AND SLOW NETWORK
========================================================= */

const ERRORS = {
  offline: { icon: 'fas fa-wifi', title: "You're offline", text: 'Check your connection, then try again.' },
  network: { icon: 'fas fa-plug-circle-xmark', title: "Can't reach the server", text: 'Check your network and try again.' },
  timeout: { icon: 'fas fa-hourglass-half', title: 'The request took too long', text: 'Your network may be slow. Try again in a moment.' },
  server: { icon: 'fas fa-triangle-exclamation', title: 'Registration failed', text: 'Something went wrong on our side. Please try again shortly.' },
}

function classifyError(error) {
  const code = error?.response?.status
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

/* =========================================================
   VALIDATION
========================================================= */

function validateForm() {
  const next = {}

  if (!form.first_name) next.first_name = ['Enter your first name.']
  if (!form.last_name) next.last_name = ['Enter your last name.']

  if (!form.email) next.email = ['Enter your email address.']
  else if (!EMAIL_PATTERN.test(form.email)) next.email = ['Enter a valid email, like name@email.com.']

  if (!form.password) next.password = ['Create a password.']
  else if (form.password.length < MIN_PASSWORD) next.password = [`Use at least ${MIN_PASSWORD} characters.`]

  if (!form.password_confirmation) next.password_confirmation = ['Confirm your password.']
  else if (form.password !== form.password_confirmation) next.password_confirmation = ['Passwords do not match.']

  if (!form.accept_terms) {
    next.accept_terms = ['Please accept the Terms of Use and Privacy Policy to continue.']
  }

  errors.value = next
  return Object.keys(next).length === 0
}

/* =========================================================
   REGISTER
========================================================= */

async function handleRegister() {
  if (loading.value) return

  requestError.value = null

  if (!isOnline.value) {
    requestError.value = ERRORS.offline
    return
  }

  if (!validateForm()) return

  loading.value = true
  startSlowTimer()

  try {
    await api.get('/sanctum/csrf-cookie')
    await api.post('/api/register', {
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
      accept_terms: form.accept_terms,
    })

    registeredEmail.value = form.email
    resendEmail.value = form.email
    view.value = 'pending'
    startCooldown()
  } catch (error) {
    if (error.response?.status === 422 && error.response.data?.errors) {
      // Server validation errors appear under the matching fields.
      errors.value = error.response.data.errors
    } else {
      requestError.value = classifyError(error)
    }
  } finally {
    loading.value = false
    stopSlowTimer()
  }
}

/* =========================================================
   RESEND
========================================================= */

async function resend(address) {
  if (!address || resending.value || cooldown.value > 0) return

  resending.value = true
  resendMessage.value = ''
  resendError.value = ''

  if (!isOnline.value) {
    resendError.value = ERRORS.offline.text
    resending.value = false
    return
  }

  try {
    await api.get('/sanctum/csrf-cookie')
    const { data } = await api.post('/api/email/resend', { email: address })

    resendMessage.value = data?.message || 'A new link has been sent.'
    startCooldown()
  } catch (error) {
    resendError.value =
      error.response?.data?.errors?.email?.[0] ||
      error.response?.data?.message ||
      classifyError(error).text
  } finally {
    resending.value = false
  }
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
   AFTER CONFIRMATION
   A session already in this browser goes to its dashboard.
   Otherwise the learner goes to sign-in.
========================================================= */

async function prepareRedirect() {
  let user = null

  try {
    user = await fetchUser(true)
  } catch {
    user = null
  }

  if (!user) redirectTarget.value = { name: 'login' }
  else if (user.role === 'admin') redirectTarget.value = { name: 'admin-dashboard' }
  else redirectTarget.value = { name: 'user-home' }

  redirectCountdown.value = REDIRECT_SECONDS

  clearInterval(redirectTimer)
  redirectTimer = setInterval(() => {
    redirectCountdown.value -= 1

    if (redirectCountdown.value <= 0) {
      clearInterval(redirectTimer)
      goNow()
    }
  }, 1000)
}

function goNow() {
  clearInterval(redirectTimer)
  if (redirectTarget.value) router.push(redirectTarget.value)
}

/* =========================================================
   CONNECTION EVENTS AND ENTRY POINTS
   /email-verified?status=success|invalid   (from the email link)
   /register?step=resend&email=...          (from an unverified sign-in)
========================================================= */

function handleOnline() {
  isOnline.value = true
}

function handleOffline() {
  isOnline.value = false
}

onMounted(() => {
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)

  if (route.name === 'email-verified') {
    if (route.query.status === 'success') {
      view.value = 'success'
      prepareRedirect()
    } else {
      view.value = 'invalid'
    }
  } else if (route.query.step === 'resend') {
    view.value = 'resend'
    resendEmail.value = String(route.query.email ?? '')
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
  clearInterval(cooldownTimer)
  clearInterval(redirectTimer)
  clearTimeout(slowTimer)
})
</script>

<style scoped>
.page {
  --ink: #0a0a0a;
  --mute: #5f5f5f;
  --faint: #8c8c8c;
  --line: #dcdcdc;
  --paper: #f5f5f4;
  --white: #ffffff;
  --error: #b42318;
  --error-bg: #fdf2f1;
  --ok: #11813d;
  --ok-bg: #eef8f1;
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
.page h2,
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

/* TOP BAR */

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

/* LAYOUT */

.main {
  flex: 1;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: clamp(8px, 3vw, 32px) clamp(16px, 4vw, 24px) 48px;
}

.stack {
  width: 100%;
  max-width: 420px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

/* PROGRESS */

.steps {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.steps li {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 7px;
  color: var(--faint);
  font-size: 12px;
  font-weight: 600;
}

.steps li.current {
  color: var(--ink);
}

.steps li.done {
  color: var(--mute);
}

.steps li span:last-child {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dot {
  width: 22px;
  height: 22px;
  flex: 0 0 22px;
  display: grid;
  place-items: center;
  border: 1px solid var(--line);
  border-radius: 50%;
  background: var(--white);
  font-size: 11px;
}

.steps li.current .dot {
  border-color: var(--ink);
  background: var(--ink);
  color: var(--white);
}

.steps li.done .dot {
  border-color: var(--mute);
  background: var(--mute);
  color: var(--white);
}

/* PANEL */

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

.row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

/* FIELDS */

.field {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field.left {
  width: 100%;
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
  /* 16px stops iOS zooming on focus */
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

.input-wrap {
  position: relative;
}

.input-wrap input {
  padding-right: 48px;
}

.toggle {
  position: absolute;
  top: 50%;
  right: 6px;
  transform: translateY(-50%);
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  padding: 0;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: var(--mute);
  cursor: pointer;
}

.toggle:hover:not(:disabled) {
  background: var(--paper);
  color: var(--ink);
}

.toggle:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* CONSENT */

.consent .check {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 13px;
  font-weight: 400;
  line-height: 1.5;
  cursor: pointer;
}

.consent .check input {
  width: 18px;
  height: 18px;
  min-height: 0;
  margin-top: 2px;
  flex-shrink: 0;
  accent-color: var(--ink);
}

.consent .check a {
  color: var(--ink);
  font-weight: 700;
}

.consent.invalid small {
  color: var(--error);
  font-size: 12.5px;
}

/* NOTICES */

.notice {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 11px 13px;
  border: 1px solid var(--line);
  border-radius: 9px;
  font-size: 13px;
  line-height: 1.45;
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

.notice-ok {
  border-color: #bfe2cc;
  background: var(--ok-bg);
  color: var(--ok);
}

.slow {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--mute);
  font-size: 13px;
}

/* BUTTONS */

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
  text-decoration: none;
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
}

.switch a:hover,
.back:hover {
  text-decoration: underline;
}

/* COOKIE NOTICE */

.cookie-notice {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-top: 14px;
  border-top: 1px solid var(--line);
  color: var(--mute);
  font-size: 12.5px;
  line-height: 1.5;
}

.cookie-notice p {
  margin: 0;
}

.cookie-notice a {
  color: var(--ink);
  font-weight: 600;
}

.cookie-notice button {
  flex-shrink: 0;
  min-height: 34px;
  padding: 0 12px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: var(--white);
  color: var(--ink);
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
}

.cookie-notice button:hover {
  border-color: var(--ink);
}

/* RESULT STATES */

.result {
  align-items: center;
  text-align: center;
}

.result .field.left {
  text-align: left;
}

.result h1 {
  font-size: 1.3rem;
  font-weight: 800;
  letter-spacing: -0.02em;
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

.icon.warn {
  border-color: #efc2be;
  background: var(--error-bg);
  color: var(--error);
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

.bar {
  width: 100%;
  height: 4px;
  overflow: hidden;
  border-radius: 999px;
  background: var(--line);
}

.bar span {
  display: block;
  height: 100%;
  background: var(--ink);
  transition: width 1s linear;
}

/* FOOTER */

.footer {
  padding: 18px 16px;
  border-top: 1px solid var(--line);
  color: var(--mute);
  font-size: 12.5px;
  text-align: center;
}

/* ANIMATION */

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* PHONE */

@media (max-width: 480px) {
  .row {
    grid-template-columns: minmax(0, 1fr);
  }

  .panel {
    padding: 22px 18px;
    border-radius: 12px;
  }

  .steps li span:last-child {
    display: none;
  }

  .cookie-notice {
    flex-direction: column;
    align-items: stretch;
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