import { client } from './client';

export const reviewsApi = {
  createReview: (payload) => client.post('/reviews', payload),
  getNannyReviews: (nannyId) => client.get(`/nannies/${nannyId}/reviews`),
};
