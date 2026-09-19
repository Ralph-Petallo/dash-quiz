<template>
    <div class="assessment-page">

        <!-- Back -->
        <button class="back-btn" @click="$router.back()">
            <i class="fas fa-arrow-left"></i>
            Back
        </button>

        <!-- COC Header -->
        <div class="coc-header">
            <div class="coc-icon">
                <i class="fas fa-desktop"></i>
            </div>

            <div>
                <h1>COC {{ assessmentObject.coc_number }}</h1>
                <p>{{ assessmentObject.title }}</p>
                <span>
                    {{ assessmentObject.description }}
                </span>
            </div>
        </div>

        <!-- Assessment Types -->
        <div class="section-heading">
            <h2>Choose Assessment</h2>
            <p>Select an assessment type to begin.</p>
        </div>

        <div v-if="assessments.length" class="assessment-grid">
            <div v-for="assessment in assessments" :key="assessment.type" class="assessment-card"
                :class="{ active: assessment.available, locked: !assessment.available }">

                <div class="card-top">
                    <div class="type-icon">
                        <i :class="assessment.icon"></i>
                    </div>

                    <span :class="assessment.available ? 'available' : 'coming'">
                        {{ assessment.available ? 'Available' : 'Coming Soon' }}
                    </span>
                </div>

                <h3>{{ assessment.title }}</h3>

                <p class="card-description">
                    {{ assessment.description }}
                </p>

                <!-- Tags -->
                <div class="tags">
                    <span class="tag category"><i class="fas fa-layer-group"></i>{{ assessment.category }}</span>
                    <span class="tag difficulty"><i class="fas fa-signal"></i>{{ assessment.difficulty }}</span>
                </div>
                <div class="card-footer">
                    <span><i class="fas fa-question-circle"></i>{{ assessment.meta }}</span>
                    <button v-if="assessment.available" type="button" @click="startAssessment(assessment)">
                        Start <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <div v-else>
            <div class="empty-assessment">
                <div class="box">
                    <p>There is no assessment for this coc yet.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route = useRoute()
const assessmentObject = ref({})
const assessments = ref([])

// get all assessments for the given COC ID and filter them based on availability
const fetchAssessmentData = async () => {
    try {
        const { data } = await axios.get(`/api/assessments-type/${route.params.id}`);
        assessments.value = data.assessments
        assessmentObject.value = data.quiz

    } catch (error) {
        console.error(error)
    }
}

const startAssessment = (assessment) => {
    const quizId = route.params.id
    if (quizId) router.push(`/${assessment.path}/${quizId}`)
}

onMounted(fetchAssessmentData)  
</script>


<style scoped>
/* =========================================================
   FROSTED NOIR — ASSESSMENT PAGE
   #FFFFFF  White
   #000000  Black
   #A9A9A9  Gray
   #D3D3D3  Light Gray
   #696969  Dark Gray
========================================================= */

.assessment-page {
    --white: #ffffff;
    --black: #000000;

    --gray-light: #d3d3d3;
    --gray: #a9a9a9;
    --gray-dark: #696969;

    --background: #f6f6f6;
    --surface: #ffffff;
    --surface-soft: #f8f8f8;

    --border: #d3d3d3;

    width: 100%;
    min-width: 0;
    min-height: 100vh;

    padding: clamp(16px, 3vw, 32px);

    background: var(--background);
    color: var(--black);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03), 0 16px 40px rgba(0, 0, 0, 0.06);
    box-sizing: border-box;
    overflow-x: hidden;
}


/* =========================================================
   BACK BUTTON
========================================================= */

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    margin: 0 0 20px;

    padding: 8px 0;

    border: none;
    background: transparent;

    color: var(--gray-dark);

    font-family: inherit;
    font-size: 13px;
    font-weight: 600;

    cursor: pointer;

    transition:
        color 0.18s ease,
        transform 0.18s ease;
}

.back-btn:hover {
    color: var(--black);
}

.back-btn:hover i {
    transform: translateX(-3px);
}

.back-btn i {
    transition: transform 0.18s ease;
}


/* =========================================================
   COC HEADER
========================================================= */

.coc-header {
    width: 100%;

    display: flex;
    align-items: center;

    gap: 18px;

    padding: clamp(18px, 3vw, 24px);

    margin-bottom: 32px;

    background: var(--surface);

    border: 1px solid var(--border);

    border-radius: 14px;

    box-sizing: border-box;

    box-shadow:
        0 4px 16px rgba(0, 0, 0, 0.04);
}


