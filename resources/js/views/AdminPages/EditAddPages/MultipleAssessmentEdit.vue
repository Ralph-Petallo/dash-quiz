<template>
    <main class="page">
        <header class="page-header">
            <div class="header-copy">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <button type="button" class="breadcrumb-link" @click="goBack">Assessments</button>
                    <span aria-hidden="true">/</span>
                    <span>Edit multiple choice</span>
                </nav>
                <h1>Edit assessment</h1>
                <p class="subtitle">Manage assessment details and questions in smaller, easier-to-edit batches.</p>
            </div>
            <div class="header-actions">
                <span class="question-total">{{ form.questions.length }} questions</span>
                <button type="button" class="button secondary" @click="goBack">Cancel</button>
            </div>
        </header>

        <div v-if="errorMessage" class="alert error" role="alert">{{ errorMessage }}</div>
        <div v-if="successMessage" class="alert success" role="status">{{ successMessage }}</div>

        <div v-if="initialLoading" class="loading-card" role="status">
            <span class="loader" aria-hidden="true"></span>
            <p>Loading assessment…</p>
        </div>

        <form v-else class="form-layout" @submit.prevent="updateQuiz">
            <details class="panel details-panel" open>
                <summary class="details-summary">
                    <span>
                        <strong>Assessment details</strong>
                        <small>Title, category, difficulty, and description</small>
                    </span>
                    <span class="summary-chevron" aria-hidden="true">⌄</span>
                </summary>
                <div class="details-grid">
                    <label class="form-field">
                        <span>COC title <b class="required">*</b></span>
                        <input v-model.trim="form.title" name="title" type="text"
                            placeholder="e.g. Motherboard fundamentals" required />
                    </label>
                    <label class="form-field">
                        <span>Category</span>
                        <input v-model.trim="form.category" name="category" type="text"
                            placeholder="e.g. Computer hardware" />
                    </label>
                    <label class="form-field">
                        <span>Difficulty</span>
                        <select v-model="form.difficulty" name="difficulty">
                            <option value="">Select difficulty</option>
                            <option value="Easy">Easy</option>
                            <option value="Medium">Medium</option>
                            <option value="Hard">Hard</option>
                        </select>
                    </label>
                    <label class="form-field full-width">
                        <span>Topic / description</span>
                        <textarea v-model.trim="form.description" name="description" rows="2"
                            placeholder="Add a short description"></textarea>
                    </label>
                </div>
            </details>

            <section class="questions-section">
                <div class="questions-top">
                    <div>
                        <p class="eyebrow">QUESTION BANK</p>
                        <h2>Questions <span class="count">{{ form.questions.length }}</span></h2>
                        <p class="section-description">Search, select, and edit questions without scrolling through the
                            entire assessment.</p>
                    </div>
                    <button type="button" class="button primary add-button" @click="addQuestion">
                        <span aria-hidden="true">＋</span> Add question
                    </button>
                </div>

                <div class="toolbar panel">
                    <label class="search-box">
                        <span class="sr-only">Search questions</span>
                        <span aria-hidden="true">⌕</span>
                        <input v-model.trim="searchQuery" type="search"
                            placeholder="Search question text or answer options…" />
                        <button v-if="searchQuery" type="button" class="clear-search" aria-label="Clear search"
                            @click="searchQuery = ''">×</button>
                    </label>
                    <div class="toolbar-actions">
                        <label class="select-all">
                            <input type="checkbox" :checked="allVisibleSelected"
                                :indeterminate="someVisibleSelected && !allVisibleSelected"
                                @change="toggleSelectVisible" />
                            <span>Select visible</span>
                        </label>
                        <button type="button" class="button danger-ghost" :disabled="selectedCount === 0"
                            @click="confirmBulkDelete('selected')">
                            Delete selected <span v-if="selectedCount">({{ selectedCount }})</span>
                        </button>
                        <button type="button" class="button danger-ghost" :disabled="form.questions.length === 0"
                            @click="confirmBulkDelete('all')">
                            Delete all
                        </button>
                    </div>
                </div>

                <div class="list-summary">
                    <span>Showing {{ filteredQuestions.length ? pageStart + 1 : 0 }}–{{ Math.min(pageStart + pageSize,
                        filteredQuestions.length) }} of {{ filteredQuestions.length }} matching</span>
                    <label class="page-size-control">
                        <span>Per page</span>
                        <select v-model.number="pageSize" @change="currentPage = 1">
                            <option :value="5">5</option>
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                        </select>
                    </label>
                </div>

                <div v-if="filteredQuestions.length === 0" class="empty-state">
                    <strong>{{ form.questions.length ? 'No matching questions' : 'No questions yet' }}</strong>
                    <p>{{ form.questions.length ?
                        'Try another search term or clear the search.' :
                        'Add your first question to start building this assessment.'
                    }}</p>
                    <button v-if="!form.questions.length" type="button" class="button primary" @click="addQuestion">＋
                        Add first question</button>
                    <button v-else type="button" class="button secondary" @click="searchQuery = ''">Clear
                        search</button>
                </div>

                <div v-else class="question-list">
                    <article v-for="item in paginatedQuestions" :key="item.question._key" class="question-card"
                        :class="{ 'is-selected': selectedIds.has(item.question._key) }">
                        <div class="question-card-header">
                            <label class="question-select" :aria-label="`Select question ${item.index + 1}`">
                                <input type="checkbox" :checked="selectedIds.has(item.question._key)"
                                    @change="toggleQuestionSelection(item.question._key, $event.target.checked)" />
                            </label>
                            <div class="question-heading">
                                <span class="question-number">Q{{ String(item.index + 1).padStart(2, '0') }}</span>
                                <span class="question-status"
                                    :class="questionComplete(item.question) ? 'complete' : 'incomplete'">
                                    {{ questionComplete(item.question) ? 'Complete' : 'Needs attention' }}
                                </span>
                            </div>
                            <button type="button" class="icon-button remove-button"
                                :aria-label="`Remove question ${item.index + 1}`" title="Remove question"
                                @click="confirmSingleDelete(item.index)">×</button>
                        </div>

                        <label class="form-field question-field">
                            <span>Question text <b class="required">*</b></span>
                            <textarea v-model.trim="item.question.text" :name="`question-${item.index}`" rows="2"
                                placeholder="Enter the question" required></textarea>
                        </label>

                        <div class="options-heading">
                            <span>Answer options</span>
                            <span class="options-hint">Choose the radio button for the correct answer</span>
                        </div>
                        <div class="options-grid">
                            <label v-for="(option, optionIndex) in letterChoices" :key="option" class="option-item"
                                :class="{ 'is-correct': item.question.correct_option === optionIndex }">
                                <input v-model="item.question.correct_option" type="radio"
                                    :name="`correct-${item.question._key}`" :value="optionIndex"
                                    :aria-label="`Mark option ${option} as correct`" />
                                <span class="option-letter">{{ option }}</span>
                                <input v-model.trim="item.question.options[optionIndex]" type="text"
                                    :aria-label="`Option ${option}`" :placeholder="`Enter option ${option}`" required />
                                <span v-if="item.question.correct_option === optionIndex"
                                    class="correct-label">Correct</span>
                            </label>
                        </div>
                    </article>
                </div>

                <nav v-if="totalPages > 1" class="pagination" aria-label="Question pages">
                    <button type="button" class="button secondary" :disabled="currentPage <= 1" @click="currentPage--">←
                        Previous</button>
                    <div class="page-indicator">
                        <span>Page</span>
                        <strong>{{ currentPage }}</strong>
                        <span>of {{ totalPages }}</span>
                    </div>
                    <button type="button" class="button secondary" :disabled="currentPage >= totalPages"
                        @click="currentPage++">Next →</button>
                </nav>
            </section>

            <footer class="save-bar">
                <div class="save-status">
                    <span class="save-dot" :class="{ busy: saving }"></span>
                    <div>
                        <strong>{{ saving ? 'Saving changes…' : 'Ready to save' }}</strong>
                        <small>{{ selectedCount ? `${selectedCount} question${selectedCount === 1 ? '' : 's'} selected`
                            : 'Review your edits, then save when ready.' }}</small>
                    </div>
                </div>
                <div class="save-actions">
                    <button type="button" class="button secondary" @click="goBack">Cancel</button>
                    <button type="submit" class="button primary save-button" :disabled="saving">
                        <span v-if="saving" class="button-loader" aria-hidden="true"></span>
                        <span v-else aria-hidden="true">✓</span>
                        {{ saving ? 'Saving…' : 'Save changes' }}
                    </button>
                </div>
            </footer>
        </form>

        <Teleport to="body">
            <div v-if="deleteDialog.open" class="modal-backdrop" @click.self="closeDeleteDialog"
                @keydown.esc="closeDeleteDialog">
                <section class="delete-modal" role="dialog" aria-modal="true" aria-labelledby="delete-title"
                    aria-describedby="delete-description">
                    <div class="modal-icon" aria-hidden="true">!</div>
                    <h2 id="delete-title">{{ deleteDialog.mode === 'single' ? 'Remove this question?' :
                        deleteDialog.mode === 'selected' ? 'Delete selected questions?' : 'Delete all questions?' }}
                    </h2>
                    <p id="delete-description">
                        <template v-if="deleteDialog.mode === 'single'">This question will be removed from the editor.
                            Save your changes to apply the update.</template>
                        <template v-else-if="deleteDialog.mode === 'selected'">You are about to remove {{ selectedCount
                        }} selected question(s) from the editor. Save your changes to apply the update.</template>
                        <template v-else>You are about to remove all {{ form.questions.length }} questions from the
                            editor. This is a destructive change. Save your changes to apply it.</template>
                    </p>
                    <div class="modal-actions">
                        <button type="button" class="button secondary" @click="closeDeleteDialog">Cancel</button>
                        <button type="button" class="button danger-solid" @click="performDelete">
                            {{ deleteDialog.mode === 'single' ? 'Remove question' : 'Delete questions' }}
                        </button>
                    </div>
                </section>
            </div>
        </Teleport>
    </main>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, reactive, ref, watch, nextTick } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "./../../../bootstrap";

