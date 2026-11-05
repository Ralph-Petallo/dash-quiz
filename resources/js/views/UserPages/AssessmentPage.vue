<template>
  <div class="assessment-page" :class="{ 'sidebar-shifted': isSidebarOpen }">
    <div class="page-shell">
      <header class="page-header">
        <div class="header-copy">
          <span class="program-tag">CSS Prep · Computer Systems Servicing NC II</span>
          <h1>NC II Competency Assessments</h1>
          <p>
            Choose a Certificate of Competency, then continue to the theory or practical assessments
            prepared for that competency area.
          </p>
        </div>
      </header>

      <!-- OFFLINE BANNER (shown on top of any state) -->
      <div v-if="!isOnline" class="notice notice-warn" role="status">
        <i class="fa-solid fa-wifi" aria-hidden="true"></i>
        <span>You're offline. Your connection will be checked again automatically.</span>
      </div>

      <!-- LOADING: skeleton cards -->
      <section v-if="status === 'loading'" class="coc-grid layout-grid skeleton-grid" role="status" aria-live="polite">
        <span class="sr-only">Loading CSS NC II competency areas</span>

        <div v-for="item in skeletonItems" :key="item" class="skeleton-card" aria-hidden="true">
          <div class="sk-row">
            <span class="sk sk-pill"></span>
            <span class="sk sk-pill sk-pill-sm"></span>
          </div>

          <span class="sk sk-icon"></span>
          <span class="sk sk-line sk-w-40"></span>
          <span class="sk sk-title"></span>
          <span class="sk sk-line"></span>
          <span class="sk sk-line sk-w-80"></span>

          <div class="sk-metrics">
            <span class="sk sk-line sk-w-30"></span>
            <span class="sk sk-line sk-w-30"></span>
          </div>

          <span class="sk sk-preview"></span>
          <span class="sk sk-preview"></span>
        </div>
      </section>

      <p v-if="status === 'loading' && slowNetwork" class="slow-note" role="status">
        <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
        Still loading. Your connection may be slow.
      </p>

      <!-- ERROR STATES -->
      <section v-else-if="status === 'error'" class="state-panel" role="alert">
        <div class="state-icon" :class="`tone-${currentError.tone}`" aria-hidden="true">
          <i :class="currentError.icon"></i>
        </div>

        <h2>{{ currentError.title }}</h2>
        <p>{{ currentError.text }}</p>

        <div class="state-actions">
          <button v-if="currentError.retry" type="button" class="btn btn-solid" @click="retry">
            <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
            Try again
          </button>

          <button v-if="currentError.signIn" type="button" class="btn btn-solid" @click="goToLogin">
            <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
            Sign in again
          </button>
        </div>
      </section>

      <!-- EMPTY STATE -->
      <section v-else-if="status === 'empty'" class="state-panel">
        <div class="state-icon" aria-hidden="true">
          <i class="fa-solid fa-layer-group"></i>
        </div>

        <h2>No competency areas yet</h2>
        <p>No COC records are available yet. Check back later, or ask your instructor to set them up.</p>

        <div class="state-actions">
          <button type="button" class="btn btn-outline" @click="retry">
            <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
            Refresh
          </button>
        </div>
      </section>

      <!-- READY -->
      <template v-else>
        <div v-if="usingCache" class="notice" role="status">
          <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
          <span>Showing your last saved catalog. It may be out of date.</span>
          <button type="button" class="notice-action" :disabled="!isOnline" @click="retry">
            Refresh
          </button>
        </div>

        <section class="catalog-toolbar">
          <div class="catalog-intro">
            <span class="section-kicker">Competency map</span>
            <h2>{{ cocs.length }} core areas for CSS NC II readiness</h2>
          </div>

          <div class="view-toggle" role="group" aria-label="Competency display layout">
            <button type="button" class="view-toggle-button" :class="{ active: layoutMode === 'grid' }"
              :aria-pressed="layoutMode === 'grid'" @click="layoutMode = 'grid'">
              <i class="fa-solid fa-grip" aria-hidden="true"></i>
              <span>Grid</span>
            </button>
            <button type="button" class="view-toggle-button" :class="{ active: layoutMode === 'flex' }"
              :aria-pressed="layoutMode === 'flex'" @click="layoutMode = 'flex'">
              <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
              <span>Flex</span>
            </button>
          </div>
        </section>

        <section class="coc-grid" :class="`layout-${layoutMode}`">
          <router-link v-for="coc in cocs" :key="coc.id" :to="`/user/assessments/assessment/${coc.id}`" class="coc-card"
            :class="{ 'is-inactive': isInactiveCoc(coc) }" :style="cocThemeStyle(coc)" draggable="false">
            <div class="card-topline">
              <span class="coc-badge">COC {{ String(coc.coc_number).padStart(2, '0') }}</span>
              <span class="core-badge">{{ coc.difficulty || 'Core' }}</span>
            </div>

            <div class="coc-icon" aria-hidden="true">
              <i :class="cocIcon(coc.coc_number)"></i>
            </div>

            <div class="coc-content">
              <span class="coc-category">{{ coc.category || 'Computer Systems Servicing' }}</span>
              <h3>{{ coc.title }}</h3>
              <p>{{ coc.description || 'Competency practice for CSS NC II readiness.' }}</p>
            </div>

            <div class="coc-metrics">
              <span class="metric">
                <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
                <strong>{{ coc.assessment_count ?? coc.assessments?.length ?? 0 }}</strong>
                {{ assessmentWord(coc.assessment_count ?? coc.assessments?.length ?? 0) }}
              </span>

              <span class="metric">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                <strong>{{ coc.available_assessment_count ?? availableInCoc(coc) }}</strong>
                ready
              </span>
            </div>

            <div v-if="coc.assessments?.length" class="assessment-preview">
              <div v-for="assessment in coc.assessments.slice(0, 3)" :key="assessment.id" class="preview-row">
                <span class="preview-icon" aria-hidden="true">
                  <i :class="assessment.icon || fallbackAssessmentIcon(assessment.type)"></i>
                </span>

                <span class="preview-copy">
                  <strong>{{ assessment.title }}</strong>
                  <small>{{ formatType(assessment.type) }}</small>
                </span>

                <span class="availability" :class="assessment.available ? 'is-ready' : 'is-planned'">
                  {{ assessment.available ? 'Ready' : 'Planned' }}
                </span>
              </div>
            </div>

            <div v-else class="assessment-preview empty-preview">
              <i class="fa-regular fa-clock" aria-hidden="true"></i>
              <span>Assessment content is being prepared.</span>
            </div>

            <div class="card-footer">
              <span>View assessments</span>
              <span class="arrow-box" aria-hidden="true">
                <i class="fa-solid fa-arrow-right"></i>
              </span>
            </div>
          </router-link>
        </section>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import { useRouter } from "vue-router"
