<template>
  <div class="fn-page">
    <div class="fn-shell">
      <div class="utility-bar">
        <div class="utility-left">
          <button class="back-button" type="button" aria-label="Back to assessments" @click="goBack">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M19 12H5m7-7-7 7 7 7" /></svg>
          </button>
          <div class="breadcrumb"><strong>{{ STATIC_ASSESSMENT.program }}</strong><span class="separator">/</span><span>COC {{ STATIC_ASSESSMENT.coc_number }}</span><span class="separator">/</span><span>Hands-on lab</span></div>
        </div>
        <span class="badge"><span class="badge-dot" aria-hidden="true"></span>Static preview</span>
      </div>

      <header class="page-header">
        <div>
          <span class="eyebrow">{{ STATIC_ASSESSMENT.subject }}</span>
          <h1>{{ STATIC_ASSESSMENT.title }}</h1>
          <p class="page-description">{{ STATIC_ASSESSMENT.description }}</p>
        </div>
        <div class="header-readout">
          <div class="readout-line"><strong class="readout-value">{{ pad(completedCount) }}</strong><span class="readout-total">/ {{ pad(steps.length) }}</span></div>
          <span class="readout-label">Components removed</span>
          <div class="progress-track" role="progressbar" aria-label="Disassembly completion" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100"><div class="progress-fill" :style="{ width: `${progress}%` }"></div></div>
        </div>
      </header>

      <div class="module-strip">
        <div class="module-tags"><span class="tag">Component removal</span><span class="tag">{{ STATIC_ASSESSMENT.difficulty }}</span><span class="tag">Self-paced</span></div>
        <span class="strip-note">{{ isComplete ? 'Procedure complete' : 'One component at a time. Every step matters.' }}</span>
      </div>

      <div v-if="configurationError" class="config-error" role="alert"><strong>Check the static data</strong><p>{{ configurationError }}</p></div>
      <main v-else class="lab-layout">
        <aside class="surface procedure-panel" aria-label="Disassembly procedure">
          <div class="panel-heading">
            <div><div class="section-label"><span class="section-number">01</span>Procedure</div><h2>Disassemble the unit</h2><p>Follow the current task. Completed steps stay available for review.</p></div>
          </div>
          <ol class="step-list">
            <li v-for="(step, index) in steps" :key="step.id">
              <button type="button" class="step-item"
                :class="{ current: currentStepIndex === index, completed: step.completed, selected: selectedStepId === step.id }"
                :disabled="!step.completed && index !== currentStepIndex"
                :aria-current="index === currentStepIndex ? 'step' : undefined"
                @click="selectStep(step)">
                <span class="step-index"><span v-if="step.completed" aria-hidden="true">&#10003;</span><span v-else>{{ pad(index + 1) }}</span></span>
                <span class="step-copy"><strong>{{ step.title }}</strong><small>{{ step.description }}</small></span>
                <span class="step-state">{{ step.completed ? 'Done' : index === currentStepIndex ? 'Now' : 'Locked' }}</span>
              </button>
            </li>
          </ol>
          <div v-if="displayedStep" class="task-card">
            <span class="eyebrow">{{ isReviewing ? 'Completed step / review' : 'Current task' }}</span>
            <h3>{{ displayedStep.title }}</h3>
            <p>{{ displayedStep.instruction }}</p>
            <div class="task-target"><span>Component</span><strong>{{ getComponentName(displayedStep.component_key) }}</strong></div>
            <button v-if="isReviewing && !isComplete" type="button" class="text-button" @click="selectedStepId = null">Return to current task &rarr;</button>
          </div>
          <div v-else-if="isComplete" class="task-card"><span class="eyebrow">All steps completed</span><h3>Workbench cleared.</h3><p>Select a completed step above to review its instructions.</p></div>
        </aside>

        <section class="work-column" aria-label="Interactive workbench">
          <div class="surface workbench">
            <div class="workbench-heading">
              <div><div class="section-label"><span class="section-number">02</span>Workbench</div><h2>Inside the system unit</h2></div>
              <span class="badge"><span class="badge-dot" aria-hidden="true"></span>{{ isComplete ? 'Complete' : 'Practice mode' }}</span>
            </div>
            <div class="case-stage" :class="{ dragging: !!draggingComponent }">
              <span class="stage-caption">SIDE VIEW / ILLUSTRATIVE LAYOUT</span>
              <!-- Hardware is drawn locally; no image files or icon packages required. -->
<svg ref="caseSvg" class="pc-case" role="group" aria-label="Illustrative computer case. Use the Remove component button as an alternative to dragging." viewBox="0 0 700 620" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg" @pointermove="handlePointerMove" @pointerup="handlePointerUp" @pointercancel="cancelDrag" @lostpointercapture="cancelDrag" >
<title>Computer disassembly workbench</title>
<!-- Original case geometry retained from the supplied component. -->
<!-- CASE SHADOW -->
<rect x="154" y="34" width="390" height="530" rx="14" class="case-shadow" />
<!-- CASE BODY -->
<rect x="145" y="25" width="390" height="530" rx="14" class="case-body" />
<!-- CASE INNER -->
<rect x="165" y="55" width="350" height="470" rx="7" class="case-inner" />
<!-- GLASS -->
<rect x="180" y="70" width="320" height="440" rx="5" class="case-glass" />
<!-- ================================ MOTHERBOARD ================================= -->
<g v-if="components.motherboard"
  :transform="componentTransform('motherboard')"
  :tabindex="isTarget('motherboard') ? 0 : -1" role="button"
  :aria-label="`Remove ${getComponentName('motherboard')}`"
  :aria-disabled="!isTarget('motherboard')"
  @keydown.enter.prevent="removeComponent('motherboard')"
  @keydown.space.prevent="removeComponent('motherboard')" class="hardware-component motherboard" :class="{ target: isTarget('motherboard'), dragging: draggingComponent === 'motherboard' }" @pointerdown.stop=" startDrag( 'motherboard', $event ) " >
