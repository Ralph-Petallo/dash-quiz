<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="modelValue" class="backdrop" @click.self="close">
                <section class="dialog" role="dialog" aria-modal="true" aria-labelledby="coc-edit-title">

                    <header class="dialog-head">
                        <div>
                            <span class="eyebrow">Competency</span>
                            <h2 id="coc-edit-title">Edit COC</h2>
                        </div>

                        <button type="button" class="icon-btn" aria-label="Close" :disabled="saving" @click="close">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </header>

                    <div v-if="loading" class="state">
                        <span class="spinner" aria-hidden="true"></span>
                        <span>Loading competency…</span>
                    </div>

                    <div v-else-if="loadError" class="state">
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                        <span>{{ loadError }}</span>
                        <button type="button" class="btn btn-outline" @click="load">Try again</button>
                    </div>

                    <form v-else class="body" novalidate @submit.prevent="save">

                        <div v-if="errors.general" class="alert" role="alert">{{ errors.general }}</div>

                        <div class="field">
                            <label for="coc-title">Title</label>
                            <input id="coc-title" v-model.trim="form.title" type="text"
                                :class="{ invalid: errors.title }" :disabled="saving" />
                            <small v-if="errors.title">{{ errors.title }}</small>
                        </div>

                        <div class="field">
                            <label for="coc-description">Description</label>
                            <textarea id="coc-description" v-model.trim="form.description" rows="3"
                                :class="{ invalid: errors.description }" :disabled="saving"></textarea>
                            <small v-if="errors.description">{{ errors.description }}</small>
                        </div>

                        <div class="row">
                            <div class="field">
                                <label for="coc-number">COC number</label>
                                <input id="coc-number" v-model.number="form.coc_number" type="number" min="1" max="99"
                                    :class="{ invalid: errors.coc_number }" :disabled="saving" />
                                <small v-if="errors.coc_number">{{ errors.coc_number }}</small>
                            </div>

                            <div class="field">
                                <label for="coc-difficulty">Difficulty</label>
                                <select id="coc-difficulty" v-model="form.difficulty"
                                    :class="{ invalid: errors.difficulty }" :disabled="saving">
                                    <option value="easy">Easy</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                                <small v-if="errors.difficulty">{{ errors.difficulty }}</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="field">
                                <label for="coc-category">Category</label>
                                <input id="coc-category" v-model.trim="form.category" type="text"
                                    :class="{ invalid: errors.category }" :disabled="saving" />
                                <small v-if="errors.category">{{ errors.category }}</small>
                            </div>

                            <div class="field">
                                <label for="coc-type">Quiz type</label>
                                <input id="coc-type" v-model.trim="form.quiz_type" type="text"
                                    :class="{ invalid: errors.quiz_type }" :disabled="saving" />
                                <small v-if="errors.quiz_type">{{ errors.quiz_type }}</small>
                            </div>
                        </div>

                        <footer class="foot">
                            <button type="button" class="btn btn-outline" :disabled="saving"
                                @click="close">Cancel</button>
                            <button type="submit" class="btn btn-solid" :disabled="saving">
                                <i v-if="saving" class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                                {{ saving ? 'Saving…' : 'Save changes' }}
                            </button>
                        </footer>
                    </form>

                </section>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { reactive, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import api from '@/bootstrap'

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    cocId: { type: [Number, String], default: null },
})

const emit = defineEmits(['update:modelValue', 'saved'])

const form = reactive({
    title: '',
    description: '',
    quiz_type: 'coc',
    coc_number: '',
    difficulty: 'easy',
    category: '',
})

const errors = reactive({})
const loading = ref(false)
const saving = ref(false)
const loadError = ref('')

const clearErrors = () => {
    Object.keys(errors).forEach((key) => delete errors[key])
}

const fillForm = (coc) => {
    form.title = coc.title ?? ''
    form.description = coc.description ?? ''
    form.quiz_type = coc.quiz_type ?? 'coc'
    form.coc_number = coc.coc_number ?? ''
    form.difficulty = coc.difficulty ?? 'easy'
    form.category = coc.category ?? ''
}

const close = () => {
    if (!saving.value) emit('update:modelValue', false)
}

const load = async () => {
    if (!props.cocId) return

    loading.value = true
    loadError.value = ''
    clearErrors()

    try {
        const { data } = await api.get(`/api/admin/cocs/${props.cocId}`)
        fillForm(data.data)
    } catch (error) {
        loadError.value = error.response?.data?.message || 'Could not load this competency.'
    } finally {
        loading.value = false
    }
}