import api from "../../bootstrap"
import { useUser } from "@/composables/useUser"
import { cocThemeStyle, isInactiveCoc } from "@/composables/cocTheme"

defineProps({
  isSidebarOpen: {
    type: Boolean,
    default: true,
  },
})

const router = useRouter()
const { fetchUser } = useUser()

/* =========================
   CONSTANTS
========================= */

const CACHE_KEY = "cssprep:coc-catalog"
const CACHE_TTL = 5 * 60 * 1000
const SLOW_AFTER_MS = 4000
const skeletonItems = [1, 2, 3, 4]

// status: loading | ready | empty | error
const ERROR_STATES = {
  offline: {
    icon: "fa-solid fa-wifi",
    title: "You're offline",
    text: "Check your internet connection. The catalog will load automatically once you're back online.",
    tone: "danger",
    retry: true,
  },
  network: {
    icon: "fa-solid fa-plug-circle-xmark",
    title: "Can't reach the server",
    text: "The connection failed. Check your network and try again.",
    tone: "danger",
    retry: true,
  },
  timeout: {
    icon: "fa-solid fa-hourglass-half",
    title: "The request took too long",
    text: "Your network may be slow right now. Try again in a moment.",
    tone: "danger",
    retry: true,
  },
  server: {
    icon: "fa-solid fa-triangle-exclamation",
    title: "Something went wrong",
    text: "The server could not load the catalog. Please try again shortly.",
    tone: "danger",
    retry: true,
  },
  permission: {
    icon: "fa-solid fa-lock",
    title: "Access denied",
    text: "Your account does not have permission to view these assessments. Contact your instructor if you think this is a mistake.",
    tone: "neutral",
    retry: false,
  },
  session: {
    icon: "fa-solid fa-user-clock",
    title: "Your session has expired",
    text: "For your security, please sign in again to continue.",
    tone: "neutral",
    retry: false,
    signIn: true,
  },
}

