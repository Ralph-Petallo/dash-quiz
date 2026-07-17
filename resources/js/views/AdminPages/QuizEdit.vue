<template>
    <section class="admin-section">

        <!-- HEADER -->
        <div class="header-row">

            <div class="breadcrumb">
                <span class="previous-tab" @click="router.push('/admin/manage-quizzes')">Quizzes</span>

                <i class="fas fa-chevron-right crumb-icon"></i>

                <span class="active-tab">Edit Quiz</span>
            </div>

            <button @click="$router.push('/admin/manage-quizzes')" class="cancel-btn">
                Cancel
            </button>

        </div>

        <!-- LOADING -->
        <div v-if="initialLoading" class="loading">
            Loading Quiz Data...
        </div>

        <div v-else class="quiz-form-card">

            <!-- QUIZ DETAILS -->
            <div class="quiz-details">

                <div class="title">Quiz Details</div>

                <div class="form-group">
                    <label>Quiz Name</label>
                    <input v-model="form.title" type="text" placeholder="e.g. Motherboard" />
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input v-model="form.category" type="text" placeholder="e.g. Motherboard" />
                </div>
                <div class="form-group">
                    <label>Difficulty</label>
                    <select v-model="form.difficulty" class="difficulty-select">
                        <option value="" selected disabled>Select</option>
                        <option :value="'Easy'" :selected="form.difficulty === 'easy'">
                            Easy
                        </option>
                        <option :value="'Medium'" :selected="form.difficulty === 'medium'">
                            Medium
                        </option>
                        <option :value="'Hard'" :selected="form.difficulty === 'hard'">
                            Hard
                        </option>
                    </select>
                </div>


                <div class="form-group">
                    <label>Questions</label>
                    <span>{{ form.questions.length }}</span>
                </div>

                <div class="form-group">
                    <label>Topic / Description</label>
                    <textarea v-model="form.description" placeholder="e.g. Parts of the Motherboard" />
                </div>

            </div>

            <!-- QUESTIONS -->
            <h3>Questions</h3>

            <div v-for="(q, index) in form.questions" :key="index" class="question-block">

                <div class="question-meta">
                    <span>Question #{{ index + 1 }}</span>
                    <button @click="showDeleteModal" class="delete-link">
                        Remove Question
                    </button>
                </div>
                <input :ref="el => imageInputs[index] = el" type="file" accept="image/*" hidden
                    @change="handleImageChange($event, index)" />
                <!-- QUESTION -->
                <div class="question-text">
                    <input v-model="q.text" class="question-input" placeholder="Enter your question here" />
                </div>

                <!-- OPTIONS -->
                <div class="options-grid">
                    <div v-for="(opt, optIndex) in ['A', 'B', 'C', 'D']" :key="optIndex" class="option-item">
                        <input type="radio" :name="'correct_' + index" :value="optIndex" v-model="q.correct_option" />
                        <div>{{ opt }}.</div>
                        <input v-model="q.options[optIndex]" type="text" :placeholder="'Option ' + (optIndex + 1)" />
                    </div>
                </div>
                <p class="help-text">Note: Click radio button to select the correct answer</p>
            </div>

            <!-- ACTIONS -->
            <div class="form-actions">
                <button type="button" @click="addQuestion" class="btn-outline">
                    + Add Question
                </button>
                <button @click="updateQuiz" class="btn-primary" :disabled="saving">
                    {{ saving ? "Saving..." : "Save Changes" }}
                </button>
            </div>
        </div>
    </section>

    <!-- MODAL -->
    <div class="modal-backdrop" v-if="showDeleteConfirm">
        <div class="delete-modal">
            <h2>Delete Question?</h2>
            <p>
                This action cannot be undone.
            </p>
            <div class="modal-actions">
                <button class="cancel-btn" @click="showDeleteConfirm = false">
                    Cancel
                </button>
                <button class="btn-danger" @click="confirmDelete">
                    Delete
                </button>
            </div>
        </div>
    </div>

</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import axios from "axios";

const router = useRouter();
const route = useRoute();

