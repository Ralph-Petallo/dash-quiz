<template>
    <div class="settings-page">

        <!-- =====================================================
             HEADER
        ====================================================== -->

        <header class="page-header">

            <div class="header-title">

                <div class="header-icon">
                    <i class="fa-solid fa-sliders"></i>
                </div>

                <div>
                    <span class="eyebrow">
                        CSS PREP · ADMINISTRATION
                    </span>

                    <h1>Settings</h1>

                    <p>
                        Manage your administrator account and system preferences.
                    </p>
                </div>

            </div>

        </header>


        <!-- =====================================================
             SUCCESS
        ====================================================== -->

        <Transition name="fade-slide">

            <div v-if="successMessage" class="alert-success">
                <i class="fa-solid fa-circle-check"></i>

                <span>
                    {{ successMessage }}
                </span>
            </div>

        </Transition>


        <!-- =====================================================
             SETTINGS GRID
        ====================================================== -->

        <div class="settings-grid">


            <!-- =================================================
                 PROFILE
            ================================================== -->

            <section class="card profile-card">

                <div class="card-header">

                    <div class="section-icon">
                        <i class="fa-solid fa-user"></i>
                    </div>

                    <div>
                        <h2>Administrator Profile</h2>

                        <p>
                            Update your personal account information.
                        </p>
                    </div>

                </div>


                <!-- Profile -->
                <div class="profile-section">

                    <div class="profile-image-wrapper">

                        <img :src="previewAvatar || userAvatar" alt="Profile" class="profile-photo" />

                        <label for="profile-upload" class="camera-btn" title="Change profile photo">
                            <i class="fa-solid fa-camera"></i>
                        </label>

                        <input id="profile-upload" type="file" accept="image/jpeg,image/png,image/webp" hidden
                            @change="handleProfileUpload" />

                    </div>


                    <div class="profile-info">

                        <h3>
                            {{ userFullName }}
                        </h3>

                        <span class="role-badge">
                            <i class="fa-solid fa-shield-halved"></i>
                            {{ user?.role || 'Administrator' }}
                        </span>

                        <small>
                            JPG, PNG or WEBP · Maximum 2MB
                        </small>

                    </div>

                </div>


                <!-- First name -->
                <div class="form-group">

                    <label for="first_name">
                        First Name
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-user input-icon"></i>

                        <input id="first_name" type="text" v-model="form.first_name" placeholder="Enter first name" />

                    </div>

                </div>


                <!-- Last name -->
                <div class="form-group">

                    <label for="last_name">
                        Last Name
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-user input-icon"></i>

                        <input id="last_name" type="text" v-model="form.last_name" placeholder="Enter last name" />

                    </div>

                </div>


                <!-- Email -->
                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-envelope input-icon"></i>

                        <input id="email" type="email" v-model="form.email" placeholder="Enter email address" />

                    </div>

                </div>


                <!-- Role -->
                <div class="form-group">

                    <label for="role">
                        Account Role
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-shield-halved input-icon"></i>

                        <input id="role" type="text" :value="user?.role || 'admin'" disabled />

                    </div>

                    <small class="field-help">
                        Your administrator role is managed by the system.
                    </small>

                </div>

            </section>



            <!-- =================================================
                 SECURITY
            ================================================== -->

            <section class="card">

                <div class="card-header">

                    <div class="section-icon">
                        <i class="fa-solid fa-lock"></i>
                    </div>

                    <div>
                        <h2>Password & Security</h2>

                        <p>
                            Keep your administrator account secure.
                        </p>
                    </div>

                </div>


                <!-- Current -->
                <div class="form-group">

                    <label for="current_password">
                        Current Password
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-key input-icon"></i>

                        <input id="current_password" type="password" v-model="password.current"
                            placeholder="Enter current password" />

                    </div>

                </div>


                <!-- New -->
                <div class="form-group">

                    <label for="new_password">
                        New Password
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-lock input-icon"></i>

                        <input id="new_password" type="password" v-model="password.new"
                            placeholder="Enter new password" />

                    </div>

                </div>


                <!-- Confirm -->
                <div class="form-group">

                    <label for="confirm_password">
                        Confirm New Password
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-check input-icon"></i>

                        <input id="confirm_password" type="password" v-model="password.confirm"
                            placeholder="Confirm new password" />

                    </div>

                </div>


                <div class="security-note">

                    <i class="fa-solid fa-circle-info"></i>

                    <span>
                        Leave the password fields empty if you only want
                        to update your profile information.
                    </span>

                </div>

            </section>



            <!-- =================================================
                 ADMIN MANAGEMENT
            ================================================== -->

            <section class="card">

                <div class="card-header">

                    <div class="section-icon">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>

                    <div>
                        <h2>Administrator Access</h2>

                        <p>
                            Create another administrator account.
                        </p>
                    </div>

                </div>


                <div class="admin-warning">

                    <i class="fa-solid fa-triangle-exclamation"></i>

                    <div>

                        <strong>
                            Administrator access
                        </strong>

                        <p>
                            Only create accounts for authorized staff
                            who need access to the CSS Prep administration area.
                        </p>

                    </div>

                </div>


                <!-- First -->
                <div class="form-group">

                    <label for="admin_first_name">
                        First Name
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-user input-icon"></i>

                        <input id="admin_first_name" type="text" v-model="newAdmin.first_name"
                            placeholder="Enter first name" />

                    </div>

                </div>


                <!-- Last -->
                <div class="form-group">

                    <label for="admin_last_name">
                        Last Name
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-user input-icon"></i>

                        <input id="admin_last_name" type="text" v-model="newAdmin.last_name"
                            placeholder="Enter last name" />

                    </div>

                </div>


                <!-- Email -->
                <div class="form-group">

                    <label for="admin_email">
                        Administrator Email
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-envelope input-icon"></i>

                        <input id="admin_email" type="email" v-model="newAdmin.email"
                            placeholder="Enter administrator email" />

                    </div>

                </div>


                <!-- Password -->
                <div class="form-group">

                    <label for="admin_password">
                        Password
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-lock input-icon"></i>

                        <input id="admin_password" type="password" v-model="newAdmin.password"
                            placeholder="Create password" />

                    </div>

                </div>


                <!-- Confirm -->
                <div class="form-group">

                    <label for="admin_password_confirmation">
                        Confirm Password
                    </label>

                    <div class="input-wrapper">

                        <i class="fa-solid fa-check input-icon"></i>

                        <input id="admin_password_confirmation" type="password" v-model="newAdmin.password_confirmation"
                            placeholder="Confirm password" />

                    </div>

                </div>


                <button class="btn btn-dark full-btn" :disabled="creatingAdmin" @click="createAdmin">

                    <i class="fa-solid" :class="creatingAdmin
                            ? 'fa-spinner fa-spin'
                            : 'fa-user-plus'
                        "></i>

                    {{
                        creatingAdmin
                            ? 'Creating...'
                            : 'Create Administrator'
                    }}

                </button>

            </section>



            <!-- =================================================
                 SYSTEM PREFERENCES
            ================================================== -->

            <section class="card">

                <div class="card-header">

                    <div class="section-icon">
                        <i class="fa-solid fa-sliders"></i>
                    </div>

                    <div>
                        <h2>System Preferences</h2>

                        <p>
                            Configure how the administration interface behaves.
                        </p>
                    </div>

                </div>


                <!-- Dashboard refresh -->
                <div class="setting-row">

                    <div class="setting-icon">
                        <i class="fa-solid fa-rotate"></i>
                    </div>

                    <div class="setting-content">

                        <strong>
                            Dashboard Auto Refresh
                        </strong>

                        <p>
                            Keep dashboard statistics updated automatically.
                        </p>

                    </div>

                    <span class="setting-status">
                        15 sec
                    </span>

                </div>


                <!-- Activity -->
                <div class="setting-row">

                    <div class="setting-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>

                    <div class="setting-content">

                        <strong>
                            Activity Tracking
                        </strong>

                        <p>
                            Track administrator actions in the activity log.
                        </p>

                    </div>

                    <span class="status-dot active"></span>

                </div>


                <!-- Assessment management -->
                <div class="setting-row">

                    <div class="setting-icon">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>

                    <div class="setting-content">

                        <strong>
                            Assessment Management
                        </strong>

                        <p>
                            Manage competency assessments from the admin panel.
                        </p>

                    </div>

                    <span class="status-text">
                        Enabled
                    </span>

                </div>


                <!-- User management -->
                <div class="setting-row">

                    <div class="setting-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>

                    <div class="setting-content">

                        <strong>
                            User Management
                        </strong>

                        <p>
                            Manage student accounts and activity records.
                        </p>

                    </div>

                    <span class="status-text">
                        Enabled
                    </span>

                </div>

            </section>



            <!-- =================================================
                 ACCOUNT INFORMATION
            ================================================== -->

            <section class="card account-card">

                <div class="card-header">

                    <div class="section-icon">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>

                    <div>
                        <h2>Account Information</h2>

                        <p>
                            Current administrator account details.
                        </p>
                    </div>

                </div>


                <div class="account-info">

                    <div class="info-row">

                        <span>
                            Account ID
                        </span>

                        <strong>
                            #{{ user?.id || 'N/A' }}
                        </strong>

                    </div>


                    <div class="info-row">

                        <span>
                            Account Type
                        </span>

                        <strong>
                            Administrator
                        </strong>

                    </div>


                    <div class="info-row">

                        <span>
                            Status
                        </span>

                        <strong class="online-status">

                            <span></span>

                            Active

                        </strong>

                    </div>


                    <div class="info-row">

                        <span>
                            Last Activity
                        </span>

                        <strong>
                            {{ user?.last_activity || 'Current session' }}
                        </strong>

                    </div>

                </div>

            </section>

        </div>



        <!-- =====================================================
             ACTIONS
        ====================================================== -->

        <div class="action-bar">

            <button class="btn btn-light" @click="resetForm">
                <i class="fa-solid fa-rotate-left"></i>
                Reset
            </button>

            <button class="btn btn-dark" :disabled="saving" @click="saveChanges">

                <i class="fa-solid" :class="saving
                        ? 'fa-spinner fa-spin'
                        : 'fa-floppy-disk'
                    "></i>

                {{
                    saving
                        ? 'Saving...'
                        : 'Save Changes'
                }}

            </button>

        </div>

    </div>
