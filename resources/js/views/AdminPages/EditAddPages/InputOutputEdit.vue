<template>
    <main id="page-top" class="device-editor">
        <section class="editor-card">
            <header class="editor-header">
                <div class="header-copy">
                    <nav class="breadcrumbs" aria-label="Breadcrumb">
                        <RouterLink to="/admin">Admin</RouterLink>
                        <span aria-hidden="true">/</span>

                        <RouterLink :to="assessmentListLink">
                            Assessments
                        </RouterLink>
                        <span aria-hidden="true">/</span>

                        <span aria-current="page">Input &amp; output devices</span>
                    </nav>

                    <p class="eyebrow">Assessment management</p>
                    <h1>Edit input &amp; output devices</h1>
                    <p class="subtitle">
                        Manage the devices included in this assessment.
                    </p>
                </div>

                <button type="button" class="button button-quiet back-button" aria-label="Back to assessment list"
                    :disabled="saving" @click="goBack">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    <span>Back</span>
                </button>
            </header>

            <form @submit.prevent="saveChanges">
                <section class="toolbar" aria-label="Device bank controls">
                    <div class="toolbar-copy">
                        <h2>Device bank</h2>
                        <p>
                            {{ devices.length }} total
                            <span class="separator" aria-hidden="true">·</span>
                            {{ activeCount }} active
                            <span class="separator" aria-hidden="true">·</span>
                            {{ inputCount }} input
                            <span class="separator" aria-hidden="true">·</span>
                            {{ outputCount }} output
                        </p>
                    </div>

                    <div class="toolbar-actions">
                        <label class="filter-field">
                            <i class="fa-solid fa-filter" aria-hidden="true"></i>
                            <span class="sr-only">Filter by device type</span>
                            <select v-model="typeFilter" :disabled="loading || loadFailed || saving">
                                <option value="all">All types</option>
                                <option value="input">Input devices</option>
                                <option value="output">Output devices</option>
                            </select>
                        </label>

                        <label class="search-field">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            <span class="sr-only">Search devices</span>
                            <input v-model.trim="searchQuery" type="search" placeholder="Search devices"
                                :disabled="loading || loadFailed || saving" />
                            <button v-if="searchQuery" type="button" class="clear-search" aria-label="Clear search"
                                :disabled="saving" @click="searchQuery = ''">
                                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                            </button>
                        </label>

                        <button type="button" class="button button-secondary"
                            :disabled="loading || loadFailed || saving" @click="addDevice">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            <span>Add device</span>
                        </button>
                    </div>
                </section>

                <div v-if="errorMessage && !loadFailed" class="message error" role="alert">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span>{{ errorMessage }}</span>
                </div>

                <div v-if="loading" class="state-panel" role="status" aria-live="polite">
                    <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                    <p>Loading devices…</p>
                </div>

                <div v-else-if="loadFailed" class="state-panel" role="alert">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    <p>{{ errorMessage }}</p>
                    <button type="button" class="button button-secondary" @click="loadDevices">
                        Try again
                    </button>
                </div>

                <div v-else-if="!devices.length" class="state-panel">
                    <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                    <h2>No devices yet</h2>
                    <p>Add a device to get started.</p>
                    <button type="button" class="button button-secondary" :disabled="saving" @click="addDevice">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        Add device
                    </button>
                </div>

                <div v-else-if="!filteredDevices.length" class="state-panel compact-state">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <h2>No matching devices</h2>
                    <p>Try a different search or filter.</p>
                    <button type="button" class="button button-quiet" @click="clearFilters">
                        Clear filters
                    </button>
                </div>

                <div v-else class="device-list">
                    <article v-for="item in filteredDevices" :key="item.device.id ?? item.device.tempId"
                        class="device-card" :class="{ inactive: !item.device.is_active }">
                        <div class="device-card-header">
                            <div class="device-identity">
                                <div class="device-symbol" aria-hidden="true">
                                    <img v-if="item.device.image_key && !item.device.imageFailed"
                                        :src="imageUrl(item.device.image_key)" alt="" draggable="false"
                                        @error="item.device.imageFailed = true" />
                                    <i v-else :class="iconFor(item.device.name)"></i>
                                </div>

                                <label class="field name-field">
                                    <span>Device name <em>*</em></span>
                                    <input v-model.trim="item.device.name" type="text" placeholder="e.g. Keyboard"
                                        maxlength="100" required :disabled="saving" />
                                </label>
                            </div>

                            <button type="button" class="remove-button" :disabled="saving"
                                :aria-label="`Remove ${item.device.name || `device ${item.index + 1}`}`"
                                @click="removeDevice(item.index)">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                <span>Remove</span>
                            </button>
                        </div>

                        <div class="device-fields">
                            <label class="field type-field">
                                <span>Type <em>*</em></span>
                                <select v-model="item.device.type" required :disabled="saving">
                                    <option value="" disabled>Select type</option>
                                    <option value="input">Input</option>
                                    <option value="output">Output</option>
                                </select>
                            </label>

                            <label class="field description-field">
                                <span>Description <small>Optional</small></span>
                                <input v-model.trim="item.device.description" type="text"
                                    placeholder="Add a short description" maxlength="255" :disabled="saving" />
                            </label>

                            <label class="field image-field">
                                <span>Image key <small>Optional</small></span>
                                <input v-model.trim="item.device.image_key" type="text" placeholder="e.g. keyboard.png"
                                    maxlength="255" :disabled="saving" />
                            </label>

                            <label class="field order-field">
                                <span>Order <em>*</em></span>
                                <input v-model.number="item.device.sort_order" type="number" min="0" step="1" required
                                    :disabled="saving" />
                            </label>

                            <label class="status-control">
                                <input v-model="item.device.is_active" type="checkbox" :disabled="saving"
                                    :aria-label="`Set ${item.device.name || 'device'} active`" />
                                <span>{{ item.device.is_active ? "Active" : "Inactive" }}</span>
                            </label>
                        </div>
                    </article>
                </div>

                <footer class="editor-footer">
                    <div class="footer-status">
                        <p v-if="successMessage" class="success-message" role="status">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                            <span>{{ successMessage }}</span>
                        </p>

                        <p v-else-if="hasUnsavedChanges" class="unsaved-message">
                            <i class="fa-solid fa-circle" aria-hidden="true"></i>
                            Unsaved changes
                        </p>

                        <p v-else class="footer-hint">
                            Changes are applied when you save.
                        </p>
                    </div>

                    <div class="footer-actions">
                        <button type="button" class="button button-quiet" :disabled="saving" @click="goBack">
                            Cancel
                        </button>

                        <button type="submit" class="button button-primary"
                            :disabled="saving || loading || loadFailed || !hasUnsavedChanges">
                            <i :class="saving
                                ? 'fa-solid fa-spinner fa-spin'
                                : 'fa-solid fa-floppy-disk'" aria-hidden="true"></i>
                            {{ saving ? "Saving…" : "Save changes" }}
                        </button>
                    </div>
                </footer>
            </form>
        </section>

        <a class="up-button" href="#page-top" aria-label="Back to top" title="Back to top">
            <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
        </a>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { RouterLink, useRoute, useRouter } from "vue-router"
