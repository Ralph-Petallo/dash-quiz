<template>
  <div class="page-wrapper">
    <section class="admin-section">

      <!-- =====================================================
           SUCCESS ALERT
      ====================================================== -->
      <Transition name="fade-slide">
        <div v-if="successMessage" class="alert-success">
          <i class="fas fa-check-circle"></i>
          <span>{{ successMessage }}</span>
        </div>
      </Transition>


      <!-- =====================================================
           HEADER
      ====================================================== -->
      <header class="header-row">

        <div class="page-heading">

          <span class="eyebrow">
            CSS PREP · ADMINISTRATION
          </span>

          <div class="heading-main">
            <div class="title-icon">
              <i class="fa-solid fa-layer-group"></i>
            </div>

            <div>
              <h1 class="section-title">
                Manage Assessments
              </h1>

              <p class="section-subtitle">
                {{ assessments.length }}
                assessment{{ assessments.length === 1 ? '' : 's' }}
                available
              </p>
            </div>
          </div>

        </div>


        <div class="header-actions">

          <!-- SORT -->
          <div class="sort-group">

            <button v-for="opt in sortOptions" :key="opt.key" class="sort-chip" :class="{ active: sortKey === opt.key }"
              @click="sortBy(opt.key)">

              {{ opt.label }}

              <i v-if="sortKey === opt.key" class="fas" :class="sortOrder === 'asc'
                ? 'fa-arrow-up-short-wide'
                : 'fa-arrow-down-wide-short'
                "></i>

            </button>

          </div>


          <!-- ADD -->
          <button class="add-btn" @click="router.push('/admin/assessments/create')">
            <i class="fas fa-plus"></i>
            New Assessment
          </button>

        </div>

      </header>


      <!-- =====================================================
           LOADING
      ====================================================== -->
      <div v-if="loading" class="loading-state">

        <div class="spinner"></div>

        <div>
          <strong>Loading assessments</strong>
          <p>Please wait...</p>
        </div>

      </div>


      <!-- =====================================================
           ASSESSMENT GRID
      ====================================================== -->
      <div v-else-if="sortedAssessments.length" class="assessment-grid">

        <article v-for="assessment in sortedAssessments" :key="assessment.id" class="assessment-card">

          <!-- CARD HEADER -->
          <header class="assessment-header">

            <div class="assessment-icon" :class="typeClass(assessment.type)">
              <i :class="assessment.icon || typeIcon(assessment.type)"></i>
            </div>


            <div class="assessment-heading">

              <div class="title-line">

                <h2 class="assessment-title">
                  {{ assessment.title }}
                </h2>

              </div>

              <span class="assessment-type">
                {{ formatType(assessment.type) }}
              </span>

            </div>

          </header>


          <!-- DESCRIPTION -->
          <p class="assessment-description">
            {{
              assessment.description ||
              'No description provided.'
            }}
          </p>


          <!-- META -->
          <div class="assessment-meta">

            <!-- COC -->
            <div class="meta-item">

              <span class="meta-label">
                COC
              </span>

              <span class="meta-value">
                {{ assessment.coc_number ?? 'N/A' }}
              </span>

            </div>


            <!-- CATEGORY -->
            <div class="meta-item">

              <span class="meta-label">
                Category
              </span>

              <span class="meta-value">
                {{ assessment.category || 'General' }}
              </span>

            </div>


            <!-- DIFFICULTY -->
            <div class="meta-item">

              <span class="meta-label">
                Difficulty
              </span>

              <span class="difficulty-badge" :class="difficultyClass(assessment.difficulty)">
                {{ assessment.difficulty || 'N/A' }}
              </span>

            </div>

          </div>


          <!-- CARD FOOTER -->
          <footer class="assessment-footer">

            <div class="availability">

              <span class="availability-dot" :class="{
                published: assessment.available !== false
              }"></span>

              <span>
                {{
                  assessment.available === false
                    ? 'Unavailable'
                    : 'Published'
                }}
              </span>

            </div>


            <div class="card-actions">

              <!-- EDIT -->
              <button class="icon-btn edit" title="Edit assessment" aria-label="Edit assessment" @click="
                router.push(
                  `/admin/assessments/${assessment.id}/edit`
                )
                ">
                <i class="fas fa-pen"></i>
              </button>


              <!-- DELETE -->
              <button class="icon-btn delete" title="Delete assessment" aria-label="Delete assessment" @click="
                deleteAssessment(
                  assessment.id,
                  assessment.title
                )
                ">
                <i class="fas fa-trash"></i>
              </button>

            </div>

          </footer>

        </article>

      </div>


      <!-- =====================================================
           EMPTY
      ====================================================== -->
      <div v-else class="empty-state">

        <div class="empty-icon">
          <i class="fas fa-layer-group"></i>
        </div>

        <h3>
          No assessments yet
        </h3>

        <p>
          Create your first competency assessment to get started.
        </p>

        <button class="add-btn subtle" @click="
          router.push('/admin/assessments/create')
          ">
          <i class="fas fa-plus"></i>
          Create Assessment
        </button>

      </div>

    </section>
  </div>
