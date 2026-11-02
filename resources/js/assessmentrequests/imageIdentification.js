import api from '../bootstrap'

export const imageIdentificationRequest = {
    fetch(quizId) {
        return api.get(`/api/image-identification/${quizId}`)
    },

    answer(quizId, attemptToken, answers, elapsedTime = 0) {
        return api.post('/api/image-identification/answer', {
            quiz_id: quizId,
            attempt_token: attemptToken,
            answers,
            elapsed_time: elapsedTime,
        })
    },


    // admin side fetches


}