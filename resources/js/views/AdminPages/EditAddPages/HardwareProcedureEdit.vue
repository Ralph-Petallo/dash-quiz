<template>
    <main class="editor">
        <header class="topbar">
            <div class="title-block">
                <RouterLink to="/admin" class="back-link">← Admin dashboard</RouterLink>
                <h1>Assembly and disassembly</h1>
                <p>{{ quiz?.title || "Hardware practical" }}</p>
            </div>

            <div class="topbar-actions">
                <span v-if="!loadError" class="count-badge">{{ totalSteps }} steps</span>
                <RouterLink to="/admin/assessments-list/1" class="btn secondary">Cancel</RouterLink>
                <button class="btn primary" type="button" :disabled="saving || loading || !!loadError"
                    @click="saveAssessment">
                    {{ saving ? "Saving…" : "Save" }}
                </button>
            </div>
        </header>

        <div v-if="errorMessage" class="alert error" role="alert">{{ errorMessage }}</div>
        <div v-if="successMessage" class="alert success" role="status">{{ successMessage }}</div>

        <div v-if="loading" class="state" role="status" aria-live="polite">Loading practical…</div>

        <!-- LOAD ERROR -->
        <section v-else-if="loadError" class="error-panel" role="alert">
            <span class="error-icon" aria-hidden="true">!</span>

            <h2>{{ loadError.status === 404 ? "Practical not found" : "Could not open this practical" }}</h2>
            <p>{{ loadError.message }}</p>

            <div class="error-actions">
                <RouterLink to="/admin" class="btn secondary">← Back to admin</RouterLink>

                <button v-if="canCreate" class="btn primary" type="button" :disabled="creating"
                    @click="createAssessment">
                    {{ creating ? "Creating…" : "Create new practical" }}
                </button>

                <button v-else-if="canRetry" class="btn primary" type="button" @click="loadAssessment">
                    Try again
                </button>
            </div>

            <p v-if="loadError.status === 404 && !cocId" class="error-hint">
                Open this page from the assessment list to create a new practical.
            </p>
        </section>

        <template v-else>
            <div v-if="locked" class="notice" role="status">
                <strong>Locked for editing.</strong>
                Learners have already attempted this practical, so the steps and their order can't change.
                You can still edit the instructions.
            </div>

            <div class="phases">
                <section v-for="phase in PHASES" :key="phase" class="phase" :class="phase"
                    :aria-labelledby="`${phase}-title`">
                    <header class="phase-head">
                        <div>
                            <span class="eyebrow">{{ phase === "disassemble" ? "Phase 1" : "Phase 2" }}</span>
                            <h2 :id="`${phase}-title`">{{ PHASE_LABELS[phase] }}</h2>
                            <p class="phase-rule">{{ phaseRule(phase) }}</p>
                        </div>

                        <span class="count" :class="{ ok: isPhaseComplete(phase) }">
                            {{ form[phase].length }} / {{ totalComponents }}
                        </span>
                    </header>

                    <div class="toolbar">
                        <button class="btn ghost small" type="button" :disabled="locked || saving"
                            @click="addStep(phase)">
                            + Add step
                        </button>

                        <button v-if="phase === 'assemble'" class="btn ghost small" type="button"
                            :disabled="locked || saving || !form.disassemble.length" @click="generateAssembly">
                            Reverse disassembly
                        </button>
                    </div>

                    <p v-if="issuesFor(phase).missing.length" class="issue">
                        Missing: {{ issuesFor(phase).missing.map(componentLabel).join(", ") }}
                    </p>

                    <p v-if="issuesFor(phase).duplicates.length" class="issue">
                        Used more than once: {{ issuesFor(phase).duplicates.map(componentLabel).join(", ") }}
                    </p>

                    <ol v-if="form[phase].length" class="steps">
                        <li v-for="(step, index) in form[phase]" :key="step.key" class="step"
                            :class="{ bad: isDuplicate(phase, step) }">
                            <span class="num">{{ index + 1 }}</span>

                            <div class="step-body">
                                <select v-model="step.component_key" :disabled="locked || saving"
                                    :aria-label="`Component for step ${index + 1}`">
                                    <option value="" disabled>Choose a component</option>
                                    <option v-for="c in components" :key="c.key" :value="c.key">{{ c.label }}</option>
                                </select>

                                <span class="rule-chip">{{ actionText(phase, step) }}</span>

                                <input v-model.trim="step.instruction" type="text" maxlength="500"
                                    placeholder="Instruction shown to the learner (optional)" :disabled="saving"
                                    :aria-label="`Instruction for step ${index + 1}`" />
                            </div>

                            <div class="row-actions">
                                <button class="icon-btn" type="button" aria-label="Move up"
                                    :disabled="locked || saving || index === 0"
                                    @click="moveStep(phase, index, -1)">↑</button>

                                <button class="icon-btn" type="button" aria-label="Move down"
                                    :disabled="locked || saving || index === form[phase].length - 1"
                                    @click="moveStep(phase, index, 1)">↓</button>

                                <button class="icon-btn danger" type="button" aria-label="Remove step"
                                    :disabled="locked || saving" @click="removeStep(phase, index)">×</button>
                            </div>
                        </li>
                    </ol>

                    <p v-else class="empty-phase">No steps yet. Add a step to begin.</p>
                </section>
            </div>

            <p class="footnote">
                Learners complete disassembly first, then assembly. Each component appears once in each phase.
            </p>
        </template>
    </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "@/bootstrap";

