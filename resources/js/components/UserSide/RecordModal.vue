<template>
  <Transition name="fade">
    <div
      v-if="modelValue"
      class="modal-overlay"
      role="dialog"
      aria-modal="true"
      aria-labelledby="record-modal-title"
      @click.self="close"
    >
      <div class="modal-content">
        <div class="modal-header">
          <div class="title-group">
            <div class="title-icon">
              <i class="fas fa-clipboard-check"></i>
            </div>

            <div class="title-copy">
              <h3 id="record-modal-title">
                {{ record?.quiz_title || 'Assessment Record' }}
              </h3>
              <p class="subtitle">
                Completed {{ formatDateTime(record?.completed_at || record?.created_at) }}
              </p>
            </div>
          </div>

          <button type="button" class="close-x" @click="close" aria-label="Close record modal">
            &times;
          </button>
        </div>

        <div v-if="record" class="record-meta">
          <span class="meta-chip">{{ formatCoc(record.coc_number) }}</span>
          <span class="meta-chip">{{ formatLabel(record.quiz_type) }}</span>
          <span v-if="record.category" class="meta-chip">{{ record.category }}</span>
          <span class="meta-chip">Passing {{ formatPercentage(record.passing_score) }}</span>
        </div>

        <div v-if="record" class="modal-summary">
          <div class="m-stat">
            <span>Score</span>
            <strong>{{ displayScore(record) }}</strong>
          </div>

          <div class="m-stat">
            <span>Percentage</span>
            <strong>{{ formatPercentage(record.percentage) }}</strong>
          </div>

          <div class="m-stat">
            <span>Time</span>
            <strong>{{ formatElapsed(record.elapsed_time) }}</strong>
          </div>

          <div class="m-stat">
            <span>Attempt</span>
            <strong>#{{ record.attempt_count || 1 }}</strong>
          </div>

          <div class="m-stat status-stat">
            <span>Status</span>
            <strong :class="['status-badge', passed ? 'pass' : 'fail']">
              {{ statusText }}
            </strong>
          </div>
        </div>

        <div class="modal-body">
          <div v-if="record?.quiz_description" class="description-box">
            <span class="description-label">Assessment</span>
            <p>{{ record.quiz_description }}</p>
          </div>

          <div v-if="questions.length" class="review-section">
            <div class="section-heading">
              <div>
                <h4>Question Review</h4>
                <p>Review your submitted and correct answers.</p>
              </div>

              <span class="question-count">
                {{ correctCount }}/{{ questions.length }} correct
              </span>
            </div>

            <div class="review-table-wrap">
              <table class="review-table">
                <thead>
                  <tr>
                    <th scope="col" class="col-num">#</th>
                    <th scope="col">Question</th>
                    <th scope="col">Your Answer</th>
                    <th scope="col">Correct Answer</th>
                    <th scope="col" class="col-result">Result</th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-for="(question, index) in questions" :key="question.question_id || index">
                    <td class="col-num">{{ index + 1 }}</td>
                    <td class="question-text">{{ question.question || '—' }}</td>
                    <td>{{ question.user_answer || 'No answer' }}</td>
                    <td>{{ question.correct_answer || '—' }}</td>
                    <td class="col-result">
                      <span :class="['result-dot', question.is_correct ? 'right' : 'wrong']">
                        <i :class="question.is_correct ? 'fas fa-check' : 'fas fa-times'"></i>
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="review-cards">
              <article
                v-for="(question, index) in questions"
                :key="`mobile-${question.question_id || index}`"
                class="question-card"
              >
                <div class="question-card-head">
                  <span class="question-number">{{ index + 1 }}</span>
                  <span :class="['result-pill', question.is_correct ? 'right' : 'wrong']">
                    <i :class="question.is_correct ? 'fas fa-check' : 'fas fa-times'"></i>
                    {{ question.is_correct ? 'Correct' : 'Incorrect' }}
                  </span>
                </div>

                <p class="question-card-text">{{ question.question || '—' }}</p>

                <div class="answer-row">
                  <span>Your answer</span>
                  <strong>{{ question.user_answer || 'No answer' }}</strong>
                </div>

                <div class="answer-row">
                  <span>Correct answer</span>
                  <strong>{{ question.correct_answer || '—' }}</strong>
                </div>
              </article>
            </div>
          </div>

          <div v-else class="summary-only">
            <div class="summary-only-icon">
              <i class="fas fa-chart-simple"></i>
            </div>

            <h4>Assessment Summary</h4>
            <p>
              This assessment stores its official score and performance summary without
              multiple-choice question rows. The result shown above is the saved assessment record.
            </p>

            <div class="summary-grid">
              <div class="summary-line">
                <span>Correct</span>
                <strong>{{ record?.correct_answers ?? record?.score ?? 0 }}</strong>
              </div>

              <div class="summary-line">
                <span>Total</span>
                <strong>{{ record?.total_questions ?? 0 }}</strong>
              </div>

              <div class="summary-line">
                <span>Percentage</span>
                <strong>{{ formatPercentage(record?.percentage) }}</strong>
              </div>

              <div class="summary-line">
                <span>Status</span>
                <strong>{{ statusText }}</strong>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="close">Close</button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  record: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue'])

