<template>
  <div class="login">
    <header class="top">
      <div class="brand">
        <img :src="logoUrl" alt="" width="28" height="28" />
        <span>CSS Prep</span>
      </div>
    </header>

    <main class="layout">
      <!-- INTRO -->
      <aside class="intro">
        <p class="eyebrow">NC II Readiness · Computer Systems Servicing</p>
        <h1>Practice the skills before the national assessment.</h1>
        <p class="lede">
          Timed practicals, knowledge checks, and clear results, all in one place.
        </p>

        <ul class="points">
          <li>
            <i class="fas fa-screwdriver-wrench" aria-hidden="true"></i>
            <span>Virtual hardware practicals with step-by-step checks</span>
          </li>
          <li>
            <i class="fas fa-list-check" aria-hidden="true"></i>
            <span>Knowledge checks and image identification</span>
          </li>
          <li>
            <i class="fas fa-chart-line" aria-hidden="true"></i>
            <span>Results saved to your records and tracked by competency</span>
          </li>
        </ul>
      </aside>

      <!-- FORM -->
      <section class="panel" aria-labelledby="login-title">
        <form class="form" novalidate @submit.prevent="submitLogin">
          <header class="form-head">
            <h2 id="login-title">{{ visited ? 'Welcome back' : 'Sign in' }}</h2>
            <p>Use your CSS Prep account to continue.</p>
          </header>

          <!-- OFFLINE -->
          <div v-if="!isOnline" class="notice notice-warn" role="status">
            <i class="fas fa-wifi" aria-hidden="true"></i>
            <span>You're offline. Connect to the internet to sign in.</span>
          </div>

          <!-- SESSION EXPIRED (from a redirect) -->
          <div v-else-if="sessionExpired && !activeError && !success && !needsVerification" class="notice"
            role="status">
            <i class="fas fa-user-clock" aria-hidden="true"></i>
            <span>Your session has expired. Sign in again to continue.</span>
          </div>

          <!-- SLOW NETWORK -->
          <p v-if="loading && slowNetwork" class="slow-note" role="status">
            <i class="fas fa-hourglass-half" aria-hidden="true"></i>
            Still signing in. Your connection may be slow.
          </p>

          <!-- EMAIL -->
          <div class="field">
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" v-model.trim="form.email" placeholder="you@email.com"
              autocomplete="username" :class="{ invalid: fieldError('email') }"
              :aria-invalid="fieldError('email') ? 'true' : 'false'" aria-describedby="email-error"
              :disabled="loading || success" />
            <small v-if="fieldError('email')" id="email-error">{{ fieldError('email') }}</small>
          </div>

          <!-- PASSWORD -->
          <div class="field">
            <div class="field-row">
              <label for="password">Password</label>
              <router-link to="/forgot" class="text-link">Forgot password?</router-link>
            </div>

            <div class="input-wrap">
              <input id="password" :type="showPassword ? 'text' : 'password'" v-model="form.password"
                placeholder="Your password" autocomplete="current-password" :class="{ invalid: fieldError('password') }"
                :aria-invalid="fieldError('password') ? 'true' : 'false'" aria-describedby="password-error"
                :disabled="loading || success" />

              <button type="button" class="toggle-password"
                :aria-label="showPassword ? 'Hide password' : 'Show password'" :aria-pressed="showPassword"
                :disabled="loading || success" @click="togglePassword">
                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'" aria-hidden="true"></i>
              </button>
            </div>
            <small v-if="fieldError('password')" id="password-error">{{ fieldError('password') }}</small>
          </div>

          <!-- SUCCESS -->
          <div v-if="success" class="notice notice-ok" role="status">
            <i class="fas fa-circle-check" aria-hidden="true"></i>
            <span>Signed in. Taking you to your dashboard…</span>
          </div>

          <!-- EMAIL NOT VERIFIED -->
          <div v-else-if="needsVerification" class="notice notice-warn verify" role="alert">
            <i class="fas fa-envelope-circle-check" aria-hidden="true"></i>
            <div>
              <strong>Verify your email to sign in</strong>
              <span>
                We sent a confirmation link to
                <b class="verify-email">{{ verifyEmail }}</b>.
                Open it, then sign in again.
              </span>

              <button type="button" class="verify-resend" :disabled="resending" @click="resendVerification">
                {{ resending ? 'Sending…' : 'Send a new link' }}
              </button>

              <span v-if="resendMessage" class="verify-status" role="status">{{ resendMessage }}</span>
            </div>
          </div>

          <!-- ERROR -->
          <div v-else-if="activeError" class="notice notice-error" :class="`tone-${activeError.tone}`" role="alert">
            <i :class="activeError.icon" aria-hidden="true"></i>
            <div>
              <strong>{{ activeError.title }}</strong>
              <span>{{ activeError.text }}</span>
            </div>
          </div>

          <button class="btn-primary" type="submit" :disabled="loading || isLocked || success || !isOnline">
            <span v-if="loading" class="loader" aria-hidden="true"></span>
            <template v-else-if="success">
              <i class="fas fa-check" aria-hidden="true"></i>
              Signed in
            </template>
            <template v-else>Sign in</template>
          </button>

          <div class="divider" aria-hidden="true">
            <span></span>
            <small>or</small>
            <span></span>
          </div>

          <a class="btn-secondary" href="/auth/google">
            <img :src="googleLogoUrl" alt="" width="18" height="18" />
            Continue with Google
          </a>

          <p class="switch">
            New to CSS Prep?
            <router-link to="/register">Create an account</router-link>
          </p>
        </form>
      </section>
    </main>

    <footer class="footer">
      <nav>
        <router-link to="/privacy">Privacy</router-link>
        <router-link to="/terms">Terms</router-link>
      </nav>
      <p>© {{ currentYear }} CSS Prep · SNSU Capstone Project</p>
    </footer>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useHandleLogin } from '@/composables/useHandleLogin'
