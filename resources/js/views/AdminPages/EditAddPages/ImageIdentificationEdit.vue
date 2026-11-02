<template>
    <main class="editor">
        <header class="topbar">
            <div class="title-block">
                <RouterLink :to="backLink" class="back-link">
                    <span aria-hidden="true">←</span> Back to assessments
                </RouterLink>
                <h1>Image identification</h1>
                <p>{{ headerTitle }}</p>
            </div>

            <div class="topbar-actions">
                <span v-if="!loadError" class="count-badge">
                    {{ form.items.length }} {{ form.items.length === 1 ? "image" : "images" }}
                </span>

                <RouterLink :to="backLink" class="btn secondary">Cancel</RouterLink>

                <button class="btn primary" type="button"
                    :disabled="saving || loading || !!loadError || !form.items.length" @click="saveAssessment">
                    {{ saving ? "Saving…" : "Save" }}
                </button>
            </div>
        </header>

        <div v-if="errorMessage" class="alert error" role="alert">
            {{ errorMessage }}
        </div>

        <div v-if="successMessage" class="alert success" role="status">
            {{ successMessage }}
        </div>

        <div v-if="loading" class="state" role="status" aria-live="polite">
            Loading assessment…
        </div>

        <section v-else-if="loadError" class="error-panel" role="alert">
            <span class="error-icon" aria-hidden="true">!</span>

            <h2>
                {{ loadError.status === 404 ? "Assessment not found" : "Could not open this assessment" }}
            </h2>

            <p>{{ loadError.message }}</p>

            <div class="error-actions">
                <RouterLink to="/admin" class="btn secondary">← Back to admin</RouterLink>

                <button v-if="canCreate" class="btn primary" type="button" :disabled="creating"
                    @click="createAssessment">
                    {{ creating ? "Creating…" : "Create new assessment" }}
                </button>

                <button v-else-if="canRetry" class="btn primary" type="button" @click="loadAssessment">
                    Try again
                </button>
            </div>

            <p v-if="loadError.status === 404 && !cocId" class="error-hint">
                To create a new assessment, open this page from the assessment list.
            </p>
        </section>

        <template v-else>
            <section class="details-card" aria-label="Assessment details">
                <div class="details-heading">
                    <div>
                        <span class="details-eyebrow">Assessment record</span>
                        <h2>Assessment details</h2>
                    </div>

                    <span class="details-badge" :class="assessmentForm.available ? 'badge-on' : 'badge-off'">
                        {{ assessmentForm.available ? "Available" : "Unavailable" }}
                    </span>
                </div>

                <div class="details-grid">
                    <label class="field full">
                        <span>Title <em>*</em></span>
                        <input v-model.trim="assessmentForm.title" type="text" maxlength="255" :disabled="saving"
                            required />
                    </label>

                    <label class="field">
                        <span>Difficulty</span>
                        <select v-model="assessmentForm.difficulty" :disabled="saving">
                            <option value="" disabled>Select difficulty</option>
                            <option value="Easy">Easy</option>
                            <option value="Medium">Medium</option>
                            <option value="Hard">Hard</option>
                        </select>
                    </label>

                    <label class="field">
                        <span>Category</span>
                        <input v-model.trim="assessmentForm.category" type="text" maxlength="100" :disabled="saving" />
                    </label>

                    <label class="field">
                        <span>Icon</span>
                        <input v-model.trim="assessmentForm.icon" type="text" maxlength="100"
                            placeholder="fa-solid fa-microchip" :disabled="saving" />
                    </label>

                    <label class="field">
                        <span>Status</span>
                        <select v-model="assessmentForm.available" :disabled="saving">
                            <option :value="true">Available</option>
                            <option :value="false">Unavailable</option>
                        </select>
                    </label>

                    <label class="field full">
                        <span>Description</span>
                        <textarea v-model.trim="assessmentForm.description" rows="3" maxlength="2000"
                            :disabled="saving"></textarea>
                        <small class="hint counter">
                            {{ (assessmentForm.description ?? "").length }} / 2000
                        </small>
                    </label>
                </div>
            </section>

            <section v-if="!form.items.length" class="empty">
                <div class="empty-icon" aria-hidden="true">＋</div>
                <h2>No images yet</h2>
                <p>Add an image and an answer description to start this assessment.</p>
                <button class="btn primary" type="button" :disabled="saving" @click="addItem">
                    Add image
                </button>
            </section>

            <form v-else class="workspace" @submit.prevent="saveAssessment">
                <aside class="list" aria-label="Images in this assessment">
                    <div class="list-head">
                        <span>Images <small>{{ form.items.length }}</small></span>
                        <button class="btn ghost small" type="button" :disabled="saving" @click="addItem">
                            + Add
                        </button>
                    </div>

                    <ol class="thumbs">
                        <li v-for="(item, index) in form.items" :key="item.key">
                            <button type="button" class="thumb" :class="{
                                active: index === activeIndex,
                                incomplete: !isComplete(item),
                            }" :aria-current="index === activeIndex ? 'true' : undefined"
                                :aria-label="`Edit ${item.item_name || `image ${index + 1}`}`"
                                @click="selectItem(index)">
                                <span class="thumb-image">
                                    <img v-if="item.preview_url" :src="item.preview_url" alt="" draggable="false" />
                                    <span v-else class="thumb-empty">No image</span>
                                </span>

                                <span class="thumb-text">
                                    <strong>{{ item.item_name || `Image ${index + 1}` }}</strong>
                                    <small>{{ isComplete(item) ? "Ready" : "Needs details" }}</small>
                                </span>
                            </button>
                        </li>
                    </ol>
                </aside>

                <section v-if="activeItem" class="detail" :aria-label="`Editing image ${activeIndex + 1}`">
                    <header class="detail-head">
                        <div>
                            <span class="step">
                                Image {{ activeIndex + 1 }} of {{ form.items.length }}
                            </span>
                            <h2>{{ activeItem.item_name || "Untitled image" }}</h2>
                        </div>

                        <button v-if="form.items.length > 1" class="btn danger small" type="button" :disabled="saving"
                            @click="removeItem(activeIndex)">
                            Remove
                        </button>
                    </header>

                    <div class="detail-body">
                        <div class="uploader">
                            <label class="upload" :class="{ filled: activeItem.preview_url }"
                                :for="`item-image-${activeItem.key}`">
                                <img v-if="activeItem.preview_url" :src="activeItem.preview_url"
                                    :alt="activeItem.item_name || 'Image preview'" draggable="false" />

                                <span v-else class="upload-empty">
                                    <span class="upload-icon" aria-hidden="true">↑</span>
                                    <strong>Choose an image</strong>
                                    <small>PNG, JPG, GIF, or WEBP · Max 10 MB</small>
                                </span>
                            </label>

                            <input :id="`item-image-${activeItem.key}`" class="sr-only" type="file"
                                accept="image/png,image/jpeg,image/gif,image/webp" :disabled="saving"
                                :aria-label="`Upload image for ${activeItem.item_name || 'this item'}`"
                                @change="handleImageChange($event, activeItem)" />

                            <div class="uploader-actions">
                                <label class="link-button" :for="`item-image-${activeItem.key}`"
                                    :class="{ disabled: saving }">
                                    {{ activeItem.preview_url ? "Replace image" : "Upload image" }}
                                </label>

                                <button v-if="activeItem.preview_url" class="link-button danger" type="button"
                                    :disabled="saving" @click="removeImage(activeItem)">
                                    Remove image
                                </button>
                            </div>
                        </div>

                        <div class="fields">
                            <label class="field">
                                <span>Item name <em>*</em></span>
                                <input v-model.trim="activeItem.item_name" type="text" maxlength="255"
                                    placeholder="e.g. Network cable" :disabled="saving" required />
                            </label>

                            <label class="field">
                                <span>Answer description <em>*</em></span>
                                <textarea v-model.trim="activeItem.description" rows="6" maxlength="2000"
                                    placeholder="Describe the purpose or use of this item." :disabled="saving"
                                    required></textarea>
                                <small class="hint counter">
                                    {{ (activeItem.description ?? "").length }} / 2000
                                </small>
                            </label>

                            <p v-if="!isComplete(activeItem)" class="hint warn">
                                Add a name, a description, and an image to finish this item.
                            </p>
                        </div>
                    </div>

                    <footer class="detail-foot">
                        <button class="btn secondary small" type="button" :disabled="activeIndex === 0" @click="prev">
                            ← Previous
                        </button>

                        <span>{{ activeIndex + 1 }} / {{ form.items.length }}</span>

                        <button class="btn secondary small" type="button"
                            :disabled="activeIndex === form.items.length - 1" @click="next">
                            Next →
                        </button>
                    </footer>
                </section>
            </form>
        </template>
    </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import axios from "axios"

