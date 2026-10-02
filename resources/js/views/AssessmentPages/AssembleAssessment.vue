<template>
  <div class="fn-page">
    <div class="fn-shell">
      <div class="utility-bar">
        <div class="utility-left">
          <button class="back-button" type="button" aria-label="Back to assessments" @click="goBack"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M19 12H5m7-7-7 7 7 7" /></svg></button>
          <div class="breadcrumb"><strong>{{ STATIC_ASSESSMENT.program }}</strong><span class="separator">/</span><span>COC {{ STATIC_ASSESSMENT.coc_number }}</span><span class="separator">/</span><span>Hands-on lab</span></div>
        </div>
        <span class="badge"><span class="badge-dot" aria-hidden="true"></span>Static preview</span>
      </div>

      <header class="page-header">
        <div><span class="eyebrow">{{ STATIC_ASSESSMENT.subject }}</span><h1>{{ STATIC_ASSESSMENT.title }}</h1><p class="page-description">{{ STATIC_ASSESSMENT.description }}</p></div>
        <div class="header-readout">
          <div class="readout-line"><strong class="readout-value">{{ pad(completedCount) }}</strong><span class="readout-total">/ {{ pad(questions.length) }}</span></div>
          <span class="readout-label">Questions submitted</span>
          <div class="progress-track" role="progressbar" aria-label="Assembly assessment completion" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100"><div class="progress-fill" :style="{ width: `${progress}%` }"></div></div>
        </div>
      </header>
      <div class="module-strip"><div class="module-tags"><span class="tag">Sequence builder</span><span class="tag">Virtual hands-on</span><span class="tag">Self-paced</span></div><span class="strip-note">{{ finished ? 'Practice results ready to review.' : 'Build the procedure. Check your thinking.' }}</span></div>

      <div v-if="configurationError" class="config-error" role="alert"><strong>Check the static data</strong><p>{{ configurationError }}</p></div>

      <main v-else-if="finished" class="results-view">
        <section class="surface completion-card">
          <span class="eyebrow">Assessment complete / sample results</span>
          <h2 ref="summaryHeading" tabindex="-1">Your assembly practice, reviewed.</h2>
          <p>These results use the sample answer keys in this file. They are not an official competency rating.</p>
          <div class="summary-metrics"><div><strong>{{ overallScore }}</strong><span>of {{ overallPossible }} positions correct</span></div><div><strong>{{ overallPercent }}%</strong><span>Sample sequence score</span></div><div><strong>{{ questions.length }}</strong><span>Questions completed</span></div></div>
        </section>
        <div class="question-results">
          <article v-for="(question, index) in questions" :key="question.id" class="surface question-result">
            <span class="eyebrow">Question {{ pad(index + 1) }} / {{ question.category }}</span>
            <h3>{{ question.question_text }}</h3>
            <p>{{ results[String(question.id)]?.score ?? 0 }} / {{ question.items.length }} positions correct</p>
            <button type="button" class="text-button" @click="loadQuestion(index)">Review this sequence &rarr;</button>
          </article>
        </div>
        <div class="results-actions"><button type="button" class="secondary-button" @click="restartAssessment">&#8635; Practice again</button><button type="button" class="primary-button" @click="goBack">Back to assessments &rarr;</button></div>
      </main>

      <main v-else-if="currentQuestion" class="assessment-content">
        <section class="surface scenario-card" aria-labelledby="question-heading">
          <div class="scenario-top"><div class="module-tags"><span class="tag">{{ currentQuestion.category }}</span><span class="tag">{{ currentQuestion.difficulty }}</span></div><span class="question-index">Question {{ pad(questionIndex + 1) }} / {{ pad(questions.length) }}</span></div>
          <span class="eyebrow">{{ currentQuestion.scenario_label || 'Service scenario' }}</span>
          <h2 id="question-heading">{{ currentQuestion.question_text }}</h2>
          <p>{{ currentQuestion.instructions }}</p>
          <div class="scenario-bottom"><span>{{ items.length }} steps to arrange</span><span>1 point per correct position</span><span>{{ submitted ? 'Submitted / review mode' : 'Editable until submission' }}</span></div>
        </section>

        <div class="workspace">
          <section class="surface items-panel" aria-labelledby="tray-heading">
            <div class="panel-heading"><div><div class="section-label"><span class="section-number">01</span>Technician tray</div><h2 id="tray-heading">Available steps</h2><p>Tap a card to add it, or drag it into the workbench.</p></div><span class="count-chip">{{ availableItems.length }}</span></div>
            <div ref="trayList" class="items-list">
              <button v-for="item in availableItems" :key="item.id" class="tray-item" type="button"
                :disabled="submitted" :draggable="!submitted" :aria-label="`Add step: ${item.item_text}`"
                @click="placeItem(item, $event)" @dragstart="startDrag(item.id, $event)" @dragend="clearDrag">
                <span class="drag-grip" aria-hidden="true">&#8942;&#8942;</span>
                <img v-if="item.item_image_path && !brokenImages.has(String(item.id))" class="item-image" :src="item.item_image_path" alt="" @error="brokenImages.add(String(item.id))" />
                <span v-else class="item-symbol" aria-hidden="true">{{ item.short || 'STEP' }}</span>
                <span class="item-copy"><strong>{{ item.item_text }}</strong><small v-if="item.description">{{ item.description }}</small></span>
                <span class="add-mark" aria-hidden="true">+</span>
              </button>
              <div v-if="!availableItems.length" class="empty-state tray-empty"><span class="empty-icon" aria-hidden="true">&#10003;</span><strong>Everything is on the workbench.</strong><p>{{ submitted ? 'Review your result on the right.' : 'Check the order, then submit your procedure.' }}</p></div>
            </div>
            <div class="tray-footnote"><span class="note-icon" aria-hidden="true">i</span><p>The tray is shuffled for practice. Use the workbench arrows to fine-tune your order.</p></div>
          </section>

          <section class="surface answer-panel" aria-labelledby="workbench-heading">
            <div class="panel-heading"><div><div class="section-label"><span class="section-number">02</span>Procedure workbench</div><h2 id="workbench-heading">Build the sequence</h2><p>{{ submitted ? 'Your submitted order is locked for review.' : 'Arrange the steps from first to last.' }}</p></div><span class="count-chip">{{ answerIds.length }}/{{ items.length }}</span></div>
            <div ref="answerZone" class="drop-zone" :class="{ 'drop-active': !!draggedId, 'is-submitted': submitted }" tabindex="-1"
              @dragover.prevent="allowDrop" @drop.stop.prevent="dropAt(answerIds.length)">
              <div v-if="!answerIds.length" class="empty-state answer-empty"><span class="empty-icon" aria-hidden="true">+</span><strong>Your procedure starts here.</strong><p>Add a step from the tray, then build the rest of the sequence.</p><span class="drop-instruction">CLICK TO ADD &nbsp;/&nbsp; DRAG TO ARRANGE</span></div>
              <ol v-else class="answer-list">
                <li v-for="(item, index) in answerItems" :key="item.id" class="answer-item"
                  :class="{ 'position-correct': submitted && isCorrectPosition(index), 'position-review': submitted && !isCorrectPosition(index), 'is-dragging': draggedId === String(item.id) }"
                  :draggable="!submitted" @dragstart="startDrag(item.id, $event)" @dragend="clearDrag"
                  @dragover.stop.prevent="allowDrop" @drop.stop.prevent="dropAt(index)">
                  <span class="answer-number">{{ pad(index + 1) }}</span>
                  <div class="answer-copy"><strong>{{ item.item_text }}</strong><span v-if="submitted" class="position-label">{{ isCorrectPosition(index) ? 'Correct position' : `Review: sample position ${expectedPosition(item.id)}` }}</span></div>
                  <span v-if="submitted" class="result-mark" :aria-label="isCorrectPosition(index) ? 'Correct position' : 'Different position'"><span aria-hidden="true">{{ isCorrectPosition(index) ? '\u2713' : '\u2194' }}</span></span>
                  <div v-else class="answer-controls">
                    <button type="button" :disabled="index === 0" :aria-label="`Move ${item.item_text} up`" title="Move up" @click.stop="moveAnswer(index, -1)"><span aria-hidden="true">&uarr;</span></button>
                    <button type="button" :disabled="index === answerIds.length - 1" :aria-label="`Move ${item.item_text} down`" title="Move down" @click.stop="moveAnswer(index, 1)"><span aria-hidden="true">&darr;</span></button>
                    <button type="button" :aria-label="`Return ${item.item_text} to the tray`" title="Return to tray" @click.stop="removeItem(index)"><span aria-hidden="true">&times;</span></button>
                  </div>
                </li>
              </ol>
              <div v-if="answerIds.length && !submitted" class="drop-more" @dragover.stop.prevent="allowDrop" @drop.stop.prevent="dropAt(answerIds.length)"><span aria-hidden="true">+</span> Drop here to move a step to the end</div>
            </div>
            <div class="workbench-status"><span>{{ submitted ? 'Sequence submitted' : answerIds.length === items.length ? 'All steps placed. Ready for review.' : `${items.length - answerIds.length} steps still in the tray` }}</span><span>{{ placementPercent }}%</span></div>
          </section>
        </div>

        <p class="feedback-line assembly-feedback" role="status" aria-live="polite">{{ notice }}</p>
        <section v-if="submitted" class="surface sequence-result" aria-label="Question result">
          <div class="sequence-result-top"><div><span class="eyebrow">Sample sequence result</span><h2>{{ currentResult.score === currentResult.max_score ? 'Every step is in place.' : 'A good moment to review.' }}</h2><p>{{ currentResult.score }} of {{ currentResult.max_score }} steps match the sample answer key.</p></div><div class="question-score"><strong>{{ currentResult.percent }}%</strong><span>Position score</span></div></div>
          <details class="answer-key"><summary>View the sample answer key</summary><ol><li v-for="item in correctItems" :key="item.id">{{ item.item_text }}</li></ol><p>This is editable sample content. Replace it with your trainer-approved procedure before formal use.</p></details>
        </section>
        <div class="assessment-actions">
          <button v-if="!submitted" type="button" class="secondary-button" :disabled="!answerIds.length" @click="resetAnswer">&#8635; Reset sequence</button>
          <button v-else type="button" class="secondary-button" @click="retryQuestion">&#8635; Retry this question</button>
          <div class="next-actions">
            <button v-if="submitted && completedCount === questions.length" type="button" class="secondary-button" @click="finishAssessment">View all results</button>
            <button v-if="!submitted" type="button" class="primary-button" :disabled="!canSubmit" @click="submitAnswer">Submit procedure &rarr;</button>
            <button v-else-if="questionIndex < questions.length - 1" type="button" class="primary-button" @click="loadQuestion(questionIndex + 1)">Next question &rarr;</button>
            <button v-else-if="completedCount < questions.length" type="button" class="primary-button" @click="loadFirstUnanswered">Go to unanswered question &rarr;</button>
          </div>
        </div>
        <div class="safety-note assembly-note"><span class="note-icon" aria-hidden="true">i</span><p><strong>Training preview.</strong> {{ STATIC_ASSESSMENT.safety_note }}</p></div>
      </main>
      <footer class="page-footer"><strong>CSS PREP / PRACTICAL LEARNING</strong><span>Sample data &middot; Progress is temporary and clears on refresh.</span></footer>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

