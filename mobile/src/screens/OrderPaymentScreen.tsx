import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  TextInput,
  StyleSheet,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import api from '../api/client';
import { Order, PaymentMethod } from '../types';

interface PaymentRow {
  payment_method_id: string;
  amount: string;
}

export default function OrderPaymentScreen() {
  const route = useRoute<any>();
  const navigation = useNavigation<any>();
  const { orderId } = route.params;

  const [order, setOrder] = useState<Order | null>(null);
  const [methods, setMethods] = useState<PaymentMethod[]>([]);
  const [rows, setRows] = useState<PaymentRow[]>([{ payment_method_id: '', amount: '' }]);
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      api.get(`/orders/${orderId}`).then(({ data }) => {
        const o: Order = data.order;
        setOrder(o);
        if (o.payments && o.payments.length > 0) {
          setRows(
            o.payments.map((p) => ({
              payment_method_id: String(p.payment_method_id),
              amount: p.amount.toFixed(2),
            }))
          );
        }
      }),
      api.get('/payment-methods').then(({ data }) => setMethods(data.payment_methods)),
    ]).finally(() => setLoading(false));
  }, [orderId]);

  const totalPaid = rows.reduce((s, r) => s + (parseFloat(r.amount) || 0), 0);

  const updateRow = (index: number, field: keyof PaymentRow, value: string) => {
    setRows((prev) => prev.map((r, i) => (i === index ? { ...r, [field]: value } : r)));
  };

  const addRow = () => setRows((prev) => [...prev, { payment_method_id: '', amount: '' }]);

  const removeRow = (index: number) => {
    if (rows.length > 1) setRows((prev) => prev.filter((_, i) => i !== index));
  };

  const handleSave = async () => {
    const invalid = rows.some((r) => !r.payment_method_id || !r.amount || parseFloat(r.amount) <= 0);
    if (invalid) {
      Alert.alert('Erreur', 'Veuillez renseigner un moyen de paiement et un montant valide pour chaque ligne.');
      return;
    }

    setSaving(true);
    try {
      await api.post(`/orders/${orderId}/payments`, {
        payments: rows.map((r) => ({
          payment_method_id: parseInt(r.payment_method_id, 10),
          amount: parseFloat(parseFloat(r.amount).toFixed(2)),
        })),
      });
      Alert.alert('Succès', 'Paiements enregistrés.', [
        { text: 'OK', onPress: () => navigation.goBack() },
      ]);
    } catch (e: any) {
      const msg = e.response?.data?.message ?? e.response?.data?.errors?.payments?.[0] ?? 'Erreur lors de l\'enregistrement.';
      Alert.alert('Erreur', msg);
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color="#92400e" size="large" />
      </View>
    );
  }

  if (!order) return null;

  const remaining = Math.max(0, order.total_amount - totalPaid);

  return (
    <ScrollView style={styles.container} contentContainerStyle={{ padding: 16, paddingBottom: 40 }}>
      {/* Récap commande */}
      <View style={styles.card}>
        <Text style={styles.cardTitle}>Commande #{String(order.id).padStart(4, '0')}</Text>
        <Text style={styles.customerName}>{order.customer_name}</Text>
        <Text style={styles.totalLabel}>
          Total : <Text style={styles.totalAmount}>{order.total_amount.toFixed(2).replace('.', ',')} €</Text>
        </Text>
      </View>

      {/* Lignes de paiement */}
      <View style={styles.card}>
        <Text style={styles.cardTitle}>Répartition du paiement</Text>

        {rows.map((row, index) => (
          <View key={index} style={styles.row}>
            <View style={styles.rowLeft}>
              <Text style={styles.rowLabel}>Moyen de paiement</Text>
              <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                <View style={styles.methodPicker}>
                  {methods.map((m) => (
                    <TouchableOpacity
                      key={m.id}
                      style={[
                        styles.methodChip,
                        row.payment_method_id === String(m.id) && styles.methodChipActive,
                      ]}
                      onPress={() => updateRow(index, 'payment_method_id', String(m.id))}
                    >
                      <Text
                        style={[
                          styles.methodChipText,
                          row.payment_method_id === String(m.id) && styles.methodChipTextActive,
                        ]}
                      >
                        {m.name}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </ScrollView>
            </View>

            <View style={styles.rowRight}>
              <Text style={styles.rowLabel}>Montant (€)</Text>
              <View style={styles.amountRow}>
                <TextInput
                  style={styles.amountInput}
                  keyboardType="decimal-pad"
                  value={row.amount}
                  onChangeText={(v) => updateRow(index, 'amount', v)}
                  placeholder="0.00"
                  placeholderTextColor="#9ca3af"
                />
                {rows.length > 1 && (
                  <TouchableOpacity onPress={() => removeRow(index)} style={styles.removeBtn}>
                    <Text style={styles.removeBtnText}>✕</Text>
                  </TouchableOpacity>
                )}
              </View>
            </View>
          </View>
        ))}

        <TouchableOpacity style={styles.addRowBtn} onPress={addRow}>
          <Text style={styles.addRowBtnText}>+ Ajouter un moyen de paiement</Text>
        </TouchableOpacity>

        {/* Récap totaux */}
        <View style={styles.totals}>
          <View style={styles.totalRow}>
            <Text style={styles.totalRowLabel}>Total commandé</Text>
            <Text style={styles.totalRowValue}>{order.total_amount.toFixed(2).replace('.', ',')} €</Text>
          </View>
          <View style={styles.totalRow}>
            <Text style={styles.totalRowLabel}>Total saisi</Text>
            <Text style={[styles.totalRowValue, { fontWeight: '700' }]}>
              {totalPaid.toFixed(2).replace('.', ',')} €
            </Text>
          </View>
          <View style={styles.totalRow}>
            <Text style={styles.totalRowLabel}>Écart</Text>
            <Text style={[styles.totalRowValue, { color: remaining > 0 ? '#dc2626' : '#16a34a' }]}>
              {remaining.toFixed(2).replace('.', ',')} €
            </Text>
          </View>
        </View>
      </View>

      <TouchableOpacity
        style={[styles.saveBtn, saving && { opacity: 0.6 }]}
        onPress={handleSave}
        disabled={saving}
      >
        {saving ? (
          <ActivityIndicator color="#fff" />
        ) : (
          <Text style={styles.saveBtnText}>Enregistrer les paiements</Text>
        )}
      </TouchableOpacity>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f5f4' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 2,
  },
  cardTitle: { fontSize: 15, fontWeight: '700', color: '#1c1917', marginBottom: 6 },
  customerName: { fontSize: 14, color: '#57534e', marginBottom: 4 },
  totalLabel: { fontSize: 14, color: '#57534e' },
  totalAmount: { fontWeight: '700', color: '#1c1917' },
  row: { marginBottom: 16, paddingBottom: 16, borderBottomWidth: 1, borderBottomColor: '#f5f5f4' },
  rowLeft: { marginBottom: 8 },
  rowLabel: { fontSize: 12, fontWeight: '600', color: '#78716c', marginBottom: 6 },
  methodPicker: { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  methodChip: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
    borderWidth: 1.5,
    borderColor: '#d6d3d1',
    backgroundColor: '#fafaf9',
  },
  methodChipActive: { borderColor: '#92400e', backgroundColor: '#fef3c7' },
  methodChipText: { fontSize: 13, color: '#57534e' },
  methodChipTextActive: { color: '#92400e', fontWeight: '700' },
  rowRight: {},
  amountRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  amountInput: {
    flex: 1,
    borderWidth: 1.5,
    borderColor: '#d6d3d1',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 16,
    color: '#1c1917',
    backgroundColor: '#fafaf9',
  },
  removeBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: '#fee2e2',
    justifyContent: 'center',
    alignItems: 'center',
  },
  removeBtnText: { color: '#dc2626', fontWeight: '700' },
  addRowBtn: { marginTop: 8 },
  addRowBtnText: { color: '#92400e', fontWeight: '600', fontSize: 14 },
  totals: { marginTop: 16, borderTopWidth: 1, borderTopColor: '#e7e5e4', paddingTop: 12, gap: 6 },
  totalRow: { flexDirection: 'row', justifyContent: 'space-between' },
  totalRowLabel: { fontSize: 14, color: '#78716c' },
  totalRowValue: { fontSize: 14, color: '#1c1917' },
  saveBtn: {
    backgroundColor: '#92400e',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    marginTop: 4,
  },
  saveBtnText: { color: '#fff', fontWeight: '700', fontSize: 15 },
});