import logoUrl from '@/assets/lightning.png'
import googleLogoUrl from '@/assets/google-logo.png'

/* =========================================================
   CONSTANTS AND MESSAGES
========================================================= */

const SLOW_AFTER_MS = 4000
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const ERROR_STATES = {
  offline: {
    icon: 'fas fa-wifi',
    title: "You're offline",
    text: 'Check your internet connection, then try again.',
    tone: 'warn',
  },
  network: {
    icon: 'fas fa-plug-circle-xmark',
    title: "Can't reach the server",
    text: 'The connection failed. Check your network and try again.',
    tone: 'error',
  },
  timeout: {
    icon: 'fas fa-hourglass-half',
    title: 'The request took too long',
    text: 'Your network may be slow right now. Try again in a moment.',
    tone: 'error',
  },
  permission: {
    icon: 'fas fa-lock',
    title: 'Access denied',
    text: 'This account cannot sign in right now. Contact your instructor if this is a mistake.',
    tone: 'error',
  },
  session: {
    icon: 'fas fa-user-clock',
    title: 'Your session has expired',
    text: 'Sign in again to continue.',
    tone: 'warn',
  },
  server: {
    icon: 'fas fa-triangle-exclamation',
    title: 'Sign-in is unavailable',
    text: 'Something went wrong on our side. Please try again shortly.',
    tone: 'error',
  },
}

/* =========================================================
   ROUTE, VISITOR, CONNECTION
========================================================= */

const route = useRoute()
const currentYear = new Date().getFullYear()
const visited = ref(false)

const sessionExpired = computed(() =>
  route.query.expired === '1' || route.query.reason === 'session-expired'
)

const isOnline = ref(navigator.onLine)
const slowNetwork = ref(false)
const success = ref(false)
const requestError = ref(null)

let slowTimer = null

/* =========================================================
   FORM STATE
========================================================= */

const form = reactive({ email: '', password: '' })
const localErrors = reactive({ email: '', password: '' })

const {
  loading,
  errors,
  generalError,
  isLocked,
  showPassword,
  needsVerification,
  verifyEmail,
  resending,
  resendMessage,
  handleLogin,
  resendVerification,
  clearVerificationNotice,
  togglePassword,
} = useHandleLogin()

// Laravel returns field errors as arrays, e.g. { email: ["..."] }.
const serverFieldError = (field) => {
  const value = errors.value?.[field]
  return Array.isArray(value) ? value[0] : value || ''
}

// Client-side messages come first, so the learner fixes the problem before a request is sent.
const fieldError = (field) => localErrors[field] || serverFieldError(field)

const activeError = computed(() => {
  if (requestError.value) return requestError.value
  if (generalError.value) return { ...ERROR_STATES.server, text: generalError.value }
  return null
})

/* =========================================================
   VALIDATION
========================================================= */

