<template>
    <div class="assessment-layout">

        <!-- =====================================================
         ASSESSMENT CONTENT
    ====================================================== -->
        <main class="assessment-main">

            <div class="assessment-content">

                <router-view />

            </div>

        </main>


        <!-- =====================================================
         FOOTER STATUS
    ====================================================== -->
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


// =====================================================
// STATE
// =====================================================

const elapsedSeconds = ref(0)

let timer = null


// =====================================================
// ASSESSMENT INFO
// =====================================================

const assessmentTitle = computed(() => {
    return route.meta?.assessmentTitle || 'Assessment'
})

const assessmentType = computed(() => {
    return route.meta?.assessmentType || 'Competency Assessment'
})


// =====================================================
// UI OPTIONS
// =====================================================

const showBackButton = computed(() => {
    return route.meta?.showBackButton ?? false
})

const showTimer = computed(() => {
    return route.meta?.showTimer ?? false
})


// =====================================================
// TIMER
// =====================================================

const formattedTime = computed(() => {

    const minutes = Math.floor(elapsedSeconds.value / 60)
    const seconds = elapsedSeconds.value % 60

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})

const timerWarning = computed(() => {
    return false
})


const startTimer = () => {

    if (!showTimer.value) {
        return
    }

    timer = setInterval(() => {
        elapsedSeconds.value++
    }, 1000)
}


// =====================================================
// NAVIGATION
// =====================================================

const handleBack = () => {
    router.back()
}


const handleExit = () => {

    const confirmed = window.confirm(
        'Are you sure you want to leave this assessment?'
    )

    if (!confirmed) {
        return
    }

    router.push('/user/assessments')
}


// =====================================================
// LIFECYCLE
// =====================================================

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
/* =========================================================
   LAYOUT
========================================================= */

.assessment-layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: #f7f7f7;
    color: #111;
}



/* =========================================================
   HEADER SECTIONS
========================================================= */

.header-left,
.header-right {
    display: flex;
    align-items: center;
    gap: 9px;

    min-width: 0;
}

.header-center {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}


/* =========================================================
   BRAND
========================================================= */

.brand {
    display: flex;
    align-items: center;
    gap: 8px;

    min-width: 0;
}

.brand-mark {
    width: 29px;
    height: 29px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 6px;

    background: #111;
    color: #fff;

    font-size: 10px;
}

.brand-text {
    display: flex;
    flex-direction: column;

    min-width: 0;
}

.brand-label {
    color: #999;

    font-size: 7px;
    font-weight: 700;

    letter-spacing: .12em;
}

.brand-title {
    max-width: 230px;

    overflow: hidden;

    color: #222;

    font-size: 10px;
    font-weight: 700;

    white-space: nowrap;
    text-overflow: ellipsis;
}


/* =========================================================
   TYPE
========================================================= */

.assessment-type {
    display: flex;
    align-items: center;
    gap: 6px;

    color: #777;

    font-size: 8px;
    font-weight: 600;

    white-space: nowrap;
}

.type-dot {
    width: 5px;
    height: 5px;

    border-radius: 50%;

    background: #111;
}


/* =========================================================
   BUTTON
========================================================= */

.header-button {
    width: 29px;
    height: 29px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 1px solid #e2e2e2;
    border-radius: 6px;

    background: #fff;
    color: #555;

    font-size: 10px;

    cursor: pointer;

    transition:
        background .15s ease,
        border-color .15s ease,
        color .15s ease;
}

.header-button:hover {
    border-color: #111;
    color: #111;
}


/* =========================================================
   TIMER
========================================================= */

.timer {
    display: flex;
    align-items: center;
    gap: 6px;

    padding: 6px 8px;

    border: 1px solid #e1e1e1;
    border-radius: 6px;

    background: #fafafa;

    color: #333;

    font-size: 9px;
    font-weight: 700;

    font-variant-numeric: tabular-nums;
}

.timer i {
    color: #888;
    font-size: 8px;
}

.timer.warning {
    background: #111;
    border-color: #111;
    color: #fff;
}

.timer.warning i {
    color: #fff;
}


/* =========================================================
   MAIN
========================================================= */

.assessment-main {
    flex: 1;
    width: 100%;
}

.assessment-content {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}


/* =========================================================
   FOOTER
========================================================= */

.assessment-footer {
    min-height: 32px;

    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 18px;
    border-top: 1px solid #e7e7e7;
    background: #fff;
    color: #999;
    font-size: 7px;
    font-weight: 600;

    letter-spacing: .03em;
}

.footer-left,
.footer-right {
    display: flex;
    align-items: center;
    gap: 5px;
}

.connection-dot {
    width: 5px;
    height: 5px;

    border-radius: 50%;

    background: #111;
}

.separator {
    color: #ccc;
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 768px) {

    .assessment-header {
        height: 52px;
        padding: 0 12px;
    }

    .brand-mark {
        width: 27px;
        height: 27px;
    }

    .brand-title {
        max-width: 160px;
    }

    .header-center {
        display: none;
    }

    .assessment-main {
        padding: 15px 12px 24px;
    }

    .assessment-footer {
        padding: 0 12px;
    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 480px) {

    .assessment-header {
        height: 50px;
    }

    .brand-label {
        font-size: 6px;
    }

    .brand-title {
        max-width: 130px;
        font-size: 9px;
    }

    .header-button {
        width: 27px;
        height: 27px;
    }

    .timer {
        padding: 5px 7px;
    }

    .assessment-main {
        padding: 12px 9px 20px;
    }

    .assessment-footer {
        min-height: 28px;
        font-size: 6px;
    }

}


/* =========================================================
   VERY SMALL
========================================================= */

@media (max-width: 360px) {

    .brand-title {
        max-width: 100px;
    }

    .footer-right {
        display: none;
    }

}
</style>