/* =========================
   STATE
========================= */

const status = ref("loading")
const errorType = ref("server")
const cocs = ref([])
const usingCache = ref(false)
const isOnline = ref(navigator.onLine)
const slowNetwork = ref(false)
const layoutMode = ref("grid")

let inFlight = null
let slowTimer = null

const currentError = computed(() => ERROR_STATES[errorType.value] ?? ERROR_STATES.server)

/* =========================
   CACHE
========================= */

function readCache(allowExpired = false) {
  try {
    const cached = sessionStorage.getItem(CACHE_KEY)
    if (!cached) return null

    const parsed = JSON.parse(cached)
    if (!Array.isArray(parsed?.data)) return null

    const expired = Date.now() - parsed.timestamp > CACHE_TTL
    if (expired && !allowExpired) return null

    return parsed.data
  } catch (error) {
    console.warn("Unable to read COC cache:", error)
    return null
  }
}

function writeCache(data) {
  try {
    sessionStorage.setItem(CACHE_KEY, JSON.stringify({ data, timestamp: Date.now() }))
  } catch (error) {
    // The page still works if sessionStorage is unavailable or full.
    console.warn("Unable to save COC cache:", error)
  }
}

/* =========================
   STATE HELPERS
========================= */

function startLoading() {
  status.value = "loading"
  slowNetwork.value = false

  clearTimeout(slowTimer)
  slowTimer = setTimeout(() => {
    slowNetwork.value = true
  }, SLOW_AFTER_MS)
}

function stopSlowTimer() {
  clearTimeout(slowTimer)
  slowNetwork.value = false
}

function showCatalog(list, fromCache = false) {
  cocs.value = list
  usingCache.value = fromCache
  status.value = list.length ? "ready" : "empty"
}

function classifyError(error) {
  const code = error?.response?.status

  if (code === 401 || code === 419) return "session"
  if (code === 403) return "permission"
  if (error?.isInvalidResponse) return "server"
  if (code >= 500) return "server"
  if (error?.response) return "server"
  if (error?.code === "ECONNABORTED" || error?.code === "ETIMEDOUT") return "timeout"
  if (!navigator.onLine) return "offline"

  return "network"
}

function showFailure(type) {
  errorType.value = type

  // Network-type failures can fall back to the saved catalog.
  // Access problems must never show old data, so the cache is cleared instead.
  const canUseCache = ["offline", "network", "timeout", "server"].includes(type)

  if (canUseCache) {
    const stale = readCache(true)

    if (stale?.length) {
      showCatalog(stale, true)
      return
    }
  } else {
    sessionStorage.removeItem(CACHE_KEY)
  }

  cocs.value = []
  usingCache.value = false
  status.value = "error"
}

/* =========================
   FETCH
========================= */

