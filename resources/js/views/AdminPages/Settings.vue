    <template>
        <div class="settings-page">

            <!-- Header -->
            <div class="page-header">
                <div>
                    <h1>Admin Settings</h1>
                    <p>Manage administrator account details.</p>
                </div>
            </div>

            <div class="alert-success" v-if="successMessage">
                {{ successMessage }}
            </div>

            <div class="settings-grid">

                <!-- Profile card -->
                <div class="card">

                    <div class="profile-section">
                        <div class="profile-image-wrapper">
                            <img :src="previewAvatar || userAvatar" alt="Profile" class="profile-photo" />
                            <label for="profile-upload" class="camera-btn">
                                <i class="fa-solid fa-camera"></i>
                            </label>
                            <input id="profile-upload" type="file" accept="image/*" hidden
                                @change="handleProfileUpload" />
                        </div>

                        <div>
                            <h2 class="profile-name">{{ userFullName }}</h2>
                            <p class="role-badge">{{ user?.role }}</p>
                            <small class="upload-text">JPG, PNG or WEBP · max 2MB</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input id="first_name" type="text" v-model="form.first_name" placeholder="First name" />
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input id="last_name" type="text" v-model="form.last_name" placeholder="Last name" />
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input id="email" type="email" v-model="form.email" placeholder="Email address" />
                    </div>

                    <div class="form-group">
                        <label for="role">Role</label>
                        <input id="role" type="text" :value="user?.role" disabled />
                    </div>

                </div>

                <!-- Security card -->
                <div class="card">

                    <div class="card-header">
                        <h2>Security</h2>
                        <p>Update your password</p>
                    </div>

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input id="current_password" type="password" v-model="password.current"
                            placeholder="Enter current password" />
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input id="new_password" type="password" v-model="password.new"
                            placeholder="Enter new password" />
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input id="confirm_password" type="password" v-model="password.confirm"
                            placeholder="Confirm new password" />
                    </div>

                </div>

            </div>

            <!-- Action buttons -->
            <div class="action-buttons">
                <button class="btn btn-secondary" @click="resetForm">Reset</button>
                <button class="btn btn-primary" @click="saveChanges">Save Changes</button>
            </div>

        </div>
    </template>

<script setup>
import { reactive, watchEffect, ref } from 'vue'
import axios from 'axios'
import { useUser } from '@/composables/useUser'

const { user, userAvatar, userFullName, fetchUser } = useUser()

const form = reactive({
    first_name: '',
    last_name: '',
    email: '',
})

const password = reactive({
    current: '',
    new: '',
    confirm: '',
})

const previewAvatar = ref(null)
const selectedFile = ref(null)

watchEffect(() => {
    if (!user.value) return
    form.first_name = user.value.first_name
    form.last_name = user.value.last_name
    form.email = user.value.email
})

const handleProfileUpload = (event) => {
    const file = event.target.files?.[0]
    if (!file) return

    const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']

    if (!allowed.includes(file.type)) {
        alert('Only JPG, PNG or WEBP allowed.')
        return
    }

    if (file.size > 2 * 1024 * 1024) {
        alert('Max file size is 2MB.')
        return
    }

    selectedFile.value = file
    previewAvatar.value = URL.createObjectURL(file)
}

const resetForm = () => {
    if (!user.value) return

    form.first_name = user.value.first_name
    form.last_name = user.value.last_name
    form.email = user.value.email

    password.current = ''
    password.new = ''
    password.confirm = ''

    previewAvatar.value = null
    selectedFile.value = null
}

const successMessage = ref('')


const saveChanges = async () => {
    try {
        // 1. update profile text + password
        const formData = new FormData()

        formData.append('first_name', form.first_name)
        formData.append('last_name', form.last_name)
        formData.append('email', form.email)

        formData.append('current_password', password.current)
        formData.append('new_password', password.new)
        formData.append('confirm_password', password.confirm)

        await axios.post('/api/admin/update-profile', formData)

        // 2. upload image ONLY if selected
        if (selectedFile.value) {
            const img = new FormData()
            img.append('photo', selectedFile.value)

            await axios.post('/api/admin/upload-photo', img, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
        }

        await fetchUser(true)
        successMessage.value = 'Profile Updated Successfully!'
        previewAvatar.value = null
        selectedFile.value = null

    } catch (error) {
        console.error(error)
    }
}

fetchUser()
</script>

<style scoped>
.settings-page {
    padding: 28px 24px;
    max-width: 1000px;
}

/* Header */
.page-header {
    margin-bottom: 24px;
}

.page-header h1 {
    font-size: 22px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 4px;
}

.page-header p {
    font-size: 0.875rem;
    color: #6b7280;
}

/* Grid */
.settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

/* Card */
.card {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

/* Profile section */
.profile-section {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 22px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f1f5f9;
}

.alert-success {
    background: #ecfeff;
    color: #0f766e;
    padding: 10px 14px;
    border-radius: 10px;
    margin-bottom: 1rem;
    font-size: 0.85rem;
    display: flex;
    gap: 8px;
}

.profile-image-wrapper {
    position: relative;
    flex-shrink: 0;
}

.profile-photo {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    object-fit: cover;
    border: 2.5px solid #e0e7ff;
    display: block;
}

.camera-btn {
    position: absolute;
    bottom: 0;
    right: -2px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #4f46e5;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #fff;
    cursor: pointer;
    font-size: 10px;
    transition: background 0.15s;
}

.camera-btn:hover {
    background: #3730a3;
}

.profile-name {
    font-size: 15px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 4px;
}

.role-badge {
    display: inline-block;
    padding: 3px 10px;
    background: #eef2ff;
    color: #4338ca;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
    text-transform: capitalize;
}

.upload-text {
    display: block;
    margin-top: 6px;
    color: #9ca3af;
    font-size: 9px;
}

/* Card header (security) */
.card-header {
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid #f1f5f9;
}

.card-header h2 {
    font-size: 15px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 3px;
}

.card-header p {
    font-size: 13px;
    color: #6b7280;
}

/* Form */
.form-group {
    margin-bottom: 16px;
}

.form-group:last-child {
    margin-bottom: 0;
}

label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    font-size: 14px;
    color: #111827;
    background: #fff;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
    box-sizing: border-box;
}

input:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
}

input:disabled {
    background: #f9fafb;
    color: #9ca3af;
    cursor: not-allowed;
}

input::placeholder {
    color: #9ca3af;
}

/* Action buttons */
.action-buttons {
    margin-top: 22px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn {
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: background 0.15s, opacity 0.15s;
}

.btn-primary {
    background: #4f46e5;
    color: #fff;
}

.btn-primary:hover {
    background: #3730a3;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

/* Responsive */
@media (max-width: 768px) {
    .settings-page {
        padding: 20px 16px;
    }

    .settings-grid {
        grid-template-columns: 1fr;
    }

    .action-buttons {
        flex-direction: column-reverse;
    }

    .btn {
        width: 100%;
        text-align: center;
    }
}
</style>