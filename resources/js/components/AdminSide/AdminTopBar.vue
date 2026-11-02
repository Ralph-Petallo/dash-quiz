<template>
  <header class="navbar">
    <div class="navbar-container">

      <!-- LEFT -->
      <div class="left-section">

        <button v-if="isMobile" class="menu-trigger" type="button" aria-label="Toggle Menu"
          @click="$emit('toggleSidebar')">
          <i class="fa-solid fa-bars-staggered"></i>
        </button>

        <div class="title-wrapper">
          <span class="title-eyebrow">CSS PREP · ADMIN</span>
          <h2 class="page-title">
            {{ currentPageTitle }}
          </h2>
        </div>

      </div>


      <!-- RIGHT -->
      <div class="user-section">

        <div v-if="showUserMeta" class="user-meta">
          <span class="user-name">
            {{ userFullName }}
          </span>

          <span class="user-status">
            <span class="status-dot"></span>
            Online
          </span>
        </div>


        <router-link v-if="route.path !== '/admin/settings'" to="/admin/settings" class="avatar-link"
          aria-label="Open settings">
          <div class="avatar-container">

            <img :src="userAvatar" class="avatar-img" alt="Administrator avatar" />

            <span class="online-indicator"></span>

          </div>
        </router-link>


        <div v-else class="avatar-container settings-avatar">
          <img :src="userAvatar" class="avatar-img" alt="Administrator avatar" />
        </div>

      </div>

    </div>
  </header>
</template>


<script setup>
import {
  ref,
  computed,
  onMounted,
  onUnmounted
} from 'vue'

import { useUser } from '@/composables/useUser'
import { useRoute } from 'vue-router'


defineProps({
  currentPageTitle: {
    type: String,
    default: 'Dashboard'
  }
})


defineEmits(['toggleSidebar'])


const route = useRoute()

const {
  fetchUser,
  userFullName,
  userAvatar
} = useUser()


const windowWidth = ref(window.innerWidth)


const updateWidth = () => {
  windowWidth.value = window.innerWidth
}


onMounted(() => {
  window.addEventListener(
    'resize',
    updateWidth
  )

  fetchUser()
})


onUnmounted(() => {
  window.removeEventListener(
    'resize',
    updateWidth
  )
})


const isMobile = computed(
  () => windowWidth.value <= 768
)


const showUserMeta = computed(
  () => windowWidth.value > 640
)
</script>


<style scoped>
/* =====================================================
   NAVBAR
===================================================== */

.navbar {
  position: sticky;
  top: 0;
  width: 100%;
  height: 62px;
  z-index: 1;

  display: flex;
  align-items: center;

  flex-shrink: 0;

  background: rgba(255, 255, 255, 0.94);

  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);

  border-bottom: 1px solid #e8e8e8;
}


/* =====================================================
   CONTAINER
===================================================== */

.navbar-container {
  width: 100%;
  height: 100%;

  padding: 0 clamp(14px, 2.5vw, 24px);

  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 20px;
}


/* =====================================================
   LEFT
===================================================== */

.left-section {
  min-width: 0;

  display: flex;
  align-items: center;

  gap: 12px;
}


.title-wrapper {
  min-width: 0;
}


.title-eyebrow {
  display: block;

  margin-bottom: 2px;

  color: #999;

  font-size: 8px;
  font-weight: 700;

  letter-spacing: 0.13em;
}


.page-title {
  margin: 0;

  color: #171717;

  font-size: clamp(15px,
      2vw,
      18px);

  font-weight: 700;

  line-height: 1.2;

  letter-spacing: -0.015em;

  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}


/* =====================================================
   MOBILE MENU
===================================================== */

.menu-trigger {
  width: 36px;
  height: 36px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  background: #111;

  border: 1px solid #111;
  border-radius: 6px;

  color: #fff;

  cursor: pointer;

  font-size: 13px;

  transition:
    background 0.15s ease,
    transform 0.15s ease;
}


.menu-trigger:hover {
  background: #2c2c2c;
}


.menu-trigger:active {
  transform: scale(0.96);
}


/* =====================================================
   RIGHT / USER
===================================================== */

.user-section {
  flex-shrink: 0;

  display: flex;
  align-items: center;

  gap: 11px;
}


.user-meta {
  display: flex;
  flex-direction: column;

  align-items: flex-end;

  gap: 2px;
}


.user-name {
  max-width: 150px;

  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;

  color: #222;

  font-size: 11px;
  font-weight: 700;
}


.user-status {
  display: flex;
  align-items: center;

  gap: 4px;

  color: #888;

  font-size: 9px;
  font-weight: 600;
}


.status-dot {
  width: 5px;
  height: 5px;

  flex-shrink: 0;

  background: green;

  border-radius: 50%;
}


/* =====================================================
   AVATAR
===================================================== */

.avatar-link {
  display: block;

  text-decoration: none;

  color: inherit;
}


.avatar-container {
  position: relative;

  width: 38px;
  height: 38px;

  padding: 2px;

  box-sizing: border-box;

  background: #fff;

  border: 1px solid #dedede;
  border-radius: 50%;

  cursor: pointer;

  transition:
    border-color 0.15s ease,
    transform 0.15s ease;
}


.avatar-link:hover .avatar-container {
  border-color: #111;

  transform: scale(1.04);
}


.settings-avatar {
  cursor: default;
}


.avatar-img {
  width: 100%;
  height: 100%;

  display: block;

  object-fit: cover;

  border-radius: 50%;

  background: #f2f2f2;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 640px) {

  .navbar {
    height: 58px;
  }

  .title-eyebrow {
    display: none;
  }

  .page-title {
    font-size: 15px;
  }

  .avatar-container {
    width: 36px;
    height: 36px;
  }

}


@media (max-width: 480px) {

  .navbar-container {
    padding: 0 12px;
  }

  .left-section {
    gap: 9px;
  }

  .menu-trigger {
    width: 34px;
    height: 34px;
  }

  .user-section {
    gap: 7px;
  }

  .user-meta {
    display: none;
  }

}


/* =====================================================
   REDUCED MOTION
===================================================== */

@media (prefers-reduced-motion: reduce) {

  .menu-trigger,
  .avatar-container {
    transition: none;
  }

}
</style>