const route = useRoute();
const router = useRouter();

const PHASES = ["disassemble", "assemble"];
const PHASE_LABELS = { disassemble: "Disassemble", assemble: "Assemble" };

// The route id is the assessment id. ?coc= is used only when creating.
const assessmentId = computed(() => String(route.params.id ?? ""));
const cocId = computed(() => (route.query.coc ? String(route.query.coc) : null));

const quiz = ref(null);
const components = ref([]);
const loading = ref(true);
const saving = ref(false);
const creating = ref(false);
const hasAttempts = ref(false);

const loadError = ref(null);
const errorMessage = ref("");
const successMessage = ref("");

// Each phase is its own ordered list. The list order is the step order.
const form = reactive({ disassemble: [], assemble: [] });

let rowKey = 0;

const locked = computed(() => hasAttempts.value);
const totalComponents = computed(() => components.value.length);
const totalSteps = computed(() => form.disassemble.length + form.assemble.length);

const canRetry = computed(() => ![400, 403, 404].includes(loadError.value?.status));
const canCreate = computed(() => loadError.value?.status === 404 && !!cocId.value);

const componentLabel = (key) => components.value.find((c) => c.key === key)?.label ?? key;

// Components used more than once, and components missing, in one phase.
function issuesFor(phase) {
    const keys = form[phase].map((step) => step.component_key).filter(Boolean);
    const counts = keys.reduce((acc, key) => ({ ...acc, [key]: (acc[key] ?? 0) + 1 }), {});

    return {
        duplicates: Object.keys(counts).filter((key) => counts[key] > 1),
        missing: components.value.map((c) => c.key).filter((key) => !counts[key]),
    };
}

function isDuplicate(phase, step) {
    return issuesFor(phase).duplicates.includes(step.component_key);
}

function isPhaseComplete(phase) {
    const issues = issuesFor(phase);
    return !issues.missing.length && !issues.duplicates.length && form[phase].length > 0;
}

const isValid = computed(() =>
    PHASES.every((phase) =>
        form[phase].length > 0 &&
        form[phase].every((step) => step.component_key) &&
        isPhaseComplete(phase)
    )
);

function phaseRule(phase) {
    return phase === "disassemble"
        ? "Learners remove each part from the case and place it in the parts table, or the cable tray for cables."
        : "Learners pick each part from the table and install it in its matching case slot.";
}

