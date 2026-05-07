import { createRouter, createWebHistory } from 'vue-router'
import { useUser } from '@/composables/useUser'

// User Pages
import HomePage from './views/UserPages/HomePage.vue'
import Profile from './views/UserPages/ProfilePage.vue'
import Records from './views/UserPages/RecordsPage.vue'
import QuizPage from './views/UserPages/QuizPage.vue'
import TakeQuizPage from './views/UserPages/TakeQuizPage.vue'
import QuizResult from './views/UserPages/QuizResult.vue'
import UserLayout from './views/UserPages/UserLayout.vue'

// Auth Pages
import LoginPage from './views/LoginPage.vue'
import RegisterPage from './views/RegisterPage.vue'
import ForgotPage from './views/ForgotPage.vue'
import ResetPage from './views/ResetPage.vue'

// Admin Pages
import AdminDashboard from './views/AdminPages/AdminDashboard.vue'
import UsersTable from './views/AdminPages/UsersTable.vue'
import StudentRecords from './views/AdminPages/StudentRecords.vue'
import QuizAdd from './views/AdminPages/QuizAdd.vue'
import QuizEdit from './views/AdminPages/QuizEdit.vue'
import ManageQuestions from './views/AdminPages/ManageQuiz.vue'
import AdminLayout from './views/AdminPages/AdminLayout.vue'

const routes = [
    { path: '/', component: LoginPage },
    { path: '/register', component: RegisterPage },
    { path: '/forgot', component: ForgotPage },
    { path: '/reset', component: ResetPage },

    {
        path: '/user',
        component: UserLayout,
        children: [
            { path: '', component: HomePage, meta: { requiresAuth: true, requiresStudent: true } },
            { path: 'records', component: Records, meta: { requiresAuth: true, requiresStudent: true } },
            { path: 'quizzes', component: QuizPage, meta: { requiresAuth: true, requiresStudent: true } },
            { path: 'profile', component: Profile, meta: { requiresAuth: true, requiresStudent: true } },
        ]
    },

    { path: '/quiz-result/:id', component: QuizResult, meta: { requiresAuth: true, requiresStudent: true } },
    { path: '/quiz/:quiz_id', name: 'quiz-start', component: TakeQuizPage, meta: { requiresAuth: true, requiresStudent: true } },

    {
        path: '/admin',
        component: AdminLayout,
        meta: { requiresAuth: true, requiresAdmin: true },
        children: [
            { path: '', component: AdminDashboard },
            { path: 'users', component: UsersTable },
            { path: 'records', component: StudentRecords },
            { path: 'quizzes/create', component: QuizAdd },
            { path: 'quizzes/:id/edit', component: QuizEdit },
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

router.beforeEach(async (to, from, next) => {
    const { user, fetchUser } = useUser()

    // Only fetch when needed — protected routes and guest redirect check
    const needsUserCheck = to.meta.requiresAuth || ['/', '/register', '/forgot', '/reset'].includes(to.path)

    if (needsUserCheck && !user.value) {
        await fetchUser()
    }

    // Protected routes — must be authenticated
    if (to.meta.requiresAuth) {
        if (!user.value) return next('/')
        if (to.meta.requiresAdmin && user.value.role !== 'admin') return next('/user')
        if (to.meta.requiresStudent && user.value.role === 'admin') return next('/admin')
    }

    // Redirect already-authenticated users away from guest pages
    if (['/', '/register', '/forgot', '/reset'].includes(to.path) && user.value) {
        return next(user.value.role === 'admin' ? '/admin' : '/user')
    }

    next()
})

export default router