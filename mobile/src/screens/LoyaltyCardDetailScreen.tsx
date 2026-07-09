import React, { useCallback, useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  TouchableOpacity,
  Modal,
  TextInput,
  Alert,
} from 'react-native';
import { useRoute, useNavigation, RouteProp } from '@react-navigation/native';
import QRCode from 'react-native-qrcode-svg';
import Barcode from 'react-native-barcode-svg';
import api from '../api/client';
import { useAuth } from '../context/AuthContext';
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
    case 'refund': return 'Remboursement';
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
  const [adjustModalVisible, setAdjustModalVisible] = useState(false);
  const [adjustType, setAdjustType] = useState<'credit' | 'debit'>('credit');
  const [adjustPoints, setAdjustPoints] = useState('');
  const [adjustReason, setAdjustReason] = useState('');
  const [supervisorNumber, setSupervisorNumber] = useState('');
  const [supervisorPin, setSupervisorPin] = useState('');
  const [adjustError, setAdjustError] = useState<string | null>(null);
  const [adjustSubmitting, setAdjustSubmitting] = useState(false);
  const { user } = useAuth();
  const isSuperAdmin = user?.global_role === 'superadmin';
  const isAdmin = user?.global_role === 'admin' || isSuperAdmin;

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

  const resetAdjustmentForm = () => {
    setAdjustType('credit');
    setAdjustPoints('');
    setAdjustReason('');
    setSupervisorNumber('');
    setSupervisorPin('');
    setAdjustError(null);
  };

  const submitAdjustment = async () => {
    const points = parseInt(adjustPoints, 10);
    if (!points || points < 1) {
      setAdjustError('Entrez un nombre de points valide.');
      return;
    }

    if (!isSuperAdmin && (!supervisorNumber.trim() || !supervisorPin.trim())) {
      setAdjustError('Le numéro et le PIN du superviseur sont requis.');
      return;
    }

    setAdjustSubmitting(true);
    setAdjustError(null);

    try {
      await api.post(`/loyalty-cards/${cardId}/adjust`, {
        type: adjustType,
        points,
        reason: adjustReason.trim() || undefined,
        ...(isSuperAdmin ? {} : {
          supervisor_number: supervisorNumber,
          supervisor_pin: supervisorPin,
        }),
      });

      await load();
      setAdjustModalVisible(false);
      resetAdjustmentForm();
      Alert.alert('Succès', 'Ajustement de points appliqué.');
    } catch (error: any) {
      setAdjustError(error?.response?.data?.message || 'Impossible d’appliquer l’ajustement.');
    } finally {
      setAdjustSubmitting(false);
    }
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

      {isAdmin && (
        <TouchableOpacity
          style={styles.adjustButton}
          onPress={() => setAdjustModalVisible(true)}
        >
          <Text style={styles.adjustButtonText}>Ajuster les points</Text>
        </TouchableOpacity>
      )}

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

      <View style={styles.cardCodeSection}>
        <View style={styles.codeBox}>
          <Text style={styles.codeLabel}>QR code</Text>
          <View style={styles.codePreview}>
            <QRCode value={card.card_number} size={140} backgroundColor="transparent" color="#1f2937" />
          </View>
        </View>
        <View style={styles.codeBox}>
          <Text style={styles.codeLabel}>Code-barres</Text>
          <View style={styles.codePreview}>
            <Barcode
              value={card.card_number}
              format="CODE128"
              singleBarWidth={2}
              height={80}
              lineColor="#1f2937"
              backgroundColor="transparent"
            />
            <Text style={styles.barcodeText}>{card.card_number}</Text>
          </View>
        </View>
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

      <Modal
        visible={adjustModalVisible}
        animationType="slide"
        transparent
        onRequestClose={() => setAdjustModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Ajuster les points</Text>
            <View style={styles.modalRow}>
              <TouchableOpacity
                style={[styles.modalOption, adjustType === 'credit' && styles.modalOptionActive]}
                onPress={() => setAdjustType('credit')}
              >
                <Text style={[styles.modalOptionText, adjustType === 'credit' && styles.modalOptionTextActive]}>Créditer</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.modalOption, adjustType === 'debit' && styles.modalOptionActive]}
                onPress={() => setAdjustType('debit')}
              >
                <Text style={[styles.modalOptionText, adjustType === 'debit' && styles.modalOptionTextActive]}>Débiter</Text>
              </TouchableOpacity>
            </View>

            <Text style={styles.modalLabel}>Nombre de points</Text>
            <TextInput
              style={styles.modalInput}
              keyboardType="numeric"
              value={adjustPoints}
              onChangeText={setAdjustPoints}
              placeholder="Ex. 100"
            />

            <Text style={styles.modalLabel}>Motif (optionnel)</Text>
            <TextInput
              style={styles.modalInput}
              value={adjustReason}
              onChangeText={setAdjustReason}
              placeholder="Raison de l’ajustement"
            />

            {!isSuperAdmin && (
              <>
                <Text style={styles.modalSectionTitle}>Validation superviseur</Text>
                <TextInput
                  style={styles.modalInput}
                  value={supervisorNumber}
                  onChangeText={setSupervisorNumber}
                  placeholder="Numéro du superviseur"
                />
                <TextInput
                  style={styles.modalInput}
                  value={supervisorPin}
                  secureTextEntry
                  keyboardType="numeric"
                  onChangeText={setSupervisorPin}
                  placeholder="PIN du superviseur"
                />
              </>
            )}

            {adjustError && <Text style={styles.modalError}>{adjustError}</Text>}

            <View style={styles.modalActions}>
              <TouchableOpacity
                style={[styles.modalButton, styles.modalButtonSecondary]}
                onPress={() => {
                  setAdjustModalVisible(false);
                  resetAdjustmentForm();
                }}
                disabled={adjustSubmitting}
              >
                <Text style={styles.modalButtonText}>Annuler</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.modalButton, styles.modalButtonPrimary]}
                onPress={submitAdjustment}
                disabled={adjustSubmitting}
              >
                {adjustSubmitting ? (
                  <ActivityIndicator size="small" color="#fff" />
                ) : (
                  <Text style={[styles.modalButtonText, styles.modalButtonTextPrimary]}>Appliquer</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
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

  cardCodeSection: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between', gap: 12, marginBottom: 12 },
  codeBox: { flex: 1, minWidth: 160, backgroundColor: '#fff', borderRadius: 12, padding: 12, alignItems: 'center', shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.06, shadowRadius: 4, elevation: 2 },
  codeLabel: { fontSize: 12, fontWeight: '700', color: '#6b7280', marginBottom: 8 },
  codePreview: { alignItems: 'center', justifyContent: 'center', padding: 6, backgroundColor: '#f8fafc', borderRadius: 12 },
  barcodeText: { fontSize: 12, letterSpacing: 2, marginTop: 6, color: '#374151' },

  statsRow: { flexDirection: 'row', gap: 8, marginBottom: 8 },
  statCard: { flex: 1, backgroundColor: '#fff', borderRadius: 12, padding: 12, alignItems: 'center', shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.06, shadowRadius: 4, elevation: 2 },
  statCardWide: { backgroundColor: '#fff', borderRadius: 12, padding: 12, alignItems: 'center', marginBottom: 16, shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.06, shadowRadius: 4, elevation: 2 },
  statValue: { fontSize: 22, fontWeight: '700', color: '#d97706' },
  statLabel: { fontSize: 12, color: '#6b7280', marginTop: 2, textAlign: 'center' },
  adjustButton: { backgroundColor: '#92400e', borderRadius: 12, paddingVertical: 12, alignItems: 'center', marginBottom: 12 },
  adjustButtonText: { color: '#fff', fontWeight: '700', fontSize: 15 },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(15, 23, 42, 0.55)', justifyContent: 'center', padding: 20 },
  modalContent: { backgroundColor: '#fff', borderRadius: 18, padding: 20, shadowColor: '#000', shadowOffset: { width: 0, height: 6 }, shadowOpacity: 0.12, shadowRadius: 16, elevation: 12 },
  modalTitle: { fontSize: 18, fontWeight: '700', color: '#1f2937', marginBottom: 16 },
  modalRow: { flexDirection: 'row', gap: 8, marginBottom: 16 },
  modalOption: { flex: 1, paddingVertical: 12, borderRadius: 12, backgroundColor: '#f3f4f6', alignItems: 'center' },
  modalOptionActive: { backgroundColor: '#92400e' },
  modalOptionText: { fontSize: 13, fontWeight: '600', color: '#475569' },
  modalOptionTextActive: { color: '#fff' },
  modalLabel: { fontSize: 13, color: '#475569', marginBottom: 6 },
  modalSectionTitle: { fontSize: 14, fontWeight: '700', color: '#1f2937', marginTop: 16, marginBottom: 8 },
  modalInput: { backgroundColor: '#f8fafc', borderRadius: 12, paddingVertical: 10, paddingHorizontal: 12, borderWidth: 1, borderColor: '#e2e8f0', marginBottom: 12, color: '#111827' },
  modalError: { color: '#b91c1c', fontSize: 13, marginBottom: 12 },
  modalActions: { flexDirection: 'row', justifyContent: 'space-between', gap: 10, marginTop: 8 },
  modalButton: { flex: 1, borderRadius: 12, paddingVertical: 12, alignItems: 'center', justifyContent: 'center' },
  modalButtonPrimary: { backgroundColor: '#92400e' },
  modalButtonSecondary: { backgroundColor: '#f3f4f6' },
  modalButtonText: { fontSize: 14, fontWeight: '700' },
  modalButtonTextPrimary: { color: '#fff' },

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
