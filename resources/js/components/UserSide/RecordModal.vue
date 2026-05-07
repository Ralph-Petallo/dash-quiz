<template>
  <Transition name="fade">
    <div v-if="modelValue" class="modal-overlay" @click.self="close">

      <div class="modal-content">

        <!-- HEADER -->
        <div class="modal-header">

          <div class="title-group">

            <h3>
              Quiz Review
            </h3>

            <p class="subtitle">
              Completed on {{ formatDate(record?.created_at) }}
            </p>

          </div>

        </div>

        <!-- SUMMARY -->
        <div class="modal-summary" v-if="record">

          <div class="m-stat">

            <span>Score</span>

            <strong :class="record.score >= passingScore
              ? 'pass-text'
              : 'fail-text'">
              {{ correctCount }} / {{ totalQuestions }}
            </strong>

          </div>

          <div class="m-stat">

            <span>Accuracy</span>

            <strong>
              {{ accuracy }}%
            </strong>

          </div>

          <div class="m-stat">

            <span>Elapsed</span>
            <strong>
              {{ formatElapsed(record.elapsed_time) }}
            </strong>
          </div>
          <div class="m-stat">
            <span>Status</span>
            <span :class="[
              'badge',
              record.score >= passingScore ? 'pass' : 'fail']">
              {{
                record.score >= passingScore
                  ? 'Passed'
                  : 'Failed'
              }}
            </span>
          </div>
        </div>

        <!-- BODY -->
        <div class="modal-body" v-if="record?.questions?.length">

          <!-- TOPBAR -->
          <div class="questions-topbar">

            <h4 class="section-label">
              Question Review
            </h4>

            <div class="review-stats">

              <span class="correct-pill">
                {{ correctCount }} Correct
              </span>

              <span class="wrong-pill">
                {{ wrongCount }} Wrong
              </span>

            </div>

          </div>

          <!-- QUESTION LIST -->
          <div class="question-list">

            <div v-for="(q, i) in record.questions" :key="q.question_id || i" class="question-item">

              <!-- NUMBER -->
              <div :class="[
                'question-indicator',
                q.is_correct
                  ? 'indicator-correct'
                  : 'indicator-wrong'
              ]">
                {{ i + 1 }}
              </div>

              <!-- CONTENT -->
              <div class="question-content">

                <!-- QUESTION BODY -->
                <div class="question-card-body">

                  <!-- QUESTION -->
                  <div class="question-block">

                    <span class="mini-label">
                      Question
                    </span>

                    <p class="question-text">
                      {{ q.question }}
                    </p>

                  </div>

                  <!-- ANSWERS -->
                  <div class="answers-grid">

                    <!-- USER ANSWER -->
                    <div class="answer-card">

                      <div class="answer-head">

                        <span class="answer-dot your-dot"></span>

                        <span class="answer-title-small">
                          Your Answer
                        </span>

                      </div>

                      <div :class="[
                        'answer-content',
                        q.is_correct
                          ? 'answer-correct-box'
                          : 'answer-wrong-box'
                      ]">
                        {{
                          q.user_answer ||
                          'No answer selected'
                        }}
                      </div>

                    </div>

                    <!-- CORRECT ANSWER -->
                    <div class="answer-card" v-if="!q.is_correct">

                      <div class="answer-head">

                        <span class="answer-dot correct-dot"></span>

                        <span class="answer-title-small">
                          Correct Answer
                        </span>

                      </div>

                      <div class="answer-content answer-correct-box">
                        {{ q.correct_answer }}
                      </div>

                    </div>

                  </div>

                  <!-- META -->
                  <div class="question-meta">

                    <div :class="[
                      'meta-pill',
                      q.is_correct
                        ? 'meta-success'
                        : 'meta-danger'
                    ]">
                      {{
                        q.is_correct
                          ? 'Answered Correctly'
                          : 'Needs Review'
                      }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- EMPTY -->
        <div v-else class="empty-state">
          No question data found.
        </div>

        <!-- FOOTER -->
        <div class="modal-footer">

          <button class="btn-secondary" @click="close">
            Close Review
          </button>

        </div>

      </div>

    </div>
  </Transition>
</template>

<script setup>
import {
  computed,
  watch,
  onMounted,
  onUnmounted
} from 'vue'

const props = defineProps({
  modelValue: Boolean,
  record: Object
})

const emit = defineEmits([
  'update:modelValue',
  'retake'
])

const passingScore = 7

const close = () => {
  emit('update:modelValue', false)
}

/*
|--------------------------------------------------------------------------
| LOCK BODY SCROLL
|--------------------------------------------------------------------------
*/
watch(
  () => props.modelValue,
  (val) => {
    document.body.style.overflow = val
      ? 'hidden'
      : ''
  },
  { immediate: true }
)

/*
|--------------------------------------------------------------------------
| ESC CLOSE
|--------------------------------------------------------------------------
*/
const handleEscape = (e) => {
  if (e.key === 'Escape') {
    close()
  }
}

onMounted(() => {
  window.addEventListener(
    'keydown',
    handleEscape
  )
})

onUnmounted(() => {
  window.removeEventListener(
    'keydown',
    handleEscape
  )

  document.body.style.overflow = ''
})

/*
|--------------------------------------------------------------------------
| TOTAL QUESTIONS
|--------------------------------------------------------------------------
*/
const totalQuestions = computed(() => {
  return (
    props.record?.total_questions ||
    props.record?.questions?.length ||
    0
  )
})

/*
|--------------------------------------------------------------------------
| ACCURACY
|--------------------------------------------------------------------------
*/
const accuracy = computed(() => {
  const qs =
    props.record?.questions || []

  if (!qs.length) return 0

  const correct = qs.filter(
    q => q.is_correct
  ).length

  return (
    (correct / qs.length) * 100
  ).toFixed(0)
})

/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/
const correctCount = computed(() => {
  return (
    props.record?.questions?.filter(
      q => q.is_correct
    ).length || 0
  )
})

const wrongCount = computed(() => {
  return (
    props.record?.questions?.filter(
      q => !q.is_correct
    ).length || 0
  )
})

/*
|--------------------------------------------------------------------------
| FORMAT DATE
|--------------------------------------------------------------------------
*/
const formatDate = (d) =>
  d
    ? new Date(d).toLocaleDateString(
      'en-US',
      {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      }
    )
    : ''

/*
|--------------------------------------------------------------------------
| FORMAT TIME
|--------------------------------------------------------------------------
*/
const formatElapsed = (sec) => {
  if (
    sec === null ||
    sec === undefined
  ) {
    return '0:00'
  }

  const m = Math.floor(sec / 60)
  const s = sec % 60

  return `${m}:${s
    .toString()
    .padStart(2, '0')}`
}

/*
|--------------------------------------------------------------------------
| RETAKE
|--------------------------------------------------------------------------
*/
const handleRetake = () => {
  emit('retake', props.record.quiz_id)
  close()
}
</script>

<style scoped>
/* OVERLAY */

.modal-overlay {
  position: fixed;
  inset: 0;

  background: rgba(15, 23, 42, 0.72);

  display: flex;
  justify-content: center;
  align-items: center;

  z-index: 9999;

  padding: 1rem;

  backdrop-filter: blur(4px);
}

/* CONTENT */

.modal-content {
  width: 100%;
  max-width: 1100px;
  max-height: 95vh;

  background: #fff;

  border-radius: 12px;

  overflow: hidden;

  display: flex;
  flex-direction: column;

  box-shadow:
    0 10px 40px rgba(15, 23, 42, 0.15),
    0 2px 10px rgba(15, 23, 42, 0.08);
}

/* HEADER */

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;

  padding: 1.5rem;

  border-bottom: 1px solid #f1f5f9;
}