function actionText(phase, step) {
    if (!step.component_key) return "Choose a component";

    if (phase === "disassemble") {
        return step.component_key === "internal_cables"
            ? "Remove → cable tray"
            : "Remove → parts table";
    }

    return `Install into ${componentLabel(step.component_key)} slot`;
}

function newStep(phase, componentKey = "", data = {}) {
    return {
        key: ++rowKey,
        id: data.id ?? null,
        phase,
        component_key: componentKey,
        instruction: data.instruction ?? "",
    };
}

/* =========================================
   ERRORS
========================================= */

function describeError(error, fallback) {
    const status = error.response?.status ?? null;

    if (!error.response) {
        return { status: null, message: "Cannot reach the server. Check your connection and try again." };
    }

    if (status === 401 || status === 419) {
        return { status, message: "Your session has expired. Sign in again to continue." };
    }

    if (status === 403) {
        return { status, message: "You don't have permission to edit this practical. Sign in with an admin account." };
    }

    if (status === 404) {
        return { status, message: "This hardware practical was not found. It may have been deleted." };
    }

    if (status >= 500) {
        return { status, message: "The server could not process this request. Try again in a moment." };
    }

    return { status, message: error.response.data?.message || fallback };
}

// Validation errors list every problem. Other errors get a readable message.
function validationMessage(error, fallback) {
    const errors = error.response?.data?.errors;

    if (error.response?.status === 422 && errors) {
        return Object.values(errors).flat().join(" ");
    }

    return describeError(error, fallback).message;
}

function buildProblemMessage() {
    const parts = [];

    for (const phase of PHASES) {
        const issues = issuesFor(phase);

        if (!form[phase].length) {
            parts.push(`${PHASE_LABELS[phase]} needs at least one step.`);
        }

        if (form[phase].some((step) => !step.component_key)) {
            parts.push(`Choose a component for every ${PHASE_LABELS[phase].toLowerCase()} step.`);
        }

        if (issues.missing.length) {
            parts.push(`${PHASE_LABELS[phase]} is missing: ${issues.missing.map(componentLabel).join(", ")}.`);
        }

        if (issues.duplicates.length) {
            parts.push(`${PHASE_LABELS[phase]} uses a component more than once: ${issues.duplicates.map(componentLabel).join(", ")}.`);
        }
    }

    return parts.join(" ");
}

/* =========================================
   LOAD
========================================= */

async function loadAssessment() {
    loading.value = true;
    loadError.value = null;
    errorMessage.value = "";

    if (!/^\d+$/.test(assessmentId.value)) {
        loadError.value = { status: 400, message: "The practical link is invalid." };
        loading.value = false;
        return;
    }

    try {
        const { data } = await api.get(
            `/api/admin/assessment/hardware-procedure/edit/${assessmentId.value}`
        );

        console.log(data)


        //assign to objects
        quiz.value = data.quiz ?? null;
        components.value = data.components ?? [];
        hasAttempts.value = Boolean(data.has_attempts);

        const steps = data.steps ?? [];

        form.disassemble = steps
            .filter((step) => step.phase === "disassemble")
            .map((step) => newStep("disassemble", step.component_key, step));

        form.assemble = steps
            .filter((step) => step.phase === "assemble")
            .map((step) => newStep("assemble", step.component_key, step));
    } catch (error) {
        console.error("Failed to load hardware practical:", error);
        quiz.value = null;
        form.disassemble = [];
        form.assemble = [];
        loadError.value = describeError(error, "Could not load this practical.");
    } finally {
        loading.value = false;
    }
}

/* =========================================
   CREATE
========================================= */