/* ======================================================================
   EDIT STATIC DATA HERE. No Axios or API requests are used in this demo.
   Question wording/answer keys below are NEW sample content, not content
   retrieved from your API or an official equipment/TESDA procedure.
   Keep each question/item ID unique. correct_order must contain every item
   ID exactly once. Numeric or string IDs are supported and normalized.
   The source's COC 1 label is retained; change it to your approved grouping.
   ====================================================================== */
const STATIC_ASSESSMENT = {
  id: 'demo-assembly',
  program: 'CSS PREP',
  coc_number: 1,
  title: 'Computer Assembly',
  subject: 'Virtual Hands-On / Procedure Assessment',
  description: 'Turn individual tasks into a complete procedure. Arrange each sequence, review your decisions, and build confidence before the practical session.',
  safety_note: 'The steps and answer keys are illustrative placeholders, not a universal assembly order. Assume safe preparation and compatible components. Replace them with your trainer-approved, equipment-specific procedure.',
  questions: [
    {
      id: 101,
      category: 'Hardware assembly',
      difficulty: 'Standard',
      scenario_label: 'Bench task / system assembly',
      question_text: 'Arrange the sample computer assembly procedure.',
      instructions: 'You are preparing a workstation from compatible parts. Build the procedure on the workbench, starting with preparation and ending with the final cable check. This exercise uses a fixed sample sequence.',
      items: [
        { id: 'case', short: 'CASE', item_text: 'Prepare the case and verify the mounting points.', description: 'Chassis preparation', item_image_path: null },
        { id: 'cpu', short: 'CPU', item_text: 'Install the processor on the motherboard.', description: 'Processor installation', item_image_path: null },
        { id: 'cooler', short: 'FAN', item_text: 'Fit the compatible CPU cooling assembly.', description: 'Cooling assembly', item_image_path: null },
        { id: 'memory', short: 'RAM', item_text: 'Seat the memory modules in the specified slots.', description: 'Memory installation', item_image_path: null },
        { id: 'board', short: 'MB', item_text: 'Position and secure the motherboard in the case.', description: 'Motherboard mounting', item_image_path: null },
        { id: 'drives', short: 'PSU', item_text: 'Mount the power supply and storage devices.', description: 'Power supply and storage', item_image_path: null },
        { id: 'graphics', short: 'GPU', item_text: 'Install and secure the graphics card.', description: 'Expansion card installation', item_image_path: null },
        { id: 'cables', short: 'I/O', item_text: 'Connect the required cables and inspect the build.', description: 'Final connection check', item_image_path: null }
      ],
      // The tray is shuffled separately; changing its display order never changes the key.
      correct_order: ['case', 'cpu', 'cooler', 'memory', 'board', 'drives', 'graphics', 'cables']
    },
    {
      id: 102,
      category: 'Post-assembly checks',
      difficulty: 'Standard',
      scenario_label: 'Bench task / verification',
      question_text: 'Put the sample verification and handover steps in order.',
      instructions: 'Assembly is complete. Arrange this sample checklist from the unpowered inspection through the documented handover.',
      items: [
        { id: 'inspect', short: 'CHK', item_text: 'Inspect internal connections with power disconnected.', description: 'Unpowered inspection', item_image_path: null },
        { id: 'close', short: 'CASE', item_text: 'Remove loose tools and close the case as specified.', description: 'Prepare for the start-up check', item_image_path: null },
        { id: 'peripherals', short: 'I/O', item_text: 'Connect the monitor, keyboard, and required peripherals.', description: 'External connections', item_image_path: null },
        { id: 'startup', short: 'BOOT', item_text: 'Apply power and verify the planned start-up checks.', description: 'Functional verification', item_image_path: null },
        { id: 'record', short: 'LOG', item_text: 'Record the results and report any unresolved issues.', description: 'Documentation and handover', item_image_path: null }
      ],
      correct_order: ['inspect', 'close', 'peripherals', 'startup', 'record']
    }
  ]
}
/* =========================== END STATIC DATA =========================== */

