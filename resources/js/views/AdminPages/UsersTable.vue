<template>
  <div class="dashboard-wrapper">
    <section class="admin-section">

      <!-- Success Message -->
      <transition name="fade">
        <div v-if="successMessage" class="alert-success">
          {{ successMessage }}
        </div>
      </transition>

      <!-- Header -->
      <div class="header-row">
        <div>
          <h3 class="section-title">Registered Dashers</h3>
          <p class="section-subtitle">Manage all user accounts</p>
        </div>

        <input type="text" v-model="searchQuery" placeholder="Search users..." class="search-input" />
      </div>

      <!-- Loading -->
      <div v-if="loading" class="table-card">
        <div class="loading-state">
          <div class="spinner"></div>
          <p>Loading records...</p>
        </div>
      </div>

      <!-- TABLE -->
      <div v-else class="table-card">
        <div class="table-scroll">

          <table class="styled-table">
            <thead>
              <tr>
                <th @click="sortBy('id')">
                  <span>ID</span>
                  <i class="fas fa-sort"></i>
                </th>

                <th @click="sortBy('full_name')">
                  <span>Name</span>
                  <i class="fas fa-user"></i>
                </th>

                <th @click="sortBy('email')">
                  <span>Email</span>
                  <i class="fas fa-envelope"></i>
                </th>

                <th @click="sortBy('quizzes_taken')">
                  <span>Quizzes</span>
                  <i class="fas fa-list-ol"></i>
                </th>

                <th @click="sortBy('created_at')">
                  <span>Date</span>
                  <i class="fas fa-calendar"></i>
                </th>

                <th>
                  <span>Actions</span>
                  <i class="fas fa-cogs"></i>
                </th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="paginatedUsers.length === 0">
                <td colspan="6" class="empty-state">No users found.</td>
              </tr>

              <tr v-for="user in paginatedUsers" :key="user.id">
                <td>{{ user.id }}</td>

                <td class="name">
                  {{ user.first_name }} {{ user.last_name }}
                </td>

                <td class="email">
                  {{ user.email }}
                </td>

                <td>
                  {{ user.quizzes_taken }}
                </td>

                <td>
                  {{ formatDate(user.created_at) }}
                </td>

                <td class="actions-col">
                  <button @click="openEdit(user)" class="btn edit">
                    <i class="fas fa-pen"></i>
                  </button>

                  <button @click="confirmDelete(user.id)" class="btn delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

        </div>

        <!-- Pagination -->
        <div class="pagination" v-if="totalPages > 1">
          <button :disabled="currentPage === 1" @click="currentPage--">
            Prev
          </button>

          <span>{{ currentPage }} / {{ totalPages }}</span>

          <button :disabled="currentPage === totalPages" @click="currentPage++">
            Next
          </button>
        </div>

      </div>
    </section>

    <ToastNotification :message="notification.message" :type="notification.type" @clear="notification.message = ''" />

    <EditUserModal :user="selectedUser" :show="openEditModal" @close="openEditModal = false" @notif="handleNotify"
      @save="handleSave" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import axios from 'axios'
import ToastNotification from "@/components/ToastNotification.vue"
import EditUserModal from '@/components/AdminSide/EditUserModal.vue'

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

const fetchUsers = async () => {
  try {
    const response = await axios.get('/api/admin/user')
    users.value = response.data?.data
  } finally {
    loading.value = false
  }
}

const showToast = (msg, type = 'success') => {
  notification.value.message = msg
  notification.value.type = type

  setTimeout(() => {
    notification.value.message = ''
  }, 2500)
}

const openEdit = (user) => {
  selectedUser.value = user
  openEditModal.value = true
}

const confirmDelete = async (id) => {
  if (!confirm(`Delete user ID: ${id}?`)) return
  await axios.delete(`/api/admin/user/delete/${id}`)
  showToast("User deleted", "success")
  fetchUsers()
}

const formatDate = (date) =>
  new Date(date).toLocaleDateString('en-US', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })

