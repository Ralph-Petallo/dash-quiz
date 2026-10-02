<template>
  <div class="dashboard-wrapper">

    <section class="admin-section">

      <!-- HEADER -->
      <div class="page-header">

        <div class="page-heading">
          <div class="title-row">
            <div class="title-icon">
              <i class="fa-solid fa-users"></i>
            </div>

            <div>
              <h3 class="section-title">
                Registered Dashers
              </h3>

              <p class="section-subtitle">
                Manage student accounts and activity
              </p>
            </div>
          </div>
        </div>

        <div class="header-tools">

          <div class="user-count">
            <span class="count-value">
              {{ filteredUsers.length }}
            </span>

            <span class="count-label">
              {{ filteredUsers.length === 1 ? 'User' : 'Users' }}
            </span>
          </div>

          <div class="search-wrapper">
            <i class="fas fa-search"></i>

            <input type="text" v-model="searchQuery" placeholder="Search users..." class="search-input" />
          </div>

        </div>

      </div>


      <!-- SUCCESS / NOTIFICATION -->
      <transition name="fade">
        <div v-if="successMessage" class="alert-success">
          {{ successMessage }}
        </div>
      </transition>


      <!-- LOADING -->
      <div v-if="loading" class="table-card">

        <div class="loading-state">

          <div class="spinner"></div>

          <p>
            Loading users...
          </p>

        </div>

      </div>


      <!-- USERS TABLE -->
      <div v-else class="table-card">

        <!-- TABLE HEADER -->
        <div class="table-toolbar">

          <div class="toolbar-title">
            <span>All Accounts</span>

            <small>
              {{ users.length }} registered
            </small>
          </div>

          <div class="toolbar-info">
            <i class="fa-solid fa-circle-info"></i>
            Click a column to sort
          </div>

        </div>


        <!-- TABLE -->
        <div class="table-scroll">

          <table class="styled-table">

            <thead>

              <tr>

                <th class="id-column" @click="sortBy('id')">
                  <span>ID</span>
                  <i class="fas fa-sort"></i>
                </th>


                <th @click="sortBy('full_name')">
                  <span>User</span>
                  <i class="fas fa-sort"></i>
                </th>


                <th @click="sortBy('email')">
                  <span>Email</span>
                  <i class="fas fa-sort"></i>
                </th>


                <th @click="sortBy('quizzes_taken')">
                  <span>Assessments</span>
                  <i class="fas fa-sort"></i>
                </th>


                <th>
                  <span>Status</span>
                </th>


                <th @click="sortBy('created_at')">
                  <span>Joined</span>
                  <i class="fas fa-sort"></i>
                </th>


                <th class="actions-header">
                  <span>Actions</span>
                </th>

              </tr>

            </thead>


            <tbody>

              <!-- EMPTY -->
              <tr v-if="paginatedUsers.length === 0">

                <td colspan="7" class="empty-state">

                  <i class="fa-solid fa-user-slash"></i>

                  <strong>
                    No users found
                  </strong>

                  <span>
                    Try another search term.
                  </span>

                </td>

              </tr>


              <!-- USERS -->
              <tr v-for="user in paginatedUsers" :key="user.id">

                <!-- ID -->
                <td class="id-cell">
                  #{{ user.id }}
                </td>


                <!-- USER -->
                <td class="user-cell">

                  <div class="user-box">

                    <img :src="setProfileURL(user)" :alt="user.first_name + ' ' + user.last_name" class="user-avatar" />

                    <div class="user-details">

                      <span class="user-name">
                        {{ user.first_name }}
                        {{ user.last_name }}
                      </span>

                      <span class="user-role">
                        Dasher
                      </span>

                    </div>

                  </div>

                </td>


                <!-- EMAIL -->
                <td class="email-cell">

                  <span>
                    {{ user.email }}
                  </span>

                </td>


                <!-- ASSESSMENTS -->
                <td class="assessment-cell">

                  <span class="assessment-count">
                    {{ user.quizzes_taken ?? 0 }}
                  </span>

                  <span class="assessment-label">
                    taken
                  </span>

                </td>


                <!-- STATUS -->
                <td>

                  <span class="status-badge" :class="user.active_status ? 'active' : 'inactive'">

                    <span class="status-dot"></span>

                    {{
                      user.active_status
                        ? 'Active'
                        : 'Inactive'
                    }}

                  </span>

                </td>


                <!-- JOINED -->
                <td class="date-cell">

                  {{ formatDate(user.created_at) }}

                </td>


                <!-- ACTIONS -->
                <td class="actions-col">

                  <button @click="openEdit(user)" class="action-btn edit" title="Edit user">
                    <i class="fas fa-pen"></i>
                  </button>


                  <button @click="confirmDelete(user.id)" class="action-btn delete" title="Delete user">
                    <i class="fas fa-trash"></i>
                  </button>

                </td>

              </tr>

            </tbody>

          </table>

        </div>


        <!-- PAGINATION -->
        <div v-if="totalPages > 1" class="pagination">

          <button :disabled="currentPage === 1" @click="currentPage--" class="page-btn">
            <i class="fas fa-chevron-left"></i>
            Prev
          </button>


          <div class="page-indicator">

            <strong>
              {{ currentPage }}
            </strong>

            <span>
              /
            </span>

            <span>
              {{ totalPages }}
            </span>

          </div>


          <button :disabled="currentPage === totalPages" @click="currentPage++" class="page-btn">
            Next
            <i class="fas fa-chevron-right"></i>
          </button>

        </div>

      </div>

    </section>


    <!-- TOAST -->
    <ToastNotification :message="notification.message" :type="notification.type" @clear="notification.message = ''" />


    <!-- EDIT USER -->
    <EditUserModal :user="selectedUser" :show="openEditModal" @close="openEditModal = false" @notif="handleNotify"
      @save="handleSave" />

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

