<template>
    <main class="editor">
        <header class="topbar">
            <div>
                <RouterLink to="/admin" class="back">← Admin dashboard</RouterLink>
                <h1>RJ-45 cable termination</h1>
                <p>{{ quiz?.title || "Networking practical" }}</p>
            </div>

            <div class="actions">
                <RouterLink to="/admin" class="btn secondary">Cancel</RouterLink>
                <button class="btn primary" type="button" :disabled="saving || loading || !!loadError" @click="save">
                    {{ saving ? "Saving…" : "Save" }}
                </button>
            </div>
        </header>

        <div v-if="errorMessage" class="alert error" role="alert">{{ errorMessage }}</div>
        <div v-if="successMessage" class="alert success" role="status">{{ successMessage }}</div>

        <div v-if="loading" class="state" role="status">Loading practical…</div>

        <!-- LOAD ERROR -->
        <section v-else-if="loadError" class="error-panel" role="alert">
            <h2>{{ loadError.status === 404 ? "Practical not found" : "Could not open this practical" }}</h2>
            <p>{{ loadError.message }}</p>

            <div class="actions">
                <RouterLink to="/admin" class="btn secondary">← Back to admin</RouterLink>

                <button v-if="canCreate" class="btn primary" type="button" :disabled="creating" @click="create">
                    {{ creating ? "Creating…" : "Create new practical" }}
                </button>

                <button v-else-if="canRetry" class="btn primary" type="button" @click="load">Try again</button>
            </div>
        </section>

        <form v-else class="stack" @submit.prevent="save">
            <div v-if="locked" class="notice" role="status">
                Learners have attempted this practical. The wire maps are locked. You can still edit the text and
                settings.
            </div>

            <!-- DETAILS -->
            <section class="card">
                <h2>Details</h2>

                <div class="grid two">
                    <label class="field">
                        <span>Title <em>*</em></span>
                        <input v-model.trim="form.title" maxlength="255" :disabled="saving" />
                    </label>

                    <label class="field">
                        <span>Default standard</span>
                        <select v-model="form.default_standard" :disabled="saving">
                            <option value="A">T568A</option>
                            <option value="B">T568B</option>
                        </select>
                    </label>
                </div>

                <label class="field">
                    <span>Description</span>
                    <textarea v-model.trim="form.description" rows="2" maxlength="1000" :disabled="saving"></textarea>
                </label>

                <div class="grid two">
                    <label class="field">
                        <span>Note shown under the practical</span>
                        <input v-model.trim="form.simulation_note" maxlength="500" :disabled="saving" />
                    </label>

                    <label class="field">
                        <span>Tester delay per pin (ms)</span>
                        <input v-model.number="form.test_pin_delay_ms" type="number" min="0" max="1000" step="50"
                            :disabled="saving" />
                    </label>
                </div>
            </section>

            <!-- WIRE MAPS -->
            <section class="grid two">
                <div v-for="std in STANDARDS" :key="std" class="card">
                    <div class="card-head">
                        <h2>T568{{ std }}</h2>
                        <span class="status" :class="{ bad: standardHasProblem(std) }">{{ standardStatus(std) }}</span>
                    </div>

                    <ol class="pins">
                        <li v-for="(value, index) in listFor(std)" :key="index">
                            <span class="pin-no">{{ index + 1 }}</span>
                            <span class="chip" :style="chipStyle(value)" aria-hidden="true"></span>

                            <select :value="value" :disabled="locked || saving"
                                :aria-label="`T568${std} pin ${index + 1}`"
                                @change="setPin(std, index, $event.target.value)">
                                <option value="">Choose wire</option>
                                <option v-for="wire in wires" :key="wire.id" :value="wire.id">{{ wire.name }}</option>
                            </select>
                        </li>
                    </ol>
                </div>
            </section>

            <!-- STARTING ARRANGEMENT -->
            <section class="card">
                <h2>Starting arrangement</h2>
                <p class="hint">
                    Optional. Wires already on the pins when the learner opens the practical. Empty pins start with the
                    wire in the tray.
                </p>

                <ol class="pins start">
                    <li v-for="(value, index) in form.initial_wiring" :key="index">
                        <span class="pin-no">{{ index + 1 }}</span>
                        <span class="chip" :style="chipStyle(value)" aria-hidden="true"></span>

                        <select :value="value" :disabled="saving" :aria-label="`Starting wire for pin ${index + 1}`"
                            @change="setStart(index, $event.target.value)">
                            <option value="">Empty</option>
                            <option v-for="wire in wires" :key="wire.id" :value="wire.id">{{ wire.name }}</option>
                        </select>
                    </li>
                </ol>
            </section>
        </form>
    </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import axios from "axios";

const route = useRoute();
const router = useRouter();