const filteredUsers = computed(() => {
  const q = searchQuery.value.toLowerCase()

  return users.value.filter(u =>
    `${u.first_name} ${u.last_name}`.toLowerCase().includes(q) ||
    u.email?.toLowerCase().includes(q)
  )
})

const totalPages = computed(() =>
  Math.ceil(filteredUsers.value.length / perPage)
)

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return filteredUsers.value.slice(start, start + perPage)
})

const sortBy = (key) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = 'asc'
  }
}

watch(filteredUsers, () => {
  if (currentPage.value > totalPages.value) {
    currentPage.value = 1
  }
})

onMounted(fetchUsers)
</script>

<style scoped>
/* ===== WRAPPER ===== */
.dashboard-wrapper {
  background: #f8fafc;
  min-height: 100vh;
  padding: clamp(1rem, 3vw, 2rem);
}

.admin-section {
  max-width: 1200px;
  margin: auto;
}

/* ===== HEADER ===== */
.header-row {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 1rem;
}

.section-title {
  font-weight: 700;
  color: #1e293b;
}

.section-subtitle {
  font-size: 0.85rem;
  color: #64748b;
}

/* ===== SEARCH ===== */
.search-input {
  width: min(320px, 100%);
  padding: 9px 12px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  outline: none;
}

.search-input:focus {
  border-color: #6366f1;
}

/* ===== TABLE CARD ===== */
.table-card {
  background: white;
  border-radius: 14px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

/* IMPORTANT FIX: RESPONSIVE SCROLL */
.table-scroll {
  overflow-x: auto;
  width: 100%;
}

/* ===== TABLE ===== */
.styled-table {
  width: 100%;
  min-width: 720px;
  border-collapse: collapse;
}

.styled-table th {
  background: #f9fafb;
  font-size: 0.75rem;
  color: #64748b;
  padding: 12px;
  cursor: pointer;
  user-select: none;
}

.styled-table th i {
  margin-left: 6px;
  font-size: 0.75rem;
  opacity: 0.6;
}

.styled-table td {
  padding: 12px;
  font-size: 0.85rem;
  text-align: center;
  border-top: 1px solid #f1f5f9;
}

.styled-table tr:hover {
  background: #f9fafb;
}

/* ===== NAME / EMAIL ===== */
.name {
  font-weight: 600;
  color: #111827;
}

.email {
  color: #4f46e5;
}

/* ===== BUTTONS ===== */
.actions-col {
  display: flex;
  justify-content: center;
  gap: 8px;
}

.btn {
  border: none;
  padding: 6px 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: 0.2s;
}

.btn i {
  font-size: 0.9rem;
}

/* EDIT */
.edit {
  background: #e0e7ff;
  color: #3730a3;
}

.edit:hover {
  background: #6366f1;
  color: white;
}

/* DELETE */
.delete {
  background: #fee2e2;
  color: #991b1b;
}

.delete:hover {
  background: #ef4444;
  color: white;
}

/* ===== PAGINATION ===== */
.pagination {
  display: flex;
  justify-content: center;
  gap: 10px;
  padding: 12px;
}

.pagination button {
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  background: white;
}

.pagination button:disabled {
  opacity: 0.4;
}

/* ===== LOADING ===== */
.loading-state {
  text-align: center;
  padding: 2.5rem;
}

.spinner {
  width: 35px;
  height: 35px;
  border: 3px solid #e0e7ff;
  border-top: 3px solid #6366f1;
  border-radius: 50%;
  margin: auto;
  animation: spin 1s linear infinite;
}

/* ===== EMPTY ===== */
.empty-state {
  text-align: center;
  padding: 2rem;
  color: #94a3b8;
}

/* ===== ANIMATION ===== */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ===== MOBILE FIX ===== */
@media (max-width: 768px) {
  .styled-table {
    min-width: 600px;
  }

  .search-input {
    width: 100%;
  }
}
</style>