function fetchCocs({ force = false } = {}) {
  if (!force) {
    const cached = readCache()

    if (cached !== null) {
      showCatalog(cached)
      return Promise.resolve()
    }
  }

  // Reuse the request already in progress.
  if (inFlight) return inFlight

  if (!navigator.onLine) {
    showFailure("offline")
    return Promise.resolve()
  }

  startLoading()

  inFlight = api
    .get("/api/coc")
    .then(({ data }) => {
      if (!Array.isArray(data?.data)) {
        const invalid = new Error("Invalid COC catalog response.")
        invalid.isInvalidResponse = true
        throw invalid
      }

      writeCache(data.data)
      showCatalog(data.data)
    })
    .catch((error) => {
      console.error("Failed to load CSS Prep COCs:", error)
      showFailure(classifyError(error))
    })
    .finally(() => {
      stopSlowTimer()
      inFlight = null
    })

  return inFlight
}

function retry() {
  fetchCocs({ force: true })
}

function goToLogin() {
  router.push("/login")
}

/* =========================
   DISPLAY HELPERS
========================= */

const availableInCoc = (coc) =>
  Array.isArray(coc?.assessments)
    ? coc.assessments.filter((assessment) => assessment.available).length
    : 0

const assessmentWord = (count) =>
  Number(count) === 1 ? "assessment" : "assessments"

const cocIcon = (number) => {
  const icons = {
    1: "fa-solid fa-computer",
    2: "fa-solid fa-network-wired",
    3: "fa-solid fa-server",
    4: "fa-solid fa-screwdriver-wrench",
  }

  return icons[Number(number)] || "fa-solid fa-layer-group"
}

const fallbackAssessmentIcon = (type) => {
  const value = String(type ?? "").toLowerCase()

  if (value.includes("rj45") || value.includes("network")) {
    return "fa-solid fa-network-wired"
  }

  if (value.includes("hardware") || value.includes("practical") || value.includes("procedure")) {
    return "fa-solid fa-screwdriver-wrench"
  }

  return "fa-solid fa-list-check"
}

const formatType = (value) => {
  const type = String(value ?? "assessment").replaceAll("_", " ").trim()
  return type.charAt(0).toUpperCase() + type.slice(1)
}

/* =========================
   CONNECTION EVENTS
========================= */

function handleOnline() {
  isOnline.value = true

  // Reload automatically when the page was waiting for a connection,
  // or when it is showing saved data.
  const waitingForNetwork =
    status.value === "error" && ["offline", "network", "timeout"].includes(errorType.value)

  if (waitingForNetwork || usingCache.value) {
    fetchCocs({ force: true })
  }
}

function handleOffline() {
  isOnline.value = false
}

/* =========================
   LIFECYCLE
========================= */

onMounted(async () => {
  window.addEventListener("online", handleOnline)
  window.addEventListener("offline", handleOffline)

  try {
    await fetchUser()
  } catch {
    // The catalog request below reports authentication problems itself.
  }

  await fetchCocs()
})

onBeforeUnmount(() => {
  window.removeEventListener("online", handleOnline)
  window.removeEventListener("offline", handleOffline)
  clearTimeout(slowTimer)
})
</script>

<style scoped>
.assessment-page {
  --black: #000000;
  --white: #ffffff;
  --light: #d3d3d3;
  --dim: #696969;
  --border: rgba(0, 0, 0, 0.12);
  --border-soft: rgba(0, 0, 0, 0.07);
  --panel: rgba(0, 0, 0, 0.025);

  /* Fallbacks, overwritten per card by cocThemeStyle() */
  --coc-accent: #000000;
  --coc-tint: #f4f4f4;

  width: 100%;
  min-width: 0;
  min-height: 100%;
  margin-inline: auto;
  background: transparent;
  color: var(--black);
  font-size: 13px;
}

.assessment-page *,
.assessment-page *::before,
.assessment-page *::after {
  box-sizing: border-box;
}

.assessment-page button {
  font-family: inherit;
  -webkit-tap-highlight-color: transparent;
}

.assessment-page :focus-visible {
  outline: 2px solid var(--coc-accent);
  outline-offset: 3px;
}

