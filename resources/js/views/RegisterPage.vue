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
            <form class="card" @submit.prevent="handleRegister">
                <h2>Create account</h2>
                <p class="subtitle">Join DashQuiz — it's free</p>

                <div class="grid-2">
                    <div class="field">
                        <label for="first_name">First name</label>
                        <input v-model.trim="form.first_name" type="text" id="first_name" name="first_name"
                            placeholder="Juan" :class="{ error: errors.first_name }" autocomplete="off" />
                        <small v-if="errors.first_name">{{ errors.first_name[0] }}</small>
                    </div>
                    <div class="field">
                        <label for="last_name">Last name</label>
                        <input v-model.trim="form.last_name" type="text" id="last_name" name="last_name"
                            placeholder="Dela Cruz" :class="{ error: errors.last_name }" autocomplete="off" />
                        <small v-if="errors.last_name">{{ errors.last_name[0] }}</small>
                    </div>
                </div>

                <div class="field">
                    <label for="email">Email address</label>
                    <input v-model.trim="form.email" type="email" id="email" name="email" placeholder="you@example.com"
                        :class="{ error: errors.email }" autocomplete="off" />
                    <small v-if="errors.email">{{ errors.email[0] }}</small>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input v-model.trim="form.password" type="password" id="password" name="password"
                        placeholder="Min. 6 characters" :class="{ error: errors.password }" autocomplete="off" />
                    <small v-if="errors.password">{{ errors.password[0] }}</small>
                </div>

                <div class="field">
                    <label for="confirm_password">Confirm password</label>
                    <input v-model.trim="form.password_confirmation" type="password" id="confirm_password"
                        name="confirm_password" placeholder="Repeat password"
                        :class="{ error: errors.password_confirmation }" autocomplete="off" />
                    <small v-if="errors.password_confirmation">{{ errors.password_confirmation[0] }}</small>
                </div>

                <div v-if="generalError" class="alert">
                    {{ generalError }}
                </div>

                <button class="btn" type="submit" :disabled="loading">
                    <span v-if="!loading">Create account</span>
                    <span v-else class="loader"></span>
                </button>

                <div class="btn-register">
                    Already have an account? &nbsp;
                    <router-link to="/" class="login-link">Login</router-link>
                </div>
            </form>
        </main>

        <footer class="auth-footer">
            © {{ new Date().getFullYear() }} Dash Quiz • SNSU Capstone Project
        </footer>

    </div>
</template>

<script setup>
import axios from 'axios'
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const form = reactive({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: ''
})

const errors = ref({})
const generalError = ref('')
const loading = ref(false)

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)
}

function validateForm() {
    errors.value = {}
    generalError.value = ''
    let valid = true

    if (!form.first_name) { errors.value.first_name = ['First name is required']; valid = false }
    if (!form.last_name) { errors.value.last_name = ['Last name is required']; valid = false }

    if (!form.email) {
        errors.value.email = ['Email is required']; valid = false
    } else if (!isValidEmail(form.email)) {
        errors.value.email = ['Invalid email format']; valid = false
    }

    if (!form.password) {
        errors.value.password = ['Password is required']; valid = false
    } else if (form.password.length < 6) {
        errors.value.password = ['Password must be at least 6 characters']; valid = false
    }

    if (form.password !== form.password_confirmation) {
        errors.value.password_confirmation = ['Passwords do not match']; valid = false
    }

    return valid
}

const handleRegister = async () => {
    if (loading.value) return
    if (!validateForm()) return

    loading.value = true

    try {
        await axios.get('/sanctum/csrf-cookie')
        await axios.post('/api/register', form)
        router.push('/')
    } catch {
        generalError.value = 'Registration failed. Please try again.'
    } finally {
        loading.value = false
    }
}
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
    --soft-bg: #333333;
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

.portal {
    font-size: 0.72rem;

    color: var(--dark-gray);

    background: var(--page-bg);

    border: 1px solid var(--border);

    padding: 4px 11px;

    border-radius: 20px;

    font-weight: 600;

    letter-spacing: 0.02em;
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
   MAIN LAYOUT (Centered)
   ========================================================= */

.container {
    flex: 1;

    width: 100%;

    padding: 3rem 20px;

    display: flex;
    align-items: center;
    justify-content: center;
}


/* =========================================================
   REGISTER CARD
   ========================================================= */

.card {
    width: 100%;
    max-width: 420px;

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
   GRID (First/Last Name)
   ========================================================= */

.grid-2 {
    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 10px;

    min-width: 0;
}

.grid-2 .field {
    min-width: 0;
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

    padding: 11px 13px;

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
    border-color: red;
    box-shadow:
        0 0 0 2px rgba(187, 49, 49, 0.1);
}

.field small {
    color: red;

    font-size: 0.68rem;

    line-height: 1.4;
}


/* =========================================================
   ALERT
   ========================================================= */

.alert {
    padding: 10px 12px;

    background: rgb(255, 217, 217);

    border: 1px solid red;

    border-radius: 8px;

    color: brown;

    font-size: 0.7rem;

    line-height: 1.45;
}


/* =========================================================
   REGISTER BUTTON
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
   LOGIN LINK
   ========================================================= */

.btn-register {
    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 0.8rem;

    color: var(--dark-gray);
}

.login-link {
    color: var(--black);

    text-decoration: none;

    font-weight: 700;

    transition: color 0.2s ease;
}

.login-link:hover {
    color: var(--dark-gray);

    text-decoration: underline;
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
.login-link:focus-visible {
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

    .grid-2 {
        grid-template-columns: 1fr;
    }

    .field input {
        min-height: 43px;

        font-size: 0.82rem;
    }

    .btn {
        min-height: 42px;
    }

    .auth-footer {
        padding: 11px 12px;

        font-size: 0.62rem;
    }
}
</style>