<rect x="205" y="110" width="180" height="340" rx="5" class="motherboard-board" />
<rect x="245" y="165" width="70" height="70" class="chip-slot" />
<rect x="255" y="175" width="50" height="50" class="chip" />
<rect x="330" y="145" width="10" height="125" rx="2" class="ram-slot" />
<rect x="345" y="145" width="10" height="125" rx="2" class="ram-slot" />
<rect x="360" y="145" width="10" height="125" rx="2" class="ram-slot" />
<rect x="230" y="295" width="135" height="7" class="expansion-slot" />
<rect x="230" y="320" width="135" height="7" class="expansion-slot" />
<rect x="230" y="345" width="135" height="7" class="expansion-slot" />
<circle cx="220" cy="425" r="5" class="board-detail" />
</g>
<!-- ================================ CPU ================================= -->
<g v-if="components.cpu"
  :transform="componentTransform('cpu')"
  :tabindex="isTarget('cpu') ? 0 : -1" role="button"
  :aria-label="`Remove ${getComponentName('cpu')}`"
  :aria-disabled="!isTarget('cpu')"
  @keydown.enter.prevent="removeComponent('cpu')"
  @keydown.space.prevent="removeComponent('cpu')" class="hardware-component" :class="{ target: isTarget('cpu'), dragging: draggingComponent === 'cpu' }" @pointerdown.stop=" startDrag('cpu', $event) " >
<rect x="257" y="177" width="46" height="46" rx="2" class="cpu-body" />
<rect x="266" y="186" width="28" height="28" class="cpu-core" />
<text x="280" y="205" text-anchor="middle" class="svg-label" > CPU </text>
</g>
<!-- ================================ RAM ================================= -->
<g v-if="components.ram"
  :transform="componentTransform('ram')"
  :tabindex="isTarget('ram') ? 0 : -1" role="button"
  :aria-label="`Remove ${getComponentName('ram')}`"
  :aria-disabled="!isTarget('ram')"
  @keydown.enter.prevent="removeComponent('ram')"
  @keydown.space.prevent="removeComponent('ram')" class="hardware-component" :class="{ target: isTarget('ram'), dragging: draggingComponent === 'ram' }" @pointerdown.stop=" startDrag('ram', $event) " >
<rect x="329" y="145" width="10" height="125" rx="2" class="ram-stick" />
<rect x="344" y="145" width="10" height="125" rx="2" class="ram-stick" />
</g>
<!-- ================================ GPU ================================= -->
<g v-if="components.gpu"
  :transform="componentTransform('gpu')"
  :tabindex="isTarget('gpu') ? 0 : -1" role="button"
  :aria-label="`Remove ${getComponentName('gpu')}`"
  :aria-disabled="!isTarget('gpu')"
  @keydown.enter.prevent="removeComponent('gpu')"
  @keydown.space.prevent="removeComponent('gpu')" class="hardware-component" :class="{ target: isTarget('gpu'), dragging: draggingComponent === 'gpu' }" @pointerdown.stop=" startDrag('gpu', $event) " >
<rect x="225" y="290" width="145" height="48" rx="3" class="gpu-body" />
<circle cx="260" cy="314" r="13" class="gpu-fan" />
<circle cx="335" cy="314" r="13" class="gpu-fan" />
<text x="295" y="318" text-anchor="middle" class="svg-label" > GPU </text>
</g>
<!-- ================================ HDD ================================= -->
<g v-if="components.hdd"
  :transform="componentTransform('hdd')"
  :tabindex="isTarget('hdd') ? 0 : -1" role="button"
  :aria-label="`Remove ${getComponentName('hdd')}`"
  :aria-disabled="!isTarget('hdd')"
  @keydown.enter.prevent="removeComponent('hdd')"
  @keydown.space.prevent="removeComponent('hdd')" class="hardware-component" :class="{ target: isTarget('hdd'), dragging: draggingComponent === 'hdd' }" @pointerdown.stop=" startDrag('hdd', $event) " >
<rect x="400" y="270" width="80" height="55" rx="3" class="drive-body" />
<circle cx="440" cy="297" r="14" class="drive-platter" />
<text x="440" y="317" text-anchor="middle" class="svg-label" > HDD </text>
</g>
<!-- ================================ FAN ================================= -->
<g v-if="components.fan"
  :transform="componentTransform('fan')"
  :tabindex="isTarget('fan') ? 0 : -1" role="button"
  :aria-label="`Remove ${getComponentName('fan')}`"
  :aria-disabled="!isTarget('fan')"
  @keydown.enter.prevent="removeComponent('fan')"
  @keydown.space.prevent="removeComponent('fan')" class="hardware-component" :class="{ target: isTarget('fan'), dragging: draggingComponent === 'fan' }" @pointerdown.stop=" startDrag('fan', $event) " >