function validate() {
  localErrors.email = !form.email
    ? 'Enter your email address.'
    : !EMAIL_PATTERN.test(form.email)
      ? 'Enter a valid email, like name@email.com.'
      : ''

  localErrors.password = form.password ? '' : 'Enter your password.'

  return !localErrors.email && !localErrors.password
}

// Clear a message as soon as the learner starts fixing that field.
watch(() => form.email, () => {
  localErrors.email = ''
  // The notice refers to the old address, so remove it when the address changes.
  if (needsVerification.value) clearVerificationNotice()
})

watch(() => form.password, () => {
  localErrors.password = ''
})

/* =========================================================
   ERRORS AND SLOW NETWORK
========================================================= */

function classifyError(error) {
  const code = error?.response?.status

  if (code === 401 || code === 419) return ERROR_STATES.session
  if (code === 403) return ERROR_STATES.permission
  if (code >= 500 || error?.response) return ERROR_STATES.server
  if (error?.code === 'ECONNABORTED' || error?.code === 'ETIMEDOUT') return ERROR_STATES.timeout
  if (!navigator.onLine) return ERROR_STATES.offline

  return ERROR_STATES.network
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
   SUBMIT
========================================================= */

const submitLogin = async () => {
  if (loading.value || isLocked.value || success.value) return

  requestError.value = null

  if (!isOnline.value) {
    requestError.value = ERROR_STATES.offline
    return
  }

  if (!validate()) return

  startSlowTimer()

  try {
    // true only when the learner is signed in. Unverified accounts return false
    // and show the verification notice instead.
    const signedIn = await handleLogin(form.email, form.password)

    if (signedIn) success.value = true
  } catch (error) {
    console.error('Login failed:', error)
    requestError.value = classifyError(error)
  } finally {
    stopSlowTimer()
  }
}

/* =========================================================
   CONNECTION EVENTS AND MOUNT
========================================================= */

function handleOnline() {
  isOnline.value = true
  if (requestError.value === ERROR_STATES.offline) requestError.value = null
}

function handleOffline() {
  isOnline.value = false
}

onMounted(() => {
  if (localStorage.getItem('visited')) {
    visited.value = true
  } else {
    localStorage.setItem('visited', 'true')
  }

  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)

  // Clear quiz caches when the login page opens.
  Object.keys(localStorage).forEach((key) => {
    if (key.startsWith('dash-quiz') || key.startsWith('quiz')) {
      localStorage.removeItem(key)
    }
  })
})

onBeforeUnmount(() => {
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
  clearTimeout(slowTimer)
})
</script>

<style scoped>
/* =========================================================
   CSS PREP LOGIN
   Frosted noir: #FFFFFF #000000 #A9A9A9 #D3D3D3 #696969
   ========================================================= */

.login {
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

.login *,
.login *::before,
.login *::after {
  box-sizing: border-box;
}

.login h1,
.login h2,
.login p {
  margin: 0;
}

.login button,
.login input {
  font: inherit;
}

.login :focus-visible {
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
  font-size: 15px;
  font-weight: 800;
  letter-spacing: 0.02em;
}

.brand img {
  width: 28px;
  height: 28px;
  object-fit: contain;
}

/* LAYOUT */

.layout {
  flex: 1;
  width: 100%;
  max-width: 1080px;
  margin: 0 auto;
  padding: clamp(8px, 3vw, 32px) clamp(16px, 4vw, 40px) 48px;
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 400px);
  gap: clamp(32px, 7vw, 96px);
  align-items: center;
}

/* INTRO */

.intro {
  max-width: 520px;
}

