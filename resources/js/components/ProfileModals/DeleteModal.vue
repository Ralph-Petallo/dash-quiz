<template>
  <Transition name="modal">
    <div v-if="modelValue" class="overlay" @click.self="close">
      <section class="sheet" role="alertdialog" aria-modal="true" aria-labelledby="delete-title"
        aria-describedby="delete-desc">

        <header class="head">
          <span class="danger-icon" aria-hidden="true">
            <i class="fas fa-triangle-exclamation"></i>
          </span>

          <div class="head-copy">
            <span class="eyebrow">Danger zone</span>
            <h2 id="delete-title">Delete your account</h2>
          </div>

          <button type="button" class="icon-btn" aria-label="Close" :disabled="deleting" @click="close">
            <i class="fas fa-xmark" aria-hidden="true"></i>
          </button>
        </header>

        <div class="body">
          <p id="delete-desc" class="lead">
            This permanently removes your account. It cannot be undone.
          </p>

          <ul class="list">
            <li><i class="fas fa-circle-minus" aria-hidden="true"></i> Your profile and photo</li>
            <li><i class="fas fa-circle-minus" aria-hidden="true"></i> All assessment records and scores</li>
            <li><i class="fas fa-circle-minus" aria-hidden="true"></i> Your access to CSS Prep</li>
          </ul>

          <div class="field">
            <label for="confirm-text">
              Type <strong>DELETE</strong> to confirm
            </label>
            <input id="confirm-text" ref="confirmInput" v-model.trim="confirmText" type="text" autocomplete="off"
              spellcheck="false" placeholder="DELETE" :disabled="deleting" :class="{ ready: canDelete }"
              @keydown.enter.prevent="onEnter" />
          </div>

          <p v-if="errorMessage" class="error" role="alert">
            <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
            {{ errorMessage }}
          </p>
        </div>

        <footer class="foot">
          <button type="button" class="btn btn-outline" :disabled="deleting" @click="close">
            Keep account
          </button>

          <button type="button" class="btn btn-danger" :disabled="!canDelete" @click="handleDelete">
            <i :class="deleting ? 'fas fa-spinner fa-spin' : 'fas fa-trash'" aria-hidden="true"></i>
            {{ deleting ? 'Deleting...' : 'Delete account' }}
          </button>
        </footer>

      </section>
    </div>
  </Transition>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import api from '@/bootstrap'
import { useUser } from '@/composables/useUser'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'deleted', 'notify'])

const { clearUser } = useUser()

const confirmText = ref('')
const deleting = ref(false)
const errorMessage = ref('')
const confirmInput = ref(null)

const canDelete = computed(() => confirmText.value === 'DELETE' && !deleting.value)

function close() {
  if (!deleting.value) emit('update:modelValue', false)
}

function onEnter() {
  if (canDelete.value) handleDelete()
}

async function handleDelete() {
  if (!canDelete.value) return

  deleting.value = true
  errorMessage.value = ''

  try {
    await api.delete('/api/profile/delete')

    clearUser()
    emit('deleted')
    emit('update:modelValue', false)
  } catch (error) {
    const status = error.response?.status

    errorMessage.value =
      status === 401 || status === 419
        ? 'Your session has expired. Please sign in again, then retry.'
        : error.response?.data?.message || "We couldn't delete your account. Please try again."

    emit('notify', { message: errorMessage.value, status: 'error' })
  } finally {
    deleting.value = false
  }
}

function onKeydown(event) {
  if (event.key === 'Escape' && props.modelValue) close()
}

watch(
  () => props.modelValue,
  async (open) => {
    document.body.style.overflow = open ? 'hidden' : ''

    if (open) {
      confirmText.value = ''
      errorMessage.value = ''
      await nextTick()
      confirmInput.value?.focus()
    }
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
  --danger-dark: #91170f;
  --danger-soft: #fdf1f0;

  position: fixed;
  inset: 0;
  z-index: 1005;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: rgba(0, 0, 0, 0.42);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
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
  width: min(440px, 100%);
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
  gap: 12px;
  padding: 16px 18px;
  border-bottom: 1px solid var(--light);
}

.danger-icon {
  width: 40px;
  height: 40px;
  flex: 0 0 40px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--danger-soft);
  color: var(--danger);
  font-size: 16px;
}

.head-copy {
  min-width: 0;
  flex: 1;
}

.eyebrow {
  display: block;
  margin-bottom: 3px;
  color: var(--danger);
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

.body {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px 18px;
}

.lead {
  color: var(--dim);
  font-size: 12px;
  line-height: 1.6;
}

.list {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin: 0;
  padding: 10px 12px;
  border: 1px solid var(--light);
  border-radius: 10px;
  background: #fafafa;
  list-style: none;
}

.list li {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--black);
  font-size: 12px;
}

.list i {
  color: var(--danger);
  font-size: 11px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field label {
  color: var(--dim);
  font-size: 11px;
  font-weight: 700;
}

.field label strong {
  color: var(--black);
  letter-spacing: 0.04em;
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
  letter-spacing: 0.06em;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.field input::placeholder {
  color: var(--gray);
  letter-spacing: 0.06em;
}

.field input:focus {
  outline: none;
  border-color: var(--danger);
  background: var(--white);
  box-shadow: 0 0 0 3px rgba(180, 35, 24, 0.12);
}

.field input.ready {
  border-color: var(--danger);
}

.error {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 9px 11px;
  border: 1px solid var(--danger);
  border-radius: 9px;
  background: var(--danger-soft);
  color: var(--danger);
  font-size: 11px;
  line-height: 1.5;
}

.foot {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 14px 18px;
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

.btn-danger {
  border: 1px solid var(--danger);
  background: var(--danger);
  color: var(--white);
}

.btn-danger:hover:not(:disabled) {
  background: var(--danger-dark);
  border-color: var(--danger-dark);
}

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

@media (max-width: 560px) {
  .overlay {
    align-items: flex-end;
    padding: 0;
  }

  .sheet {
    width: 100%;
    border-radius: 16px 16px 0 0;
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