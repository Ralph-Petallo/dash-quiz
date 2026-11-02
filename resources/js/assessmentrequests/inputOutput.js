import api from '@/bootstrap'

const BASE = '/api/input-output'
const ADMIN = '/api/admin/assessment/input-output'

export const inputOutputRequest = {
    // Learner
    fetch() {
        return api.get(BASE)
    },

    submit(payload) {
        return api.post(`${BASE}/answer`, payload)
    },

    // FOR ADMIN SIDE REQUESTS FOR ASSESSMENT
    adminEdit(assessmentId) {
        return api.get(`${ADMIN}/edit/${assessmentId}`)
    },

    adminUpdate(assessmentId, payload) {
        return api.put(`${ADMIN}/update/${assessmentId}`, payload)
    },
}