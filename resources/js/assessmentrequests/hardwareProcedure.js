import api from '@/bootstrap'

const BASE = '/api/hardware'

export const hardwareProcedureRequest = {
    show(quizId) {
        return api.get(`${BASE}/${quizId}`)
    },

    get(quizId) {
        return hardwareProcedureRequest.show(quizId)
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

    record(recordIdOrQuizId, maybeRecordId) {
        const recordId = maybeRecordId !== undefined ? maybeRecordId : recordIdOrQuizId
        return api.get(`/api/records/${recordId}/hardware`)
    },
}

export default hardwareProcedureRequest