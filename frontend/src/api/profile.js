import { client } from './client';

export const profileApi = {
  updateProfile: (data) => client.put('/profile', data),
};
