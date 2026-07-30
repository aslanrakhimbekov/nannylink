import { client } from './client';

export const searchApi = {
  getNearbyOrders: (latitude, longitude, radiusKm) => 
    client.get('/orders/nearby', { latitude, longitude, radius_km: radiusKm }),
};
