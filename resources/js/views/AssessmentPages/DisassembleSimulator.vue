<template>
    <div class="hardware-page">
        <div class="shell">
            <header class="topbar">
                <div>
                    <div class="eyebrow">CSS PREP <span>/</span> COC {{ quiz.coc_number || 3 }}</div>
                    <h1>Computer Assembly &amp; Disassembly</h1>
                    <p>Computer Maintenance and Troubleshooting</p>
                </div>
                <div class="progress-box">
                    <strong>{{ completedSteps }}/{{ steps.length }}</strong>
                    <span>PROCEDURE STEPS</span>
                    <div class="progress-track">
                        <i :style="{ width: `${progress}%` }"></i>
                    </div>
                </div>
            </header>

            <main class="lab-grid">
                <aside class="procedure-panel">
                    <div class="section-label">01 / PROCEDURE</div>
                    <div class="phase-pill" :class="phase">{{ phase === 'disassemble' ? 'DISASSEMBLY' : 'ASSEMBLY' }}
                    </div>
                    <h2>{{ phase === 'disassemble' ? 'Disassemble the unit' : 'Assemble the unit' }}</h2>
                    <p class="intro">Follow the required order. Only the component for the current step can be moved.
                    </p>

                    <div class="steps">
                        <div v-for="(step, index) in steps" :key="step.id" class="step"
                            :class="{ active: index === currentIndex, done: index < currentIndex, locked: index > currentIndex }">
                            <span class="step-no">{{ index < currentIndex ? '✓' : String(index + 1).padStart(2, '0')
                            }}</span>
                                    <img :src="step.image" :alt="step.text" />
                                    <div><strong>{{ step.text }}</strong><small>{{ step.phase === 'disassemble' ?
                                        'REMOVE' : 'INSTALL'
                                    }}</small></div>
                        </div>
                    </div>

                    <div v-if="activeStep" class="task-card">
                        <small>CURRENT TASK</small>
                        <h3>{{ activeStep.text }}</h3>
                        <p>{{ activeStep.instruction }}</p>
                        <b>Drag: {{ componentName(activeStep.component_key) }}</b>
                    </div>
                    <div v-else class="task-card complete-card">
                        <small>PROCEDURE COMPLETE</small>
                        <h3>Ready to submit</h3>
                        <p>The disassembly and assembly sequence is complete.</p>
                    </div>
                </aside>

                <section class="workspace">
                    <div class="workspace-head">
                        <span>&gt; C:\CSSPREP\HARDWARE\MIXED-LAB</span>
                        <strong>{{ dragging ? `DRAGGING ${componentName(dragging)}` : 'INTERACTIVE LAB' }}</strong>
                    </div>

                    <div class="bench-row">
                        <div class="zone case-zone" :class="{ target: phase === 'assemble' && activeStep }"
                            @dragover.prevent @drop="dropIntoCase">
                            <div class="zone-title"><b>PC CASE</b>
                            <span>Installed components</span>
                            </div>
                            <div class="component-grid">
                                <button v-for="component in installedComponents" :key="component.key" type="button"
                                    class="component-card"
                                    :class="{ current: isActiveComponent(component.key), disabled: !isActiveComponent(component.key) }"
                                    :draggable="phase === 'disassemble' && isActiveComponent(component.key)"
                                    @dragstart="startDrag(component.key, 'case', $event)" @dragend="stopDrag">
                                    <img :src="component.image" :alt="component.name" />
                                    <strong>{{ component.name }}</strong>
                                    <small>{{ isActiveComponent(component.key) ? 'DRAG TO PARTS TRAY' : 'INSTALLED'
                                    }}</small>
                                </button>
                                <div v-if="installedComponents.length === 0" class="empty">Case is empty. Start assembly
                                    from the tray.
                                </div>
                            </div>
                        </div>

                        <div class="transfer-arrow">⇄</div>

                        <div class="zone tray-zone" :class="{ target: phase === 'disassemble' && activeStep }"
                            @dragover.prevent @drop="dropIntoTray">
                            <div class="zone-title"><b>PARTS TRAY</b><span>Removed components</span></div>
                            <div class="component-grid">
                                <button v-for="component in trayComponents" :key="component.key" type="button"
                                    class="component-card"
                                    :class="{ current: isActiveComponent(component.key), disabled: !isActiveComponent(component.key) }"
                                    :draggable="phase === 'assemble' && isActiveComponent(component.key)"
                                    @dragstart="startDrag(component.key, 'tray', $event)" @dragend="stopDrag">
                                    <img :src="component.image" :alt="component.name" />
                                    <strong>{{ component.name }}</strong>
                                    <small>{{ isActiveComponent(component.key) ? 'DRAG TO PC CASE' : 'REMOVED'
                                    }}</small>
                                </button>
                                <div v-if="trayComponents.length === 0" class="empty">No removed components yet.</div>
                            </div>
                        </div>
                    </div>

                    <div class="status-card" :class="{ complete: allDone }">
                        <div>
                            <small>PROCEDURE STATUS</small>
                            <strong>{{ statusText }}</strong>
                            <p>{{ feedback }}</p>
                        </div>
                        <button type="button" class="reset" :disabled="saving" @click="resetLab">Reset</button>
                        <button type="button" class="submit" :disabled="!allDone || saving || submitted"
                            @click="submitAttempt">
                            {{ submitted ? 'Submitted' : saving ? 'Saving...' : 'Submit Attempt' }}
                        </button>
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { hardwareProcedureRequest } from '@/assessmentrequests/hardwareProcedure'

