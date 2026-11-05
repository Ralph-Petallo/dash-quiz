// src/assessmentrequests/hardwareProcedure.js
import api from '@/bootstrap'

const BASE = '/api/hardware'
const ADMIN = '/api/admin/assessment/hardware-procedure'

export const hardwareProcedureRequest = {
    /* =========================================================
       USER (LEARNER) REQUESTS
    ========================================================= */

    get(quizId) {
        return api.get(`${BASE}/${quizId}`)
    },

    start(quizId) {
        return api.post(`${BASE}/${quizId}/start`)
    },

    action(quizId, attemptId, payload) {
        return api.post(`${BASE}/${quizId}/${attemptId}/actions`, payload)
    },

    complete(quizId, attemptId) {
        return api.post(`${BASE}/${quizId}/${attemptId}/complete`)
    },

    record(recordId) {
        return api.get(`/api/records/${recordId}/hardware`)
    },

    /* =========================================================
       ADMIN REQUESTS
    ========================================================= */

    adminStore(cocId) {
        return api.post(`${ADMIN}/create/${cocId}`)
    },

    adminEdit(assessmentId) {
        return api.get(`${ADMIN}/edit/${assessmentId}`)
    },

    adminUpdate(assessmentId, payload) {
        return api.put(`${ADMIN}/update/${assessmentId}`, payload)
    },
}