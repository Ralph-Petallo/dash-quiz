<template>
    <div v-if="show" class="modal" @click.self="$emit('close')">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="edit-user-title">
            <h3 id="edit-user-title">Edit Profile</h3>

            <p v-if="error" class="form-error" role="alert">{{ error }}</p>

            <div class="edit-fields">
                <label for="first_name">First Name</label>
                <input id="first_name" v-model.trim="form.first_name" type="text" autocomplete="off" :disabled="saving"
                    :class="{ invalid: errors.first_name }" />
                <small v-if="errors.first_name">{{ errors.first_name[0] }}</small>
            </div>

            <div class="edit-fields">
                <label for="last_name">Last Name</label>
                <input id="last_name" v-model.trim="form.last_name" type="text" autocomplete="off" :disabled="saving"
                    :class="{ invalid: errors.last_name }" />
                <small v-if="errors.last_name">{{ errors.last_name[0] }}</small>
            </div>

            <div class="edit-fields">
                <label for="email">Email</label>
                <input id="email" v-model.trim="form.email" type="email" autocomplete="off" :disabled="saving"
                    :class="{ invalid: errors.email }" />
                <small v-if="errors.email">{{ errors.email[0] }}</small>
            </div>

            <div class="edit-fields">
                <label for="password">Current Password</label>
                <input id="password" v-model="form.password" type="password" autocomplete="new-password"
                    :disabled="saving" :class="{ invalid: errors.password }" />
                <small v-if="errors.password">{{ errors.password[0] }}</small>
            </div>

            <div class="edit-fields">
                <label for="new_password">New Password</label>
                <input id="new_password" v-model="form.new_password" type="password" autocomplete="new-password"
                    :disabled="saving" :class="{ invalid: errors.new_password }" />
                <small v-if="errors.new_password">{{ errors.new_password[0] }}</small>
            </div>

            <div class="edit-fields">
                <label for="password_confirmation">Confirm New Password</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password"
                    autocomplete="new-password" :disabled="saving" />
            </div>

            <div class="modal-buttons">
                <button type="button" class="cancel-btn" :disabled="saving" @click="$emit('close')">Cancel</button>
                <button type="button" class="save-btn" :disabled="saving" @click="handleSave">
                    {{ saving ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    show: Boolean,
    user: { type: Object, default: () => ({}) },
    saving: Boolean,
    error: { type: String, default: '' },
    errors: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['save', 'close'])

const blankForm = () => ({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    new_password: '',
    password_confirmation: '',
})

const form = ref(blankForm())

// Reset the form each time the modal opens, using the selected student.
watch(
    () => props.show,
    (open) => {
        if (!open) return

        form.value = {
            ...blankForm(),
            first_name: props.user?.first_name || '',
            last_name: props.user?.last_name || '',
            email: props.user?.email || '',
        }
    }
)

const handleSave = () => {
    if (props.saving) return

    const payload = {
        first_name: form.value.first_name,
        last_name: form.value.last_name,
        email: form.value.email,
    }

    if (form.value.new_password) {
        payload.password = form.value.password
        payload.new_password = form.value.new_password
        payload.new_password_confirmation = form.value.password_confirmation
    }

    emit('save', payload)
}
</script>

<style scoped>
.modal {
    position: fixed;
    inset: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.modal-content {
    width: 400px;
    max-width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    padding: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    background: rgba(25, 28, 36, 0.94);
    color: #eceef3;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.45);
}

h3 {
    margin: 0 0 20px;
    color: #f2f3f7;
}

.edit-fields {
    display: flex;
    flex-direction: column;
    margin: 12px 0;
}

.edit-fields label {
    margin-bottom: 6px;
    color: #b8bfce;
    font-size: 0.9rem;
    font-weight: 600;
}

.edit-fields input {
    padding: 10px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 6px;
    outline: none;
    background: rgba(8, 10, 15, 0.55);
    color: #eceef3;
    font-size: 1rem;
}

.form-error {
    margin: 0 0 8px;
    padding: 8px 10px;
    border: 1px solid rgba(255, 120, 120, 0.4);
    border-radius: 6px;
    background: rgba(255, 80, 80, 0.12);
    color: #ffb4b4;
    font-size: 0.85rem;
}

.edit-fields input.invalid {
    border-color: #ff8a8a;
}

.edit-fields small {
    margin-top: 4px;
    color: #ffb4b4;
    font-size: 0.8rem;
}

.edit-fields input:focus {
    border-color: #929fbd;
}

.modal-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 22px;
}

button {
    padding: 10px 18px;
    border: 1px solid transparent;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.cancel-btn {
    border-color: rgba(255, 255, 255, 0.14);
    background: rgba(255, 255, 255, 0.06);
    color: #c6cbd5;
}

.save-btn {
    background: #d8deeb;
    color: #171a22;
}

.save-btn:hover {
    background: #eef1f7;
}
</style>