const MAX_IMAGE_SIZE = 10 * 1024 * 1024
const ALLOWED_IMAGE_TYPES = new Set([
    "image/png",
    "image/jpeg",
    "image/gif",
    "image/webp",
])

const route = useRoute()
const router = useRouter()

const assessmentId = computed(() => String(route.params.id ?? ""))
const cocId = computed(() => (route.query.coc ? String(route.query.coc) : null))

const assessmentForm = reactive({
    id: null,
    coc_id: null,
    type: "",
    title: "",
    description: "",
    category: "",
    difficulty: "",
    icon: "",
    available: true,
    path: "",
    meta: {},
    created_at: null,
    updated_at: null,
})

const loading = ref(true)
const saving = ref(false)
const creating = ref(false)
const loadError = ref(null)
const errorMessage = ref("")
const successMessage = ref("")
const activeIndex = ref(0)

let itemKey = 0
let loadRequestId = 0

const form = reactive({
    items: [],
})

const activeItem = computed(() => form.items[activeIndex.value] ?? null)

const headerTitle = computed(
    () => assessmentForm.title || "Image identification assessment"
)

const backLink = computed(() =>
    assessmentForm.coc_id
        ? `/admin/assessments-list/${assessmentForm.coc_id}`
        : "/admin"
)

const canRetry = computed(() => {
    const status = loadError.value?.status
    return status !== 404 && status !== 403 && status !== 400
})