</template>


<script setup>
import {
  ref,
  computed,
  onMounted
} from 'vue'

import { useRouter } from 'vue-router'
import api from '../../bootstrap'


const router = useRouter()


/* =========================================================
   STATE
========================================================= */

const assessments = ref([])

const loading = ref(true)

const successMessage = ref('')

const sortKey = ref('id')

const sortOrder = ref('asc')


/* =========================================================
   SORT OPTIONS
========================================================= */

const sortOptions = [
  {
    key: 'id',
    label: 'ID'
  },
  {
    key: 'title',
    label: 'Title'
  },
  {
    key: 'type',
    label: 'Type'
  },
  {
    key: 'difficulty',
    label: 'Difficulty'
  }
]


/* =========================================================
   FETCH ASSESSMENTS
========================================================= */

const fetchAssessments = async (retry = 0) => {

  loading.value = true

  try {

    /*
     * If your real endpoint is different,
     * change only this URL.
     */
    const { data } = await api.get(
      '/api/admin/quizzes'
    )
    console.log(data)
    assessments.value =
      data.result ||
      data.data ||
      []

  } catch (error) {

    if (
      error.response?.status === 429 &&
      retry < 3
    ) {

      setTimeout(() => {
        fetchAssessments(retry + 1)
      }, 1000)

      return
    }

    console.error(
      'Failed to load assessments:',
      error
    )

  } finally {

    loading.value = false

  }
}


/* =========================================================
   SORT
========================================================= */

const sortedAssessments = computed(() => {

  return [...assessments.value].sort(
    (a, b) => {

      let A =
        a[sortKey.value] ??
        ''

      let B =
        b[sortKey.value] ??
        ''


      if (
        typeof A === 'string'
      ) {
        A = A.toLowerCase()
      }


      if (
        typeof B === 'string'
      ) {
        B = B.toLowerCase()
      }


      if (A === B) {
        return 0
      }


      return sortOrder.value === 'asc'
        ? A > B
          ? 1
          : -1
        : A < B
          ? 1
          : -1

    }
  )

})


const sortBy = (key) => {

  if (
    sortKey.value === key
  ) {

    sortOrder.value =
      sortOrder.value === 'asc'
        ? 'desc'
        : 'asc'

    return
  }


  sortKey.value = key

  sortOrder.value = 'asc'
}


/* =========================================================
   TYPE
========================================================= */

const formatType = (type) => {

  if (!type) {
    return 'Assessment'
  }

  return type
    .replace(/_/g, ' ')
    .replace(/\b\w/g, char =>
      char.toUpperCase()
    )
}


const typeClass = (type) => {

  const value =
    (type || '')
      .toLowerCase()


  if (
    value.includes('image')
  ) {
    return 'type-image'
  }


  if (
    value.includes('drag')
  ) {
    return 'type-drag'
  }


  if (
    value.includes('scenario')
  ) {
    return 'type-scenario'
  }


  if (
    value.includes('virtual')
  ) {
    return 'type-virtual'
  }


  if (
    value.includes('multiple')
  ) {
    return 'type-multiple'
  }


  return 'type-default'
}


const typeIcon = (type) => {

  const value =
    (type || '')
      .toLowerCase()


  if (
    value.includes('image')
  ) {
    return 'fa-solid fa-image'
  }


  if (
    value.includes('drag')
  ) {
    return 'fa-solid fa-arrows-up-down-left-right'
  }


  if (
    value.includes('scenario')
  ) {
    return 'fa-solid fa-comments'
  }


  if (
    value.includes('virtual')
  ) {
    return 'fa-solid fa-laptop-code'
  }


  if (
    value.includes('multiple')
  ) {
    return 'fa-solid fa-list-check'
  }


  return 'fa-solid fa-layer-group'
}


