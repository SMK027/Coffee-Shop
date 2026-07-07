import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import api from '../api/client';
import { Order, OrderStatus } from '../types';

export default function OrderDetailScreen() {
  const route = useRoute<any>();
  const navigation = useNavigation<any>();
  const { orderId } = route.params;

  const [order, setOrder] = useState<Order | null>(null);
  const [statuses, setStatuses] = useState<OrderStatus[]>([]);
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);

  useEffect(() => {
    Promise.all([
      api.get(`/orders/${orderId}`).then(({ data }) => setOrder(data.order)),
      api.get('/orders/statuses').then(({ data }) => setStatuses(data.statuses)),
    ]).finally(() => setLoading(false));
  }, [orderId]);

  const updateStatus = async (key: string) => {
    setUpdating(true);
    try {
      const { data } = await api.patch(`/orders/${orderId}/status`, { status: key });
      setOrder(data.order);
    } catch {
      Alert.alert('Erreur', 'Impossible de mettre à jour le statut.');
    } finally {
      setUpdating(false);
    }
  };

  if (loading || !order) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#92400e" />
      </View>
    );
  }

  const currentStatus = statuses.find((s) => s.key === order.status);
  const availableTransitions = currentStatus?.is_terminal
    ? []
    : statuses.filter((s) => s.is_active && s.key !== order.status);

  const subtotal =
    (order.total_amount ?? 0) +
    (order.discount_amount ?? 0) +
    (order.loyalty_discount_amount ?? 0);

  return (
    <ScrollView style={styles.container} contentContainerStyle={{ padding: 16, paddingBottom: 48 }}>
      {/* En-tête */}
      <View style={styles.header}>
        <Text style={styles.orderId}>Commande #{String(order.id).padStart(4, '0')}</Text>
        <View style={styles.badge}>
          <Text style={styles.badgeText}>{order.status_label}</Text>
        </View>
      </View>

      <Text style={styles.customerName}>{order.customer_name}</Text>
      {order.is_employee_order && <Text style={styles.tag}>👤 Commande salarié (-15%)</Text>}
      {order.loyalty_card && (
        <Text style={styles.tag}>
          🎁 Carte {order.loyalty_card.card_number} — {order.loyalty_card.points} pts
        </Text>
      )}
      <Text style={styles.meta}>
        {new Date(order.created_at).toLocaleDateString('fr-FR', {
          weekday: 'long',
          day: '2-digit',
          month: 'long',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        })}
      </Text>
      {order.handled_by && <Text style={styles.meta}>Pris en charge par {order.handled_by}</Text>}

      {/* Articles */}
      <Text style={styles.sectionTitle}>Articles</Text>
      <View style={styles.card}>
        {order.items?.map((item) => (
          <View key={item.id} style={styles.itemRow}>
            <Text style={styles.itemName}>
              {item.quantity}× {item.drink_name}
              {item.custom_label ? ` (${item.custom_label})` : ''}
            </Text>
            <Text style={styles.itemPrice}>{item.subtotal.toFixed(2)} €</Text>
          </View>
        ))}
      </View>

      {/* Récapitulatif financier */}
      <View style={styles.card}>
        <View style={styles.summaryRow}>
          <Text style={styles.summaryLabel}>Sous-total</Text>
          <Text style={styles.summaryValue}>{subtotal.toFixed(2)} €</Text>
        </View>
        {order.loyalty_discount_amount > 0 && (
          <View style={styles.summaryRow}>
            <Text style={styles.summaryLabel}>Réductions fidélité</Text>
            <Text style={styles.summaryDiscount}>−{order.loyalty_discount_amount.toFixed(2)} €</Text>
          </View>
        )}
        {order.discount_amount > 0 && (
          <View style={styles.summaryRow}>
            <Text style={styles.summaryLabel}>Réduction salarié (15%)</Text>
            <Text style={styles.summaryDiscount}>−{order.discount_amount.toFixed(2)} €</Text>
          </View>
        )}
        <View style={[styles.summaryRow, styles.totalRow]}>
          <Text style={styles.totalLabel}>Total</Text>
          <Text style={styles.totalValue}>{order.total_amount.toFixed(2)} €</Text>
        </View>
      </View>

      {/* Notes */}
      {order.notes && (
        <>
          <Text style={styles.sectionTitle}>Notes</Text>
          <View style={styles.card}>
            <Text style={styles.notes}>{order.notes}</Text>
          </View>
        </>
      )}

      {/* Transitions de statut */}
      {availableTransitions.length > 0 && (
        <>
          <Text style={styles.sectionTitle}>Changer le statut</Text>
          <View style={styles.transitionsRow}>
            {availableTransitions.map((s) => (
              <TouchableOpacity
                key={s.key}
                style={styles.transitionBtn}
                onPress={() => updateStatus(s.key)}
                disabled={updating}
              >
                {updating ? (
                  <ActivityIndicator size="small" color="#fff" />
                ) : (
                  <Text style={styles.transitionBtnText}>{s.label}</Text>
                )}
              </TouchableOpacity>
            ))}
          </View>
        </>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  orderId: { fontSize: 20, fontWeight: '700', color: '#1f2937' },
  badge: { backgroundColor: '#fef3c7', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 12 },
  badgeText: { fontSize: 13, fontWeight: '600', color: '#92400e' },
  customerName: { fontSize: 22, fontWeight: '700', color: '#111827', marginBottom: 4 },
  tag: { fontSize: 13, color: '#6b7280', marginBottom: 2 },
  meta: { fontSize: 13, color: '#9ca3af', marginTop: 4 },
  sectionTitle: { fontSize: 13, fontWeight: '700', color: '#92400e', textTransform: 'uppercase', letterSpacing: 1, marginTop: 20, marginBottom: 8 },
  card: { backgroundColor: '#fff', borderRadius: 12, padding: 14, marginBottom: 8, shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.06, shadowRadius: 4, elevation: 2 },
  itemRow: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 5, borderBottomWidth: 1, borderBottomColor: '#f3f4f6' },
  itemName: { fontSize: 15, color: '#374151', flex: 1 },
  itemPrice: { fontSize: 15, fontWeight: '600', color: '#1f2937' },
  summaryRow: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 5 },
  summaryLabel: { fontSize: 14, color: '#6b7280' },
  summaryValue: { fontSize: 14, color: '#374151' },
  summaryDiscount: { fontSize: 14, color: '#22c55e', fontWeight: '600' },
  totalRow: { borderTopWidth: 1, borderTopColor: '#e5e7eb', marginTop: 4, paddingTop: 8 },
  totalLabel: { fontSize: 16, fontWeight: '700', color: '#1f2937' },
  totalValue: { fontSize: 18, fontWeight: '700', color: '#92400e' },
  notes: { fontSize: 14, color: '#374151', fontStyle: 'italic' },
  transitionsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  transitionBtn: { backgroundColor: '#92400e', paddingHorizontal: 16, paddingVertical: 10, borderRadius: 8 },
  transitionBtnText: { color: '#fff', fontWeight: '600', fontSize: 14 },
});