const router = useRouter();
const route = useRoute();

const initialLoading = ref(true);
const saving = ref(false);
const errorMessage = ref("");
const successMessage = ref("");
const searchQuery = ref("");
const currentPage = ref(1);
const pageSize = ref(10);
const selectedIds = ref(new Set());
const deleteDialog = reactive({ open: false, mode: "", index: null });
const letterChoices = ["A", "B", "C", "D"];
let questionKeyCounter = 0;
let successTimer;

const form = ref({
    title: "",
    description: "",
    category: "",
    difficulty: "",
    questions: [],
});

const filteredQuestions = computed(() => {
    const query = searchQuery.value.toLowerCase();
    return form.value.questions
        .map((question, index) => ({ question, index }))
        .filter(({ question }) => {
            if (!query) return true;
            return [question.text, ...(question.options || [])].some(value => String(value ?? "").toLowerCase().includes(query));
        });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredQuestions.value.length / pageSize.value)));
const pageStart = computed(() => (currentPage.value - 1) * pageSize.value);
const paginatedQuestions = computed(() => filteredQuestions.value.slice(pageStart.value, pageStart.value + pageSize.value));
const selectedCount = computed(() => selectedIds.value.size);
const visibleIds = computed(() => paginatedQuestions.value.map(item => item.question._key));
const allVisibleSelected = computed(() => visibleIds.value.length > 0 && visibleIds.value.every(id => selectedIds.value.has(id)));
const someVisibleSelected = computed(() => visibleIds.value.some(id => selectedIds.value.has(id)));

