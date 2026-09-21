<template>
    <div class="dash-quiz" :class="{ 'sidebar-shifted': isSidebarOpen }">
        <div class="container">

            <!-- HEADER -->
            <header class="page-header">
                <div class="header-content">
                    <div class="header-text">
                        <span class="program-tag">Computer Systems Servicing · NC II</span>
                        <h1>Competency Assessments</h1>
                        <p class="page-description">
                            Work through each Certificate of Competency at your own pace. Every module below checks a
                            different set of hands-on and theory skills.
                        </p>
                    </div>

                    <div class="readout">
                        <span class="readout-value">{{ String(quizzes.length).padStart(2, '0') }}</span>
                        <span class="readout-label">Modules ready</span>
                    </div>
                </div>
            </header>

            <!-- LOADING -->
            <div v-if="loading" class="loading">
                <div class="spinner"></div>
                <span>Preparing your modules…</span>
            </div>

            <!-- CONTENT -->
            <template v-else>

                <section v-for="group in cocGroups" :key="group.number" class="coc-section">

                    <!-- COC HEADER -->
                    <div class="coc-heading">
                        <span class="coc-number">
                            COC {{ String(group.number).padStart(2, '0') }}
                        </span>

                        <div class="coc-title-text">
                            <h2>Certificate of Competency {{ group.number }}</h2>
                            <span class="coc-subtitle">
                                {{ group.quizzes.length }}
                                {{ group.quizzes.length === 1 ? 'module' : 'modules' }} in this unit
                            </span>
                        </div>

                        <div class="coc-line"></div>
                    </div>

                    <!-- EMPTY COC -->
                    <div v-if="group.quizzes.length === 0" class="empty">
                        <div class="empty-icon">
                            <i class="fa-solid fa-box-archive"></i>
                        </div>

                        <div>
                            <strong>Nothing published here yet</strong>
                            <span>Check back once this unit's modules go live.</span>
                        </div>
                    </div>

                    <!-- ASSESSMENTS ROW -->
                    <div v-else class="assessment-row">

                        <router-link v-for="(quiz, index) in group.quizzes" :key="quiz.id"
                            :to="`/user/quizzes/assessment/${quiz.id}`" class="assessment-card" draggable="false">

                            <span class="corner corner-tl"></span>
                            <span class="corner corner-tr"></span>

                            <!-- CARD TOP -->
                            <div class="assessment-top">
                                <div class="assessment-icon">
                                    <i :class="quiz.icons"></i>
                                </div>

                                <span class="difficulty" :class="String(quiz.difficulty || '').toLowerCase()">
                                    <span class="difficulty-dot"></span>
                                    {{ quiz.difficulty || 'Standard' }}
                                </span>
                            </div>

                            <!-- CARD CONTENT -->
                            <div class="assessment-content">
                                <span class="assessment-type">
                                    {{ quiz.quiz_type || 'Competency check' }}
                                </span>

                                <h3>{{ quiz.title }}</h3>

                                <p v-if="quiz.description">{{ quiz.description }}</p>
                            </div>

                            <!-- CARD META -->
                            <div class="assessment-meta">
                                <span class="meta-item">
                                    <i class="fa-regular fa-circle-question"></i>
                                    {{ quiz.questions_count ?? '—' }} items
                                </span>

                                <span class="meta-item">
                                    <i class="fa-regular fa-clock"></i>
                                    Self-paced
                                </span>
                            </div>

                            <!-- CARD FOOT -->
                            <div class="assessment-footer">
                                <span>{{ ctaLabel(index) }}</span>

                                <span class="arrow">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </span>
                            </div>

                        </router-link>

                    </div>

                </section>

            </template>

        </div>
    </div>
</template>


<script setup>
import { useUser } from "@/composables/useUser"
import { ref, computed, onMounted } from "vue"
import axios from "axios"


defineProps({
    isSidebarOpen: {
        type: Boolean,
        default: true
    }
})


const { fetchUser } = useUser()

const quizzes = ref([])
const loading = ref(false)


const icons = [
    "fa-solid fa-microchip",
    "fa-solid fa-desktop",
    "fa-solid fa-gears",
    "fa-solid fa-network-wired",
    "fa-solid fa-screwdriver-wrench"
]

// Rotating call-to-action wording so the row doesn't repeat the same
// verb on every card.
const ctaWords = [
    "Begin assessment",
    "Start module",
    "Take this test",
    "Launch assessment",
    "Open module"
]

const ctaLabel = (index) => ctaWords[index % ctaWords.length]


