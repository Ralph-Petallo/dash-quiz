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

        <div class="assessment-grid">

            <!-- Multiple Choice -->
            <div class="assessment-card active">

                <div class="card-top">
                    <div class="type-icon">
                        <i class="fas fa-list-check"></i>
                    </div>

                    <span class="available">
                        Available
                    </span>
                </div>

                <h3>Multiple Choice</h3>

                <p class="card-description">
                    Test your knowledge of computer systems and basic troubleshooting.
                </p>

                <!-- Tags -->
                <div class="tags">
                    <span class="tag category">
                        <i class="fas fa-layer-group"></i>
                        Hardware
                    </span>

                    <span class="tag difficulty">
                        <i class="fas fa-signal"></i>
                        Easy
                    </span>
                </div>

                <div class="card-footer">
                    <span>
                        <i class="fas fa-question-circle"></i>
                        10 Questions
                    </span>

                    <button @click="startAssessment">
                        Start
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>

            </div>


            <!-- Image Identification -->
            <div class="assessment-card locked">

                <div class="card-top">
                    <div class="type-icon">
                        <i class="fas fa-image"></i>
                    </div>

                    <span class="coming">
                        Coming Soon
                    </span>
                </div>

                <h3>Image Identification</h3>

                <p class="card-description">
                    Identify computer components, tools, ports, and equipment.
                </p>

                <div class="tags">
                    <span class="tag category">
                        <i class="fas fa-layer-group"></i>
                        Components
                    </span>

                    <span class="tag difficulty">
                        <i class="fas fa-signal"></i>
                        Medium
                    </span>
                </div>

                <div class="card-footer">
                    <span>
                        <i class="fas fa-lock"></i>
                        Not Available
                    </span>
                </div>

            </div>


            <!-- Drag & Drop -->
            <div class="assessment-card locked">

                <div class="card-top">
                    <div class="type-icon">
                        <i class="fas fa-arrows-up-down-left-right"></i>
                    </div>

                    <span class="coming">
                        Coming Soon
                    </span>
                </div>

                <h3>Drag & Drop</h3>

                <p class="card-description">
                    Arrange computer components and installation procedures correctly.
                </p>

                <div class="tags">
                    <span class="tag category">
                        <i class="fas fa-layer-group"></i>
                        Installation
                    </span>

                    <span class="tag difficulty">
                        <i class="fas fa-signal"></i>
                        Medium
                    </span>
                </div>

                <div class="card-footer">
                    <span>
                        <i class="fas fa-lock"></i>
                        Not Available
                    </span>
                </div>

            </div>


            <!-- Scenario Based -->
            <div class="assessment-card locked">

                <div class="card-top">
                    <div class="type-icon">
                        <i class="fas fa-comments"></i>
                    </div>

                    <span class="coming">
                        Coming Soon
                    </span>
                </div>

                <h3>Scenario-Based</h3>

                <p class="card-description">
                    Solve realistic computer problems using troubleshooting knowledge.
                </p>

                <div class="tags">
                    <span class="tag category">
                        <i class="fas fa-layer-group"></i>
                        Troubleshooting
                    </span>

                    <span class="tag difficulty">
                        <i class="fas fa-signal"></i>
                        Hard
                    </span>
                </div>

                <div class="card-footer">
                    <span>
                        <i class="fas fa-lock"></i>
                        Not Available
                    </span>
                </div>

            </div>


            <!-- Virtual Hands-On -->
            <div class="assessment-card locked">

                <div class="card-top">
                    <div class="type-icon">
                        <i class="fas fa-screwdriver-wrench"></i>
                    </div>

                    <span class="coming">
                        Coming Soon
                    </span>
                </div>

                <h3>Virtual Hands-On</h3>

                <p class="card-description">
                    Perform computer installation and configuration tasks virtually.
                </p>

                <div class="tags">
                    <span class="tag category">
                        <i class="fas fa-layer-group"></i>
                        Practical
                    </span>

                    <span class="tag difficulty">
                        <i class="fas fa-signal"></i>
                        Hard
                    </span>
                </div>

                <div class="card-footer">
                    <span>
                        <i class="fas fa-lock"></i>
                        Not Available
                    </span>
                </div>

            </div>

        </div>

    </div>