.assessment-page h1,
.assessment-page h2,
.assessment-page h3,
.assessment-page p {
  margin: 0;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
  border: 0;
}

.page-shell {
  width: 100%;
  max-width: 1200px;
  min-width: 0;
  margin-inline: auto;
  padding: clamp(18px, 2.5vw, 32px);
}

/* HEADER */

.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 24px;
  min-width: 0;
  padding-bottom: 24px;
  margin-bottom: 24px;
  border-bottom: 1px solid var(--border);
}

.header-copy {
  min-width: 0;
  max-width: 760px;
}

.program-tag,
.section-kicker {
  display: inline-block;
  margin-bottom: 8px;
  color: var(--dim);
  font-size: 10.5px;
  font-weight: 800;
  letter-spacing: 0.12em;
  line-height: 1.5;
  text-transform: uppercase;
}

.page-header h1 {
  color: var(--black);
  font-size: clamp(24px, 3vw, 38px);
  line-height: 1.12;
  letter-spacing: -0.04em;
  overflow-wrap: anywhere;
}

.page-header p {
  max-width: 680px;
  margin-top: 12px;
  color: var(--dim);
  font-size: 13.5px;
  line-height: 1.65;
}

/* NOTICES (offline, saved data) */

.notice {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
  padding: 11px 14px;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--white);
  color: var(--black);
  font-size: 12px;
  line-height: 1.5;
}

.notice i {
  color: var(--dim);
}

.notice span {
  flex: 1 1 240px;
  min-width: 0;
}

.notice-warn {
  border-color: #e7c76a;
  background: #fffaf0;
}

.notice-action {
  min-height: 30px;
  padding: 0 12px;
  border: 1px solid var(--black);
  border-radius: 9px;
  background: var(--white);
  color: var(--black);
  font-size: 11px;
  font-weight: 750;
  cursor: pointer;
}

.notice-action:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.slow-note {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 12px;
  color: var(--dim);
  font-size: 12px;
}

/* STATE PANELS (error, empty) */

.state-panel {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 240px;
  padding: 28px 22px;
  border: 1px dashed var(--border);
  border-radius: 16px;
  background: var(--white);
  text-align: center;
}

.state-panel h2 {
  color: var(--black);
  font-size: 16px;
  font-weight: 800;
}

.state-panel p {
  max-width: 420px;
  color: var(--dim);
  font-size: 12.5px;
  line-height: 1.6;
}

.state-icon {
  width: 50px;
  height: 50px;
  display: grid;
  place-items: center;
  margin-bottom: 4px;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: var(--panel);
  color: var(--black);
  font-size: 19px;
}

.state-icon.tone-danger {
  border-color: #f3c4c0;
  background: #fff1f0;
  color: #b42318;
}

.state-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
  margin-top: 8px;
}

.btn {
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 8px 14px;
  border: 1px solid var(--black);
  border-radius: 9px;
  font-size: 12px;
  font-weight: 750;
  cursor: pointer;
}

.btn-solid {
  background: var(--black);
  color: var(--white);
}

.btn-solid:hover {
  background: #333;
  border-color: #333;
}

.btn-outline {
  background: var(--white);
  color: var(--black);
}

.btn-outline:hover {
  background: var(--panel);
}

/* CATALOG TOOLBAR */

.catalog-toolbar {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  min-width: 0;
  margin-bottom: 16px;
}

.catalog-intro {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.catalog-intro h2 {
  color: var(--black);
  font-size: clamp(16px, 2vw, 20px);
  line-height: 1.35;
  letter-spacing: -0.02em;
  overflow-wrap: anywhere;
}

.view-toggle {
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  gap: 4px;
  padding: 4px;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--white);
}

.view-toggle-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  min-height: 34px;
  padding: 8px 12px;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: var(--dim);
  font-size: 11px;
  font-weight: 750;
  cursor: pointer;
  transition: background 160ms ease, color 160ms ease;
}