const route = useRoute()
const quizId = computed(() => route.params.id)
const quiz = ref({})
const question = ref({})
const steps = ref([])
const currentIndex = ref(0)
const installed = ref(new Set())
const dragging = ref(null)
const dragSource = ref(null)
const feedback = ref('Follow the highlighted procedure step.')
const saving = ref(false)
const submitted = ref(false)
const startedAt = ref(Date.now())
const performedSteps = ref([])

const names = {
    fan: 'CPU Fan', ram: 'RAM', gpu: 'Graphics Card', hdd: 'Hard Drive',
    psu: 'Power Supply', cpu: 'CPU', motherboard: 'Motherboard',
}

const componentName = key => names[key] || key

const assets = computed(() => {
    const map = new Map()
    steps.value.forEach(step => {
        if (!map.has(step.component_key)) map.set(step.component_key, {
            key: step.component_key,
            name: componentName(step.component_key),
            image: step.image,
        })
    })
    return [...map.values()]
})
const activeStep = computed(() => steps.value[currentIndex.value] || null)
const phase = computed(() => activeStep.value?.phase || 'assemble')
const allDone = computed(() => steps.value.length > 0 && currentIndex.value >= steps.value.length)
const completedSteps = computed(() => Math.min(currentIndex.value, steps.value.length))
const progress = computed(() => steps.value.length ? (completedSteps.value / steps.value.length) * 100 : 0)
const installedComponents = computed(() => assets.value.filter(item => installed.value.has(item.key)))
const trayComponents = computed(() => assets.value.filter(item => !installed.value.has(item.key)))
const statusText = computed(() => allDone.value ? 'Assembly and disassembly complete' : `${phase.value === 'disassemble' ? 'Remove' : 'Install'} ${componentName(activeStep.value?.component_key)}`)

function isActiveComponent(key) {
    return activeStep.value?.component_key === key
}

