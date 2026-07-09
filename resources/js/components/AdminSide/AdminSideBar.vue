<template>
  <aside class="admin-sidebar" :class="{ 'open': isSidebarOpen }">
    <div class="logo-section">
        <img src="/public/bolt.png" alt="Logo" width="32" height="32" >
      <span class="logo-text">DASH<span>QUIZ</span></span>
    </div>

    <div class="nav-label">MAIN MENU</div>

    <nav class="sidebar-nav">
      <router-link to="/admin" exact-active-class="active" @click="$emit('closeSidebar')" class="nav-link">
        <span class="nav-icon"><i class="fas fa-th-large"></i></span>
        <span class="nav-text">Dashboard</span>
      </router-link>

      <router-link to="/admin/manage-quizzes" active-class="active" @click="$emit('closeSidebar')" class="nav-link">
        <span class="nav-icon"><i class="fas fa-book-open"></i></span>
        <span class="nav-text">Quizzes</span>
      </router-link>

      <router-link to="/admin/users" active-class="active" @click="$emit('closeSidebar')" class="nav-link">
        <span class="nav-icon"><i class="fas fa-user-shield"></i></span>
        <span class="nav-text">Users</span>
      </router-link>

      <router-link to="/admin/records" active-class="active" @click="$emit('closeSidebar')" class="nav-link">
        <span class="nav-icon"><i class="fas fa-chart-pie"></i></span>
        <span class="nav-text">Records</span>
      </router-link>
      <router-link to="/admin/settings" active-class="active" @click="$emit('closeSidebar')" class="nav-link">
        <span class="nav-icon"><i class="fas fa-key"></i></span>
        <span class="nav-text">Admin Settings</span>
      </router-link>
    </nav>

    <div class="sidebar-spacer"></div>

    <div class="sidebar-footer">
      <div class="admin-badge">
        <div class="admin-avatar">A</div>
        <div class="admin-info">
          <span class="admin-name">Admin</span>
          <span class="admin-role">Full Access</span>
        </div>
      </div>
      <button class="logout-icon-btn" @click="$emit('logout')" title="Logout">
        <i class="fas fa-power-off"></i>
      </button>
    </div>
  </aside>
</template>

<script setup>
defineProps({
  isSidebarOpen: Boolean
})
defineEmits(['closeSidebar', 'logout'])
</script>

<style scoped>
/* ── VARIABLES ── */
.admin-sidebar {
  --bg-primary:   #1e1b4b;
  --bg-hover:     rgba(99, 102, 241, 0.1);
  --accent:       #818cf8;
  --text-primary: #f8fafc;
  --text-secondary: #94a3b8;
  --text-muted:   #4b5563;
  --border:       rgba(255, 255, 255, 0.05);

  width: 240px;
  background: var(--bg-primary);
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  display: flex;
  flex-direction: column;
  padding: 1.5rem;
  z-index: 1000;
  border-right: 1px solid var(--border);
  overflow-y: auto;
  font-family: 'Inter', -apple-system, sans-serif;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ── DESKTOP ── */
@media (min-width: 1025px) {
  .admin-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    transform: translateX(0) !important;
  }
}

/* ── TABLET ── */
@media (max-width: 1024px) and (min-width: 769px) {
  .admin-sidebar {
    width: 220px;
    padding: 1.25rem;
  }
}

/* ── MOBILE ── */
@media (max-width: 768px) {
  .admin-sidebar {
    transform: translateX(-100%);
    width: 280px;
    padding: 1.25rem;
  }

  .admin-sidebar.open {
    transform: translateX(0);
    box-shadow: 12px 0 40px rgba(0, 0, 0, 0.4);
  }
}

@media (max-width: 480px) {
  .admin-sidebar.open {
    width: 85vw;
    max-width: 280px;
  }
}

/* ── LOGO ── */
.logo-section {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 2.5rem;
  flex-shrink: 0;
}

.logo-mark {
  width: 32px;
  height: 32px;
  background: #8b5cf6;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.logo-text {
  font-weight: 800;
  font-size: 1.1rem;
  color: var(--text-primary);
  letter-spacing: 1px;
  white-space: nowrap;
}

.logo-text span {
  color: var(--accent);
}

/* ── NAV LABEL ── */
.nav-label {
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--text-muted);
  letter-spacing: 1.5px;
  margin-bottom: 1rem;
  flex-shrink: 0;
}

/* ── NAV ── */
.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 12px 12px 16px;
  border-radius: 10px;
  color: var(--text-secondary);
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9375rem;
  transition: all 0.2s ease;
}

.nav-link:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
  padding-left: 19px;
  margin-left: -3px;
}

.nav-link.active {
  background: var(--bg-hover);
  color: var(--text-primary);
}

.nav-icon {
  width: 18px;
  font-size: 0.9375rem;
  opacity: 0.8;
  flex-shrink: 0;
}

.nav-link:hover .nav-icon,
.nav-link.active .nav-icon {
  opacity: 1;
  color: var(--accent);
}

/* ── SPACER ── */
.sidebar-spacer { flex: 1; }

/* ── FOOTER ── */
.sidebar-footer {
  padding-top: 1rem;
  border-top: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-shrink: 0;
}

.admin-badge {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.admin-avatar {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #6366f1, #4f46e5);
  border-radius: 8px;
  display: grid;
  place-items: center;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}

.admin-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.admin-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.admin-role {
  font-size: 0.6875rem;
  color: var(--text-muted);
}

.logout-icon-btn {
  background: none;
  border: 1px solid var(--border);
  border-radius: 8px;
  color: #ef4444;
  cursor: pointer;
  padding: 0.625rem;
  width: 40px;
  height: 40px;
  display: grid;
  place-items: center;
  transition: 0.2s;
  flex-shrink: 0;
}

.logout-icon-btn:hover {
  background: rgba(239, 68, 68, 0.12);
  border-color: #ef4444;
}

/* ── SCROLLBAR ── */
.admin-sidebar::-webkit-scrollbar       { width: 4px; }
.admin-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }

/* ── RESPONSIVE FOOTER ── */
@media (max-width: 480px) {
  .admin-info  { display: none; }
  .admin-badge { gap: 8px; }
  .admin-avatar { width: 32px; height: 32px; font-size: 0.85rem; }
}
</style>