/* =========================================================
   COC ICON
========================================================= */

.coc-icon {
    width: 54px;
    height: 54px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    background: var(--black);

    color: var(--white);

    border-radius: 10px;

    font-size: 20px;
}


/* =========================================================
   COC TEXT
========================================================= */

.coc-header h1 {
    margin: 0 0 3px;

    color: var(--gray-dark);

    font-size: 12px;

    font-weight: 800;

    letter-spacing: 0.08em;

    text-transform: uppercase;
}

.coc-header p {
    margin: 0;

    color: var(--black);

    font-size: clamp(17px, 2vw, 20px);

    font-weight: 700;

    line-height: 1.35;
}

.coc-header span {
    display: block;

    margin-top: 5px;

    color: var(--gray-dark);

    font-size: 12px;

    line-height: 1.5;

    overflow-wrap: anywhere;
}


/* =========================================================
   SECTION HEADING
========================================================= */

.section-heading {
    margin-bottom: 16px;
}

.section-heading h2 {
    margin: 0;

    color: var(--black);

    font-size: 18px;

    font-weight: 750;

    letter-spacing: -0.01em;
}

.section-heading p {
    margin: 4px 0 0;

    color: var(--gray-dark);

    font-size: 12px;
}


/* =========================================================
   ASSESSMENT GRID
========================================================= */

.assessment-grid {
    width: 100%;

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(min(100%, 270px), 1fr));

    gap: 14px;

    box-sizing: border-box;
}


/* =========================================================
   ASSESSMENT CARD
========================================================= */

.assessment-card {
    min-width: 0;

    display: flex;
    flex-direction: column;

    padding: 19px;

    background: var(--surface);

    border: 1px solid var(--border);

    border-radius: 12px;

    box-sizing: border-box;

    transition:
        transform 0.2s ease,
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}


/* =========================================================
   ACTIVE CARD
========================================================= */

.assessment-card.active {
    cursor: default;
}

.assessment-card.active:hover {
    transform: translateY(-3px);

    border-color: var(--gray-dark);

    box-shadow:
        0 10px 24px rgba(0, 0, 0, 0.08);
}


/* =========================================================
   LOCKED CARD
========================================================= */

.assessment-card.locked {
    background: #fafafa;

    opacity: 0.58;
}


/* =========================================================
   CARD TOP
========================================================= */

.card-top {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;
}


/* =========================================================
   TYPE ICON
========================================================= */

.type-icon {
    width: 40px;
    height: 40px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 9px;

    background: var(--surface-soft);

    border: 1px solid var(--gray-light);

    color: var(--gray-dark);

    font-size: 15px;
}


/* Active assessment icon */

.assessment-card.active .type-icon {
    background: var(--black);

    border-color: var(--black);

    color: var(--white);
}


/* =========================================================
   STATUS
========================================================= */

.available,
.coming {
    display: inline-flex;

    align-items: center;

    padding: 5px 8px;

    border-radius: 5px;

    font-size: 9px;

    font-weight: 750;

    letter-spacing: 0.04em;

    text-transform: uppercase;

    white-space: nowrap;
}


/* Available */

.available {
    background: #f0f0f0;

    color: var(--black);

    border: 1px solid var(--gray-light);
}


/* Coming soon */

.coming {
    background: #eeeeee;

    color: var(--gray-dark);

    border: 1px solid var(--gray-light);
}


/* =========================================================
   CARD TITLE
========================================================= */

.assessment-card h3 {
    margin: 16px 0 6px;

    color: var(--black);

    font-size: 15px;

    font-weight: 750;

    line-height: 1.3;
}


/* =========================================================
   DESCRIPTION
========================================================= */

.card-description {
    margin: 0;

    min-height: 42px;

    color: var(--gray-dark);

    font-size: 11.5px;

    line-height: 1.55;
}


/* =========================================================
   TAGS
========================================================= */

.tags {
    display: flex;

    flex-wrap: wrap;

    gap: 6px;

    margin-top: 15px;
}

.tag {
    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 5px 8px;

    border-radius: 5px;

    font-size: 9.5px;

    font-weight: 700;

    line-height: 1;
}


/* Category */

.tag.category {
    background: #f2f2f2;

    color: var(--gray-dark);

    border: 1px solid #e3e3e3;
}


/* Difficulty */

.tag.difficulty {
    background: #e9e9e9;

    color: var(--black);

    border: 1px solid var(--gray-light);
}