function startDrag(key, source, event) {
    if (!isActiveComponent(key) || submitted.value) {
        event.preventDefault()
        return
    }
    dragging.value = key
    dragSource.value = source
    event.dataTransfer?.setData('text/plain', key)
    if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move'
}
function stopDrag() {
    dragging.value = null
    dragSource.value = null
}
function dropIntoTray() {
    if (!activeStep.value || activeStep.value.phase !== 'disassemble') return rejectDrop()
    if (dragging.value !== activeStep.value.component_key || dragSource.value !== 'case') return rejectDrop()
    moveComponent(false)
}
function dropIntoCase() {
    if (!activeStep.value || activeStep.value.phase !== 'assemble') return rejectDrop()
    if (dragging.value !== activeStep.value.component_key || dragSource.value !== 'tray') return rejectDrop()
    moveComponent(true)
}
function rejectDrop() {
    feedback.value = 'That is not the required component or destination for the current procedure step.'
    stopDrag()
}
function moveComponent(toInstalled) {
    const step = activeStep.value
    if (!step) return
    const next = new Set(installed.value)
    if (toInstalled) next.add(step.component_key)
    else next.delete(step.component_key)
    installed.value = next
    performedSteps.value.push({ item_id: step.id, phase: step.phase })
    currentIndex.value += 1
    feedback.value = allDone.value
        ? 'Procedure complete. Submit the attempt to record it.'
        : `Correct. Next: ${activeStep.value.text}`
    stopDrag()
}
function resetLab() {
    currentIndex.value = 0
    performedSteps.value = []
    submitted.value = false
    startedAt.value = Date.now()
    installed.value = new Set(assets.value.map(item => item.key))
    feedback.value = 'Procedure reset. Start with the first disassembly step.'
}
async function submitAttempt() {
    if (!allDone.value || saving.value || submitted.value) return
    saving.value = true
    try {
        const response = await hardwareProcedureRequest.submit(quizId.value, {
            question_id: question.value.id,
            steps: performedSteps.value,
            elapsed_time: Math.max(0, Math.round((Date.now() - startedAt.value) / 1000)),
        })
        if (!response?.success) throw new Error('Invalid submission response.')
        submitted.value = true
        feedback.value = `Attempt recorded: ${response.data.percentage}% (${response.data.passed ? 'Passed' : 'Failed'}).`
    } catch (error) {
        feedback.value = error?.response?.data?.message || error?.message || 'Unable to submit attempt.'
    } finally {
        saving.value = false
    }
}
async function load() {
    const response = await hardwareProcedureRequest.get(quizId.value)
    quiz.value = response.data.quiz || {}
    question.value = response.data.question || {}
    steps.value = response.data.items || []
    installed.value = new Set(assets.value.map(item => item.key))
    startedAt.value = Date.now()
}
onMounted(load)
</script>

<style scoped>
* {
    box-sizing: border-box
}

.hardware-page {
    min-height: 100vh;
    background: radial-gradient(circle at 8% 8%, rgba(211, 211, 211, .25), transparent 34%), #fff;
    color: #111;
    font-family: Inter, ui-sans-serif, system-ui
}

.shell {
    width: min(1500px, 100%);
    margin: auto;
    padding: 26px
}

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 28px;
    padding: 18px 20px;
    border: 1px solid #d3d3d3;
    border-radius: 16px;
    background: rgba(255, 255, 255, .88);
    box-shadow: 0 14px 38px rgba(0, 0, 0, .05)
}

.eyebrow,
.section-label,
.progress-box span,
.phase-pill,
.workspace-head,
.task-card small,
.zone-title span,
.component-card small,
.status-card small {
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .11em;
    text-transform: uppercase;
    color: #696969
}

.eyebrow span {
    margin: 0 6px;
    color: #a9a9a9
}

.topbar h1 {
    margin: 4px 0 3px;
    font-size: 25px;
    letter-spacing: -.04em
}

.topbar p {
    margin: 0;
    color: #696969;
    font-size: 12px
}

.progress-box {
    width: 220px
}

.progress-box strong {
    display: block;
    font-size: 18px
}

.progress-track {
    height: 5px;
    margin-top: 8px;
    border-radius: 99px;
    background: #eee;
    overflow: hidden
}

.progress-track i {
    display: block;
    height: 100%;
    background: #111;
    transition: width .2s
}

.lab-grid {
    display: grid;
    grid-template-columns: 320px minmax(0, 1fr);
    gap: 16px;
    margin-top: 16px
}

.procedure-panel,
.workspace {
    border: 1px solid #d3d3d3;
    border-radius: 16px;
    background: rgba(255, 255, 255, .92);
    box-shadow: 0 14px 36px rgba(0, 0, 0, .045)
}

.procedure-panel {
    padding: 18px
}

.phase-pill {
    display: inline-flex;
    margin: 14px 0 8px;
    padding: 5px 8px;
    border: 1px solid #d3d3d3;
    border-radius: 7px;
    color: #111
}

.procedure-panel h2 {
    margin: 0;
    font-size: 19px
}

.intro {
    margin: 7px 0 15px;
    color: #696969;
    font-size: 11px;
    line-height: 1.55
}

.steps {
    display: flex;
    flex-direction: column;
    gap: 5px;
    max-height: 500px;
    overflow: auto
}

.step {
    display: grid;
    grid-template-columns: 27px 38px minmax(0, 1fr);
    align-items: center;
    gap: 8px;
    padding: 7px;
    border: 1px solid transparent;
    border-radius: 9px;
    opacity: .45
}

.step.active {
    opacity: 1;
    border-color: #696969;
    background: #f7f7f7
}