const STANDARDS = ["A", "B"];

// The route id is the assessment id. ?coc= is used only when creating.
const assessmentId = computed(() => String(route.params.id ?? ""));
const cocId = computed(() => (route.query.coc ? String(route.query.coc) : null));

const quiz = ref(null);
const wires = ref([]);
const loading = ref(true);
const saving = ref(false);
const creating = ref(false);
const locked = ref(false);

// { status, message } when the practical could not be loaded
const loadError = ref(null);
const errorMessage = ref("");
const successMessage = ref("");

const form = reactive({
    title: "",
    description: "",
    simulation_note: "",
    default_standard: "B",
    test_pin_delay_ms: 0,
    standard_a: Array(8).fill(""),
    standard_b: Array(8).fill(""),
    initial_wiring: Array(8).fill(""),
});

const wireById = computed(() => Object.fromEntries(wires.value.map((wire) => [wire.id, wire])));

const canRetry = computed(() => ![400, 403, 404].includes(loadError.value?.status));
const canCreate = computed(() => loadError.value?.status === 404 && !!cocId.value);

onMounted(load);
watch(assessmentId, load);

/* =========================================
   HELPERS
========================================= */

const listFor = (std) => (std === "A" ? form.standard_a : form.standard_b);

function setPin(std, index, value) {
    listFor(std)[index] = value;
}

function setStart(index, value) {
    form.initial_wiring[index] = value;
}

const chipStyle = (key) => ({ background: wireById.value[key]?.color ?? "#D3D3D3" });

function wireName(key) {
    return wireById.value[key]?.name ?? key;
}

// Wires used more than once. Empty slots are ignored.
function repeatedKeys(list) {
    const seen = new Set();
    const repeated = new Set();

    list.filter(Boolean).forEach((key) => {
        if (seen.has(key)) repeated.add(key);
        seen.add(key);
    });

    return [...repeated];
}

function standardStatus(std) {
    const list = listFor(std);

    if (list.some((value) => !value)) return "Choose a wire for every pin";
    if (repeatedKeys(list).length) return "Each wire can only be used once";

    return "Complete";
}

function standardHasProblem(std) {
    return standardStatus(std) !== "Complete";
}

function problemsFor() {
    const problems = [];

    if (!form.title.trim()) problems.push("Enter a title.");

    for (const std of STANDARDS) {
        if (standardHasProblem(std)) {
            problems.push(`T568${std}: ${standardStatus(std).toLowerCase()}.`);
        }
    }

    const repeated = repeatedKeys(form.initial_wiring);
    if (repeated.length) {
        problems.push(`Starting arrangement: ${repeated.map(wireName).join(", ")} used more than once.`);
    }

    return problems;
}

function payload() {
    return {
        title: form.title.trim(),
        description: form.description?.trim() || null,
        simulation_note: form.simulation_note?.trim() || null,
        default_standard: form.default_standard,
        test_pin_delay_ms: Number(form.test_pin_delay_ms) || 0,
        standard_a: [...form.standard_a],
        standard_b: [...form.standard_b],
        initial_wiring: form.initial_wiring.map((value) => value || null),
    };
}

/* =========================================
   ERRORS
========================================= */

function messageFor(error, fallback) {
    const status = error.response?.status;
    const errors = error.response?.data?.errors;

    if (!error.response) return "Cannot reach the server. Check your connection and try again.";
    if (status === 422 && errors) return Object.values(errors).flat().join(" ");
    if (status === 401 || status === 419) return "Your session has expired. Sign in again to continue.";
    if (status === 403) return "You don't have permission to edit this practical.";
    if (status === 404) return "This RJ-45 practical was not found. It may have been deleted.";
    if (status === 409) return error.response.data?.message || "This practical is locked.";
    if (status >= 500) return "The server could not process this request. Try again in a moment.";

    return error.response.data?.message || fallback;
}

/* =========================================
   LOAD AND CREATE
========================================= */

function applyConfig(data) {
    quiz.value = data.quiz ?? null;
    wires.value = data.wires ?? [];
    locked.value = Boolean(data.has_attempts);

    const assessment = data.assessment ?? {};
    const config = data.config ?? {};

    form.title = assessment.title ?? "";
    form.description = assessment.description ?? "";
    form.simulation_note = config.simulation_note ?? "";
    form.default_standard = config.default_standard ?? "B";
    form.test_pin_delay_ms = config.test_pin_delay_ms ?? 0;
    form.standard_a = [...(config.standard_a ?? Array(8).fill(""))];
    form.standard_b = [...(config.standard_b ?? Array(8).fill(""))];
    form.initial_wiring = Array.from({ length: 8 }, (_, index) => config.initial_wiring?.[index] ?? "");
}

