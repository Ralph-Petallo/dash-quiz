<template>
  <header class="navbar">
    <div class="navbar-container">

      <div class="left-section">
        <button class="menu-trigger" @click="$emit('toggleSidebar')" v-if="isMobile">
          <i class="fas fa-bars"></i>
        </button>
        <h2 class="page-title">{{ currentPageTitle }}</h2>
      </div>
      <div v-if="currentPageTitle !== 'My Profile'">
        <div class="user-section">
          <div class="user-meta">
            <span class="user-name">{{ userFullName }}</span>
            <span class="user-status">Online</span>
          </div>

          <router-link to="/user/profile">
            <div class="avatar-container">
              <img :src="userAvatar" alt="avatar" class="avatar-img">
              <span class="status-indicator"></span>
            </div>
          </router-link>
        </div>
      </div>

    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useUser } from '@/composables/useUser.js'

defineProps({
  currentPageTitle: String,
})
defineEmits(['toggleSidebar'])

const { userAvatar, userFullName, fetchUser } = useUser()

const screenWidth = ref(window.innerWidth)
const updateWidth = () => { screenWidth.value = window.innerWidth }

onMounted(() => {
  window.addEventListener('resize', updateWidth)
  fetchUser()
})
onUnmounted(() => window.removeEventListener('resize', updateWidth))

const isMobile = computed(() => screenWidth.value <= 768)
</script>

<style scoped>
/* =========================================================
   FROSTED NOIR
   #FFFFFF — White
   #000000 — Black
   #A9A9A9 — Gray
   #D3D3D3 — Light Gray
   #696969 — Dim Gray
========================================================= */

.navbar {
  position: sticky;
  top: 0;
  z-index: 99;

  height: 64px;

  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);

  border-bottom: 1px solid rgba(211, 211, 211, 0.8);

  display: flex;
  align-items: center;

  flex-shrink: 0;
}


/* =========================================================
   CONTAINER
========================================================= */

.navbar-container {
  width: 100%;
  height: 100%;

  padding: 0 clamp(14px, 2.5vw, 24px);

  display: flex;
  align-items: center;
  justify-content: space-between;
}


/* =========================================================
   LEFT SECTION
========================================================= */

.left-section {
  min-width: 0;

  display: flex;
  align-items: center;

  gap: 12px;
}


/* =========================================================
   MENU BUTTON
========================================================= */

.menu-trigger {
  width: 36px;
  height: 36px;

  display: flex;
  align-items: center;
  justify-content: center;

  flex-shrink: 0;

  background: #ffffff;

  border: 1px solid #d3d3d3;
  border-radius: 9px;

  color: #696969;

  font-size: 14px;

  cursor: pointer;

  transition:
    background 0.18s ease,
    border-color 0.18s ease,
    color 0.18s ease,
    transform 0.18s ease;
}


.menu-trigger:hover {
  background: #f5f5f5;
  border-color: #a9a9a9;
  color: #000000;
}


.menu-trigger:active {
  transform: scale(0.94);
}


/* =========================================================
   PAGE TITLE
========================================================= */

.page-title {
  min-width: 0;

  margin: 0;

  color: #000000;

  font-size: clamp(16px, 2.5vw, 19px);
  line-height: 1.2;

  font-weight: 750;

  letter-spacing: -0.025em;

  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}


/* =========================================================
   USER SECTION
========================================================= */

.user-section {
  display: flex;
  align-items: center;

  gap: 11px;

  flex-shrink: 0;
}


/* =========================================================
   USER META
========================================================= */

.user-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;

  gap: 2px;
}


.user-name {
  max-width: 150px;

  color: #000000;

  font-size: 13px;
  font-weight: 650;
  line-height: 1.2;

  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}


.user-status {
  display: flex;
  align-items: center;
  gap: 5px;

  color: green;

  font-size: 10px;
  font-weight: 600;
}


.user-status::before {
  content: "";

  width: 5px;
  height: 5px;

  border-radius: 50%;

  background: green;
}


/* =========================================================
   AVATAR
========================================================= */

.avatar-container {
  position: relative;

  width: 38px;
  height: 38px;

  padding: 2px;

  display: block;

  background: #ffffff;

  border: 1px solid #d3d3d3;
  border-radius: 50%;

  cursor: pointer;

  transition:
    border-color 0.18s ease,
    box-shadow 0.18s ease,
    transform 0.18s ease;
}


.avatar-container:hover {
  border-color: #696969;

  box-shadow:
    0 3px 12px rgba(0, 0, 0, 0.12);

  transform: translateY(-1px);
}


.avatar-container:active {
  transform: scale(0.96);
}


/* =========================================================
   AVATAR IMAGE
========================================================= */

.avatar-img {
  width: 100%;
  height: 100%;

  display: block;

  border-radius: 50%;

  object-fit: cover;
}


/* =========================================================
   STATUS INDICATOR
========================================================= */

.status-indicator {
  position: absolute;

  right: 0;
  bottom: 1px;

  width: 8px;
  height: 8px;

  background: green;

  border: 2px solid #ffffff;

  border-radius: 50%;
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 768px) {

  .navbar {
    height: 60px;
  }


  .navbar-container {
    padding: 0 16px;
  }


  .page-title {
    font-size: 17px;
  }


  .user-section {
    gap: 9px;
  }


  .avatar-container {
    width: 36px;
    height: 36px;
  }
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 480px) {

  .navbar {
    height: 58px;
  }


  .navbar-container {
    padding: 0 13px;
  }


  .left-section {
    gap: 10px;
  }


  .menu-trigger {
    width: 34px;
    height: 34px;

    font-size: 13px;
  }


  .page-title {
    font-size: 16px;
  }


  .user-meta {
    display: none;
  }


  .avatar-container {
    width: 34px;
    height: 34px;
  }


  .status-indicator {
    width: 7px;
    height: 7px;
  }
}


/* =========================================================
   VERY SMALL MOBILE
========================================================= */

@media (max-width: 320px) {

  .navbar-container {
    padding: 0 9px;
  }


  .page-title {
    max-width: 170px;
  }


  .menu-trigger {
    width: 32px;
    height: 32px;
  }


  .avatar-container {
    width: 32px;
    height: 32px;
  }
}


/* =========================================================
   REDUCED MOTION
========================================================= */

@media (prefers-reduced-motion: reduce) {

  .menu-trigger,
  .avatar-container {
    transition: none;
  }
}
</style>