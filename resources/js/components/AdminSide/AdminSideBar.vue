<template>
  <aside class="admin-sidebar" :class="{ open: isSidebarOpen }">

    <!-- BRAND -->
    <div class="logo-section">

      <div class="logo-mark">
        <img src="/public/lightning.png" alt="CSS Prep" />
      </div>

      <div class="logo-copy">
        <span class="logo-text">
          CSS<span>Prep</span>
        </span>

        <span class="logo-subtitle">
          ADMINISTRATION
        </span>
      </div>

    </div>


    <!-- NAVIGATION -->
    <div class="nav-label">
      MAIN MENU
    </div>

    <nav class="sidebar-nav">

      <router-link to="/admin" exact-active-class="active" class="nav-link" @click="$emit('closeSidebar')">
        <span class="nav-icon">
          <i class="fa-solid fa-chart-line"></i>
        </span>

        <span class="nav-text">
          Dashboard
        </span>
      </router-link>


      <router-link to="/admin/manage-quizzes" active-class="active" class="nav-link" @click="$emit('closeSidebar')">
        <span class="nav-icon">
          <i class="fa-solid fa-layer-group"></i>
        </span>

        <span class="nav-text">
          Assessments
        </span>
      </router-link>


      <router-link to="/admin/users" active-class="active" class="nav-link" @click="$emit('closeSidebar')">
        <span class="nav-icon">
          <i class="fa-solid fa-users"></i>
        </span>

        <span class="nav-text">
          Users
        </span>
      </router-link>


      <router-link to="/admin/records" active-class="active" class="nav-link" @click="$emit('closeSidebar')">
        <span class="nav-icon">
          <i class="fa-solid fa-chart-simple"></i>
        </span>

        <span class="nav-text">
          Records
        </span>
      </router-link>


      <div class="nav-divider"></div>


      <router-link to="/admin/settings" active-class="active" class="nav-link" @click="$emit('closeSidebar')">
        <span class="nav-icon">
          <i class="fa-solid fa-sliders"></i>
        </span>

        <span class="nav-text">
          Settings
        </span>
      </router-link>

    </nav>


    <!-- SPACER -->
    <div class="sidebar-spacer"></div>


    <!-- ADMIN FOOTER -->
    <div class="sidebar-footer">

      <div class="admin-badge">

        <div class="admin-avatar">
          <i class="fa-solid fa-shield-halved"></i>
        </div>

        <div class="admin-info">

          <span class="admin-name">
            Administrator
          </span>

          <span class="admin-role">
            Full Access
          </span>

        </div>

      </div>


      <button class="logout-icon-btn" type="button" title="Logout" aria-label="Logout" @click="$emit('logout')">
        <i class="fa-solid fa-arrow-right-from-bracket"></i>
      </button>

    </div>

  </aside>
</template>


<script setup>

defineProps({
  isSidebarOpen: {
    type: Boolean,
    default: false
  }
})


defineEmits([
  'closeSidebar',
  'logout'
])

</script>


<style scoped>
/* =====================================================
   SIDEBAR
===================================================== */

.admin-sidebar {
  --sidebar-bg: #111;
  --sidebar-border: #292929;

  --text-primary: #f5f5f5;
  --text-secondary: #999;
  --text-muted: #666;

  --hover-bg: #1c1c1c;
  --active-bg: #222;

  width: 238px;
  height: 100vh;

  position: fixed;
  top: 0;
  left: 0;

  z-index: 100;

  display: flex;
  flex-direction: column;

  box-sizing: border-box;

  padding: 20px 14px;

  background: var(--sidebar-bg);

  border-right: 1px solid var(--sidebar-border);

  overflow-y: auto;

  font-family:
    'Inter',
    -apple-system,
    BlinkMacSystemFont,
    'Segoe UI',
    sans-serif;

  transition:
    transform 0.25s ease,
    box-shadow 0.25s ease;
}


/* =====================================================
   DESKTOP
===================================================== */

@media (min-width: 1025px) {

  .admin-sidebar {
    transform: translateX(0) !important;
  }

}


/* =====================================================
   TABLET
===================================================== */

@media (max-width: 1024px) and (min-width: 769px) {

  .admin-sidebar {
    width: 220px;
    padding: 18px 12px;
  }

}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 768px) {

  .admin-sidebar {
    width: 278px;

    transform: translateX(-100%);
  }

  .admin-sidebar.open {
    transform: translateX(0);

    box-shadow:
      12px 0 35px rgba(0, 0, 0, 0.25);
  }

}


@media (max-width: 480px) {

  .admin-sidebar.open {
    width: 84vw;
    max-width: 278px;
  }

}


/* =====================================================
   BRAND
===================================================== */

.logo-section {
  display: flex;
  align-items: center;

  gap: 10px;

  padding: 2px 6px;

  margin-bottom: 30px;

  flex-shrink: 0;
}


