<template>
    <div class="settings-page">
        <header class="page-header">
            <div class="header-title">
                <div class="header-icon">
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                </div>

                <div>
                    <span class="eyebrow">CSS PREP · ADMINISTRATION</span>
                    <h1>Settings</h1>
                    <p>Manage your administrator account and system preferences.</p>
                </div>
            </div>
        </header>

        <Transition name="fade-slide">
            <div v-if="successMessage" class="alert-success" role="status">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                <span>{{ successMessage }}</span>
            </div>
        </Transition>

        <Transition name="fade-slide">
            <div v-if="errorMessage" class="alert-error" role="alert">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <span>{{ errorMessage }}</span>
            </div>
        </Transition>

        <div class="settings-grid">
            <!-- Administrator profile -->
            <section class="card profile-card">
                <div class="card-header">
                    <div class="section-icon">
                        <i class="fa-solid fa-user" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h2>Administrator Profile</h2>
                        <p>Update your personal account information.</p>
                    </div>
                </div>

                <div class="profile-section">
                    <div class="profile-image-wrapper">
                        <img :src="previewAvatar || userAvatar" alt="Administrator profile" class="profile-photo" />

                        <label for="profile-upload" class="camera-btn" title="Change profile photo"
                            aria-label="Change profile photo">
                            <i class="fa-solid fa-camera" aria-hidden="true"></i>
                        </label>

                        <input id="profile-upload" name="profile_photo" type="file"
                            accept="image/jpeg,image/png,image/webp" hidden @change="handleProfileUpload" />
                    </div>

                    <div class="profile-info">
                        <h3>{{ userFullName }}</h3>

                        <span class="role-badge">
                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                            {{ user?.role || "Administrator" }}
                        </span>

                        <small>JPG, PNG or WEBP · Maximum 2MB</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-user input-icon" aria-hidden="true"></i>
                        <input id="first_name" v-model.trim="form.first_name" name="first_name" type="text"
                            autocomplete="given-name" required maxlength="100" placeholder="Enter first name" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-user input-icon" aria-hidden="true"></i>
                        <input id="last_name" v-model.trim="form.last_name" name="last_name" type="text"
                            autocomplete="family-name" required maxlength="100" placeholder="Enter last name" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-envelope input-icon" aria-hidden="true"></i>
                        <input id="email" v-model.trim="form.email" name="email" type="email" autocomplete="email"
                            inputmode="email" required maxlength="255" placeholder="Enter email address" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="role">Account Role</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-shield-halved input-icon" aria-hidden="true"></i>
                        <input id="role" name="role" type="text" :value="user?.role || 'admin'" autocomplete="off"
                            disabled />
                    </div>
                    <small class="field-help">
                        Your administrator role is managed by the system.
                    </small>
                </div>
            </section>

            <!-- Password and security -->
            <section class="card">
                <div class="card-header">
                    <div class="section-icon">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h2>Password &amp; Security</h2>
                        <p>Keep your administrator account secure.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-key input-icon" aria-hidden="true"></i>
                        <input id="current_password" v-model="password.current" name="current_password" type="password"
                            autocomplete="current-password" placeholder="Enter current password" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                        <input id="new_password" v-model="password.new" name="new_password" type="password"
                            autocomplete="new-password" minlength="8" placeholder="Enter new password" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-check input-icon" aria-hidden="true"></i>
                        <input id="confirm_password" v-model="password.confirm" name="password_confirmation"
                            type="password" autocomplete="new-password" minlength="8"
                            placeholder="Confirm new password" />
                    </div>
                </div>

                <div class="security-note">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <span>
                        Leave the password fields empty if you only want to update your
                        profile information.
                    </span>
                </div>
            </section>

            <!-- System preferences -->
            <section class="card">
                <div class="card-header">
                    <div class="section-icon">
                        <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h2>System Preferences</h2>
                        <p>Configure how the administration interface behaves.</p>
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-icon">
                        <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                    </div>
                    <div class="setting-content">
                        <strong>Dashboard Auto Refresh</strong>
                        <p>Keep dashboard statistics updated automatically.</p>
                    </div>
                    <span class="setting-status">15 sec</span>
                </div>

                <div class="setting-row">
                    <div class="setting-icon">
                        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                    </div>
                    <div class="setting-content">
                        <strong>Activity Tracking</strong>
                        <p>Track administrator actions in the activity log.</p>
                    </div>
                    <span class="status-dot active" aria-label="Active"></span>
                </div>
            </section>

            <!-- Account information -->
            <section class="card account-card">
                <div class="card-header">
                    <div class="section-icon">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h2>Account Information</h2>
                        <p>Current administrator account details.</p>
                    </div>
                </div>

                <div class="account-info">
                    <div class="info-row">
                        <span>Account ID</span>
                        <strong>#{{ user?.id || "N/A" }}</strong>
                    </div>

                    <div class="info-row">
                        <span>Account Type</span>
                        <strong>Administrator</strong>
                    </div>

                    <div class="info-row">
                        <span>Status</span>
                        <strong class="online-status">
                            <span></span>
                            Active
                        </strong>
                    </div>

                    <div class="info-row">
                        <span>Last Activity</span>
                        <strong>{{ user?.last_activity || "Current session" }}</strong>
                    </div>
                </div>
            </section>
        </div>

        <div class="action-bar">
            <button type="button" class="btn btn-light" :disabled="saving" @click="resetForm">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                Reset
            </button>

            <button type="button" class="btn btn-dark" :disabled="saving" @click="saveChanges">
                <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'" aria-hidden="true"></i>
                {{ saving ? "Saving..." : "Save Changes" }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import api from "@/bootstrap";
import { useUser } from "@/composables/useUser";

const { user, userAvatar, userFullName, fetchUser } = useUser();

const form = reactive({
    first_name: "",
    last_name: "",
    email: "",
});

const password = reactive({
    current: "",
    new: "",
    confirm: "",
});

const newAdmin = reactive({
    first_name: "",
    last_name: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const previewAvatar = ref("");
const avatarFile = ref(null);
const saving = ref(false);
const creatingAdmin = ref(false);
const successMessage = ref("");
const errorMessage = ref("");

const clearMessages = () => {
    successMessage.value = "";
    errorMessage.value = "";
};

const loadForm = () => {
    form.first_name = user.value?.first_name || "";
    form.last_name = user.value?.last_name || "";
    form.email = user.value?.email || "";
};

const resetPassword = () => {
    password.current = "";
    password.new = "";
    password.confirm = "";
};

const resetNewAdmin = () => {
    newAdmin.first_name = "";
    newAdmin.last_name = "";
    newAdmin.email = "";
    newAdmin.password = "";
    newAdmin.password_confirmation = "";
};

onMounted(async () => {
    if (!user.value) {
        await fetchUser();
    }

    loadForm();
});

const handleProfileUpload = (event) => {
    clearMessages();

    const file = event.target.files?.[0];
    if (!file) return;

    const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
    const maxSize = 2 * 1024 * 1024;

    if (!allowedTypes.includes(file.type)) {
        errorMessage.value = "Choose a JPG, PNG, or WEBP image.";
        event.target.value = "";
        return;
    }

    if (file.size > maxSize) {
        errorMessage.value = "The profile image must be 2MB or smaller.";
        event.target.value = "";
        return;
    }

    avatarFile.value = file;

    if (previewAvatar.value) {
        URL.revokeObjectURL(previewAvatar.value);
    }

    previewAvatar.value = URL.createObjectURL(file);
};

const saveChanges = async () => {
    clearMessages();

    if (!form.first_name || !form.last_name || !form.email) {
        errorMessage.value = "First name, last name, and email are required.";
        return;
    }

    const changingPassword =
        password.current || password.new || password.confirm;

    if (changingPassword) {
        if (!password.current || !password.new || !password.confirm) {
            errorMessage.value =
                "Fill in all three password fields to change your password.";
            return;
        }

        if (password.new.length < 8) {
            errorMessage.value = "The new password must be at least 8 characters.";
            return;
        }

        if (password.new !== password.confirm) {
            errorMessage.value = "The new passwords do not match.";
            return;
        }
    }

    saving.value = true;

    try {
        const payload = new FormData();
        payload.append("first_name", form.first_name);
        payload.append("last_name", form.last_name);
        payload.append("email", form.email);

        if (avatarFile.value) {
            payload.append("profile_photo", avatarFile.value);
        }

        if (changingPassword) {
            payload.append("current_password", password.current);
            payload.append("new_password", password.new);
            payload.append("password_confirmation", password.confirm);
        }

        // Change this endpoint and field names to match your backend.
        await api.post("/api/admin/settings", payload);

        await fetchUser();
        loadForm();
        resetPassword();

        successMessage.value = "Settings saved successfully.";
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message || "Could not save settings.";
    } finally {
        saving.value = false;
    }
};

const createAdmin = async () => {
    clearMessages();

    if (
        !newAdmin.first_name ||
        !newAdmin.last_name ||
        !newAdmin.email ||
        !newAdmin.password ||
        !newAdmin.password_confirmation
    ) {
        errorMessage.value = "Complete all fields to create an administrator.";
        return;
    }

    if (newAdmin.password.length < 8) {
        errorMessage.value = "The administrator password must be at least 8 characters.";
        return;
    }

    if (newAdmin.password !== newAdmin.password_confirmation) {
        errorMessage.value = "The administrator passwords do not match.";
        return;
    }

    creatingAdmin.value = true;

    try {
        // Change this endpoint to match your backend.
        await api.post("/api/admin/users", {
            first_name: newAdmin.first_name,
            last_name: newAdmin.last_name,
            email: newAdmin.email,
            password: newAdmin.password,
            password_confirmation: newAdmin.password_confirmation,
            role: "admin",
        });

        resetNewAdmin();
        successMessage.value = "Administrator account created successfully.";
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message || "Could not create administrator.";
    } finally {
        creatingAdmin.value = false;
    }
};

const resetForm = () => {
    clearMessages();
    loadForm();
    resetPassword();
    resetNewAdmin();

    if (previewAvatar.value) {
        URL.revokeObjectURL(previewAvatar.value);
    }

    previewAvatar.value = "";
    avatarFile.value = null;
};
</script>

<style scoped>
/* Page */
.settings-page {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 24px 40px;
    box-sizing: border-box;
    color: #222;
}

/* Header */
.page-header {
    margin-bottom: 22px;
}

.header-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-icon {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f6f8;
    color: #3b4655;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 15px;
}

.eyebrow {
    display: block;
    margin-bottom: 4px;
    color: #858a93;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.1em;
}

.page-header h1 {
    margin: 0;
    color: #20242b;
    font-size: 23px;
    font-weight: 700;
    letter-spacing: -0.02em;
}

.page-header p {
    margin: 4px 0 0;
    color: #858a93;
    font-size: 13px;
}

/* Success message */
.alert-success {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 18px;
    padding: 11px 14px;
    background: rgb(35 140 51);
    border: 1px solid #dedede;
    border-left: 3px solid #111;
    border-radius: 6px;
    color: #eee;
    font-size: 13px;
}

.alert-success i {
    color: #111;
}

/* Grid */
.settings-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

/* Cards */
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
    box-shadow: 0 4px 18px rgb(0 0 0 / 3.5%);
}

/* Card headers */
.card-header {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.section-icon {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
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

/* Profile */
.profile-section {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 22px;
    padding-bottom: 18px;
    border-bottom: 1px solid #eee;
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

/* Form fields */
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
    box-shadow: 0 0 0 2px rgb(17 17 17 / 6%);
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

/* Security note */
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

/* Administrator warning */
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

/* System preferences */
.setting-row {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 13px 0;
    border-bottom: 1px solid #eee;
}

.setting-row:first-of-type {
    padding-top: 0;
}

.setting-row:last-child {
    padding-bottom: 0;
    border-bottom: none;
}

.setting-icon {
    width: 31px;
    height: 31px;
    flex-shrink: 0;
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
    color: green;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
}

.status-dot {
    width: 7px;
    height: 7px;
    flex-shrink: 0;
    border-radius: 50%;
}

.status-dot.active {
    background: green;
}

/* Account information */
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
    border-bottom: 1px solid #eee;
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
    background: green;
}

/* Buttons */
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

/* Action bar */
.action-bar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e5e5e5;
}

/* Success transition */
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

/* Responsive */
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