.title-group h3 {
  margin: 0;

  font-size: 1.25rem;
  font-weight: 800;

  color: #0f172a;
}

.subtitle {
  margin-top: 4px;

  font-size: 0.82rem;

  color: #94a3b8;
}

.close-x {
  width: 38px;
  height: 38px;

  border: none;

  border-radius: 50%;

  background: #f8fafc;

  color: #64748b;

  cursor: pointer;

  font-size: 0.95rem;

  transition: all 0.2s ease;
}

.close-x:hover {
  background: #eef2ff;
  color: #4f46e5;
}

/* SUMMARY */

.modal-summary {
  display: grid;
  grid-template-columns: repeat(4, 1fr);

  gap: 1rem;

  padding: 1.25rem 1.5rem;

  background: #f8fafc;

  border-bottom: 1px solid #f1f5f9;
}

.m-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.m-stat span {
  font-size: 0.68rem;

  text-transform: uppercase;

  color: #94a3b8;

  font-weight: 700;

  margin-bottom: 0.35rem;
}

.m-stat strong {
  font-size: 1.25rem;
  font-weight: 800;

  color: #0f172a;
}

.pass-text {
  color: #10b981;
}

.fail-text {
  color: #f43f5e;
}

/* BADGES */

.badge {
  padding: 5px 12px;

  border-radius: 999px;

  font-size: 0.72rem;
  font-weight: 700;
}

.badge.pass {
  background: #ecfdf5;
  color: #10b981;
}

.badge.fail {
  background: #fff1f2;
  color: #f43f5e;
}

/* BODY */

.modal-body {
  flex: 1;

  overflow-y: auto;

  padding: 1.5rem;
}

.modal-body::-webkit-scrollbar {
  width: 6px;
}

.modal-body::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 999px;
}

/* TOPBAR */

.questions-topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;

  margin-bottom: 1.25rem;

  gap: 1rem;

  flex-wrap: wrap;
}