/*
|--------------------------------------------------------------------------
| GROUP QUIZZES BY COC
|--------------------------------------------------------------------------
*/

const cocGroups = computed(() => {

    const groups = {
        1: [],
        2: [],
        3: []
    }

    quizzes.value.forEach((quiz) => {

        const number = Number(quiz.coc_number) || 1

        if (!groups[number]) {
            groups[number] = []
        }

        groups[number].push(quiz)

    })

    return Object.keys(groups)
        .sort((a, b) => a - b)
        .map((number) => ({
            number: Number(number),
            quizzes: groups[number]
        }))
})


/*
|--------------------------------------------------------------------------
| FETCH QUIZZES
|--------------------------------------------------------------------------
*/

const fetchQuizzes = async () => {

    if (loading.value) {
        return
    }

    try {

        loading.value = true

        const { data } = await axios.get("/api/quizzes")

        quizzes.value = data.data || []

        quizzes.value.forEach((quiz, index) => {
            quiz.icons = icons[index % icons.length]
        })

    } catch (err) {

        console.error("Failed to fetch quizzes:", err)

    } finally {

        loading.value = false

    }
}


/*
|--------------------------------------------------------------------------
| INITIAL LOAD
|--------------------------------------------------------------------------
*/

onMounted(async () => {

    await fetchUser()

    await fetchQuizzes()

})
</script>


<style scoped>
/* =========================================================
   FROSTED NOIR — INVERTED
   White-first / black contrast / grayscale only
========================================================= */

.dash-quiz {
    --black: #000000;
    --white: #FFFFFF;

    --gray-light: #D3D3D3;
    --gray: #A9A9A9;
    --gray-dark: #696969;

    /* Main surfaces */
    --bg: #FFFFFF;
    --panel: rgba(0, 0, 0, 0.025);
    --panel-hover: #000000;
    --panel-soft: rgba(0, 0, 0, 0.02);

    /* Borders */
    --border: rgba(0, 0, 0, 0.14);
    --border-soft: rgba(0, 0, 0, 0.08);
    --border-strong: rgba(0, 0, 0, 0.28);

    /* Text */
    --ink: #000000;
    --ink-soft: #696969;
    --ink-muted: #696969;
    --ink-dim: #A9A9A9;

    /* Frosted */
    --frost: rgba(0, 0, 0, 0.045);
    --frost-strong: rgba(0, 0, 0, 0.08);

    /* Difficulty — grayscale */
    --easy: #696969;
    --medium: #000000;
    --hard: #000000;

    width: 100%;
    min-height: 100%;

    background:
        radial-gradient(circle at top right,
            rgba(0, 0, 0, 0.035),
            transparent 35%),
        var(--bg);

    padding: clamp(1.25rem, 3vw, 2.5rem);

    font-family:
        'Manrope',
        'Inter',
        -apple-system,
        BlinkMacSystemFont,
        sans-serif;

    color: var(--ink);
}


/* =========================================================
   CONTAINER
========================================================= */

.container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {
    margin-bottom: 2.75rem;
    padding-bottom: 1.75rem;

    border-bottom: 1px solid var(--border-soft);
}

.header-content {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 30px;
}

.header-text {
    max-width: 620px;
}


/* PROGRAM TAG */

.program-tag {
    display: inline-block;

    margin-bottom: 14px;

    font-family:
        'JetBrains Mono',
        'SFMono-Regular',
        Consolas,
        monospace;

    font-size: 0.7rem;
    font-weight: 600;

    letter-spacing: 0.04em;

    color: var(--gray-dark);
}


/* PAGE TITLE */

.page-header h1 {
    margin: 0 0 10px;

    color: var(--black);

    font-size: clamp(1.7rem, 4vw, 2.35rem);
    line-height: 1.15;

    font-weight: 700;

    letter-spacing: -0.025em;
}


/* DESCRIPTION */

.page-description {
    margin: 0;

    color: var(--gray-dark);

    font-size: 0.86rem;
    line-height: 1.65;
}


/* =========================================================
   READOUT
========================================================= */

.readout {
    display: flex;
    flex-direction: column;
    align-items: flex-end;

    flex-shrink: 0;

    padding: 10px 18px;

    background: rgba(0, 0, 0, 0.025);

    border: 1px solid var(--border);

    border-radius: 10px;

    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);

    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.8),
        0 8px 30px rgba(0, 0, 0, 0.06);
}

.readout-value {
    font-family:
        'JetBrains Mono',
        monospace;

    font-size: 1.9rem;
    font-weight: 600;

    line-height: 1;

    color: var(--black);
}

