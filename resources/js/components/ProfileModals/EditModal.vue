<template>
  <Transition name="modal">
    <div v-if="modelValue" class="overlay" @click.self="close">
      <section class="sheet" role="dialog" aria-modal="true" aria-labelledby="edit-title">

        <header class="head">
          <div>
            <span class="eyebrow">Account</span>
            <h2 id="edit-title">Edit profile</h2>
          </div>
          <button type="button" class="icon-btn" aria-label="Close" :disabled="saving" @click="close">
            <i class="fas fa-xmark" aria-hidden="true"></i>
          </button>
        </header>

        <form class="body" novalidate @submit.prevent="save">

          <!-- NAME -->
          <div class="row">
            <div class="field">
              <label for="first_name">First name</label>
              <input id="first_name" v-model.trim="form.first_name" type="text" autocomplete="given-name"
                :class="{ invalid: errors.first_name }" :disabled="saving" />
              <small v-if="errors.first_name">{{ errors.first_name }}</small>
            </div>

            <div class="field">
              <label for="last_name">Last name</label>
              <input id="last_name" v-model.trim="form.last_name" type="text" autocomplete="family-name"
                :class="{ invalid: errors.last_name }" :disabled="saving" />
              <small v-if="errors.last_name">{{ errors.last_name }}</small>
            </div>
          </div>

          <!-- EMAIL -->
          <div class="field">
            <label for="email">Email address</label>
            <input id="email" v-model.trim="form.email" type="email" autocomplete="email"
              :class="{ invalid: errors.email }" :disabled="saving" />
            <small v-if="errors.email">{{ errors.email }}</small>
          </div>

          <!-- PASSWORD (optional) -->
          <div class="section-head">
            <div>
              <span class="eyebrow">Security</span>
              <strong>Change password</strong>
            </div>
            <span class="optional">Optional</span>
          </div>

          <div class="field">
            <label for="current_password">Current password</label>
            <div class="input-wrap">
              <input id="current_password" v-model="form.current_password" :type="showPasswords ? 'text' : 'password'"
                autocomplete="current-password" :class="{ invalid: errors.current_password }" :disabled="saving" />
            </div>
            <small v-if="errors.current_password">{{ errors.current_password }}</small>
          </div>

          <div class="row">
            <div class="field">
              <label for="password">New password</label>
              <div class="input-wrap">
                <input id="password" v-model="form.password" :type="showPasswords ? 'text' : 'password'"
                  autocomplete="new-password" :class="{ invalid: errors.password }" :disabled="saving" />
                <button type="button" class="toggle" :aria-label="showPasswords ? 'Hide passwords' : 'Show passwords'"
                  :aria-pressed="showPasswords" :disabled="saving" @click="showPasswords = !showPasswords">
                  <i :class="showPasswords ? 'fas fa-eye' : 'fas fa-eye-slash'" aria-hidden="true"></i>
                </button>
              </div>
              <small v-if="errors.password">{{ errors.password }}</small>
            </div>

            <div class="field">
              <label for="password_confirmation">Confirm new password</label>
              <input id="password_confirmation" v-model="form.password_confirmation"
                :type="showPasswords ? 'text' : 'password'" autocomplete="new-password"
                :class="{ invalid: errors.password_confirmation }" :disabled="saving" />
              <small v-if="errors.password_confirmation">{{ errors.password_confirmation }}</small>
            </div>
          </div>

          <p class="hint">Use at least 8 characters. Leave these blank to keep your current password.</p>

          <!-- ACTIONS -->
          <footer class="foot">
            <button type="button" class="btn btn-outline" :disabled="saving" @click="close">
              Cancel
            </button>
            <button type="submit" class="btn btn-solid" :disabled="saving">
              <i v-if="saving" class="fas fa-spinner fa-spin" aria-hidden="true"></i>
              {{ saving ? 'Saving...' : 'Save changes' }}
            </button>
          </footer>
        </form>

      </section>
    </div>
  </Transition>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useUser } from '@/composables/useUser'
import { useUserRequests } from '@/composables/useUserRequests'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'notify'])

const { user } = useUser()
const { updateProfile } = useUserRequests()

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  current_password: '',
  password: '',
  password_confirmation: '',
})

const errors = reactive({})
const saving = ref(false)
const showPasswords = ref(false)

const wantsPasswordChange = computed(() =>
  Boolean(form.password || form.password_confirmation)
)

function clearErrors() {
  Object.keys(errors).forEach((key) => delete errors[key])
}

function resetForm() {
  form.first_name = user.value?.first_name ?? ''
  form.last_name = user.value?.last_name ?? ''
  form.email = user.value?.email ?? ''
  form.current_password = ''
  form.password = ''
  form.password_confirmation = ''
  showPasswords.value = false
  clearErrors()
}

function close() {
  if (!saving.value) emit('update:modelValue', false)
}

// Client-side checks: the server still validates everything.
function validate() {
  clearErrors()

  if (!form.first_name) errors.first_name = 'First name is required.'
  if (!form.last_name) errors.last_name = 'Last name is required.'

  if (!form.email) {
    errors.email = 'Email is required.'
  } else if (!/^\S+@\S+\.\S+$/.test(form.email)) {
    errors.email = 'Enter a valid email address.'
  }

  if (wantsPasswordChange.value) {
    if (!form.current_password) {
      errors.current_password = 'Enter your current password to change it.'
    }
    if (form.password.length < 8) {
      errors.password = 'Use at least 8 characters.'
    }
    if (form.password !== form.password_confirmation) {
      errors.password_confirmation = 'Passwords do not match.'
    }
  }

  return Object.keys(errors).length === 0
}

