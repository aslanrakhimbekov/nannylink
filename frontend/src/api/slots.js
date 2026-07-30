import { client } from './client';

export const slotsApi = {
  getNannySlots: () => client.get('/nanny/slots'),
  createNannySlot: (start_time, end_time) => client.post('/nanny/slots', { start_time, end_time }),
  deleteNannySlot: (id) => client.delete(`/nanny/slots/${id}`),
  getNannyPublicSlots: (nannyId) => client.get(`/nannies/${nannyId}/slots`),
};