watch([searchQuery, pageSize], () => { currentPage.value = 1; });
watch(totalPages, value => {
    if (currentPage.value > value) currentPage.value = value;
});

function goBack() {
    router.push("/admin/assessments-list/1");
}

function makeQuestion(question = {}) {
    return {
        id: question.id ?? null,
        _key: `question-${++questionKeyCounter}`,
        text: question.text ?? question.question_text ?? "",
        options: Array.from({ length: 4 }, (_, index) => question.options?.[index]?.option_text ?? question.options?.[index] ?? ""),
        correct_option: Number.isInteger(question.correct_option) ? question.correct_option : 0,
        image_path: question.image_path ?? null,
        newImage: question.newImage ?? null,
    };
}

function imagePathLocator(imagePath) {
    return imagePath ? `/storage/images/profiles/${imagePath}` : null;
}

async function loadQuiz() {
    initialLoading.value = true;
    errorMessage.value = "";
    try {
        const { data } = await api.get(`/api/admin/assessment/multiple-choice/edit/${route.params.id}`);
        form.value.title = data.quiz?.title ?? "";
        form.value.description = data.quiz?.description ?? "";
        form.value.category = data.quiz?.category ?? "";
        form.value.difficulty = data.quiz?.difficulty ? capitalize(data.quiz.difficulty) : "";
        form.value.questions = (data.questions ?? []).map(question => {
            const options = question.options ?? [];
            const correctIndex = options.findIndex(option => option.option_text === question.correct_answer_text);
            const normalized = {
                id: question.id,
                text: question.question_text ?? "",
                options: letterChoices.map((_, index) => options[index]?.option_text ?? ""),
                correct_option: correctIndex >= 0 ? correctIndex : 0,
                image_path: imagePathLocator(question.image_path),
                newImage: null,
            };
            return makeQuestion(normalized);
        });
    } catch (error) {
        console.error("Error fetching assessment data:", error);
        errorMessage.value = error.response?.data?.message || "Failed to load assessment data.";
    } finally {
        initialLoading.value = false;
    }
}