const close = () => emit('update:modelValue', false)

const questions = computed(() => {
  return Array.isArray(props.record?.questions) ? props.record.questions : []
})

const correctCount = computed(() => {
  return questions.value.filter((question) => Boolean(question.is_correct)).length
})

const passed = computed(() => {
  const status = String(props.record?.status || '').toLowerCase()

  if (status.includes('pass') || status.includes('complete')) return true
  if (status.includes('fail')) return false

  return Number(props.record?.percentage ?? 0) >= Number(props.record?.passing_score ?? 70)
})

const statusText = computed(() => {
  if (props.record?.status) return formatLabel(props.record.status)
  return passed.value ? 'Passed' : 'Failed'
})

const displayScore = (record) => {
  if (!record) return '—'

  const score = record.correct_answers ?? record.score
  const total = Number(record.total_questions ?? 0)

  if (score !== null && score !== undefined && total > 0) {
    return `${score} / ${total}`
  }

  return score !== null && score !== undefined ? String(score) : '—'
}

const formatPercentage = (value) => {
  const percentage = Number(value)
  if (!Number.isFinite(percentage)) return '0%'
  return `${Number.isInteger(percentage) ? percentage : percentage.toFixed(1)}%`
}

const formatElapsed = (seconds) => {
  if (seconds === null || seconds === undefined || Number.isNaN(Number(seconds))) {
    return '—'
  }

  const totalSeconds = Math.max(0, Math.floor(Number(seconds)))
  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  const secs = totalSeconds % 60

  if (hours > 0) return `${hours}h ${minutes}m ${secs}s`
  return `${minutes}:${String(secs).padStart(2, '0')}`
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
    .replace(/[\_-]+/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

const formatCoc = (value) => {
  return value === null || value === undefined || value === ''
    ? 'COC —'
    : `COC ${value}`
}

watch(
  () => props.modelValue,
  (value) => {
    document.body.style.overflow = value ? 'hidden' : ''
  },
  { immediate: true }
)

const handleEscape = (event) => {
  if (event.key === 'Escape') close()
}

onMounted(() => window.addEventListener('keydown', handleEscape))

onUnmounted(() => {
  window.removeEventListener('keydown', handleEscape)
  document.body.style.overflow = ''
})
</script>

<style scoped>
*,
*::before,
*::after {
  box-sizing: border-box;
}

.modal-overlay {
  --black: #111111;
  --white: #ffffff;
  --text: #222222;
  --muted: #737373;
  --light: #a3a3a3;
  --border: #e5e5e5;
  --soft-border: #f0f0f0;
  --surface: #fafafa;

  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(0, 0, 0, 0.42);
  backdrop-filter: blur(5px);
}

.modal-content {
  width: min(940px, 100%);
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.16);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 16px 18px;
  border-bottom: 1px solid var(--soft-border);
}

.title-group {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.title-icon {
  width: 36px;
  height: 36px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 0.5rem;
  background: var(--black);
  color: var(--white);
}

.title-copy {
  min-width: 0;
}

.title-copy h3 {
  margin: 0;
  overflow: hidden;
  color: var(--black);
  font-size: 0.95rem;
  font-weight: 750;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.subtitle {
  margin: 0.2rem 0 0;
  color: var(--light);
  font-size: 0.68rem;
}

.close-x {
  width: 30px;
  height: 30px;
  flex-shrink: 0;
  border: 0;
  border-radius: 0.45rem;
  background: transparent;
  color: var(--muted);
  font-size: 1.2rem;
  cursor: pointer;
}

.close-x:hover {
  background: var(--surface);
  color: var(--black);
}

.record-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  padding: 10px 18px;
  border-bottom: 1px solid var(--soft-border);
}

.meta-chip {
  padding: 0.22rem 0.5rem;
  border: 1px solid var(--border);
  border-radius: 0.35rem;
  background: var(--surface);
  color: var(--muted);
  font-size: 0.65rem;
  font-weight: 650;
}

.modal-summary {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  background: var(--surface);
  border-bottom: 1px solid var(--border);
}

.m-stat {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 12px 14px;
  border-right: 1px solid var(--border);
}

.m-stat:last-child {
  border-right: 0;
}

.m-stat > span:first-child {
  color: var(--muted);
  font-size: 0.62rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.m-stat strong {
  color: var(--black);
  font-size: 0.9rem;
  font-weight: 750;
}

.status-badge {
  width: fit-content;
  padding: 0.28rem 0.55rem;
  border-radius: 999px;
  font-size: 0.65rem !important;
}

.status-badge.pass {
  background: var(--black);
  color: var(--white);
}

.status-badge.fail {
  border: 1px solid var(--border);
  background: var(--white);
  color: var(--muted);
}

.modal-body {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: 16px 18px;
}

.description-box {
  margin-bottom: 1rem;
  padding: 0.8rem;
  border: 1px solid var(--border);
  border-radius: 0.55rem;
  background: var(--surface);
}

.description-label {
  color: var(--muted);
  font-size: 0.6rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.description-box p {
  margin: 0.25rem 0 0;
  color: var(--text);
  font-size: 0.72rem;
  line-height: 1.55;
}

.section-heading {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.section-heading h4,
.summary-only h4 {
  margin: 0;
  color: var(--black);
  font-size: 0.82rem;
  font-weight: 700;
}

.section-heading p,
.summary-only p {
  margin: 0.2rem 0 0;
  color: var(--muted);
  font-size: 0.68rem;
}

.question-count {
  color: var(--muted);
  font-size: 0.68rem;
  font-weight: 650;
  white-space: nowrap;
}

.review-table-wrap {
  overflow-x: auto;
  border: 1px solid var(--border);
  border-radius: 0.6rem;
}

.review-table {
  width: 100%;
  min-width: 760px;
  border-collapse: collapse;
  table-layout: fixed;
}

.review-table th {
  padding: 0.6rem 0.7rem;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  color: var(--muted);
  font-size: 0.6rem;
  font-weight: 700;
  text-align: left;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.review-table td {
  padding: 0.7rem;
  border-bottom: 1px solid var(--soft-border);
  color: var(--text);
  font-size: 0.7rem;
  line-height: 1.45;
  vertical-align: top;
  overflow-wrap: anywhere;
}

.review-table tbody tr:last-child td {
  border-bottom: 0;
}

.review-table .col-num {
  width: 44px;
  text-align: center;
}

.review-table .col-result {
  width: 62px;
  text-align: center;
}

.question-text {
  font-weight: 650;
}

.result-dot {
  width: 24px;
  height: 24px;
  display: inline-grid;
  place-items: center;
  border-radius: 50%;
  font-size: 0.62rem;
}

.result-dot.right {
  background: var(--black);
  color: var(--white);
}

.result-dot.wrong {
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--muted);
}

.review-cards {
  display: none;
  flex-direction: column;
  gap: 0.65rem;
}

.question-card {
  padding: 0.8rem;
  border: 1px solid var(--border);
  border-radius: 0.6rem;
  background: var(--white);
}

.question-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.6rem;
}

.question-number {
  width: 24px;
  height: 24px;
  display: grid;
  place-items: center;
  border-radius: 0.4rem;
  background: var(--surface);
  border: 1px solid var(--border);
  color: var(--muted);
  font-size: 0.65rem;
  font-weight: 700;
}

.result-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.24rem 0.45rem;
  border-radius: 999px;
  font-size: 0.62rem;
  font-weight: 700;
}

.result-pill.right {
  background: var(--black);
  color: var(--white);
}

.result-pill.wrong {
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--muted);
}

.question-card-text {
  margin: 0 0 0.75rem;
  color: var(--black);
  font-size: 0.74rem;
  font-weight: 650;
  line-height: 1.5;
}

.answer-row {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  padding: 0.55rem 0;
  border-top: 1px solid var(--soft-border);
}

.answer-row span {
  color: var(--muted);
  font-size: 0.6rem;
  font-weight: 700;
  text-transform: uppercase;
}

.answer-row strong {
  color: var(--text);
  font-size: 0.7rem;
  font-weight: 600;
  overflow-wrap: anywhere;
}

.summary-only {
  min-height: 220px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  text-align: center;
}

.summary-only-icon {
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  margin-bottom: 0.75rem;
  border: 1px solid var(--border);
  border-radius: 0.6rem;
  background: var(--surface);
  color: var(--muted);
}

.summary-only p {
  max-width: 520px;
  line-height: 1.6;
}

.summary-grid {
  width: min(420px, 100%);
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
  margin-top: 1rem;
}

.summary-line {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.65rem 0.75rem;
  border: 1px solid var(--border);
  border-radius: 0.45rem;
  background: var(--surface);
}

.summary-line span {
  color: var(--muted);
  font-size: 0.64rem;
}

.summary-line strong {
  color: var(--black);
  font-size: 0.7rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  padding: 12px 18px;
  border-top: 1px solid var(--soft-border);
}

.btn-secondary {
  min-height: 34px;
  padding: 7px 14px;
  border: 1px solid var(--border);
  border-radius: 0.45rem;
  background: var(--white);
  color: var(--muted);
  font-size: 0.7rem;
  font-weight: 700;
  cursor: pointer;
}

.btn-secondary:hover {
  background: var(--black);
  border-color: var(--black);
  color: var(--white);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@media (max-width: 720px) {
  .modal-overlay {
    padding: 10px;
  }

  .modal-content {
    max-height: 94vh;
  }

  .modal-summary {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .m-stat {
    border-bottom: 1px solid var(--border);
  }

  .status-stat {
    grid-column: 1 / -1;
    border-bottom: 0;
  }

  .review-table-wrap {
    display: none;
  }

  .review-cards {
    display: flex;
  }
}

@media (max-width: 480px) {
  .modal-overlay {
    padding: 0;
    align-items: stretch;
  }

  .modal-content {
    max-height: 100vh;
    min-height: 100vh;
    border: 0;
    border-radius: 0;
  }

  .modal-header,
  .record-meta,
  .modal-body,
  .modal-footer {
    padding-left: 14px;
    padding-right: 14px;
  }

  .modal-summary {
    grid-template-columns: 1fr 1fr;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
