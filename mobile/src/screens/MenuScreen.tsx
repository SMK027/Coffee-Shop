import React, { useCallback, useEffect, useState } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  Alert,
  Image,
} from 'react-native';
import api from '../api/client';
import { Drink } from '../types';

export default function MenuScreen() {
  const [drinks, setDrinks] = useState<Drink[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [toggling, setToggling] = useState<number | null>(null);

  const load = useCallback(async () => {
    try {
      const { data } = await api.get('/drinks');
      setDrinks(data.drinks);
    } catch {
      Alert.alert('Erreur', 'Impossible de charger le menu.');
    }
  }, []);

  useEffect(() => {
    load().finally(() => setLoading(false));
  }, [load]);

  const onRefresh = async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  };

  const toggleAvailability = async (drink: Drink) => {
    setToggling(drink.id);
    try {
      const { data } = await api.patch(`/drinks/${drink.id}/availability`);
      setDrinks((prev) =>
        prev.map((d) => (d.id === drink.id ? { ...d, available: data.available } : d))
      );
    } catch {
      Alert.alert('Erreur', 'Impossible de modifier la disponibilité.');
    } finally {
      setToggling(null);
    }
  };

  // Regroupe les boissons par catégorie
  const grouped = drinks.reduce<{ category: string; data: Drink[] }[]>((acc, drink) => {
    const catName = drink.category?.name ?? 'Sans catégorie';
    const group = acc.find((g) => g.category === catName);
    if (group) {
      group.data.push(drink);
    } else {
      acc.push({ category: catName, data: [drink] });
    }
    return acc;
  }, []);

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#92400e" />
      </View>
    );
  }

  return (
    <FlatList
      style={styles.container}
      data={grouped}
      keyExtractor={(item) => item.category}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      renderItem={({ item: group }) => (
        <View>
          <Text style={styles.categoryHeader}>{group.category}</Text>
          {group.data.map((drink) => (
            <View key={drink.id} style={[styles.card, !drink.available && styles.cardDisabled]}>
              <View style={styles.cardContent}>
                {drink.image_url ? (
                  <Image source={{ uri: drink.image_url }} style={styles.image} />
                ) : (
                  <View style={styles.imagePlaceholder}>
                    <Text style={styles.imagePlaceholderText}>☕</Text>
                  </View>
                )}
                <View style={styles.info}>
                  <Text style={styles.drinkName}>{drink.name}</Text>
                  {drink.description ? (
                    <Text style={styles.description} numberOfLines={2}>
                      {drink.description}
                    </Text>
                  ) : null}
                  <Text style={styles.price}>{drink.price.toFixed(2)} €</Text>
                  {drink.loyalty_points > 0 && (
                    <Text style={styles.points}>+{drink.loyalty_points} pts fidélité</Text>
                  )}
                </View>
              </View>
              <TouchableOpacity
                style={[styles.toggleBtn, drink.available ? styles.toggleBtnActive : styles.toggleBtnInactive]}
                onPress={() => toggleAvailability(drink)}
                disabled={toggling === drink.id}
              >
                {toggling === drink.id ? (
                  <ActivityIndicator size="small" color="#fff" />
                ) : (
                  <Text style={styles.toggleBtnText}>
                    {drink.available ? 'Désactiver' : 'Activer'}
                  </Text>
                )}
              </TouchableOpacity>
            </View>
          ))}
        </View>
      )}
      contentContainerStyle={{ padding: 16, paddingBottom: 40 }}
    />
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  categoryHeader: {
    fontSize: 13,
    fontWeight: '700',
    color: '#92400e',
    textTransform: 'uppercase',
    letterSpacing: 1,
    marginTop: 16,
    marginBottom: 8,
  },
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    marginBottom: 10,
    padding: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.06,
    shadowRadius: 4,
    elevation: 2,
  },
  cardDisabled: { opacity: 0.55 },
  cardContent: { flexDirection: 'row', marginBottom: 10 },
  image: { width: 64, height: 64, borderRadius: 8, marginRight: 12 },
  imagePlaceholder: {
    width: 64,
    height: 64,
    borderRadius: 8,
    backgroundColor: '#fef3c7',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  imagePlaceholderText: { fontSize: 28 },
  info: { flex: 1 },
  drinkName: { fontSize: 16, fontWeight: '600', color: '#1f2937', marginBottom: 2 },
  description: { fontSize: 13, color: '#6b7280', marginBottom: 4 },
  price: { fontSize: 15, fontWeight: '700', color: '#92400e' },
  points: { fontSize: 12, color: '#d97706', marginTop: 2 },
  toggleBtn: {
    borderRadius: 8,
    paddingVertical: 8,
    alignItems: 'center',
  },
  toggleBtnActive: { backgroundColor: '#ef4444' },
  toggleBtnInactive: { backgroundColor: '#22c55e' },
  toggleBtnText: { color: '#fff', fontWeight: '600', fontSize: 14 },
});
