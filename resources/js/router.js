import { createRouter, createWebHistory } from 'vue-router'
import { useUser } from '@/composables/useUser'

// ==========================================================
// USER PAGES
// ==========================================================

import HomePage from './views/UserPages/HomePage.vue'
import Profile from './views/UserPages/ProfilePage.vue'
import Records from './views/UserPages/RecordsPage.vue'
import AssessmentPage from './views/UserPages/AssessmentPage.vue'

// ==========================================================
// ASSESSMENTS PAGE
// ==========================================================
import MultipleChoice from './views/AssessmentPages/MultipleChoice.vue'
import AssembleSimulator from './views/AssessmentPages/AssembleAssessment.vue'
import ImageIdentification from './views/AssessmentPages/ImageIdentification.vue'
import RJ45HandsOn from './views/AssessmentPages/Rj45Assessment.vue'
import ChooseAssessmentPage from './views/AssessmentPages/ChooseAssessmentPage.vue'
import ResultPage from './views/UserPages/ResultPage.vue'
import UserLayout from './views/UserPages/UserLayout.vue'
import CMD from './views/AssessmentPages/CMD.vue'
import DisassembleSimulator from './views/AssessmentPages/DisassembleSimulator.vue'
import OrderingSequence from './views/AssessmentPages/DragDropOrderingSequence.vue'
import AssessmentLayout from './views/AssessmentPages/AssessmentLayout.vue'

// ==========================================================
// PUBLIC
// ==========================================================

import LoginPage from './views/LoginPage.vue'
import RegisterPage from './views/RegisterPage.vue'
import ForgotPage from './views/ForgotPage.vue'
import ResetPage from './views/ResetPage.vue'

// ==========================================================
// ADMIN
// ==========================================================

import AdminDashboard from './views/AdminPages/AdminDashboard.vue'
import UsersTable from './views/AdminPages/UsersTable.vue'
import Settings from './views/AdminPages/Settings.vue'
import StudentRecords from './views/AdminPages/StudentRecords.vue'
import ManageQuestions from './views/AdminPages/ManageQuiz.vue'
import AdminLayout from './views/AdminPages/AdminLayout.vue'

import MultipleAssessmentAdd from './views/AdminPages/MultipleAssessmentAdd.vue'
import MultipleAssessmentEdit from './views/AdminPages/MultipleAssessmentEdit.vue'
import RJ45AssessmentEdit from './views/AdminPages/RJ-45AssessmentEdit.vue'

// ==========================================================
// ROUTES
// ==========================================================