.step.done {
    opacity: .75
}

.step img {
    width: 38px;
    height: 30px;
    object-fit: contain;
    border-radius: 5px;
    background: #fff
}

.step-no {
    display: grid;
    place-items: center;
    width: 25px;
    height: 25px;
    border: 1px solid #d3d3d3;
    border-radius: 6px;
    font-size: 9px;
    font-weight: 800
}

.step strong {
    display: block;
    font-size: 10px
}

.step small {
    display: block;
    margin-top: 2px;
    font-size: 7px;
    color: #a9a9a9
}

.task-card {
    margin-top: 14px;
    padding: 13px;
    border: 1px solid #d3d3d3;
    border-radius: 10px;
    background: #111;
    color: #fff
}

.task-card small {
    color: #a9a9a9
}

.task-card h3 {
    margin: 5px 0;
    font-size: 13px
}

.task-card p {
    margin: 0 0 8px;
    color: #d3d3d3;
    font-size: 10px;
    line-height: 1.5
}

.task-card b {
    font-size: 10px
}

.complete-card {
    background: #f7f7f7;
    color: #111
}

.complete-card p {
    color: #696969
}

.workspace {
    padding: 14px
}

.workspace-head {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 10px 12px;
    border-bottom: 1px solid #eee
}

.workspace-head strong {
    color: #111
}

.bench-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 36px minmax(0, 1fr);
    align-items: stretch;
    gap: 10px;
    padding: 14px 0
}

.transfer-arrow {
    display: grid;
    place-items: center;
    font-size: 27px;
    color: #a9a9a9
}

.zone {
    min-height: 510px;
    padding: 14px;
    border: 1px dashed #d3d3d3;
    border-radius: 13px;
    background: #fafafa;
    transition: .15s
}

.zone.target {
    border-color: #111;
    background: #fff
}

.zone-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 11px
}

.zone-title b {
    font-size: 11px
}

.component-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 9px
}

.component-card {
    min-width: 0;
    padding: 9px;
    border: 1px solid #d3d3d3;
    border-radius: 11px;
    background: #fff;
    text-align: left
}

.component-card img {
    display: block;
    width: 100%;
    aspect-ratio: 1.6/1;
    object-fit: contain;
    border-radius: 8px;
    background: #f7f7f7
}

.component-card strong {
    display: block;
    margin-top: 7px;
    font-size: 11px
}

.component-card small {
    display: block;
    margin-top: 3px;
    font-size: 7px
}

.component-card.current {
    border-color: #000;
    box-shadow: 0 0 0 1px #000;
    cursor: grab
}

.component-card.current:active {
    cursor: grabbing
}

.component-card.disabled {
    opacity: .5;
    cursor: default
}

.empty {
    grid-column: 1/-1;
    display: grid;
    place-items: center;
    min-height: 130px;
    color: #a9a9a9;
    font-size: 11px
}

.status-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px;
    border: 1px solid #d3d3d3;
    border-radius: 12px;
    background: #f7f7f7
}

.status-card>div {
    flex: 1
}

.status-card strong {
    display: block;
    margin: 3px 0;
    font-size: 13px
}

.status-card p {
    margin: 0;
    color: #696969;
    font-size: 10px
}

.reset,
.submit {
    min-height: 39px;
    padding: 8px 14px;
    border-radius: 8px;
    font-weight: 750;
    font-size: 10px
}

.reset {
    border: 1px solid #d3d3d3;
    background: #fff
}

.submit {
    border: 1px solid #000;
    background: #000;
    color: #fff
}

.submit:disabled,
.reset:disabled {
    opacity: .4;
    cursor: not-allowed
}

@media(max-width:980px) {
    .lab-grid {
        grid-template-columns: 1fr
    }

    .steps {
        max-height: 300px
    }

    .bench-row {
        grid-template-columns: 1fr
    }

    .transfer-arrow {
        transform: rotate(90deg)
    }

    .zone {
        min-height: 330px
    }
}

@media(max-width:600px) {
    .shell {
        padding: 12px
    }

    .topbar {
        align-items: flex-start;
        flex-direction: column
    }

    .progress-box {
        width: 100%
    }

    .component-grid {
        grid-template-columns: 1fr
    }

    .status-card {
        align-items: stretch;
        flex-direction: column
    }

    .reset,
    .submit {
        width: 100%
    }
}
</style>
