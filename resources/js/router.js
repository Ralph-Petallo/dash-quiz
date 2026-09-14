import { createRouter, createWebHistory } from 'vue-router'
import { useUser } from '@/composables/useUser'

// User Pages
import HomePage from './views/UserPages/HomePage.vue'
import Profile from './views/UserPages/ProfilePage.vue'
import Records from './views/UserPages/RecordsPage.vue'
import AssessmentPage from './views/UserPages/AssessmentPage.vue'
import MultipleChoice from './views/UserPages/QuizzesPages/MultipleChoice.vue'
import DragDrop from './views/UserPages/QuizzesPages/DragDrop.vue'
import ImageIdentification from './views/UserPages/QuizzesPages/ImageIdentification.vue'
import RJ45HandsOn from './views/UserPages/QuizzesPages/RJ45HandsOn.vue'
import ChooseAssessmentPage from './views/UserPages/QuizzesPages/ChooseAssessmentPage.vue'
import Result from './views/UserPages/ResultPage.vue'
import UserLayout from './views/UserPages/UserLayout.vue'

// Public Pages
import LoginPage from './views/LoginPage.vue'
import RegisterPage from './views/RegisterPage.vue'
import ForgotPage from './views/ForgotPage.vue'
import ResetPage from './views/ResetPage.vue'

// Admin Pages
import AdminDashboard from './views/AdminPages/AdminDashboard.vue'
import UsersTable from './views/AdminPages/UsersTable.vue'
import Settings from './views/AdminPages/Settings.vue'
import StudentRecords from './views/AdminPages/StudentRecords.vue'
import ManageQuestions from './views/AdminPages/ManageQuiz.vue'
import AdminLayout from './views/AdminPages/AdminLayout.vue'

//Admin Edit Assessments Pages
import MultipleAssessmentAdd from './views/AdminPages/MultipleAssessmentAdd.vue'
import MultipleAssessmentEdit from './views/AdminPages/MultipleAssessmentEdit.vue'
import RJ45AssessmentEdit from './views/AdminPages/RJ-45AssessmentEdit.vue'

const routes = [
    { path: '/', component: LoginPage },
    { path: '/register', component: RegisterPage },
    { path: '/forgot', component: ForgotPage },
    { path: '/reset/:token', component: ResetPage },

    {
        path: '/user',
        component: UserLayout,
        children: [
            { path: '', component: HomePage, meta: { requiresAuth: true, requiresStudent: true } },
            { path: 'records', component: Records, meta: { requiresAuth: true, requiresStudent: true } },
            { path: 'quizzes', component: AssessmentPage, meta: { requiresAuth: true, requiresStudent: true } },
            { path: 'quizzes/assessment/:id', component: ChooseAssessmentPage, meta: { requiresAuth: true, requiresStudent: true } },
            { path: 'profile', component: Profile, meta: { requiresAuth: true, requiresStudent: true } },
        ]
    },

    { path: '/quiz-result/:id', component: Result, meta: { requiresAuth: true, requiresStudent: true } },
    { path: '/quiz/:quiz_id', name: 'quiz-start', component: MultipleChoice, meta: { requiresAuth: true, requiresStudent: true } },
    { path: '/dragdrop/:id', component: DragDrop, meta: { requiresAuth: true, requiresStudent: true } },
    { path: '/image-identification/:id', component: ImageIdentification, meta: { requiresAuth: true, requiresStudent: true } },
    { path: '/rj45-hands-on/:id', component: RJ45HandsOn, meta: { requiresAuth: true, requiresStudent: true } },

    {
        path: '/admin',
        component: AdminLayout,
        meta: { requiresAuth: true, requiresAdmin: true },
        children: [
            { path: '', component: AdminDashboard },
            { path: 'users', component: UsersTable },
            { path: 'records', component: StudentRecords },
            { path: 'quizzes/create', component: MultipleAssessmentAdd },
            { path: 'quizzes/:id/edit', component: MultipleAssessmentEdit },

            { path: 'quizzes/:id/create', component: MultipleAssessmentAdd },
            { path: 'quizzes/:id/:type/edit', component: MultipleAssessmentEdit },

            { path: 'quizzes/:id/:type/edit', component: RJ45AssessmentEdit },

            { path: 'settings', component: Settings },
            { path: 'manage-quizzes', component: ManageQuestions }
        ]
    },

    {
        path: '/:pathMatch(.*)*',
        name: 'NotFound',
        component: () => import('./views/404.vue')
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach(async (to) => {
    const { user, fetchUser } = useUser()

    // Only fetch user when accessing protected routes
    if (to.meta.requiresAuth && !user.value) {
        await fetchUser()
    }

    // User is not logged in
    if (to.meta.requiresAuth && !user.value) {
        return '/'
    }

    // Non-admin accessing admin page
    if (to.meta.requiresAdmin && user.value.role !== 'admin') {
        return '/user'
    }

    // Admin accessing student page
    if (to.meta.requiresStudent && user.value.role === 'admin') {
        return '/admin'
    }

    // Allow navigation
    return true
})

export default router