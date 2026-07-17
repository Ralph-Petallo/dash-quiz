<template>
  <div class="auth-wrapper">

    <header class="auth-header">
      <div class="header-inner">
        <div class="brand">
          <img src="/public/bolt.png" alt="Logo" width="32" height="32">
          <span class="brand-name">Dash<span>Quiz</span></span>
        </div>
        <span class="portal">Assessment Portal</span>
      </div>
    </header>

    <main class="container">

      <section class="hero">
        <div class="hero-badge">✦ SNSU Capstone Project</div>
        <h1>
          Learning is <span>better</span><br />
          when we do it <span>together</span>
        </h1>
        <p>Practice, learn, and improve your skills with Dash Quiz.</p>
      </section>

      <section class="form-section">
        <form class="card" @submit.prevent="handleLogin">
          <h2>Welcome{{ visited ? ' Back!' : '!' }}</h2>
          <p class="subtitle">Sign in to your account</p>

          <div class="field">
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" v-model.trim="form.email" placeholder="@example.com"
              :class="{ error: errors.email }" autocomplete="off" />
            <small v-if="errors.email">{{ errors.email[0] }}</small>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" v-model="form.password" placeholder="••••••"
              :class="{ error: errors.password }" autocomplete="off" />
            <small v-if="errors.password">{{ errors.password[0] }}</small>
          </div>

          <div v-if="generalError" class="alert">
            {{ generalError }}
          </div>

          <button class="btn" type="submit" :disabled="loading || isLocked">
            <span v-if="!loading">Login</span>
            <span v-else class="loader"></span>
          </button>

          <div class="footer-links">
            <div class="forgot-link">
              <router-link to="/forgot">Forgot password?</router-link>
            </div>
            <div class="divider">
              <div class="line"></div>or<div class="line"></div>
            </div>
            <router-link to="/register" class="btn-register">Create account</router-link>
          </div>
        </form>
      </section>

    </main>

    <footer class="auth-footer">
      © {{ new Date().getFullYear() }} Dash Quiz • SNSU Capstone Project
    </footer>

  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUser } from '@/composables/useUser'
import axios from 'axios'

const visited = ref(false) // check if user has visited the page before to prevent multiple login attempts on page reload

const router = useRouter()
const { fetchUser } = useUser()

const form = reactive({ email: '', password: '' })
const loading = ref(false)
const errors = ref({})
const generalError = ref('')

const maxAttempts = 3
const attempts = ref(0)
const isLocked = ref(false)

function newVisitorCheck() {
  if (localStorage.getItem('visited')) {
    visited.value = true
  } else {
    localStorage.setItem('visited', 'true')
  }
}
newVisitorCheck()

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
}

console.log(window.innerWidth)

function timeout(seconds) {
  setTimeout(() => {
    attempts.value = 0
    isLocked.value = false
  }, seconds * 1000)
}

const handleLogin = async () => {
  if (loading.value || isLocked.value) return

  errors.value = {}
  generalError.value = ''

  // Validate BEFORE setting loading — so early returns don't leave button stuck
  form.email = form.email.trim()

  if (!form.email || !form.password) {
    generalError.value = 'Please enter your email and password.'
    return
  }

  if (!isValidEmail(form.email)) {
    generalError.value = 'Please enter a valid email address.'
    return
  }

  loading.value = true

  try {
    await axios.get('/sanctum/csrf-cookie')
    const { data } = await axios.post('/api/login', form)

    attempts.value = 0

    // Force refresh user from session — don't trust localStorage for role
    await fetchUser(true)

    router.push(data.role === 'admin' ? '/admin' : '/user')
  } catch (error) {
    const response = error?.response
    const status = response?.status
    const responseData = response?.data
    let shouldIncrementAttempts = false

    if (status === 422 && responseData?.errors) {
      errors.value = responseData.errors
      generalError.value = responseData.message || 'Please fix the highlighted fields.'
    } else if (status === 401) {
      shouldIncrementAttempts = true
      generalError.value = responseData?.message || 'Invalid email or password.'
    } else if (status === 429) {
      shouldIncrementAttempts = true
      const retryAfter = parseInt(response?.headers?.['retry-after'], 10)
      if (retryAfter > 0) {
        generalError.value = `Too many login attempts. Please wait ${retryAfter} seconds and try again.`
      } else {
        generalError.value = responseData?.message || 'Too many login attempts. Please try again later.'
      }
    } else {
      generalError.value = responseData?.message || 'Unable to login right now. Please try again later.'
    }

    if (shouldIncrementAttempts) {
      attempts.value++
    }

    if (attempts.value >= maxAttempts) {
      isLocked.value = true
      generalError.value = 'Too many failed attempts. Please wait 30 seconds.'
      timeout(30) // 30 seconds
    }
  } finally {
    loading.value = false
  }
}