</template>


<script setup>
import { useRouter, useRoute } from 'vue-router'
import { onMounted, ref } from 'vue'
import axios from 'axios'

const router = useRouter()
const route = useRoute()
const assessmentObject = ref({});

const fetchAssessmentData = async () => {
    const quizId = route.params.id;

    const { data } = await axios.get('/api/quizzes')
    assessmentObject.value = data.data[quizId - 1] || {};

}

const startAssessment = () => {
    // Change this route to your actual quiz 
    // const quizId = route.params.quiz_id
    const quizId = route.params.id
    console.log(quizId)

    if (!quizId) {
        router.push('/user/quizzes')
        return
    }
    router.push(`/quiz/${quizId}`)
}

onMounted(() => {
    fetchAssessmentData()
})

</script>


<style scoped>
.assessment-page {
    min-height: 100vh;
    padding: 24px;
    background: #f8fafc;
    color: #1e293b;
}


/* BACK */

.back-btn {
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 13px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 20px;
}

.back-btn:hover {
    color: #4f46e5;
}


/* COC HEADER */

.coc-header {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 22px;
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 30px;
}

.coc-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    background: #eef2ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.coc-header h1 {
    margin: 0;
    font-size: 14px;
    color: #4f46e5;
    font-weight: 700;
}

.coc-header p {
    margin: 3px 0;
    font-size: 19px;
    font-weight: 650;
    color: #1e293b;
}

.coc-header span {
    font-size: 13px;
    color: #64748b;
}


/* SECTION */

.section-heading {
    margin-bottom: 15px;
}

.section-heading h2 {
    margin: 0;
    font-size: 17px;
    font-weight: 650;
}

.section-heading p {
    margin: 4px 0 0;
    font-size: 12px;
    color: #64748b;
}


/* GRID */

.assessment-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
    gap: 14px;
}


/* CARD */

.assessment-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 18px;
    transition: 0.2s ease;
}

.assessment-card.active:hover {
    border-color: #c7d2fe;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
}

.assessment-card.locked {
    opacity: 0.65;
}


/* TOP */

.card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.type-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #f1f5f9;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
}

.active .type-icon {
    background: #eef2ff;
    color: #4f46e5;
}


/* STATUS */

.available,
.coming {
    font-size: 10px;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 20px;
}

.available {
    background: #ecfdf5;
    color: #059669;
}

.coming {
    background: #f1f5f9;
    color: #64748b;
}


/* TEXT */

.assessment-card h3 {
    margin: 15px 0 6px;
    font-size: 15px;
}

.card-description {
    margin: 0;
    min-height: 40px;
    font-size: 12px;
    line-height: 1.5;
    color: #64748b;
}


/* TAGS */

.tags {
    display: flex;
    gap: 6px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 600;
}

.tag.category {
    background: #f1f5f9;
    color: #475569;
}

.tag.difficulty {
    background: #eef2ff;
    color: #4f46e5;
}


/* FOOTER */

.card-footer {
    border-top: 1px solid #f1f5f9;
    margin-top: 17px;
    padding-top: 13px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    font-size: 11px;
    color: #64748b;
}

.card-footer span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.card-footer button {
    border: none;
    background: #4f46e5;
    color: white;
    border-radius: 7px;
    padding: 7px 11px;
    font-size: 11px;
    cursor: pointer;

    display: flex;
    align-items: center;
    gap: 6px;
}

.card-footer button:hover {
    background: #4338ca;
}


/* MOBILE */

@media (max-width: 600px) {

    .assessment-page {
        padding: 16px;
    }

    .coc-header {
        padding: 16px;
    }

    .coc-header p {
        font-size: 16px;
    }

    .coc-header span {
        font-size: 11px;
    }

    .assessment-grid {
        grid-template-columns: 1fr;
    }

}
</style>