async function createAssessment() {
    if (!cocId.value || creating.value) return;

    creating.value = true;
    errorMessage.value = "";

    try {
        const { data } = await api.post(
            `/api/admin/assessment/hardware-procedure/create/${cocId.value}`
        );

        await router.replace({
            name: "admin-hardware-edit",
            params: { id: data.assessment_id },
        });
    } catch (error) {
        console.error("Failed to create hardware practical:", error);
        loadError.value = describeError(error, "Could not create the practical.");
    } finally {
        creating.value = false;
    }
}

/* =========================================
   EDITING STEPS
========================================= */

function addStep(phase) {
    if (locked.value) return;

    const used = form[phase].map((step) => step.component_key);
    const next = components.value.find((c) => !used.includes(c.key))?.key ?? "";

    form[phase].push(newStep(phase, next));
}

function removeStep(phase, index) {
    if (locked.value) return;
    form[phase].splice(index, 1);
}

function moveStep(phase, index, direction) {
    if (locked.value) return;

    const list = form[phase];
    const target = index + direction;

    if (target < 0 || target >= list.length) return;

    [list[index], list[target]] = [list[target], list[index]];
}

// Builds the assembly as the exact reverse of disassembly.
function generateAssembly() {
    if (locked.value || !form.disassemble.length) return;

    if (form.assemble.length && !window.confirm("Replace the current assembly steps?")) {
        return;
    }

    // set automatically
    form.assemble = [...form.disassemble]
        .reverse()
        .map((step) => newStep("assemble", step.component_key));
}

/* =========================================
   SAVE
========================================= */

async function saveAssessment() {
    if (saving.value || loadError.value) return;

    errorMessage.value = "";
    successMessage.value = "";

    if (!isValid.value) {
        errorMessage.value = buildProblemMessage();
        return;
    }

    saving.value = true;

    // Action and target zone are set by the server, so only the order and components are sent.
    const steps = [...form.disassemble, ...form.assemble].map((step) => ({
        id: step.id,
        phase: step.phase,
        component_key: step.component_key,
        instruction: step.instruction?.trim() || null,
    }));

    try {
        await api.put(
            `/api/admin/assessment/hardware-procedure/update/${assessmentId.value}`,
            { steps }
        );

        await loadAssessment();
        successMessage.value = "Hardware procedure updated successfully.";
    } catch (error) {
        console.error("Failed to save hardware practical:", error);
        errorMessage.value = validationMessage(error, "Could not save this practical.");
    } finally {
        saving.value = false;
    }
}

onMounted(loadAssessment);

watch(assessmentId, loadAssessment);
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
    --ok: #047857;
    --warn: #b45309;

    width: min(1120px, calc(100% - 32px));
    margin: 28px auto 48px;
    color: var(--ink);
    font-size: 14px;
}

.editor *,
.editor *::before,
.editor *::after {
    box-sizing: border-box;
}

.editor h1,
.editor h2,
.editor p {
    margin: 0;
}

/* TOP BAR */

.topbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
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

.title-block h1 {
    font-size: 26px;
    letter-spacing: -0.03em;
}

.title-block p {
    margin-top: 4px;
    color: var(--muted);
}

.topbar-actions {
    display: flex;
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

/* BUTTONS */

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid transparent;
    border-radius: 9px;
    font: inherit;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
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
    background: #fff;
    border-color: var(--line);
    color: #334155;
}

.btn.secondary:hover:not(:disabled) {
    background: var(--soft);
}

.btn.ghost {
    background: transparent;
    color: var(--accent);
    padding: 0 8px;
}

.btn.small {
    min-height: 32px;
    padding: 0 10px;
    font-size: 13px;
}

/* ALERTS AND STATES */

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

.state {
    padding: 48px 20px;
    border: 1px dashed #d6dde7;
    border-radius: 14px;
    background: var(--soft);
    color: var(--muted);
    text-align: center;
}

.notice {
    margin-bottom: 16px;
    padding: 12px 14px;
    border: 1px solid #fde68a;
    border-radius: 10px;
    background: #fffbeb;
    color: var(--warn);
    font-size: 13px;
    line-height: 1.5;
}

