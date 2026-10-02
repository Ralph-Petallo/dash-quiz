<template>
  <div class="page-wrapper">

    <section class="admin-section">

      <!-- HEADER -->
      <div class="page-header">

        <div class="title-row">

          <div class="title-icon">
            <i class="fa-solid fa-chart-line"></i>
          </div>

          <div>
            <h3 class="section-title">
              Assessment Records
            </h3>

            <p class="section-subtitle">
              Review completed Dasher assessment attempts
            </p>
          </div>

        </div>


        <div class="header-tools">

          <div class="record-count">
            <strong>
              {{ filteredRecords.length }}
            </strong>

            <span>
              Records
            </span>
          </div>


          <div class="search-wrap">

            <i class="fas fa-search"></i>

            <input type="text" v-model="searchQuery" placeholder="Search dasher or assessment..."
              class="search-input" />

          </div>

        </div>

      </div>


      <!-- LOADING -->
      <div v-if="loading" class="table-card">

        <div class="loading-state">

          <div class="spinner"></div>

          <p>
            Loading records...
          </p>

        </div>

      </div>


      <!-- RECORDS -->
      <div v-else class="table-card">

        <!-- TOOLBAR -->
        <div class="table-toolbar">

          <div class="toolbar-title">

            <span>
              Assessment Activity
            </span>

            <small>
              {{ records.length }} total
            </small>

          </div>


          <div class="toolbar-info">

            <i class="fa-solid fa-arrow-down-a-z"></i>

            Click column headers to sort

          </div>

        </div>


        <!-- TABLE -->
        <div class="table-scroll">

          <table class="styled-table">

            <thead>

              <tr>

                <th @click="sortBy('user_name')">

                  <span>
                    Dasher
                  </span>

                  <i class="fas fa-sort"></i>

                </th>


                <th @click="sortBy('quiz_title')">

                  <span>
                    Assessment
                  </span>

                  <i class="fas fa-sort"></i>

                </th>


                <th @click="sortBy('score')">

                  <span>
                    Score
                  </span>

                  <i class="fas fa-sort"></i>

                </th>


                <th>

                  <span>
                    Result
                  </span>

                </th>


                <th @click="sortBy('created_at')">

                  <span>
                    Completed
                  </span>

                  <i class="fas fa-sort"></i>

                </th>


                <th class="action-header">
                  <span>View</span>
                </th>

              </tr>

            </thead>


            <tbody>

              <!-- EMPTY -->
              <tr v-if="paginatedRecords.length === 0">

                <td colspan="6" class="empty-state">

                  <i class="fa-solid fa-inbox"></i>

                  <strong>
                    No assessment records
                  </strong>

                  <span>
                    Completed assessment attempts will appear here.
                  </span>

                </td>

              </tr>


              <!-- RECORD -->
              <tr v-for="rec in paginatedRecords" :key="rec.id">

                <!-- DASHER -->
                <td>

                  <div class="user-box">

                    <img :src="profileImageUrl(rec.user)" :alt="rec.user
                        ? rec.user.first_name + ' ' + rec.user.last_name
                        : 'Dasher'
                      " class="user-avatar" />

                    <div class="user-details">

                      <span class="user-name">

                        {{
                          rec.user
                            ? rec.user.first_name + ' ' + rec.user.last_name
                            : 'User #' + rec.user_id
                        }}

                      </span>

                      <span class="user-id">
                        ID #{{ rec.user_id }}
                      </span>

                    </div>

                  </div>

                </td>


                <!-- ASSESSMENT -->
                <td>

                  <div class="assessment-box">

                    <div class="assessment-icon">
                      <i class="fa-solid fa-file-lines"></i>
                    </div>

                    <div class="assessment-info">

                      <span class="assessment-name">

                        {{
                          rec.quiz
                            ? rec.quiz.title
                            : 'Deleted Assessment'
                        }}

                      </span>

                      <span class="assessment-type">
                        NC II Readiness
                      </span>

                    </div>

                  </div>

                </td>


                <!-- SCORE -->
                <td>

                  <span class="score-badge" :class="getScoreClass(rec)">

                    <i class="fas fa-star"></i>

                    <strong>
                      {{ rec.score }}
                    </strong>

                    <span>
                      /
                      {{ rec.total_questions || 10 }}
                    </span>

                  </span>

                </td>


                <!-- RESULT -->
                <td>

                  <span class="result-badge" :class="getResultClass(rec)">

                    <span class="result-dot"></span>

                    {{ getResultLabel(rec) }}

                  </span>

                </td>


                <!-- DATE -->
                <td class="date-cell">

                  <span>
                    {{ formatDate(rec.created_at) }}
                  </span>

                </td>


                <!-- ACTION -->
                <td class="action-cell">

                  <button class="view-btn" title="View assessment record" @click="viewRecord(rec)">

                    <i class="fas fa-eye"></i>

                  </button>

                </td>

              </tr>

            </tbody>

          </table>

        </div>


        <!-- PAGINATION -->
        <div v-if="totalPages > 1" class="pagination">

          <button :disabled="currentPage === 1" @click="currentPage--" title="Previous page">

            <i class="fas fa-chevron-left"></i>

          </button>


          <span class="page-number">

            {{ currentPage }}

            <span>
              /
            </span>

            {{ totalPages }}

          </span>


          <button :disabled="currentPage === totalPages" @click="currentPage++" title="Next page">

            <i class="fas fa-chevron-right"></i>

          </button>

        </div>

      </div>

    </section>

  </div>
