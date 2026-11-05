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
        <section class="panel" aria-labelledby="register-title">
          <template v-if="!submitted">
            <header class="head">
              <h1 id="register-title">Create your account</h1>
              <p>Join CSS Prep. It’s free.</p>
            </header>

            <div v-if="!isOnline" class="notice notice-warn" role="status">
              <svg class="ui-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M2 8.8a15 15 0 0 1 20 0M5 12a10 10 0 0 1 14 0m-11 3.2a5 5 0 0 1 8 0M12 19h.01"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" />
              </svg>
              <span>You’re offline. Connect to the internet to create your account.</span>
            </div>

            <p v-if="loading && slowNetwork" class="slow" role="status">
              <svg class="ui-icon spin-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" opacity=".25" />
                <path d="M12 3a9 9 0 0 1 9 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
              </svg>
              Still working. Your connection may be slow.
            </p>

            <form class="form" novalidate @submit.prevent="handleRegister">
              <div class="row">
                <div class="field">
                  <label for="first_name">First name</label>
                  <input id="first_name" v-model.trim="form.first_name" name="first_name" type="text"
                    autocomplete="given-name" maxlength="50" required :aria-invalid="Boolean(err('first_name'))"
                    :aria-describedby="err('first_name') ? 'first-name-error' : undefined"
                    :class="{ invalid: err('first_name') }" :disabled="loading" />
                  <small v-if="err('first_name')" id="first-name-error" role="alert">
                    {{ err('first_name') }}
                  </small>
                </div>

                <div class="field">
                  <label for="last_name">Last name</label>
                  <input id="last_name" v-model.trim="form.last_name" name="last_name" type="text"
                    autocomplete="family-name" maxlength="50" required :aria-invalid="Boolean(err('last_name'))"
                    :aria-describedby="err('last_name') ? 'last-name-error' : undefined"
                    :class="{ invalid: err('last_name') }" :disabled="loading" />
                  <small v-if="err('last_name')" id="last-name-error" role="alert">
                    {{ err('last_name') }}
                  </small>
                </div>
              </div>

              <div class="field">
                <label for="email">Email address</label>
                <input id="email" v-model.trim="form.email" name="email" type="email" autocomplete="email"
                  maxlength="254" required :aria-invalid="Boolean(err('email'))"
                  :aria-describedby="err('email') ? 'email-error' : undefined" :class="{ invalid: err('email') }"
                  :disabled="loading" />
                <small v-if="err('email')" id="email-error" role="alert">
                  {{ err('email') }}
                </small>
              </div>

              <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap">
                  <input id="password" v-model="form.password" name="password"
                    :type="showPassword ? 'text' : 'password'" autocomplete="new-password" minlength="8" maxlength="128"
                    required :aria-invalid="Boolean(err('password'))"
                    :aria-describedby="err('password') ? 'password-error' : undefined"
                    :class="{ invalid: err('password') }" :disabled="loading" />
                  <button type="button" class="toggle" :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    :aria-pressed="showPassword" :disabled="loading" @click="showPassword = !showPassword">
                    <svg v-if="showPassword" class="ui-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path
                        d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A11 11 0 0 1 12 5c5 0 9 7 9 7a15 15 0 0 1-3.1 3.8M6.2 6.2C4.2 7.5 3 12 3 12s3 7 9 7a10 10 0 0 0 4-.8"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <svg v-else class="ui-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M3 12s3-7 9-7 9 7 9 7-3 7-9 7-9-7-9-7Z" stroke="currentColor" stroke-width="1.8" />
                      <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8" />
                    </svg>
                  </button>
                </div>
                <small v-if="err('password')" id="password-error" role="alert">
                  {{ err('password') }}
                </small>
              </div>

              <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" v-model="form.password_confirmation" name="password_confirmation"
                  type="password" autocomplete="new-password" maxlength="128" required
                  :aria-invalid="Boolean(err('password_confirmation'))" :aria-describedby="err('password_confirmation')
                      ? 'password-confirmation-error'
                      : undefined
                    " :class="{ invalid: err('password_confirmation') }" :disabled="loading" />
                <small v-if="err('password_confirmation')" id="password-confirmation-error" role="alert">
                  {{ err('password_confirmation') }}
                </small>
              </div>

              <div class="field consent">
                <label class="check">
                  <input v-model="form.accept_terms" type="checkbox" name="accept_terms" required :disabled="loading"
                    :aria-invalid="Boolean(err('accept_terms'))"
                    :aria-describedby="err('accept_terms') ? 'terms-error' : undefined" />
                  <span>
                    I agree to the
                    <router-link to="/terms" target="_blank" rel="noopener noreferrer">
                      Terms of Use
                    </router-link>
                    and
                    <router-link to="/privacy" target="_blank" rel="noopener noreferrer">
                      Privacy Policy
                    </router-link>.
                  </span>
                </label>
                <small v-if="err('accept_terms')" id="terms-error" role="alert">
                  {{ err('accept_terms') }}
                </small>
              </div>

              <div v-if="requestError" class="notice notice-error" role="alert">
                <svg class="ui-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M12 3 2.8 19h18.4L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                  <path d="M12 9v4m0 3h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
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
          </template>

          <div v-else class="result" role="status" aria-live="polite">
            <div class="icon" aria-hidden="true">
              <svg class="ui-icon" viewBox="0 0 24 24" fill="none">
                <path d="M4 6h16v12H4zM4 7l8 6 8-6M16 16l2 2 4-4" stroke="currentColor" stroke-width="1.8"
                  stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </div>
            <h1>Check your inbox</h1>
            <p class="lede">
              Your account has been created. Check your email for a verification link.
            </p>
            <p v-if="registeredEmail" class="chip">{{ registeredEmail }}</p>
            <p class="hint">If you don’t see the message, check your spam folder.</p>
            <router-link to="/" class="back">Back to sign in</router-link>
          </div>
        </section>
      </div>
    </main>

    <footer class="footer">
      © {{ currentYear }} CSS Prep · SNSU Capstone Project
    </footer>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import api from '@/bootstrap'
