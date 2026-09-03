import React, { createContext, useContext, useEffect, useState } from 'react';
import * as SecureStore from 'expo-secure-store';
import api, { ACCESS_TOKEN_KEY, PERMANENT_SUPERVISION_TOKEN_KEY, REFRESH_TOKEN_KEY } from '../api/client';
import { useServer } from './ServerContext';
import { User } from '../types';

interface AuthContextValue {
  user: User | null;
  isLoading: boolean;
  isPermanentSupervisionEnabled: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  enablePermanentSupervision: (credentials: { token?: string; number?: string; pin?: string }) => Promise<void>;
  disablePermanentSupervision: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const { isReady } = useServer();
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isPermanentSupervisionEnabled, setIsPermanentSupervisionEnabled] = useState(false);

  // Restaure la session une fois le serveur initialisé
  useEffect(() => {
    if (!isReady) return;
    (async () => {
      try {
        const token = await SecureStore.getItemAsync(ACCESS_TOKEN_KEY);
        if (token) {
          const { data } = await api.get('/auth/me');
          setUser(data);
          if (data.global_role === 'superadmin') {
            try {
              const supervision = await api.get('/supervision/permanent');
              setIsPermanentSupervisionEnabled(Boolean(supervision.data?.enabled));
              if (!supervision.data?.enabled) {
                await SecureStore.deleteItemAsync(PERMANENT_SUPERVISION_TOKEN_KEY);
              }
            } catch {
              await SecureStore.deleteItemAsync(PERMANENT_SUPERVISION_TOKEN_KEY);
              setIsPermanentSupervisionEnabled(false);
            }
          }
        }
      } catch {
        await SecureStore.deleteItemAsync(ACCESS_TOKEN_KEY);
        await SecureStore.deleteItemAsync(REFRESH_TOKEN_KEY);
      } finally {
        setIsLoading(false);
      }
    })();
  }, [isReady]);

  const login = async (email: string, password: string) => {
    await SecureStore.deleteItemAsync(ACCESS_TOKEN_KEY);
    await SecureStore.deleteItemAsync(REFRESH_TOKEN_KEY);
    await SecureStore.deleteItemAsync(PERMANENT_SUPERVISION_TOKEN_KEY);

    const { data } = await api.post('/auth/login', { email, password });
    await SecureStore.setItemAsync(ACCESS_TOKEN_KEY, data.access_token);
    await SecureStore.setItemAsync(REFRESH_TOKEN_KEY, data.refresh_token ?? data.access_token);
    setUser(data.user);
    setIsPermanentSupervisionEnabled(false);
  };

  const logout = async () => {
    try {
      await api.post('/auth/logout');
    } catch { /* ignorer les erreurs réseau */ }
    await SecureStore.deleteItemAsync(ACCESS_TOKEN_KEY);
    await SecureStore.deleteItemAsync(REFRESH_TOKEN_KEY);
    await SecureStore.deleteItemAsync(PERMANENT_SUPERVISION_TOKEN_KEY);
    setUser(null);
    setIsPermanentSupervisionEnabled(false);
  };

  const enablePermanentSupervision = async (credentials: { token?: string; number?: string; pin?: string }) => {
    const payload = credentials.token
      ? { supervisor_token: credentials.token }
      : { supervisor_number: credentials.number, supervisor_pin: credentials.pin };
    const { data } = await api.post('/supervision/permanent', payload);

    await SecureStore.setItemAsync(PERMANENT_SUPERVISION_TOKEN_KEY, data.token);
    setIsPermanentSupervisionEnabled(true);
  };

  const disablePermanentSupervision = async () => {
    try {
      await api.delete('/supervision/permanent');
    } finally {
      await SecureStore.deleteItemAsync(PERMANENT_SUPERVISION_TOKEN_KEY);
      setIsPermanentSupervisionEnabled(false);
    }
  };

  return (
    <AuthContext.Provider value={{ user, isLoading, isPermanentSupervisionEnabled, login, logout, enablePermanentSupervision, disablePermanentSupervision }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth doit être utilisé dans AuthProvider');
  return ctx;
}