import api from "@/bootstrap"

const router = useRouter()
const route = useRoute()

const devices = ref([])
const loading = ref(false)
const saving = ref(false)
const loadFailed = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const searchQuery = ref("")
const typeFilter = ref("all")
const initialSnapshot = ref("")

let nextTempId = 1
let loadRequestId = 0

const assessmentId = computed(() => String(route.params.id ?? ""))

const assessmentListLink = computed(() => {
    const cocId = route.query.coc

    return cocId
        ? `/admin/assessments-list/${encodeURIComponent(String(cocId))}`
        : "/admin"
})

const activeCount = computed(
    () => devices.value.filter((device) => Boolean(device.is_active)).length
)

const inputCount = computed(
    () => devices.value.filter((device) => device.type === "input").length
)

const outputCount = computed(
    () => devices.value.filter((device) => device.type === "output").length
)

const filteredDevices = computed(() => {
    const query = searchQuery.value.toLowerCase()

    return devices.value
        .map((device, index) => ({ device, index }))
        .filter(({ device }) => {
            const matchesType =
                typeFilter.value === "all" || device.type === typeFilter.value

            const matchesSearch =
                !query ||
                [
                    device.name,
                    device.description,
                    device.type,
                    device.image_key,
                ].some((value) => String(value || "").toLowerCase().includes(query))

            return matchesType && matchesSearch
        })
})

const hasUnsavedChanges = computed(() => {
    if (!initialSnapshot.value) return false
    return JSON.stringify(devices.value) !== initialSnapshot.value
})