function capitalize(value) {
    return String(value).charAt(0).toUpperCase() + String(value).slice(1).toLowerCase();
}

function addQuestion() {
    form.value.questions.push(makeQuestion());
    currentPage.value = Math.max(1, Math.ceil(form.value.questions.length / pageSize.value));
    searchQuery.value = "";
    selectedIds.value = new Set();
    successMessage.value = "";
    errorMessage.value = "";
    nextTickFocusQuestion();
}

function nextTickFocusQuestion() {
    nextTick(() => {
        const cards = document.querySelectorAll(".question-card");
        cards[cards.length - 1]?.scrollIntoView({ behavior: "smooth", block: "start" });
    });
}

function toggleQuestionSelection(id, checked) {
    const next = new Set(selectedIds.value);
    if (checked) next.add(id);
    else next.delete(id);
    selectedIds.value = next;
}

function toggleSelectVisible(event) {
    const next = new Set(selectedIds.value);
    if (event.target.checked) visibleIds.value.forEach(id => next.add(id));
    else visibleIds.value.forEach(id => next.delete(id));
    selectedIds.value = next;
}

function confirmSingleDelete(index) {
    deleteDialog.mode = "single";
    deleteDialog.index = index;
    deleteDialog.open = true;
}

function confirmBulkDelete(mode) {
    if (mode === "selected" && selectedIds.value.size === 0) return;
    deleteDialog.mode = mode;
    deleteDialog.index = null;
    deleteDialog.open = true;
}

function closeDeleteDialog() {
    deleteDialog.open = false;
    deleteDialog.mode = "";
    deleteDialog.index = null;
}