.section-label {
  margin: 0;

  font-size: 0.78rem;
  font-weight: 700;

  text-transform: uppercase;

  color: #94a3b8;
}

.review-stats {
  display: flex;
  gap: 0.5rem;
}

.correct-pill,
.wrong-pill {
  padding: 6px 12px;

  border-radius: 999px;

  font-size: 0.72rem;
  font-weight: 700;
}

.correct-pill {
  background: #ecfdf5;
  color: #10b981;
}

.wrong-pill {
  background: #fff1f2;
  color: #f43f5e;
}

/* QUESTION LIST */

.question-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.question-item {
  display: flex;
  align-items: flex-start;

  padding: 1.5rem;

  border-radius: 18px;
  box-shadow: grey 0px 0px 0.5px 1px;

  background: #fff;

  transition: all 0.2s ease;
}

.question-item:hover {
  border-color: #dbeafe;
}

/* INDICATOR */

.question-indicator {
  width: 44px;
  height: 44px;
  min-width: 44px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  font-weight: 800;
  margin-right: 10px;
}

.indicator-correct {
  background: #ecfdf5;
  color: #10b981;
}

.indicator-wrong {
  background: #fff1f2;
  color: #f43f5e;
}

/* CONTENT */

.question-content {
  flex: 1;
}

.question-top {
  display: flex;
  justify-content: space-between;
  align-items: center;

  margin-bottom: 0.8rem;

  gap: 1rem;

  flex-wrap: wrap;
}

.question-title {
  font-size: 0.75rem;
  font-weight: 700;

  color: #64748b;

  text-transform: uppercase;
}

.question-badge {
  padding: 5px 10px;

  border-radius: 999px;

  font-size: 0.72rem;
  font-weight: 700;
}

.badge-correct {
  background: #ecfdf5;
  color: #10b981;
}

.badge-wrong {
  background: #fff1f2;
  color: #f43f5e;
}

/* QUESTION BODY */

.question-card-body {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.question-block {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.mini-label {
  font-size: 0.7rem;

  font-weight: 700;

  text-transform: uppercase;

  color: #94a3b8;
}

.question-text {
  margin: 0;

  font-size: 0.96rem;
  line-height: 1.75;

  font-weight: 600;

  color: #0f172a;
}

/* ANSWERS */

.answers-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);

  gap: 0.9rem;
}

.answer-card {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}

.answer-head {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.answer-dot {
  width: 10px;
  height: 10px;

  border-radius: 999px;
}

.your-dot {
  background: #6366f1;
}

.correct-dot {
  background: #10b981;
}

.answer-title-small {
  font-size: 0.72rem;

  font-weight: 700;

  text-transform: uppercase;

  color: #94a3b8;
}

.answer-content {
  padding: 0.9rem;

  border-radius: 14px;

  font-size: 0.92rem;
  font-weight: 600;

  line-height: 1.6;

  border: 1px solid transparent;

  word-break: break-word;
}

.answer-correct-box {
  background: #ecfdf5;
  border-color: #d1fae5;
  color: #047857;
}

.answer-wrong-box {
  background: #fff1f2;
  border-color: #ffe4e6;
  color: #be123c;
}

/* META */

.question-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.meta-pill {
  padding: 6px 12px;

  border-radius: 999px;

  background: #f8fafc;

  border: 1px solid #e2e8f0;

  font-size: 0.72rem;
  font-weight: 700;

  color: #64748b;
}

.meta-success {
  background: #ecfdf5;
  border-color: #d1fae5;
  color: #10b981;
}

.meta-danger {
  background: #fff1f2;
  border-color: #ffe4e6;
  color: #f43f5e;
}

/* EMPTY */

.empty-state {
  padding: 3rem 1rem;

  text-align: center;

  color: #94a3b8;
}

/* FOOTER */

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  background-color: grey;
  padding: 1.25rem 1.5rem;

  border-top: 1px solid #f1f5f9;
}

.btn-primary,
.btn-secondary {
  border: none;

  padding: 0.75rem 1.2rem;

  border-radius: 12px;

  font-size: 0.9rem;
  font-weight: 700;

  cursor: pointer;

  transition: all 0.2s ease;
}

.btn-primary {
  background: #6366f1;
  color: #fff;
}

.btn-primary:hover {
  background: #4f46e5;
}

.btn-secondary {
  background: #fff;

  border: 1px solid #e2e8f0;

  color: #64748b;
}

/* TRANSITION */

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* RESPONSIVE */

@media (max-width: 768px) {

  .modal-summary {
    grid-template-columns: repeat(2, 1fr);
  }

  .answers-grid {
    grid-template-columns: 1fr;
  }

}

@media (max-width: 640px) {

  .modal-body {
    padding: 1rem;
  }

  .modal-footer {
    flex-direction: column;
  }

  .btn-primary,
  .btn-secondary {
    width: 100%;
  }

}
</style>