const emit = defineEmits(['submit', 'complete'])
const route = useRoute()
const router = useRouter()
const questions = Array.isArray(STATIC_ASSESSMENT.questions) ? STATIC_ASSESSMENT.questions : []
const questionIndex = ref(0)
const answerIds = ref([])
const trayOrder = ref([])
const results = ref({})
const draggedId = ref(null)
const finished = ref(false)
const notice = ref('Choose a step from the tray to begin.')
const brokenImages = ref(new Set())
const trayList = ref(null)
const answerZone = ref(null)
const summaryHeading = ref(null)
const pad = value => String(value).padStart(2, '0')

const configurationError = computed(() => {
  if (!questions.length) return 'Add at least one question to STATIC_ASSESSMENT.questions.'
  const questionIds = questions.map(question => String(question.id))
  if (questions.some(question => question.id == null) || new Set(questionIds).size !== questionIds.length) return 'Each question needs a unique ID.'
  for (const question of questions) {
    if (!Array.isArray(question.items) || !question.items.length) return `Question ${question.id} needs at least one item.`
    if (question.items.some(item => item.id == null || !String(item.item_text ?? '').trim())) return `Every item in question ${question.id} needs an ID and item_text.`
    const ids = question.items.map(item => String(item.id))
    const key = Array.isArray(question.correct_order) ? question.correct_order.map(String) : []
    if (new Set(ids).size !== ids.length) return `Question ${question.id} has duplicate item IDs.`
    if (key.length !== ids.length || new Set(key).size !== key.length || key.some(id => !ids.includes(id))) return `Question ${question.id}: correct_order must contain every item ID exactly once.`
  }
  return ''
})
const currentQuestion = computed(() => questions[questionIndex.value] ?? null)
const items = computed(() => Array.isArray(currentQuestion.value?.items) ? currentQuestion.value.items : [])
const itemMap = computed(() => new Map(items.value.map(item => [String(item.id), item])))
const correctOrder = computed(() => (currentQuestion.value?.correct_order ?? []).map(String))
const correctItems = computed(() => correctOrder.value.map(id => itemMap.value.get(id)).filter(Boolean))
const currentResult = computed(() => results.value[String(currentQuestion.value?.id)] ?? null)
const submitted = computed(() => Boolean(currentResult.value))
const answerItems = computed(() => answerIds.value.map(id => itemMap.value.get(id)).filter(Boolean))
const availableItems = computed(() => {
  const selected = new Set(answerIds.value)
  return trayOrder.value.filter(id => !selected.has(id)).map(id => itemMap.value.get(id)).filter(Boolean)
})
const completedCount = computed(() => Object.keys(results.value).length)
const progress = computed(() => questions.length ? Math.round(completedCount.value / questions.length * 100) : 0)
const placementPercent = computed(() => items.value.length ? Math.round(answerIds.value.length / items.value.length * 100) : 0)
const overallScore = computed(() => Object.values(results.value).reduce((sum, result) => sum + result.score, 0))
const overallPossible = computed(() => questions.reduce((sum, question) => sum + (question.items?.length ?? 0), 0))
const overallPercent = computed(() => overallPossible.value ? Math.round(overallScore.value / overallPossible.value * 100) : 0)
const canSubmit = computed(() => !submitted.value && !configurationError.value && items.value.length > 0 && answerIds.value.length === items.value.length && new Set(answerIds.value).size === items.value.length && answerIds.value.every(id => itemMap.value.has(id)))
const isCorrectPosition = index => answerIds.value[index] === correctOrder.value[index]
const expectedPosition = id => correctOrder.value.indexOf(String(id)) + 1