const canCreate = computed(() => loadError.value?.status === 404 && !!cocId.value)

onMounted(loadAssessment)
watch(assessmentId, loadAssessment)

onBeforeUnmount(() => {
    loadRequestId++
    form.items.forEach(revokeItemPreview)
})

function describeError(error, fallback) {
    const status = error.response?.status ?? null

    if (!error.response) {
        return {
            status: null,
            message: "Cannot reach the server. Check your connection and try again.",
        }
    }

    if (status === 401 || status === 419) {
        return {
            status,
            message: "Your session has expired. Sign in again to continue.",
        }
    }

    if (status === 403) {
        return {
            status,
            message: "You don't have permission to edit this assessment. Sign in with an admin account.",
        }
    }

    if (status === 404) {
        return {
            status,
            message: "This image identification assessment was not found. It may have been deleted.",
        }
    }

    if (status >= 500) {
        return {
            status,
            message: "The server could not process this request. Try again in a moment.",
        }
    }

    return {
        status,
        message: error.response.data?.message || error.message || fallback,
    }
}

function validationMessage(error, fallback) {
    const errors = error.response?.data?.errors

    if (error.response?.status === 422 && errors) {
        return Object.values(errors).flat().join(" ")
    }

    return describeError(error, fallback).message
}

function applyLoadedAssessment(assessment) {
    Object.assign(assessmentForm, {
        id: assessment?.id ?? null,
        coc_id: assessment?.coc_id ?? null,
        type: assessment?.type ?? "",
        title: assessment?.title ?? "",
        description: assessment?.description ?? "",
        category: assessment?.category ?? "",
        difficulty: assessment?.difficulty ?? "",
        icon: assessment?.icon ?? "",
        available: assessment?.available === undefined
            ? true
            : Boolean(assessment.available),
        path: assessment?.path ?? "",
        meta: assessment?.meta ?? {},
        created_at: assessment?.created_at ?? null,
        updated_at: assessment?.updated_at ?? null,
    })
}