async function save() {
  if (saving.value || !validate()) return

  saving.value = true

  const payload = {
    first_name: form.first_name,
    last_name: form.last_name,
    email: form.email,
  }

  if (wantsPasswordChange.value) {
    payload.current_password = form.current_password
    payload.password = form.password
    payload.password_confirmation = form.password_confirmation
  }

  try {
    await updateProfile(payload)

    emit('notify', { message: 'Profile updated.', status: 'success' })
    emit('update:modelValue', false)
  } catch (error) {
    const response = error.response

    // Laravel validation errors arrive as { field: ["message"] }.
    if (response?.status === 422 && response.data?.errors) {
      Object.entries(response.data.errors).forEach(([field, messages]) => {
        errors[field] = Array.isArray(messages) ? messages[0] : messages
      })
    } else {
      emit('notify', {
        message: response?.data?.message || 'Could not save your changes.',
        status: 'error',
      })
    }
  } finally {
    saving.value = false
  }
}

function onKeydown(event) {
  if (event.key === 'Escape' && props.modelValue) close()
}

watch(
  () => props.modelValue,
  (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
    if (open) resetForm()
  }
)

onMounted(() => window.addEventListener('keydown', onKeydown))

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>

<style scoped>
.overlay {
  --black: #000000;
  --white: #ffffff;
  --gray: #a9a9a9;
  --light: #d3d3d3;
  --dim: #696969;
  --danger: #b42318;

  position: fixed;
  inset: 0;
  z-index: 1004;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: rgba(0, 0, 0, 0.5);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  color: var(--black);
  box-sizing: border-box;
}

.overlay *,
.overlay *::before,
.overlay *::after {
  box-sizing: border-box;
}

.overlay button {
  font: inherit;
  cursor: pointer;
}

.overlay button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.overlay :focus-visible {
  outline: 2px solid var(--black);
  outline-offset: 2px;
}

.sheet {
  width: min(500px, 100%);
  max-height: min(92vh, 720px);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid var(--light);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.97);
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.22);
}

.head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 18px;
  border-bottom: 1px solid var(--light);
}

.eyebrow {
  display: block;
  margin-bottom: 3px;
  color: var(--dim);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.head h2 {
  margin: 0;
  font-size: 17px;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.icon-btn {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  display: grid;
  place-items: center;
  border: 1px solid var(--light);
  border-radius: 9px;
  background: var(--white);
  color: var(--dim);
}

.icon-btn:hover:not(:disabled) {
  border-color: var(--black);
  color: var(--black);
}

/* BODY */

.body {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px 18px;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.field {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.field label {
  color: var(--dim);
  font-size: 11px;
  font-weight: 700;
}

.field input {
  width: 100%;
  min-height: 40px;
  padding: 9px 11px;
  border: 1px solid var(--light);
  border-radius: 9px;
  background: #fafafa;
  color: var(--black);
  font: inherit;
  font-size: 16px;
  transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}

.field input::placeholder {
  color: var(--gray);
}

.field input:hover:not(:disabled) {
  border-color: var(--gray);
}

.field input:focus {
  outline: none;
  border-color: var(--dim);
  background: var(--white);
  box-shadow: 0 0 0 3px rgba(105, 105, 105, 0.14);
}

.field input.invalid {
  border-color: var(--danger);
}

.field small {
  color: var(--danger);
  font-size: 11px;
}

.input-wrap {
  position: relative;
}

.input-wrap input {
  padding-right: 44px;
}

.toggle {
  position: absolute;
  top: 50%;
  right: 4px;
  transform: translateY(-50%);
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  padding: 0;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: var(--dim);
}

.toggle:hover:not(:disabled) {
  background: #f1f1f1;
  color: var(--black);
}

.section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-top: 6px;
  padding-top: 12px;
  border-top: 1px solid var(--light);
}

.section-head strong {
  font-size: 13px;
  font-weight: 700;
}

.optional {
  padding: 3px 8px;
  border: 1px solid var(--light);
  border-radius: 999px;
  color: var(--dim);
  font-size: 10px;
  font-weight: 700;
}

.hint {
  color: var(--dim);
  font-size: 11px;
  line-height: 1.5;
}

/* FOOTER */

.foot {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 4px;
  padding-top: 14px;
  border-top: 1px solid var(--light);
}

.btn {
  min-height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 16px;
  border-radius: 9px;
  font-size: 12px;
  font-weight: 700;
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.btn-outline {
  border: 1px solid var(--light);
  background: var(--white);
  color: var(--dim);
}

.btn-outline:hover:not(:disabled) {
  border-color: var(--black);
  color: var(--black);
}

.btn-solid {
  border: 1px solid var(--black);
  background: var(--black);
  color: var(--white);
}

.btn-solid:hover:not(:disabled) {
  background: var(--dim);
  border-color: var(--dim);
}

/* TRANSITION */

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.16s ease;
}

.modal-enter-active .sheet,
.modal-leave-active .sheet {
  transition: transform 0.16s ease, opacity 0.16s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .sheet,
.modal-leave-to .sheet {
  opacity: 0;
  transform: translateY(10px) scale(0.98);
}

/* RESPONSIVE */

@media (max-width: 560px) {
  .overlay {
    align-items: flex-end;
    padding: 0;
  }

  .sheet {
    width: 95%;
    max-height: 94vh;
    border-radius: 16px 16px 0 0;
  }

  .row {
    grid-template-columns: minmax(0, 1fr);
  }

  .foot {
    flex-direction: column-reverse;
  }

  .foot .btn {
    width: 100%;
  }
}

@media (prefers-reduced-motion: reduce) {

  .overlay *,
  .overlay *::before,
  .overlay *::after {
    transition: none !important;
    animation: none !important;
  }
}
</style>