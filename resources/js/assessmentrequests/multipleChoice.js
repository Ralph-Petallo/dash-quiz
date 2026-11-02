
import api from '@/bootstrap'

export const multipleChoiceRequest = {
    fetch: (quizId) =>
        api.get(`/api/multiple-choice/${quizId}`),

    submitAnswer: (payload) =>
        api.post('/api/multiple-choice/answer', payload),

    fetchResult: (recordId) =>
        api.get(`/api/multiple-choice/result/${recordId}`),
}