async function loadAssessment() {
    const requestId = ++loadRequestId

    loading.value = true
    loadError.value = null
    errorMessage.value = ""
    successMessage.value = ""

    if (!/^\d+$/.test(assessmentId.value)) {
        loadError.value = {
            status: 400,
            message: "The assessment link is invalid.",
        }
        loading.value = false
        return
    }

    try {
        const { data } = await axios.get(
            `/api/admin/assessment/image-identification/edit/${encodeURIComponent(assessmentId.value)}`
        )

        if (requestId !== loadRequestId) return

        if (data.status !== "success") {
            throw new Error(data.message || "The server returned an unexpected response.")
        }

        form.items.forEach(revokeItemPreview)
        applyLoadedAssessment(data.assessment)

        form.items = (data.items ?? []).map(newItem)

        if (!form.items.length) {
            form.items.push(newItem())
        }

        activeIndex.value = 0
    } catch (error) {
        if (requestId !== loadRequestId) return

        console.error("Failed to load image identification assessment:", error)
        form.items.forEach(revokeItemPreview)
        form.items = []
        loadError.value = describeError(error, "Could not load this assessment.")
    } finally {
        if (requestId === loadRequestId) {
            loading.value = false
        }
    }
}

async function createAssessment() {
    if (!cocId.value || creating.value) return

    creating.value = true
    errorMessage.value = ""

    try {
        const { data } = await axios.post(
            `/api/admin/assessment/image-identification/create/${encodeURIComponent(cocId.value)}`
        )

        const newAssessmentId = data.assessment_id ?? data.assessment?.id

        if (!newAssessmentId) {
            throw new Error("The server did not return the new assessment ID.")
        }

        await router.replace({
            name: "admin-image-edit",
            params: { id: newAssessmentId },
        })
    } catch (error) {
        console.error("Failed to create image identification assessment:", error)
        loadError.value = describeError(error, "Could not create the assessment.")
    } finally {
        creating.value = false
    }
}

function newItem(item = {}) {
    const imagePath = item.image_path ?? ""
    const imageUrl = item.image_url || (imagePath ? imagePathToUrl(imagePath) : "")

    return {
        key: ++itemKey,
        id: item.id ?? null,
        assessment_id: item.assessment_id ?? null,
        item_name: item.item_name ?? "",
        description: item.description ?? "",
        sort_order: item.sort_order ?? null,
        image_path: imagePath,
        image_url: imageUrl,
        preview_url: imageUrl,
        image_file: null,
        remove_image: false,
    }
}