/* LOAD ERROR */

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
    font-size: 18px;
    color: #334155;
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
    font-size: 12px;
    color: var(--muted);
}

/* PHASES: side by side on desktop, stacked on small screens */

.phases {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    align-items: start;
}

.phase {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 18px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
}

.phase.disassemble {
    border-top: 3px solid #b45309;
}

.phase.assemble {
    border-top: 3px solid var(--accent);
}

.phase-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.eyebrow {
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.phase-head h2 {
    margin-top: 3px;
    font-size: 18px;
    letter-spacing: -0.02em;
}

.phase-rule {
    margin-top: 4px;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.45;
}

.count {
    flex: 0 0 auto;
    padding: 3px 9px;
    border-radius: 999px;
    background: #eef2f7;
    color: #475569;
    font-size: 12px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.count.ok {
    background: #ecfdf3;
    color: var(--ok);
}

.toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.issue {
    padding: 8px 10px;
    border-radius: 8px;
    background: #fff1f0;
    color: #b42318;
    font-size: 12px;
    line-height: 1.45;
}

.empty-phase {
    padding: 22px 12px;
    border: 1px dashed #d6dde7;
    border-radius: 10px;
    color: var(--muted);
    font-size: 13px;
    text-align: center;
}

/* STEPS */

.steps {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.step {
    display: grid;
    grid-template-columns: 30px minmax(0, 1fr) auto;
    align-items: start;
    gap: 10px;
    padding: 10px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: #fff;
}

.step.bad {
    border-color: #fecaca;
    background: #fffafa;
}

.num {
    display: grid;
    width: 30px;
    height: 30px;
    place-items: center;
    border-radius: 8px;
    background: var(--soft);
    color: #334155;
    font-size: 13px;
    font-weight: 700;
}

.step-body {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.step select,
.step input {
    width: 100%;
    min-height: 36px;
    padding: 6px 10px;
    border: 1px solid #d8dee9;
    border-radius: 8px;
    background: #fff;
    color: var(--ink);
    font: inherit;
    font-size: 13px;
    outline: none;
}

.step select:focus,
.step input:focus {
    border-color: #528bff;
    box-shadow: 0 0 0 3px rgba(82, 139, 255, 0.15);
}

.step select:disabled,
.step input:disabled {
    background: var(--soft);
    color: var(--muted);
}

.rule-chip {
    align-self: flex-start;
    padding: 2px 8px;
    border-radius: 999px;
    background: var(--soft);
    color: var(--muted);
    font-size: 11px;
    font-weight: 600;
}

.row-actions {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.icon-btn {
    display: grid;
    width: 28px;
    height: 28px;
    place-items: center;
    border: 1px solid var(--line);
    border-radius: 7px;
    background: #fff;
    color: #334155;
    font: inherit;
    font-size: 13px;
    cursor: pointer;
}

.icon-btn:hover:not(:disabled) {
    background: var(--soft);
}

.icon-btn.danger {
    color: var(--danger);
    border-color: #fecaca;
}

.icon-btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

.footnote {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.5;
}

/* RESPONSIVE */

@media (max-width: 860px) {
    .phases {
        grid-template-columns: minmax(0, 1fr);
    }
}

@media (max-width: 560px) {
    .editor {
        width: calc(100% - 20px);
        margin-top: 18px;
    }

    .topbar-actions {
        width: 100%;
    }

    .topbar-actions .btn {
        flex: 1;
    }

    .title-block h1 {
        font-size: 22px;
    }

    .step {
        grid-template-columns: 26px minmax(0, 1fr);
    }

    .row-actions {
        grid-column: 1 / -1;
        flex-direction: row;
        justify-content: flex-end;
    }
}

@media (prefers-reduced-motion: reduce) {

    .editor *,
    .editor *::before,
    .editor *::after {
        transition: none !important;
        animation: none !important;
    }
}
</style>