.view-toggle-button:hover {
  background: var(--panel);
  color: var(--black);
}

.view-toggle-button.active {
  background: var(--black);
  color: var(--white);
}

.view-toggle-button i {
  font-size: 12px;
}

/* GRID */

.coc-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  width: 100%;
  min-width: 0;
}

.coc-grid.layout-flex {
  display: flex;
  flex-wrap: wrap;
  align-items: stretch;
}

.coc-grid.layout-flex .coc-card {
  flex: 1 1 320px;
  max-width: 100%;
}

/* SKELETON (loading placeholders) */

.skeleton-card {
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 0;
  min-height: 420px;
  padding: clamp(16px, 2vw, 22px);
  border: 1px solid var(--border);
  border-radius: 18px;
  background: var(--white);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
}

.sk-row {
  display: flex;
  justify-content: space-between;
  gap: 8px;
}

.sk {
  display: block;
  border-radius: 6px;
  background: linear-gradient(90deg, #efefef 25%, #f8f8f8 37%, #efefef 63%);
  background-size: 400% 100%;
  animation: shimmer 1.4s ease infinite;
}

.sk-pill {
  width: 72px;
  height: 22px;
  border-radius: 999px;
}

.sk-pill-sm {
  width: 56px;
}

.sk-icon {
  width: 50px;
  height: 50px;
  margin: 18px 0 8px;
  border-radius: 14px;
}

.sk-line {
  height: 12px;
}

.sk-title {
  width: 85%;
  height: 22px;
}

.sk-w-40 {
  width: 40%;
}

.sk-w-80 {
  width: 80%;
}

.sk-w-30 {
  width: 30%;
}

.sk-metrics {
  display: flex;
  gap: 14px;
  margin-top: 8px;
  padding: 12px 0;
  border-top: 1px solid var(--border-soft);
  border-bottom: 1px solid var(--border-soft);
}

.sk-preview {
  height: 44px;
  border-radius: 11px;
}

@keyframes shimmer {
  0% {
    background-position: 100% 0;
  }

  100% {
    background-position: -100% 0;
  }
}

/* CARD */

.coc-card {
  position: relative;
  display: flex;
  flex-direction: column;
  min-width: 0;
  min-height: 420px;
  padding: clamp(16px, 2vw, 22px);
  overflow: hidden;
  border: 1px solid var(--border);
  border-radius: 18px;
  background: var(--white);
  color: inherit;
  text-decoration: none;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
  transition: transform 180ms ease, border-color 180ms ease, box-shadow 180ms ease;
}

.coc-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--coc-accent);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 180ms ease;
}

.coc-card:hover {
  transform: translateY(-3px);
  border-color: var(--coc-accent);
  box-shadow: 0 16px 36px rgba(0, 0, 0, 0.07);
}

.coc-card:hover::before {
  transform: scaleX(1);
}

.coc-card:focus-visible {
  outline: 3px solid var(--coc-accent);
  outline-offset: 3px;
}

.coc-card.is-inactive:hover {
  border-color: var(--border);
  box-shadow: 0 10px 26px rgba(0, 0, 0, 0.05);
}

.coc-card.is-inactive:hover::before {
  transform: scaleX(0);
}

/* BADGES */

.card-topline {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
  min-width: 0;
}

.coc-badge,
.core-badge,
.availability {
  display: inline-flex;
  align-items: center;
  max-width: 100%;
  border-radius: 999px;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  white-space: nowrap;
}

.coc-badge {
  padding: 6px 10px;
  background: var(--coc-accent);
  color: var(--white);
  font-size: 10px;
}

.core-badge {
  padding: 6px 10px;
  border: 1px solid var(--border);
  background: var(--white);
  color: var(--dim);
  font-size: 10px;
}

/* ICON */