</template>


<script setup>

import {
  ref,
  computed,
  onMounted,
  watch
} from 'vue'

import axios from 'axios'


const records = ref([])

const loading = ref(false)


const currentPage = ref(1)

const perPage = 10


const sortKey = ref('created_at')

const sortOrder = ref('desc')


const searchQuery = ref('')


/* =========================================================
   FETCH RECORDS
========================================================= */

const fetchRecords = async () => {

  if (loading.value) {
    return
  }

  try {

    loading.value = true

    const { data } =
      await axios.get(
        '/api/admin/records'
      )

    records.value =
      data?.data ?? []

    console.log(
      'Fetched records:',
      records.value
    )

  } catch (error) {

    console.error(
      'Failed to fetch records:',
      error
    )

  } finally {

    loading.value = false

  }

}


/* =========================================================
   PROFILE IMAGE
========================================================= */

const profileImageUrl = (user) => {

  const profilepath =
    `/storage/images/profiles`

  return user?.profile_photo
    ? `${profilepath}/${user.profile_photo}`
    : `${profilepath}/default.png`

}


/* =========================================================
   DATE
========================================================= */

const formatDate = (dateString) => {

  if (!dateString) {
    return 'N/A'
  }

  const date =
    new Date(dateString)

  return date.toLocaleDateString(
    'en-US',
    {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    }
  )

}


/* =========================================================
   SCORE
========================================================= */

const getScorePercent = (rec) => {

  const total =
    rec.total_questions || 10

  if (!total) {
    return 0
  }

  return (
    rec.score / total
  ) * 100

}


const getScoreClass = (rec) => {

  const percent =
    getScorePercent(rec)

  if (percent >= 80) {
    return 'score-high'
  }

  if (percent >= 50) {
    return 'score-mid'
  }

  return 'score-low'

}


/* =========================================================
   RESULT
========================================================= */

const getResultLabel = (rec) => {

  const percent =
    getScorePercent(rec)

  if (percent >= 80) {
    return 'Ready'
  }

  if (percent >= 50) {
    return 'Needs Review'
  }

  return 'Not Ready'

}


const getResultClass = (rec) => {

  const percent =
    getScorePercent(rec)

  if (percent >= 80) {
    return 'result-ready'
  }

  if (percent >= 50) {
    return 'result-review'
  }

  return 'result-not-ready'

}


/* =========================================================
   MAP RECORDS
========================================================= */

const mappedRecords = computed(() => {

  return records.value.map(record => ({

    ...record,

    user_name:
      record.user
        ? `${record.user.first_name} ${record.user.last_name}`
        : '',

    quiz_title:
      record.quiz
        ? record.quiz.title
        : ''

  }))

})


/* =========================================================
   FILTER + SORT
========================================================= */