const initialLoading = ref(true);
const saving = ref(false);

const form = ref({
    title: "",
    description: "",
    category: "",
    difficulty: "",
    questions: [],
});
const imageInputs = ref([]);
const showDeleteConfirm = ref(false);


const image_path_locator = (image_path) => {
    return `/storage/images/profiles/${image_path}`;
};

// Fetch data when component loads
onMounted(async () => {
    try {
        const response = await axios.get(
            `/api/admin/quizzes/${route.params.id}/edit`
        );
        const data = response.data;

        form.value.title = data.quiz.title;
        form.value.description = data.quiz.description;
        form.value.category = data.quiz.category;
        form.value.difficulty = data.quiz.difficulty;

        // Map the questions and options perfectly!
        form.value.questions = data.questions.map((q) => {
            // Find which option matches the correct answer text
            const correctIndex = q.options.findIndex(
                (opt) => opt.option_text === q.correct_answer_text
            );

            return {
                id: q.id,
                text: q.question_text,
                options: q.options.map((opt) => opt.option_text), // Extract just the text strings
                correct_option: correctIndex !== -1 ? correctIndex : 0, // Set the radio button!
                image_path: image_path_locator(q.image_path || 'default.png'),
                newImage: null,
            };
        });
    } catch (err) {
        console.error("Error fetching quiz data:", err);
        alert("Failed to load quiz data.");
    } finally {
        initialLoading.value = false;
    }
});

const addQuestion = () => {
    form.value.questions.push({
        text: "",
        options: ["", "", "", ""],
        correct_option: 0,
        image_path: null,
        newImage: null,
    });
};

const openImagePicker = (index) => {
    imageInputs.value[index]?.click();
};

const handleImageChange = (event, index) => {
    const file = event.target.files[0];

    if (!file) return;

    // store actual file
    form.value.questions[index].newImage = file;

    // instant preview
    form.value.questions[index].image_path = URL.createObjectURL(file);
};

const showDeleteModal = () => showDeleteConfirm.value = true;

const removeQuestion = (index) => {
    if (form.value.questions.length > 1) {
        form.value.questions.splice(index, 1);
    } else {
        alert("You must have at least one question.");
    }
};

const updateQuiz = async () => {
    saving.value = true;

    try {
        const payload = new FormData();
        payload.append("title", form.value.title);
        payload.append("description", form.value.description);
        payload.append("category", form.value.category);
        payload.append("difficulty", form.value.difficulty.toLowerCase());
        
        form.value.questions.forEach((q, index) => {
            payload.append(
                `questions[${index}][id]`,
                q.id ?? ""
            );

            payload.append(
                `questions[${index}][text]`,
                q.text
            );

            payload.append(
                `questions[${index}][correct_option]`,
                q.correct_option
            );

            q.options.forEach((opt, optIndex) => {
                payload.append(
                    `questions[${index}][options][${optIndex}]`,
                    opt
                );
            });

            if (q.newImage) {
                payload.append(
                    `questions[${index}][image]`,
                    q.newImage
                );
            }
        });

        payload.append("_method", "PUT");

        await axios.post(`/api/admin/quizzes/${route.params.id}`,
            payload,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }
        );

        alert("Quiz updated successfully!");
        router.push("/admin/manage-quizzes");
    } catch (e) {
        console.error(e.response?.data || e);
    } finally {
        saving.value = false;
    }
};
</script>

<style scoped>
/* ───────────── BASE ───────────── */
.quiz-form-card {
    background: #fff;
    padding: 18px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

h3 {
    margin-bottom: 12px;
    font-size: 16px;
    font-weight: 700;
    color: #111827;
}

/* ───────────── HEADER / BREADCRUMB ───────────── */
.header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #3f2ea3;
}

.breadcrumb {
    font-size: 12px;
}

.previous-tab {
    color: #555;
    cursor: pointer;
}

.active-tab {
    color: #222;
}

.smallicon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    font-size: 11px;
    color: #6b7280;
}