function shuffled(ids) {
  const copy = [...ids]
  for (let index = copy.length - 1; index > 0; index--) {
    const randomIndex = Math.floor(Math.random() * (index + 1))
    ;[copy[index], copy[randomIndex]] = [copy[randomIndex], copy[index]]
  }
  // Keep the initial tray from accidentally presenting the complete answer key.
  if (copy.length > 1 && copy.every((id, index) => id === correctOrder.value[index])) copy.push(copy.shift())
  return copy
}

async function loadQuestion(index) {
  if (!Number.isInteger(index) || index < 0 || index >= questions.length) return
  clearDrag()
  questionIndex.value = index
  finished.value = false
  answerIds.value = [...(currentResult.value?.submitted_order ?? [])]
  trayOrder.value = shuffled(items.value.map(item => String(item.id)))
  notice.value = submitted.value ? 'Reviewing your submitted sequence. Retry to create a new sample result.' : 'Choose a step from the tray to begin.'
}

async function placeItem(item, event) {
  const id = String(item.id)
  if (submitted.value || !itemMap.value.has(id) || answerIds.value.includes(id)) return
  answerIds.value.push(id)
  notice.value = `Step added in position ${answerIds.value.length}. You can change its position with the arrows.`
  // Keep keyboard focus useful when the just-activated tray button disappears.
  if (event?.detail === 0) {
    await nextTick()
    const nextButton = trayList.value?.querySelector('button:not(:disabled)')
    if (nextButton) nextButton.focus()
    else answerZone.value?.focus()
  }
}

