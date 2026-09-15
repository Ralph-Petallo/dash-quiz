<template>
  <div class="auth-wrapper">

    <header class="auth-header">
      <div class="header-inner">
        <div class="brand">
          <img src="/public/lightning.png" alt="Logo" width="32" height="32">
          <span class="brand-name">CSS<span> Prep</span></span>
        </div>
      </div>
    </header>

    <main class="container">

      <section class="hero">
        <h1>
          Learning is <span>better</span><br />
          when we do it <span>together</span>
        </h1>
        <p>Practice, learn, and improve your skills with CSS Prep.</p>
      </section>

      <section class="form-section">
        <form class="card" @submit.prevent="submitLogin">
          <h2>Welcome{{ visited ? ' Back!' : '!' }}</h2>
          <p class="subtitle">Sign in to your account</p>

          <div class="field">
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" v-model.trim="form.email" placeholder="yourname@email.com"
              :class="{ error: errors.email }" autocomplete="off" />
            <small v-if="errors.email">{{ errors.email[0] }}</small>
          </div>

          <div class="field">
            <label for="password">Password</label>

            <div class="eyeButton" @click="togglePassword">
              <i :class="showPassword ? 'fas fa-eye' : 'fas fa-eye-slash'"></i>
            </div>

            <input id="password" :type="showPassword ? 'text' : 'password'" v-model="form.password"
              placeholder="••••••" />
          </div>

          <div v-if="generalError" class="alert">
            {{ generalError }}
          </div>

          <button class="btn" type="submit" :disabled="loading || isLocked">
            <span v-if="!loading"><i class="fa-solid fa-right-to-bracket"></i>&nbsp;Login</span>
            <span v-else class="loader"></span>
          </button>

          <div class="footer-links">
            <div class="forgot-link">
              <router-link to="/forgot">Forgot password?</router-link>
            </div>
            <div class="divider">
              <div class="line"></div>or<div class="line"></div>
            </div>
            <router-link to="/register" class="btn-register"><i class="fas fa-user-plus"></i>
              &nbsp;Sign Up</router-link>
            <a class="btn-google" @click="authGoogle">
              <img class="google-logo" src="/public/google logo.png" alt="Google Logo" width="20" height="20">
              Sign up with Google
            </a>
          </div>
        </form>
      </section>

    </main>

    <footer class="auth-footer">
      © {{ new Date().getFullYear() }} CSS Prep • SNSU Capstone Project
    </footer>

  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useHandleLogin } from '@/composables/useHandleLogin.js'

const visited = ref(false)

const form = reactive({
  email: '',
  password: ''
})

const {
  loading,
  errors,
  generalError,
  isLocked,
  showPassword,
  handleLogin,
  togglePassword,
  authGoogle
} = useHandleLogin()

const submitLogin = async () => {
  await handleLogin(form.email, form.password)
}

function newVisitorCheck() {
  if (localStorage.getItem('visited')) {
    visited.value = true
  } else {
    localStorage.setItem('visited', 'true')
  }
}



onMounted(() => {
  newVisitorCheck()

  // Clear quiz-related caches when opening login page
  Object.keys(localStorage).forEach(key => {
    if (key.startsWith('dash-quiz') || key.startsWith('quiz')) {
      localStorage.removeItem(key)
    }
  })
})
</script>

<style scoped>
/* =========================================================
   DASHQUIZ — FROSTED NOIR
   #FFFFFF  White
   #000000  Black
   #A9A9A9  Gray
   #D3D3D3  Light Gray
   #696969  Dim Gray
   ========================================================= */

* {
  box-sizing: border-box;
}

.auth-wrapper {
  --white: #ffffff;
  --black: #000000;
  --gray: #a9a9a9;
  --light-gray: #d3d3d3;
  --dark-gray: #696969;

  --page-bg: #f7f7f7;
  --soft-bg: #eeeeee;
  --border: #d3d3d3;

  --text-primary: #000000;
  --text-secondary: #696969;
  --text-muted: #a9a9a9;

  min-height: 100vh;

  display: flex;
  flex-direction: column;

  background: var(--page-bg);

  color: var(--text-primary);

  font-family:
    "Inter",
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;
}

