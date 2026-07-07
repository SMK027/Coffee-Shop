import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuth } from '../context/AuthContext';
import LoginScreen from '../screens/LoginScreen';
import MenuScreen from '../screens/MenuScreen';
import OrdersScreen from '../screens/OrdersScreen';
import OrderDetailScreen from '../screens/OrderDetailScreen';
import CreateOrderScreen from '../screens/CreateOrderScreen';
import LoyaltyCardsScreen from '../screens/LoyaltyCardsScreen';
import LoyaltyCardDetailScreen from '../screens/LoyaltyCardDetailScreen';

const Tab = createBottomTabNavigator();
const OrderStack = createNativeStackNavigator();
const LoyaltyStack = createNativeStackNavigator();

function OrdersStack() {
  return (
    <OrderStack.Navigator
      screenOptions={{
        headerStyle: { backgroundColor: '#78350f' },
        headerTintColor: '#fff',
        headerTitleStyle: { fontWeight: '700' },
      }}
    >
      <OrderStack.Screen name="OrdersList" component={OrdersScreen} options={{ title: 'Commandes' }} />
      <OrderStack.Screen name="OrderDetail" component={OrderDetailScreen} options={{ title: 'Détail' }} />
      <OrderStack.Screen name="CreateOrder" component={CreateOrderScreen} options={{ title: 'Nouvelle commande' }} />
    </OrderStack.Navigator>
  );
}

function LoyaltyStackNavigator() {
  return (
    <LoyaltyStack.Navigator
      screenOptions={{
        headerStyle: { backgroundColor: '#78350f' },
        headerTintColor: '#fff',
        headerTitleStyle: { fontWeight: '700' },
      }}
    >
      <LoyaltyStack.Screen name="LoyaltyCardsList" component={LoyaltyCardsScreen} options={{ title: 'Fidélité' }} />
      <LoyaltyStack.Screen name="LoyaltyCardDetail" component={LoyaltyCardDetailScreen} options={{ title: 'Fiche fidélité' }} />
    </LoyaltyStack.Navigator>
  );
}

function LogoutButton({ onPress }: { onPress: () => void }) {
  return (
    <TouchableOpacity onPress={onPress} style={{ marginRight: 14 }}>
      <Text style={{ color: '#fff', fontSize: 14 }}>Déconnexion</Text>
    </TouchableOpacity>
  );
}

function AppTabs() {
  const { logout, user } = useAuth();

  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerStyle: { backgroundColor: '#78350f' },
        headerTintColor: '#fff',
        headerTitleStyle: { fontWeight: '700' },
        tabBarActiveTintColor: '#92400e',
        tabBarInactiveTintColor: '#9ca3af',
        tabBarStyle: { borderTopColor: '#e5e7eb' },
        tabBarLabelStyle: { fontSize: 12, fontWeight: '600' },
        headerRight: () => <LogoutButton onPress={logout} />,
        tabBarIcon: ({ focused, color }) => {
          const icons: Record<string, string> = {
            Orders: '📋',
            Menu: '☕',
            LoyaltyCards: '🎁',
          };
          return <Text style={{ fontSize: focused ? 22 : 18 }}>{icons[route.name] ?? '•'}</Text>;
        },
      })}
    >
      <Tab.Screen name="Orders" component={OrdersStack} options={{ title: 'Commandes', headerShown: false }} />
      <Tab.Screen name="Menu" component={MenuScreen} options={{ title: 'Menu' }} />
      <Tab.Screen name="LoyaltyCards" component={LoyaltyStackNavigator} options={{ title: 'Fidélité', headerShown: false }} />
    </Tab.Navigator>
  );
}

export default function RootNavigator() {
  const { user, isLoading } = useAuth();

  if (isLoading) {
    return (
      <View style={styles.splash}>
        <Text style={styles.splashText}>☕</Text>
      </View>
    );
  }

  return (
    <NavigationContainer>
      {user ? <AppTabs /> : <LoginScreen />}
    </NavigationContainer>
  );
}

const styles = StyleSheet.create({
  splash: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#fdf8f3' },
  splashText: { fontSize: 64 },
});
