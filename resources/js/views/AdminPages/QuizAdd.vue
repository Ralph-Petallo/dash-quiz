<template>
    <section class="admin-section">
        <!-- HEADER -->
        <div class="header-row">
            <div>
                <h2 class="section-title">Create Quiz</h2>
                <p class="subtitle">Build a new assessment for students</p>
            </div>

            <button @click="$router.push('/admin/manage-quizzes')" class="btn-secondary">
                Cancel
            </button>
        </div>

        <!-- FORM -->
        <div class="quiz-form-card">

            <!-- BASIC INFO -->
            <div class="form-grid">
                <div class="form-group">
                    <label>Quiz Name</label>
                    <input v-model="form.title" type="text" placeholder="e.g. Motherboard" />
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <input v-model="form.description" type="text" placeholder="e.g. Parts of motherboard" />
                </div>
            </div>

            <div class="divider"></div>

            <!-- QUESTIONS -->
            <div class="questions-header">
                <h3>Questions</h3>
                <span>{{ form.questions.length }} total</span>
            </div>

            <div v-for="(q, index) in form.questions" :key="index" class="question-card">

                <div class="question-top">
                    <span class="question-number">Question {{ index + 1 }}</span>
                    <button @click="removeQuestion(index)" class="delete-btn">Remove</button>
                </div>

                <input v-model="q.text" type="text" class="question-input" placeholder="Enter question..." />

                <div class="options-grid">
                    <div v-for="(opt, i) in ['A', 'B', 'C', 'D']" :key="i" class="option-item">
                        <input type="radio" :name="'correct_' + index" :value="i" v-model="q.correct_option" />
                        <span>{{ opt }}</span>
                        <input v-model="q.options[i]" type="text" :placeholder="'....'" />
                    </div>
                </div>
            </div>

            <!-- ACTIONS -->
            <div class="form-actions">
                <button @click="addQuestion" class="btn-outline">
                    + Add Question
                </button>

                <button @click="submitQuiz" class="btn-primary" :disabled="loading">
                    <span v-if="!loading">Save Quiz</span>
                    <span v-else>Saving...</span>
                </button>
            </div>

        </div>
    </section>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

const router = useRouter();
const loading = ref(false);

const form = ref({
    title: "",
    description: "",
    questions: Array.from({ length: 10 }, () => ({
        text: "",
        options: ["", "", "", ""],
        correct_option: 0,
    })),
});

const addQuestion = () => {
    form.value.questions.push({
        text: "",
        options: ["", "", "", ""],
        correct_option: 0,
    });
};

const removeQuestion = (index) => {
    // Keeping the 10-question minimum logic from your controller
    if (form.value.questions.length > 10) {
        form.value.questions.splice(index, 1);
    } else {
        alert("The backend requires at least 10 questions.");
    }
};

const submitQuiz = async () => {
    loading.value = true;
    try {
        // Note: Since your backend returns a redirect, Axios might
        // technically receive the HTML of the 'manage' page as a response.
        await axios.post("/api/admin/quizzes/create", form.value);

        alert("Quiz created successfully!");
        router.push("/admin/manage-quizzes");
    } catch (e) {
        console.error("Submission Error:", e.response?.data || console.error("Backend Crash Details:", exactError));
        alert(
            "Validation Error: Ensure all fields are filled and you have at least 10 questions."
        );

    } finally {
        loading.value = false;
    }
};
</script>
<style scoped>
.admin-section {
    padding: 20px;
}

/* HEADER */
.header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}

.section-title {
    font-size: 1.4rem;
    font-weight: 800;
}

.subtitle {
    font-size: 0.85rem;
    color: #6b7280;
}

/* CARD */
.quiz-form-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* GRID */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #374151;
}

.form-group input {
    padding: 10px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
}

.form-group input:focus {
    border-color: #6366f1;
    outline: none;
}

/* DIVIDER */
.divider {
    height: 1px;
    background: #f1f5f9;
}

/* QUESTIONS */
.questions-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
}

.question-card {
    border: 1px solid #f1f5f9;
    padding: 15px;
    border-radius: 12px;
    background: #fafafa;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* TOP */
.question-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.question-number {
    font-size: 0.8rem;
    font-weight: 700;
    color: #6366f1;
}

.delete-btn {
    font-size: 0.75rem;
    color: #ef4444;
    background: none;
    border: none;
    cursor: pointer;
}

/* INPUT */
.question-input {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

/* OPTIONS */
.options-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.option-item {
    display: flex;
    gap: 8px;
    align-items: center;
}

.option-item input[type="text"] {
    flex: 1;
    padding: 8px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

/* ACTIONS */
.form-actions {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-top: 10px;
}

.btn-primary {
    background: #6366f1;
    color: white;
    padding: 10px 18px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
}

.btn-primary:hover {
    background: #4f46e5;
}

.btn-outline {
    border: 1px dashed #6366f1;
    background: transparent;
    padding: 10px 14px;
    border-radius: 10px;
    cursor: pointer;
}

.btn-secondary {
    background: #f3f4f6;
    border: none;
    padding: 8px 14px;
    border-radius: 10px;
    cursor: pointer;
}

/* MOBILE */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }

    .options-grid {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
    }
}
</style>