import ToastNotification
  from "@/components/ToastNotification.vue"

import EditUserModal
  from '@/components/AdminSide/EditUserModal.vue'


const users = ref([])

const loading = ref(true)


const openEditModal = ref(false)

const selectedUser = ref(null)


const currentPage = ref(1)

const perPage = 10


const sortKey = ref('id')

const sortOrder = ref('asc')


const searchQuery = ref('')


const notification = ref({
  message: '',
  type: 'success'
})


/* =========================================================
   PROFILE
========================================================= */

const setProfileURL = (user) => {

  const profilepath =
    `/storage/images/profiles`

  return user?.profile_photo
    ? `${profilepath}/${user.profile_photo}`
    : `${profilepath}/default.png`

}


/* =========================================================
   FETCH USERS
========================================================= */

const fetchUsers = async () => {

  try {

    const response =
      await axios.get('/api/admin/user')

    users.value =
      response.data?.data ?? []

    console.log(
      'Fetched users:',
      users.value
    )

  } catch (error) {

    console.error(
      'Error fetching users:',
      error
    )

  } finally {

    loading.value = false

  }

}


/* =========================================================
   NOTIFICATION
========================================================= */

const showToast = (
  msg,
  type = 'success'
) => {

  notification.value.message = msg

  notification.value.type = type

  setTimeout(() => {

    notification.value.message = ''

  }, 2500)

}


/* =========================================================
   EDIT
========================================================= */

const openEdit = (user) => {

  selectedUser.value = user

  openEditModal.value = true

}


/* =========================================================
   DELETE
========================================================= */

const confirmDelete = async (id) => {

  if (
    !confirm(
      `Delete user ID: ${id}?`
    )
  ) {
    return
  }

  try {

    await axios.delete(
      `/api/admin/user/delete/${id}`
    )

    showToast(
      "User deleted",
      "success"
    )

    await fetchUsers()

  } catch (error) {

    console.error(
      "Delete user failed:",
      error
    )

    showToast(
      "Failed to delete user",
      "error"
    )

  }

}


/* =========================================================
   DATE
========================================================= */

const formatDate = (date) => {

  if (!date) {
    return '—'
  }

  return new Date(date)
    .toLocaleDateString(
      'en-US',
      {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
      }
    )

}


/* =========================================================
   FILTER
========================================================= */

