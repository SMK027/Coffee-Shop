import axios from 'axios';
import * as SecureStore from 'expo-secure-store';
import { API_BASE_URL } from './config';

export const ACCESS_TOKEN_KEY = 'access_token';
export const REFRESH_TOKEN_KEY = 'refresh_token';

const api = axios.create({
  baseURL: `${API_BASE_URL}/api`,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  timeout: 15000,
});

// Injecte le token JWT dans chaque requête
api.interceptors.request.use(async (config) => {
  const token = await SecureStore.getItemAsync(ACCESS_TOKEN_KEY);
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Renouvelle le token automatiquement sur 401
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;
      try {
        const refreshToken = await SecureStore.getItemAsync(REFRESH_TOKEN_KEY);
        if (!refreshToken) throw new Error('Pas de token de renouvellement');

        // On utilise le refresh token comme token pour appeler /refresh
        const refreshResponse = await axios.post(
          `${API_BASE_URL}/api/auth/refresh`,
          {},
          {
            headers: {
              Authorization: `Bearer ${refreshToken}`,
              Accept: 'application/json',
            },
          }
        );

        const { access_token, refresh_token } = refreshResponse.data;
        await SecureStore.setItemAsync(ACCESS_TOKEN_KEY, access_token);
        if (refresh_token) {
          await SecureStore.setItemAsync(REFRESH_TOKEN_KEY, refresh_token);
        }

        originalRequest.headers.Authorization = `Bearer ${access_token}`;
        return api(originalRequest);
      } catch {
        // Le refresh a échoué — on laisse l'erreur 401 se propager
        // pour que le contexte Auth déconnecte l'utilisateur
        await SecureStore.deleteItemAsync(ACCESS_TOKEN_KEY);
        await SecureStore.deleteItemAsync(REFRESH_TOKEN_KEY);
      }
    }

    return Promise.reject(error);
  }
);

export default api;