.logo-mark {
  width: 34px;
  height: 34px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  background: #fff;

  border-radius: 7px;
}


.logo-mark img {
  width: 21px;
  height: 21px;

  object-fit: contain;
}


.logo-copy {
  display: flex;
  flex-direction: column;

  min-width: 0;
}


.logo-text {
  color: #fff;

  font-size: 14px;
  font-weight: 800;

  line-height: 1;

  letter-spacing: 0.08em;
}


.logo-text span {
  color: #aaa;
}


.logo-subtitle {
  margin-top: 4px;

  color: #666;

  font-size: 7px;
  font-weight: 700;

  letter-spacing: 0.14em;
}


/* =====================================================
   NAV LABEL
===================================================== */

.nav-label {
  padding: 0 9px;

  margin-bottom: 8px;

  color: #555;

  font-size: 8px;
  font-weight: 700;

  letter-spacing: 0.14em;
}


/* =====================================================
   NAV
===================================================== */

.sidebar-nav {
  display: flex;
  flex-direction: column;

  gap: 3px;
}


.nav-link {
  position: relative;

  display: flex;
  align-items: center;

  gap: 11px;

  min-height: 40px;

  padding: 0 11px;

  box-sizing: border-box;

  color: var(--text-secondary);

  border: 1px solid transparent;
  border-radius: 6px;

  text-decoration: none;

  font-size: 12px;
  font-weight: 550;

  transition:
    background 0.15s ease,
    color 0.15s ease,
    border-color 0.15s ease;
}


.nav-link:hover {
  background: var(--hover-bg);
  color: #fff;
}


.nav-link.active {
  background: var(--active-bg);

  border-color: #303030;

  color: #fff;
}


.nav-link.active::before {
  content: '';

  position: absolute;

  left: -1px;
  top: 9px;
  bottom: 9px;

  width: 2px;

  background: #fff;

  border-radius: 0 2px 2px 0;
}


.nav-icon {
  width: 17px;

  flex-shrink: 0;

  display: flex;
  justify-content: center;

  color: #777;

  font-size: 12px;

  transition:
    color 0.15s ease;
}


.nav-link:hover .nav-icon,
.nav-link.active .nav-icon {
  color: #fff;
}


.nav-text {
  white-space: nowrap;
}


/* =====================================================
   DIVIDER
===================================================== */

.nav-divider {
  height: 1px;

  margin: 9px 8px;

  background: #252525;
}


/* =====================================================
   SPACER
===================================================== */

.sidebar-spacer {
  flex: 1;
}


/* =====================================================
   FOOTER
===================================================== */

.sidebar-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 9px;

  padding: 13px 5px 2px;

  border-top: 1px solid #292929;

  flex-shrink: 0;
}


.admin-badge {
  min-width: 0;

  display: flex;
  align-items: center;

  gap: 9px;
}


.admin-avatar {
  width: 31px;
  height: 31px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  background: #222;

  border: 1px solid #353535;

  border-radius: 6px;

  color: #ddd;

  font-size: 11px;
}


.admin-info {
  min-width: 0;

  display: flex;
  flex-direction: column;

  gap: 2px;
}


.admin-name {
  max-width: 125px;

  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;

  color: #ddd;

  font-size: 10px;
  font-weight: 700;
}


.admin-role {
  color: #666;

  font-size: 8px;
  font-weight: 600;

  text-transform: uppercase;

  letter-spacing: 0.05em;
}


/* =====================================================
   LOGOUT
===================================================== */

.logout-icon-btn {
  width: 32px;
  height: 32px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  background: transparent;

  border: 1px solid #303030;
  border-radius: 6px;

  color: #888;

  cursor: pointer;

  font-size: 11px;

  transition:
    background 0.15s ease,
    color 0.15s ease,
    border-color 0.15s ease;
}


.logout-icon-btn:hover {
  background: #fff;

  border-color: #fff;

  color: #111;
}


/* =====================================================
   SCROLLBAR
===================================================== */

.admin-sidebar::-webkit-scrollbar {
  width: 4px;
}


.admin-sidebar::-webkit-scrollbar-track {
  background: transparent;
}


.admin-sidebar::-webkit-scrollbar-thumb {
  background: #333;

  border-radius: 4px;
}


.admin-sidebar::-webkit-scrollbar-thumb:hover {
  background: #444;
}


/* =====================================================
   MOBILE FOOTER
===================================================== */

@media (max-width: 480px) {

  .admin-info {
    display: none;
  }

  .admin-badge {
    gap: 0;
  }

  .admin-avatar {
    width: 32px;
    height: 32px;
  }

}


/* =====================================================
   REDUCED MOTION
===================================================== */

@media (prefers-reduced-motion: reduce) {

  .admin-sidebar,
  .nav-link,
  .nav-icon,
  .logout-icon-btn {
    transition: none;
  }

}
</style>