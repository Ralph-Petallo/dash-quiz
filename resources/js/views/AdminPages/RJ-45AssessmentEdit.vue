<template>
    <div class="admin-wrapper">
        <div class="sidebar-overlay" :class="{ active: isSidebarOpen && isMobile }" @click="isSidebarOpen = false" />

        <AdminSidebar :isSidebarOpen="isSidebarOpen" @logout="handleLogout" @closeSidebar="closeSidebar" />

        <div class="main-wrapper">
            <AdminTopbar :currentPageTitle="currentPageTitle" @toggleSidebar="toggleSidebar" />
            <main class="admin-main">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup>
import AdminSidebar from '@/components/AdminSide/AdminSideBar.vue'
import AdminTopbar from '@/components/AdminSide/AdminTopBar.vue'
import axios from 'axios'
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUser } from '@/composables/useUser'

const router = useRouter()
const route = useRoute()
const { user } = useUser()


console.log(user)
</script>

<style scoped>
</style>