const DEVICE_ICONS = {
    keyboard: "fa-solid fa-keyboard",
    mouse: "fa-solid fa-computer-mouse",
    microphone: "fa-solid fa-microphone",
    webcam: "fa-solid fa-video",
    scanner: "fa-solid fa-file-image",
    joystick: "fa-solid fa-gamepad",
    monitor: "fa-solid fa-desktop",
    speaker: "fa-solid fa-volume-high",
    printer: "fa-solid fa-print",
    headphones: "fa-solid fa-headphones",
    projector: "fa-solid fa-photo-film",
    plotter: "fa-solid fa-ruler-combined",
}

function iconFor(name) {
    return DEVICE_ICONS[String(name || "").trim().toLowerCase()]
        || "fa-solid fa-microchip"
}

function imageUrl(imageKey) {
    if (!imageKey) return ""

    // Treat image keys as filenames/keys, not arbitrary external URLs.
    const safeKey = String(imageKey).split("/").pop()
    return `/images/input_output/${encodeURIComponent(safeKey)}`
}

function errorText(error, fallback) {
    const status = error.response?.status
    const body = error.response?.data

    if (!error.response) {
        return "Cannot reach the server. Check your connection and try again."
    }

    if (status === 401 || status === 419) {
        return "Your session has expired. Please sign in again."
    }

    if (status === 403) {
        return "You are not authorized to manage this assessment."
    }

    if (status === 404) {
        return "This assessment was not found or is not an Input & Output assessment."
    }

    if (status === 422) {
        const validationMessage = Object.values(body?.errors || {}).flat()[0]
        return validationMessage || body?.message || "Please check the submitted information."
    }

    if (status >= 500) {
        return "The server could not process the request. Please try again."
    }

    return body?.message || error.message || fallback
}

function deviceSnapshot(list) {
    return JSON.stringify(
        list.map((device) => ({
            id: device.id ?? null,
            name: device.name ?? "",
            type: device.type ?? "",
            image_key: device.image_key ?? "",
            description: device.description ?? "",
            is_active: Boolean(device.is_active),
            sort_order: Number(device.sort_order ?? 0),
        }))
    )
}

function normalizeDevice(device) {
    return {
        ...device,
        is_active: Boolean(device.is_active),
        sort_order: Number(device.sort_order ?? 0),
        imageFailed: false,
    }
}

function addDevice() {
    devices.value.push({
        tempId: `new-${nextTempId++}`,
        name: "",
        type: typeFilter.value === "output" ? "output" : "input",
        image_key: "",
        description: "",
        is_active: true,
        sort_order: devices.value.length + 1,
        imageFailed: false,
    })

    errorMessage.value = ""
    successMessage.value = ""
    searchQuery.value = ""
    typeFilter.value = "all"

    requestAnimationFrame(() => {
        const newCard = document.querySelector(".device-card:last-child")
        newCard?.scrollIntoView({ behavior: "smooth", block: "center" })
        newCard?.querySelector('input[type="text"]')?.focus()
    })
}

function removeDevice(index) {
    const device = devices.value[index]
    const name = device?.name?.trim()

    const message = name
        ? `Remove "${name}" from this device bank?`
        : "Remove this device from the device bank?"

    if (!window.confirm(message)) return

    devices.value.splice(index, 1)
    successMessage.value = ""
    errorMessage.value = ""
}

function clearFilters() {
    searchQuery.value = ""
    typeFilter.value = "all"
}

async function loadDevices() {
    const requestId = ++loadRequestId

    loading.value = true
    loadFailed.value = false
    errorMessage.value = ""
    successMessage.value = ""

    try {
        if (!/^\d+$/.test(assessmentId.value)) {
            throw new Error("The assessment ID in the page URL is invalid.")
        }

        const { data: body } = await api.get(
            `/api/admin/assessment/input-output/edit/${encodeURIComponent(assessmentId.value)}`
        )

        if (requestId !== loadRequestId) return

        if (!body?.success || !Array.isArray(body.data)) {
            throw new Error("The server returned an unexpected response.")
        }

        devices.value = body.data.map(normalizeDevice)
        initialSnapshot.value = deviceSnapshot(devices.value)
    } catch (error) {
        if (requestId !== loadRequestId) return

        loadFailed.value = true
        errorMessage.value = errorText(error, "Could not load the device list.")
    } finally {
        if (requestId === loadRequestId) {
            loading.value = false
        }
    }
}