// Client-side checks. The server validates everything again.
const validate = () => {
    clearErrors()

    if (!form.title) errors.title = 'Title is required.'

    const number = Number(form.coc_number)
    if (form.coc_number === '' || !Number.isInteger(number) || number < 1 || number > 99) {
        errors.coc_number = 'Enter a whole number from 1 to 99.'
    }

    if (!form.category) errors.category = 'Category is required.'
    if (!form.quiz_type) errors.quiz_type = 'Quiz type is required.'

    return Object.keys(errors).length === 0
}

const save = async () => {
    if (saving.value || !validate()) return

    saving.value = true
    clearErrors()

    try {
        const { data } = await api.put(`/api/admin/cocs/${props.cocId}`, {
            title: form.title,
            description: form.description || null,
            quiz_type: form.quiz_type,
            coc_number: Number(form.coc_number),
            difficulty: form.difficulty,
            category: form.category,
        })

        emit('saved', data.data)
        emit('update:modelValue', false)
    } catch (error) {
        const response = error.response

        if (response?.status === 422 && response.data?.errors) {
            Object.entries(response.data.errors).forEach(([field, messages]) => {
                errors[field] = Array.isArray(messages) ? messages[0] : messages
            })
        } else {
            errors.general = response?.data?.message || 'Could not save the changes. Please try again.'
        }
    } finally {
        saving.value = false
    }
}

const onKeydown = (event) => {
    if (event.key === 'Escape' && props.modelValue) close()
}

watch(
    () => props.modelValue,
    (open) => {
        document.body.style.overflow = open ? 'hidden' : ''
        if (open) load()
    }
)

onMounted(() => window.addEventListener('keydown', onKeydown))

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
})
</script>

<style scoped>
.backdrop {
    --ink: #182230;
    --muted: #778396;
    --line: #e3e8ef;
    --accent: #3b5ccc;
    --accent-dark: #2f4fb2;
    --danger: #bd4b55;

    position: fixed;
    inset: 0;
    z-index: 60;
    display: grid;
    place-items: center;
    padding: 16px;
    background: rgb(0,0,0, 0.5);
    font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    color: var(--ink);
}

.backdrop *,
.backdrop *::before,
.backdrop *::after {
    box-sizing: border-box;
}

.backdrop button,
.backdrop input,
.backdrop select,
.backdrop textarea {
    font: inherit;
}

.dialog {
    width: min(560px, 100%);
    max-height: min(90vh, 680px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 24px 70px rgb(13 24 42 / 24%);
}

.dialog-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--line);
}

.eyebrow {
    display: block;
    margin-bottom: 4px;
    color: var(--muted);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.dialog-head h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 750;
    letter-spacing: -0.03em;
}

.icon-btn {
    width: 32px;
    height: 32px;
    display: grid;
    place-items: center;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #fff;
    color: var(--muted);
    cursor: pointer;
}

.icon-btn:hover:not(:disabled) {
    color: var(--ink);
    border-color: #d5dce7;
}

.body {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 18px 20px;
    overflow-y: auto;
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
    color: #526075;
    font-size: 11px;
    font-weight: 650;
}

.field input,
.field select,
.field textarea {
    width: 100%;
    padding: 9px 11px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #fff;
    color: var(--ink);
    font-size: 12px;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.field textarea {
    resize: vertical;
    min-height: 74px;
    line-height: 1.5;
}

.field input:focus,
.field select:focus,
.field textarea:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgb(59 92 204 / 12%);
}

.field .invalid {
    border-color: var(--danger);
}

.field small {
    color: var(--danger);
    font-size: 10px;
}

.alert {
    padding: 9px 11px;
    border: 1px solid #f1d3d5;
    border-radius: 8px;
    background: #fff5f5;
    color: var(--danger);
    font-size: 11px;
}

.state {
    min-height: 220px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 24px;
    color: var(--muted);
    font-size: 12px;
    text-align: center;
}

.state i {
    color: var(--danger);
    font-size: 18px;
}

.spinner {
    width: 26px;
    height: 26px;
    border: 3px solid #e9edf4;
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

.foot {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 6px;
    padding-top: 14px;
    border-top: 1px solid var(--line);
}

.btn {
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 650;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.btn:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-outline {
    border: 1px solid var(--line);
    background: #fff;
    color: #526075;
}

.btn-outline:hover:not(:disabled) {
    background: #f6f8fb;
}

.btn-solid {
    border: 1px solid var(--accent);
    background: var(--accent);
    color: #fff;
}

.btn-solid:hover:not(:disabled) {
    background: var(--accent-dark);
}

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.18s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

@media (max-width: 560px) {
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

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>