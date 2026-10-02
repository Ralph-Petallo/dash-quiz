import api from '@/bootstrap'

const encodeId = (value) =>
    encodeURIComponent(String(value))

const baseUrl = (quizId) =>
    `/api/quizzes/${encodeId(quizId)}/hardware-procedure`

export const hardwareProcedureRequest = {
    async get(quizId) {
        const response = await api.get(
            baseUrl(quizId)
        )

        return response.data
    },

    async start(quizId) {
        const response = await api.post(
            `${baseUrl(quizId)}/attempts/start`
        )

        return response.data
    },

    async action(quizId, attemptId, payload) {
        const response = await api.post(
            `${baseUrl(quizId)}/attempts/${encodeId(attemptId)}/actions`,
            payload
        )

        return response.data
    },

    async complete(quizId, attemptId) {
        const response = await api.post(
            `${baseUrl(quizId)}/attempts/${encodeId(attemptId)}/complete`
        )

        return response.data
    },

    async record(recordId) {
        const response = await api.get(
            `/api/records/${encodeId(recordId)}/hardware-procedure`
        )

        return response.data
    },
}

export default hardwareProcedureRequest