const filteredRecords = computed(() => {

  const query =
    searchQuery.value
      .toLowerCase()
      .trim()


  let filtered =
    mappedRecords.value.filter(record => {

      return (
        record.user_name
          .toLowerCase()
          .includes(query) ||

        record.quiz_title
          .toLowerCase()
          .includes(query)
      )

    })


  filtered.sort((a, b) => {

    let A =
      a[sortKey.value]

    let B =
      b[sortKey.value]


    if (
      sortKey.value === 'created_at'
    ) {

      A =
        new Date(A).getTime()

      B =
        new Date(B).getTime()

    }


    if (
      sortKey.value === 'score'
    ) {

      A =
        Number(A ?? 0)

      B =
        Number(B ?? 0)

    }


    if (
      typeof A === 'string'
    ) {

      return sortOrder.value === 'asc'
        ? A.localeCompare(B)
        : B.localeCompare(A)

    }


    return sortOrder.value === 'asc'
      ? A - B
      : B - A

  })


  return filtered

})


/* =========================================================
   PAGINATION
========================================================= */

const totalPages = computed(() => {

  return Math.ceil(
    filteredRecords.value.length /
    perPage
  )

})


const paginatedRecords = computed(() => {

  const start =
    (currentPage.value - 1) *
    perPage

  return filteredRecords.value.slice(
    start,
    start + perPage
  )

})


/* =========================================================
   SORT
========================================================= */

const sortBy = (key) => {

  if (sortKey.value === key) {

    sortOrder.value =
      sortOrder.value === 'asc'
        ? 'desc'
        : 'asc'

  } else {

    sortKey.value = key

    sortOrder.value = 'asc'

  }

}


/* =========================================================
   VIEW RECORD
========================================================= */

const viewRecord = (record) => {

  console.log(
    'View record:',
    record
  )

  /*
   * Later:
   * Open a record details modal
   * or navigate to a detailed attempt page.
   */

}


/* =========================================================
   RESET PAGE
========================================================= */

watch(
  searchQuery,
  () => {

    currentPage.value = 1

  }
)


watch(
  filteredRecords,
  () => {

    if (
      currentPage.value >
      totalPages.value
    ) {

      currentPage.value =
        totalPages.value || 1

    }

  }
)


/* =========================================================
   LIFECYCLE
========================================================= */

onMounted(
  fetchRecords
)

</script>

<style scoped>
*,
*::before,
*::after {
  box-sizing: border-box;
}


/* =========================================================
   WRAPPER
========================================================= */

.page-wrapper {
  width: 100%;
  min-height: 100%;

  padding: 1rem;

  background: #fafafa;
}

.admin-section {
  width: 100%;
  max-width: 1400px;

  margin: 0 auto;
}


/* =========================================================
   HEADER
========================================================= */

.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;

  gap: 20px;

  margin-bottom: 14px;
}

.title-row {
  display: flex;
  align-items: center;

  gap: 12px;
}

.title-icon {
  width: 42px;
  height: 42px;

  display: grid;
  place-items: center;

  background: #111;
  color: #fff;

  border-radius: 8px;

  font-size: 0.9rem;
}

.section-title {
  margin: 0;

  color: #111;

  font-size: 1.05rem;
  font-weight: 750;
}

.section-subtitle {
  margin: 4px 0 0;

  color: #888;

  font-size: 0.75rem;
}


/* =========================================================
   HEADER TOOLS
========================================================= */

.header-tools {
  display: flex;
  align-items: center;

  gap: 8px;
}

.record-count {
  height: 36px;

  display: flex;
  align-items: center;

  gap: 5px;

  padding: 0 11px;

  background: #fff;

  border: 1px solid #e3e3e3;
  border-radius: 7px;

  white-space: nowrap;
}

.record-count strong {
  color: #111;

  font-size: 0.78rem;
}

.record-count span {
  color: #999;

  font-size: 0.68rem;
}