function performDelete() {
    const mode = deleteDialog.mode;
    if (mode === "single" && deleteDialog.index !== null) {
        form.value.questions.splice(deleteDialog.index, 1);
    } else if (mode === "selected") {
        form.value.questions = form.value.questions.filter(question => !selectedIds.value.has(question._key));
    } else if (mode === "all") {
        form.value.questions = [];
    }
    selectedIds.value = new Set();
    currentPage.value = Math.min(currentPage.value, Math.max(1, Math.ceil(filteredQuestions.value.length / pageSize.value)));
    closeDeleteDialog();
    errorMessage.value = "";
    successMessage.value = "Questions removed from the editor. Save changes to apply them.";
}

function questionComplete(question) {
    return Boolean(
        question.text?.trim() &&
        question.options?.length === 4 &&
        question.options.every(option => String(option ?? "").trim()) &&
        Number.isInteger(question.correct_option) &&
        question.correct_option >= 0 &&
        question.correct_option <= 3
    );
}

function cleanupImagePreviews() {
    form.value.questions.forEach(question => {
        if (question.image_path?.startsWith("blob:")) URL.revokeObjectURL(question.image_path);
    });
}

async function updateQuiz() {
    if (saving.value) return;
    errorMessage.value = "";
    successMessage.value = "";

    if (!form.value.title.trim()) {
        errorMessage.value = "Please enter an assessment name.";
        window.scrollTo({ top: 0, behavior: "smooth" });
        return;
    }

    const incompleteIndex = form.value.questions.findIndex(question => !questionComplete(question));
    if (incompleteIndex !== -1) {
        errorMessage.value = `Question ${incompleteIndex + 1} is incomplete. Add the question text, all four options, and choose the correct answer before saving.`;
        const pageContainingQuestion = filteredQuestions.value.findIndex(item => item.index === incompleteIndex);
        if (pageContainingQuestion >= 0) currentPage.value = Math.floor(pageContainingQuestion / pageSize.value) + 1;
        window.scrollTo({ top: 0, behavior: "smooth" });
        return;
    }

    saving.value = true;
    try {
        const payload = new FormData();
        payload.append("title", form.value.title);
        payload.append("description", form.value.description);
        payload.append("category", form.value.category);
        payload.append("difficulty", form.value.difficulty.toLowerCase());

        form.value.questions.forEach((question, index) => {
            payload.append(`questions[${index}][id]`, question.id ?? "");
            payload.append(`questions[${index}][text]`, question.text);
            payload.append(`questions[${index}][correct_option]`, question.correct_option);
            question.options.forEach((option, optionIndex) => {
                payload.append(`questions[${index}][options][${optionIndex}]`, option);
            });
            if (question.newImage) payload.append(`questions[${index}][image]`, question.newImage);
        });

        payload.append("_method", "PUT");
        await api.post(`/api/admin/assessment/multiple-choice/update/${route.params.id}`, payload, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        successMessage.value = "Assessment updated successfully.";
        selectedIds.value = new Set();
        window.scrollTo({ top: 0, behavior: "smooth" });
        if (successTimer) window.clearTimeout(successTimer);
        successTimer = window.setTimeout(() => { successMessage.value = ""; }, 5000);
    } catch (error) {
        console.error("Error updating assessment:", error.response?.data || error);
        errorMessage.value = error.response?.data?.message || "Could not update the assessment.";
        window.scrollTo({ top: 0, behavior: "smooth" });
    } finally {
        saving.value = false;
    }
}

onMounted(loadQuiz);
onBeforeUnmount(() => {
    cleanupImagePreviews();
    if (successTimer) window.clearTimeout(successTimer);
});
</script>

<style scoped>
* {
    box-sizing: border-box;
}

.page {
    width: min(1120px, calc(100% - 40px));
    margin: 30px auto 48px;
    color: #171717;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 24px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    color: #858585;
    font-size: 12px;
}

.breadcrumb-link {
    border: 0;
    padding: 0;
    background: transparent;
    color: #666;
    cursor: pointer;
    font: inherit;
}

.breadcrumb-link:hover {
    color: #111;
    text-decoration: underline;
}

h1,
h2,
p {
    margin-top: 0;
}

h1 {
    margin-bottom: 7px;
    font-size: clamp(27px, 4vw, 34px);
    letter-spacing: -.04em;
}

.subtitle,
.section-description {
    margin-bottom: 0;
    color: #737373;
    font-size: 14px;
    line-height: 1.5;
}

.header-actions,
.toolbar-actions,
.save-actions {
    display: flex;
    align-items: center;
    gap: 9px;
}

.question-total,
.count {
    padding: 6px 10px;
    border: 1px solid #e5e5e5;
    border-radius: 999px;
    background: #f7f7f7;
    color: #555;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.button {
    display: inline-flex;
    min-height: 40px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 13px;
    border: 1px solid #d4d4d4;
    border-radius: 8px;
    background: #fff;
    color: #333;
    cursor: pointer;
    font: inherit;
    font-size: 13px;
    font-weight: 650;
    transition: background .15s, border-color .15s;
    white-space: nowrap;
}

.button:hover:not(:disabled) {
    background: #f5f5f5;
    border-color: #aaa;
}

.button:disabled {
    cursor: not-allowed;
    opacity: .45;
}

.button.primary {
    border-color: #171717;
    background: #171717;
    color: #fff;
}

.button.primary:hover:not(:disabled) {
    background: #333;
    border-color: #333;
}

.button.secondary {
    background: #fff;
}

.button.danger-ghost {
    border-color: #f0d0d0;
    color: #a32626;
}

.button.danger-ghost:hover:not(:disabled) {
    border-color: #e5aaaa;
    background: #fff5f5;
}

.button.danger-solid {
    border-color: #b42318;
    background: #b42318;
    color: #fff;
}

.alert {
    margin-bottom: 16px;
    padding: 12px 14px;
    border: 1px solid;
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.5;
}

.alert.error {
    border-color: #e5baba;
    background: #fff5f5;
    color: #9f2525;
}

.alert.success {
    border-color: #b8dfc4;
    background: #f1fbf4;
    color: #20733b;
}

.loading-card {
    display: flex;
    min-height: 180px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    border: 1px solid #e5e5e5;
    border-radius: 12px;
    color: #737373;
}

.loading-card p {
    margin: 0;
}

.loader,
.button-loader {
    width: 18px;
    height: 18px;
    border: 2px solid #ddd;
    border-top-color: currentColor;
    border-radius: 50%;
    animation: spin .7s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.form-layout {
    display: grid;
    gap: 24px;
}

.panel,
.question-card {
    border: 1px solid #e6e6e6;
    border-radius: 12px;
    background: #fff;
}

.details-panel {
    padding: 0 20px 20px;
}

.details-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 19px 0;
    cursor: pointer;
    list-style: none;
}

.details-summary::-webkit-details-marker {
    display: none;
}

.details-summary strong,
.details-summary small {
    display: block;
}

.details-summary strong {
    font-size: 15px;
}

.details-summary small {
    margin-top: 4px;
    color: #777;
    font-size: 12px;
    font-weight: 400;
}

.summary-chevron {
    color: #666;
    font-size: 20px;
    transition: transform .15s;
}

details:not([open]) .summary-chevron {
    transform: rotate(-90deg);
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    padding-top: 2px;
}

.form-field {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 7px;
    color: #333;
    font-size: 12px;
    font-weight: 650;
}

.form-field input,
.form-field select,
.form-field textarea,
.option-item input[type="text"],
.page-size-control select {
    width: 100%;
    min-width: 0;
    min-height: 40px;
    padding: 9px 11px;
    border: 1px solid #d8d8d8;
    border-radius: 8px;
    outline: none;
    background: #fff;
    color: #171717;
    font: inherit;
    font-size: 13px;
    font-weight: 400;
}

.form-field textarea {
    resize: vertical;
    min-height: 70px;
}

.form-field input:focus,
.form-field select:focus,
.form-field textarea:focus,
.option-item input[type="text"]:focus,
.search-box input:focus {
    border-color: #777;
    box-shadow: 0 0 0 3px rgb(0 0 0 / 6%);
}

.form-field input::placeholder,
.form-field textarea::placeholder,
.option-item input::placeholder {
    color: #a0a0a0;
}

.required {
    color: #b42318;
}

.full-width {
    grid-column: 1 / -1;
}

.questions-section {
    display: grid;
    gap: 13px;
    min-width: 0;
}

.questions-top {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
}

.eyebrow {
    margin-bottom: 5px;
    color: #858585;
    font-size: 10px;
    font-weight: 750;
    letter-spacing: .12em;
}

.questions-top h2 {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 5px;
    font-size: 21px;
    letter-spacing: -.025em;
}

.section-description {
    font-size: 12px;
}

.add-button {
    min-height: 42px;
}

.add-button span {
    font-size: 19px;
    line-height: 1;
}

.toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 11px;
}