/* =========================================================
   DIFFICULTY
========================================================= */

const difficultyClass = (
  difficulty
) => {

  const value =
    (difficulty || '')
      .toLowerCase()


  if (value === 'easy') {
    return 'level-easy'
  }


  if (value === 'medium') {
    return 'level-medium'
  }


  if (value === 'hard') {
    return 'level-hard'
  }


  return 'level-default'
}


/* =========================================================
   DELETE
========================================================= */

const deleteAssessment = async (
  id,
  title
) => {

  if (
    !confirm(
      `Are you sure you want to delete "${title}"?`
    )
  ) {
    return
  }


  try {

    /*
     * PLACEHOLDER ENDPOINT
     *
     * Replace this later if your API
     * uses another endpoint.
     */
    await axios.delete(
      `/api/admin/assessments/${id}`
    )


    successMessage.value =
      'Assessment deleted successfully.'


    await fetchAssessments()


    setTimeout(() => {
      successMessage.value = ''
    }, 3000)

  } catch (error) {

    console.error(
      'Failed to delete assessment:',
      error
    )

    alert(
      'Error deleting assessment.'
    )

  }

}


/* =========================================================
   MOUNT
========================================================= */

onMounted(
  fetchAssessments
)

</script>


<style scoped>
*,
*::before,
*::after {
  box-sizing: border-box;
}


/* =========================================================
   VARIABLES
========================================================= */

.page-wrapper {
  --ink: #111111;
  --text: #222222;
  --muted: #777777;
  --light-muted: #a0a0a0;

  --line: #e5e5e5;
  --soft-line: #eeeeee;

  --surface: #ffffff;
  --soft: #f5f5f5;
  --page: #fafafa;

  width: 100%;
  min-height: 100vh;

  padding:
    clamp(1rem, 2.5vw, 2rem);

  background: var(--page);

  font-family:
    'Inter',
    -apple-system,
    BlinkMacSystemFont,
    'Segoe UI',
    sans-serif;
}


/* =========================================================
   SECTION
========================================================= */

.admin-section {
  width: 100%;

  max-width: 1200px;

  margin: 0 auto;
}


/* =========================================================
   HEADER
========================================================= */

.header-row {
  display: flex;

  align-items: flex-end;
  justify-content: space-between;

  gap: 20px;

  margin-bottom: 22px;
}

.page-heading {
  min-width: 0;
}

.eyebrow {
  display: block;

  margin-bottom: 7px;

  color: #999;

  font-size: 0.59rem;
  font-weight: 750;

  letter-spacing: 0.14em;
}

.heading-main {
  display: flex;

  align-items: center;

  gap: 11px;
}

.title-icon {
  width: 38px;
  height: 38px;

  flex: 0 0 38px;

  display: grid;
  place-items: center;

  background: #111;

  border-radius: 6px;

  color: #fff;

  font-size: 0.8rem;
}

.section-title {
  margin: 0;

  color: var(--ink);

  font-size:
    clamp(1.15rem, 1.6vw, 1.4rem);

  font-weight: 750;

  letter-spacing: -0.035em;
}

.section-subtitle {
  margin: 4px 0 0;

  color: var(--muted);

  font-size: 0.68rem;
}


/* =========================================================
   HEADER ACTIONS
========================================================= */

.header-actions {
  display: flex;

  align-items: center;

  gap: 8px;

  flex-wrap: wrap;
}

.sort-group {
  display: flex;

  gap: 2px;

  padding: 3px;

  background: #fff;

  border: 1px solid var(--line);

  border-radius: 6px;
}

.sort-chip {
  display: flex;

  align-items: center;

  gap: 5px;

  padding: 6px 8px;

  border: none;

  border-radius: 4px;

  background: transparent;

  color: #888;

  font-size: 0.62rem;
  font-weight: 650;

  cursor: pointer;

  transition:
    background 0.15s ease,
    color 0.15s ease;
}

.sort-chip:hover {
  color: #222;

  background: #f5f5f5;
}

.sort-chip.active {
  background: #111;

  color: #fff;
}


/* =========================================================
   ADD BUTTON
========================================================= */