<circle cx="442" cy="170" r="48" class="fan-ring" />
<circle cx="442" cy="170" r="10" class="fan-center" />
<ellipse cx="442" cy="142" rx="9" ry="23" class="fan-blade" />
<ellipse cx="470" cy="170" rx="23" ry="9" class="fan-blade" />
<ellipse cx="442" cy="198" rx="9" ry="23" class="fan-blade" />
<ellipse cx="414" cy="170" rx="23" ry="9" class="fan-blade" />
</g>
<!-- ================================ PSU ================================= -->
<g v-if="components.psu"
  :transform="componentTransform('psu')"
  :tabindex="isTarget('psu') ? 0 : -1" role="button"
  :aria-label="`Remove ${getComponentName('psu')}`"
  :aria-disabled="!isTarget('psu')"
  @keydown.enter.prevent="removeComponent('psu')"
  @keydown.space.prevent="removeComponent('psu')" class="hardware-component" :class="{ target: isTarget('psu'), dragging: draggingComponent === 'psu' }" @pointerdown.stop=" startDrag('psu', $event) " >
<rect x="405" y="390" width="75" height="90" rx="4" class="psu-body" />
<circle cx="442" cy="430" r="23" class="psu-fan" />
<circle cx="442" cy="430" r="12" class="psu-center" />
<text x="442" y="465" text-anchor="middle" class="svg-label" > PSU </text>
</g>
<!-- CASE FRONT -->
<rect x="470" y="85" width="30" height="410" rx="4" class="case-front" />
<circle cx="485" cy="110" r="4" class="power-led" />
<rect x="478" y="130" width="14" height="5" rx="2" class="front-slot" />
<rect x="478" y="145" width="14" height="5" rx="2" class="front-slot" />
</svg>
              <div class="stage-task"><span class="eyebrow">{{ isComplete ? 'Status' : 'Highlighted component' }}</span><strong>{{ isComplete ? 'All components removed' : getComponentName(currentStep?.component_key) }}</strong></div>
            </div>
            <div class="workbench-controls">
              <p class="feedback-line" role="status" aria-live="polite">{{ notice }}</p>
              <button type="button" class="primary-button remove-button" :disabled="isComplete || !currentStep" @click="removeComponent(currentStep.component_key)">Remove {{ currentStep ? getComponentName(currentStep.component_key) : 'component' }} <span aria-hidden="true">&darr;</span></button>
            </div>
          </div>

          <section ref="partsTray" class="surface parts-tray" :class="{ 'drop-active': isOverRemoveZone }" aria-label="Removed components tray">
            <div class="tray-heading"><div><span class="eyebrow">03 / Parts tray</span><h2>{{ isOverRemoveZone ? 'Release to remove component' : 'Removed components' }}</h2></div><span class="count-chip">{{ completedCount }}/{{ steps.length }}</span></div>
            <p class="tray-help">Drag the highlighted component here, or use the Remove button above.</p>
            <div class="component-grid">
              <div v-for="component in componentList" :key="component.id" class="inventory-item" :class="{ removed: !components[component.id], target: isTarget(component.id) }">
                <span class="inventory-icon" aria-hidden="true">{{ !components[component.id] ? '\u2713' : component.short }}</span>
                <strong>{{ component.name }}</strong>
                <small>{{ !components[component.id] ? 'Removed' : isTarget(component.id) ? 'Current target' : 'In the case' }}</small>
              </div>
            </div>
          </section>
          <div class="safety-note"><span class="note-icon" aria-hidden="true">i</span><p><strong>Simulation only.</strong> {{ STATIC_ASSESSMENT.safety_note }}</p></div>
        </section>
      </main>

      <section v-if="isComplete && !configurationError" class="surface result-banner" aria-label="Disassembly result">
        <div><span class="eyebrow">Procedure complete</span><h2>{{ completedCount }} components. One clear workbench.</h2><p>You completed the guided removal sequence. This records practice completion, not an official competency grade.</p></div>
        <div class="result-actions"><button type="button" class="secondary-button" @click="resetSimulator">Practice again</button><button type="button" class="primary-button" @click="goBack">Back to assessments &rarr;</button></div>
      </section>
      <div v-else-if="!configurationError" class="lab-actions"><span>Review a completed step without losing your place.</span><button type="button" class="secondary-button" :disabled="completedCount === 0 && !draggingComponent" @click="resetSimulator">&#8635; Reset practice</button></div>
      <footer class="page-footer"><strong>CSS PREP / PRACTICAL LEARNING</strong><span>Sample data &middot; Progress is temporary and clears on refresh.</span></footer>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

/* ======================================================================
   EDIT STATIC DATA HERE. No backend request is made by this component.
   The seven component IDs match the supplied SVG. You may rename labels,
   reorder steps, and edit instructions. New hardware IDs also need an SVG
   group. The source's COC 3 label is retained, not an official classification.
   API integration later: hydrate the same shape and validate on the server.
   ====================================================================== */
