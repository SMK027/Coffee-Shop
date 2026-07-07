import React, { useCallback, useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  TouchableOpacity,
} from 'react-native';
import { useRoute, useNavigation, RouteProp } from '@react-navigation/native';
import api from '../api/client';
import { LoyaltyCardDetail, LoyaltyCardOrderSummary, LoyaltyPointAdjustment } from '../types';

type ParamList = { LoyaltyCardDetail: { cardId: number; fullName?: string } };

const formatDate = (iso: string) => {
  const d = new Date(iso);
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
    + ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};

const sourceLabel = (src: LoyaltyPointAdjustment['source']) => {
  switch (src) {
    case 'manual': return 'Ajustement manuel';
    case 'order_debit': return 'Utilisation sur commande';
    case 'order_credit': return 'Gain sur commande';
    default: return src;
  }
};

export default function LoyaltyCardDetailScreen() {
  const route = useRoute<RouteProp<ParamList, 'LoyaltyCardDetail'>>();
  const navigation = useNavigation<any>();
  const { cardId } = route.params;

  const [data, setData] = useState<LoyaltyCardDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [tab, setTab] = useState<'orders' | 'points'>('orders');

  const load = useCallback(async () => {
    const { data: json } = await api.get<LoyaltyCardDetail>(`/loyalty-cards/${cardId}`);
    setData(json);
  }, [cardId]);

  useEffect(() => {
    setLoading(true);
    load().finally(() => setLoading(false));
  }, [load]);

  useEffect(() => {
    if (data?.card) {
      navigation.setOptions({ title: data.card.full_name });
    }
  }, [data, navigation]);

  const onRefresh = async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  };

  if (loading || !data) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#92400e" />
      </View>
    );
  }

  const { card, orders, adjustments, totals } = data;

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={{ padding: 16, paddingBottom: 40 }}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      {/* Carte identité */}
      <View style={styles.headerCard}>
        <View style={styles.headerTop}>
          <View style={{ flex: 1 }}>
            <Text style={styles.fullName}>{card.full_name}</Text>
            <Text style={styles.cardNumber}>{card.card_number}</Text>
          </View>
          {card.has_employee_benefits && (
            <View style={styles.badgeEmployee}>
              <Text style={styles.badgeEmployeeText}>👤 Salarié</Text>
            </View>
          )}
        </View>
        {card.email && <Text style={styles.contact}>✉︎ {card.email}</Text>}
        {card.phone && <Text style={styles.contact}>☎ {card.phone}</Text>}
      </View>

      {/* Statistiques */}
      <View style={styles.statsRow}>
        <View style={styles.statCard}>
          <Text style={styles.statValue}>{card.points}</Text>
          <Text style={styles.statLabel}>Solde actuel</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={[styles.statValue, { color: '#16a34a' }]}>+{totals.points_credited}</Text>
          <Text style={styles.statLabel}>Cumulés</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={[styles.statValue, { color: '#dc2626' }]}>−{totals.points_debited}</Text>
          <Text style={styles.statLabel}>Dépensés</Text>
        </View>
      </View>

      <View style={styles.statCardWide}>
        <Text style={styles.statValue}>{totals.orders_count}</Text>
        <Text style={styles.statLabel}>Commande{totals.orders_count > 1 ? 's' : ''} au total</Text>
      </View>

      {/* Onglets */}
      <View style={styles.tabs}>
        <TouchableOpacity
          style={[styles.tab, tab === 'orders' && styles.tabActive]}
          onPress={() => setTab('orders')}
        >
          <Text style={[styles.tabText, tab === 'orders' && styles.tabTextActive]}>
            Commandes ({orders.length})
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.tab, tab === 'points' && styles.tabActive]}
          onPress={() => setTab('points')}
        >
          <Text style={[styles.tabText, tab === 'points' && styles.tabTextActive]}>
            Historique points ({adjustments.length})
          </Text>
        </TouchableOpacity>
      </View>

      {tab === 'orders' ? (
        orders.length === 0 ? (
          <Text style={styles.empty}>Aucune commande associée.</Text>
        ) : (
          orders.map((o) => <OrderRow key={o.id} order={o} navigation={navigation} />)
        )
      ) : (
        adjustments.length === 0 ? (
          <Text style={styles.empty}>Aucun mouvement de points.</Text>
        ) : (
          adjustments.map((a) => <AdjustmentRow key={a.id} adj={a} />)
        )
      )}
    </ScrollView>
  );
}

