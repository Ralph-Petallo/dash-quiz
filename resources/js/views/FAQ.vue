<template>
    <div class="faq-page">

        <!-- =====================================================
         HEADER
    ====================================================== -->

        <header class="faq-header">

            <div class="header-content">

                <span class="eyebrow">
                    CSS PREP / SUPPORT
                </span>

                <h1>
                    FAQ:
                </h1>

                <p>
                    Find answers about CSS Prep, assessments, studying,
                    progress tracking, and getting support.
                </p>

            </div>

            <!-- SEARCH -->

            <div class="faq-search">

                <i class="fas fa-search"></i>

                <input v-model="searchQuery" type="search" placeholder="Search questions..."
                    aria-label="Search frequently asked questions" />

                <button v-if="searchQuery" type="button" class="clear-search" @click="searchQuery = ''"
                    aria-label="Clear search">
                    <i class="fas fa-times"></i>
                </button>

            </div>

        </header>


        <!-- =====================================================
         FAQ CONTENT
    ====================================================== -->

        <main class="faq-content">

            <!-- NO RESULTS -->

            <div v-if="filteredSections.length === 0" class="faq-empty">

                <div class="empty-icon">
                    <i class="fas fa-search"></i>
                </div>

                <h2>
                    No questions found
                </h2>

                <p>
                    Try searching with a different keyword.
                </p>

                <button type="button" @click="searchQuery = ''">
                    Clear search
                </button>

            </div>


            <!-- FAQ SECTIONS -->

            <section v-for="section in filteredSections" :key="section.title" class="faq-section">

                <div class="section-heading">

                    <span class="section-number">
                        {{ String(section.number).padStart(2, '0') }}
                    </span>

                    <div>
                        <h2>
                            {{ section.title }}
                        </h2>

                        <span>
                            {{ section.items.length }}
                            {{ section.items.length === 1 ? 'question' : 'questions' }}
                        </span>
                    </div>

                </div>


                <div class="faq-list">

                    <article v-for="item in section.items" :key="item.question" class="faq-item" :class="{
                        'is-open': isOpen(item.question)
                    }">

                        <button type="button" class="faq-question" :aria-expanded="isOpen(item.question)"
                            @click="toggleQuestion(item.question)">

                            <span>
                                {{ item.question }}
                            </span>

                            <span class="question-icon">
                                <i class="fas" :class="isOpen(item.question)
                                    ? 'fa-minus'
                                    : 'fa-plus'
                                    "></i>
                            </span>

                        </button>


                        <Transition name="faq">

                            <div v-if="isOpen(item.question)" class="faq-answer">

                                <p>
                                    {{ item.answer }}
                                </p>

                            </div>

                        </Transition>

                    </article>

                </div>

            </section>

        </main>

    </div>
</template>


<script setup>
import {
    computed,
    ref,
} from 'vue'


/* ============================================================
   SEARCH
============================================================ */

const searchQuery = ref('')


/* ============================================================
   OPEN QUESTION
============================================================ */

const openQuestion = ref(null)


/* ============================================================
   FAQ DATA
============================================================ */