const STATIC_ASSESSMENT = {
  id: 'demo-disassembly',
  program: 'CSS PREP',
  coc_number: 3,
  title: 'Computer Disassembly',
  subject: 'Computer Maintenance and Troubleshooting',
  difficulty: 'Guided practice',
  description: 'Explore the system unit, identify each component, and work through a guided removal sequence at your own pace.',
  safety_note: 'Assume the workstation is shut down, unplugged, and prepared for safe handling. Cables and fasteners are not simulated. Follow your trainer and equipment manual for real hardware; do not open a power supply.',
  components: [
    { id: 'fan', name: 'CPU Fan', short: 'FAN' },
    { id: 'ram', name: 'RAM', short: 'RAM' },
    { id: 'gpu', name: 'Graphics Card', short: 'GPU' },
    { id: 'hdd', name: 'Hard Drive', short: 'HDD' },
    { id: 'psu', name: 'Power Supply', short: 'PSU' },
    { id: 'cpu', name: 'CPU', short: 'CPU' },
    { id: 'motherboard', name: 'Motherboard', short: 'MB' }
  ],
  // Order and wording adapted from the user's supplied disassembly data.
  steps: [
    { id: 1, component_key: 'fan', title: 'Remove CPU Fan', description: 'Remove the cooling assembly.', instruction: 'Locate the CPU fan and move it into the parts tray outside the case.' },
    { id: 2, component_key: 'ram', title: 'Remove RAM', description: 'Release the memory module.', instruction: 'Locate the RAM module and move it into the parts tray.' },
    { id: 3, component_key: 'gpu', title: 'Remove Graphics Card', description: 'Remove the expansion card.', instruction: 'Locate the graphics card and move it into the parts tray.' },
    { id: 4, component_key: 'hdd', title: 'Remove Storage', description: 'Disconnect the storage device.', instruction: 'Locate the hard drive and move it into the parts tray.' },
    { id: 5, component_key: 'psu', title: 'Remove Power Supply', description: 'Disconnect the power source.', instruction: 'Locate the power supply and move the complete unit into the parts tray.' },
    { id: 6, component_key: 'cpu', title: 'Remove CPU', description: 'Release the processor.', instruction: 'Locate the CPU and move it into the parts tray.' },
    { id: 7, component_key: 'motherboard', title: 'Remove Motherboard', description: 'Remove the motherboard last.', instruction: 'Locate the motherboard and move it into the parts tray to finish.' }
  ]
}
/* =========================== END STATIC DATA =========================== */

const emit = defineEmits(['progress', 'complete'])
const route = useRoute()
const router = useRouter()
const componentList = STATIC_ASSESSMENT.components
const svgComponentIds = ['fan', 'ram', 'gpu', 'hdd', 'psu', 'cpu', 'motherboard']
const steps = ref(STATIC_ASSESSMENT.steps.map(step => ({ ...step, completed: false })))
const components = ref(Object.fromEntries(componentList.map(item => [item.id, true])))
const selectedStepId = ref(null)
const notice = ref('Drag the highlighted part into the tray, or use the Remove button.')
const caseSvg = ref(null)
const partsTray = ref(null)
const draggingComponent = ref(null)
const isOverRemoveZone = ref(false)
const dragOffset = ref({ x: 0, y: 0 })
let pointerId = null
let startPoint = null
let startClient = null
let hasMoved = false

const configurationError = computed(() => {
  const componentIds = componentList.map(item => item.id)
  if (!steps.value.length) return 'Add at least one removal step.'
  if (new Set(componentIds).size !== componentIds.length) return 'Each component needs a unique ID.'
  if (componentIds.some(id => !svgComponentIds.includes(id))) return 'A component ID is missing from the SVG. Add its visual group before using it.'
  if (new Set(steps.value.map(step => step.id)).size !== steps.value.length) return 'Each step needs a unique ID.'
  const keys = steps.value.map(step => step.component_key)
  if (keys.some(key => !componentIds.includes(key))) return 'A step references an unknown component_key.'
  if (new Set(keys).size !== keys.length) return 'Each component can be removed only once.'
  if (componentIds.some(id => !keys.includes(id))) return 'Give every listed component one removal step.'
  return ''
})
const completedCount = computed(() => steps.value.filter(step => step.completed).length)
// Reviewing old steps NEVER changes the next unfinished step.
const currentStepIndex = computed(() => steps.value.findIndex(step => !step.completed))
const currentStep = computed(() => steps.value[currentStepIndex.value] ?? null)
const displayedStep = computed(() => steps.value.find(step => step.id === selectedStepId.value) ?? currentStep.value)
const isReviewing = computed(() => Boolean(displayedStep.value?.completed))
const isComplete = computed(() => steps.value.length > 0 && completedCount.value === steps.value.length)
const progress = computed(() => steps.value.length ? Math.round(completedCount.value / steps.value.length * 100) : 0)
const pad = value => String(value).padStart(2, '0')
const getComponentName = id => componentList.find(item => item.id === id)?.name ?? 'component'
const isTarget = id => !configurationError.value && components.value[id] && currentStep.value?.component_key === id

function selectStep(step) {
  if (step.completed || step.id === currentStep.value?.id) selectedStepId.value = step.id
}

function resultPayload() {
  return {
    assessment_id: STATIC_ASSESSMENT.id,
    assessment_type: 'disassembly',
    status: isComplete.value ? 'completed' : 'in_progress',
    completed_step_ids: steps.value.filter(step => step.completed).map(step => step.id),
    removed_component_ids: componentList.filter(item => !components.value[item.id]).map(item => item.id),
    completion_percent: progress.value
  }
}

function removeComponent(id) {
  if (configurationError.value || isComplete.value || !isTarget(id)) return
  const step = currentStep.value
  cancelDrag()
  components.value[id] = false
  step.completed = true
  selectedStepId.value = null
  notice.value = isComplete.value
    ? 'All components are in the tray. Your practice sequence is complete.'
    : `${getComponentName(id)} removed. Next: ${getComponentName(currentStep.value.component_key)}.`
  emit('progress', resultPayload())
  if (isComplete.value) emit('complete', resultPayload())
}

function pointInSvg(event) {
  const svg = caseSvg.value
  const matrix = svg?.getScreenCTM()
  if (!svg || !matrix) return null
  const point = svg.createSVGPoint()
  point.x = event.clientX
  point.y = event.clientY
  return point.matrixTransform(matrix.inverse())
}