function imagePathToUrl(path) {
    if (!path) return ""
    if (/^https?:\/\//i.test(path) || path.startsWith("/")) return path

    return `/storage/${path.replace(/^storage\//, "")}`
}

function selectItem(index) {
    activeIndex.value = index
}

function next() {
    if (activeIndex.value < form.items.length - 1) {
        activeIndex.value++
    }
}

function prev() {
    if (activeIndex.value > 0) {
        activeIndex.value--
    }
}

function addItem() {
    form.items.push(newItem())
    activeIndex.value = form.items.length - 1
    errorMessage.value = ""
    successMessage.value = ""
}

function removeItem(index) {
    const [item] = form.items.splice(index, 1)

    if (item) {
        revokeItemPreview(item)
    }

    if (index < activeIndex.value) {
        activeIndex.value--
    }

    activeIndex.value = Math.max(
        0,
        Math.min(activeIndex.value, form.items.length - 1)
    )
}

function handleImageChange(event, item) {
    const input = event.target
    const file = input.files?.[0]

    if (!file) return

    errorMessage.value = ""

    if (!ALLOWED_IMAGE_TYPES.has(file.type)) {
        errorMessage.value = "Choose a PNG, JPG, GIF, or WEBP image."
        input.value = ""
        return
    }

    if (file.size > MAX_IMAGE_SIZE) {
        errorMessage.value = "This image is larger than 10 MB. Choose a smaller file."
        input.value = ""
        return
    }

    clearBlobPreview(item)

    item.image_file = file
    item.remove_image = false
    item.preview_url = URL.createObjectURL(file)

    // Permit selecting the same file again after a later change.
    input.value = ""
}

function removeImage(item) {
    clearBlobPreview(item)

    item.image_file = null
    item.image_url = ""
    item.preview_url = ""
    item.remove_image = true
}

function clearBlobPreview(item) {
    if (item?.preview_url?.startsWith("blob:")) {
        URL.revokeObjectURL(item.preview_url)
    }
}

function revokeItemPreview(item) {
    clearBlobPreview(item)

    if (item) {
        item.preview_url = ""
    }
}

function isComplete(item) {
    return Boolean(
        item.item_name?.trim()
        && item.description?.trim()
        && (item.image_file || (item.image_url && !item.remove_image))
    )
}

async function saveAssessment() {
    if (saving.value || loadError.value) return

    errorMessage.value = ""
    successMessage.value = ""

    if (!assessmentForm.title.trim()) {
        errorMessage.value = "Title is required."
        return
    }

    if (!form.items.length) {
        errorMessage.value = "Add at least one image before saving."
        return
    }

    const firstIncomplete = form.items.findIndex((item) => !isComplete(item))

    if (firstIncomplete !== -1) {
        activeIndex.value = firstIncomplete
        errorMessage.value =
            `Image ${firstIncomplete + 1} needs a name, a description, and an image.`
        return
    }

    saving.value = true

    const payload = new FormData()
    payload.append("_method", "PUT")
    payload.append("assessment[title]", assessmentForm.title.trim())
    payload.append("assessment[description]", (assessmentForm.description ?? "").trim())
    payload.append("assessment[category]", assessmentForm.category.trim())
    payload.append("assessment[difficulty]", assessmentForm.difficulty || "")
    payload.append("assessment[icon]", assessmentForm.icon.trim())
    payload.append("assessment[available]", assessmentForm.available ? "1" : "0")

    form.items.forEach((item, index) => {
        const prefix = `items[${index}]`

        if (item.id) {
            payload.append(`${prefix}[id]`, item.id)
        }

        payload.append(`${prefix}[item_name]`, item.item_name.trim())
        payload.append(`${prefix}[description]`, item.description.trim())
        payload.append(`${prefix}[sort_order]`, String(index + 1))

        if (item.image_file) {
            payload.append(`${prefix}[image]`, item.image_file)
        }

        if (item.remove_image) {
            payload.append(`${prefix}[remove_image]`, "1")
        }
    })

    try {
        const { data } = await axios.post(
            `/api/admin/assessment/image-identification/update/${encodeURIComponent(assessmentId.value)}`,
            payload
        )

        if (data.status && data.status !== "success") {
            throw new Error(data.message || "The server could not save the changes.")
        }

        successMessage.value =
            data.message || "Image identification assessment updated successfully."

        await loadAssessment()
    } catch (error) {
        console.error("Failed to save image identification assessment:", error)
        errorMessage.value = validationMessage(error, "Could not save this assessment.")
    } finally {
        saving.value = false
    }
}
</script>

<style scoped>
.editor {
    --ink: #0f172a;
    --muted: #64748b;
    --line: #e2e8f0;
    --soft: #f8fafc;
    --accent: #2563eb;
    --accent-dark: #1d4ed8;
    --danger: #dc2626;
    --warn: #b45309;

    width: min(1120px, calc(100% - 32px));
    margin: 28px auto 48px;
    color: var(--ink);
    font-size: 14px;
    line-height: 1.45;
}

.editor *,
.editor *::before,
.editor *::after {
    box-sizing: border-box;
}

.editor h1,
.editor h2,
.editor h3,
.editor p {
    margin: 0;
}

.btn,
.thumb,
.upload,
.field input,
.field select,
.field textarea,
.link-button {
    transition:
        background-color 160ms ease,
        border-color 160ms ease,
        box-shadow 160ms ease,
        color 160ms ease,
        transform 160ms ease;
}

.back-link {
    display: inline-block;
    margin-bottom: 8px;
    color: var(--muted);
    font-size: 13px;
    text-decoration: none;
}

.back-link:hover {
    color: var(--ink);
}

.title-block {
    min-width: 0;
}

.title-block h1 {
    font-size: clamp(20px, 4vw, 26px);
    letter-spacing: -0.03em;
}

.title-block p {
    margin-top: 4px;
    color: var(--muted);
    overflow-wrap: anywhere;
}

.topbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}