/* ───────────── BUTTONS ───────────── */
.cancel-btn {
    padding: 8px 14px;
    font-size: 12px;
    color: #fff;
    background: #111;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: 0.2s ease;
}

.cancel-btn:hover {
    background: #333;
}

.btn-primary {
    background: #6366f1;
    color: white;
    padding: 10px 18px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-weight: 600;
}

.btn-primary:hover {
    background: #4f46e5;
}

.difficulty-select {
    padding: 10px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 13px;
    outline: none;
    transition: 0.2s;
    background: #fff;
}

.loading {
    font-size: 14px;
    color: #6b7280;
    text-align: center;
    padding: 40px 0;
}

.btn-outline {
    border: 1px dashed #6366f1;
    background: transparent;
    padding: 10px 14px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    color: #6366f1;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-top: 14px;
}

.help-text {
    font-style: italic;
    font-size: 10px;
    margin-top: 5px;
    color: #6b7280;
}

@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
    }
}

/* ───────────── QUIZ DETAILS ───────────── */
.quiz-details {
    border: 1px solid #eee;
    padding: 18px;
    border-radius: 10px;
    margin-bottom: 10px;
}

.quiz-details .title {
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 10px;
    color: #111827;
}

.form-group {
    margin-bottom: 14px;
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 5px;
    color: #374151;
}

.form-group input,
.form-group textarea {
    padding: 10px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 13px;
    outline: none;
    transition: 0.2s;
    background: #fff;
}

.form-group textarea {
    min-height: 70px;
    resize: vertical;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

/* ───────────── QUESTION CARD ───────────── */
.question-block {
    border: 1px solid #eee;
    padding: 14px;
    border-radius: 10px;
    margin-bottom: 16px;
    background: #fff;
}

.question-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    font-weight: 600;
    font-size: 12px;
    color: #3f2ea3;
}

.delete-link {
    border: 1px solid;
    background: brown;
    color: white;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 10px;
    cursor: pointer;
    transition: 0.2s;
}

.delete-link:hover {
    border: 1px solid brown;
    color: brown;
    background: white;
}

/* ───────────── IMAGE ───────────── */
.image-container {
    display: flex;
    justify-content: center;
    margin: 10px 0;
}

.image-wrapper {
    position: relative;
    display: inline-block;
    cursor: pointer;
    border-radius: 8px;
    overflow: hidden;
}

.image-wrapper img {
    display: block;
    max-width: 100%;
    max-height: 120px;
    object-fit: contain;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    transition: transform 0.2s;
}

.image-wrapper:hover img {
    transform: scale(1.02);
}

.image-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 12px;
    opacity: 0;
    transition: opacity 0.2s ease;
    text-align: center;
    padding: 10px;
}

.image-wrapper:hover .image-overlay {
    opacity: 1;
}

/* ───────────── INPUTS ───────────── */

.question-text {
    display: flex;
}

.question-input {
    display: flex;
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 13px;
}

.options-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

@media (max-width: 600px) {
    .options-grid {
        grid-template-columns: 1fr;
    }
}

.option-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.option-item input[type="text"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 13px;
}

/* ───────────── MODAL ───────────── */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 100;
}

.delete-modal {
    width: 90%;
    max-width: 420px;
    background: #fff;
    border-radius: 14px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.delete-modal h2 {
    font-size: 18px;
    font-weight: 700;
}

.delete-modal p {
    margin-top: 8px;
    color: #6b7280;
    font-size: 12px;
    line-height: 1.5;
}

.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.modal-actions button {
    flex: 1;
    padding: 10px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    cursor: pointer;
}

.cancel-btn {
    background: black;
    border: 1px solid;
    color: white;
    padding: 8px 14px;
    border-radius: 10px;
    cursor: pointer;
}

.cancel-btn:hover {
    background: white;
    border: 1px solid black;
    color: black;
}

.btn-danger {
    background: #dc2626;
    color: white;
}

.btn-danger:hover {
    background: #b91c1c;
}

/* ───────────── MISC ───────────── */
.form-divider {
    margin: 20px 0;
    border: 0;
    border-top: 1px solid #eee;
}
</style>
