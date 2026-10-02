import api from '../bootstrap'

export const imageIdentificationRequest = {

    fetch: (quizId) => {
        return api.get(
            `/api/assessments/image-identification/${quizId}`
        )
    },

    answer: (quizId, answers) => {
        return api.post(
            `/api/assessments/image-identification/answer`,
            {
                quiz_id: quizId,
                answers: answers,
            }
        )
    },
}