.topbar-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.count-badge {
    padding: 5px 10px;
    border-radius: 999px;
    background: #eef2f7;
    color: #475569;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 40px;
    padding: 0 14px;
    border: 1px solid transparent;
    border-radius: 9px;
    font: inherit;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    touch-action: manipulation;
}

.btn:active:not(:disabled) {
    transform: translateY(1px);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn.primary {
    background: var(--accent);
    color: #fff;
}

.btn.primary:hover:not(:disabled) {
    background: var(--accent-dark);
}

.btn.secondary {
    border-color: var(--line);
    background: #fff;
    color: #334155;
}

.btn.secondary:hover:not(:disabled) {
    background: var(--soft);
}

.btn.ghost {
    padding: 0 8px;
    background: transparent;
    color: var(--accent);
}

.btn.danger {
    border-color: #fecaca;
    background: #fff;
    color: var(--danger);
}

.btn.danger:hover:not(:disabled) {
    background: #fff1f0;
}

.btn.small {
    min-height: 34px;
    padding: 0 10px;
    font-size: 13px;
}

.alert {
    margin-bottom: 16px;
    padding: 11px 14px;
    border-radius: 9px;
    font-size: 13px;
}

.alert.error {
    background: #fff1f0;
    color: #b42318;
}

.alert.success {
    background: #ecfdf3;
    color: #067647;
}

.state,
.empty {
    padding: 48px 20px;
    border: 1px dashed #d6dde7;
    border-radius: 14px;
    background: var(--soft);
    color: var(--muted);
    text-align: center;
}

.empty-icon {
    display: grid;
    width: 44px;
    height: 44px;
    place-items: center;
    margin: 0 auto 12px;
    border-radius: 12px;
    background: #eaf1ff;
    color: var(--accent);
    font-size: 24px;
}

.empty h2 {
    margin-bottom: 6px;
    color: #334155;
    font-size: 18px;
}

.empty p {
    margin-bottom: 16px;
}

.error-panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 40px 24px;
    border: 1px solid #fecaca;
    border-radius: 14px;
    background: #fffafa;
    text-align: center;
}

.error-icon {
    display: grid;
    width: 44px;
    height: 44px;
    place-items: center;
    margin-bottom: 4px;
    border-radius: 50%;
    background: #fee2e2;
    color: var(--danger);
    font-size: 20px;
    font-weight: 700;
}

.error-panel h2 {
    color: #334155;
    font-size: 18px;
}

.error-panel p {
    max-width: 460px;
    color: var(--muted);
    line-height: 1.5;
}

.error-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px;
    margin-top: 10px;
}

.error-hint {
    color: var(--muted);
    font-size: 12px;
}

.details-card {
    min-width: 0;
    margin-bottom: 18px;
    padding: 18px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.025);
}

