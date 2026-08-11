import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';

export interface Server {
  id: string;
  label: string;
  url: string;
  readonly?: boolean; // true = non modifiable par l'utilisateur
}

export const PROD_SERVER: Server = {
  id: 'prod',
  label: 'Production',
  url: 'https://coffee.leofranz.fr',
  readonly: true,
};

export const DEV_SERVER: Server = {
  id: 'dev',
  label: 'Dev',
  url: 'https://dev-coffee.leofranz.fr',
  readonly: true,
};

const CUSTOM_SERVERS_KEY = 'custom_servers';
export const LAST_SERVER_KEY = 'last_server_id';

export async function loadCustomServers(): Promise<Server[]> {
  try {
    const raw = await AsyncStorage.getItem(CUSTOM_SERVERS_KEY);
    return raw ? (JSON.parse(raw) as Server[]) : [];
  } catch {
    return [];
  }
}

export async function saveCustomServers(servers: Server[]): Promise<void> {
  await AsyncStorage.setItem(CUSTOM_SERVERS_KEY, JSON.stringify(servers));
}

export async function loadAllServers(): Promise<Server[]> {
  const custom = await loadCustomServers();
  return [PROD_SERVER, DEV_SERVER, ...custom];
}

export async function loadLastServerId(): Promise<string | null> {
  return SecureStore.getItemAsync(LAST_SERVER_KEY);
}

export async function saveLastServerId(id: string): Promise<void> {
  return SecureStore.setItemAsync(LAST_SERVER_KEY, id);
}
