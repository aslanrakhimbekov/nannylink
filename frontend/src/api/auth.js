import { client } from './client';

export const authApi = {
  requestOtp: (phone) => client.post('/auth/request-otp', { phone }),
  verifyOtp: (phone, code) => client.post('/auth/verify-otp', { phone, code }),
  telegramCallback: (data) => client.post('/auth/telegram-callback', data),
  telegramLink: (data) => client.post('/auth/telegram-link', data),
};