async function saveChanges() {
    if (saving.value || loading.value || loadFailed.value) return

    errorMessage.value = ""
    successMessage.value = ""

    if (!devices.value.length) {
        errorMessage.value = "Add at least one device before saving."
        return
    }

    const invalidIndex = devices.value.findIndex((device) => {
        const sortOrder = Number(device.sort_order)

        return (
            !device.name?.trim()
            || !["input", "output"].includes(device.type)
            || !Number.isInteger(sortOrder)
            || sortOrder < 0
        )
    })

    if (invalidIndex !== -1) {
        errorMessage.value =
            `Device ${invalidIndex + 1} needs a name, a valid type, and a non-negative whole-number order.`

        requestAnimationFrame(() => {
            document.querySelectorAll(".device-card")[invalidIndex]
                ?.scrollIntoView({ behavior: "smooth", block: "center" })
        })
        return
    }

    saving.value = true

    try {
        const payload = {
            devices: devices.value.map((device) => ({
                id: device.id ?? null,
                name: device.name.trim(),
                type: device.type,
                image_key: device.image_key?.trim() || null,
                description: device.description?.trim() || null,
                sort_order: Number(device.sort_order),
                is_active: Boolean(device.is_active),
            })),
        }

        const { data: body } = await api.put(
            `/api/admin/assessment/input-output/update/${encodeURIComponent(assessmentId.value)}`,
            payload
        )

        if (!body?.success || !Array.isArray(body.data)) {
            throw new Error("The server returned an unexpected response.")
        }

        devices.value = body.data.map(normalizeDevice)
        initialSnapshot.value = deviceSnapshot(devices.value)
        successMessage.value = body.message || "Device bank updated successfully."
    } catch (error) {
        errorMessage.value = errorText(error, "Could not save changes. Please try again.")
    } finally {
        saving.value = false
    }
}

function goBack() {
    if (saving.value) return

    if (hasUnsavedChanges.value && !window.confirm("Discard your unsaved changes?")) {
        return
    }

    router.push(assessmentListLink.value)
}

onMounted(loadDevices)
</script>

<style scoped>
.device-editor {
    --text: #242424;
    --muted: #737373;
    --line: #e5e5e5;
    --surface: #fff;
    --surface-soft: #f7f7f7;
    --accent: #343434;
    --accent-hover: #171717;
    --danger: #a33b35;
    --success: #28734e;
    --focus: #4b78df;

    min-height: 100%;
    padding: clamp(8px, 2vw, 22px);
    color: var(--text);
    font: 13px/1.45 Inter, ui-sans-serif, system-ui, -apple-system,
        BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.device-editor *,
.device-editor *::before,
.device-editor *::after {
    box-sizing: border-box;
}

.editor-card {
    width: min(100%, 1040px);
    margin: 0 auto;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--surface);
    box-shadow: 0 8px 28px rgb(15 23 42 / 4%);
}

.editor-header,
.toolbar,
.editor-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px clamp(12px, 2vw, 20px);
}

.editor-header,
.toolbar {
    border-bottom: 1px solid var(--line);
}

.header-copy {
    min-width: 0;
}

.breadcrumbs {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 7px;
    margin-bottom: 12px;
    color: var(--muted);
    font-size: 11px;
}

.breadcrumbs a {
    color: inherit;
    text-decoration: none;
}

.breadcrumbs a:hover {
    color: var(--text);
    text-decoration: underline;
    text-underline-offset: 3px;
}

.breadcrumbs [aria-current="page"] {
    color: #3f3f3f;
    font-weight: 600;
}

