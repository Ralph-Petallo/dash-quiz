<template>
  <main class="wrapper">
    <div class="card">

      <div class="header">
        <div class="logo">
          <img src="/public/bolt.png" alt="Logo" width="32" height="32">
        </div>
        <h2>Forgot Password</h2>
        <p>Enter your email and we’ll send you a reset link.</p>
      </div>

      <form @submit.prevent="sendResetLink" class="form">
        <div class="field">
          <input type="email" name="email" v-model="email" placeholder="Email address" required autocomplete="off" />
        </div>

        <button class="btn" :disabled="loading">
          {{ loading ? 'Sending...' : 'Send reset link' }}
        </button>
      </form>

      <div class="footer">
        <span>Remembered your password?</span>
        <router-link to="/">Back to login</router-link>
      </div>

    </div>
  </main>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const email = ref('')
const loading = ref(false)

const sendResetLink = async () => {
  loading.value = true

  try {
    await axios.post('/api/forgot-password', {
      email: email.value
    })

    alert('Reset link sent! Please check your email.')

    // clear input after success
    email.value = ''
  } catch (error) {
    console.log(error)
    alert('Failed to send reset link. Please try again.')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  padding: 20px;
}

.card {
  width: 100%;
  max-width: 400px;
  padding: 32px;
  border-radius: 16px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.header {
  text-align: center;
  display: flex;
  flex-direction: column;
}

.logo {
  width: 40px;
  height: 40px;
  margin: 0 auto 10px;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
}

.header h2 {
  margin: 0;
  font-size: 1.5rem;
  color: #111827;
}

.header p {
  font-size: 0.9rem;
  color: #6b7280;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.field input {
  width: 100%;
  padding: 14px;
  border-radius: 10px;
  border: 1px solid #d1d5db;
  font-size: 14px;
  transition: 0.2s;
}

.field input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

.btn {
  padding: 14px;
  border-radius: 10px;
  border: none;
  background: #6366f1;
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: 0.2s;
}

.btn:hover {
  background: #4f46e5;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.footer {
  text-align: center;
  font-size: 13px;
  color: #6b7280;
}

.footer a {
  margin-left: 6px;
  color: #6366f1;
  text-decoration: none;
  font-weight: 500;
}

.footer a:hover {
  text-decoration: underline;
}

@media (max-width: 480px) {
  .card {
    padding: 24px;
  }
}
</style>