:root.dark-mode .auth-wrapper {
  --white: #1a1a1a;
  --black: #e5e5e5;
  --gray: #555555;
  --light-gray: #2a2a2a;
  --dark-gray: #888888;

  --page-bg: #0f0f0f;
  --soft-bg: #2a2a2a;
  --border: #2a2a2a;

  --text-primary: #e5e5e5;
  --text-secondary: #888888;
  --text-muted: #666666;
}


/* =========================================================
   HEADER
   ========================================================= */

.auth-header {
  width: 100%;

  background: rgba(255, 255, 255, 0.94);

  border-bottom: 1px solid var(--border);

  position: sticky;
  top: 0;
  z-index: 100;

  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

:root.dark-mode .auth-header {
  background: rgba(26, 26, 26, 0.94);
}

.header-inner {
  width: 100%;
  max-width: 1100px;

  margin: 0 auto;

  padding: 15px 20px;

  display: flex;
  align-items: center;
  justify-content: space-between;
}


/* =========================================================
   BRAND
   ========================================================= */

.brand {
  display: flex;
  align-items: center;

  gap: 10px;
}

.brand img {
  width: 32px;
  height: 32px;

  object-fit: contain;
}

.brand-name {
  color: var(--black);

  font-size: 1rem;
  font-weight: 800;

  letter-spacing: 0.04em;
}

.brand-name span {
  color: var(--dark-gray);
}


/* =========================================================
   MAIN LAYOUT
   ========================================================= */

.container {
  flex: 1;

  width: 100%;
  max-width: 1100px;

  margin: 0 auto;

  padding: 3rem 20px;

  display: grid;

  grid-template-columns: minmax(0, 1fr) minmax(340px, 380px);

  gap: clamp(40px, 7vw, 90px);

  align-items: center;
}


/* =========================================================
   HERO
   ========================================================= */

.hero {
  max-width: 540px;
}

.hero h1 {
  margin: 0;

  color: var(--black);

  font-size: clamp(2.1rem, 4vw, 3.2rem);

  font-weight: 800;

  line-height: 1.12;

  letter-spacing: -0.045em;
}

.hero h1 span {
  color: var(--dark-gray);
}

.hero p {
  max-width: 460px;

  margin: 1.1rem 0 0;

  color: var(--dark-gray);

  font-size: 0.95rem;

  line-height: 1.7;
}


/* =========================================================
   FORM SECTION
   ========================================================= */

.form-section {
  width: 100%;

  display: flex;
  align-items: center;
  justify-content: center;
}


/* =========================================================
   LOGIN CARD
   ========================================================= */

.card {
  width: 100%;
  max-width: 380px;

  padding: 2rem;

  background: var(--white);

  border: 1px solid var(--border);

  border-radius: 16px;

  display: flex;
  flex-direction: column;

  gap: 15px;

  box-shadow:
    0 8px 30px rgba(0, 0, 0, 0.05);

  animation: cardEnter 0.35s ease-out both;
}

.card h2 {
  margin: 0;

  color: var(--black);

  font-size: 1.35rem;

  font-weight: 800;

  letter-spacing: -0.025em;
}

.subtitle {
  margin: -8px 0 3px;

  color: var(--dark-gray);

  font-size: 0.8rem;
}


/* =========================================================
   FORM FIELDS
   ========================================================= */

.field {
  position: relative;

  display: flex;
  flex-direction: column;

  gap: 6px;
}

.field label {
  color: var(--dark-gray);

  font-size: 0.72rem;

  font-weight: 700;

  letter-spacing: 0.01em;
}

.field input {
  width: 100%;

  min-height: 44px;

  padding: 11px 42px 11px 13px;

  background: var(--page-bg);

  border: 1px solid var(--border);

  border-radius: 9px;

  color: var(--black);

  font-family: inherit;

  font-size: 0.84rem;

  transition:
    background 0.2s ease,
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.field input::placeholder {
  color: var(--gray);
}

.field input:hover {
  border-color: var(--gray);
}

.field input:focus {
  outline: none;

  background: var(--white);

  border-color: var(--dark-gray);

  box-shadow:
    0 0 0 3px rgba(105, 105, 105, 0.12);
}

.field input.error {
  border-color: #696969;
  box-shadow:
    0 0 0 3px rgba(105, 105, 105, 0.1);
}

.field small {
  color: #696969;

  font-size: 0.68rem;

  line-height: 1.4;
}


/* =========================================================
   PASSWORD TOGGLE
   ========================================================= */

.field .eyeButton {
  position: absolute;

  right: 12px;
  bottom: 11px;

  width: 24px;
  height: 24px;

  display: flex;
  align-items: center;
  justify-content: center;

  color: var(--gray);

  cursor: pointer;

  z-index: 2;

  transition:
    color 0.2s ease,
    transform 0.2s ease;
}

.field .eyeButton:hover {
  color: var(--black);

  transform: scale(1.05);
}


/* =========================================================
   ALERT
   ========================================================= */

.alert {
  padding: 10px 12px;

  background: var(--soft-bg);

  border: 1px solid var(--border);

  border-radius: 8px;

  color: var(--dark-gray);

  font-size: 0.7rem;

  line-height: 1.45;
}


/* =========================================================
   LOGIN BUTTON
   ========================================================= */

.btn {
  width: 100%;

  min-height: 44px;

  padding: 11px 14px;

  border: 1px solid var(--black);

  border-radius: 9px;

  background: var(--black);

  color: var(--white);

  font-family: inherit;

  font-size: 0.84rem;

  font-weight: 700;

  cursor: pointer;

  display: flex;
  align-items: center;
  justify-content: center;

  transition:
    background 0.2s ease,
    transform 0.15s ease,
    box-shadow 0.2s ease;
}

:root.dark-mode .btn {
  background: #e5e5e5;
  color: #000000;
  border-color: #e5e5e5;
}

.btn:hover:not(:disabled) {
  background: var(--dark-gray);

  box-shadow:
    0 5px 15px rgba(0, 0, 0, 0.12);
}

:root.dark-mode .btn:hover:not(:disabled) {
  background: #888888;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.btn:active:not(:disabled) {
  transform: scale(0.98);
}

.btn:disabled {
  opacity: 0.55;

  cursor: not-allowed;
}


/* =========================================================
   LOADER
   ========================================================= */

.loader {
  width: 17px;
  height: 17px;

  border: 2px solid rgba(255, 255, 255, 0.35);

  border-top-color: var(--white);

  border-radius: 50%;

  animation: spin 0.75s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}


/* =========================================================
   FOOTER LINKS
   ========================================================= */

.footer-links {
  display: flex;
  flex-direction: column;

  gap: 11px;
}


/* =========================================================
   FORGOT PASSWORD
   ========================================================= */

.forgot-link {
  display: flex;

  justify-content: center;

  font-size: 0.76rem;
}

.forgot-link a {
  color: var(--dark-gray);

  text-decoration: none;

  font-weight: 600;

  transition: color 0.2s ease;
}

.forgot-link a:hover {
  color: var(--black);

  text-decoration: underline;
}


/* =========================================================
   DIVIDER
   ========================================================= */

.divider {
  display: flex;

  align-items: center;

  justify-content: center;

  gap: 10px;

  color: var(--gray);

  font-size: 0.68rem;

  text-transform: uppercase;

  letter-spacing: 0.05em;
}

.line {
  flex: 1;

  height: 1px;

  background: var(--border);
}


/* =========================================================
   REGISTER
   ========================================================= */

.btn-register {
  width: 100%;

  min-height: 43px;

  padding: 10px 13px;

  display: flex;

  align-items: center;
  justify-content: center;

  gap: 5px;

  background: var(--white);

  color: var(--black);

  border: 1px solid var(--dark-gray);

  border-radius: 9px;

  text-decoration: none;

  font-size: 0.82rem;

  font-weight: 700;

  cursor: pointer;

  transition:
    background 0.2s ease,
    color 0.2s ease,
    transform 0.15s ease;
}

.btn-register:hover {
  background: var(--black);

  color: var(--white);

  transform: translateY(-1px);
}


/* =========================================================
   GOOGLE
   ========================================================= */

.btn-google {
  width: 100%;

  min-height: 43px;

  padding: 10px 13px;

  display: flex;

  align-items: center;
  justify-content: center;

  gap: 7px;

  background: var(--white);

  color: var(--black);

  border: 1px solid var(--border);

  border-radius: 9px;

  text-decoration: none;

  font-size: 0.82rem;

  font-weight: 600;

  cursor: pointer;

  transition:
    background 0.2s ease,
    border-color 0.2s ease,
    transform 0.15s ease;
}

.btn-google:hover {
  background: var(--page-bg);

  border-color: var(--gray);

  transform: translateY(-1px);
}

.google-logo {
  width: 18px;
  height: 18px;

  object-fit: contain;

  margin: 0;
}


/* =========================================================
   FOOTER
   ========================================================= */

.auth-footer {
  width: 100%;

  padding: 13px 20px;

  background: var(--black);

  color: var(--gray);

  text-align: center;

  font-size: 0.68rem;

  line-height: 1.5;
}


/* =========================================================
   FOCUS
   ========================================================= */

.btn:focus-visible,
.btn-register:focus-visible,
.btn-google:focus-visible,
.forgot-link a:focus-visible,
.eyeButton:focus-visible {
  outline: 2px solid var(--black);

  outline-offset: 3px;
}


/* =========================================================
   CARD ANIMATION
   ========================================================= */

@keyframes cardEnter {
  from {
    opacity: 0;

    transform: translateY(12px);
  }

  to {
    opacity: 1;

    transform: translateY(0);
  }
}


/* =========================================================
   TABLET
   ========================================================= */

@media (max-width: 900px) {
  .container {
    grid-template-columns: 1fr 360px;

    gap: 35px;

    padding: 2.5rem 20px;
  }

  .hero h1 {
    font-size: 2.3rem;
  }
}


/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 768px) {
  .header-inner {
    padding: 13px 16px;
  }

  .container {
    grid-template-columns: 1fr;

    gap: 2rem;

    padding: 2rem 16px 2.5rem;
  }

  .hero {
    width: 100%;

    max-width: 600px;

    margin: 0 auto;

    text-align: center;
  }

  .hero h1 {
    font-size: clamp(2rem, 8vw, 2.5rem);
  }

  .hero p {
    margin-left: auto;
    margin-right: auto;

    font-size: 0.88rem;
  }

  .form-section {
    width: 100%;
  }

  .card {
    max-width: 430px;
  }
}


/* =========================================================
   SMALL MOBILE
   ========================================================= */

@media (max-width: 480px) {
  .header-inner {
    padding: 12px 14px;
  }

  .brand-name {
    font-size: 0.9rem;
  }

  .brand img {
    width: 29px;
    height: 29px;
  }

  .container {
    padding: 1.5rem 12px 2rem;

    gap: 1.5rem;
  }

  .hero h1 {
    font-size: 1.8rem;

    letter-spacing: -0.035em;
  }

  .hero p {
    margin-top: 0.8rem;

    font-size: 0.8rem;
  }

  .card {
    padding: 1.5rem;

    border-radius: 14px;

    gap: 14px;
  }

  .card h2 {
    font-size: 1.2rem;
  }

  .subtitle {
    font-size: 0.76rem;
  }

  .field input {
    min-height: 43px;

    font-size: 0.82rem;
  }

  .btn,
  .btn-register,
  .btn-google {
    min-height: 42px;
  }

  .auth-footer {
    padding: 11px 12px;

    font-size: 0.62rem;
  }
}


/* =========================================================
   VERY SMALL PHONES
   ========================================================= */

@media (max-width: 340px) {
  .container {
    padding-left: 8px;
    padding-right: 8px;
  }

  .card {
    padding: 1.25rem;
  }

  .hero h1 {
    font-size: 1.6rem;
  }
}
</style>