.details-heading {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 14px;
}

.details-eyebrow {
    display: block;
    margin-bottom: 3px;
    color: var(--muted);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
}

.details-heading h2 {
    font-size: 16px;
}

.details-badge {
    padding: 4px 8px;
    border: 1px solid var(--line);
    border-radius: 999px;
    background: var(--soft);
    color: var(--muted);
    font-size: 11px;
    white-space: nowrap;
}

.details-badge.badge-on {
    border-color: #a7f3d0;
    background: #ecfdf3;
    color: #067647;
}

.details-badge.badge-off {
    border-color: #fecaca;
    background: #fff1f0;
    color: #b42318;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}

.field.full {
    grid-column: 1 / -1;
}

.field>span {
    color: #334155;
    font-size: 13px;
    font-weight: 600;
}

.field em {
    color: var(--danger);
    font-style: normal;
}

.field input,
.field select,
.field textarea {
    width: 100%;
    min-width: 0;
    padding: 10px 12px;
    border: 1px solid #d8dee9;
    border-radius: 9px;
    background: #fff;
    color: var(--ink);
    font: inherit;
    font-size: 14px;
    outline: none;
}

.field input,
.field select {
    min-height: 42px;
}

.field textarea {
    resize: vertical;
    line-height: 1.5;
}

.field input:focus,
.field select:focus,
.field textarea:focus {
    border-color: #528bff;
    box-shadow: 0 0 0 3px rgba(82, 139, 255, 0.15);
}

.field input:disabled,
.field select:disabled,
.field textarea:disabled {
    background: #f8fafc;
    color: #64748b;
    cursor: not-allowed;
}

.hint {
    color: var(--muted);
    font-size: 12px;
}

.hint.counter {
    align-self: flex-end;
}

.hint.warn {
    padding: 9px 11px;
    border: 1px solid #fde68a;
    border-radius: 8px;
    background: #fffbeb;
    color: var(--warn);
    line-height: 1.45;
}

.workspace {
    display: grid;
    grid-template-columns: 240px minmax(0, 1fr);
    gap: 16px;
    align-items: start;
}

.list {
    position: sticky;
    top: 16px;
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 32px);
    max-height: calc(100dvh - 32px);
    padding: 12px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
}

.list-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2px 4px 10px;
    font-size: 13px;
    font-weight: 700;
}

.list-head small {
    margin-left: 4px;
    color: var(--muted);
    font-weight: 500;
}

.thumbs {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-height: 0;
    margin: 0;
    padding: 0;
    overflow-y: auto;
    list-style: none;
}

.thumb {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 6px;
    border: 1px solid transparent;
    border-radius: 10px;
    background: transparent;
    color: inherit;
    font: inherit;
    text-align: left;
    cursor: pointer;
    touch-action: manipulation;
}

.thumb:hover {
    background: var(--soft);
}

.thumb.active {
    border-color: var(--accent);
    background: #eff6ff;
}

.thumb-image {
    display: grid;
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    place-items: center;
    overflow: hidden;
    border-radius: 8px;
    background: var(--soft);
}

.thumb-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumb-empty {
    color: var(--muted);
    font-size: 10px;
}

.thumb-text {
    display: flex;
    min-width: 0;
    flex-direction: column;
}

.thumb-text strong {
    overflow: hidden;
    font-size: 13px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.thumb-text small {
    color: var(--muted);
    font-size: 11px;
}

.thumb.incomplete .thumb-text small {
    color: var(--warn);
}

.detail {
    display: flex;
    min-width: 0;
    flex-direction: column;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.025);
}

.detail-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--line);
}