const routes = [

    // ======================================================
    // PUBLIC
    // ======================================================

    {
        path: '/',
        name: 'login',
        component: LoginPage,
    },

    {
        path: '/register',
        name: 'register',
        component: RegisterPage,
    },

    {
        path: '/forgot',
        name: 'forgot',
        component: ForgotPage,
    },

    {
        path: '/reset/:token',
        name: 'reset',
        component: ResetPage,
    },


    // ======================================================
    // USER
    // ======================================================

    {
        path: '/user',
        component: UserLayout,

        meta: {
            requiresAuth: true,
            requiresStudent: true,
        },

        children: [

            {
                path: '',
                name: 'user-home',
                component: HomePage,
            },

            {
                path: 'records',
                name: 'user-records',
                component: Records,
            },

            {
                path: 'assessments',
                name: 'user-assessments',
                component: AssessmentPage,
            },

            {
                path: 'quizzes/assessment/:id',
                name: 'choose-assessment',
                component: ChooseAssessmentPage,
            },

            {
                path: 'profile',
                name: 'user-profile',
                component: Profile,
            },



            // ==================================================
            // RESULT
            // ==================================================

            {
                path: 'quiz-result/:id',
                name: 'quiz-result',
                component: ResultPage,
            },

        ],
    },

    {
        path: '/taking',

        component: AssessmentLayout,
        name: "layout-assessment",
        meta: {
            requiresAuth: true,
            requiresStudent: true,
        },

        children: [

            // ==================================================
            // MULTIPLE CHOICE
            // ==================================================

            {
                path: 'multiple-choice/:quiz_id',
                name: 'quiz-start',
                component: MultipleChoice,

                meta: {
                    assessmentTitle: 'Multiple Choice',
                    assessmentType: 'Knowledge Assessment',
                },
            },


            // ==================================================
            // DRAG & DROP
            // ==================================================

            {
                path: 'assemble/:id',
                name: 'assemble',
                component: AssembleSimulator,

                meta: {
                    assessmentTitle: 'Component Arrangement',
                    assessmentType: 'Drag & Drop',
                },
            },


            // ==================================================
            // IMAGE IDENTIFICATION
            // ==================================================

            {
                path: 'image-identification/:id',
                name: 'image-identification',
                component: ImageIdentification,

                meta: {
                    assessmentTitle: 'Computer Components',
                    assessmentType: 'Image Identification',
                },
            },


            // ==================================================
            // RJ45 VIRTUAL HANDS-ON
            // ==================================================

            {
                path: 'rj45/:id',
                name: 'rj45-hands-on',
                component: RJ45HandsOn,

                meta: {
                    assessmentTitle: 'RJ-45 Cable Assembly',
                    assessmentType: 'Virtual Hands-On',
                },
            },


            // ==================================================
            // VIRTUAL HANDS-ON
            // ==================================================

            {
                path: 'cmd',
                name: 'cmd',
                component: CMD,

                meta: {
                    assessmentTitle: 'Command Line',
                    assessmentType: 'Virtual Hands-On',
                },
            },

            {
                path: 'disassemble',
                name: 'disassemble',
                component: DisassembleSimulator,

                meta: {
                    assessmentTitle: 'Computer Disassembly',
                    assessmentType: 'Virtual Hands-On',
                },
            },

            {
                path: 'ordering-sequence',
                name: 'ordering-sequence',
                component: OrderingSequence,

                meta: {
                    assessmentTitle: 'Installation Sequence',
                    assessmentType: 'Virtual Hands-On',
                },
            },

        ],
    },

    // ======================================================
    // ADMIN
    // ======================================================

    {
        path: '/admin',
        component: AdminLayout,

        meta: {
            requiresAuth: true,
            requiresAdmin: true,
        },

        children: [

            {
                path: '',
                name: 'admin-dashboard',
                component: AdminDashboard,
            },

            {
                path: 'users',
                name: 'admin-users',
                component: UsersTable,
            },

            {
                path: 'records',
                name: 'admin-records',
                component: StudentRecords,
            },

            {
                path: 'quizzes/create',
                name: 'admin-quiz-create',
                component: MultipleAssessmentAdd,
            },

            {
                path: 'quizzes/:id/edit',
                name: 'admin-quiz-edit',
                component: MultipleAssessmentEdit,
            },

            {
                path: 'quizzes/:id/create',
                name: 'admin-quiz-create-specific',
                component: MultipleAssessmentAdd,
            },

            {
                path: 'quizzes/:id/:type/multiple/edit',
                name: 'admin-multiple-edit',
                component: MultipleAssessmentEdit,
            },

            {
                path: 'quizzes/:id/:type/rj-45/edit',
                name: 'admin-rj45-edit',
                component: RJ45AssessmentEdit,
            },

            {
                path: 'settings',
                name: 'admin-settings',
                component: Settings,
            },

            {
                path: 'manage-quizzes',
                name: 'admin-manage-quizzes',
                component: ManageQuestions,
            },
        ],
    },


    // ======================================================
    // 404
    // ======================================================

    {
        path: '/:pathMatch(.*)*',
        name: 'NotFound',
        component: () => import('./views/404.vue'),
    },
]


// ==========================================================
// ROUTER
// ==========================================================

const router = createRouter({
    history: createWebHistory(),
    routes,

    scrollBehavior() {
        return {
            top: 0,
            behavior: 'smooth',
        }
    },
})


// ==========================================================
// AUTH GUARD
// ==========================================================
let authChecked = false
let authPromise = null

router.beforeEach(async (to) => {
    const { user, fetchUser } = useUser()

    // ✅ Only check auth once, but wait for it to complete
    if (!authChecked) {
        authChecked = true

        // Create a promise and wait for it
        authPromise = (async () => {
            try {

                console.log('🔐 Checking auth...')
                await fetchUser()
                console.log('✅ Auth check complete:', user.value)
            } catch (error) {
                console.error('❌ Auth check failed:', error)
            }
        })()
    }

    // Always wait for auth promise to complete
    if (authPromise) {
        await authPromise
    }

    const currentUser = user.value

    // PUBLIC ROUTES
    if (!to.meta.requiresAuth) {
        if (to.path === '/' && currentUser) {
            return currentUser.role === 'admin' ? '/admin' : '/user'
        }
        return true
    }

    // PROTECTED ROUTES - NOT LOGGED IN
    if (!currentUser) {
        console.warn('❌ NOT AUTHENTICATED - Redirecting to login')
        return {
            name: 'login',
            query: { redirect: to.fullPath },
        }
    }

    // ROLE-BASED ACCESS
    if (to.meta.requiresStudent && currentUser.role === 'admin') {
        return { name: 'admin-dashboard' }
    }

    if (to.meta.requiresAdmin && currentUser.role !== 'admin') {
        return { name: 'user-home' }
    }

    return true
})

export default router