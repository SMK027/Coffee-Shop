import { useEffect } from 'react';
import { Platform } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import * as NavigationBar from 'expo-navigation-bar';
import { ServerProvider } from './src/context/ServerContext';
import { AuthProvider } from './src/context/AuthContext';
import RootNavigator from './src/components/RootNavigator';

export default function App() {
  useEffect(() => {
    if (Platform.OS === 'android') {
      NavigationBar.setVisibilityAsync('hidden').catch(() => {});
      NavigationBar.setBehaviorAsync('overlay-swipe').catch(() => {});
    }
  }, []);

  return (
    <ServerProvider>
      <AuthProvider>
        <StatusBar hidden />
        <RootNavigator />
      </AuthProvider>
    </ServerProvider>
  );
}