.add-btn {
  display: flex;

  align-items: center;
  justify-content: center;

  gap: 7px;

  min-height: 34px;

  padding:
    0 12px;

  background: #111;

  border: 1px solid #111;

  border-radius: 5px;

  color: #fff;

  font-size: 0.68rem;
  font-weight: 650;

  white-space: nowrap;

  cursor: pointer;

  transition:
    background 0.15s ease,
    transform 0.15s ease;
}

.add-btn:hover {
  background: #333;

  transform: translateY(-1px);
}


/* =========================================================
   ALERT
========================================================= */

.alert-success {
  display: flex;

  align-items: center;

  gap: 8px;

  margin-bottom: 15px;

  padding: 10px 12px;

  background: #111;

  border: 1px solid #111;

  border-radius: 5px;

  color: #fff;

  font-size: 0.7rem;
}

.alert-success i {
  color: #bbb;
}


/* =========================================================
   ASSESSMENT GRID
========================================================= */

.assessment-grid {
  display: grid;

  grid-template-columns:
    repeat(auto-fill,
      minmax(285px, 1fr));

  gap: 10px;
}


/* =========================================================
   ASSESSMENT CARD
========================================================= */

.assessment-card {
  min-width: 0;

  display: flex;

  flex-direction: column;

  padding: 15px;

  background: #fff;

  border: 1px solid var(--line);

  border-radius: 7px;

  transition:
    border-color 0.15s ease,
    box-shadow 0.15s ease,
    transform 0.15s ease;
}

.assessment-card:hover {
  border-color: #cccccc;

  box-shadow:
    0 8px 24px rgba(0, 0, 0, 0.05);

  transform:
    translateY(-1px);
}


/* =========================================================
   CARD HEADER
========================================================= */

.assessment-header {
  display: flex;

  align-items: flex-start;

  gap: 10px;
}

.assessment-icon {
  width: 38px;
  height: 38px;

  flex: 0 0 38px;

  display: grid;
  place-items: center;

  border-radius: 5px;

  font-size: 0.8rem;
}

.type-default {
  background: #f1f1f1;
  color: #444;
}

.type-image {
  background: #f1f1f1;
  color: #333;
}

.type-drag {
  background: #e9e9e9;
  color: #222;
}

.type-scenario {
  background: #f3f3f3;
  color: #444;
}

.type-virtual {
  background: #ededed;
  color: #222;
}

.type-multiple {
  background: #f2f2f2;
  color: #333;
}

.assessment-heading {
  min-width: 0;

  flex: 1;
}

.title-line {
  display: flex;

  align-items: baseline;

  gap: 6px;

  min-width: 0;
}

.assessment-title {
  min-width: 0;

  margin: 0;

  color: #171717;

  font-size: 0.76rem;
  font-weight: 700;

  line-height: 1.35;

  overflow: hidden;

  display: -webkit-box;

  -webkit-line-clamp: 2;

  -webkit-box-orient: vertical;
}

.assessment-id {
  flex: 0 0 auto;

  color: #b0b0b0;

  font-size: 0.58rem;
  font-weight: 650;
}

.assessment-type {
  display: inline-block;

  margin-top: 4px;

  color: #999;

  font-size: 0.59rem;

  text-transform: uppercase;

  letter-spacing: 0.07em;
}


/* =========================================================
   DESCRIPTION
========================================================= */

.assessment-description {
  min-height: 38px;

  margin:
    14px 0 12px;

  color: #777;

  font-size: 0.7rem;

  line-height: 1.55;

  display: -webkit-box;

  -webkit-line-clamp: 2;

  -webkit-box-orient: vertical;

  overflow: hidden;
}


/* =========================================================
   META
========================================================= */

.assessment-meta {
  display: grid;

  grid-template-columns:
    0.7fr 1fr 0.9fr;

  gap: 6px;

  padding:
    10px 0;

  border-top:
    1px solid var(--soft-line);

  border-bottom:
    1px solid var(--soft-line);
}

.meta-item {
  min-width: 0;

  display: flex;

  flex-direction: column;

  gap: 4px;
}

.meta-label {
  color: #aaa;

  font-size: 0.55rem;
  font-weight: 700;

  text-transform: uppercase;

  letter-spacing: 0.08em;
}

.meta-value {
  min-width: 0;

  color: #444;

  font-size: 0.63rem;
  font-weight: 650;

  white-space: nowrap;

  overflow: hidden;

  text-overflow: ellipsis;
}