function pointInTray(event) {
  const box = partsTray.value?.getBoundingClientRect()
  return Boolean(box && event.clientX >= box.left && event.clientX <= box.right && event.clientY >= box.top && event.clientY <= box.bottom)
}

function startDrag(id, event) {
  if (!event.isPrimary || (event.pointerType === 'mouse' && event.button !== 0) || pointerId !== null) return
  if (!isTarget(id)) {
    if (currentStep.value) notice.value = `Follow the current task: remove ${getComponentName(currentStep.value.component_key)} first.`
    return
  }
  const point = pointInSvg(event)
  if (!point) return
  event.preventDefault()
  pointerId = event.pointerId
  startPoint = point
  startClient = { x: event.clientX, y: event.clientY }
  draggingComponent.value = id
  dragOffset.value = { x: 0, y: 0 }
  hasMoved = false
  // Capture on the stable SVG, not a group that disappears after removal.
  try { caseSvg.value.setPointerCapture(event.pointerId) } catch { cancelDrag(); return }
  notice.value = `Move ${getComponentName(id)} into the parts tray below.`
}

function handlePointerMove(event) {
  if (event.pointerId !== pointerId || !draggingComponent.value || !startPoint || !startClient) return
  const point = pointInSvg(event)
  if (!point) return
  dragOffset.value = { x: point.x - startPoint.x, y: point.y - startPoint.y }
  hasMoved ||= Math.hypot(event.clientX - startClient.x, event.clientY - startClient.y) > 6
  isOverRemoveZone.value = pointInTray(event)
}

function handlePointerUp(event) {
  if (event.pointerId !== pointerId || !draggingComponent.value) return
  const id = draggingComponent.value
  const shouldRemove = hasMoved && pointInTray(event)
  cancelDrag()
  if (shouldRemove) removeComponent(id)
  else notice.value = 'Drop the part into the tray, or use the Remove button. No progress was changed.'
}

// Cancellation is NOT submission. A browser gesture must not remove hardware.
function cancelDrag(event) {
  if (event && pointerId !== null && event.pointerId !== pointerId) return
  const capturedId = pointerId
  pointerId = null
  draggingComponent.value = null
  isOverRemoveZone.value = false
  dragOffset.value = { x: 0, y: 0 }
  startPoint = null
  startClient = null
  hasMoved = false
  if (capturedId !== null && caseSvg.value?.hasPointerCapture(capturedId)) {
    try { caseSvg.value.releasePointerCapture(capturedId) } catch { /* Already released by the browser. */ }
  }
}

function componentTransform(id) {
  return draggingComponent.value === id ? `translate(${dragOffset.value.x} ${dragOffset.value.y})` : undefined
}

function resetSimulator() {
  cancelDrag()
  steps.value = STATIC_ASSESSMENT.steps.map(step => ({ ...step, completed: false }))
  components.value = Object.fromEntries(componentList.map(item => [item.id, true]))
  selectedStepId.value = null
  notice.value = 'Practice reset. Start with the highlighted component.'
  emit('progress', resultPayload())
}

function goBack() {
  const id = route.params.id ?? route.params.quizId
  router.push(id ? `/user/quizzes/assessment/${encodeURIComponent(String(id))}` : '/user/quizzes')
}

watch(() => route.params.id ?? route.params.quizId, resetSimulator)
onBeforeUnmount(() => cancelDrag())
</script>

<style scoped>