const faqSections = [

    {
        number: 1,
        title: 'General Questions',

        items: [

            {
                question: 'What is CSS Prep?',

                answer:
                    'CSS Prep is a learning and assessment platform designed to help Computer Systems Servicing (CSS) students prepare for their TESDA CSS NC II competency assessments. It provides study resources, practice assessments, progress tracking, and interactive activities.'
            },

            {
                question: 'Who is CSS Prep for?',

                answer:
                    'CSS Prep is primarily designed for students studying Computer Systems Servicing (CSS), particularly learners preparing for TESDA Computer Systems Servicing NC II competency assessments.'
            },

            {
                question: 'How should I get started?',

                answer:
                    'Start by reviewing the available competencies and assessments. Work through each topic one at a time, practice what you have learned, and use your assessment results to identify areas that need more review.'
            },

        ],
    },


    {
        number: 2,
        title: 'Studying and Preparation',

        items: [

            {
                question: 'How can I make a study plan?',

                answer:
                    'Break the CSS competencies into manageable topics. Set realistic weekly goals, dedicate time to both theory and practical activities, and leave enough time for review and practice before your assessment.'
            },

            {
                question: 'How often should I practice?',

                answer:
                    'Practice regularly instead of relying on last-minute studying. Short and consistent study sessions can help you retain concepts and become more comfortable with practical procedures.'
            },

            {
                question: 'What should I do if I’m struggling with a topic?',

                answer:
                    'Go back to the fundamentals and review the topic carefully. Try simpler practice questions or activities first, then gradually work toward more difficult assessments. You can also ask your teacher or instructor for additional guidance.'
            },

            {
                question: 'How can I improve my written answers?',

                answer:
                    'Read each question carefully, identify what is being asked, and organize your answer clearly. Use correct technical terms and explain your reasoning when necessary. Practice answering within a reasonable time limit.'
            },

        ],
    },


    {
        number: 3,
        title: 'Practice and Progress',

        items: [

            {
                question: 'Are practice questions included?',

                answer:
                    'Yes. CSS Prep can provide different types of assessments and practice activities, including multiple-choice questions, image identification, drag-and-drop activities, scenario-based tasks, and virtual hands-on activities.'
            },

            {
                question: 'How can I track my progress?',

                answer:
                    'Use the Records section to review your completed assessments and results. Your assessment history can help you identify completed competencies and topics that may require additional practice.'
            },

            {
                question: 'What should I do before assessment day?',

                answer:
                    'Review the competencies you have studied, practice important procedures, check your previous assessment results, and make sure you understand the assessment instructions. Get enough rest so you can focus during the assessment.'
            },

        ],
    },


    {
        number: 4,
        title: 'Support',

        items: [

            {
                question: 'I found an error or have a question. What should I do?',

                answer:
                    'Report the issue or question to your CSS Prep teacher or administrator so it can be reviewed and addressed.'
            },

            {
                question: 'How can I suggest a new feature or resource?',

                answer:
                    'You can send your suggestion to the CSS Prep teacher or administrator. Suggestions about new assessments, learning resources, or improvements can help make the platform more useful for students.'
            },

        ],
    },

]


/* ============================================================
   FILTER
============================================================ */

const filteredSections = computed(() => {

    const query =
        searchQuery.value
            .trim()
            .toLowerCase()


    if (!query) {
        return faqSections
    }


    return faqSections
        .map(section => {

            const items =
                section.items.filter(item => {

                    return (
                        item.question
                            .toLowerCase()
                            .includes(query) ||

                        item.answer
                            .toLowerCase()
                            .includes(query)
                    )

                })


            return {
                ...section,
                items,
            }

        })
        .filter(section =>
            section.items.length > 0
        )

})


/* ============================================================
   TOGGLE
============================================================ */

function toggleQuestion(question) {

    openQuestion.value =
        openQuestion.value === question
            ? null
            : question

}


/* ============================================================
   CHECK OPEN
============================================================ */

function isOpen(question) {

    return openQuestion.value === question

}
</script>


<style scoped>
/* ============================================================
   PAGE
============================================================ */

.faq-page {
    min-height: 100%;
    background: #fafafa;
    color: #111111;
}


/* ============================================================
   HEADER
============================================================ */

.faq-header {
    padding: 48px 32px 36px;
    border-bottom: 1px solid #e5e5e5;
    background: #ffffff;
}


.header-content {
    max-width: 900px;
    margin: 0 auto;
}


.eyebrow {
    display: inline-flex;
    align-items: center;

    margin-bottom: 12px;

    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.14em;

    color: #737373;
}


.faq-header h1 {
    margin: 0;

    font-size: clamp(30px,
            4vw,
            46px);

    line-height: 1.05;
    font-weight: 700;
    letter-spacing: -0.04em;
}


.faq-header p {
    max-width: 650px;

    margin: 16px 0 0;

    font-size: 15px;
    line-height: 1.7;

    color: #737373;
}


/* ============================================================
   SEARCH
============================================================ */

.faq-search {
    position: relative;

    max-width: 900px;

    margin: 28px auto 0;
}


.faq-search>i {
    position: absolute;

    top: 50%;
    left: 17px;

    transform: translateY(-50%);

    color: #737373;
    font-size: 14px;

    pointer-events: none;
}


.faq-search input {
    width: 100%;
    height: 48px;

    padding: 0 46px;

    border: 1px solid #d9d9d9;
    border-radius: 10px;

    background: #fafafa;

    color: #111111;

    font-family: inherit;
    font-size: 14px;

    outline: none;

    transition:
        border-color 0.2s ease,
        background 0.2s ease;
}


.faq-search input:focus {
    border-color: #111111;
    background: #ffffff;
}


.faq-search input::placeholder {
    color: #a3a3a3;
}


.clear-search {
    position: absolute;

    top: 50%;
    right: 10px;

    width: 30px;
    height: 30px;

    transform: translateY(-50%);

    border: 0;
    border-radius: 6px;

    background: transparent;

    color: #737373;

    cursor: pointer;
}


