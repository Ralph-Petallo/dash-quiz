<template>
  <aside class="user-sidebar" :class="{ open: isSidebarOpen }">
    <!-- Logo Section -->
    <div class="logo-section">
      <img src="/public/lightning.png" alt="DASHQUIZ Logo" width="32" height="32">

      <span class="logo-text">
        CSS<span>Prep</span>
      </span>
    </div>

    <!-- Menu Label -->
    <div class="nav-label">
      MAIN MENU
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">

      <router-link to="/user" exact-active-class="active" @click="$emit('closeSidebar')" class="nav-link">
        <span class="nav-icon">
          <i class="fas fa-home"></i>
        </span>

        <span class="nav-text">
          Home
        </span>
      </router-link>

      <router-link to="/user/quizzes" exact-active-class="active" @click="$emit('closeSidebar')" class="nav-link">
        <span class="nav-icon">
          <i class="fas fa-clipboard-list"></i>
        </span>

        <span class="nav-text">
          Assessments
        </span>
      </router-link>

      <router-link to="/user/records" exact-active-class="active" @click="$emit('closeSidebar')" class="nav-link">
        <span class="nav-icon">
          <i class="fas fa-chart-simple"></i>
        </span>

        <span class="nav-text">
          Records
        </span>
      </router-link>

      <router-link to="/user/profile" exact-active-class="active" @click="$emit('closeSidebar')" class="nav-link">
        <span class="nav-icon">
          <i class="fas fa-user"></i>
        </span>

        <span class="nav-text">
          Profile
        </span>
      </router-link>

    </nav>

    <!-- Spacer -->
    <div class="sidebar-spacer"></div>

    <!-- Footer -->
    <div class="sidebar-footer">
      <button class="logout-btn" @click="$emit('logout')" title="Logout">
        <i class="fas fa-power-off"></i> Log out
      </button>
    </div>
  </aside>
</template>


<script setup>
defineProps({
  isSidebarOpen: Boolean
})

defineEmits([
  'closeSidebar',
  'logout'
])
</script>


<style scoped>
/* =========================================
   FROSTED NOIR PALETTE
========================================= */

.user-sidebar {

  --white: #FFFFFF;
  --black: #000000;
  --gray-light: #D3D3D3;
  --gray: #A9A9A9;
  --gray-dark: #696969;

  --bg-primary: #FFFFFF;
  --bg-hover: #F5F5F5;
  --bg-active: #D3D3D3;

  --text-primary: #000000;
  --text-secondary: #696969;
  --text-muted: #A9A9A9;

  --border: #D3D3D3;

  width: 240px;

  background: var(--bg-primary);

  position: fixed;

  left: 0;
  top: 0;

  display: flex;
  flex-direction: column;

  height: 100vh;

  padding: 1.5rem;

  transition:
    transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);

  z-index: 1000;

  border-right: 1px solid var(--border);

  overflow-y: auto;

  font-family:
    'Inter',
    -apple-system,
    BlinkMacSystemFont,
    'Segoe UI',
    sans-serif;

  box-sizing: border-box;
}


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 1024px) and (min-width: 769px) {

  .user-sidebar {
    width: 220px;
    padding: 1.25rem;
  }

}


@media (max-width: 768px) {

  .user-sidebar {

    transform: translateX(-100%);

    width: 280px;

    height: 100vh;

    padding: 1.25rem;
  }

  .user-sidebar.open {

    transform: translateX(0);

    box-shadow:
      12px 0 40px rgba(0, 0, 0, 0.15);
  }

}


@media (max-width: 480px) {

  .user-sidebar.open {

    width: 100%;

    max-width: 280px;
  }

}


/* =========================================
   LOGO
========================================= */

.logo-section {

  display: flex;

  align-items: center;

  gap: 12px;

  margin-bottom: 2.5rem;

  padding-right: 0.5rem;
}

.logo-section img {

  width: 32px;
  height: 32px;

  object-fit: contain;

  flex-shrink: 0;
}

.logo-text {

  font-weight: 800;

  font-size: 1.1rem;

  color: var(--black);

  letter-spacing: 0.5px;
}

.logo-text span {

  color: var(--gray-dark);
}


/* =========================================
   NAV LABEL
========================================= */

.nav-label {

  font-size: 0.7rem;

  font-weight: 700;

  color: var(--gray);

  letter-spacing: 1.5px;

  margin-bottom: 1rem;

  padding-left: 0.25rem;
}


/* =========================================
   NAVIGATION
========================================= */

.sidebar-nav {

  display: flex;

  flex-direction: column;

  gap: 5px;
}


.nav-link {

  position: relative;

  display: flex;

  align-items: center;

  gap: 12px;

  padding: 12px 12px 12px 16px;

  border-radius: 8px;

  color: var(--text-secondary);

  text-decoration: none;

  font-weight: 500;

  font-size: 0.9375rem;

  transition:
    background 0.2s ease,
    color 0.2s ease,
    transform 0.2s ease;
}


/* Hover */

.nav-link:hover {

  background: var(--bg-hover);

  color: var(--black);

  transform: translateX(2px);
}


/* Active */

.nav-link.active {

  background: var(--bg-active);

  color: var(--black);

  font-weight: 600;

  box-shadow:
    inset 3px 0 0 var(--black);
}


.nav-link.active .nav-icon {

  color: var(--black);
}


/* =========================================
   ICONS
========================================= */

.nav-icon {

  width: 18px;

  font-size: 0.9375rem;

  color: var(--gray-dark);

  opacity: 0.9;

  transition:
    color 0.2s ease,
    opacity 0.2s ease;
}


.nav-link:hover .nav-icon {

  color: var(--black);

  opacity: 1;
}


.nav-link.active .nav-icon {

  color: var(--black);

  opacity: 1;
}


.nav-text {

  opacity: 1;
}


/* =========================================
   SPACER
========================================= */

.sidebar-spacer {

  flex: 1;

  min-height: 40px;
}


/* =========================================
   FOOTER
========================================= */

.sidebar-footer {

  padding-top: 1rem;

  padding-bottom: 0.25rem;

  border-top: 1px solid var(--border);

  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 12px;
}


/* =========================================
   LOGOUT
========================================= */

.logout-btn {

  background: var(--white);

  border: 1px solid var(--border);

  border-radius: 8px;

  color: #696969;

  cursor: pointer;

  font-size: 1rem;

  padding: 0.625rem;

  display: flex;

  align-items: center;

  justify-content: center;

  transition:
    background 0.2s ease,
    color 0.2s ease,
    border-color 0.2s ease,
    transform 0.2s ease;

  flex-shrink: 0;

  width: 100%;
  height: 40px;
}


.logout-btn:hover {

  background: #000000;

  border-color: #000000;

  color: #FFFFFF;

  transform: scale(1.04);
}


/* =========================================
   SCROLLBAR
========================================= */

.user-sidebar::-webkit-scrollbar {

  width: 4px;
}


.user-sidebar::-webkit-scrollbar-track {

  background: transparent;
}


.user-sidebar::-webkit-scrollbar-thumb {

  background: #D3D3D3;

  border-radius: 4px;
}


.user-sidebar::-webkit-scrollbar-thumb:hover {

  background: #A9A9A9;
}
</style>