.coc-icon {
  width: 50px;
  height: 50px;
  flex: 0 0 50px;
  display: grid;
  place-items: center;
  margin: 20px 0 16px;
  border: 1px solid var(--white);
  border-radius: 14px;
  background: var(--white);
  color: var(--coc-accent);
  font-size: 19px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.coc-card.is-inactive .coc-icon {
  color: var(--dim);
}

/* TEXT */

.coc-content {
  min-width: 0;
}

.coc-category {
  display: block;
  margin-bottom: 6px;
  color: var(--dim);
  font-size: 10.5px;
  font-weight: 750;
  line-height: 1.45;
}

.coc-content h3 {
  color: var(--black);
  font-size: clamp(17px, 1.7vw, 20px);
  font-weight: 800;
  line-height: 1.25;
  letter-spacing: -0.025em;
  overflow-wrap: anywhere;
}

.coc-content p {
  margin-top: 8px;
  color: var(--dim);
  font-size: 12.5px;
  line-height: 1.6;
  overflow-wrap: anywhere;
  display: -webkit-box;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
}

/* METRICS */

.coc-metrics {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 14px;
  padding: 12px 0;
  margin-top: 14px;
  border-top: 1px solid var(--border-soft);
  border-bottom: 1px solid var(--border-soft);
}

.metric {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
  color: var(--dim);
  font-size: 11px;
  line-height: 1.4;
}

.metric i {
  flex-shrink: 0;
  color: var(--coc-accent);
  font-size: 11px;
}

.metric strong {
  color: var(--black);
  font-weight: 800;
}

/* PREVIEW LIST */

.assessment-preview {
  display: grid;
  gap: 6px;
  min-width: 0;
  margin-top: 12px;
}

.preview-row {
  display: grid;
  grid-template-columns: 30px minmax(0, 1fr) auto;
  align-items: center;
  gap: 9px;
  min-width: 0;
  padding: 8px 9px;
  border: 1px solid var(--border-soft);
  border-radius: 11px;
  background: rgba(255, 255, 255, 0.85);
}

.preview-icon {
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  border: 1px solid var(--border-soft);
  border-radius: 8px;
  background: var(--coc-tint);
  color: var(--coc-accent);
  font-size: 12px;
}

.coc-card.is-inactive .preview-icon {
  color: var(--dim);
}

.preview-copy {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.preview-copy strong,
.preview-copy small {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.preview-copy strong {
  color: var(--black);
  font-size: 11.5px;
  font-weight: 750;
}

.preview-copy small {
  margin-top: 2px;
  color: var(--dim);
  font-size: 10px;
}

.availability {
  padding: 4px 7px;
  font-size: 8.5px;
  letter-spacing: 0.04em;
}

.is-ready {
  background: var(--coc-accent);
  color: var(--white);
}

.is-planned {
  border: 1px solid var(--border);
  background: var(--white);
  color: var(--dim);
}

.empty-preview {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 11px;
  border: 1px dashed var(--border);
  border-radius: 11px;
  color: var(--dim);
  font-size: 11px;
  line-height: 1.5;
}

.empty-preview i {
  color: var(--coc-accent);
}

/* FOOTER */

.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  min-width: 0;
  margin-top: auto;
  padding-top: 16px;
  color: var(--black);
  font-size: 12px;
  font-weight: 800;
}

.card-footer>span:first-child {
  overflow-wrap: anywhere;
}

.arrow-box {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  background: var(--coc-accent);
  color: var(--white);
  transition: transform 180ms ease;
}

.coc-card.is-inactive .arrow-box {
  background: var(--dim);
}

.coc-card:hover .arrow-box {
  transform: translateX(3px);
}

/* TABLET */

@media (max-width: 900px) {
  .page-shell {
    padding: 22px 18px 28px;
  }

  .coc-card,
  .skeleton-card {
    min-height: 0;
  }

  .coc-icon {
    width: 44px;
    height: 44px;
    flex-basis: 44px;
    margin: 18px 0 14px;
    font-size: 17px;
  }

  .sk-icon {
    margin: 14px 0 8px;
  }
}

/* PHONE */

@media (max-width: 600px) {
  .assessment-page {
    font-size: 12px;
  }

  .page-shell {
    padding: 14px 10px 24px;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
    padding-bottom: 16px;
    margin-bottom: 16px;
  }

  .program-tag,
  .section-kicker {
    font-size: 8.5px;
    letter-spacing: 0.1em;
    margin-bottom: 6px;
  }

  .page-header h1 {
    font-size: 21px;
  }

  .page-header p {
    margin-top: 6px;
    font-size: 11px;
    line-height: 1.55;
  }

  .catalog-toolbar {
    align-items: flex-start;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 12px;
  }

  .catalog-intro h2 {
    font-size: 14px;
  }

  .view-toggle {
    align-self: flex-start;
  }

  .view-toggle-button {
    min-height: 32px;
    padding: 7px 10px;
  }

  .coc-grid {
    gap: 8px;
  }

  .coc-grid.layout-flex .coc-card {
    flex-basis: 100%;
  }

  .coc-card,
  .skeleton-card {
    padding: 11px;
    border-radius: 13px;
  }

  .skeleton-card {
    gap: 8px;
  }

  .sk-icon {
    width: 34px;
    height: 34px;
    margin: 12px 0 8px;
    border-radius: 9px;
  }

  .sk-preview {
    height: 34px;
  }

  .card-topline {
    gap: 5px;
  }

  .coc-badge,
  .core-badge {
    padding: 4px 6px;
    font-size: 7.5px;
    letter-spacing: 0.04em;
  }

  .coc-icon {
    width: 34px;
    height: 34px;
    flex-basis: 34px;
    margin: 12px 0 10px;
    border-radius: 9px;
    font-size: 14px;
  }

  .coc-category {
    font-size: 8.5px;
    margin-bottom: 4px;
  }

  .coc-content h3 {
    font-size: 13.5px;
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
  }

  .coc-content p {
    margin-top: 5px;
    font-size: 10px;
    line-height: 1.45;
    -webkit-line-clamp: 3;
  }

  .coc-metrics {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 4px;
    padding: 8px 0;
    margin-top: 10px;
  }

  .metric {
    font-size: 9px;
    gap: 4px;
  }

  .metric i {
    font-size: 9px;
  }

  .assessment-preview {
    gap: 4px;
    margin-top: 8px;
  }

  .preview-row {
    grid-template-columns: 22px minmax(0, 1fr);
    gap: 6px;
    padding: 6px;
    border-radius: 9px;
  }

  .preview-icon {
    width: 22px;
    height: 22px;
    border-radius: 6px;
    font-size: 9px;
  }

  .preview-copy strong {
    font-size: 9.5px;
  }

  .preview-copy small {
    font-size: 8px;
  }

  .availability {
    grid-column: 2;
    justify-self: start;
    padding: 2px 5px;
    font-size: 7px;
  }

  .empty-preview {
    padding: 8px;
    font-size: 9.5px;
    gap: 6px;
  }

  .card-footer {
    flex-direction: column;
    align-items: stretch;
    gap: 6px;
    padding-top: 9px;
    font-size: 9.5px;
    border-top: 1px solid var(--border-soft);
  }

  .card-footer>span:first-child {
    text-align: center;
  }

  .arrow-box {
    display: none;
  }

  .state-panel {
    min-height: 180px;
    padding: 20px 16px;
  }

  .state-panel h2 {
    font-size: 14px;
  }

  .state-panel p {
    font-size: 11px;
  }

  .notice {
    font-size: 11px;
  }
}

/* VERY SMALL PHONES */

@media (max-width: 360px) {
  .coc-grid {
    gap: 6px;
  }

  .coc-card,
  .skeleton-card {
    padding: 9px;
  }

  .coc-content h3 {
    font-size: 12.5px;
  }

  .preview-row {
    grid-template-columns: minmax(0, 1fr);
  }

  .preview-icon {
    display: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>