const filteredUsers = computed(() => {

  const q =
    searchQuery.value
      .toLowerCase()
      .trim()

  let result =
    users.value.filter(user => {

      const fullName =
        `${user.first_name} ${user.last_name}`
          .toLowerCase()

      return (
        fullName.includes(q) ||
        user.email
          ?.toLowerCase()
          .includes(q)
      )

    })


  /* SORT */

  result.sort((a, b) => {

    let valueA
    let valueB


    if (sortKey.value === 'full_name') {

      valueA =
        `${a.first_name} ${a.last_name}`
          .toLowerCase()

      valueB =
        `${b.first_name} ${b.last_name}`
          .toLowerCase()

    } else {

      valueA =
        a[sortKey.value]

      valueB =
        b[sortKey.value]

    }


    if (
      sortKey.value === 'created_at'
    ) {

      valueA =
        new Date(valueA).getTime()

      valueB =
        new Date(valueB).getTime()

    }


    if (
      typeof valueA === 'string'
    ) {

      return sortOrder.value === 'asc'
        ? valueA.localeCompare(valueB)
        : valueB.localeCompare(valueA)

    }


    return sortOrder.value === 'asc'
      ? (valueA ?? 0) - (valueB ?? 0)
      : (valueB ?? 0) - (valueA ?? 0)

  })


  return result

})


/* =========================================================
   PAGINATION
========================================================= */

const totalPages = computed(() => {

  return Math.ceil(
    filteredUsers.value.length /
    perPage
  )

})


