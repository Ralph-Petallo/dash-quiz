<template>
  <Transition name="fade">
    <div v-if="modelValue" class="overlay" @click.self="close">
      <section ref="sheet" class="sheet" role="dialog" aria-modal="true" aria-labelledby="record-title" tabindex="-1">
        <!-- HEADER -->
        <header class="sheet-head">
          <div class="head-text">
            <p class="kicker">Record {{ record?.id ? `#${record.id}` : '' }}</p>
            <h2 id="record-title">{{ record?.quiz_title || 'Assessment record' }}</h2>
            <p class="meta">Completed {{ formatDateTime(record?.completed_at || record?.created_at) }}</p>
          </div>

          <button ref="closeButton" type="button" class="icon-close" aria-label="Close" @click="close">
            <i class="fas fa-xmark" aria-hidden="true"></i>
          </button>
        </header>

        <div v-if="record" class="sheet-body">
          <!-- RESULT -->
          <section class="result">
            <div class="result-score">
              <span class="label">Score</span>
              <strong>{{ formatPercentage(record.percentage) }}</strong>
            </div>

            <div class="result-side">
              <span class="verdict" :class="passed ? 'is-pass' : 'is-fail'">
                <i :class="passed ? 'fas fa-check' : 'fas fa-xmark'" aria-hidden="true"></i>
                {{ statusText }}
              </span>
              <span class="label">Passing mark {{ formatPercentage(record.passing_score) }}</span>
            </div>

            <div class="track" role="progressbar" :aria-valuenow="clampPercentage(record.percentage)" aria-valuemin="0"
              aria-valuemax="100">
              <span :style="{ width: `${clampPercentage(record.percentage)}%` }"
                :class="passed ? 'is-pass' : 'is-fail'"></span>
              <i class="mark" :style="{ left: `${clampPercentage(record.passing_score)}%` }" aria-hidden="true"></i>
            </div>
          </section>

          <!-- FIGURES -->
          <dl class="figures">
            <div>
              <dt>Raw score</dt>
              <dd>{{ displayScore(record) }}</dd>
            </div>
            <div>
              <dt>Attempt</dt>
              <dd>{{ record.attempt_count ? `No. ${record.attempt_count}` : '—' }}</dd>
            </div>
            <div>
              <dt>Duration</dt>
              <dd>{{ formatElapsed(record.elapsed_time) }}</dd>
            </div>
          </dl>

          <!-- DETAILS -->
          <section class="block">
            <h3>Details</h3>
            <dl class="rows">
              <div>
                <dt>Competency</dt>
                <dd>{{ formatCoc(record.coc_number) }}</dd>
              </div>
              <div>
                <dt>Type</dt>
                <dd>{{ formatLabel(record.quiz_type) }}</dd>
              </div>
              <div>
                <dt>Category</dt>
                <dd>{{ record.category || 'Not specified' }}</dd>
              </div>
              <div>
                <dt>Completed</dt>
                <dd>{{ formatDateTime(record.completed_at || record.created_at) }}</dd>
              </div>
              <div v-if="record.quiz_description" class="wide">
                <dt>Description</dt>
                <dd>{{ record.quiz_description }}</dd>
              </div>
            </dl>
          </section>

          <!-- QUESTION REVIEW -->
          <section v-if="questions.length" class="block">
            <div class="block-head">
              <h3>Question review</h3>
              <span class="count">{{ correctCount }} of {{ questions.length }} correct</span>
            </div>

            <ol class="review">
              <li v-for="(question, index) in questions" :key="question.question_id || index" class="review-item"
                :class="question.is_correct ? 'is-correct' : 'is-wrong'">
                <div class="review-top">
                  <span class="index">{{ index + 1 }}</span>
                  <p class="question">{{ question.question || 'Question unavailable' }}</p>
                  <span class="outcome">{{ question.is_correct ? 'Correct' : 'Incorrect' }}</span>
                </div>

                <dl class="answers">
                  <div>
                    <dt>Your answer</dt>
                    <dd>{{ question.user_answer || 'No answer' }}</dd>
                  </div>
                  <div>
                    <dt>Correct answer</dt>
                    <dd>{{ question.correct_answer || '—' }}</dd>
                  </div>
                </dl>
              </li>
            </ol>
          </section>

          <!-- PRACTICAL NOTE -->
          <section v-else class="block">
            <h3>Practical activity</h3>
            <p class="note">
              This activity has no question review. The score, timing, and attempt above are the recorded result.
            </p>
          </section>
        </div>

        <!-- FOOTER -->
        <footer class="sheet-foot">
          <div class="export">
            <span class="label">Export</span>
            <button type="button" class="text-btn" :disabled="!record || downloading" @click="downloadPdf">
              <i :class="downloading ? 'fas fa-spinner fa-spin' : 'far fa-file-pdf'" aria-hidden="true"></i>
              PDF
            </button>
            <button type="button" class="text-btn" :disabled="!record" @click="downloadCsv">
              <i class="far fa-file-lines" aria-hidden="true"></i>
              CSV
            </button>
            <p v-if="downloadError" class="error" role="alert">{{ downloadError }}</p>
          </div>

          <button type="button" class="done-btn" @click="close">Done</button>
        </footer>
      </section>
    </div>
  </Transition>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { jsPDF } from 'jspdf'

