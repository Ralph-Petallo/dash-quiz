<template>
  <aside class="sidebar" :class="{ open: isSidebarOpen }" aria-label="Main navigation">
    <!-- Brand -->
    <router-link to="/user" class="brand" @click="onNavigate">
      <img :src="logoUrl" alt="" width="28" height="28" />
      <span class="brand-name">CSS<span>Prep</span></span>
    </router-link>

    <div class="divider"></div>

    <!-- Menu: this wrapper keeps every item inside the sidebar width -->
    <div class="menu">
      <nav class="nav" aria-label="Menu">
        <p class="group-label">Learn</p>

        <router-link v-for="item in learnItems" :key="item.to" :to="item.to" class="nav-item"
          :class="{ active: isActive(item) }" @click="onNavigate">
          <i :class="item.icon" aria-hidden="true"></i>
          <span class="nav-text">{{ item.label }}</span>
        </router-link>

        <p class="group-label">Account</p>

        <router-link v-for="item in accountItems" :key="item.to" :to="item.to" class="nav-item"
          :class="{ active: isActive(item) }" @click="onNavigate">
          <i :class="item.icon" aria-hidden="true"></i>
          <span class="nav-text">{{ item.label }}</span>
        </router-link>

        <button type="button" class="nav-item logout" @click="emit('logout')">
          <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
          <span class="nav-text">Log out</span>
        </button>
      </nav>
    </div>
  </aside>
</template>

<script setup>
import { computed } from "vue"
import { useRoute } from "vue-router"
import { useUser } from "@/composables/useUser"
import logoUrl from "@/assets/lightning.png"

defineProps({
  isSidebarOpen: { type: Boolean, default: false },
})

const emit = defineEmits(["closeSidebar", "logout"])

const route = useRoute()
const { user } = useUser()

const learnItems = [
  { to: "/user", label: "Home", icon: "fa-solid fa-house", exact: true },
  { to: "/user/assessments", label: "Competency", icon: "fa-solid fa-layer-group", section: "assessments" },
  { to: "/user/records", label: "Records", icon: "fa-solid fa-chart-column" },
]

const accountItems = [
  { to: "/user/profile", label: "Profile", icon: "fa-solid fa-user" },
]

const displayName = computed(() => {
  const name = [user.value?.first_name, user.value?.last_name].filter(Boolean).join(" ")
  return name || "Learner"
})

const initials = computed(() => {
  const first = user.value?.first_name?.[0] ?? ""
  const last = user.value?.last_name?.[0] ?? ""
  return (first + last).toUpperCase() || "CP"
})

const roleLabel = computed(() => (user.value?.role === "admin" ? "Admin" : "Learner"))

// Competency stays highlighted on its sub-pages, such as a single COC.
const isActive = (item) => {
  if (item.section) return route.meta?.navSection === item.section
  if (item.exact) return route.path === item.to || route.path === `${item.to}/`
  return route.path.startsWith(item.to)
}

// Close the drawer on phones and tablets after choosing a page.
const onNavigate = () => emit("closeSidebar")
</script>

<style scoped>
/* FROSTED NOIR
   #FFFFFF surface   #000000 text   #696969 secondary
   #A9A9A9 icons     #D3D3D3 lines  #F5F5F5 hover
   Red appears only on logout hover. */

.sidebar {
  --ink: #000000;
  --muted: #696969;
  --icon: #a9a9a9;
  --line: #d3d3d3;
  --hover: #f5f5f5;
  --selected: #ededed;
  --danger: #c81e2e;
  --danger-soft: #fdf1f2;

  position: fixed;
  top: 0;
  left: 0;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  width: 240px;
  height: 100vh;
  height: 100dvh;
  padding: 1.5rem 1rem 1rem;
  overflow-x: hidden;
  overflow-y: auto;
  box-sizing: border-box;
  background: #ffffff;
  border-right: 1px solid var(--line);
  color: var(--ink);
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  font-size: 0.875rem;
  line-height: 1.3;
  -webkit-font-smoothing: antialiased;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

/* Every direct child stays inside the sidebar width */
.sidebar>* {
  min-width: 0;
  max-width: 100%;
}

/* Buttons and links do not inherit the font by default */
.sidebar a,
.sidebar button {
  font: inherit;
  color: inherit;
}

/* BRAND */

.brand {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  justify-content: center;
  margin: 0 0.5rem 1.5rem;
  text-decoration: none;
}

.brand img {
  display: block;
  width: 28px;
  height: 28px;
  object-fit: contain;
  flex-shrink: 0;
}

.brand-name {
  font-size: 1.0625rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--ink);
}

.brand-name span {
  color: var(--muted);
}

/* divider */

.divider {
  display: flex;
  align-items: center;
  margin-bottom: 1.5rem;
  border: 1px solid var(--line);
  border-radius: 10px;
}


/* MENU: the wrapper that keeps all items inside the sidebar */

.menu {
  width: 100%;
  min-width: 0;
  overflow: hidden;
}

.nav {
  display: flex;
  flex-direction: column;
  gap: 2px;
  width: 100%;
  min-width: 0;
}

.group-label {
  margin: 0.75rem 0 0.375rem 0.75rem;
  color: var(--muted);
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.group-label:first-child {
  margin-top: 0;
}

/* One row style for every item, including logout */

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  min-width: 0;
  min-height: 42px;
  padding: 0 0.75rem;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: var(--muted);
  font-size: 0.875rem;
  font-weight: 500;
  text-align: left;
  text-decoration: none;
  white-space: nowrap;
  cursor: pointer;
  box-sizing: border-box;
  transition: background 0.15s ease, color 0.15s ease;
}

.nav-item i {
  width: 18px;
  flex-shrink: 0;
  text-align: center;
  color: var(--icon);
  font-size: 0.9375rem;
  transition: color 0.15s ease;
}

.nav-text {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.nav-item:hover {
  background: var(--hover);
  color: var(--ink);
}

.nav-item:hover i {
  color: var(--ink);
}

.nav-item.active {
  background: var(--selected);
  color: var(--ink);
  font-weight: 600;
}

.nav-item.active i {
  color: var(--ink);
}

.nav-item:focus-visible {
  outline: 2px solid var(--ink);
  outline-offset: 2px;
}

/* LOGOUT: same row as the others, red only on hover */

.logout:hover,
.logout:focus-visible {
  background: var(--danger-soft);
  color: var(--danger);
}

.logout:hover i,
.logout:focus-visible i {
  color: var(--danger);
}

/* RESPONSIVE */

@media (max-width: 1024px) and (min-width: 769px) {
  .sidebar {
    width: 220px;
  }
}

/* Phone and small tablet: the sidebar slides in as a drawer */
@media (max-width: 768px) {
  .sidebar {
    width: min(280px, 85vw);
    transform: translateX(-100%);
  }

  .sidebar.open {
    transform: translateX(0);
    box-shadow: 8px 0 32px rgba(0, 0, 0, 0.12);
  }
}

@media (prefers-reduced-motion: reduce) {

  .sidebar,
  .nav-item,
  .nav-item i {
    transition: none;
  }
}
</style>