/* =========================================================
   DIFFICULTY
========================================================= */

.difficulty-badge {
  width: fit-content;

  padding:
    3px 7px;

  border-radius: 4px;

  font-size: 0.56rem;
  font-weight: 700;

  text-transform: capitalize;
}

.level-easy {
  background: #f0f0f0;
  color: #444;
}

.level-medium {
  background: #e5e5e5;
  color: #222;
}

.level-hard {
  background: #d8d8d8;
  color: #111;
}

.level-default {
  background: #f4f4f4;
  color: #888;
}


/* =========================================================
   FOOTER
========================================================= */

.assessment-footer {
  display: flex;

  align-items: center;
  justify-content: space-between;

  gap: 10px;

  padding-top: 11px;
}

.availability {
  display: flex;

  align-items: center;

  gap: 6px;

  color: #999;

  font-size: 0.59rem;
}

.availability-dot {
  width: 5px;
  height: 5px;

  border-radius: 50%;

  background: #bbb;
}

.availability-dot.published {
  background: #111;
}


/* =========================================================
   ACTION BUTTONS
========================================================= */

.card-actions {
  display: flex;

  gap: 4px;
}

.icon-btn {
  width: 29px;
  height: 29px;

  display: grid;
  place-items: center;

  border: 1px solid #e4e4e4;

  border-radius: 4px;

  background: #fff;

  color: #777;

  font-size: 0.64rem;

  cursor: pointer;

  transition:
    background 0.15s ease,
    border-color 0.15s ease,
    color 0.15s ease;
}

.icon-btn.edit:hover {
  background: #111;

  border-color: #111;

  color: #fff;
}

.icon-btn.delete:hover {
  background: #444;

  border-color: #444;

  color: #fff;
}


/* =========================================================
   EMPTY
========================================================= */

.empty-state {
  display: flex;

  flex-direction: column;

  align-items: center;

  justify-content: center;

  min-height: 300px;

  padding: 30px;

  background: #fff;

  border:
    1px dashed #dcdcdc;

  border-radius: 7px;

  text-align: center;
}

.empty-icon {
  width: 42px;
  height: 42px;

  display: grid;
  place-items: center;

  margin-bottom: 12px;

  background: #f3f3f3;

  border-radius: 6px;

  color: #777;
}

.empty-state h3 {
  margin: 0;

  color: #222;

  font-size: 0.8rem;
}

.empty-state p {
  margin: 5px 0 0;

  color: #999;

  font-size: 0.66rem;
}

.add-btn.subtle {
  margin-top: 15px;
}


/* =========================================================
   LOADING
========================================================= */

.loading-state {
  min-height: 300px;

  display: flex;

  align-items: center;
  justify-content: center;

  gap: 12px;

  color: #777;
}

.loading-state strong {
  display: block;

  color: #333;

  font-size: 0.72rem;
}

.loading-state p {
  margin: 3px 0 0;

  color: #aaa;

  font-size: 0.61rem;
}

.spinner {
  width: 28px;
  height: 28px;

  border:
    2px solid #e5e5e5;

  border-top-color: #111;

  border-radius: 50%;

  animation:
    spin 0.7s linear infinite;
}

@keyframes spin {

  to {
    transform: rotate(360deg);
  }

}


/* =========================================================
   TRANSITION
========================================================= */

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;

  transform:
    translateY(-5px);
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 850px) {

  .header-row {
    align-items: flex-start;

    flex-direction: column;
  }

  .header-actions {
    width: 100%;

    justify-content: space-between;
  }

  .assessment-grid {
    grid-template-columns:
      repeat(2,
        minmax(0, 1fr));
  }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 600px) {

  .page-wrapper {
    padding:
      14px 10px;
  }

  .header-actions {
    align-items: stretch;

    flex-direction: column;
  }

  .sort-group {
    width: 100%;

    overflow-x: auto;
  }

  .sort-chip {
    flex: 1;

    justify-content: center;

    white-space: nowrap;
  }

  .add-btn {
    width: 100%;
  }

  .assessment-grid {
    grid-template-columns: 1fr;
  }

}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 400px) {

  .heading-main {
    align-items: flex-start;
  }

  .title-icon {
    width: 34px;
    height: 34px;

    flex-basis: 34px;
  }

  .assessment-meta {
    grid-template-columns:
      0.65fr 1fr 0.9fr;
  }

}
</style>