.clear-search:hover {
    background: #eeeeee;
    color: #111111;
}


/* ============================================================
   CONTENT
============================================================ */

.faq-content {
    max-width: 900px;

    margin: 0 auto;

    padding: 42px 32px 80px;
}


/* ============================================================
   SECTION
============================================================ */

.faq-section {
    margin-bottom: 48px;
}


.section-heading {
    display: flex;
    align-items: center;
    gap: 14px;

    margin-bottom: 16px;
}


.section-number {
    display: grid;
    place-items: center;

    width: 34px;
    height: 34px;

    flex: 0 0 34px;

    border: 1px solid #d9d9d9;
    border-radius: 8px;

    background: #ffffff;

    font-size: 10px;
    font-weight: 700;

    color: #737373;
}


.section-heading h2 {
    margin: 0;

    font-size: 18px;
    font-weight: 700;

    letter-spacing: -0.02em;
}


.section-heading span:not(.section-number) {
    display: block;

    margin-top: 2px;

    font-size: 11px;

    color: #a3a3a3;
}


/* ============================================================
   FAQ LIST
============================================================ */

.faq-list {
    border-top: 1px solid #e5e5e5;
}


.faq-item {
    border-bottom: 1px solid #e5e5e5;

    background: #ffffff;

    transition:
        background 0.2s ease;
}


.faq-item.is-open {
    background: #f7f7f7;
}


.faq-question {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 24px;

    width: 100%;

    padding: 20px 18px;

    border: 0;

    background: transparent;

    color: #111111;

    text-align: left;

    font-family: inherit;
    font-size: 14px;
    font-weight: 600;

    line-height: 1.5;

    cursor: pointer;
}


.faq-question:hover {
    background: #f3f3f3;
}


.question-icon {
    display: grid;
    place-items: center;

    width: 26px;
    height: 26px;

    flex: 0 0 26px;

    border: 1px solid #d9d9d9;
    border-radius: 6px;

    background: #ffffff;

    color: #737373;

    font-size: 10px;

    transition:
        transform 0.2s ease,
        background 0.2s ease;
}


.faq-item.is-open .question-icon {
    background: #111111;
    border-color: #111111;
    color: #ffffff;
}


/* ============================================================
   ANSWER
============================================================ */

.faq-answer {
    padding: 0 18px 20px 18px;
}


.faq-answer p {
    max-width: 760px;

    margin: 0;

    font-size: 14px;
    line-height: 1.75;

    color: #696969;
}


/* ============================================================
   TRANSITION
============================================================ */

.faq-enter-active,
.faq-leave-active {
    overflow: hidden;

    transition:
        opacity 0.2s ease,
        max-height 0.25s ease;
}


.faq-enter-from,
.faq-leave-to {
    max-height: 0;
    opacity: 0;
}


.faq-enter-to,
.faq-leave-from {
    max-height: 300px;
    opacity: 1;
}


/* ============================================================
   EMPTY
============================================================ */

.faq-empty {
    padding: 80px 20px;

    text-align: center;
}


.empty-icon {
    display: grid;
    place-items: center;

    width: 48px;
    height: 48px;

    margin: 0 auto 16px;

    border: 1px solid #dedede;
    border-radius: 12px;

    background: #ffffff;

    color: #737373;
}


.faq-empty h2 {
    margin: 0;

    font-size: 18px;
}


.faq-empty p {
    margin: 8px 0 20px;

    color: #737373;
    font-size: 14px;
}


.faq-empty button {
    height: 38px;

    padding: 0 16px;

    border: 1px solid #111111;
    border-radius: 7px;

    background: #111111;
    color: #ffffff;

    font-family: inherit;
    font-size: 13px;
    font-weight: 600;

    cursor: pointer;
}


.faq-empty button:hover {
    background: #2a2a2a;
}


/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 640px) {

    .faq-header {
        padding: 32px 18px 28px;
    }


    .faq-header h1 {
        font-size: 30px;
    }


    .faq-header p {
        font-size: 14px;
    }


    .faq-content {
        padding: 30px 18px 60px;
    }


    .faq-section {
        margin-bottom: 36px;
    }


    .section-heading {
        gap: 10px;
    }


    .section-number {
        width: 30px;
        height: 30px;

        flex-basis: 30px;
    }


    .section-heading h2 {
        font-size: 16px;
    }


    .faq-question {
        padding: 17px 14px;

        gap: 14px;

        font-size: 13px;
    }


    .faq-answer {
        padding:
            0 14px 18px;
    }


    .faq-answer p {
        font-size: 13px;
        line-height: 1.7;
    }

}
</style>