function moveAnswer(index, offset) {
  const target = index + offset
  if (submitted.value || index < 0 || index >= answerIds.value.length || target < 0 || target >= answerIds.value.length) return
  const [id] = answerIds.value.splice(index, 1)
  answerIds.value.splice(target, 0, id)
  notice.value = `Step moved to position ${target + 1}.`
}

function removeItem(index) {
  if (submitted.value || index < 0 || index >= answerIds.value.length) return
  answerIds.value.splice(index, 1)
  notice.value = 'Step returned to the tray.'
}

function startDrag(itemId, event) {
  const id = String(itemId)
  if (submitted.value || !itemMap.value.has(id) || !event.dataTransfer) { event.preventDefault(); return }
  draggedId.value = id
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/plain', id)
}

function allowDrop(event) {
  if (event.dataTransfer) event.dataTransfer.dropEffect = !submitted.value && draggedId.value ? 'move' : 'none'
}

function dropAt(targetIndex) {
  const id = draggedId.value
  if (submitted.value || !id || !itemMap.value.has(id)) { clearDrag(); return }
  const list = [...answerIds.value]
  const sourceIndex = list.indexOf(id)
  if (sourceIndex >= 0) {
    list.splice(sourceIndex, 1)
    if (sourceIndex < targetIndex) targetIndex--
  }
  targetIndex = Math.max(0, Math.min(targetIndex, list.length))
  list.splice(targetIndex, 0, id)
  answerIds.value = list
  clearDrag()
  notice.value = `Step placed in position ${targetIndex + 1}.`
}