/* FROSTED NOIR: only the five requested colors and their transparent tints. */
.fn-page {
  --white: #FFFFFF;
  --black: #000000;
  --silver: #A9A9A9;
  --light-gray: #D3D3D3;
  --dim-gray: #696969;
  --soft: rgba(211, 211, 211, 0.16);
  --line: rgba(169, 169, 169, 0.38);
  color-scheme: light;
  min-height: 100vh;
  width: 100%;
  min-width: 0;
  color: var(--black);
  background: radial-gradient(ellipse at 95% 0%, rgba(211,211,211,.45), transparent 43%), var(--white);
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  font-size: 14px;
  line-height: 1.5;
  -webkit-font-smoothing: antialiased;
}
.fn-page *, .fn-page *::before, .fn-page *::after { box-sizing: border-box; }
.fn-page button { font: inherit; }
.fn-page button:not(:disabled) { cursor: pointer; }
.fn-page button:disabled { cursor: not-allowed; }
.fn-page button:focus-visible, .fn-page a:focus-visible,
.fn-page [tabindex="0"]:focus-visible {
  outline: 2px solid var(--black);
  outline-offset: 4px;
}
.fn-page h1, .fn-page h2, .fn-page h3, .fn-page p { margin: 0; }
.fn-page h1, .fn-page h2, .fn-page h3 { color: var(--black); }
.fn-shell { width: min(1280px, 100%); margin: 0 auto; padding: 24px 32px 28px; }
.utility-bar { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 26px; }
.utility-left { display: flex; align-items: center; gap: 15px; min-width: 0; }
.back-button { display: inline-grid; place-items: center; width: 42px; height: 42px; flex-shrink: 0; border: 1px solid var(--line); border-radius: 12px; color: var(--black); background: rgba(255,255,255,.86); }
.back-button:hover { border-color: var(--dim-gray); background: var(--white); }
.back-button svg { width: 18px; height: 18px; }
.breadcrumb { color: var(--dim-gray); font-size: 10px; letter-spacing: .13em; text-transform: uppercase; display: flex; gap: 11px; align-items: center; flex-wrap: wrap; }
.breadcrumb strong { color: var(--black); font-weight: 800; }
.breadcrumb .separator { color: var(--silver); }
.badge { display: inline-flex; align-items: center; gap: 7px; flex-shrink: 0; padding: 6px 10px; border: 1px solid var(--line); background: rgba(255,255,255,.78); color: var(--dim-gray); border-radius: 8px; font-size: 11px; font-weight: 600; }
.badge-dot { width: 5px; height: 5px; background: var(--dim-gray); border-radius: 50%; }
.page-header { display: flex; justify-content: space-between; align-items: center; gap: 28px; margin-bottom: 22px; }
.eyebrow { display: block; color: var(--dim-gray); font-size: 10px; font-weight: 750; letter-spacing: .13em; text-transform: uppercase; margin-bottom: 8px; }
.page-header h1 { font-size: clamp(26px, 3vw, 38px); font-weight: 760; letter-spacing: -.045em; line-height: 1.12; }
.page-description { color: var(--dim-gray); max-width: 650px; margin-top: 10px !important; font-size: 13px; line-height: 1.75; }
.header-readout { min-width: 182px; border-left: 1px solid var(--light-gray); padding-left: 28px; }
.readout-line { display: flex; align-items: baseline; gap: 6px; }
.readout-value { font-size: 37px; font-weight: 650; line-height: 1.15; letter-spacing: -.05em; font-variant-numeric: tabular-nums; }
.readout-total { font-size: 18px; color: var(--dim-gray); }
.readout-label { display: block; color: var(--dim-gray); font-size: 11px; margin: 4px 0 10px; }
.progress-track { height: 4px; border-radius: 99px; background: var(--light-gray); overflow: hidden; }
.progress-fill { height: 100%; background: var(--black); border-radius: inherit; transition: width .25s ease; }
.module-strip { display: flex; align-items: center; flex-wrap: wrap; justify-content: space-between; gap: 12px; padding: 13px 0 19px; margin-bottom: 19px; border-bottom: 1px solid var(--light-gray); }
.module-tags { display: flex; flex-wrap: wrap; gap: 8px; }
.tag { display: inline-flex; align-items: center; gap: 6px; padding: 5px 9px; border-radius: 6px; font-size: 10px; font-weight: 650; background: var(--soft); color: var(--dim-gray); border: 1px solid var(--line); }
.strip-note { font-size: 11px; color: var(--dim-gray); }
.surface { min-width: 0; border: 1px solid var(--line); border-radius: 18px; background: rgba(255,255,255,.87); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 12px 34px rgba(0,0,0,.035); }
.panel-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
.panel-heading h2 { font-size: 18px; font-weight: 720; letter-spacing: -.035em; }
.panel-heading p { margin-top: 4px; font-size: 12px; line-height: 1.65; color: var(--dim-gray); }
.section-label { display: flex; align-items: center; gap: 8px; font-size: 10px; font-weight: 750; text-transform: uppercase; letter-spacing: .12em; color: var(--dim-gray); margin-bottom: 11px; }
.section-label .section-number { display: inline-grid; place-items: center; width: 22px; height: 22px; border: 1px solid var(--line); border-radius: 6px; color: var(--black); background: var(--white); font-size: 9px; letter-spacing: 0; }
.count-chip { flex-shrink: 0; display: inline-grid; place-items: center; min-width: 36px; min-height: 32px; padding: 5px 9px; border: 1px solid var(--line); border-radius: 9px; background: var(--soft); color: var(--black); font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; }
.primary-button, .secondary-button { display: inline-flex; justify-content: center; align-items: center; gap: 10px; min-height: 44px; padding: 10px 17px; border-radius: 10px; border: 1px solid var(--black); font-size: 12px !important; font-weight: 700 !important; transition: background .15s ease, border-color .15s ease, transform .15s ease; text-decoration: none; }
.primary-button { background: var(--black); color: var(--white); }
.primary-button:hover:not(:disabled) { background: var(--dim-gray); border-color: var(--dim-gray); }
.secondary-button { background: var(--white); color: var(--dim-gray); border-color: var(--line); }
.secondary-button:hover:not(:disabled) { background: var(--soft); border-color: var(--dim-gray); color: var(--black); }
.primary-button:disabled, .secondary-button:disabled { opacity: .42; }
.text-button { min-height: 36px; padding: 5px 0; border: 0; background: transparent; color: var(--black); font-size: 11px !important; font-weight: 650 !important; text-decoration: underline; text-underline-offset: 4px; }
.feedback-line { color: var(--dim-gray); font-size: 12px; min-height: 20px; overflow-wrap: anywhere; }
.safety-note { display: flex; align-items: flex-start; gap: 10px; padding: 13px 15px; border: 1px solid var(--line); background: var(--soft); border-radius: 11px; color: var(--dim-gray); font-size: 11px; line-height: 1.75; }
.safety-note strong { color: var(--black); font-weight: 700; }
.note-icon { width: 18px; height: 18px; flex-shrink: 0; border: 1px solid var(--silver); border-radius: 50%; display: inline-grid; place-items: center; font-size: 11px; font-weight: 700; margin-top: 2px; }
.empty-state { min-height: 170px; display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 24px; text-align: center; gap: 8px; color: var(--dim-gray); }
.empty-state strong { color: var(--black); font-size: 14px; }
.empty-state p { max-width: 270px; font-size: 12px; line-height: 1.75; }
.empty-icon { width: 42px; height: 42px; display: grid; place-items: center; border: 1px solid var(--line); border-radius: 12px; margin-bottom: 5px; background: var(--white); font-size: 22px; color: var(--black); }
.result-banner { padding: 22px; display: flex; justify-content: space-between; gap: 22px; align-items: center; margin-top: 18px; }
.result-banner h2 { font-size: 20px; letter-spacing: -.03em; }
.result-banner p { color: var(--dim-gray); font-size: 12px; margin-top: 5px; max-width: 560px; line-height: 1.7; }
.result-actions { display: flex; gap: 9px; flex-wrap: wrap; }
.page-footer { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 9px; margin-top: 23px; padding-top: 15px; border-top: 1px solid var(--light-gray); color: var(--dim-gray); font-size: 10px; line-height: 1.6; }
.page-footer strong { letter-spacing: .11em; font-size: 9px; }
.config-error { padding: 20px; margin: 18px 0; border: 1px dashed var(--black); background: var(--white); border-radius: 12px; }
.config-error p { margin-top: 5px; color: var(--dim-gray); }
.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
@media (max-width: 1000px) { .fn-shell { padding: 22px; } }
@media (max-width: 640px) {
  .fn-shell { padding: 16px 14px 22px; }
  .utility-bar { margin-bottom: 22px; gap: 8px; }
  .utility-left { gap: 10px; }
  .breadcrumb { gap: 7px; font-size: 9px; }
  .badge { font-size: 10px; padding: 6px 8px; }
  .page-header { align-items: flex-start; flex-direction: column; gap: 18px; }
  .page-header h1 { font-size: 29px; }
  .page-description { font-size: 12px; }
  .header-readout { width: 100%; padding: 0; border: 0; display: grid; grid-template-columns: auto 1fr; align-items: center; column-gap: 14px; }
  .readout-line { grid-row: span 2; }
  .readout-value { font-size: 29px; }
  .readout-total { font-size: 16px; }
  .readout-label { margin: 0 0 4px; }
  .module-strip { padding-top: 4px; margin-bottom: 14px; }
  .surface { border-radius: 14px; }
  .panel-heading h2 { font-size: 17px; }
  .result-banner { flex-direction: column; align-items: stretch; padding: 18px; }
  .result-actions > button { flex: 1; }
}
@media (prefers-reduced-motion: reduce) {
  .fn-page *, .fn-page *::before, .fn-page *::after { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
}

.lab-layout { display: grid; grid-template-columns: 340px minmax(0, 1fr); align-items: start; gap: 18px; }
.procedure-panel { padding: 23px 20px; }
.step-list { display: flex; flex-direction: column; gap: 7px; padding: 0; margin: 0; list-style: none; }
.step-item { width: 100%; display: grid; grid-template-columns: 30px minmax(0,1fr) auto; align-items: center; gap: 10px; padding: 13px 10px; border: 1px solid transparent; border-radius: 11px; background: transparent; color: var(--dim-gray); text-align: left; }
.step-item:hover:not(:disabled) { background: var(--soft); }
.step-item.current { border-color: var(--silver); background: var(--soft); color: var(--black); }
.step-item.selected:not(.current) { border-color: var(--silver); border-style: dashed; }
.step-index { display: grid; place-items: center; width: 29px; height: 29px; border: 1px solid var(--line); background: var(--white); border-radius: 8px; color: var(--dim-gray); font-size: 10px; font-weight: 700; }
.current .step-index { background: var(--black); color: var(--white); border-color: var(--black); }
.completed .step-index { border-color: var(--silver); color: var(--black); }
.step-copy { min-width: 0; }
.step-copy strong { display: block; font-size: 12px; font-weight: 670; }
.step-copy small { display: block; color: var(--dim-gray); font-size: 10px; line-height: 1.5; margin-top: 3px; }
.step-state { color: var(--dim-gray); font-size: 8px; letter-spacing: .04em; text-transform: uppercase; }
.current .step-state { color: var(--black); font-weight: 800; }
.task-card { background: var(--soft); border: 1px solid var(--line); border-radius: 12px; padding: 17px; margin-top: 21px; }
.task-card h3 { font-size: 14px; letter-spacing: -.02em; }
.task-card p { font-size: 12px; line-height: 1.8; color: var(--dim-gray); margin-top: 7px; }
.task-target { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--line); font-size: 10px; color: var(--dim-gray); }
.task-target strong { color: var(--black); }
.work-column { display: flex; flex-direction: column; gap: 15px; min-width: 0; }
.workbench { position: relative; }
.workbench-heading { display: flex; align-items: center; justify-content: space-between; gap: 15px; padding: 22px 24px 17px; }
.workbench-heading h2 { font-size: 18px; letter-spacing: -.03em; }
.workbench-heading .section-label { margin-bottom: 6px; }
.case-stage { position: relative; display: grid; place-items: center; background: linear-gradient(rgba(211,211,211,.24) 1px, transparent 1px), linear-gradient(90deg,rgba(211,211,211,.24) 1px,transparent 1px), var(--white); background-size: 22px 22px; border-block: 1px solid var(--line); padding: 34px 8px 50px; }
.case-stage.dragging { z-index: 5; }
.stage-caption { position: absolute; top: 14px; left: 23px; font-size: 8px; font-weight: 700; color: var(--dim-gray); letter-spacing: .12em; }
.pc-case { position: relative; display: block; width: min(100%, 550px); height: auto; max-height: 390px; overflow: visible; touch-action: pan-y; }
.case-shadow { fill: var(--light-gray); opacity: .6; }
.case-body { fill: var(--white); stroke: var(--silver); stroke-width: 2; }
.case-inner { fill: var(--light-gray); stroke: var(--silver); }
.case-glass { fill: var(--white); fill-opacity: .7; stroke: var(--silver); }
.hardware-component { cursor: default; user-select: none; }
.hardware-component.target { cursor: grab; touch-action: none; filter: drop-shadow(0 2px 5px rgba(0,0,0,.25)); }
.hardware-component.target > :first-child { stroke: var(--black); stroke-width: 3; stroke-dasharray: 5 2; }
.hardware-component.dragging { cursor: grabbing; opacity: .88; }
.motherboard-board { fill: var(--light-gray); stroke: var(--dim-gray); }
.chip-slot { fill: var(--white); stroke: var(--dim-gray); }
.chip { fill: var(--silver); }
.ram-slot { fill: var(--silver); }
.ram-stick { fill: var(--dim-gray); stroke: var(--white); }
.expansion-slot { fill: var(--dim-gray); }
.board-detail { fill: var(--white); stroke: var(--dim-gray); }
.cpu-body { fill: var(--silver); stroke: var(--dim-gray); }
.cpu-core { fill: var(--white); }
.gpu-body, .psu-body, .drive-body { fill: var(--light-gray); stroke: var(--dim-gray); }
.gpu-fan, .drive-platter, .psu-fan { fill: var(--white); stroke: var(--dim-gray); }
.fan-ring { fill: var(--white); stroke: var(--dim-gray); stroke-width: 2; }
.fan-blade { fill: var(--silver); }
.fan-center, .psu-center { fill: var(--dim-gray); }
.svg-label { fill: var(--black); font-size: 8px; font-family: ui-monospace, Consolas, monospace; pointer-events: none; }
.case-front { fill: var(--light-gray); stroke: var(--silver); }
.power-led { fill: var(--dim-gray); }
.front-slot { fill: var(--dim-gray); }
.stage-task { position: absolute; bottom: 14px; left: 50%; transform: translateX(-50%); text-align: center; min-width: 170px; max-width: 90%; padding: 8px 20px; border: 1px solid var(--line); border-radius: 9px; background: rgba(255,255,255,.95); pointer-events: none; }
.stage-task .eyebrow { font-size: 8px; margin-bottom: 1px; }
.stage-task strong { font-size: 12px; }
.workbench-controls { display: flex; align-items: center; gap: 18px; padding: 17px 22px; }
.workbench-controls p { flex: 1; font-size: 11px; }
.remove-button { flex-shrink: 0; }
.parts-tray { padding: 19px 21px; transition: border-color .15s ease, background .15s ease; }
.parts-tray.drop-active { border: 2px dashed var(--black); background: var(--white); }
.tray-heading { display: flex; justify-content: space-between; gap: 12px; align-items: center; }
.tray-heading .eyebrow { margin-bottom: 3px; font-size: 9px; }
.tray-heading h2 { font-size: 16px; letter-spacing: -.025em; }
.tray-help { margin: 7px 0 14px !important; font-size: 11px; color: var(--dim-gray); }
.component-grid { display: grid; grid-template-columns: repeat(7,minmax(0,1fr)); gap: 6px; }
.inventory-item { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 10px 3px 9px; border: 1px dashed var(--line); border-radius: 9px; gap: 4px; }
.inventory-item.target { border-color: var(--silver); }
.inventory-item.removed { background: var(--soft); border-style: solid; border-color: var(--silver); }
.inventory-icon { display: grid; place-items: center; width: 29px; height: 29px; background: var(--white); color: var(--dim-gray); border: 1px solid var(--line); border-radius: 7px; font-size: 8px; font-weight: 700; }
.removed .inventory-icon { color: var(--black); font-size: 14px; }
.inventory-item strong { font-size: 9px; line-height: 1.5; font-weight: 650; min-height: 27px; display: flex; align-items: center; }
.inventory-item small { font-size: 8px; color: var(--dim-gray); }
.lab-actions { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-top: 19px; }
.lab-actions > span { font-size: 11px; color: var(--dim-gray); }
@media (max-width: 1100px) { .lab-layout { grid-template-columns: 305px minmax(0,1fr); gap: 14px; } .component-grid { grid-template-columns: repeat(4,minmax(0,1fr)); } .workbench-controls { align-items: stretch; flex-direction: column; gap: 12px; } }
@media (max-width: 850px) { .lab-layout { grid-template-columns: 1fr; } .procedure-panel { padding: 20px; } .step-list { display: grid; grid-template-columns: 1fr 1fr; } .task-card { margin-top: 15px; } .component-grid { grid-template-columns: repeat(7,minmax(0,1fr)); } .workbench-controls { flex-direction: row; align-items: center; } }
@media (max-width: 640px) { .step-list { grid-template-columns: 1fr; gap: 4px; } .procedure-panel { padding: 18px 14px; } .step-item { padding: 11px 9px; } .workbench-heading { padding: 18px 15px 14px; } .workbench-heading h2 { font-size: 16px; } .workbench-heading .badge { font-size: 9px; } .case-stage { padding: 34px 0 62px; } .pc-case { max-height: none; } .workbench-controls { flex-direction: column; align-items: stretch; padding: 15px; } .parts-tray { padding: 17px 14px; } .component-grid { grid-template-columns: repeat(4,minmax(0,1fr)); } .lab-actions { align-items: stretch; flex-direction: column; } }

</style>