const props = defineProps({
  modelValue: Boolean,
  record: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue'])

const DEFAULT_PASSING_SCORE = 75

const sheet = ref(null)
const closeButton = ref(null)
const downloading = ref(false)
const downloadError = ref('')

const close = () => emit('update:modelValue', false)

/* =========================================================
   DERIVED DATA
========================================================= */

const questions = computed(() =>
  Array.isArray(props.record?.questions) ? props.record.questions : []
)

const correctCount = computed(() =>
  questions.value.filter((question) => Boolean(question.is_correct)).length
)

const passed = computed(() => {
  const status = String(props.record?.status || '').toLowerCase()

  if (status.includes('fail')) return false
  if (status.includes('pass')) return true

  return Number(props.record?.percentage ?? 0) >=
    Number(props.record?.passing_score ?? DEFAULT_PASSING_SCORE)
})

const statusText = computed(() => (passed.value ? 'Passed' : 'Not passed'))

const fileBase = computed(() => `css-prep-record-${props.record?.id ?? 'unknown'}`)

/* =========================================================
   FORMATTERS
========================================================= */

const clampPercentage = (value) => {
  const number = Number(value)
  if (!Number.isFinite(number)) return 0
  return Math.min(100, Math.max(0, number))
}

const formatPercentage = (value) => {
  const number = Number(value)
  if (!Number.isFinite(number)) return '0%'
  return `${Number.isInteger(number) ? number : number.toFixed(1)}%`
}

const displayScore = (record) => {
  const score = record?.correct_answers ?? record?.score
  const total = Number(record?.total_questions ?? 0)

  if (score !== null && score !== undefined && total > 0) return `${score} / ${total}`
  return score !== null && score !== undefined ? String(score) : '—'
}

const formatElapsed = (seconds) => {
  if (seconds === null || seconds === undefined || Number.isNaN(Number(seconds))) return '—'

  const total = Math.max(0, Math.floor(Number(seconds)))
  const hours = Math.floor(total / 3600)
  const minutes = Math.floor((total % 3600) / 60)
  const secs = total % 60

  if (hours > 0) return `${hours}h ${minutes}m`
  if (minutes > 0) return `${minutes}m ${secs}s`
  return `${secs}s`
}

const formatDateTime = (date) => {
  if (!date) return '—'

  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return '—'

  return parsed.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

const formatLabel = (value) => {
  if (!value) return '—'

  return String(value)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

const formatCoc = (value) =>
  value === null || value === undefined || value === '' ? '—' : `COC ${value}`

/* =========================================================
   DOWNLOADS
========================================================= */

function saveBlob(blob, filename) {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()

  setTimeout(() => URL.revokeObjectURL(url), 1000)
}

const PAGE_MARGIN = 48

// Standard PDF fonts only, so keep text to plain characters.
function buildPdf() {
  const record = props.record
  const doc = new jsPDF({ unit: 'pt', format: 'a4' })

  const pageWidth = doc.internal.pageSize.getWidth()
  const pageHeight = doc.internal.pageSize.getHeight()
  const contentWidth = pageWidth - PAGE_MARGIN * 2
  const labelWidth = 130

  let y = PAGE_MARGIN

  const ensureSpace = (height) => {
    if (y + height > pageHeight - PAGE_MARGIN) {
      doc.addPage()
      y = PAGE_MARGIN
    }
  }

  const writeBlock = (text, { size = 10, style = 'normal', color = [35, 35, 35], gap = 6 } = {}) => {
    doc.setFont('helvetica', style)
    doc.setFontSize(size)
    doc.setTextColor(...color)

    const lines = doc.splitTextToSize(String(text ?? '—'), contentWidth)
    const height = lines.length * size * 1.35

    ensureSpace(height)
    doc.text(lines, PAGE_MARGIN, y + size)
    y += height + gap
  }

  const rule = () => {
    ensureSpace(14)
    doc.setDrawColor(215)
    doc.line(PAGE_MARGIN, y, pageWidth - PAGE_MARGIN, y)
    y += 14
  }

  const row = (label, value) => {
    const lines = doc.splitTextToSize(String(value ?? '—'), contentWidth - labelWidth)
    const height = lines.length * 14

    ensureSpace(height + 6)

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(8.5)
    doc.setTextColor(120)
    doc.text(label.toUpperCase(), PAGE_MARGIN, y + 10)

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(10.5)
    doc.setTextColor(30)
    doc.text(lines, PAGE_MARGIN + labelWidth, y + 10)

    y += height + 6
  }

  writeBlock('CSS Prep', { size: 10, style: 'bold', color: [110, 110, 110], gap: 2 })
  writeBlock(record?.quiz_title || 'Assessment record', { size: 18, style: 'bold', gap: 4 })
  writeBlock(`Record #${record?.id ?? '—'}  |  Generated ${new Date().toLocaleString('en-US')}`, {
    size: 9,
    color: [120, 120, 120],
    gap: 10,
  })
  rule()

  writeBlock('Result', { size: 12, style: 'bold', gap: 8 })
  row('Score', formatPercentage(record?.percentage))
  row('Status', statusText.value)
  row('Raw score', displayScore(record))
  row('Passing mark', formatPercentage(record?.passing_score))
  row('Attempt', record?.attempt_count ? `No. ${record.attempt_count}` : '—')
  row('Duration', formatElapsed(record?.elapsed_time))
  row('Completed', formatDateTime(record?.completed_at || record?.created_at))
  y += 6
  rule()

  writeBlock('Details', { size: 12, style: 'bold', gap: 8 })
  row('Competency', formatCoc(record?.coc_number))
  row('Type', formatLabel(record?.quiz_type))
  row('Category', record?.category || 'Not specified')
  if (record?.quiz_description) row('Description', record.quiz_description)
  y += 6

  if (questions.value.length) {
    rule()
    writeBlock(`Question review (${correctCount.value} of ${questions.value.length} correct)`, {
      size: 12,
      style: 'bold',
      gap: 8,
    })

    questions.value.forEach((question, index) => {
      writeBlock(`${index + 1}. ${question.question || 'Question unavailable'}`, {
        size: 10,
        style: 'bold',
        gap: 3,
      })
      writeBlock(`Your answer: ${question.user_answer || 'No answer'}`, {
        size: 9.5,
        color: [70, 70, 70],
        gap: 2,
      })
      writeBlock(`Correct answer: ${question.correct_answer || '—'}`, {
        size: 9.5,
        color: [70, 70, 70],
        gap: 2,
      })
      writeBlock(`Result: ${question.is_correct ? 'Correct' : 'Incorrect'}`, {
        size: 9.5,
        style: 'bold',
        color: question.is_correct ? [17, 129, 61] : [180, 35, 24],
        gap: 10,
      })
    })
  } else {
    rule()
    writeBlock('Practical activity', { size: 12, style: 'bold', gap: 6 })
    writeBlock(
      'This activity has no question review. The score, timing, and attempt above are the recorded result.',
      { size: 9.5, color: [90, 90, 90] }
    )
  }

  const totalPages = doc.getNumberOfPages()

  for (let page = 1; page <= totalPages; page++) {
    doc.setPage(page)
    doc.setFont('helvetica', 'normal')
    doc.setFontSize(8)
    doc.setTextColor(140)
    doc.text(`CSS Prep  |  Page ${page} of ${totalPages}`, pageWidth / 2, pageHeight - 22, {
      align: 'center',
    })
  }

  return doc
}

function downloadPdf() {
  if (!props.record) return

  downloading.value = true
  downloadError.value = ''

  try {
    buildPdf().save(`${fileBase.value}.pdf`)
  } catch (error) {
    console.error('PDF download failed:', error)
    downloadError.value = 'Could not create the PDF. Please try again.'
  } finally {
    downloading.value = false
  }
}

const csvEscape = (value) => {
  const text = value === null || value === undefined ? '' : String(value)
  return /[",\r\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text
}

function downloadCsv() {
  const record = props.record
  if (!record) return

  downloadError.value = ''

  const summary = [
    ['Field', 'Value'],
    ['Record ID', record.id],
    ['Assessment', record.quiz_title],
    ['Type', record.quiz_type],
    ['COC', record.coc_number],
    ['Category', record.category],
    ['Status', statusText.value],
    ['Percentage', record.percentage],
    ['Passing score', record.passing_score],
    ['Raw score', displayScore(record)],
    ['Attempt', record.attempt_count],
    ['Duration (seconds)', record.elapsed_time],
    ['Completed', record.completed_at || record.created_at],
  ]

  const review = [
    ['Question no.', 'Question', 'Your answer', 'Correct answer', 'Result'],
    ...questions.value.map((question, index) => [
      index + 1,
      question.question,
      question.user_answer,
      question.correct_answer,
      question.is_correct ? 'Correct' : 'Incorrect',
    ]),
  ]

  const lines = [...summary, [], ...review].map((row) => row.map(csvEscape).join(','))

  saveBlob(
    new Blob(['\uFEFF' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' }),
    `${fileBase.value}.csv`
  )
}

/* =========================================================
   MODAL BEHAVIOUR
========================================================= */

// Move focus into the dialog when it opens, and return it when it closes.
let lastFocused = null

watch(
  () => props.modelValue,
  async (open) => {
    document.body.style.overflow = open ? 'hidden' : ''

    if (open) {
      lastFocused = document.activeElement
      await nextTick()
      closeButton.value?.focus()
    } else {
      downloadError.value = ''
      lastFocused?.focus?.()
    }
  }
)

const handleKeydown = (event) => {
  if (!props.modelValue) return

  if (event.key === 'Escape') close()

  // Keep Tab inside the dialog.
  if (event.key === 'Tab' && sheet.value) {
    const focusable = [...sheet.value.querySelectorAll('button:not([disabled])')]
    if (!focusable.length) return

    const first = focusable[0]
    const last = focusable[focusable.length - 1]

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault()
      last.focus()
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault()
      first.focus()
    }
  }
}

onMounted(() => window.addEventListener('keydown', handleKeydown))

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>

<style scoped>
.overlay,
.overlay *,
.overlay *::before,
.overlay *::after {
  box-sizing: border-box;
}

.overlay {
  --ink: #171717;
  --text: #333;
  --muted: #6b6b6b;
  --faint: #9a9a9a;
  --line: #ebebeb;
  --line-strong: #d6d6d6;
  --paper: #ffffff;
  --pass: #11813d;
  --fail: #b42318;

  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: rgba(17, 17, 17, 0.42);
  font-family: inherit;
  color: var(--text);
}

.overlay h2,
.overlay h3,
.overlay p,
.overlay dl,
.overlay dd,
.overlay ol {
  margin: 0;
}

.overlay ol {
  padding: 0;
  list-style: none;
}

.overlay button {
  font: inherit;
  cursor: pointer;
}

.overlay button:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.overlay :focus-visible {
  outline: 2px solid var(--ink);
  outline-offset: 2px;
}

.label,
.kicker,
dt {
  color: var(--muted);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.02em;
}

/* ================= SHEET ================= */

.sheet {
  width: min(720px, 100%);
  max-height: min(88vh, 860px);
  display: flex;
  flex-direction: column;
  background: var(--paper);
  border: 1px solid var(--line-strong);
  border-radius: 14px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18);
  outline: none;
  overflow: hidden;
}

/* ================= HEADER ================= */

.sheet-head {
  flex: 0 0 auto;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 22px 24px 18px;
  border-bottom: 1px solid var(--line);
}

.head-text {
  min-width: 0;
}

.kicker {
  margin: 0 0 6px;
}

.sheet-head h2 {
  color: var(--ink);
  font-size: 19px;
  font-weight: 700;
  line-height: 1.3;
  letter-spacing: -0.01em;
  overflow-wrap: anywhere;
}

.meta {
  margin-top: 4px;
  color: var(--muted);
  font-size: 12px;
}

.icon-close {
  width: 32px;
  height: 32px;
  flex: 0 0 32px;
  display: grid;
  place-items: center;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: var(--paper);
  color: var(--muted);
}

.icon-close:hover:not(:disabled) {
  border-color: var(--ink);
  color: var(--ink);
}

/* ================= BODY ================= */

.sheet-body {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: 22px 24px 8px;
}

/* RESULT */

.result {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  align-items: end;
  gap: 4px 24px;
  padding-bottom: 20px;
}

.result-score {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.result-score strong {
  color: var(--ink);
  font-size: 46px;
  font-weight: 700;
  line-height: 1;
  letter-spacing: -0.03em;
  font-variant-numeric: tabular-nums;
}

.result-side {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
  text-align: right;
}

.verdict {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 700;
}

.verdict.is-pass {
  color: var(--pass);
}

.verdict.is-fail {
  color: var(--fail);
}

.track {
  grid-column: 1 / -1;
  position: relative;
  height: 6px;
  margin-top: 14px;
  border-radius: 999px;
  background: #efefef;
}

.track>span {
  display: block;
  height: 100%;
  border-radius: inherit;
  transition: width 0.4s ease;
}

.track>span.is-pass {
  background: var(--pass);
}

.track>span.is-fail {
  background: var(--fail);
}

.track .mark {
  position: absolute;
  top: -4px;
  width: 2px;
  height: 14px;
  margin-left: -1px;
  background: var(--ink);
}

/* FIGURES */

.figures {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  border-top: 1px solid var(--line);
  border-bottom: 1px solid var(--line);
}

.figures>div {
  min-width: 0;
  padding: 14px 0;
}

.figures>div+div {
  padding-left: 16px;
  border-left: 1px solid var(--line);
}

.figures dd {
  margin-top: 4px;
  color: var(--ink);
  font-size: 15px;
  font-weight: 650;
  font-variant-numeric: tabular-nums;
}

/* BLOCKS */

.block {
  padding: 22px 0 6px;
}

.block h3 {
  margin-bottom: 12px;
  color: var(--ink);
  font-size: 13px;
  font-weight: 700;
}

.block-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.block-head h3 {
  margin: 0;
}

.count {
  color: var(--muted);
  font-size: 12px;
  white-space: nowrap;
}

/* DETAILS */

.rows {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0;
  border-top: 1px solid var(--line);
}

.rows>div {
  min-width: 0;
  padding: 11px 0;
  border-bottom: 1px solid var(--line);
}

.rows>div:nth-child(odd) {
  padding-right: 16px;
}

.rows>div:nth-child(even) {
  padding-left: 16px;
  border-left: 1px solid var(--line);
}

.rows>div.wide {
  grid-column: 1 / -1;
  padding-left: 0;
  padding-right: 0;
  border-left: 0;
}

.rows dd {
  margin-top: 3px;
  color: var(--text);
  font-size: 13px;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

/* REVIEW */

.review {
  display: flex;
  flex-direction: column;
  gap: 0;
  border-top: 1px solid var(--line);
}

.review-item {
  padding: 14px 0 14px 14px;
  border-bottom: 1px solid var(--line);
  border-left: 2px solid transparent;
}

.review-item.is-correct {
  border-left-color: var(--pass);
}

.review-item.is-wrong {
  border-left-color: var(--fail);
}

.review-top {
  display: grid;
  grid-template-columns: 22px minmax(0, 1fr) auto;
  align-items: start;
  gap: 10px;
}

.index {
  color: var(--faint);
  font-size: 12px;
  font-variant-numeric: tabular-nums;
  padding-top: 1px;
}

.question {
  color: var(--ink);
  font-size: 13px;
  font-weight: 600;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

.outcome {
  font-size: 12px;
  font-weight: 650;
  white-space: nowrap;
}

.is-correct .outcome {
  color: var(--pass);
}

.is-wrong .outcome {
  color: var(--fail);
}

.answers {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin: 10px 0 0 32px;
}

.answers dd {
  margin-top: 2px;
  color: var(--text);
  font-size: 12.5px;
  line-height: 1.45;
  overflow-wrap: anywhere;
}

.note {
  color: var(--muted);
  font-size: 13px;
  line-height: 1.6;
  padding-bottom: 14px;
}

/* ================= FOOTER ================= */

.sheet-foot {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px 16px;
  padding: 14px 24px;
  border-top: 1px solid var(--line);
  background: #fafafa;
}

.export {
  min-width: 0;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px 14px;
}

.text-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 0;
  border: 0;
  background: none;
  color: var(--ink);
  font-size: 12.5px;
  font-weight: 600;
  text-decoration: underline;
  text-decoration-color: var(--line-strong);
  text-underline-offset: 4px;
}

.text-btn:hover:not(:disabled) {
  text-decoration-color: var(--ink);
}

.error {
  flex-basis: 100%;
  color: var(--fail);
  font-size: 12px;
}

.done-btn {
  min-width: 96px;
  height: 38px;
  padding: 0 18px;
  border: 1px solid var(--ink);
  border-radius: 8px;
  background: var(--ink);
  color: #fff;
  font-size: 13px;
  font-weight: 650;
}

.done-btn:hover {
  background: #333;
}

/* ================= TRANSITION ================= */

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-active .sheet,
.fade-leave-active .sheet {
  transition: transform 0.15s ease, opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.fade-enter-from .sheet,
.fade-leave-to .sheet {
  opacity: 0;
  transform: translateY(6px);
}

/* ================= RESPONSIVE ================= */

@media (max-width: 600px) {
  .overlay {
    padding: 0;
    align-items: flex-end;
  }

  .sheet {
    width: 100%;
    max-height: 92vh;
    border-radius: 16px 16px 0 0;
    border-bottom: 0;
  }

  .sheet-head {
    padding: 18px 18px 14px;
  }

  .sheet-head h2 {
    font-size: 17px;
  }

  .sheet-body {
    padding: 18px 18px 6px;
  }

  .result {
    grid-template-columns: minmax(0, 1fr);
  }

  .result-side {
    align-items: flex-start;
    text-align: left;
    margin-top: 6px;
  }

  .result-score strong {
    font-size: 38px;
  }

  .figures>div {
    padding: 12px 0;
  }

  .figures>div+div {
    padding-left: 12px;
  }

  .figures dd {
    font-size: 13.5px;
  }

  .rows {
    grid-template-columns: minmax(0, 1fr);
  }

  .rows>div:nth-child(odd),
  .rows>div:nth-child(even) {
    padding-left: 0;
    padding-right: 0;
    border-left: 0;
  }

  .answers {
    grid-template-columns: minmax(0, 1fr);
    gap: 8px;
  }

  .sheet-foot {
    padding: 12px 18px calc(12px + env(safe-area-inset-bottom, 0px));
  }

  .export {
    width: 100%;
  }

  .done-btn {
    width: 100%;
    height: 44px;
  }
}

@media (prefers-reduced-motion: reduce) {

  .fade-enter-active,
  .fade-leave-active,
  .fade-enter-active .sheet,
  .fade-leave-active .sheet {
    transition: none;
  }
}
</style>