/* =========================================================
   CARD FOOTER
========================================================= */

.card-footer {
    width: 100%;

    margin-top: 18px;

    padding-top: 13px;

    border-top: 1px solid #e8e8e8;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    box-sizing: border-box;
}


/* Footer information */

.card-footer>span {
    min-width: 0;

    display: flex;

    align-items: center;

    gap: 5px;

    color: var(--gray-dark);

    font-size: 10px;

    white-space: nowrap;
}


/* =========================================================
   START BUTTON
========================================================= */

.card-footer button {
    flex-shrink: 0;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    padding: 8px 12px;

    border: 1px solid var(--black);

    border-radius: 6px;

    background: var(--black);

    color: var(--white);

    font-family: inherit;

    font-size: 10px;

    font-weight: 700;

    cursor: pointer;

    transition:
        background 0.18s ease,
        border-color 0.18s ease,
        transform 0.12s ease;
}

.card-footer button:hover {
    background: var(--gray-dark);

    border-color: var(--gray-dark);
}

.card-footer button:active {
    transform: scale(0.96);
}


/* =========================================================
   LOCKED FOOTER
========================================================= */

.assessment-card.locked .card-footer {
    color: var(--gray);
}

.assessment-card.locked .card-footer i {
    color: var(--gray);
}

/* Empty Assessment Section */
.empty-assessment {
    width: 100%;
    height: 250px;
    background: #f0f0f0;
    border-radius: 5px;
    position: relative;
}

.box {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    height: 100px;
}

.box p {
    font-size: 13px;
    color: grey;
}

/* =========================================================
   LARGE TABLET
========================================================= */

@media (max-width: 1024px) {

    .assessment-page {
        padding: 24px;
    }

    .assessment-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 768px) {

    .assessment-page {
        padding: 20px 16px;
    }

    .coc-header {
        gap: 14px;

        margin-bottom: 26px;
    }

    .coc-icon {
        width: 48px;
        height: 48px;

        font-size: 18px;
    }

    .assessment-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 12px;
    }

    .assessment-card {
        padding: 16px;
    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 600px) {

    .assessment-page {
        padding: 16px 12px;
    }

    .back-btn {
        margin-bottom: 15px;
    }

    .coc-header {
        align-items: flex-start;

        padding: 16px;

        margin-bottom: 24px;

        border-radius: 12px;
    }

    .coc-icon {
        width: 44px;
        height: 44px;

        font-size: 16px;
    }

    .coc-header h1 {
        font-size: 10px;
    }

    .coc-header p {
        font-size: 16px;
    }

    .coc-header span {
        font-size: 11px;
    }

    .section-heading {
        margin-bottom: 13px;
    }

    .section-heading h2 {
        font-size: 16px;
    }

    .assessment-grid {
        grid-template-columns: 1fr;

        gap: 10px;
    }

    .assessment-card {
        padding: 16px;
    }

}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 400px) {

    .assessment-page {
        padding: 12px 10px;
    }

    .coc-header {
        gap: 11px;

        padding: 14px;
    }

    .coc-icon {
        width: 40px;
        height: 40px;

        border-radius: 8px;

        font-size: 14px;
    }

    .coc-header p {
        font-size: 15px;
    }

    .coc-header span {
        font-size: 10px;
    }

    .assessment-card {
        padding: 14px;

        border-radius: 10px;
    }

    .type-icon {
        width: 36px;
        height: 36px;

        font-size: 13px;
    }

    .assessment-card h3 {
        margin-top: 14px;

        font-size: 14px;
    }

    .card-description {
        min-height: 0;

        font-size: 11px;
    }

    .card-footer {
        margin-top: 15px;

        padding-top: 11px;
    }

}


/* =========================================================
   EXTRA SMALL
========================================================= */

@media (max-width: 330px) {

    .assessment-page {
        padding: 10px 8px;
    }

    .coc-header {
        padding: 12px;
    }

    .available,
    .coming {
        padding: 4px 6px;

        font-size: 8px;
    }

    .assessment-card {
        padding: 13px;
    }

    .card-footer>span {
        font-size: 9px;
    }

    .card-footer button {
        padding: 7px 9px;

        font-size: 9px;
    }

}


/* =========================================================
   REDUCED MOTION
========================================================= */

@media (prefers-reduced-motion: reduce) {

    .assessment-card,
    .back-btn,
    .card-footer button {
        transition: none;
    }

}
</style>