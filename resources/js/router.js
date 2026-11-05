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
import ImageIdentification from './views/AssessmentPages/ImageIdentification.vue'
import RJ45HandsOn from './views/AssessmentPages/Rj45Assessment.vue'
import ChooseAssessmentPage from './views/AssessmentPages/ChooseAssessmentPage.vue'
import UserLayout from './views/UserPages/UserLayout.vue'
import CMD from './views/AssessmentPages/CMD.vue'
import OrderingSequence from './views/AssessmentPages/DragDropOrderingSequence.vue'
import AssessmentLayout from './views/AssessmentPages/AssessmentLayout.vue'
import HardwareProcedure from './views/AssessmentPages/HardwareProcedure.vue'
import InputOutputAssessment from './views/AssessmentPages/InputOutputAssessment.vue'

// ==========================================================
// PUBLIC
// ==========================================================

import RegisterPage from './views/RegisterPage.vue'
import ForgotPage from './views/ForgotPage.vue'
import ResetPage from './views/ResetPage.vue'
import FAQ from './views/FAQ.vue'
import LoginPage from './views/LoginPage.vue'
import PrivacyPolicy from './views/PrivacyPolicy.vue'
import TermsOfUse from './views/TermsOfUse.vue'

// ==========================================================
// ADMIN
// ==========================================================
import AdminDashboard from './views/AdminPages/AdminDashboard.vue'
import UsersTable from './views/AdminPages/UsersTable.vue'
import Settings from './views/AdminPages/Settings.vue'
import StudentRecords from './views/AdminPages/StudentRecords.vue'
import ManageQuestions from './views/AdminPages/ManageQuiz.vue'
import AdminLayout from './views/AdminPages/AdminLayout.vue'
import AdminAssessmentPage from './views/AdminPages/AdminAssessmentPages.vue'

import RJ45AssessmentEdit from './views/AdminPages/RJ-45AssessmentEdit.vue'

// ==========================================================
// EDIT/ADD PAGES
// ==========================================================
import ImageIdentificationEdit from './views/AdminPages/EditAddPages/ImageIdentificationEdit.vue'
import MultipleAssessmentAdd from './views/AdminPages/EditAddPages/MultipleAssessmentAdd.vue'
import InputOutputEdit from './views/AdminPages/EditAddPages/InputOutputEdit.vue'
import RJ45Edit from './views/AdminPages/EditAddPages/RJ45Edit.vue'
import HardwareProcedureEdit from './views/AdminPages/EditAddPages/HardwareProcedureEdit.vue'
import MultipleAssessmentEdit from './views/AdminPages/EditAddPages/MultipleAssessmentEdit.vue'

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
        // Email the learner requests a new verification link from.
        path: '/verify',
        name: 'verify-email',
        component: () => import('./views/VerifyEmail.vue'),
    },

    {
        path: '/forgot',
        name: 'forgot',
        component: ForgotPage,
    },

    {
        path: '/faq',
        name: 'frequently-ask-question',
        component: FAQ,
    },

    {
        path: '/privacy',
        name: 'privacy-policy',
        component: PrivacyPolicy,
    },

    {
        path: '/terms',
        name: 'terms-of-use',
        component: TermsOfUse,
    },

    {
        path: '/reset/:token',
        name: 'reset',
        component: ResetPage,
    },

    {
        // Old verification links that point here go to the login page with the result.
        path: '/email-verified',
        redirect: (to) => ({
            path: '/',
            query: { verified: to.query.status === 'success' ? '1' : '0' },
        }),
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
                meta: {
                    navSection: 'home',
                },
            },

            {
                path: 'records',
                name: 'user-records',
                component: Records,
                meta: {
                    navSection: 'records',
                },
            },

            {
                path: 'assessments',
                name: 'user-assessments',
                component: AssessmentPage,
                meta: {
                    navSection: 'assessments',
                },
            },

            {
                path: 'assessments/assessment/:id',
                name: 'choose-assessment',
                component: ChooseAssessmentPage,
                meta: {
                    navSection: 'assessments',
                },
            },

            {
                path: 'profile',
                name: 'user-profile',
                component: Profile,
                meta: {
                    navSection: 'profile',
                },
            },
        ],
    },

    {
        path: '/taking',

        component: AssessmentLayout,
        name: 'layout-assessment',

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
            // COMPUTER ASSEMBLY & DISASSEMBLY
            // ==================================================

            {
                path: 'hardware-procedure/:id',
                name: 'assemble',
                component: HardwareProcedure,

                meta: {
                    assessmentTitle: 'Computer Assembly and Disassembly',
                    assessmentType: 'Virtual Hands-On Practical',
                },
            },

            {
                path: 'input-output/:id',
                name: 'input-output',
                component: InputOutputAssessment,

                meta: {
                    assessmentTitle: 'Input & Output Devices',
                    assessmentType: 'Drag and Drop Input/Output Devices',
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
            // CMD VIRTUAL HANDS-ON
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
                path: 'ordering-sequence/:id',
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
                path: 'assessments-list/:id',
                name: 'admin-assessment-list',
                component: AdminAssessmentPage,
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
                path: 'assessment/create',
                name: 'admin-quiz-create',
                component: MultipleAssessmentAdd,
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

            /*
            *
            * BELOW ARE THE EDIT ASSESSMENTS PATHS
            *
            */
            {
                path: 'assessment/:id/create',
                name: 'admin-quiz-create-specific',
                component: MultipleAssessmentAdd,
            },
            {
                path: 'assessment/:id/image_identification/edit',
                name: 'admin-image-edit',
                component: ImageIdentificationEdit,
            },
            {
                path: 'assessment/:id/multiple_choice/edit',
                name: 'admin-quiz-edit',
                component: MultipleAssessmentEdit,
            },
            {
                path: 'assessment/:id/input_output/edit',
                name: 'admin-input-output-edit',
                component: InputOutputEdit,
            },
            {
                path: 'assessment/:id/rj45_hands_on/edit',
                name: 'admin-rj45-edit',
                component: RJ45Edit,
            },
            {
                path: 'assessment/:id/hardware_procedure/edit',
                name: 'admin-hardware-procedure-edit',
                component: HardwareProcedureEdit,
            },
        ],
    },

    // ======================================================
    // 404 (keep last)
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

    // Only check auth once, but wait for it to complete.
    if (!authChecked) {
        authChecked = true

        authPromise = (async () => {
            try {
                await fetchUser()
            } catch (error) {
                console.error('Auth check failed:', error)
            }
        })()
    }

    if (authPromise) {
        await authPromise
    }

    const currentUser = user.value

    // PUBLIC ROUTES
    if (!to.meta.requiresAuth) {
        // Logged-in users go to their home page, except when a verification
        // result is in the URL. Redirecting then would hide the notice.
        if (to.path === '/' && currentUser && !to.query.verified) {
            return currentUser.role === 'admin' ? '/admin' : '/user'
        }
        return true
    }

    // PROTECTED ROUTES: NOT LOGGED IN
    if (!currentUser) {
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