.search-box {
    display: flex;
    min-width: 220px;
    flex: 1;
    align-items: center;
    gap: 9px;
    padding-left: 10px;
    color: #777;
}

.search-box>span {
    font-size: 20px;
}

.search-box input {
    width: 100%;
    min-width: 0;
    min-height: 36px;
    padding: 7px 8px;
    border: 1px solid transparent;
    border-radius: 7px;
    outline: none;
    background: transparent;
    font: inherit;
    font-size: 12px;
}

.clear-search {
    border: 0;
    background: transparent;
    color: #777;
    font-size: 20px;
    cursor: pointer;
}

.toolbar-actions {
    flex-wrap: wrap;
    justify-content: flex-end;
}

.select-all,
.question-select {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #555;
    font-size: 12px;
    white-space: nowrap;
    cursor: pointer;
}

input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #171717;
    cursor: pointer;
}

.list-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    color: #777;
    font-size: 12px;
}

.page-size-control {
    display: flex;
    align-items: center;
    gap: 7px;
}

.page-size-control select {
    width: auto;
    min-height: 32px;
    padding: 5px 8px;
}

.question-list {
    display: grid;
    gap: 12px;
}

.question-card {
    padding: 17px;
    scroll-margin-top: 20px;
}

.question-card.is-selected {
    border-color: #777;
    background: #fcfcfc;
}