</template>


<script setup>
import {
    reactive,
    watchEffect,
    ref,
    onUnmounted
} from 'vue'

import axios from 'axios'

import { useUser } from '@/composables/useUser'


/* =========================================================
   USER
========================================================= */

const {
    user,
    userAvatar,
    userFullName,
    fetchUser
} = useUser()


/* =========================================================
   PROFILE
========================================================= */

const form = reactive({
    first_name: '',
    last_name: '',
    email: '',
})


/* =========================================================
   PASSWORD
========================================================= */

const password = reactive({
    current: '',
    new: '',
    confirm: '',
})


/* =========================================================
   NEW ADMIN
========================================================= */

const newAdmin = reactive({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
})


/* =========================================================
   STATE
========================================================= */

const previewAvatar = ref(null)
const selectedFile = ref(null)

const successMessage = ref('')

const saving = ref(false)
const creatingAdmin = ref(false)


/* =========================================================
   SYNC USER
========================================================= */

watchEffect(() => {

    if (!user.value) {
        return
    }

    form.first_name =
        user.value.first_name || ''

    form.last_name =
        user.value.last_name || ''

    form.email =
        user.value.email || ''
})


/* =========================================================
   PROFILE PHOTO
========================================================= */

const handleProfileUpload = (event) => {

    const file =
        event.target.files?.[0]

    if (!file) {
        return
    }

    const allowed = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp'
    ]

    if (!allowed.includes(file.type)) {

        alert(
            'Please select a JPG, PNG, or WEBP image.'
        )

        event.target.value = ''

        return
    }

    if (file.size > 2 * 1024 * 1024) {

        alert(
            'The image size must not exceed 2MB.'
        )

        event.target.value = ''

        return
    }

    selectedFile.value = file

    if (previewAvatar.value) {
        URL.revokeObjectURL(
            previewAvatar.value
        )
    }

    previewAvatar.value =
        URL.createObjectURL(file)
}


