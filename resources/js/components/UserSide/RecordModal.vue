<template>
  <Transition name="fade">
    <div v-if="modelValue" class="modal-overlay" @click.self="close">
      <div class="modal-content">

        <div class="modal-header">
          <div class="title-group">
            <h3>Quiz Review</h3>
            <p class="subtitle">Completed on {{ formatDate(record?.created_at) }}</p>
          </div>
          <button class="close-x" @click="close">&times;</button>
        </div>

        <div class="modal-summary" v-if="record">
          <div class="m-stat">
            <span>Score</span>
            <strong :class="record.score >= passingScore ? 'pass-text' : 'fail-text'">
              {{ correctCount }}/{{ totalQuestions }}
            </strong>
          </div>
          <div class="m-stat">
            <span>Accuracy</span>
            <h3>{{ accuracy }}%</h3>
          </div>
          <div class="m-stat">
            <span>Time</span>
            <h3>{{ formatElapsed(record.elapsed_time) }}</h3>
          </div>
          <div class="m-stat">
            <span :class="['badge', record.score >= passingScore ? 'pass' : 'fail']">
              {{ record.score >= passingScore ? 'Passed' : 'Failed' }}
            </span>
          </div>
        </div>

        <div class="modal-body" v-if="record?.questions?.length">
          <div class="question-list">
            <div v-for="(q, i) in record.questions" :key="q.question_id || i" class="question-item">

              <div :class="['q-indicator', q.is_correct ? 'ind-pass' : 'ind-fail']">
                {{ i + 1 }}
              </div>

              <div class="q-content">
                <p class="q-text">{{ q.question }}</p>

                <div class="answers-stack">
                  <div class="ans-line">
                    <span class="ans-label">Answer:</span>
                    <span :class="['ans-val', q.is_correct ? 'text-pass' : 'text-fail']">
                      {{ q.user_answer || 'No answer selected' }}
                    </span>
                  </div>

                  <div class="ans-line" v-if="!q.is_correct">
                    <span class="ans-label">Correct:</span>
                    <span class="ans-val text-pass">{{ q.correct_answer }}</span>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          No question data found.
        </div>

        <div class="modal-footer">
          <button class="btn-secondary" @click="close">Close</button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  record: Object
})

const emit = defineEmits(['update:modelValue', 'retake'])
const passingScore = 7

const close = () => emit('update:modelValue', false)

watch(
  () => props.modelValue,
  (val) => { document.body.style.overflow = val ? 'hidden' : '' },
  { immediate: true }
)

const handleEscape = (e) => { if (e.key === 'Escape') close() }

onMounted(() => window.addEventListener('keydown', handleEscape))
onUnmounted(() => {
  window.removeEventListener('keydown', handleEscape)
  document.body.style.overflow = ''
})

const totalQuestions = computed(() => props.record?.total_questions || props.record?.questions?.length || 0)
const correctCount = computed(() => props.record?.questions?.filter(q => q.is_correct).length || 0)
const wrongCount = computed(() => props.record?.questions?.filter(q => !q.is_correct).length || 0)
const accuracy = computed(() => {
  const qs = props.record?.questions || []
  return qs.length ? ((props.record.questions.filter(q => q.is_correct).length / qs.length) * 100).toFixed(0) : 0
})

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : ''
const formatElapsed = (sec) => {
  if (sec == null) return '0:00'
  return `${Math.floor(sec / 60)}:${(sec % 60).toString().padStart(2, '0')}`
}
</script>

<style scoped>
/* OVERLAY */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.4);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  padding: 1rem;
  backdrop-filter: blur(8px);
}

/* COMPACT CONTENT CONTAINER */
.modal-content {
  width: 100%;
  max-width: 580px;
  /* Shrunk down from 1100px */
  max-height: 85vh;
  background: #ffffff;
  border-radius: 16px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  border: 1px solid #f1f5f9;
}

/* HEADER */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.2rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
}

.title-group h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: #0f172a;
}

.subtitle {
  margin: 2px 0 0 0;
  font-size: 0.75rem;
  color: #94a3b8;
}

.close-x {
  border: none;
  background: transparent;
  color: #94a3b8;
  font-size: 1.5rem;
  cursor: pointer;
  line-height: 1;
  padding: 0.2rem;
  transition: color 0.2s;
}

.close-x:hover {
  color: #0f172a;
}

/* COMPACT HORIZONTAL SUMMARY */
.modal-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-bottom: 1px solid #f1f5f9;
}

.m-stat {
  display: flex;
  flex-direction: column;
}

.m-stat span {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #94a3b8;
  font-weight: 600;
}

.m-stat strong,
.m-stat h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: #0f172a;
}

/* UI UTILITIES */
.pass-text,
.text-pass {
  color: #10b981;
}

.fail-text,
.text-fail {
  color: #f43f5e;
}

.badge {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.7rem;
  font-weight: 700;
}

.badge.pass {
  background: #d1fae5;
  color: #065f46;
}

.badge.fail {
  background: #ffe4e6;
  color: #991b1b;
}

/* SCROLLABLE BODY */
.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem 1.5rem;
}

.modal-body::-webkit-scrollbar {
  width: 4px;
}

.modal-body::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 4px;
}

/* QUESTION ITEM MINIMAL DESIGN */
.question-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.question-item {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  border-radius: 12px;
  background: #fff;
  border: 1px solid #f1f5f9;
}

/* Mini Indicator Pill */
.q-indicator {
  width: 26px;
  height: 26px;
  min-width: 26px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
}

.ind-pass {
  background: #e6f4ea;
  color: #137333;
}

.ind-fail {
  background: #fce8e6;
  color: #c5221f;
}

.q-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.q-text {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 600;
  color: #1e293b;
  line-height: 1.4;
}

/* Inline Answers Setup */
.answers-stack {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.8rem;
  background: #f8fafc;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
}

.ans-line {
  display: flex;
  gap: 0.5rem;
}

.ans-label {
  color: #64748b;
  font-weight: 500;
  min-width: 60px;
}

.ans-val {
  font-weight: 600;
}

/* FOOTER */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  padding: 1rem 1.5rem;
  border-top: 1px solid #f1f5f9;
}

.btn-secondary {
  border: 1px solid #e2e8f0;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  background: #fff;
  color: #475569;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-secondary:hover {
  background: #f8fafc;
}

/* TRANSITIONS */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.empty-state {
  padding: 2rem;
  text-align: center;
  color: #94a3b8;
  font-size: 0.9rem;
}
</style>