async function load() {
    loading.value = true;
    loadError.value = null;
    errorMessage.value = "";

    if (!/^\d+$/.test(assessmentId.value)) {
        loadError.value = { status: 400, message: "The practical link is invalid." };
        loading.value = false;
        return;
    }

    try {
        const { data } = await axios.get(`/api/admin/assessment/rj45/edit/${assessmentId.value}`);
        applyConfig(data);
    } catch (error) {
        console.error("Failed to load RJ-45 practical:", error);
        loadError.value = {
            status: error.response?.status ?? null,
            message: messageFor(error, "Could not load this practical."),
        };
    } finally {
        loading.value = false;
    }
}

async function create() {
    if (!cocId.value || creating.value) return;

    creating.value = true;

    try {
        const { data } = await axios.post(`/api/admin/assessment/rj45/create/${cocId.value}`);

        // Changing the route id reloads this page through the watcher.
        await router.replace({ name: "admin-rj45-edit", params: { id: data.assessment_id } });
    } catch (error) {
        console.error("Failed to create RJ-45 practical:", error);
        loadError.value = {
            status: error.response?.status ?? null,
            message: messageFor(error, "Could not create the practical."),
        };
    } finally {
        creating.value = false;
    }
}

/* =========================================
   SAVE
========================================= */

async function save() {
    if (saving.value || loadError.value) return;

    errorMessage.value = "";
    successMessage.value = "";

    const problems = problemsFor();

    if (problems.length) {
        errorMessage.value = problems.join(" ");
        return;
    }

    saving.value = true;

    try {
        await axios.put(`/api/admin/assessment/rj45/update/${assessmentId.value}`, payload());

        await load();
        successMessage.value = "RJ-45 practical updated successfully.";
    } catch (error) {
        console.error("Failed to save RJ-45 practical:", error);
        errorMessage.value = messageFor(error, "Could not save this practical.");
    } finally {
        saving.value = false;
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

    width: min(1040px, calc(100% - 32px));
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

.back {
    display: inline-block;
    margin-bottom: 8px;
    color: var(--muted);
    font-size: 13px;
    text-decoration: none;
}

.topbar h1 {
    font-size: 26px;
    letter-spacing: -0.03em;
}

.topbar p {
    margin-top: 4px;
    color: var(--muted);
}

.actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* BUTTONS */

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
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
    padding: 12px 14px;
    border: 1px solid #fde68a;
    border-radius: 10px;
    background: #fffbeb;
    color: #92400e;
    font-size: 13px;
    line-height: 1.5;
}

.error-panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 40px 24px;
    border: 1px solid #fecaca;
    border-radius: 14px;
    background: #fffafa;
    text-align: center;
}

.error-panel h2 {
    font-size: 18px;
}

.error-panel p {
    max-width: 460px;
    color: var(--muted);
    line-height: 1.5;
}

/* LAYOUT */

.stack {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.grid.two {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.card {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 18px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
}

.card h2 {
    font-size: 16px;
}

.card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.hint {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.5;
}

.status {
    color: #047857;
    font-size: 12px;
    font-weight: 600;
}

.status.bad {
    color: #b45309;
}

/* FIELDS */

.field {
    display: flex;
    flex-direction: column;
    gap: 6px;
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
    padding: 9px 12px;
    border: 1px solid #d8dee9;
    border-radius: 9px;
    background: #fff;
    color: var(--ink);
    font: inherit;
    outline: none;
}

.field input,
.field select {
    min-height: 40px;
}

.field textarea {
    resize: vertical;
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
    background: var(--soft);
    color: var(--muted);
}

/* PIN LISTS */

.pins {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.pins li {
    display: grid;
    grid-template-columns: 26px 14px minmax(0, 1fr);
    align-items: center;
    gap: 10px;
}

.pin-no {
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
}

.chip {
    width: 14px;
    height: 14px;
    border: 1px solid rgba(0, 0, 0, 0.15);
    border-radius: 4px;
    background: #d3d3d3;
}

.pins select {
    width: 100%;
    min-height: 36px;
    padding: 6px 10px;
    border: 1px solid #d8dee9;
    border-radius: 8px;
    background: #fff;
    color: var(--ink);
    font: inherit;
    font-size: 13px;
}

.pins select:disabled {
    background: var(--soft);
    color: var(--muted);
}

/* RESPONSIVE */

@media (max-width: 760px) {
    .grid.two {
        grid-template-columns: minmax(0, 1fr);
    }
}

@media (max-width: 560px) {
    .editor {
        width: calc(100% - 20px);
        margin-top: 18px;
    }

    .topbar {
        align-items: stretch;
    }

    .actions {
        width: 100%;
    }

    .actions .btn {
        flex: 1;
    }
}
</style>