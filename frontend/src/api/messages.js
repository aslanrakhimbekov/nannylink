import { client } from './client';

export const messagesApi = {
  getMessages: (bookingId) => client.get(`/bookings/${bookingId}/messages`),
  sendMessage: (bookingId, content) => client.post(`/bookings/${bookingId}/messages`, { content }),
};