.question-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}

.question-heading {
    display: flex;
    min-width: 0;
    flex: 1;
    align-items: center;
    gap: 9px;
}

.question-number {
    padding: 5px 8px;
    border: 1px solid #e5e5e5;
    border-radius: 6px;
    background: #f5f5f5;
    color: #333;
    font-size: 11px;
    font-weight: 750;
}

.question-status {
    font-size: 11px;
    font-weight: 650;
}

.question-status.complete {
    color: #267442;
}

.question-status.incomplete {
    color: #a26012;
}

.icon-button {
    display: grid;
    width: 32px;
    height: 32px;
    place-items: center;
    border: 1px solid #e5e5e5;
    border-radius: 7px;
    background: #fff;
    color: #777;
    cursor: pointer;
    font-size: 22px;
    line-height: 1;
}

.icon-button:hover {
    border-color: #e2b2b2;
    background: #fff5f5;
    color: #a32626;
}

.question-field {
    margin-bottom: 17px;
}

.question-field textarea {
    min-height: 58px;
}

.options-heading {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 9px;
    color: #333;
    font-size: 12px;
    font-weight: 700;
}

.options-hint {
    color: #888;
    font-size: 11px;
    font-weight: 400;
}

.options-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 9px;
}

.option-item {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 8px;
    padding: 7px;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    background: #fff;
}

.option-item.is-correct {
    border-color: #a8ceb1;
    background: #f5fbf6;
}

.option-item input[type="radio"] {
    width: 15px;
    height: 15px;
    flex: 0 0 auto;
    margin: 0;
    accent-color: #287a42;
    cursor: pointer;
}

.option-letter {
    display: grid;
    width: 24px;
    height: 24px;
    flex: 0 0 auto;
    place-items: center;
    border: 1px solid #e5e5e5;
    border-radius: 6px;
    background: #f7f7f7;
    color: #555;
    font-size: 10px;
    font-weight: 750;
}