// Clear any quiz-related caches on mount to prevent stale data if user tries to login with different account on same device
Object.keys(localStorage).forEach(key => {
  if (key.startsWith('dash-quiz') || key.startsWith('quiz')) {
    localStorage.removeItem(key)
  }
})
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.auth-wrapper {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #ffffff;
}

.auth-header {
  border-bottom: 1px solid #e5e7eb;
  background: #ffffff;
}

.header-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
}

.brand-name {
  font-weight: 800;
  color: #111827;
}

.brand-name span {
  color: #6366f1;
}

.portal {
  font-size: 12px;
  color: #6b7280;
  background: #f3f4f6;
  padding: 4px 10px;
  border-radius: 20px;
}

.container {
  flex: 1;
  max-width: 1100px;
  margin: auto;
  padding: 40px 20px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  align-items: center;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #eef2ff;
  color: #6366f1;
  font-size: 12px;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 20px;
  margin-bottom: 16px;
}

.hero h1 {
  font-size: 2.6rem;
  font-weight: 800;
  color: #111827;
  line-height: 1.2;
}

.hero span {
  color: #6366f1;
}

.hero p {
  margin-top: 14px;
  color: #6b7280;
  font-size: 15px;
  line-height: 1.6;
}

.form-section {
  display: flex;
  align-items: center;
  justify-content: center;
}

.card {
  width: 100%;
  max-width: 380px;
  padding: 32px;
  border-radius: 20px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04), 0 20px 40px rgba(0, 0, 0, 0.06);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.card h2 {
  margin: 0;
  color: #111827;
  font-size: 1.4rem;
}

.subtitle {
  color: #9ca3af;
  font-size: 13px;
  margin-top: -8px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.field label {
  font-size: 12px;
  font-weight: 600;
  color: #374151;
}

.field input {
  width: 100%;
  padding: 11px 14px;
  border-radius: 10px;
  border: 1.5px solid #e5e7eb;
  font-size: 14px;
  color: #111827;
  background: #fafafa;
  transition: all 0.15s;
}

.field input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
  background: #fff;
}

.field input.error {
  border-color: #ef4444;
}

.field small {
  font-size: 11px;
  color: #ef4444;
}

.alert {
  background: #fef2f2;
  color: #b91c1c;
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 13px;
  border-left: 3px solid #ef4444;
}

.btn {
  padding: 12px;
  border-radius: 10px;
  border: none;
  background: #4f46e5;
  color: white;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  transition: all 0.15s;
  letter-spacing: 0.01em;
}

.btn:hover {
  background: #4338ca;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.loader {
  width: 18px;
  height: 18px;
  border: 3px solid rgba(255, 255, 255, 0.4);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.footer-links {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.forgot-link {
  display: flex;
  text-decoration: none;
  color: #6366f1;
  justify-content: center;
  font-size: 13px;
}

.forgot-link a {
  text-decoration: none;
}

.forgot-link a:hover {
  text-decoration: underline;
}

.forgot-link a:visited {
  text-decoration: none;
}

.forgot-link a:visited:hover {
  text-decoration: underline;
}

.divider {
  color: #d1d5db;
  font-size: 12px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
}

.line {
  width: 100%;
  background-color: #e5e7eb;
  height: 1px;
}

.btn-register {
  padding: 11px;
  display: flex;
  justify-content: center;
  align-items: center;
  text-decoration: none;
  color: #fff;
  border-radius: 10px;
  background: #16a34a;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.15s;
}

.btn-register:hover {
  background: #15803d;
}

.auth-footer {
  text-align: center;
  padding: 12px;
  background-color: #6366f1;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.8);
}

@media (max-width: 768px) {
  .container {
    grid-template-columns: 1fr;
    gap: 32px;
  }

  .hero {
    text-align: center;
  }

  .hero h1 {
    font-size: 2rem;
  }
}
</style>