.eyebrow {
    margin: 0 0 3px;
    color: var(--muted);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

h1,
h2,
p {
    margin-top: 0;
}

h1 {
    margin-bottom: 3px;
    font-size: clamp(17px, 2.2vw, 22px);
    line-height: 1.25;
    letter-spacing: -0.02em;
}

h2 {
    margin: 0 0 2px;
    font-size: 14px;
}

.subtitle,
.toolbar-copy>p,
.footer-hint,
.unsaved-message {
    margin: 0;
    color: var(--muted);
    font-size: 11px;
}

.separator {
    margin: 0 3px;
    color: #aaa;
}

.button,
.remove-button {
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 11px;
    border: 1px solid var(--line);
    border-radius: 7px;
    background: var(--surface);
    color: var(--text);
    font: inherit;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition:
        background-color 160ms ease,
        border-color 160ms ease,
        color 160ms ease,
        box-shadow 160ms ease,
        transform 160ms ease;
}

.button:hover:not(:disabled) {
    background: var(--surface-soft);
}

.button:active:not(:disabled),
.remove-button:active:not(:disabled) {
    transform: translateY(1px);
}

.button:disabled,
.remove-button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.button-primary {
    border-color: var(--accent);
    background: var(--accent);
    color: #fff;
}

.button-primary:hover:not(:disabled) {
    border-color: var(--accent-hover);
    background: var(--accent-hover);
}

.button-secondary,
.button-quiet {
    background: #fff;
}

.back-button {
    flex: 0 0 auto;
}

.toolbar {
    background: var(--surface-soft);
}

.toolbar-copy {
    min-width: 0;
}

.toolbar-actions {
    display: flex;
    align-items: center;
    gap: 7px;
}

.filter-field,
.search-field {
    height: 34px;
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 0 9px;
    border: 1px solid var(--line);
    border-radius: 7px;
    background: #fff;
    color: var(--muted);
    transition: border-color 160ms ease, box-shadow 160ms ease;
}

.filter-field select,
.search-field input {
    min-width: 0;
    border: 0;
    outline: 0;
    background: transparent;
    color: var(--text);
    font: inherit;
    font-size: 12px;
}

.filter-field select {
    max-width: 125px;
    cursor: pointer;
}

.search-field {
    width: clamp(135px, 20vw, 190px);
}

.search-field input {
    width: 100%;
}

.search-field input::-webkit-search-cancel-button {
    display: none;
}

.clear-search {
    display: grid;
    place-items: center;
    padding: 3px;
    border: 0;
    border-radius: 4px;
    background: transparent;
    color: var(--muted);
    cursor: pointer;
}

.clear-search:hover:not(:disabled) {
    background: var(--surface-soft);
    color: var(--text);
}

.message {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin: 12px 16px 0;
    padding: 10px 11px;
    border: 1px solid #ead1cf;
    border-radius: 7px;
    background: #fff8f7;
    color: var(--danger);
    font-size: 12px;
}

.device-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 390px), 1fr));
    align-items: start;
    gap: 10px;
    padding: 14px clamp(12px, 2vw, 20px);
}

.device-card {
    min-width: 0;
    padding: 12px;
    border: 1px solid var(--line);
    border-radius: 9px;
    background: var(--surface);
    transition: border-color 160ms ease, background-color 160ms ease;
}

.device-card:focus-within {
    border-color: #c7c7c7;
}

.device-card.inactive {
    background: #fafafa;
}

.device-card-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 10px;
}

.device-identity {
    display: flex;
    min-width: 0;
    flex: 1;
    align-items: center;
    gap: 9px;
}

.device-symbol {
    width: 36px;
    height: 36px;
    flex: 0 0 36px;
    display: grid;
    place-items: center;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: var(--surface-soft);
    color: #555;
    font-size: 14px;
}

.device-symbol img {
    width: 100%;
    height: 100%;
    padding: 4px;
    object-fit: contain;
    user-select: none;
    -webkit-user-drag: none;
}

.field {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 4px;
}

.field>span {
    color: #555;
    font-size: 10px;
    font-weight: 650;
}

.field em {
    color: var(--danger);
    font-style: normal;
}

.field small {
    color: var(--muted);
    font-weight: 400;
}

.field input,
.field select {
    width: 100%;
    min-width: 0;
    height: 34px;
    padding: 0 9px;
    border: 1px solid #dedede;
    border-radius: 6px;
    outline: none;
    background: #fff;
    color: var(--text);
    font: inherit;
    font-size: 12px;
    transition: border-color 160ms ease, box-shadow 160ms ease;
}

.field input:disabled,
.field select:disabled {
    background: #f7f7f7;
    color: var(--muted);
    cursor: not-allowed;
}

.name-field {
    flex: 1;
}

.device-fields {
    display: grid;
    grid-template-columns: minmax(86px, 0.7fr) minmax(130px, 1.3fr) minmax(110px, 1fr) 62px auto;
    align-items: end;
    gap: 8px;
}

.status-control {
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #555;
    font-size: 11px;
    white-space: nowrap;
    cursor: pointer;
}

.status-control input {
    width: 15px;
    height: 15px;
    margin: 0;
    accent-color: #444;
}

.remove-button {
    min-height: 32px;
    padding: 0 9px;
    color: var(--danger);
}

.remove-button:hover:not(:disabled) {
    border-color: #ead1cf;
    background: #fff8f7;
}

.state-panel {
    display: grid;
    justify-items: center;
    gap: 9px;
    padding: 38px 16px;
    color: var(--muted);
    text-align: center;
}

.state-panel>i {
    font-size: 21px;
}

.state-panel h2 {
    margin: 0;
    color: var(--text);
    font-size: 14px;
}

.state-panel p {
    margin: 0;
    font-size: 12px;
}