.readout-label {
    margin-top: 6px;

    font-size: 0.65rem;

    color: var(--gray-dark);
}


/* =========================================================
   COC SECTION
========================================================= */

.coc-section {
    margin-bottom: 3rem;
}


/* =========================================================
   COC HEADER
========================================================= */

.coc-heading {
    display: flex;
    align-items: center;

    gap: 16px;

    margin-bottom: 16px;
}


/* COC NUMBER */

.coc-number {
    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    min-width: 58px;
    height: 34px;

    padding: 0 10px;

    background: var(--black);

    border: 1px solid var(--black);

    color: var(--white);

    border-radius: 8px;

    font-family:
        'JetBrains Mono',
        monospace;

    font-size: 0.68rem;
    font-weight: 600;

    letter-spacing: 0.04em;
}


/* COC TITLE */

.coc-title-text {
    display: flex;
    flex-direction: column;

    gap: 2px;

    flex-shrink: 0;
}

.coc-title-text h2 {
    margin: 0;

    color: var(--black);

    font-size: 0.95rem;
    font-weight: 700;

    letter-spacing: -0.01em;
}

.coc-subtitle {
    font-size: 0.7rem;

    color: var(--gray-dark);
}


/* COC LINE */

.coc-line {
    flex: 1;

    height: 1px;

    background:
        linear-gradient(to right,
            var(--border-strong),
            transparent);
}


/* =========================================================
   ASSESSMENT ROW
========================================================= */

.assessment-row {
    display: flex;
    flex-flow: row wrap;

    align-items: stretch;

    gap: 14px;
}


/* =========================================================
   ASSESSMENT CARD
========================================================= */

.assessment-card {
    position: relative;

    display: flex;
    flex-direction: column;

    flex: 1 1 270px;

    min-width: 250px;
    max-width: 100%;

    min-height: 240px;

    padding: 18px;

    color: var(--black);

    text-decoration: none;

    /* Frosted white */
    background:
        linear-gradient(145deg,
            rgba(0, 0, 0, 0.025),
            rgba(0, 0, 0, 0.01));

    border: 1px solid var(--border);

    border-radius: 12px;

    overflow: hidden;

    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);

    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.9),
        0 10px 35px rgba(0, 0, 0, 0.07);

    transition:
        transform 0.2s ease,
        border-color 0.2s ease,
        background 0.2s ease,
        box-shadow 0.2s ease,
        color 0.2s ease;
}


/* =========================================================
   CARD HOVER
========================================================= */

.assessment-card:hover {
    transform: translateY(-4px);




    box-shadow:
        0 16px 45px rgba(0, 0, 0, 0.16);
}


/* =========================================================
   CARD CORNERS
========================================================= */

.corner {
    position: absolute;

    width: 6px;
    height: 6px;

    border: 1px solid var(--gray-dark);

    border-radius: 50%;

    opacity: 0;

    transition:
        opacity 0.2s ease,
        border-color 0.2s ease;
}

.corner-tl {
    top: 10px;
    left: 10px;
}

.corner-tr {
    top: 10px;
    right: 10px;
}

.assessment-card:hover .corner {
    opacity: 1;

    border-color: var(--black);
}


/* =========================================================
   CARD TOP
========================================================= */

.assessment-top {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 10px;
}


/* =========================================================
   ASSESSMENT ICON
========================================================= */

.assessment-icon {
    width: 38px;
    height: 38px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: var(--frost);

    border: 1px solid var(--border);

    border-radius: 9px;

    font-size: 0.92rem;

    color: var(--black);

    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);

    transition:
        background 0.2s ease,
        border-color 0.2s ease,
        color 0.2s ease;
}


/* ICON ON HOVER */



/* =========================================================
   DIFFICULTY
========================================================= */

.difficulty {
    display: inline-flex;
    align-items: center;

    gap: 6px;

    font-family:
        'JetBrains Mono',
        monospace;

    font-size: 0.62rem;

    font-weight: 600;

    letter-spacing: 0.02em;

    transition: color 0.2s ease;
}

.difficulty-dot {
    width: 6px;
    height: 6px;

    border-radius: 50%;

    background: currentColor;
}


/* DIFFICULTY */

.difficulty.easy {
    color: var(--easy);
}

.difficulty.medium {
    color: var(--medium);
}

.difficulty.hard {
    color: var(--hard);
}




/* =========================================================
   CARD CONTENT
========================================================= */

.assessment-content {
    flex: 1;

    margin-top: 18px;
}