import logoUrl from '@/assets/lightning.png'

const MIN_PASSWORD = 8
const MAX_PASSWORD = 128
const NAME_PATTERN = /^[\p{L}\p{M}][\p{L}\p{M}\s'’-]*$/u
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/

const currentYear = new Date().getFullYear()

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
const submitted = ref(false)
const registeredEmail = ref('')
const requestError = ref(null)
const showPassword = ref(false)
const isOnline = ref(typeof navigator === 'undefined' ? true : navigator.onLine)
const slowNetwork = ref(false)

const ERRORS = {
  offline: {
    title: 'You’re offline',
    text: 'Check your connection, then try again.',
  },
  network: {
    title: 'Can’t reach the server',
    text: 'Check your connection and try again.',
  },
  server: {
    title: 'Registration failed',
    text: 'Something went wrong. Please try again shortly.',
  },
}

let slowTimer = null

function firstError(value) {
  if (Array.isArray(value)) return value[0] || ''
  return typeof value === 'string' ? value : ''
}

function err(field) {
  return firstError(errors.value?.[field])
}

watch(
  form,
  () => {
    if (Object.keys(errors.value).length) {
      errors.value = {}
    }
    requestError.value = null
  },
  { deep: true }
)

function validateForm() {
  const next = {}
  const firstName = form.first_name.trim()
  const lastName = form.last_name.trim()
  const email = form.email.trim()

  if (!firstName) {
    next.first_name = ['Enter your first name.']
  } else if (firstName.length > 50 || !NAME_PATTERN.test(firstName)) {
    next.first_name = ['Use a valid name of up to 50 characters.']
  }

  if (!lastName) {
    next.last_name = ['Enter your last name.']
  } else if (lastName.length > 50 || !NAME_PATTERN.test(lastName)) {
    next.last_name = ['Use a valid name of up to 50 characters.']
  }

  if (!email) {
    next.email = ['Enter your email address.']
  } else if (email.length > 254 || !EMAIL_PATTERN.test(email)) {
    next.email = ['Enter a valid email address.']
  }

  if (!form.password) {
    next.password = ['Create a password.']
  } else if (form.password.length < MIN_PASSWORD) {
    next.password = [`Use at least ${MIN_PASSWORD} characters.`]
  } else if (form.password.length > MAX_PASSWORD) {
    next.password = [`Use no more than ${MAX_PASSWORD} characters.`]
  }

  if (!form.password_confirmation) {
    next.password_confirmation = ['Confirm your password.']
  } else if (form.password !== form.password_confirmation) {
    next.password_confirmation = ['Passwords do not match.']
  }

  if (!form.accept_terms) {
    next.accept_terms = [
      'Accept the Terms of Use and Privacy Policy to continue.',
    ]
  }

  errors.value = next
  return Object.keys(next).length === 0
}

function classifyError(error) {
  const status = error?.response?.status

  if (typeof navigator !== 'undefined' && !navigator.onLine) {
    return ERRORS.offline
  }

  if (status === 429) {
    return {
      title: 'Too many attempts',
      text: 'Please wait a little while, then try again.',
    }
  }

  if (status === 419) {
    return {
      title: 'Session expired',
      text: 'Refresh the page, then try again.',
    }
  }

  if (status >= 500) {
    return ERRORS.server
  }

  if (error?.response) {
    return {
      title: 'Registration could not be completed',
      text:
        error.response.data?.message ||
        'Check your information and try again.',
    }
  }

  return ERRORS.network
}

function startSlowTimer() {
  slowNetwork.value = false
  clearTimeout(slowTimer)

  slowTimer = setTimeout(() => {
    slowNetwork.value = true
  }, 4000)
}

function stopSlowTimer() {
  clearTimeout(slowTimer)
  slowNetwork.value = false
}

async function handleRegister() {
  if (loading.value) return

  errors.value = {}
  requestError.value = null

  if (!isOnline.value) {
    requestError.value = ERRORS.offline
    return
  }

  if (!validateForm()) return

  loading.value = true
  startSlowTimer()

  try {
    // Keep this if your API uses Laravel Sanctum's cookie-based CSRF protection.
    await api.get('/sanctum/csrf-cookie')

    const { data } = await api.post('/api/register', {
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      email: form.email.trim().toLowerCase(),
      password: form.password,
      password_confirmation: form.password_confirmation,
      accept_terms: form.accept_terms,
    })

    registeredEmail.value = data?.email || form.email.trim().toLowerCase()
    submitted.value = true

    // Clear credentials from component state after successful submission.
    form.password = ''
    form.password_confirmation = ''
  } catch (error) {
    const serverErrors = error?.response?.data?.errors

    if (serverErrors && typeof serverErrors === 'object') {
      errors.value = serverErrors
    } else {
      requestError.value = classifyError(error)
    }
  } finally {
    loading.value = false
    stopSlowTimer()
  }
}

function handleOnline() {
  isOnline.value = true
  requestError.value = null
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

/* Header */

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

/* Layout */

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

/* Panel */

.panel {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: clamp(24px, 4vw, 34px);
  border: 1px solid var(--line);
  border-radius: 14px;
  background: var(--white);
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

/* Fields */

.field {
  min-width: 0;
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

.field input:hover:not(:disabled) {
  border-color: var(--faint);
}

.field input:focus {
  outline: none;
  border-color: var(--ink);
  box-shadow: 0 0 0 3px rgb(10 10 10 / 8%);
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
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  padding: 0;
  transform: translateY(-50%);
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

/* Icons */

.ui-icon {
  width: 20px;
  height: 20px;
  flex: 0 0 auto;
  display: block;
}

.spin-icon {
  animation: spin 1s linear infinite;
}

/* Consent */

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
  flex-shrink: 0;
  margin-top: 2px;
  accent-color: var(--ink);
}

.consent .check a {
  color: var(--ink);
  font-weight: 700;
}

/* Notices */

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

.notice>.ui-icon {
  margin-top: 1px;
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

/* Buttons */

.btn-primary {
  width: 100%;
  min-height: 46px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1px solid var(--ink);
  border-radius: 9px;
  background: var(--ink);
  color: var(--white);
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease;
}

.btn-primary:hover:not(:disabled) {
  background: #262626;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.loader {
  width: 18px;
  height: 18px;
  border: 2px solid rgb(255 255 255 / 30%);
  border-top-color: var(--white);
  border-radius: 50%;
  animation: spin 0.75s linear infinite;
}

.switch {
  color: var(--mute);
  font-size: 14px;
  text-align: center;
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

/* Success state */

.result {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  text-align: center;
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
}

.icon .ui-icon {
  width: 26px;
  height: 26px;
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

/* Footer */

.footer {
  padding: 18px 16px;
  border-top: 1px solid var(--line);
  color: var(--mute);
  font-size: 12.5px;
  text-align: center;
}

/* Animation */

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Mobile */

@media (max-width: 480px) {
  .row {
    grid-template-columns: minmax(0, 1fr);
  }

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