function OrderRow({ order, navigation }: { order: LoyaltyCardOrderSummary; navigation: any }) {
  const isCredit = order.points_awarded > 0;
  const isDebit = order.loyalty_points_spent > 0;

  return (
    <TouchableOpacity
      style={styles.rowCard}
      onPress={() => navigation.navigate('Orders', {
        screen: 'OrderDetail',
        params: { orderId: order.id },
      })}
    >
      <View style={styles.rowHeader}>
        <Text style={styles.orderId}>Commande #{order.id}</Text>
        <Text style={styles.orderAmount}>{order.total_amount.toFixed(2)} €</Text>
      </View>
      <Text style={styles.rowMeta}>
        {formatDate(order.created_at)} · {order.items_count} article{order.items_count > 1 ? 's' : ''}
      </Text>
      <View style={styles.rowFooter}>
        <View style={[styles.statusPill, styles[`status_${order.status}` as keyof typeof styles] as any]}>
          <Text style={styles.statusPillText}>{order.status_label}</Text>
        </View>
        {isCredit && <Text style={styles.pointsCredit}>+{order.points_awarded} pts</Text>}
        {isDebit && <Text style={styles.pointsDebit}>−{order.loyalty_points_spent} pts</Text>}
      </View>
    </TouchableOpacity>
  );
}

function AdjustmentRow({ adj }: { adj: LoyaltyPointAdjustment }) {
  const isCredit = adj.type === 'credit';
  return (
    <View style={styles.rowCard}>
      <View style={styles.rowHeader}>
        <Text style={styles.adjSource}>{sourceLabel(adj.source)}</Text>
        <Text style={[styles.adjPoints, isCredit ? styles.pointsCredit : styles.pointsDebit]}>
          {isCredit ? '+' : '−'}{adj.points} pts
        </Text>
      </View>
      <Text style={styles.rowMeta}>
        {formatDate(adj.created_at)}
        {adj.order_id ? ` · Commande #${adj.order_id}` : ''}
        {adj.user_name ? ` · par ${adj.user_name}` : ''}
      </Text>
      {adj.reason && <Text style={styles.reason}>« {adj.reason} »</Text>}
      <Text style={styles.balanceAfter}>Solde après : {adj.balance_after} pts</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#fdf8f3' },
  headerCard: { backgroundColor: '#fff', borderRadius: 12, padding: 16, marginBottom: 12, shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.06, shadowRadius: 4, elevation: 2 },
  headerTop: { flexDirection: 'row', alignItems: 'flex-start', marginBottom: 8 },
  fullName: { fontSize: 20, fontWeight: '700', color: '#1f2937' },
  cardNumber: { fontSize: 13, color: '#9ca3af', fontFamily: 'monospace', marginTop: 2 },
  badgeEmployee: { backgroundColor: '#fef3c7', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 12 },
  badgeEmployeeText: { color: '#92400e', fontSize: 12, fontWeight: '600' },
  contact: { fontSize: 14, color: '#6b7280', marginTop: 2 },

  statsRow: { flexDirection: 'row', gap: 8, marginBottom: 8 },
  statCard: { flex: 1, backgroundColor: '#fff', borderRadius: 12, padding: 12, alignItems: 'center', shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.06, shadowRadius: 4, elevation: 2 },
  statCardWide: { backgroundColor: '#fff', borderRadius: 12, padding: 12, alignItems: 'center', marginBottom: 16, shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.06, shadowRadius: 4, elevation: 2 },
  statValue: { fontSize: 22, fontWeight: '700', color: '#d97706' },
  statLabel: { fontSize: 12, color: '#6b7280', marginTop: 2, textAlign: 'center' },

  tabs: { flexDirection: 'row', backgroundColor: '#fff', borderRadius: 12, padding: 4, marginBottom: 12 },
  tab: { flex: 1, paddingVertical: 10, alignItems: 'center', borderRadius: 8 },
  tabActive: { backgroundColor: '#92400e' },
  tabText: { fontSize: 13, fontWeight: '600', color: '#6b7280' },
  tabTextActive: { color: '#fff' },

  rowCard: { backgroundColor: '#fff', borderRadius: 10, padding: 12, marginBottom: 8, shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.04, shadowRadius: 3, elevation: 1 },
  rowHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  orderId: { fontSize: 15, fontWeight: '700', color: '#1f2937' },
  orderAmount: { fontSize: 15, fontWeight: '700', color: '#92400e' },
  rowMeta: { fontSize: 12, color: '#9ca3af', marginTop: 2 },
  rowFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 8 },
  statusPill: { paddingHorizontal: 10, paddingVertical: 3, borderRadius: 10, backgroundColor: '#f3f4f6' },
  statusPillText: { fontSize: 11, fontWeight: '600', color: '#374151' },
  status_completed: { backgroundColor: '#dcfce7' },
  status_cancelled: { backgroundColor: '#fee2e2' },
  status_pending: { backgroundColor: '#fef3c7' },
  status_preparing: { backgroundColor: '#dbeafe' },
  status_serving: { backgroundColor: '#e0e7ff' },

  adjSource: { fontSize: 14, fontWeight: '600', color: '#1f2937' },
  adjPoints: { fontSize: 15, fontWeight: '700' },
  pointsCredit: { color: '#16a34a' },
  pointsDebit: { color: '#dc2626' },
  reason: { fontSize: 13, fontStyle: 'italic', color: '#6b7280', marginTop: 4 },
  balanceAfter: { fontSize: 12, color: '#9ca3af', marginTop: 4 },

  empty: { textAlign: 'center', color: '#9ca3af', marginTop: 24, fontSize: 15 },
});
