<template>
    <div class="dash-quiz" :class="{ 'sidebar-shifted': isSidebarOpen }">
        <div class="container">

            <header class="page-header">
                <div class="eyebrow">
                    <span class="pulse-dot" aria-hidden="true"></span>
                    NC II · Computer Systems Servicing
                </div>
                <h1>Choose a competency</h1>
                <p>Work through each Certificate of Competency at your own pace. Progress is scored per attempt.</p>
            </header>

            <div v-if="loading" class="loading">
                <div class="spinner"></div>
                <span>Loading competencies…</span>
            </div>

            <template v-else>
                <section v-for="group in cocGroups" :key="group.number" class="coc-block">
                    <div class="coc-heading">
                        <span class="coc-tag">COC&nbsp;/&nbsp;{{ String(group.number).padStart(2, '0') }}</span>
                        <span class="coc-line" aria-hidden="true"></span>
                        <span class="coc-count">{{ group.quizzes.length }} {{ group.quizzes.length === 1 ? 'module' : 'modules' }}</span>
                    </div>

                    <div v-if="group.quizzes.length === 0" class="empty">
                        <i class="fas fa-box-archive"></i>
                        <span>No modules published for this competency yet</span>
                    </div>

                    <div v-else class="quiz-grid">
                        <router-link
                            v-for="quiz in group.quizzes"
                            :key="quiz.id"
                            :title="quiz.description"
                            :to="`quizzes/assessment/${quiz.id}`"
                            class="quiz-card"
                        >
                            <div class="card-top">
                                <div class="icon-wrapper">
                                    <i :class="quiz.icons"></i>
                                </div>
                                <span class="difficulty" :class="quiz.difficulty.toLowerCase()">
                                    <span class="difficulty-dot"></span>
                                    {{ quiz.difficulty }}
                                </span>
                            </div>

                            <h3>{{ quiz.title }}</h3>

                            <div class="card-meta">
                                <span class="meta-item">
                                    <i class="fas fa-list-ol"></i>
                                    {{ quiz.total_questions }} questions
                                </span>
                                <span class="meta-item">{{ quiz.quiz_type }}</span>
                            </div>

                            <div class="card-foot">
                                <span>Start module</span>
                                <i class="fas fa-arrow-right"></i>
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
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// 1. Receive sidebar state from Parent Layout to adjust margins
defineProps({
    isSidebarOpen: {
        type: Boolean,
        default: true
    }
})


const { fetchUser } = useUser()
const quizzes = ref([])
const loading = ref(false)

const icons = ['fa-solid fa-microchip', 'fa-solid fa-desktop', 'fa-solid fa-cogs', 'fa-solid fa-network-wired', 'fa-solid fa-screwdriver-wrench']

// Group quizzes by their COC number so COC1 / COC2 / COC3 always render,
// even when a competency has no modules published yet.
const cocGroups = computed(() => {
    const groups = { 1: [], 2: [], 3: [] }
    quizzes.value.forEach((quiz) => {
        const number = Number(quiz.coc_number) || 1
        if (!groups[number]) groups[number] = []
        groups[number].push(quiz)
    })
    return Object.keys(groups)
        .sort((a, b) => a - b)
        .map((number) => ({ number: Number(number), quizzes: groups[number] }))
})


const fetchQuizzes = async (force = false) => {
    if (loading.value) return

    try {
        loading.value = true
        const { data } = await axios.get('/api/quizzes')
        quizzes.value = data.data
        quizzes.value.forEach((quiz, index) => {
            quiz.icons = icons[index % icons.length]
        })
    } catch (err) {
        console.error('Failed to fetch quizzes:', err)
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    await fetchUser()
    fetchQuizzes()
})
</script>