/* =========================================================
   RESET
========================================================= */

const resetForm = () => {

    if (!user.value) {
        return
    }

    form.first_name =
        user.value.first_name || ''

    form.last_name =
        user.value.last_name || ''

    form.email =
        user.value.email || ''

    password.current = ''
    password.new = ''
    password.confirm = ''

    previewAvatar.value = null
    selectedFile.value = null
}


/* =========================================================
   SUCCESS MESSAGE
========================================================= */

let successTimer = null

const showSuccess = (message) => {

    successMessage.value = message

    clearTimeout(successTimer)

    successTimer = setTimeout(() => {
        successMessage.value = ''
    }, 3000)
}


/* =========================================================
   SAVE PROFILE
========================================================= */

const saveChanges = async () => {

    if (saving.value) {
        return
    }

    saving.value = true

    try {

        /*
         * Update profile + password
         */

        const formData = new FormData()

        formData.append(
            'first_name',
            form.first_name
        )

        formData.append(
            'last_name',
            form.last_name
        )

        formData.append(
            'email',
            form.email
        )

        /*
         * Only send password fields when
         * the admin actually entered one.
         */

        if (
            password.current ||
            password.new ||
            password.confirm
        ) {

            formData.append(
                'current_password',
                password.current
            )

            formData.append(
                'new_password',
                password.new
            )

            formData.append(
                'confirm_password',
                password.confirm
            )
        }


        await axios.post(
            '/api/admin/update-profile',
            formData
        )


        /*
         * Upload profile photo only
         * when a new image was selected.
         */

        if (selectedFile.value) {

            const imageData =
                new FormData()

            imageData.append(
                'photo',
                selectedFile.value
            )

            await axios.post(
                '/api/admin/upload-photo',
                imageData,
                {
                    headers: {
                        'Content-Type':
                            'multipart/form-data'
                    }
                }
            )
        }


        /*
         * Refresh current user.
         */

        await fetchUser(true)


        password.current = ''
        password.new = ''
        password.confirm = ''

        previewAvatar.value = null
        selectedFile.value = null

        showSuccess(
            'Administrator profile updated successfully.'
        )

    } catch (error) {

        console.error(
            'Failed to update profile:',
            error
        )

        alert(
            error.response?.data?.message ||
            'Unable to update your profile.'
        )

    } finally {

        saving.value = false
    }
}


