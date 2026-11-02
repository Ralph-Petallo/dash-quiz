<template>
    <main class="page">

        <!-- NOTICES -->
        <Transition name="notice">
            <div v-if="successMessage" class="notice notice-success" role="status">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                <span>{{ successMessage }}</span>
                <button type="button" aria-label="Dismiss" @click="successMessage = ''">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
        </Transition>

        <div v-if="errorMessage" class="notice notice-error" role="alert">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <span>{{ errorMessage }}</span>
            <button type="button" aria-label="Dismiss" @click="errorMessage = ''">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <!-- HEADER -->
        <header class="page-head">
            <div>
                <RouterLink to="/admin/manage-quizzes" class="back">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    Back to competencies
                </RouterLink>

                <p class="eyebrow">Competency {{ coc?.coc_number ? `COC ${coc.coc_number}` : '' }}</p>
                <h1>{{ coc?.title || 'Assessments' }}</h1>
                <p class="lede">
                    {{ coc?.description || 'View, edit, or delete the assessments in this competency.' }}
                </p>
            </div>

            <div class="head-actions">
                <button type="button" class="btn btn-outline" :disabled="!coc" @click="cocModalOpen = true">
                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                    Edit COC
                </button>

                <!-- <RouterLink :to="`/admin/assessment/${cocId}/create`" class="btn btn-solid">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    New assessment
                </RouterLink> -->
            </div>
        </header>

        <!-- COC SUMMARY -->
        <section v-if="coc" class="coc-summary" aria-label="Competency details">
            <div class="summary-item">
                <span class="summary-label">COC number</span>
                <strong>{{ coc.coc_number ?? '—' }}</strong>
            </div>
            <div class="summary-item">
                <span class="summary-label">Category</span>
                <strong>{{ coc.category || '—' }}</strong>
            </div>
            <div class="summary-item">
                <span class="summary-label">Difficulty</span>
                <span class="chip">{{ coc.difficulty || '—' }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Assessments</span>
                <strong>{{ assessments.length }}</strong>
            </div>
        </section>

        <!-- TOOLBAR -->
        <section class="toolbar">
            <div>
                <h2>
                    Assessments
                    <span class="count">{{ assessments.length }}</span>
                </h2>
                <p>Edit an assessment to change its questions and settings.</p>
            </div>

            <div class="toolbar-controls">
                <button type="button" class="btn btn-outline"
                    :disabled="downloadingPdf || loading || !assessments.length" @click="downloadPdf">
                    <i :class="downloadingPdf ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-file-pdf'"
                        aria-hidden="true"></i>
                    {{ downloadingPdf ? 'Preparing…' : 'PDF' }}
                </button>

                <label class="search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input v-model="search" type="search" placeholder="Search assessments"
                        aria-label="Search assessments" />
                </label>
            </div>
        </section>

        <!-- LIST -->
        <div v-if="loading" class="state" role="status">
            <span class="spinner" aria-hidden="true"></span>
            <p>Loading assessments…</p>
        </div>

        <div v-else-if="filteredAssessments.length" class="list">
            <article v-for="assessment in filteredAssessments" :key="assessment.id" class="item">
                <div class="item-icon" aria-hidden="true">
                    <i :class="assessment.icon || typeIcon(assessment.type)"></i>
                </div>

                <div class="item-copy">
                    <h3>{{ assessment.title || 'Untitled assessment' }}</h3>
                    <p v-if="assessment.description">{{ assessment.description }}</p>

                    <div class="meta">
                        <span v-if="assessment.type" class="tag">{{ formatType(assessment.type) }}</span>
                        <span v-if="assessment.questions_count != null" class="tag">
                            {{ assessment.questions_count }} {{ assessment.questions_count === 1 ? 'question' :
                                'questions' }}
                        </span>
                    </div>
                </div>

                <div class="item-actions">
                    <button v-if="canEdit(assessment)" type="button" class="btn btn-outline btn-sm"
                        :aria-label="`Edit ${assessment.title || 'assessment'}`"
                        @click="router.push(editPath(assessment))">
                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                        Edit
                    </button>

                    <span v-else class="no-edit">Editing not available</span>

                    <button type="button" class="btn btn-danger-outline btn-sm"
                        :aria-label="`Delete ${assessment.title || 'assessment'}`"
                        @click="assessmentToDelete = assessment">
                        <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                        Delete
                    </button>
                </div>
            </article>
        </div>

        <div v-else class="state">
            <span class="empty-icon" aria-hidden="true">
                <i class="fa-solid fa-file-circle-question"></i>
            </span>
            <strong>{{ search ? 'No matching assessments' : 'No assessments yet' }}</strong>
            <p>{{ search ? 'Try a different search term.' : 'Create an assessment to add it to this competency.' }}</p>
            <RouterLink v-if="!search" :to="`/admin/assessment/${cocId}/create`" class="btn btn-solid btn-sm">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                New assessment
            </RouterLink>
        </div>

        <!-- DELETE CONFIRMATION -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="assessmentToDelete" class="backdrop" @click.self="closeDelete">
                    <section class="dialog dialog-small" role="alertdialog" aria-modal="true"
                        aria-labelledby="del-title" aria-describedby="del-desc">
                        <div class="dialog-icon danger">
                            <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                        </div>

                        <h2 id="del-title">Delete assessment?</h2>
                        <p id="del-desc">
                            <strong>{{ assessmentToDelete.title || 'This assessment' }}</strong>
                            will be removed. This cannot be undone.
                        </p>

                        <div class="dialog-actions">
                            <button type="button" class="btn btn-outline" :disabled="!!deletingId" @click="closeDelete">
                                Cancel
                            </button>
                            <button type="button" class="btn btn-danger" :disabled="!!deletingId"
                                @click="confirmDelete">
                                <i :class="deletingId ? 'fa-solid fa-spinner fa-spin' : 'fa-regular fa-trash-can'"
                                    aria-hidden="true"></i>
                                {{ deletingId ? 'Deleting…' : 'Delete' }}
                            </button>
                        </div>
                    </section>
                </div>
            </Transition>
        </Teleport>

        <!-- COC EDIT -->
        <EditCocModal v-model="cocModalOpen" :coc-id="cocId" @saved="onCocSaved" />
    </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { jsPDF } from 'jspdf'
import { autoTable } from 'jspdf-autotable'
import api from '@/bootstrap'
import EditCocModal from '@/components/AdminSide/EditCocModal.vue'

const route = useRoute()
const router = useRouter()

const cocId = computed(() => route.params.id)

const coc = ref(null)
const assessments = ref([])
const loading = ref(true)
const search = ref('')
const errorMessage = ref('')
const successMessage = ref('')
const deletingId = ref(null)
const assessmentToDelete = ref(null)
const downloadingPdf = ref(false)
const cocModalOpen = ref(false)

let noticeTimer

// Each assessment type maps to its own admin edit page. Types without one are view-only.
const EDIT_ROUTES = {
    multiple_choice: (id) => `/admin/assessment/${id}/multiple_choice/edit`,
    image_identification: (id) => `/admin/assessment/${id}/image_identification/edit`,
    rj45_hands_on: (id) => `/admin/assessment/${id}/rj45_hands_on/edit`,
    hardware_procedure: (id) => `/admin/assessment/${id}/hardware_procedure/edit`,
    input_output: (id) => `/admin/assessment/${id}/input_output/edit`,
}

const canEdit = (assessment) => Boolean(EDIT_ROUTES[assessment.type])
const editPath = (assessment) => EDIT_ROUTES[assessment.type]?.(assessment.id)

const typeIcon = (type) => {
    const value = String(type ?? '').toLowerCase()

    if (value.includes('multiple_choice')) return 'fa-solid fa-list-check'
    if (value.includes('image_identification')) return 'fa-solid fa-image'
    if (value.includes('rj45_hands_on') || value.includes('network')) return 'fa-solid fa-network-wired'
    if (value.includes('input_output')) return 'fa-solid fa-arrows-up-down-left-right'
    if (value.includes('hardware') || value.includes('procedure')) return 'fa-solid fa-screwdriver-wrench'

    return 'fa-solid fa-file-lines'
}

const formatType = (type) =>
    String(type ?? '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())

const filteredAssessments = computed(() => {
    const query = search.value.trim().toLowerCase()
    if (!query) return assessments.value

    return assessments.value.filter((assessment) =>
        [assessment.title, assessment.description, assessment.type, assessment.id]
            .filter((value) => value != null)
            .some((value) => String(value).toLowerCase().includes(query))
    )
})

const flash = (message) => {
    successMessage.value = message
    errorMessage.value = ''

    clearTimeout(noticeTimer)
    noticeTimer = setTimeout(() => {
        successMessage.value = ''
    }, 3500)
}

/* =========================================================
   LOAD
========================================================= */

const loadCoc = async () => {
    try {
        const { data } = await api.get(`/api/admin/cocs/${cocId.value}`)
        coc.value = data.data
    } catch (error) {
        console.error('Failed to load COC:', error)
        coc.value = null
    }
}

const loadAssessments = async () => {
    loading.value = true
    errorMessage.value = ''

    try {
        const { data } = await api.get(`/api/admin/quizzes/assessments/${cocId.value}`)
        assessments.value = Array.isArray(data.result) ? data.result : []
    } catch (error) {
        console.error('Failed to load assessments:', error)
        errorMessage.value = error.response?.data?.message || 'Could not load assessments.'
    } finally {
        loading.value = false
    }
}

const reloadAll = () => Promise.all([loadCoc(), loadAssessments()])

/* =========================================================
   COC EDIT
========================================================= */

const onCocSaved = (updated) => {
    coc.value = { ...coc.value, ...updated }
    flash(`COC ${updated.coc_number} updated.`)
}

/* =========================================================
   DELETE ASSESSMENT
========================================================= */

const closeDelete = () => {
    if (!deletingId.value) assessmentToDelete.value = null
}

const confirmDelete = async () => {
    const target = assessmentToDelete.value
    if (!target || deletingId.value) return

    deletingId.value = target.id
    errorMessage.value = ''

    try {
        // Assessment delete. Not the COC delete endpoint.
        await api.delete(`/api/admin/quizzes/assessments/${target.id}`)

        assessments.value = assessments.value.filter((item) => item.id !== target.id)
        assessmentToDelete.value = null
        flash('Assessment deleted.')
    } catch (error) {
        assessmentToDelete.value = null
        errorMessage.value = error.response?.data?.message || 'Could not delete this assessment.'
    } finally {
        deletingId.value = null
    }
}

/* =========================================================
   PDF
========================================================= */

const downloadPdf = () => {
    if (!assessments.value.length) return

    downloadingPdf.value = true

    try {
        const pdf = new jsPDF({ unit: 'mm', format: 'a4' })
        const title = coc.value?.title || 'Assessments'

        pdf.setFont('helvetica', 'bold')
        pdf.setFontSize(16)
        pdf.text(title, 14, 18)

        pdf.setFont('helvetica', 'normal')
        pdf.setFontSize(9)
        pdf.setTextColor(105)
        pdf.text(
            `COC ${coc.value?.coc_number ?? '-'}  |  ${assessments.value.length} assessments  |  Generated ${new Date().toLocaleDateString('en-US')}`,
            14,
            25
        )

        autoTable(pdf, {
            startY: 31,
            head: [['#', 'Title', 'Type', 'Questions', 'Description']],
            body: assessments.value.map((assessment, index) => [
                index + 1,
                assessment.title || 'Untitled assessment',
                assessment.type ? formatType(assessment.type) : '-',
                assessment.questions_count ?? '-',
                assessment.description || '-',
            ]),
            styles: { fontSize: 8, cellPadding: 2.5, overflow: 'linebreak' },
            headStyles: { fillColor: [0, 0, 0], textColor: 255 },
            alternateRowStyles: { fillColor: [247, 247, 247] },
            columnStyles: {
                0: { cellWidth: 10 },
                1: { cellWidth: 48 },
                2: { cellWidth: 36 },
                3: { cellWidth: 22 },
            },
            margin: { top: 14, right: 14, bottom: 14, left: 14 },
        })

        pdf.save(`coc-${cocId.value}-assessments.pdf`)
    } catch (error) {
        console.error('PDF generation failed:', error)
        errorMessage.value = 'Could not generate the PDF.'
    } finally {
        downloadingPdf.value = false
    }
}

/* =========================================================
   LIFECYCLE
========================================================= */

const onKeydown = (event) => {
    if (event.key === 'Escape' && assessmentToDelete.value) closeDelete()
}

watch(cocId, reloadAll)

onMounted(() => {
    reloadAll()
    window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
    clearTimeout(noticeTimer)
    window.removeEventListener('keydown', onKeydown)
})
</script>

<style scoped>
.page {
    --ink: #000000;
    --white: #ffffff;
    --dim: #696969;
    --silver: #a9a9a9;
    --light: #d3d3d3;
    --line: #e6e6e6;
    --soft: #f7f7f7;
    --danger: #b42318;
    --danger-soft: #fdf1f0;

    width: min(1040px, calc(100% - 32px));
    margin: 28px auto 48px;
    color: var(--ink);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    font-size: 13px;
    line-height: 1.5;
}

.page *,
.page *::before,
.page *::after {
    box-sizing: border-box;
}

.page button,
.page input {
    font: inherit;
}

.page :focus-visible {
    outline: 2px solid var(--ink);
    outline-offset: 2px;
}

.page h1,
.page h2,
.page h3,
.page p {
    margin: 0;
}

/* BUTTONS: one height, one radius, three roles */

.btn {
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 16px;
    border: 1px solid transparent;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.btn:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.btn i {
    font-size: 12px;
}

.btn-sm {
    min-height: 34px;
    padding: 0 12px;
    font-size: 11px;
}

.btn-solid {
    border-color: var(--ink);
    background: var(--ink);
    color: var(--white);
}

.btn-solid:hover:not(:disabled) {
    background: #262626;
}

.btn-outline {
    border-color: var(--light);
    background: var(--white);
    color: var(--ink);
}

.btn-outline:hover:not(:disabled) {
    border-color: var(--ink);
}

.btn-danger {
    border-color: var(--danger);
    background: var(--danger);
    color: var(--white);
}

.btn-danger:hover:not(:disabled) {
    border-color: #911c13;
    background: #911c13;
}

.btn-danger-outline {
    border-color: #efcfcb;
    background: var(--white);
    color: var(--danger);
}

.btn-danger-outline:hover:not(:disabled) {
    border-color: var(--danger);
    background: var(--danger-soft);
}

/* NOTICES */

.notice {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: 12px;
}

.notice>span {
    flex: 1;
}

.notice button {
    border: 0;
    background: transparent;
    color: inherit;
    cursor: pointer;
}

.notice-success {
    border: 1px solid #d5e5dc;
    background: #f3faf5;
    color: #2f6b4a;
}

.notice-error {
    border: 1px solid #f0d3d0;
    background: var(--danger-soft);
    color: var(--danger);
}

/* HEADER */

.page-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 18px;
}

.back {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 12px;
    color: var(--dim);
    font-size: 12px;
    font-weight: 650;
    text-decoration: none;
}

.back:hover {
    color: var(--ink);
}

.eyebrow {
    margin-bottom: 4px;
    color: var(--dim);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.page-head h1 {
    font-size: clamp(22px, 2.6vw, 28px);
    font-weight: 800;
    letter-spacing: -0.035em;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.lede {
    max-width: 620px;
    margin-top: 6px;
    color: var(--dim);
    font-size: 12px;
    line-height: 1.6;
}

.head-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

/* COC SUMMARY */

.coc-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1px;
    margin-bottom: 18px;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--line);
}

.summary-item {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
    min-width: 0;
    padding: 13px 16px;
    background: var(--white);
}

.summary-label {
    color: var(--dim);
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.summary-item strong {
    overflow: hidden;
    max-width: 100%;
    font-size: 13px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.chip {
    padding: 3px 9px;
    border: 1px solid var(--light);
    border-radius: 999px;
    color: var(--ink);
    font-size: 11px;
    font-weight: 700;
    text-transform: capitalize;
}

/* TOOLBAR */

.toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.toolbar h2 {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.toolbar p {
    margin-top: 4px;
    color: var(--dim);
    font-size: 11px;
}

.count {
    padding: 2px 8px;
    border: 1px solid var(--light);
    border-radius: 999px;
    color: var(--dim);
    font-size: 10px;
    font-weight: 700;
}

.toolbar-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.search {
    display: flex;
    align-items: center;
    gap: 8px;
    width: min(260px, 100%);
    height: 40px;
    padding: 0 12px;
    border: 1px solid var(--light);
    border-radius: 9px;
    background: var(--white);
    color: var(--dim);
}

.search:focus-within {
    border-color: var(--ink);
}

.search input {
    width: 100%;
    min-width: 0;
    border: 0;
    outline: 0;
    background: transparent;
    color: var(--ink);
    font-size: 12px;
}

/* LIST */

.list {
    display: grid;
    gap: 10px;
}

.item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--white);
    transition: border-color 0.15s ease;
}

.item:hover {
    border-color: var(--light);
}

.item-icon {
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    display: grid;
    place-items: center;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: var(--soft);
    font-size: 14px;
}

.item-copy {
    flex: 1;
    min-width: 0;
}

.item-copy h3 {
    font-size: 14px;
    font-weight: 750;
    letter-spacing: -0.01em;
    overflow-wrap: anywhere;
}

.item-copy p {
    margin-top: 3px;
    color: var(--dim);
    font-size: 11px;
    line-height: 1.5;
    overflow-wrap: anywhere;
}

.meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
}

.tag {
    padding: 3px 8px;
    border-radius: 6px;
    background: var(--soft);
    color: var(--dim);
    font-size: 10px;
    font-weight: 650;
}

.item-actions {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 8px;
}

.no-edit {
    color: var(--silver);
    font-size: 11px;
    font-style: italic;
    white-space: nowrap;
}

/* STATES */

.state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 240px;
    padding: 24px;
    border: 1px dashed var(--light);
    border-radius: 12px;
    background: var(--soft);
    color: var(--dim);
    text-align: center;
}

.state strong {
    color: var(--ink);
    font-size: 14px;
}

.state p {
    font-size: 12px;
}

.empty-icon {
    width: 46px;
    height: 46px;
    display: grid;
    place-items: center;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--white);
    color: var(--ink);
    font-size: 18px;
}

