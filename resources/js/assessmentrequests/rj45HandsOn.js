import axios from 'axios'
/**
 * @param {number} quizId
 * @param {string} difficulty
 * @returns {Promise<Object>}
 */
export const rj45HandsOnRequest = {
    fetch: (quizId) => axios.get(`/api/assessments/rj45/${quizId}`),
    complete: (payload) => axios.post('/api/assessments/rj45/complete', payload),
}
