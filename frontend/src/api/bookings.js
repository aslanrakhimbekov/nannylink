import { client } from './client';

export const bookingsApi = {
  getBookings: () => client.get('/bookings'),
  createBooking: (payload) => client.post('/bookings', payload),
  getBooking: (id) => client.get(`/bookings/${id}`),
  confirmBooking: (id) => client.post(`/bookings/${id}/confirm`),
  rejectBooking: (id) => client.post(`/bookings/${id}/reject`),
  cancelBooking: (id, cancellation_comment) => client.post(`/bookings/${id}/cancel`, { cancellation_comment }),
  getNearbyNannies: (latitude, longitude, radiusKm, filters = {}) => 
    client.get('/nannies/nearby', { latitude, longitude, radius_km: radiusKm, ...filters }),
  depositCoins: (amount) => client.post('/nanny/balance/deposit', { amount }),
};