const paginatedUsers = computed(() => {

  const start =
    (currentPage.value - 1) *
    perPage

  return filteredUsers.value.slice(
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
   SEARCH RESET
========================================================= */

watch(
  searchQuery,
  () => {

    currentPage.value = 1

  }
)


watch(
  filteredUsers,
  () => {

    if (
      currentPage.value >
      totalPages.value
    ) {

      currentPage.value = 1

    }

  }
)


/* =========================================================
   LIFECYCLE
========================================================= */

onMounted(
  fetchUsers
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

.dashboard-wrapper {
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
   PAGE HEADER
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

  gap: 10px;
}

.user-count {
  height: 36px;

  display: flex;
  align-items: center;

  gap: 5px;

  padding: 0 11px;

  background: #fff;

  border: 1px solid #e5e5e5;
  border-radius: 7px;

  white-space: nowrap;
}

.count-value {
  color: #111;

  font-size: 0.78rem;
  font-weight: 750;
}

.count-label {
  color: #999;

  font-size: 0.7rem;
}

.search-wrapper {
  width: 240px;
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

.search-wrapper:focus-within {
  border-color: #111;

  box-shadow:
    0 0 0 2px rgba(0, 0, 0, 0.05);
}

.search-wrapper i {
  color: #999;

  font-size: 0.7rem;
}

.search-input {
  width: 100%;

  border: none;
  outline: none;

  background: transparent;

  color: #111;

  font-size: 0.75rem;
}

.search-input::placeholder {
  color: #aaa;
}


/* =========================================================
   SUCCESS
========================================================= */

.alert-success {
  margin-bottom: 12px;

  padding: 9px 12px;

  background: #f3f3f3;

  border: 1px solid #ddd;
  border-radius: 7px;

  color: #333;

  font-size: 0.75rem;
}


/* =========================================================
   TABLE CARD
========================================================= */

.table-card {
  width: 100%;

  background: #fff;

  border: 1px solid #e4e4e4;
  border-radius: 9px;

  overflow: hidden;
}


/* =========================================================
   TABLE TOOLBAR
========================================================= */

.table-toolbar {
  min-height: 48px;

  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 12px;

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

  font-size: 0.65rem;
}

.toolbar-info i {
  font-size: 0.6rem;
}


/* =========================================================
   TABLE SCROLL
========================================================= */

.table-scroll {
  width: 100%;

  overflow-x: auto;
}

.table-scroll::-webkit-scrollbar {
  height: 5px;
}

.table-scroll::-webkit-scrollbar-track {
  background: #fafafa;
}

.table-scroll::-webkit-scrollbar-thumb {
  background: #d5d5d5;
  border-radius: 5px;
}


/* =========================================================
   TABLE
========================================================= */

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

  font-size: 0.66rem;
  font-weight: 700;

  text-align: left;

  text-transform: uppercase;
  letter-spacing: 0.05em;

  cursor: pointer;
  user-select: none;

  white-space: nowrap;
}

.styled-table th:hover {
  color: #111;
}

.styled-table th i {
  margin-left: 4px;

  color: #aaa;

  font-size: 0.58rem;
}

.styled-table th:nth-child(1) {
  width: 65px;
}

.styled-table th:nth-child(2) {
  width: 22%;
}

.styled-table th:nth-child(3) {
  width: 25%;
}

.styled-table th:nth-child(4) {
  width: 115px;
}

.styled-table th:nth-child(5) {
  width: 100px;
}

.styled-table th:nth-child(6) {
  width: 115px;
}

.styled-table th:nth-child(7) {
  width: 100px;
}

.styled-table td {
  height: 58px;

  padding: 8px 12px;

  border-bottom: 1px solid #f0f0f0;

  color: #333;

  font-size: 0.75rem;

  text-align: left;

  vertical-align: middle;
}

.styled-table tbody tr {
  transition:
    background 0.15s ease;
}

.styled-table tbody tr:hover {
  background: #fafafa;
}

.styled-table tbody tr:last-child td {
  border-bottom: none;
}


/* =========================================================
   ID
========================================================= */

.id-cell {
  color: #999 !important;

  font-family: monospace;

  font-size: 0.7rem !important;
}


/* =========================================================
   USER
========================================================= */

.user-cell {
  min-width: 180px;
}

.user-box {
  display: flex;
  align-items: center;

  gap: 9px;
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

.user-role {
  color: #aaa;

  font-size: 0.62rem;
}


/* =========================================================
   EMAIL
========================================================= */

.email-cell {
  color: #666 !important;

  overflow: hidden;

  text-overflow: ellipsis;

  white-space: nowrap;
}


/* =========================================================
   ASSESSMENTS
========================================================= */

.assessment-cell {
  display: table-cell;
}

.assessment-count {
  color: #111;

  font-size: 0.78rem;
  font-weight: 700;
}

.assessment-label {
  margin-left: 4px;

  color: #aaa;

  font-size: 0.65rem;
}


/* =========================================================
   STATUS
========================================================= */

.status-badge {
  display: inline-flex;
  align-items: center;

  gap: 6px;

  padding: 4px 7px;

  border-radius: 5px;

  font-size: 0.62rem;
  font-weight: 650;
}

.status-badge.active {
  background: #f2f2f2;
  color: #222;
}

.status-badge.inactive {
  background: #f7f7f7;
  color: #999;
}

.status-dot {
  width: 5px;
  height: 5px;

  border-radius: 50%;

  background: currentColor;
}


/* =========================================================
   DATE
========================================================= */

.date-cell {
  color: #777 !important;

  white-space: nowrap;
}


/* =========================================================
   ACTIONS
========================================================= */

.actions-header {
  text-align: center !important;
}

.actions-col {
  text-align: center !important;
}

.action-btn {
  width: 30px;
  height: 30px;

  display: inline-grid;
  place-items: center;

  margin: 0 2px;

  border: 1px solid #ddd;
  border-radius: 6px;

  background: #fff;

  cursor: pointer;

  transition:
    background 0.15s ease,
    color 0.15s ease,
    border-color 0.15s ease;
}

.action-btn i {
  font-size: 0.68rem;
}

.action-btn.edit {
  color: #555;
}

.action-btn.edit:hover {
  background: #111;
  border-color: #111;
  color: #fff;
}

.action-btn.delete {
  color: #888;
}

.action-btn.delete:hover {
  background: #111;
  border-color: #111;
  color: #fff;
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

  padding: 8px 14px;

  border-top: 1px solid #ededed;
}

.page-btn {
  height: 30px;

  display: flex;
  align-items: center;

  gap: 6px;

  padding: 0 10px;

  background: #fff;

  border: 1px solid #ddd;
  border-radius: 6px;

  color: #555;

  font-size: 0.68rem;
  font-weight: 600;

  cursor: pointer;
}

.page-btn:hover:not(:disabled) {
  background: #111;
  border-color: #111;
  color: #fff;
}

.page-btn:disabled {
  opacity: 0.35;

  cursor: not-allowed;
}

.page-indicator {
  display: flex;
  align-items: center;

  gap: 5px;

  color: #aaa;

  font-size: 0.7rem;
}

.page-indicator strong {
  color: #111;
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
  display: block;

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
   MOBILE
========================================================= */

@media (max-width: 768px) {

  .dashboard-wrapper {
    padding: 10px;
  }

  .page-header {
    align-items: stretch;

    flex-direction: column;

    gap: 12px;
  }

  .header-tools {
    width: 100%;
  }

  .search-wrapper {
    flex: 1;

    width: auto;
  }

  .user-count {
    flex-shrink: 0;
  }

  .table-toolbar {
    align-items: flex-start;

    flex-direction: column;

    justify-content: center;

    gap: 4px;

    padding: 10px 12px;
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

  .header-tools {
    align-items: stretch;
  }

  .user-count {
    height: 34px;
  }

  .search-wrapper {
    height: 34px;
  }

}
</style>