.compact-state {
    padding: 28px 16px;
}

.editor-footer {
    position: sticky;
    bottom: 0;
    z-index: 2;
    min-height: 62px;
    border-top: 1px solid var(--line);
    background: rgb(255 255 255 / 96%);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.footer-status {
    min-width: 0;
}

.success-message,
.unsaved-message {
    display: flex;
    align-items: center;
    gap: 7px;
}

.success-message {
    margin: 0;
    color: var(--success);
    font-size: 11px;
    font-weight: 600;
}

.unsaved-message {
    color: #8a5a12;
}

.unsaved-message i {
    font-size: 7px;
}

.footer-actions {
    display: flex;
    flex: 0 0 auto;
    gap: 7px;
}

.up-button {
    position: fixed;
    right: 20px;
    bottom: 20px;
    z-index: 3;
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border: 1px solid rgb(255 255 255 / 20%);
    border-radius: 13px;
    background: rgb(20 20 20 / 88%);
    box-shadow: 0 6px 20px rgb(0 0 0 / 15%);
    color: #fff;
    text-decoration: none;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    transition: transform 160ms ease, background-color 160ms ease;
}

.up-button:hover {
    transform: translateY(-2px);
    background: #000;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    clip-path: inset(50%);
    border: 0;
}

.device-editor button:focus-visible,
.device-editor a:focus-visible,
.device-editor input:focus-visible,
.device-editor select:focus-visible {
    outline: 3px solid rgb(75 120 223 / 45%);
    outline-offset: 2px;
}

.filter-field:focus-within,
.search-field:focus-within {
    border-color: var(--focus);
    box-shadow: 0 0 0 3px rgb(75 120 223 / 12%);
}

@media (max-width: 760px) {
    .device-list {
        grid-template-columns: 1fr;
    }

    .device-fields {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .status-control {
        align-self: end;
    }
}

@media (max-width: 560px) {
    .toolbar {
        align-items: stretch;
        flex-direction: column;
        gap: 9px;
    }

    .toolbar-actions {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.3fr) auto;
        width: 100%;
    }

    .filter-field,
    .search-field {
        width: 100%;
        min-width: 0;
    }

    .filter-field select {
        width: 100%;
        max-width: none;
    }
}

@media (max-width: 480px) {
    .device-editor {
        padding: 6px;
    }

    .editor-card {
        border-radius: 9px;
    }

    .editor-header,
    .toolbar,
    .editor-footer {
        padding: 11px;
    }

    .editor-header {
        align-items: flex-start;
    }

    .breadcrumbs {
        gap: 5px;
        font-size: 10px;
    }

    .back-button {
        width: 34px;
        min-width: 34px;
        padding: 0;
    }

    .back-button span {
        display: none;
    }

    .toolbar-actions {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr) auto;
        gap: 5px;
    }

    .filter-field,
    .search-field {
        gap: 5px;
        padding: 0 6px;
    }

    .filter-field select,
    .search-field input {
        font-size: 11px;
    }

    .toolbar-actions>.button {
        padding: 0 8px;
    }

    .device-list {
        gap: 8px;
        padding: 10px;
    }

    .device-card {
        padding: 10px;
    }

    .device-fields {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .description-field,
    .image-field {
        grid-column: 1 / -1;
    }

    .order-field {
        max-width: 110px;
    }

    .status-control {
        justify-self: end;
    }

    .editor-footer {
        align-items: stretch;
        flex-direction: column;
        gap: 9px;
    }

    .footer-actions {
        display: grid;
        grid-template-columns: 1fr 1.35fr;
    }

    .footer-actions .button {
        width: 100%;
    }

    .up-button {
        right: 12px;
        bottom: 12px;
        width: 38px;
        height: 38px;
    }
}

@media (max-width: 360px) {
    .toolbar-actions {
        grid-template-columns: 1fr 1fr;
    }

    .filter-field {
        grid-column: 1 / -1;
    }

    .toolbar-actions>.button {
        width: 100%;
    }

    .device-card-header {
        align-items: flex-start;
    }

    .device-symbol {
        width: 31px;
        height: 31px;
        flex-basis: 31px;
    }

    .remove-button {
        width: 32px;
        padding: 0;
    }

    .remove-button span {
        display: none;
    }
}

@media (prefers-reduced-motion: reduce) {

    .device-editor *,
    .device-editor *::before,
    .device-editor *::after {
        scroll-behavior: auto !important;
        transition-duration: 0.01ms !important;
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
    }
}
</style>