.option-item input[type="text"] {
    min-height: 34px;
    padding: 6px 8px;
    border-color: transparent;
    background: transparent;
    font-size: 12px;
}

.correct-label {
    color: #267442;
    font-size: 10px;
    font-weight: 750;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-top: 4px;
}

.page-indicator {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #777;
    font-size: 12px;
}

.page-indicator strong {
    color: #171717;
}

.empty-state {
    display: grid;
    justify-items: center;
    gap: 9px;
    padding: 40px 20px;
    border: 1px dashed #d5d5d5;
    border-radius: 12px;
    text-align: center;
}

.empty-state strong {
    font-size: 15px;
}

.empty-state p {
    margin: 0 0 5px;
    color: #777;
    font-size: 12px;
}

.save-bar {
    position: sticky;
    z-index: 20;
    bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 12px 14px;
    border: 1px solid #dedede;
    border-radius: 12px;
    background: rgb(255 255 255 / 96%);
    box-shadow: 0 8px 28px rgb(0 0 0 / 9%);
    backdrop-filter: blur(12px);
}

.save-status {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 10px;
}

.save-status strong,
.save-status small {
    display: block;
}

.save-status strong {
    font-size: 12px;
}

.save-status small {
    margin-top: 3px;
    color: #777;
    font-size: 11px;
}

.save-dot {
    width: 9px;
    height: 9px;
    flex: 0 0 auto;
    border-radius: 50%;
    background: #777;
}

.save-dot.busy {
    background: #b7791f;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    50% {
        opacity: .4;
    }
}

.save-button {
    min-width: 145px;
}

.modal-backdrop {
    position: fixed;
    z-index: 1000;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 18px;
    background: rgb(0 0 0 / 48%);
}

.delete-modal {
    width: min(430px, 100%);
    padding: 25px;
    border: 1px solid #e5e5e5;
    border-radius: 13px;
    background: #fff;
    box-shadow: 0 20px 60px rgb(0 0 0 / 20%);
}

.modal-icon {
    display: grid;
    width: 40px;
    height: 40px;
    margin-bottom: 15px;
    place-items: center;
    border: 1px solid #f0caca;
    border-radius: 10px;
    background: #fff5f5;
    color: #b42318;
    font-size: 20px;
    font-weight: 800;
}

.delete-modal h2 {
    margin-bottom: 8px;
    font-size: 19px;
    letter-spacing: -.02em;
}

.delete-modal p {
    margin-bottom: 22px;
    color: #666;
    font-size: 13px;
    line-height: 1.6;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

@media (max-width: 760px) {
    .page {
        width: calc(100% - 24px);
        margin: 20px auto 32px;
    }

    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .header-actions {
        width: 100%;
        justify-content: space-between;
    }

    .details-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .toolbar {
        align-items: stretch;
        flex-direction: column;
    }

    .toolbar-actions {
        justify-content: flex-start;
    }

    .search-box {
        min-width: 0;
    }

    .save-bar {
        align-items: stretch;
        flex-direction: column;
    }

    .save-actions .button {
        flex: 1;
    }
}

@media (max-width: 520px) {

    .details-grid,
    .options-grid {
        grid-template-columns: 1fr;
    }

    .questions-top {
        align-items: flex-start;
        flex-direction: column;
    }

    .add-button {
        width: 100%;
    }

    .options-heading {
        align-items: flex-start;
        flex-direction: column;
        gap: 4px;
    }

    .toolbar-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    .select-all {
        grid-column: 1 / -1;
    }

    .toolbar-actions .button {
        white-space: normal;
    }

    .pagination .button {
        padding: 0 9px;
        font-size: 12px;
    }

    .save-actions {
        width: 100%;
    }

    .save-button {
        min-width: 0;
    }

    .modal-actions {
        flex-direction: column-reverse;
    }

    .modal-actions .button {
        width: 100%;
    }
}
</style>
