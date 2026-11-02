<template>
    <div class="assessment-layout">
        <!-- Assessment content -->
        <main class="assessment-main">
            <div class="assessment-content">
                <router-view />
            </div>
        </main>

        <!-- Footer status -->
        <footer class="assessment-footer">
            <div class="footer-left">
                <span class="connection-dot"></span>
                <span>Assessment session active</span>
            </div>

            <div class="footer-right">
                <span>CSS PREP</span>
                <span class="separator">•</span>
                <span>NC II</span>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

// Timer state
const elapsedSeconds = ref(0)
let timer = null

// Assessment information
const assessmentTitle = computed(() => {
    return route.meta?.assessmentTitle || 'Assessment'
})

const assessmentType = computed(() => {
    return route.meta?.assessmentType || 'Competency Assessment'
})

// UI options
const showBackButton = computed(() => {
    return route.meta?.showBackButton ?? false
})

const showTimer = computed(() => {
    return route.meta?.showTimer ?? false
})

// Timer
const formattedTime = computed(() => {
    const minutes = Math.floor(elapsedSeconds.value / 60)
    const seconds = elapsedSeconds.value % 60

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})

const timerWarning = computed(() => false)

const startTimer = () => {
    if (!showTimer.value) return

    timer = setInterval(() => {
        elapsedSeconds.value++
    }, 1000)
}

// Navigation
const handleBack = () => {
    router.back()
}

const handleExit = () => {
    const confirmed = window.confirm(
        'Are you sure you want to leave this assessment?'
    )

    if (!confirmed) return

    router.push('/user/assessments')
}

// Lifecycle
onMounted(() => {
    startTimer()
})

onUnmounted(() => {
    if (timer) {
        clearInterval(timer)
        timer = null
    }
})
</script>

<style scoped>
/* Consistent sizing */
.assessment-layout,
.assessment-layout * {
    box-sizing: border-box;
}

/* Main page wrapper */
.assessment-layout {
    display: flex;
    flex-direction: column;

    width: 100%;
    max-width: 100%;
    min-width: 0;

    min-height: 100vh;
    min-height: 100dvh;

    background: #f7f7f7;
    color: #111;

    overflow-x: clip;
}

/* Allow assessment content to grow AND shrink */
.assessment-main {
    flex: 1 1 auto;

    width: 100%;
    max-width: 100%;
    min-width: 0;
    min-height: 0;

    display: flex;
    flex-direction: column;
}

/* Router-view wrapper */
.assessment-content {
    flex: 1 1 auto;

    width: 100%;
    max-width: 100%;
    min-width: 0;
    min-height: 0;

    margin: 0 auto;
}

/*
 * Help rendered assessment components and their children
 * respect their available width.
 */
.assessment-content :deep(*) {
    box-sizing: border-box;
    min-width: 0;
}

.assessment-content :deep(img),
.assessment-content :deep(video),
.assessment-content :deep(canvas),
.assessment-content :deep(svg) {
    max-width: 100%;
}

.assessment-content :deep(input),
.assessment-content :deep(select),
.assessment-content :deep(textarea),
.assessment-content :deep(button) {
    max-width: 100%;
}

/* Footer */
.assessment-footer {
    display: flex;
    flex: 0 0 auto;

    width: 100%;
    min-width: 0;
    min-height: 36px;

    align-items: center;
    justify-content: space-between;
    gap: 12px;

    padding: 6px 18px;
    padding-bottom: max(6px, env(safe-area-inset-bottom));

    border-top: 1px solid #e7e7e7;
    background: #fff;
    color: #999;

    font-size: 8px;
    font-weight: 600;
    letter-spacing: 0.03em;
}

.footer-left,
.footer-right {
    display: flex;
    align-items: center;
    gap: 5px;

    min-width: 0;
}

.footer-left {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.connection-dot {
    width: 6px;
    height: 6px;
    flex: 0 0 6px;

    border-radius: 50%;
    background: #111;
}

.separator {
    color: #ccc;
}

/* Tablets */
@media (max-width: 768px) {
    .assessment-footer {
        padding-right: 12px;
        padding-left: 12px;
    }
}

/* Phones */
@media (max-width: 480px) {
    .assessment-footer {
        min-height: 32px;
        gap: 8px;

        padding: 6px 10px;
        padding-bottom: max(6px, env(safe-area-inset-bottom));

        font-size: 7px;
    }

    .footer-left {
        flex: 1 1 auto;
    }

    .footer-right {
        flex: 0 0 auto;
        white-space: nowrap;
    }
}

/* Very narrow phones */
@media (max-width: 360px) {
    .assessment-footer {
        font-size: 6px;
    }

    .footer-right {
        display: none;
    }
}

/* Respect reduced-motion preferences */
@media (prefers-reduced-motion: reduce) {

    .assessment-layout *,
    .assessment-layout *::before,
    .assessment-layout *::after {
        scroll-behavior: auto !important;
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