/* =========================================================
   CREATE ADMIN
========================================================= */

const createAdmin = async () => {

    if (creatingAdmin.value) {
        return
    }

    if (
        !newAdmin.first_name ||
        !newAdmin.last_name ||
        !newAdmin.email ||
        !newAdmin.password ||
        !newAdmin.password_confirmation
    ) {

        alert(
            'Please complete all administrator fields.'
        )

        return
    }


    if (
        newAdmin.password !==
        newAdmin.password_confirmation
    ) {

        alert(
            'Password and confirmation do not match.'
        )

        return
    }


    creatingAdmin.value = true

    try {

        await axios.post(
            '/api/admin/create-admin',
            newAdmin
        )


        /*
         * Clear form after successful creation.
         */

        newAdmin.first_name = ''
        newAdmin.last_name = ''
        newAdmin.email = ''
        newAdmin.password = ''
        newAdmin.password_confirmation = ''


        showSuccess(
            'New administrator created successfully.'
        )

    } catch (error) {

        console.error(
            'Failed to create administrator:',
            error
        )

        alert(
            error.response?.data?.message ||
            'Unable to create administrator.'
        )

    } finally {

        creatingAdmin.value = false
    }
}


/* =========================================================
   INITIAL LOAD
========================================================= */

fetchUser()


/* =========================================================
   CLEANUP
========================================================= */

onUnmounted(() => {

    if (previewAvatar.value) {

        URL.revokeObjectURL(
            previewAvatar.value
        )
    }

    clearTimeout(successTimer)
})
</script>


<style scoped>
/* =========================================================
   PAGE
========================================================= */

.settings-page {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 28px 24px 40px;

    box-sizing: border-box;

    color: #222;
}


/* =========================================================
   HEADER
========================================================= */

.page-header {
    margin-bottom: 24px;
}

.header-title {
    display: flex;
    align-items: center;
    gap: 14px;
}

.header-icon {
    width: 42px;
    height: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #111;
    color: #fff;

    border-radius: 7px;

    font-size: 16px;
}

.eyebrow {
    display: block;

    margin-bottom: 4px;

    color: #888;

    font-size: 10px;
    font-weight: 700;

    letter-spacing: 0.12em;
}

.page-header h1 {
    margin: 0;

    color: #111;

    font-size: 24px;
    font-weight: 700;
    letter-spacing: -0.02em;
}

.page-header p {
    margin: 4px 0 0;

    color: #777;

    font-size: 13px;
}


/* =========================================================
   SUCCESS
========================================================= */

.alert-success {
    display: flex;
    align-items: center;
    gap: 9px;

    margin-bottom: 18px;
    padding: 11px 14px;

    background: rgb(35, 140, 51);

    border: 1px solid #dedede;
    border-left: 3px solid #111;

    border-radius: 6px;

    color: #eee;

    font-size: 13px;
}

.alert-success i {
    color: #111;
}


/* =========================================================
   GRID
========================================================= */

.settings-grid {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 14px;
}


/* =========================================================
   CARD
========================================================= */

