import React, { createContext, useContext, useEffect, useState } from 'react';
import { PROD_SERVER, Server, loadAllServers, loadCustomServers, loadLastServerId, saveCustomServers, saveLastServerId } from '../api/servers';
import api from '../api/client';

interface ServerContextValue {
  server: Server;
  servers: Server[];
  isReady: boolean;
  setServer: (server: Server) => Promise<void>;
  addServer: (label: string, url: string) => Promise<void>;
  updateServer: (id: string, label: string, url: string) => Promise<void>;
  deleteServer: (id: string) => Promise<void>;
}

const ServerContext = createContext<ServerContextValue | null>(null);

export function ServerProvider({ children }: { children: React.ReactNode }) {
  const [servers, setServers] = useState<Server[]>([PROD_SERVER]);
  const [server, setServerState] = useState<Server>(PROD_SERVER);
  const [isReady, setIsReady] = useState(false);

  useEffect(() => {
    (async () => {
      const all = await loadAllServers();
      const lastId = await loadLastServerId();
      const found = lastId ? all.find((s) => s.id === lastId) : null;
      const initial = found ?? PROD_SERVER;
      setServers(all);
      setServerState(initial);
      api.defaults.baseURL = `${initial.url}/api`;
      setIsReady(true);
    })();
  }, []);

  const setServer = async (s: Server) => {
    setServerState(s);
    api.defaults.baseURL = `${s.url}/api`;
    await saveLastServerId(s.id);
  };

  const addServer = async (label: string, url: string) => {
    const custom = await loadCustomServers();
    const newServer: Server = { id: `custom_${Date.now()}`, label: label.trim(), url: url.trim() };
    const updated = [...custom, newServer];
    await saveCustomServers(updated);
    setServers([PROD_SERVER, ...updated]);
  };

  const updateServer = async (id: string, label: string, url: string) => {
    const custom = await loadCustomServers();
    const updated = custom.map((s) => s.id === id ? { ...s, label: label.trim(), url: url.trim() } : s);
    await saveCustomServers(updated);
    setServers([PROD_SERVER, ...updated]);
    // Met à jour le serveur actif si c'est celui qui est modifié
    if (server.id === id) {
      const updatedServer = { ...server, label: label.trim(), url: url.trim() };
      setServerState(updatedServer);
      api.defaults.baseURL = `${updatedServer.url}/api`;
    }
  };

  const deleteServer = async (id: string) => {
    const custom = await loadCustomServers();
    const updated = custom.filter((s) => s.id !== id);
    await saveCustomServers(updated);
    setServers([PROD_SERVER, ...updated]);
    // Bascule sur prod si le serveur supprimé était actif
    if (server.id === id) {
      setServerState(PROD_SERVER);
      api.defaults.baseURL = `${PROD_SERVER.url}/api`;
      await saveLastServerId(PROD_SERVER.id);
    }
  };

  return (
    <ServerContext.Provider value={{ server, servers, isReady, setServer, addServer, updateServer, deleteServer }}>
      {children}
    </ServerContext.Provider>
  );
}

export function useServer() {
  const ctx = useContext(ServerContext);
  if (!ctx) throw new Error('useServer doit être utilisé dans ServerProvider');
  return ctx;
}
