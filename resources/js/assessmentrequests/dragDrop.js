import axios from 'axios'

export const dragDropRequest = {
    fetch: (quizId) => axios.get(`/api/assessments/drag-drop/${quizId}`),
    submit: (payload) => axios.post('/api/assessments/drag-drop/answer', payload),
}
