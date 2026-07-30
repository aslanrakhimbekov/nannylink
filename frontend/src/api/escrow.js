import { client } from './client';

export const escrowApi = {
  payBooking: (bookingId, paymentMethod = 'kaspi_qr_mock') => 
    client.post(`/bookings/${bookingId}/pay`, { payment_method: paymentMethod }),
  completeBooking: (bookingId) => 
    client.post(`/bookings/${bookingId}/complete`),
};
