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
  TextInput,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import api from '../api/client';
import { Order, OrderStatus } from '../types';

export default function OrdersScreen() {
  const navigation = useNavigation<any>();
  const [orders, setOrders] = useState<Order[]>([]);
  const [statuses, setStatuses] = useState<OrderStatus[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [activeFilter, setActiveFilter] = useState<'active' | 'all'>('active');
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loadingMore, setLoadingMore] = useState(false);

  const loadStatuses = useCallback(async () => {
    const { data } = await api.get('/orders/statuses');
    setStatuses(data.statuses);
  }, []);

  const loadOrders = useCallback(async (p = 1, replace = true) => {
    const params: Record<string, any> = { page: p };
    if (activeFilter === 'active') params.active = 1;
    const { data } = await api.get('/orders', { params });
    if (replace) {
      setOrders(data.data);
    } else {
      setOrders((prev) => [...prev, ...data.data]);
    }
    setLastPage(data.last_page);
    setPage(p);
  }, [activeFilter]);

  useEffect(() => {
    setLoading(true);
    Promise.all([loadOrders(1, true), loadStatuses()]).finally(() => setLoading(false));
  }, [loadOrders, loadStatuses]);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadOrders(1, true);
    setRefreshing(false);
  };

  const loadMore = async () => {
    if (loadingMore || page >= lastPage) return;
    setLoadingMore(true);
    await loadOrders(page + 1, false);
    setLoadingMore(false);
  };

  const statusColor = (key: string) => {
    const s = statuses.find((st) => st.key === key);
    return s?.color ?? '#6b7280';
  };

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#92400e" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Filtres actif / tout */}
      <View style={styles.filterRow}>
        {(['active', 'all'] as const).map((f) => (
          <TouchableOpacity
            key={f}
            style={[styles.filterBtn, activeFilter === f && styles.filterBtnActive]}
            onPress={() => setActiveFilter(f)}
          >
            <Text style={[styles.filterText, activeFilter === f && styles.filterTextActive]}>
              {f === 'active' ? 'En cours' : 'Toutes'}
            </Text>
          </TouchableOpacity>
        ))}
        <TouchableOpacity
          style={styles.newOrderBtn}
          onPress={() => navigation.navigate('CreateOrder')}
        >
          <Text style={styles.newOrderBtnText}>+ Nouvelle commande</Text>
        </TouchableOpacity>
      </View>

      <FlatList
        data={orders}
        keyExtractor={(item) => String(item.id)}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        onEndReached={loadMore}
        onEndReachedThreshold={0.3}
        ListEmptyComponent={
          <Text style={styles.empty}>Aucune commande à afficher.</Text>
        }
        ListFooterComponent={
          loadingMore ? <ActivityIndicator style={{ marginVertical: 16 }} color="#92400e" /> : null
        }
        renderItem={({ item: order }) => (
          <TouchableOpacity
            style={styles.card}
            onPress={() => navigation.navigate('OrderDetail', { orderId: order.id })}
          >
            <View style={styles.cardHeader}>
              <Text style={styles.orderId}>#{String(order.id).padStart(4, '0')}</Text>
              <View style={[styles.badge, { backgroundColor: statusColor(order.status) + '22' }]}>
                <Text style={[styles.badgeText, { color: statusColor(order.status) }]}>
                  {order.status_label}
                </Text>
              </View>
            </View>
            <Text style={styles.customerName}>{order.customer_name}</Text>
            <View style={styles.cardFooter}>
              <Text style={styles.amount}>{order.total_amount.toFixed(2)} €</Text>
              <Text style={styles.date}>
                {new Date(order.created_at).toLocaleDateString('fr-FR', {
                  day: '2-digit',
                  month: '2-digit',
                  hour: '2-digit',
                  minute: '2-digit',
                })}
              </Text>
            </View>
            {order.is_employee_order && (
              <Text style={styles.employeeTag}>👤 Commande salarié</Text>
            )}
          </TouchableOpacity>
        )}
        contentContainerStyle={{ padding: 16, paddingBottom: 40 }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  filterRow: {
    flexDirection: 'row',
    padding: 12,
    gap: 8,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
    alignItems: 'center',
  },
  filterBtn: {
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#d1d5db',
  },
  filterBtnActive: { backgroundColor: '#92400e', borderColor: '#92400e' },
  filterText: { fontSize: 13, color: '#374151', fontWeight: '500' },
  filterTextActive: { color: '#fff' },
  newOrderBtn: {
    marginLeft: 'auto',
    backgroundColor: '#d97706',
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 20,
  },
  newOrderBtnText: { color: '#fff', fontWeight: '700', fontSize: 13 },
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
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 4 },
  orderId: { fontSize: 14, fontWeight: '700', color: '#6b7280' },
  badge: { paddingHorizontal: 10, paddingVertical: 3, borderRadius: 12 },
  badgeText: { fontSize: 12, fontWeight: '600' },
  customerName: { fontSize: 16, fontWeight: '600', color: '#1f2937', marginBottom: 8 },
  cardFooter: { flexDirection: 'row', justifyContent: 'space-between' },
  amount: { fontSize: 16, fontWeight: '700', color: '#92400e' },
  date: { fontSize: 13, color: '#9ca3af' },
  employeeTag: { fontSize: 12, color: '#6b7280', marginTop: 6 },
});
