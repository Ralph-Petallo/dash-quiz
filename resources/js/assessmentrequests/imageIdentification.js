import axios from 'axios'

export const imageIdentificationRequest = {
    fetch: (quizId) => axios.get(`/api/assessments/image-identification/${quizId}`),
}