/* ASSESSMENT TYPE */

.assessment-type {
    display: block;

    margin-bottom: 7px;

    font-family:
        'JetBrains Mono',
        monospace;

    font-size: 0.62rem;

    color: var(--gray-dark);

    transition: color 0.2s ease;
}



/* TITLE */

.assessment-content h3 {
    margin: 0;

    color: var(--black);

    font-size: 1rem;
    line-height: 1.35;

    font-weight: 700;

    letter-spacing: -0.01em;

    transition: color 0.2s ease;
}



/* DESCRIPTION */

.assessment-content p {
    margin: 8px 0 0;

    color: var(--gray-dark);

    font-size: 0.76rem;

    line-height: 1.55;

    display: -webkit-box;

    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;

    overflow: hidden;

    transition: color 0.2s ease;
}


/* =========================================================
   CARD META
========================================================= */

.assessment-meta {
    display: flex;
    flex-wrap: wrap;

    gap: 12px;

    padding-top: 14px;
    margin-top: 14px;

    border-top: 1px solid var(--border-soft);

    transition: border-color 0.2s ease;
}


.meta-item {
    display: inline-flex;
    align-items: center;

    gap: 5px;

    color: var(--gray-dark);

    font-size: 0.67rem;

    transition: color 0.2s ease;
}


.meta-item i {
    color: var(--black);

    transition: color 0.2s ease;
}


/* =========================================================
   CARD FOOTER
========================================================= */

.assessment-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 10px;

    margin-top: 15px;
    padding-top: 13px;

    border-top: 1px solid var(--border-soft);

    color: var(--black);

    font-size: 0.72rem;

    font-weight: 700;

    transition:
        border-color 0.2s ease,
        color 0.2s ease;
}


/* =========================================================
   ARROW
========================================================= */

.arrow {
    display: flex;
    align-items: center;
    justify-content: center;

    width: 26px;
    height: 26px;

    border-radius: 50%;

    background: var(--white);

    border: 1px solid var(--black);

    color: var(--black);
}


/* Arrow hover */

.assessment-card:hover .arrow {
    background: var(--black);

    border-color: var(--black);

    color: var(--white);
}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty {
    display: flex;
    align-items: center;

    gap: 14px;

    padding: 20px;

    border: 1px dashed var(--border-strong);

    border-radius: 10px;

    color: var(--gray-dark);

    background: var(--panel);

    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
}

.empty-icon {
    display: flex;
    align-items: center;
    justify-content: center;

    width: 38px;
    height: 38px;

    border: 1px solid var(--border);

    border-radius: 8px;

    background: var(--frost);

    color: var(--black);

    flex-shrink: 0;
}

.empty strong {
    display: block;

    margin-bottom: 3px;

    color: var(--black);

    font-size: 0.76rem;
}

.empty span {
    display: block;

    color: var(--gray-dark);

    font-size: 0.7rem;
}


/* =========================================================
   LOADING
========================================================= */

.loading {
    min-height: 300px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    gap: 12px;

    color: var(--gray-dark);

    font-size: 0.8rem;
}


/* SPINNER */

.spinner {
    width: 28px;
    height: 28px;

    border: 3px solid rgba(0, 0, 0, 0.1);

    border-top-color: var(--black);

    border-radius: 50%;

    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 760px) {

    .header-content {
        flex-direction: column;

        align-items: flex-start;

        gap: 18px;
    }

    .readout {
        align-self: flex-start;

        flex-direction: row;

        align-items: baseline;

        gap: 10px;
    }

    .assessment-row {
        gap: 12px;
    }

    .assessment-card {
        flex: 1 1 calc(50% - 6px);

        min-width: 230px;
    }
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 540px) {

    .dash-quiz {
        padding: 1rem;
    }

    .page-header {
        margin-bottom: 2rem;

        padding-bottom: 1.25rem;
    }

    .page-header h1 {
        font-size: 1.5rem;
    }

    .page-description {
        font-size: 0.78rem;
    }

    .coc-heading {
        flex-wrap: wrap;

        gap: 10px 12px;
    }

    .coc-line {
        order: 1;

        flex-basis: 100%;
    }

    .assessment-row {
        flex-direction: column;

        flex-wrap: nowrap;
    }

    .assessment-card {
        flex: 1 1 auto;

        min-width: 0;

        min-height: 220px;
    }
}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 380px) {

    .dash-quiz {
        padding: 0.85rem;
    }

    .assessment-card {
        padding: 15px;
    }

    .readout {
        padding: 8px 14px;
    }
}
</style>