.search-wrap {
  width: 260px;
  height: 36px;

  display: flex;
  align-items: center;

  gap: 8px;

  padding: 0 11px;

  background: #fff;

  border: 1px solid #ddd;
  border-radius: 7px;

  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.search-wrap:focus-within {
  border-color: #111;

  box-shadow:
    0 0 0 2px rgba(0, 0, 0, 0.05);
}

.search-wrap i {
  color: #999;

  font-size: 0.7rem;
}

.search-input {
  width: 100%;

  border: none;
  outline: none;

  background: transparent;

  color: #111;

  font-size: 0.74rem;
}

.search-input::placeholder {
  color: #aaa;
}


/* =========================================================
   CARD
========================================================= */

.table-card {
  width: 100%;

  background: #fff;

  border: 1px solid #e4e4e4;
  border-radius: 9px;

  overflow: hidden;
}


/* =========================================================
   TOOLBAR
========================================================= */

.table-toolbar {
  min-height: 48px;

  display: flex;
  align-items: center;
  justify-content: space-between;

  padding: 0 14px;

  border-bottom: 1px solid #ededed;
}

.toolbar-title {
  display: flex;
  align-items: center;

  gap: 8px;

  color: #222;

  font-size: 0.75rem;
  font-weight: 700;
}

.toolbar-title small {
  color: #aaa;

  font-size: 0.65rem;
  font-weight: 500;
}

.toolbar-info {
  display: flex;
  align-items: center;

  gap: 5px;

  color: #aaa;

  font-size: 0.64rem;
}


/* =========================================================
   TABLE
========================================================= */

.table-scroll {
  width: 100%;

  overflow-x: auto;
}

.table-scroll::-webkit-scrollbar {
  height: 5px;
}

.table-scroll::-webkit-scrollbar-thumb {
  background: #d5d5d5;

  border-radius: 5px;
}

.styled-table {
  width: 100%;

  min-width: 850px;

  border-collapse: collapse;

  table-layout: fixed;
}

.styled-table th {
  height: 42px;

  padding: 0 12px;

  background: #fafafa;

  border-bottom: 1px solid #e8e8e8;

  color: #888;

  font-size: 0.65rem;
  font-weight: 700;

  text-align: left;

  text-transform: uppercase;
  letter-spacing: 0.05em;

  cursor: pointer;

  user-select: none;
}

.styled-table th:hover {
  color: #111;
}

.styled-table th i {
  margin-left: 4px;

  color: #aaa;

  font-size: 0.55rem;
}

.styled-table th:nth-child(1) {
  width: 23%;
}

.styled-table th:nth-child(2) {
  width: 27%;
}

.styled-table th:nth-child(3) {
  width: 15%;
}

.styled-table th:nth-child(4) {
  width: 15%;
}

.styled-table th:nth-child(5) {
  width: 14%;
}

.styled-table th:nth-child(6) {
  width: 6%;
}

.styled-table td {
  height: 60px;

  padding: 8px 12px;

  border-bottom: 1px solid #f0f0f0;

  vertical-align: middle;

  color: #333;

  font-size: 0.74rem;
}

.styled-table tbody tr:hover {
  background: #fafafa;
}

.styled-table tbody tr:last-child td {
  border-bottom: none;
}


/* =========================================================
   USER
========================================================= */

.user-box {
  display: flex;
  align-items: center;

  gap: 9px;

  min-width: 0;
}

.user-avatar {
  width: 34px;
  height: 34px;

  flex: 0 0 34px;

  object-fit: cover;

  border-radius: 50%;

  border: 1px solid #ddd;

  background: #f5f5f5;
}

.user-details {
  min-width: 0;

  display: flex;
  flex-direction: column;

  gap: 3px;
}

.user-name {
  color: #222;

  font-size: 0.75rem;
  font-weight: 650;

  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-id {
  color: #aaa;

  font-size: 0.6rem;
}


/* =========================================================
   ASSESSMENT
========================================================= */

.assessment-box {
  display: flex;
  align-items: center;

  gap: 9px;

  min-width: 0;
}

.assessment-icon {
  width: 30px;
  height: 30px;

  flex: 0 0 30px;

  display: grid;
  place-items: center;

  background: #f3f3f3;

  border-radius: 6px;

  color: #444;

  font-size: 0.7rem;
}

.assessment-info {
  min-width: 0;

  display: flex;
  flex-direction: column;

  gap: 3px;
}

.assessment-name {
  color: #222;

  font-size: 0.73rem;
  font-weight: 600;

  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.assessment-type {
  color: #aaa;

  font-size: 0.6rem;
}


/* =========================================================
   SCORE
========================================================= */

.score-badge {
  display: inline-flex;
  align-items: center;

  gap: 4px;

  padding: 5px 8px;

  border-radius: 6px;

  font-size: 0.68rem;

  white-space: nowrap;
}

.score-badge i {
  font-size: 0.58rem;
}

.score-high {
  background: #f1f1f1;
  color: #222;
}

.score-mid {
  background: #f5f5f5;
  color: #555;
}

.score-low {
  background: #f7f7f7;
  color: #888;
}


/* =========================================================
   RESULT
========================================================= */

.result-badge {
  display: inline-flex;
  align-items: center;

  gap: 6px;

  font-size: 0.66rem;
  font-weight: 650;
}

.result-dot {
  width: 6px;
  height: 6px;

  border-radius: 50%;

  background: currentColor;
}

.result-ready {
  color: #222;
}

.result-review {
  color: #666;
}

.result-not-ready {
  color: #999;
}


/* =========================================================
   DATE
========================================================= */

.date-cell {
  color: #777 !important;

  white-space: nowrap;
}


/* =========================================================
   ACTION
========================================================= */

.action-header {
  text-align: center !important;
}

.action-cell {
  text-align: center;
}

.view-btn {
  width: 30px;
  height: 30px;

  display: inline-grid;
  place-items: center;

  background: #fff;

  border: 1px solid #ddd;
  border-radius: 6px;

  color: #555;

  cursor: pointer;

  transition:
    background 0.15s ease,
    color 0.15s ease,
    border-color 0.15s ease;
}

.view-btn:hover {
  background: #111;

  border-color: #111;

  color: #fff;
}

.view-btn i {
  font-size: 0.68rem;
}


/* =========================================================
   EMPTY
========================================================= */

.empty-state {
  height: 180px !important;

  text-align: center !important;

  vertical-align: middle !important;
}

.empty-state i {
  display: block;

  margin-bottom: 8px;

  color: #bbb;

  font-size: 1.1rem;
}

.empty-state strong {
  display: block;

  margin-bottom: 3px;

  color: #555;

  font-size: 0.75rem;
}

.empty-state span {
  color: #aaa;

  font-size: 0.65rem;
}


/* =========================================================
   LOADING
========================================================= */

.loading-state {
  min-height: 280px;

  display: flex;
  flex-direction: column;

  align-items: center;
  justify-content: center;

  gap: 10px;

  color: #999;

  font-size: 0.75rem;
}

.spinner {
  width: 28px;
  height: 28px;

  border: 3px solid #e8e8e8;

  border-top-color: #111;

  border-radius: 50%;

  animation: spin 0.8s linear infinite;
}

@keyframes spin {

  to {
    transform: rotate(360deg);
  }

}


/* =========================================================
   PAGINATION
========================================================= */

.pagination {
  min-height: 52px;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 12px;

  padding: 8px;

  border-top: 1px solid #ededed;
}

.pagination button {
  width: 30px;
  height: 30px;

  display: grid;
  place-items: center;

  background: #fff;

  border: 1px solid #ddd;
  border-radius: 6px;

  color: #555;

  cursor: pointer;
}

.pagination button:hover:not(:disabled) {
  background: #111;

  border-color: #111;

  color: #fff;
}

.pagination button:disabled {
  opacity: 0.35;

  cursor: not-allowed;
}

.page-number {
  color: #777;

  font-size: 0.7rem;
}

.page-number span {
  margin: 0 4px;

  color: #bbb;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 768px) {

  .page-wrapper {
    padding: 10px;
  }

  .page-header {
    align-items: stretch;

    flex-direction: column;
  }

  .header-tools {
    width: 100%;
  }

  .search-wrap {
    flex: 1;

    width: auto;
  }

  .table-toolbar {
    min-height: 44px;
  }

  .toolbar-info {
    display: none;
  }

}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 480px) {

  .title-icon {
    width: 38px;
    height: 38px;
  }

  .section-title {
    font-size: 0.95rem;
  }

  .section-subtitle {
    font-size: 0.68rem;
  }

  .record-count {
    height: 34px;
  }

  .search-wrap {
    height: 34px;
  }

}
</style>