.spinner {
    width: 26px;
    height: 26px;
    border: 3px solid var(--light);
    border-top-color: var(--ink);
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

/* DIALOG */

.backdrop {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: grid;
    place-items: center;
    padding: 16px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    color: var(--ink);
}

.dialog {
    width: min(420px, 100%);
    padding: 24px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: var(--white);
    box-shadow: 0 24px 60px rgb(0 0 0 / 22%);
    text-align: center;
}

.dialog-icon {
    width: 46px;
    height: 46px;
    display: grid;
    place-items: center;
    margin: 0 auto 14px;
    border-radius: 12px;
    font-size: 18px;
}

.dialog-icon.danger {
    border: 1px solid #efcfcb;
    background: var(--danger-soft);
    color: var(--danger);
}

.dialog h2 {
    font-size: 17px;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.dialog p {
    margin-top: 8px;
    color: var(--dim);
    font-size: 12px;
    line-height: 1.6;
}

.dialog p strong {
    color: var(--ink);
}

.dialog-actions {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
}

.fade-enter-active,
.fade-leave-active,
.notice-enter-active,
.notice-leave-active {
    transition: opacity 0.16s ease, transform 0.16s ease;
}

.fade-enter-from,
.fade-leave-to,
.notice-enter-from,
.notice-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* RESPONSIVE */

@media (max-width: 860px) {
    .coc-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .page {
        width: calc(100% - 24px);
        margin-top: 18px;
    }

    .page-head,
    .toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .head-actions .btn {
        flex: 1;
    }

    .toolbar-controls {
        width: 100%;
    }

    .toolbar-controls .btn,
    .search {
        flex: 1 1 100%;
        width: 100%;
    }

    .item {
        flex-direction: column;
        align-items: stretch;
    }

    .item-actions {
        width: 100%;
    }

    .item-actions .btn {
        flex: 1;
    }

    .no-edit {
        flex: 1;
        text-align: center;
    }

    .dialog-actions {
        flex-direction: column-reverse;
    }

    .dialog-actions .btn {
        width: 100%;
    }
}

@media (max-width: 380px) {
    .coc-summary {
        grid-template-columns: minmax(0, 1fr);
    }
}

@media (prefers-reduced-motion: reduce) {

    .page *,
    .page *::before,
    .page *::after {
        animation: none !important;
        transition: none !important;
    }
}
</style>