.card {
    min-width: 0;

    padding: 20px;

    background: #fff;

    border: 1px solid #e5e5e5;

    border-radius: 8px;

    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

.card:hover {
    border-color: #d7d7d7;

    box-shadow:
        0 4px 18px rgba(0, 0, 0, 0.035);
}


/* =========================================================
   CARD HEADER
========================================================= */

.card-header {
    display: flex;
    align-items: flex-start;

    gap: 11px;

    margin-bottom: 20px;
    padding-bottom: 15px;

    border-bottom: 1px solid #eeeeee;
}

.section-icon {
    flex-shrink: 0;

    width: 32px;
    height: 32px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #f3f3f3;

    border: 1px solid #e6e6e6;

    border-radius: 6px;

    color: #222;

    font-size: 13px;
}

.card-header h2 {
    margin: 1px 0 3px;

    color: #171717;

    font-size: 15px;
    font-weight: 700;
}

.card-header p {
    margin: 0;

    color: #858585;

    font-size: 12px;
    line-height: 1.45;
}


/* =========================================================
   PROFILE
========================================================= */

.profile-section {
    display: flex;
    align-items: center;

    gap: 14px;

    margin-bottom: 22px;
    padding-bottom: 18px;

    border-bottom: 1px solid #eeeeee;
}

.profile-image-wrapper {
    position: relative;

    flex-shrink: 0;
}

.profile-photo {
    width: 68px;
    height: 68px;

    display: block;

    object-fit: cover;

    border: 2px solid #e5e5e5;

    border-radius: 50%;

    background: #f5f5f5;
}

.camera-btn {
    position: absolute;

    right: -2px;
    bottom: 0;

    width: 24px;
    height: 24px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #111;

    color: #fff;

    border: 2px solid #fff;

    border-radius: 50%;

    cursor: pointer;

    font-size: 9px;

    transition:
        background 0.15s ease,
        transform 0.15s ease;
}

.camera-btn:hover {
    background: #333;

    transform: scale(1.05);
}

.profile-info h3 {
    margin: 0 0 6px;

    color: #171717;

    font-size: 15px;
    font-weight: 700;
}

.profile-info small {
    display: block;

    margin-top: 6px;

    color: #999;

    font-size: 10px;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;

    padding: 4px 8px;

    background: #f1f1f1;

    border: 1px solid #e4e4e4;

    border-radius: 4px;

    color: #333;

    font-size: 10px;
    font-weight: 700;

    text-transform: capitalize;
}


/* =========================================================
   FORM
========================================================= */

.form-group {
    margin-bottom: 15px;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;

    margin-bottom: 6px;

    color: #444;

    font-size: 12px;
    font-weight: 650;
}

.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;

    top: 50%;
    left: 12px;

    transform: translateY(-50%);

    color: #999;

    font-size: 12px;

    pointer-events: none;
}