<style scoped>
.dash-quiz {
    --surface: #fafaf9;
    --card: #ffffff;
    --border: #e7e5e4;
    --ink: #1c1917;
    --ink-muted: #78716c;
    --accent: #0d9488;
    --accent-soft: #f0fdfa;
    --easy: #16a34a;
    --medium: #d97706;
    --hard: #dc2626;

    width: 100%;
    height: auto;
    background: var(--surface);
    padding: clamp(1.25rem, 3vw, 2.25rem);
    border-radius: 16px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--ink);
}

.container {
    width: 100%;
    max-width: 1080px;
    margin: 0 auto;
}

/* HEADER */
.page-header {
    margin-bottom: 2.25rem;
}

.eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: 'IBM Plex Mono', 'SFMono-Regular', monospace;
    font-size: 0.7rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--accent);
    background: var(--accent-soft);
    border: 1px solid #ccfbf1;
    padding: 5px 10px;
    border-radius: 999px;
    margin-bottom: 14px;
}

.pulse-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-soft);
}

.page-header h1 {
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 700;
    letter-spacing: -0.02em;
    margin-bottom: 6px;
}

.page-header p {
    font-size: 0.9rem;
    color: var(--ink-muted);
    max-width: 46ch;
}

/* LOADING */
.loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 50px 0;
    color: var(--ink-muted);
    font-size: 0.8rem;
}

.spinner {
    width: 28px;
    height: 28px;
    border: 3px solid var(--border);
    border-top: 3px solid var(--accent);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* COC BLOCK */
.coc-block {
    margin-bottom: 2rem;
}

.coc-heading {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
}

.coc-tag {
    font-family: 'IBM Plex Mono', 'SFMono-Regular', monospace;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    color: var(--ink);
    background: var(--card);
    border: 1px solid var(--border);
    padding: 4px 9px;
    border-radius: 6px;
    white-space: nowrap;
}

.coc-line {
    flex: 1;
    height: 1px;
    background: var(--border);
}

.coc-count {
    font-size: 0.72rem;
    color: var(--ink-muted);
    white-space: nowrap;
}

/* EMPTY */
.empty {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 20px;
    border: 1px dashed var(--border);
    border-radius: 12px;
    color: var(--ink-muted);
    font-size: 0.82rem;
    background: var(--card);
}

.empty i {
    color: var(--border);
    font-size: 1rem;
}

/* GRID */
.quiz-grid {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}

/* CARD */
.quiz-card {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 12px;
    text-decoration: none;
    color: inherit;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px;
    overflow: hidden;
    transition: border-color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
}

.quiz-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--accent);
    transform: scaleY(0);
    transform-origin: bottom;
    transition: transform 0.18s ease;
}

.quiz-card:hover {
    border-color: #cbd5c9;
    transform: translateY(-2px);
    box-shadow: 0 10px 24px -12px rgba(28, 25, 23, 0.16);
}

.quiz-card:hover::before {
    transform: scaleY(1);
    transform-origin: top;
}

.card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.icon-wrapper {
    width: 36px;
    height: 36px;
    background: var(--accent-soft);
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent);
    font-size: 0.9rem;
    flex-shrink: 0;
}

.difficulty {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--ink-muted);
}

.difficulty-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.difficulty.easy { color: var(--easy); }
.difficulty.medium { color: var(--medium); }
.difficulty.hard { color: var(--hard); }

.quiz-card h3 {
    font-size: 0.92rem;
    font-weight: 700;
    line-height: 1.3;
    color: var(--ink);
}

.card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 0.72rem;
    color: var(--ink-muted);
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.card-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid var(--border);
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--ink);
}

.card-foot i {
    font-size: 0.7rem;
    color: var(--accent);
    transition: transform 0.18s ease;
}

.quiz-card:hover .card-foot i {
    transform: translateX(3px);
}

@media (max-width: 380px) {
    .dash-quiz {
        padding: 1rem;
        border-radius: 0;
    }

    .eyebrow {
        font-size: 0.62rem;
    }

    .quiz-grid {
        grid-template-columns: 1fr;
    }
}
</style>