function clearDrag() { draggedId.value = null }

function resetAnswer() {
  if (submitted.value) return
  clearDrag()
  answerIds.value = []
  notice.value = 'Sequence cleared. The current tray order is unchanged.'
}

function submitAnswer() {
  if (!canSubmit.value) return
  clearDrag()
  const score = answerIds.value.filter((id, index) => id === correctOrder.value[index]).length
  const result = {
    question_id: currentQuestion.value.id,
    submitted_order: [...answerIds.value],
    score,
    max_score: items.value.length,
    percent: Math.round(score / items.value.length * 100),
    submitted_at: new Date().toISOString()
  }
  results.value = { ...results.value, [String(currentQuestion.value.id)]: result }
  notice.value = `Submitted: ${result.score} of ${result.max_score} positions match the sample answer key.`
  // LOCAL DEMO ONLY. Production scores must be calculated by Laravel.
  emit('submit', { assessment_id: STATIC_ASSESSMENT.id, ...result })
}

function retryQuestion() {
  const nextResults = { ...results.value }
  delete nextResults[String(currentQuestion.value.id)]
  results.value = nextResults
  loadQuestion(questionIndex.value)
  notice.value = 'Question restarted. Your previous sample result for this question was cleared.'
}

function loadFirstUnanswered() {
  const index = questions.findIndex(question => !results.value[String(question.id)])
  if (index >= 0) loadQuestion(index)
}

async function finishAssessment() {
  if (completedCount.value !== questions.length || configurationError.value) return
  finished.value = true
  emit('complete', {
    assessment_id: STATIC_ASSESSMENT.id,
    assessment_type: 'assembly',
    score: overallScore.value,
    max_score: overallPossible.value,
    percent: overallPercent.value,
    answers: Object.values(results.value)
  })
  await nextTick()
  summaryHeading.value?.focus()
}

function restartAssessment() {
  results.value = {}
  brokenImages.value = new Set()
  loadQuestion(0)
}

function goBack() {
  const id = route.params.id ?? route.params.quizId
  router.push(id ? `/user/quizzes/assessment/${encodeURIComponent(String(id))}` : '/user/quizzes')
}

watch(() => route.params.id ?? route.params.quizId, restartAssessment)
loadQuestion(0)
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