input {
    width: 100%;

    box-sizing: border-box;

    padding: 10px 12px 10px 34px;

    background: #fff;

    border: 1px solid #dcdcdc;

    border-radius: 6px;

    outline: none;

    color: #222;

    font-size: 13px;

    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

input:focus {
    border-color: #111;

    box-shadow:
        0 0 0 2px rgba(17, 17, 17, 0.06);
}

input:disabled {
    background: #f6f6f6;

    color: #999;

    cursor: not-allowed;
}

input::placeholder {
    color: #aaa;
}

.field-help {
    display: block;

    margin-top: 5px;

    color: #999;

    font-size: 10px;
}


/* =========================================================
   SECURITY NOTE
========================================================= */

.security-note {
    display: flex;
    align-items: flex-start;

    gap: 8px;

    margin-top: 4px;
    padding: 10px;

    background: #f7f7f7;

    border: 1px solid #ededed;

    border-radius: 6px;

    color: #777;

    font-size: 10px;

    line-height: 1.5;
}

.security-note i {
    margin-top: 2px;

    color: #777;
}


/* =========================================================
   ADMIN WARNING
========================================================= */

.admin-warning {
    display: flex;
    align-items: flex-start;

    gap: 9px;

    margin-bottom: 18px;
    padding: 10px;

    background: #f7f7f7;

    border: 1px solid #e8e8e8;

    border-left: 3px solid #777;

    border-radius: 6px;
}

.admin-warning>i {
    margin-top: 2px;

    color: #666;

    font-size: 12px;
}

.admin-warning strong {
    display: block;

    margin-bottom: 2px;

    color: #333;

    font-size: 11px;
}

.admin-warning p {
    margin: 0;

    color: #888;

    font-size: 10px;

    line-height: 1.45;
}


/* =========================================================
   SYSTEM SETTINGS
========================================================= */

.setting-row {
    display: flex;
    align-items: center;

    gap: 11px;

    padding: 13px 0;

    border-bottom: 1px solid #eeeeee;
}

.setting-row:first-of-type {
    padding-top: 0;
}

.setting-row:last-child {
    padding-bottom: 0;

    border-bottom: none;
}

.setting-icon {
    flex-shrink: 0;

    width: 31px;
    height: 31px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #f5f5f5;

    border: 1px solid #e8e8e8;

    border-radius: 6px;

    color: #444;

    font-size: 11px;
}

.setting-content {
    flex: 1;

    min-width: 0;
}

.setting-content strong {
    display: block;

    margin-bottom: 2px;

    color: #333;

    font-size: 12px;
}

.setting-content p {
    margin: 0;

    color: #929292;

    font-size: 10px;

    line-height: 1.4;
}

.setting-status {
    flex-shrink: 0;

    padding: 4px 7px;

    background: #f2f2f2;

    border: 1px solid #e5e5e5;

    border-radius: 4px;

    color: #555;

    font-size: 9px;
    font-weight: 700;
}

.status-text {
    flex-shrink: 0;

    color: #666;

    font-size: 9px;
    font-weight: 700;

    text-transform: uppercase;
}

.status-dot {
    flex-shrink: 0;

    width: 7px;
    height: 7px;

    border-radius: 50%;

    background: #aaa;
}

.status-dot.active {
    background: #222;
}


/* =========================================================
   ACCOUNT INFO
========================================================= */

.account-info {
    display: flex;
    flex-direction: column;
}

.info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 15px;

    padding: 11px 0;

    border-bottom: 1px solid #eeeeee;
}

.info-row:first-child {
    padding-top: 0;
}

.info-row:last-child {
    padding-bottom: 0;

    border-bottom: none;
}

.info-row span:first-child {
    color: #888;

    font-size: 11px;
}

.info-row strong {
    color: #333;

    font-size: 11px;

    text-align: right;
}

.online-status {
    display: inline-flex;

    align-items: center;

    gap: 5px;
}

.online-status span {
    width: 6px;
    height: 6px;

    border-radius: 50%;

    background: #222;
}


/* =========================================================
   BUTTONS
========================================================= */

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    gap: 7px;

    padding: 10px 15px;

    border: 1px solid transparent;

    border-radius: 6px;

    font-size: 12px;
    font-weight: 650;

    cursor: pointer;

    transition:
        background 0.15s ease,
        border-color 0.15s ease,
        opacity 0.15s ease;
}

.btn:disabled {
    opacity: 0.55;

    cursor: not-allowed;
}

.btn-dark {
    background: #111;

    color: #fff;
}

.btn-dark:hover:not(:disabled) {
    background: #2b2b2b;
}

.btn-light {
    background: #f4f4f4;

    border-color: #e3e3e3;

    color: #444;
}

.btn-light:hover {
    background: #e9e9e9;
}

.full-btn {
    width: 100%;
}


/* =========================================================
   ACTION BAR
========================================================= */

.action-bar {
    display: flex;
    align-items: center;
    justify-content: flex-end;

    gap: 8px;

    margin-top: 16px;
    padding-top: 16px;

    border-top: 1px solid #e5e5e5;
}


/* =========================================================
   TRANSITION
========================================================= */

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;

    transform: translateY(-5px);
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 900px) {

    .settings-grid {
        grid-template-columns: 1fr;
    }

    .settings-page {
        max-width: 700px;
    }
}


@media (max-width: 600px) {

    .settings-page {
        padding: 20px 14px 30px;
    }

    .header-title {
        align-items: flex-start;
    }

    .page-header h1 {
        font-size: 21px;
    }

    .card {
        padding: 16px;
    }

    .profile-section {
        align-items: flex-start;
    }

    .action-bar {
        flex-direction: column-reverse;
    }

    .action-bar .btn {
        width: 100%;
    }

    .setting-row {
        align-items: flex-start;
    }

    .setting-status,
    .status-text {
        margin-top: 2px;
    }

}


@media (max-width: 400px) {

    .header-icon {
        width: 38px;
        height: 38px;
    }

    .profile-photo {
        width: 60px;
        height: 60px;
    }

    .profile-info h3 {
        font-size: 14px;
    }

}
</style>