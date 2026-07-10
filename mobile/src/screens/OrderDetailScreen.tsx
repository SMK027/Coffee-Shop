import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  Alert,
  Modal,
  TextInput,
} from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import api from '../api/client';
import { useAuth } from '../context/AuthContext';
import { Order, OrderStatus } from '../types';
import { PaymentMethod } from '../types';

export default function OrderDetailScreen() {
  const route = useRoute<any>();
  const navigation = useNavigation<any>();
  const { orderId } = route.params;
  const { user } = useAuth();
  const isSuperAdmin = user?.global_role === 'superadmin';
  const isAdmin = user?.global_role === 'admin' || isSuperAdmin;

  const [order, setOrder] = useState<Order | null>(null);
  const [statuses, setStatuses] = useState<OrderStatus[]>([]);
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);
  const [supervisorModalVisible, setSupervisorModalVisible] = useState(false);
  const [pendingStatus, setPendingStatus] = useState<string | null>(null);
  const [supervisorNumber, setSupervisorNumber] = useState('');
  const [supervisorPin, setSupervisorPin] = useState('');
  const [supervisorError, setSupervisorError] = useState<string | null>(null);
  const [refundModalVisible, setRefundModalVisible] = useState(false);
  const [refundMode, setRefundMode] = useState<'partial' | 'total'>('partial');
  const [refundSelection, setRefundSelection] = useState<Record<number, number>>({});
  const [refundError, setRefundError] = useState<string | null>(null);
  const [refundSupervisorNumber, setRefundSupervisorNumber] = useState('');
  const [refundSupervisorPin, setRefundSupervisorPin] = useState('');
  const [refundPaymentMethodId, setRefundPaymentMethodId] = useState<string>('');
  const [refundReason, setRefundReason] = useState('');
  const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([]);

  useEffect(() => {
    Promise.all([
      api.get(`/orders/${orderId}`).then(({ data }) => setOrder(data.order)),
      api.get('/orders/statuses').then(({ data }) => setStatuses(data.statuses)),
      api.get('/payment-methods').then(({ data }) => setPaymentMethods(data.payment_methods)),
    ]).finally(() => setLoading(false));
  }, [orderId]);

  const currentStatus = statuses.find((s) => s.key === order?.status);
  const requiresSupervisor = currentStatus?.is_terminal && !isSuperAdmin;

  const updateStatus = async (key: string, supervisor?: { number: string; pin: string }) => {
    setUpdating(true);
    try {
      const payload: Record<string, any> = { status: key };
      if (supervisor) {
        payload.supervisor_number = supervisor.number;
        payload.supervisor_pin = supervisor.pin;
      }

      const { data } = await api.patch(`/orders/${orderId}/status`, payload);
      setOrder(data.order);
      if (supervisorModalVisible) {
        setSupervisorModalVisible(false);
        setPendingStatus(null);
        setSupervisorNumber('');
        setSupervisorPin('');
        setSupervisorError(null);
      }
    } catch (error: any) {
      const message = error?.response?.data?.message || 'Impossible de mettre à jour le statut.';
      if (supervisorModalVisible) {
        setSupervisorError(message);
      } else {
        Alert.alert('Erreur', message);
      }
    } finally {
      setUpdating(false);
    }
  };

  const handleStatusPress = (key: string) => {
    if (requiresSupervisor) {
      setPendingStatus(key);
      setSupervisorModalVisible(true);
      setSupervisorError(null);
      return;
    }

    updateStatus(key);
  };

  const orderItems = order?.items ?? [];
  const refundableOriginalItems = orderItems.filter((item) => !item.is_refund);
  const refundableItems = refundableOriginalItems.map((original) => {
    const alreadyRefundedQty = orderItems
      .filter((item) => item.is_refund && item.refund_item_id === original.id)
      .reduce((sum, item) => sum + Math.abs(item.quantity), 0);

    return {
      ...original,
      refundable_qty: original.quantity - alreadyRefundedQty,
    };
  }).filter((item) => item.refundable_qty > 0);

  const totalRefundableAmount = Math.max(0, (order?.total_amount ?? 0) - (order?.refunded_amount ?? 0));

  const refundPayload = () => {
    const payload: Record<string, unknown> = {
      total_refund: refundMode === 'total',
      payment_method_id: parseInt(refundPaymentMethodId, 10),
      refund_reason: refundReason || undefined,
    };

    if (refundMode === 'partial') {
      payload.items = refundableItems
        .filter((item) => refundSelection[item.id] > 0)
        .map((item) => ({ item_id: item.id, qty: refundSelection[item.id] }));
    }

    if (!isSuperAdmin) {
      payload.supervisor_number = refundSupervisorNumber;
      payload.supervisor_pin = refundSupervisorPin;
    }

    return payload;
  };

  const confirmRefund = async () => {
    if (!refundPaymentMethodId) {
      setRefundError('Veuillez sélectionner un moyen de paiement pour le remboursement.');
      return;
    }
    setUpdating(true);
    setRefundError(null);

    try {
      const { data } = await api.post(`/orders/${orderId}/refund`, refundPayload());
      setOrder(data.order);
      setRefundModalVisible(false);
      setRefundSelection({});
      setRefundSupervisorNumber('');
      setRefundSupervisorPin('');
      setRefundPaymentMethodId('');
      setRefundReason('');
    } catch (error: any) {
      setRefundError(error?.response?.data?.message || 'Impossible de procéder au remboursement.');
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

  const availableTransitions = statuses.filter((s) => s.is_active && s.key !== order.status);

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
          <Text style={styles.sectionTitle}>
            Changer le statut{requiresSupervisor ? ' (superviseur requis)' : ''}
          </Text>
          <View style={styles.transitionsRow}>
            {availableTransitions.map((s) => (
              <TouchableOpacity
                key={s.key}
                style={styles.transitionBtn}
                onPress={() => handleStatusPress(s.key)}
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

      {isAdmin && totalRefundableAmount > 0 && (
        <View style={styles.sectionButtonRow}>
          <TouchableOpacity
            style={styles.paymentBtn}
            onPress={() => navigation.navigate('OrderPayment', { orderId: order.id })}
          >
            <Text style={styles.paymentBtnText}>💳  Enregistrer le paiement</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={styles.refundBtn}
            onPress={() => {
              setRefundMode('partial');
              setRefundModalVisible(true);
              setRefundError(null);
            }}
            disabled={updating}
          >
            <Text style={styles.refundBtnText}>Remboursement</Text>
          </TouchableOpacity>
        </View>
      )}

      {/* Paiements enregistrés */}
      {((order.payments ?? []).length > 0 || (order.refunds ?? []).length > 0) && (
        <>
          <Text style={styles.sectionTitle}>Paiements enregistrés</Text>
          <View style={styles.card}>
            {(order.payments ?? []).map((p) => (
              <View key={`p-${p.id}`} style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>{p.method_name}</Text>
                <Text style={styles.summaryValue}>{p.amount.toFixed(2)} €</Text>
              </View>
            ))}
            {(order.refunds ?? []).length > 0 && (
              <>
                <View style={styles.refundDivider}>
                  <Text style={styles.refundDividerText}>Remboursements</Text>
                </View>
                {(order.refunds ?? []).map((r) => (
                  <View key={`r-${r.id}`} style={styles.summaryRow}>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.summaryLabel}>{r.method_name}</Text>
                      {r.reason ? <Text style={styles.refundReasonText}>{r.reason}</Text> : null}
                    </View>
                    <Text style={[styles.summaryValue, { color: '#dc2626' }]}>-{r.amount.toFixed(2)} €</Text>
                  </View>
                ))}
              </>
            )}
          </View>
        </>
      )}

      <Modal visible={refundModalVisible} animationType="slide" transparent>
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Remboursement</Text>
            <View style={styles.modalModeRow}>
              <TouchableOpacity
                style={[styles.modeButton, refundMode === 'partial' && styles.modeButtonActive]}
                onPress={() => setRefundMode('partial')}
              >
                <Text style={[styles.modeButtonText, refundMode === 'partial' && styles.modeButtonTextActive]}>Partiel</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.modeButton, refundMode === 'total' && styles.modeButtonActive]}
                onPress={() => setRefundMode('total')}
              >
                <Text style={[styles.modeButtonText, refundMode === 'total' && styles.modeButtonTextActive]}>Total</Text>
              </TouchableOpacity>
            </View>

            {refundMode === 'total' ? (
              <View style={styles.refundSummaryCard}>
                <Text style={styles.refundSummaryLabel}>Montant à rembourser</Text>
                <Text style={styles.refundSummaryValue}>{totalRefundableAmount.toFixed(2)} €</Text>
              </View>
            ) : (
              <ScrollView style={styles.refundItemsList}>
                {refundableItems.length === 0 ? (
                  <Text style={styles.noRefundItems}>Aucun article remboursable.</Text>
                ) : (
                  refundableItems.map((item) => (
                    <View key={item.id} style={styles.refundItemRow}>
                      <View style={styles.refundItemInfo}>
                        <Text style={styles.refundItemLabel}>{item.drink_name || item.custom_label}</Text>
                        <Text style={styles.refundItemMeta}>Quantité remboursable : {item.refundable_qty}</Text>
                      </View>
                      <View style={styles.refundQtyControls}>
                        <TouchableOpacity
                          style={styles.qtyBtn}
                          onPress={() => setRefundSelection((prev) => ({ ...prev, [item.id]: Math.max(0, (prev[item.id] ?? 0) - 1) }))}
                          disabled={(refundSelection[item.id] ?? 0) <= 0}
                        >
                          <Text style={styles.qtyBtnText}>−</Text>
                        </TouchableOpacity>
                        <Text style={styles.qtyValue}>{refundSelection[item.id] ?? 0}</Text>
                        <TouchableOpacity
                          style={styles.qtyBtn}
                          onPress={() => setRefundSelection((prev) => ({ ...prev, [item.id]: Math.min(item.refundable_qty, (prev[item.id] ?? 0) + 1) }))}
                          disabled={(refundSelection[item.id] ?? 0) >= item.refundable_qty}
                        >
                          <Text style={styles.qtyBtnText}>+</Text>
                        </TouchableOpacity>
                      </View>
                    </View>
                  ))
                )}
              </ScrollView>
            )}

            {!isSuperAdmin && (
              <>
                <TextInput
                  style={styles.input}
                  placeholder="Numéro du superviseur"
                  placeholderTextColor="#9ca3af"
                  value={refundSupervisorNumber}
                  onChangeText={setRefundSupervisorNumber}
                  autoCapitalize="none"
                  keyboardType="default"
                />
                <TextInput
                  style={styles.input}
                  placeholder="PIN du superviseur"
                  placeholderTextColor="#9ca3af"
                  value={refundSupervisorPin}
                  onChangeText={setRefundSupervisorPin}
                  secureTextEntry
                  keyboardType="numeric"
                />
              </>
            )}
            {/* Moyen de paiement du remboursement */}
            <Text style={styles.refundFieldLabel}>Moyen de paiement *</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 8 }}>
              <View style={{ flexDirection: 'row', gap: 8, paddingBottom: 4 }}>
                {paymentMethods.map((m) => (
                  <TouchableOpacity
                    key={m.id}
                    style={[
                      styles.methodChip,
                      refundPaymentMethodId === String(m.id) && styles.methodChipActive,
                    ]}
                    onPress={() => setRefundPaymentMethodId(String(m.id))}
                  >
                    <Text style={[
                      styles.methodChipText,
                      refundPaymentMethodId === String(m.id) && styles.methodChipTextActive,
                    ]}>
                      {m.name}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>
            </ScrollView>

            {/* Motif optionnel */}
            <TextInput
              style={styles.input}
              placeholder="Motif du remboursement (optionnel)"
              placeholderTextColor="#9ca3af"
              value={refundReason}
              onChangeText={setRefundReason}
            />

            {refundError ? <Text style={styles.errorText}>{refundError}</Text> : null}
            <View style={styles.modalActions}>
              <TouchableOpacity
                style={[styles.modalButton, styles.modalCancelButton]}
                onPress={() => {
                  setRefundModalVisible(false);
                  setRefundSelection({});
                  setRefundSupervisorNumber('');
                  setRefundSupervisorPin('');
                  setRefundPaymentMethodId('');
                  setRefundReason('');
                  setRefundError(null);
                }}
                disabled={updating}
              >
                <Text style={styles.modalCancelText}>Annuler</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.modalButton, styles.modalConfirmButton]}
                onPress={confirmRefund}
                disabled={updating || (refundMode === 'partial' && Object.values(refundSelection).every((qty) => qty <= 0))}
              >
                {updating ? (
                  <ActivityIndicator size="small" color="#fff" />
                ) : (
                  <Text style={styles.modalConfirmText}>Valider</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      <Modal visible={supervisorModalVisible} animationType="slide" transparent>
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Supervision requise</Text>
            <Text style={styles.modalDescription}>
              Cette transition nécessite l’authentification d’un superviseur.
            </Text>
            <TextInput
              style={styles.input}
              placeholder="Numéro du superviseur"
              placeholderTextColor="#9ca3af"
              value={supervisorNumber}
              onChangeText={setSupervisorNumber}
              autoCapitalize="none"
              keyboardType="default"
            />
            <TextInput
              style={styles.input}
              placeholder="PIN du superviseur"
              placeholderTextColor="#9ca3af"
              value={supervisorPin}
              onChangeText={setSupervisorPin}
              secureTextEntry
              keyboardType="numeric"
            />
            {supervisorError ? <Text style={styles.errorText}>{supervisorError}</Text> : null}
            <View style={styles.modalActions}>
              <TouchableOpacity
                style={[styles.modalButton, styles.modalCancelButton]}
                onPress={() => {
                  setSupervisorModalVisible(false);
                  setPendingStatus(null);
                  setSupervisorNumber('');
                  setSupervisorPin('');
                  setSupervisorError(null);
                }}
                disabled={updating}
              >
                <Text style={styles.modalCancelText}>Annuler</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.modalButton, styles.modalConfirmButton]}
                onPress={() => pendingStatus && updateStatus(pendingStatus, { number: supervisorNumber, pin: supervisorPin })}
                disabled={updating}
              >
                {updating ? (
                  <ActivityIndicator size="small" color="#fff" />
                ) : (
                  <Text style={styles.modalConfirmText}>Valider</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
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
  sectionButtonRow: { marginTop: 16, gap: 10 },
  paymentBtn: { backgroundColor: '#16a34a', paddingHorizontal: 16, paddingVertical: 12, borderRadius: 10, alignItems: 'center', marginBottom: 8 },
  paymentBtnText: { color: '#fff', fontWeight: '700', fontSize: 14 },
  refundBtn: { backgroundColor: '#dc2626', paddingHorizontal: 16, paddingVertical: 12, borderRadius: 10, alignItems: 'center' },
  refundBtnText: { color: '#fff', fontWeight: '700', fontSize: 14 },
  modalOverlay: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: 'rgba(15, 23, 42, 0.45)', padding: 24 },
  modalContent: { width: '100%', backgroundColor: '#fff', borderRadius: 16, padding: 20, shadowColor: '#000', shadowOpacity: 0.12, shadowRadius: 18, elevation: 10 },
  modalTitle: { fontSize: 18, fontWeight: '700', color: '#111827', marginBottom: 6 },
  modalDescription: { fontSize: 14, color: '#6b7280', marginBottom: 16 },
  modalModeRow: { flexDirection: 'row', gap: 12, marginBottom: 16 },
  modeButton: { flex: 1, borderRadius: 12, backgroundColor: '#f3f4f6', paddingVertical: 10, alignItems: 'center' },
  modeButtonActive: { backgroundColor: '#92400e' },
  modeButtonText: { color: '#374151', fontWeight: '700' },
  modeButtonTextActive: { color: '#fff' },
  refundSummaryCard: { backgroundColor: '#f8fafc', borderRadius: 14, padding: 16, marginBottom: 16 },
  refundSummaryLabel: { fontSize: 14, color: '#6b7280', marginBottom: 4 },
  refundSummaryValue: { fontSize: 20, fontWeight: '700', color: '#111827' },
  refundItemsList: { maxHeight: 260, marginBottom: 16 },
  noRefundItems: { color: '#6b7280', textAlign: 'center', paddingVertical: 20 },
  refundDivider: { marginTop: 10, marginBottom: 6, borderTopWidth: 1, borderTopColor: '#fee2e2', paddingTop: 8 },
  refundDividerText: { fontSize: 11, fontWeight: '700', color: '#dc2626', textTransform: 'uppercase', letterSpacing: 0.5 },
  refundReasonText: { fontSize: 11, color: '#a8a29e', fontStyle: 'italic', marginTop: 1 },
  refundFieldLabel: { fontSize: 13, fontWeight: '600', color: '#78716c', marginBottom: 6, marginTop: 4 },
  methodChip: { paddingHorizontal: 12, paddingVertical: 7, borderRadius: 20, borderWidth: 1.5, borderColor: '#d6d3d1', backgroundColor: '#fafaf9' },
  methodChipActive: { borderColor: '#92400e', backgroundColor: '#fef3c7' },
  methodChipText: { fontSize: 13, color: '#57534e' },
  methodChipTextActive: { color: '#92400e', fontWeight: '700' },
  refundItemRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#e5e7eb' },
  refundItemInfo: { flex: 1, marginRight: 12 },
  refundItemLabel: { fontSize: 14, fontWeight: '700', color: '#111827' },
  refundItemMeta: { fontSize: 12, color: '#6b7280', marginTop: 2 },
  refundQtyControls: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  qtyBtn: { width: 32, height: 32, borderRadius: 8, backgroundColor: '#f3f4f6', justifyContent: 'center', alignItems: 'center' },
  qtyBtnText: { fontSize: 18, color: '#374151', fontWeight: '700' },
  qtyValue: { fontSize: 14, fontWeight: '700', color: '#111827', minWidth: 24, textAlign: 'center' },
  input: { backgroundColor: '#f8fafc', borderRadius: 12, borderWidth: 1, borderColor: '#e5e7eb', paddingHorizontal: 14, paddingVertical: 12, marginBottom: 12, color: '#111827' },
  errorText: { color: '#b91c1c', fontSize: 13, marginBottom: 12 },
  modalActions: { flexDirection: 'row', justifyContent: 'flex-end', gap: 10 },
  modalButton: { minWidth: 100, paddingHorizontal: 16, paddingVertical: 12, borderRadius: 10, alignItems: 'center' },
  modalCancelButton: { backgroundColor: '#f3f4f6' },
  modalCancelText: { color: '#374151', fontWeight: '700' },
  modalConfirmButton: { backgroundColor: '#92400e' },
  modalConfirmText: { color: '#fff', fontWeight: '700' },
});
