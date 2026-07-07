import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  View,
  Text,
  FlatList,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  Alert,
} from 'react-native';
import api from '../api/client';
import { LoyaltyCard } from '../types';

export default function LoyaltyCardsScreen() {
  const [cards, setCards] = useState<LoyaltyCard[]>([]);
  const [search, setSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loadingMore, setLoadingMore] = useState(false);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Debounce recherche
  useEffect(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => setDebouncedSearch(search), 400);
    return () => { if (debounceRef.current) clearTimeout(debounceRef.current); };
  }, [search]);

  const loadCards = useCallback(async (p = 1, replace = true) => {
    const params: Record<string, any> = { page: p };
    if (debouncedSearch.trim()) params.q = debouncedSearch.trim();
    const { data } = await api.get('/loyalty-cards', { params });
    if (replace) {
      setCards(data.data);
    } else {
      setCards((prev) => [...prev, ...data.data]);
    }
    setLastPage(data.last_page);
    setPage(p);
  }, [debouncedSearch]);

  useEffect(() => {
    setLoading(true);
    loadCards(1, true).finally(() => setLoading(false));
  }, [loadCards]);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadCards(1, true);
    setRefreshing(false);
  };

  const loadMore = async () => {
    if (loadingMore || page >= lastPage) return;
    setLoadingMore(true);
    await loadCards(page + 1, false);
    setLoadingMore(false);
  };

  return (
    <View style={styles.container}>
      {/* Barre de recherche */}
      <View style={styles.searchBar}>
        <TextInput
          style={styles.searchInput}
          placeholder="Rechercher par nom, e-mail, n° de carte…"
          placeholderTextColor="#9ca3af"
          value={search}
          onChangeText={setSearch}
          returnKeyType="search"
          clearButtonMode="while-editing"
        />
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color="#92400e" />
        </View>
      ) : (
        <FlatList
          data={cards}
          keyExtractor={(item) => String(item.id)}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
          onEndReached={loadMore}
          onEndReachedThreshold={0.3}
          ListEmptyComponent={
            <Text style={styles.empty}>Aucune carte trouvée.</Text>
          }
          ListFooterComponent={
            loadingMore ? <ActivityIndicator style={{ marginVertical: 16 }} color="#92400e" /> : null
          }
          renderItem={({ item: card }) => (
            <View style={styles.card}>
              <View style={styles.cardHeader}>
                <Text style={styles.fullName}>{card.full_name}</Text>
                <View style={[styles.badge, card.has_employee_benefits ? styles.badgeEmployee : styles.badgeClient]}>
                  <Text style={[styles.badgeText, card.has_employee_benefits ? styles.badgeEmployeeText : styles.badgeClientText]}>
                    {card.has_employee_benefits ? '👤 Salarié' : 'Client'}
                  </Text>
                </View>
              </View>
              <Text style={styles.cardNumber}>{card.card_number}</Text>
              {card.email && <Text style={styles.contact}>{card.email}</Text>}
              {card.phone && <Text style={styles.contact}>{card.phone}</Text>}
              <View style={styles.pointsRow}>
                <Text style={styles.points}>🏆 {card.points} point{card.points > 1 ? 's' : ''}</Text>
              </View>
            </View>
          )}
          contentContainerStyle={{ padding: 16, paddingBottom: 40 }}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  searchBar: {
    padding: 12,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  searchInput: {
    backgroundColor: '#f3f4f6',
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 10,
    fontSize: 15,
    color: '#111827',
  },
  empty: { textAlign: 'center', color: '#9ca3af', marginTop: 48, fontSize: 15 },
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    marginBottom: 10,
    padding: 14,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.06,
    shadowRadius: 4,
    elevation: 2,
  },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  fullName: { fontSize: 17, fontWeight: '700', color: '#1f2937', flex: 1 },
  badge: { paddingHorizontal: 10, paddingVertical: 3, borderRadius: 12, marginLeft: 8 },
  badgeEmployee: { backgroundColor: '#fef3c7' },
  badgeClient: { backgroundColor: '#f3f4f6' },
  badgeText: { fontSize: 12, fontWeight: '600' },
  badgeEmployeeText: { color: '#92400e' },
  badgeClientText: { color: '#6b7280' },
  cardNumber: { fontSize: 13, color: '#9ca3af', fontFamily: 'monospace', marginBottom: 4 },
  contact: { fontSize: 14, color: '#6b7280', marginTop: 2 },
  pointsRow: { marginTop: 8 },
  points: { fontSize: 15, fontWeight: '600', color: '#d97706' },
});
