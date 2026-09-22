import axios from 'axios'

/**
 * Image Identification Assessment API
 * 
 * Handles fetching assessment questions and submitting batch answers
 */

export const imageIdentificationRequest = {
    /**
     * Fetch assessment questions
     * 
     * @param {number} quizId - The assessment/quiz ID
     * @returns {Promise} - Axios promise with questions data
     * 
     * Response:
     * {
     *   status: 'success',
     *   questions: [
     *     {
     *       id: 1,
     *       question_text: 'Identify this component',
     *       image_path: 'path/to/image.jpg',
     *       options: [
     *         { id: 1, description: 'Option A' },
     *         { id: 2, description: 'Option B' }
     *       ]
     *     }
     *   ]
     * }
     */
    fetch: (quizId) => axios.get(`/api/assessments/image-identification/${quizId}`),

    /**
     * Submit batch answers for assessment
     * 
     * @param {Array} answers - Array of answer objects
     * @returns {Promise} - Axios promise with grading results
     * 
     * Request format:
     * [
     *   { question_id: 1, answer_id: 2 },
     *   { question_id: 2, answer_id: 5 },
     *   { question_id: 3, answer_id: 8 }
     * ]
     * 
     * Response:
     * {
     *   status: 'success',
     *   total: 3,
     *   correct: 2,
     *   incorrect: 1,
     *   results: [
     *     { question_id: 1, answer_id: 2, correct: true },
     *     { question_id: 2, answer_id: 5, correct: false },
     *     { question_id: 3, answer_id: 8, correct: true }
     *   ]
     * }
     */
    answer: (answers) => axios.post(`/api/assessments/image-identification/answer`, {
        answers: answers,
    })
}