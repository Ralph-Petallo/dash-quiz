import axios from 'axios'

export const multipleChoiceRequest = {
    fetch: (quizId) => axios.get(`/api/assessments/multiple-choice/${quizId}`),
    submitAnswer: (payload) => axios.post('/api/assessments/multiple-choice/answer', payload),
    submitResult: (payload) => axios.post('/api/assessments/multiple-choice/result', payload),
}