.eyebrow {
  margin-bottom: 14px;
  color: var(--mute);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.intro h1 {
  font-size: clamp(2rem, 3.6vw, 2.9rem);
  font-weight: 800;
  line-height: 1.1;
  letter-spacing: -0.035em;
}

.lede {
  margin-top: 16px;
  color: var(--mute);
  font-size: 1rem;
  line-height: 1.6;
}

.points {
  display: grid;
  gap: 14px;
  margin: 32px 0 0;
  padding: 0;
  list-style: none;
}

.points li {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  color: var(--ink);
  font-size: 14px;
}

.points i {
  width: 30px;
  height: 30px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: var(--white);
  font-size: 13px;
}

/* PANEL */

.panel {
  width: 100%;
  padding: clamp(24px, 4vw, 36px);
  background: var(--white);
  border: 1px solid var(--line);
  border-radius: 14px;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-head {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 4px;
}

.form-head h2 {
  font-size: 1.4rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.form-head p {
  color: var(--mute);
  font-size: 14px;
}

/* FIELDS */

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
}

.field label {
  font-size: 13px;
  font-weight: 600;
  text-align: left;
}

.text-link {
  color: var(--mute);
  font-size: 12.5px;
  font-weight: 600;
  text-decoration: none;
}

.text-link:hover {
  color: var(--ink);
  text-decoration: underline;
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
  /* 16px stops iOS from zooming when the field is focused */
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

.field input.invalid:focus {
  box-shadow: 0 0 0 3px rgba(180, 35, 24, 0.12);
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

.toggle-password {
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

.toggle-password:hover:not(:disabled) {
  background: var(--paper);
  color: var(--ink);
}

.toggle-password:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* NOTICES */

.notice,
.slow-note {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 11px 13px;
  border: 1px solid var(--line);
  border-radius: 9px;
  background: var(--paper);
  color: var(--ink);
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

.notice-warn,
.notice.tone-warn {
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

.slow-note {
  align-items: center;
  border: 0;
  background: transparent;
  color: var(--mute);
  padding: 0;
}

/* BUTTONS */

.btn-primary,
.btn-secondary {
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
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.btn-primary {
  border: 1px solid var(--ink);
  background: var(--ink);
  color: var(--white);
}

.btn-primary:hover:not(:disabled) {
  background: #262626;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  border: 1px solid var(--line);
  background: var(--white);
  color: var(--ink);
  font-weight: 600;
}

.btn-secondary:hover {
  border-color: var(--ink);
}

.btn-secondary img {
  width: 18px;
  height: 18px;
  object-fit: contain;
}

.loader {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: var(--white);
  border-radius: 50%;
  animation: spin 0.75s linear infinite;
}

/* DIVIDER AND SWITCH */

.divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 2px 0;
  color: var(--faint);
}

.divider span {
  flex: 1;
  height: 1px;
  background: var(--line);
}

.divider small {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.switch {
  text-align: center;
  color: var(--mute);
  font-size: 14px;
}

.switch a {
  margin-left: 4px;
  color: var(--ink);
  font-weight: 700;
  text-decoration: none;
}

.switch a:hover {
  text-decoration: underline;
}



.verify {
  flex-wrap: wrap;
}

.verify>div {
  flex: 1;
  min-width: 0;
}

.verify strong {
  display: block;
  margin-bottom: 2px;
}

.verify-email {
  color: var(--ink);
  font-weight: 700;
  overflow-wrap: anywhere;
}

.verify-resend {
  min-height: 36px;
  margin-top: 10px;
  padding: 0 14px;
  border: 1px solid var(--ink);
  border-radius: 8px;
  background: var(--ink);
  color: var(--white);
  font: inherit;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
}

.verify-resend:hover:not(:disabled) {
  background: #262626;
}

.verify-resend:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.verify-status {
  display: block;
  margin-top: 8px;
  font-size: 12.5px;
}

/* FOOTER */

.footer {
  padding: 20px clamp(16px, 4vw, 40px);
  border-top: 1px solid var(--line);
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  color: var(--mute);
  font-size: 12.5px;
}

.footer nav {
  display: flex;
  gap: 16px;
}

.footer a {
  color: var(--mute);
  text-decoration: none;
}

.footer a:hover {
  color: var(--ink);
  text-decoration: underline;
}

/* ANIMATION */

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* RESPONSIVE */

@media (max-width: 900px) {
  .layout {
    grid-template-columns: minmax(0, 1fr);
    gap: 32px;
  }

  .intro {
    max-width: 560px;
  }

  .points {
    display: none;
  }

  .panel {
    max-width: 480px;
  }
}

@media (max-width: 480px) {
  .layout {
    padding-top: 4px;
    gap: 24px;
  }

  .intro h1 {
    font-size: 1.7rem;
  }

  .lede {
    margin-top: 10px;
    font-size: 14px;
  }

  .panel {
    padding: 22px 18px;
    border-radius: 12px;
  }

  .form {
    gap: 14px;
  }

  .btn-primary,
  .btn-secondary {
    min-height: 48px;
  }

  .footer {
    justify-content: center;
    text-align: center;
  }

  .footer nav {
    width: 100%;
    justify-content: center;
  }
}

@media (prefers-reduced-motion: reduce) {

  .login *,
  .login *::before,
  .login *::after {
    animation: none !important;
    transition: none !important;
  }
}
</style>