.step {
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.detail-head h2 {
    margin-top: 4px;
    overflow-wrap: anywhere;
    font-size: 20px;
    letter-spacing: -0.02em;
}

.detail-body {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 20px;
    padding: 20px;
}

.uploader {
    min-width: 0;
}

.upload {
    position: relative;
    display: grid;
    aspect-ratio: 4 / 3;
    place-items: center;
    overflow: hidden;
    border: 1.5px dashed #cbd5e1;
    border-radius: 12px;
    background: var(--soft);
    cursor: pointer;
}

.upload:hover {
    border-color: var(--accent);
    background: #f5f8ff;
}

.upload.filled {
    border-style: solid;
    background: #fff;
}

.upload img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.upload-empty {
    display: grid;
    justify-items: center;
    gap: 6px;
    padding: 12px;
    color: var(--muted);
    text-align: center;
}

.upload-empty strong {
    color: #334155;
}

.upload-empty small {
    font-size: 12px;
}

.upload-icon {
    display: grid;
    width: 40px;
    height: 40px;
    place-items: center;
    border-radius: 11px;
    background: #eaf1ff;
    color: var(--accent);
    font-size: 20px;
}

.uploader-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-top: 10px;
}

.link-button {
    padding: 8px 0;
    border: 0;
    background: none;
    color: var(--accent);
    font: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.link-button:hover:not(:disabled) {
    color: var(--accent-dark);
}

.link-button.danger {
    color: var(--danger);
}

.link-button:disabled,
.link-button.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.fields {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 16px;
}

.detail-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 20px;
    border-top: 1px solid var(--line);
    border-radius: 0 0 14px 14px;
    background: var(--soft);
    color: var(--muted);
    font-size: 13px;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    margin: -1px;
    padding: 0;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
    border: 0;
}

.btn:focus-visible,
.thumb:focus-visible,
.link-button:focus-visible,
.back-link:focus-visible,
.upload:focus-within {
    outline: 3px solid rgba(37, 99, 235, 0.35);
    outline-offset: 2px;
}

.upload img,
.thumb-image img {
    -webkit-user-drag: none;
    user-select: none;
    pointer-events: none;
}

@media (max-width: 1024px) {
    .workspace {
        grid-template-columns: 200px minmax(0, 1fr);
    }
}

@media (max-width: 860px) {
    .workspace {
        grid-template-columns: minmax(0, 1fr);
    }

    .list {
        position: static;
        max-height: none;
        padding: 10px;
    }

    .thumbs {
        flex-direction: row;
        overflow-x: auto;
        overflow-y: hidden;
        padding-bottom: 4px;
        -webkit-overflow-scrolling: touch;
    }

    .thumb {
        width: auto;
        min-width: 180px;
    }

    .detail-body {
        grid-template-columns: minmax(0, 1fr);
    }

    .upload {
        aspect-ratio: 16 / 10;
    }
}

@media (max-width: 640px) {
    .editor {
        width: calc(100% - 24px);
        margin: 18px auto 40px;
    }

    .topbar {
        flex-direction: column;
        align-items: stretch;
    }

    .topbar-actions {
        display: grid;
        grid-template-columns: auto 1fr 1fr;
        align-items: stretch;
    }

    .topbar-actions .count-badge {
        justify-self: start;
        align-self: center;
    }

    .topbar-actions .btn {
        width: 100%;
    }

    .details-card {
        padding: 14px;
    }

    .details-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .field.full {
        grid-column: auto;
    }

    .detail-head,
    .detail-body,
    .detail-foot {
        padding-right: 14px;
        padding-left: 14px;
    }

    .detail-foot .btn {
        padding: 0 10px;
    }
}

@media (max-width: 420px) {
    .topbar-actions {
        grid-template-columns: 1fr 1fr;
    }

    .topbar-actions .count-badge {
        grid-column: 1 / -1;
        justify-self: start;
    }

    .detail-foot {
        gap: 8px;
        font-size: 12px;
    }

    .detail-foot .btn {
        min-height: 40px;
    }
}

@media (prefers-reduced-motion: reduce) {

    .editor *,
    .editor *::before,
    .editor *::after {
        scroll-behavior: auto !important;
        transition: none !important;
        animation: none !important;
    }
}
</style>