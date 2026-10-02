import api from '../bootstrap'

export const rj45HandsOnRequest = {
    /**
     * Load RJ-45 assessment data
     *
     * GET:
     * /api/quizzes/{quiz}/rj45
     */
    async get(quizId) {
        const { data } = await api.get(
            `/api/quizzes/${encodeURIComponent(quizId)}/rj45`
        )

        return data
    },

    /**
     * Test the current RJ-45 wire arrangement.
     *
     * This DOES NOT save a quiz record yet.
     * It only checks the wiring against the database pinout.
     *
     * POST:
     * /api/quizzes/{quiz}/rj45/test
     *
     * Payload example:
     * {
     *     standard: 'B',
     *     wirings: [
     *         'wo',
     *         'o',
     *         'wg',
     *         'b',
     *         'wb',
     *         'g',
     *         'wbr',
     *         'br'
     *     ]
     * }
     */
    async test(quizId, payload) {
        await api.get('/sanctum/csrf-cookie')

        const { data } = await api.post(
            `/api/quizzes/${encodeURIComponent(quizId)}/rj45/test`,
            payload
        )

        return data
    },

    /**
     * Submit and save an RJ-45 attempt.
     *
     * IMPORTANT:
     * The attempt can be submitted even when it is NOT 100% correct.
     *
     * Laravel will:
     * - calculate the score again
     * - determine passed / failed
     * - create quiz_records
     * - create rj45_attempts
     * - create rj45_attempt_pins
     *
     * POST:
     * /api/quizzes/{quiz}/rj45/attempts
     *
     * Payload example:
     * {
     *     standard: 'B',
     *     wirings: [...],
     *     started_at: '2026-10-01T10:00:00.000Z',
     *     duration_ms: 45000
     * }
     */
    async submit(quizId, payload) {
        await api.get('/sanctum/csrf-cookie')

        const { data } = await api.post(
            `/api/quizzes/${encodeURIComponent(quizId)}/rj45/attempts`,
            payload
        )

        return data
    },
}