.scenario-card { padding: 24px 27px; }
.scenario-top { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; }
.question-index { font-size: 11px; font-weight: 650; color: var(--dim-gray); font-variant-numeric: tabular-nums; }
.scenario-card h2 { font-size: clamp(18px, 2.1vw, 24px); font-weight: 700; letter-spacing: -.025em; line-height: 1.4; max-width: 900px; }
.scenario-card > p { color: var(--dim-gray); max-width: 960px; font-size: 12px; line-height: 1.85; margin-top: 10px; }
.scenario-bottom { display: flex; gap: 10px 23px; flex-wrap: wrap; padding-top: 17px; margin-top: 18px; border-top: 1px solid var(--line); color: var(--dim-gray); font-size: 10px; }
.scenario-bottom span::before { content: ''; display: inline-block; width: 4px; height: 4px; border-radius: 50%; background: var(--silver); vertical-align: middle; margin-right: 7px; }
.workspace { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1.1fr); gap: 18px; align-items: start; margin-top: 18px; }
.items-panel, .answer-panel { padding: 24px 21px 19px; }
.items-list { display: flex; flex-direction: column; gap: 8px; }
.tray-item { width: 100%; display: flex; align-items: center; gap: 11px; padding: 13px 11px; min-height: 81px; border: 1px solid var(--line); border-radius: 11px; background: rgba(211,211,211,.09); color: var(--black); text-align: left; cursor: grab !important; }
.tray-item:hover:not(:disabled) { border-color: var(--silver); background: var(--white); box-shadow: 0 4px 13px rgba(0,0,0,.025); }
.tray-item:active:not(:disabled) { cursor: grabbing !important; }
.drag-grip { color: var(--silver); font-size: 17px; letter-spacing: -5px; padding-right: 4px; flex-shrink: 0; }
.item-symbol, .item-image { display: grid; place-items: center; width: 36px; height: 36px; flex-shrink: 0; border: 1px solid var(--line); border-radius: 9px; background: var(--white); color: var(--dim-gray); font-size: 8px; font-weight: 750; object-fit: contain; }
.item-copy { flex: 1; min-width: 0; }
.item-copy strong { display: block; font-size: 12px; font-weight: 620; line-height: 1.65; }
.item-copy small { display: block; margin-top: 3px; color: var(--dim-gray); font-size: 10px; }
.add-mark { display: grid; place-items: center; width: 25px; height: 25px; flex-shrink: 0; border-radius: 7px; border: 1px solid var(--line); background: var(--white); font-size: 17px; font-weight: 400; color: var(--dim-gray); }
.tray-footnote { display: flex; align-items: flex-start; gap: 9px; color: var(--dim-gray); font-size: 10px; line-height: 1.8; border-top: 1px solid var(--line); padding-top: 15px; margin-top: 18px; }
.tray-footnote .note-icon { width: 16px; height: 16px; font-size: 9px; }
.drop-zone { min-height: 420px; border: 1px dashed var(--silver); border-radius: 12px; background: linear-gradient(rgba(211,211,211,.2) 1px, transparent 1px), linear-gradient(90deg,rgba(211,211,211,.2) 1px,transparent 1px), rgba(255,255,255,.8); background-size: 20px 20px; padding: 9px; }
.drop-zone.drop-active { border-color: var(--black); border-width: 2px; }
.drop-zone.is-submitted { background: var(--soft); border-style: solid; }
.answer-empty { min-height: 397px; }
.drop-instruction { margin-top: 12px; font-size: 8px; letter-spacing: .1em; font-weight: 700; color: var(--dim-gray); }
.answer-list { display: flex; flex-direction: column; gap: 8px; padding: 0; margin: 0; list-style: none; }
.answer-item { display: flex; gap: 10px; align-items: center; padding: 13px 11px; min-height: 81px; border: 1px solid var(--line); border-radius: 10px; background: var(--white); }
.answer-item[draggable="true"] { cursor: grab; }
.answer-item[draggable="true"]:hover { border-color: var(--silver); }
.answer-item.is-dragging { opacity: .4; }
.answer-number { display: grid; place-items: center; width: 28px; height: 28px; flex-shrink: 0; border: 1px solid var(--line); background: var(--soft); color: var(--black); border-radius: 8px; font-size: 10px; font-weight: 750; }
.answer-copy { min-width: 0; flex: 1; }
.answer-copy strong { font-size: 12px; line-height: 1.7; font-weight: 620; display: block; }
.answer-controls { display: grid; grid-template-columns: 30px 30px; gap: 4px; flex-shrink: 0; }
.answer-controls button { border: 1px solid var(--line); min-width: 30px; min-height: 30px; border-radius: 6px; background: var(--white); color: var(--dim-gray); font-size: 14px; line-height: 1; }
.answer-controls button:last-child { grid-column: span 2; min-height: 24px; }
.answer-controls button:hover:not(:disabled) { border-color: var(--dim-gray); background: var(--soft); color: var(--black); }
.answer-controls button:disabled { opacity: .35; }
.position-correct { border-color: var(--dim-gray); }
.position-review { border-style: dashed; border-color: var(--silver); }
.position-label { display: block; font-size: 10px; margin-top: 5px; color: var(--dim-gray); }
.position-correct .position-label { color: var(--black); }
.result-mark { flex-shrink: 0; display: grid; place-items: center; width: 24px; height: 24px; border: 1px solid var(--line); border-radius: 7px; color: var(--dim-gray); background: var(--soft); }
.position-correct .result-mark { color: var(--black); }
.drop-more { padding: 13px 8px; margin-top: 9px; display: flex; gap: 7px; align-items: center; justify-content: center; font-size: 10px; color: var(--dim-gray); border: 1px dashed var(--silver); border-radius: 8px; background: rgba(255,255,255,.75); }
.drop-more span { font-size: 17px; }
.workbench-status { display: flex; justify-content: space-between; gap: 12px; font-size: 10px; color: var(--dim-gray); margin-top: 14px; }
.assembly-feedback { margin: 14px 2px 0 !important; }
.assessment-actions { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 16px; }
.next-actions { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; justify-content: flex-end; }
.assembly-note { margin-top: 19px; }
.sequence-result { padding: 22px 25px; margin-top: 14px; }
.sequence-result-top { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.sequence-result h2 { font-size: 20px; letter-spacing: -.03em; }
.sequence-result p { font-size: 12px; color: var(--dim-gray); line-height: 1.8; margin-top: 5px; }
.question-score { text-align: right; }
.question-score strong { display: block; font-size: 32px; font-weight: 670; letter-spacing: -.04em; }
.question-score span { font-size: 10px; color: var(--dim-gray); }
.answer-key { margin-top: 17px; border-top: 1px solid var(--line); padding-top: 13px; }
.answer-key summary { cursor: pointer; font-size: 12px; font-weight: 650; min-height: 34px; }
.answer-key ol { padding-left: 21px; color: var(--dim-gray); font-size: 12px; line-height: 1.8; margin: 6px 0; }
.answer-key li { padding: 4px 0; }
.answer-key > p { font-size: 11px; }
.completion-card { padding: 30px; }
.completion-card h2 { font-size: clamp(24px,3vw,32px); letter-spacing: -.04em; }
.completion-card > p { margin-top: 10px; max-width: 770px; font-size: 13px; color: var(--dim-gray); line-height: 1.8; }
.summary-metrics { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 25px; border-top: 1px solid var(--line); margin-top: 27px; padding-top: 22px; }
.summary-metrics strong { display: block; font-size: 38px; font-weight: 650; letter-spacing: -.05em; }
.summary-metrics span { display: block; color: var(--dim-gray); font-size: 12px; margin-top: 5px; }
.question-results { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 17px; margin-top: 18px; }
.question-result { padding: 23px; }
.question-result h3 { font-size: 17px; line-height: 1.5; letter-spacing: -.02em; }
.question-result p { font-size: 12px; color: var(--dim-gray); margin-top: 13px; }
.question-result .text-button { margin-top: 8px; }
.results-actions { display: flex; justify-content: space-between; gap: 12px; margin-top: 20px; }
@media (max-width: 900px) { .workspace { grid-template-columns: 1fr; } .items-list { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); } .tray-empty { grid-column: 1/-1; } .tray-item { gap: 8px; } .drag-grip { display: none; } .answer-controls { display: flex; } .answer-controls button { min-height: 34px; min-width: 34px; } .drop-zone { min-height: 300px; } .answer-empty { min-height: 276px; } }
@media (max-width: 640px) { .scenario-card { padding: 19px 16px; } .scenario-top { margin-bottom: 15px; } .scenario-bottom { gap: 8px 15px; } .items-panel, .answer-panel { padding: 19px 14px 16px; } .items-list { grid-template-columns: 1fr; } .tray-item { padding: 12px 10px; gap: 10px; min-height: 76px; } .tray-item .item-symbol { width: 33px; height: 33px; } .answer-item { padding: 12px 9px; flex-wrap: wrap; } .answer-number { align-self: flex-start; margin-top: 3px; } .answer-copy { min-width: calc(100% - 42px); } .answer-controls { margin-left: 38px; } .answer-controls button { min-width: 36px; min-height: 32px; } .result-mark { margin-left: 38px; } .position-label { margin-top: 7px; } .assessment-actions { align-items: stretch; flex-direction: column-reverse; } .next-actions { flex-direction: column; align-items: stretch; } .drop-zone { padding: 7px; } .sequence-result { padding: 18px 16px; } .sequence-result-top { align-items: flex-start; gap: 12px; } .sequence-result h2 { font-size: 17px; } .question-score strong { font-size: 28px; } .completion-card { padding: 22px 18px; } .summary-metrics { gap: 15px; grid-template-columns: 1fr; } .summary-metrics > div { display: flex; align-items: baseline; gap: 13px; } .summary-metrics strong { font-size: 32px; } .question-results { grid-template-columns: 1fr; } .results-actions { flex-direction: column; } }

</style>
