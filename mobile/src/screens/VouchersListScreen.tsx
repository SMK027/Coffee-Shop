import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  Alert,
  TextInput,
  ScrollView,
} from 'react-native';
import DateTimePicker, { DateTimePickerEvent } from '@react-native-community/datetimepicker';
import api from '../api/client';
import { useNavigation } from '@react-navigation/native';

type VoucherStatusFilter = 'all' | 'valid' | 'used' | 'expired';

type VoucherItem = {
  id: number;
  code: string;
  amount: number;
  active: boolean;
  is_used: boolean;
  issued_by_name?: string | null;
  issued_at?: string | null;
  expires_at?: string | null;
  restricted_name?: string | null;
  restricted_card_number?: string | null;
};

export default function VouchersListScreen() {
  const navigation = useNavigation<any>();
  const [items, setItems] = useState<VoucherItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const [q, setQ] = useState('');
  const [status, setStatus] = useState<VoucherStatusFilter>('all');
  const [recipient, setRecipient] = useState('');
  const [issuer, setIssuer] = useState('');
  const [amountMin, setAmountMin] = useState('');
  const [amountMax, setAmountMax] = useState('');
  const [expiresFrom, setExpiresFrom] = useState('');
  const [expiresTo, setExpiresTo] = useState('');
  const [datePickerTarget, setDatePickerTarget] = useState<'from' | 'to' | null>(null);
  const [datePickerValue, setDatePickerValue] = useState<Date>(new Date());
  const hasLoadedOnce = useRef(false);

  const getVoucherStatus = (voucher: VoucherItem) => {
    if (voucher.is_used) {
      return { label: 'Déjà utilisé', container: styles.statusUsed, text: styles.statusUsedText };
    }
    if (!voucher.active) {
      return { label: 'Expiré', container: styles.statusExpired, text: styles.statusExpiredText };
    }
    return { label: 'Actif', container: styles.statusActive, text: styles.statusActiveText };
  };

  const formatDateInput = (date: Date) => {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
  };

  const parseDateInput = (value: string): Date => {
    const [y, m, d] = value.split('-').map(Number);
    if (!y || !m || !d) return new Date();
    return new Date(y, m - 1, d);
  };

  const hasFilters = useMemo(() => {
    return (
      q.trim() !== '' ||
      status !== 'all' ||
      recipient.trim() !== '' ||
      issuer.trim() !== '' ||
      amountMin.trim() !== '' ||
      amountMax.trim() !== '' ||
      expiresFrom.trim() !== '' ||
      expiresTo.trim() !== ''
    );
  }, [q, status, recipient, issuer, amountMin, amountMax, expiresFrom, expiresTo]);

  const load = useCallback(async (isRefresh = false) => {
    if (isRefresh) {
      setRefreshing(true);
    } else {
      setLoading(true);
    }

    try {
      const params: Record<string, string> = {};
      if (q.trim()) params.q = q.trim();
      if (status !== 'all') params.status = status;
      if (recipient.trim()) params.recipient = recipient.trim();
      if (issuer.trim()) params.issuer = issuer.trim();
      if (amountMin.trim()) params.amount_min = amountMin.trim();
      if (amountMax.trim()) params.amount_max = amountMax.trim();
      if (expiresFrom.trim()) params.expires_from = expiresFrom.trim();
      if (expiresTo.trim()) params.expires_to = expiresTo.trim();

      const { data } = await api.get('/vouchers', { params });
      setItems(data);
    } catch (e) {
      Alert.alert('Erreur', 'Impossible de récupérer les bons.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [q, status, recipient, issuer, amountMin, amountMax, expiresFrom, expiresTo]);

  const clearFilters = () => {
    setQ('');
    setStatus('all');
    setRecipient('');
    setIssuer('');
    setAmountMin('');
    setAmountMax('');
    setExpiresFrom('');
    setExpiresTo('');
  };

  const openDatePicker = (target: 'from' | 'to') => {
    const current = target === 'from' ? expiresFrom : expiresTo;
    setDatePickerValue(current ? parseDateInput(current) : new Date());
    setDatePickerTarget(target);
  };

  const onDateChange = (event: DateTimePickerEvent, selectedDate?: Date) => {
    setDatePickerTarget(null);
    if (event.type === 'dismissed' || !selectedDate) return;
    const value = formatDateInput(selectedDate);
    if (datePickerTarget === 'from') setExpiresFrom(value);
    if (datePickerTarget === 'to') setExpiresTo(value);
  };

  const formatCardNumber = (raw?: string | null) => {
    if (!raw) return '';
    return raw.replace(/(.{4})/g, '$1 ').trim();
  };

  const getRecipientLabel = (voucher: VoucherItem) => {
    if (voucher.restricted_name) return `Dest. : ${voucher.restricted_name}`;
    if (voucher.restricted_card_number) return `Dest. : carte ${formatCardNumber(voucher.restricted_card_number)}`;
    return 'Dest. : tous clients';
  };

  useEffect(() => {
    if (!hasLoadedOnce.current) {
      hasLoadedOnce.current = true;
      load(false);
      return;
    }

    const timer = setTimeout(() => {
      load(false);
    }, 3000);

    return () => clearTimeout(timer);
  }, [load]);

  if (loading) return (
    <View style={styles.center}><ActivityIndicator color="#92400e" size="large" /></View>
  );

  return (
    <View style={styles.container}>
      <FlatList
        data={items}
        keyExtractor={(i) => String(i.id)}
        contentContainerStyle={{ padding: 12 }}
        ListHeaderComponent={
          <View style={styles.filtersCard}>
            <Text style={styles.filtersTitle}>Filtres</Text>

            <TextInput
              value={q}
              onChangeText={setQ}
              placeholder="Code ou nom émetteur"
              style={styles.input}
              placeholderTextColor="#9ca3af"
              selectionColor="#92400e"
            />

            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipsRow}>
              {[
                { key: 'all', label: 'Tous' },
                { key: 'valid', label: 'Valides' },
                { key: 'used', label: 'Utilisés' },
                { key: 'expired', label: 'Expirés' },
              ].map((chip) => {
                const isActive = status === chip.key;
                return (
                  <TouchableOpacity
                    key={chip.key}
                    style={[styles.chip, isActive && styles.chipActive]}
                    onPress={() => setStatus(chip.key as VoucherStatusFilter)}
                  >
                    <Text style={[styles.chipText, isActive && styles.chipTextActive]}>{chip.label}</Text>
                  </TouchableOpacity>
                );
              })}
            </ScrollView>

            <TextInput
              value={recipient}
              onChangeText={setRecipient}
              placeholder="Destinataire (nom ou carte)"
              style={styles.input}
              placeholderTextColor="#9ca3af"
              selectionColor="#92400e"
            />

            <TextInput
              value={issuer}
              onChangeText={setIssuer}
              placeholder="Émetteur (compte admin)"
              style={styles.input}
              placeholderTextColor="#9ca3af"
              selectionColor="#92400e"
            />

            <View style={styles.row2}>
              <TextInput
                value={amountMin}
                onChangeText={setAmountMin}
                placeholder="Montant min"
                keyboardType="decimal-pad"
                style={[styles.input, styles.halfInput]}
                placeholderTextColor="#9ca3af"
                selectionColor="#92400e"
              />
              <TextInput
                value={amountMax}
                onChangeText={setAmountMax}
                placeholder="Montant max"
                keyboardType="decimal-pad"
                style={[styles.input, styles.halfInput]}
                placeholderTextColor="#9ca3af"
                selectionColor="#92400e"
              />
            </View>

            <View style={styles.row2}>
              <TouchableOpacity style={[styles.input, styles.dateBtn, styles.halfInput]} onPress={() => openDatePicker('from')}>
                <Text style={expiresFrom ? styles.dateText : styles.datePlaceholder}>{expiresFrom || 'Expiration du'}</Text>
              </TouchableOpacity>
              <TouchableOpacity style={[styles.input, styles.dateBtn, styles.halfInput]} onPress={() => openDatePicker('to')}>
                <Text style={expiresTo ? styles.dateText : styles.datePlaceholder}>{expiresTo || 'Expiration au'}</Text>
              </TouchableOpacity>
            </View>

            {hasFilters && (
              <TouchableOpacity style={styles.resetBtn} onPress={clearFilters}>
                <Text style={styles.resetBtnText}>Réinitialiser les filtres</Text>
              </TouchableOpacity>
            )}
          </View>
        }
        renderItem={({ item }) => (
          <TouchableOpacity style={styles.row} onPress={() => navigation.navigate('VoucherDetail', { voucherId: item.id })}>
            <View>
              <Text style={styles.title}>{item.code}</Text>
              <Text style={styles.meta}>{item.amount} € • Expire: {item.expires_at ? new Date(item.expires_at).toLocaleDateString() : '—'}</Text>
              <Text style={styles.metaSecondary}>{item.issued_by_name ? `Émis par ${item.issued_by_name}` : 'Émetteur inconnu'}</Text>
              <Text style={styles.metaSecondary}>{getRecipientLabel(item)}</Text>
            </View>
            <View style={[styles.statusBadge, getVoucherStatus(item).container]}>
              <Text style={[styles.statusBadgeText, getVoucherStatus(item).text]}>{getVoucherStatus(item).label}</Text>
            </View>
          </TouchableOpacity>
        )}
        ListEmptyComponent={<Text style={styles.empty}>Aucun bon trouvé.</Text>}
        onRefresh={() => load(true)}
        refreshing={refreshing}
      />
      {datePickerTarget && (
        <DateTimePicker
          value={datePickerValue}
          mode="date"
          display="default"
          onChange={onDateChange}
        />
      )}
      <TouchableOpacity style={styles.fab} onPress={() => navigation.navigate('CreateVoucher')}>
        <Text style={styles.fabText}>＋</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  filtersCard: { backgroundColor: '#fff', borderRadius: 12, padding: 12, marginBottom: 10, borderWidth: 1, borderColor: '#ede7df' },
  filtersTitle: { color: '#78350f', fontWeight: '700', marginBottom: 10, fontSize: 13 },
  input: {
    borderWidth: 1,
    borderColor: '#e5e7eb',
    borderRadius: 10,
    backgroundColor: '#fff',
    paddingHorizontal: 12,
    paddingVertical: 10,
    color: '#111827',
    marginBottom: 8,
  },
  chipsRow: { gap: 8, paddingBottom: 8 },
  chip: { borderWidth: 1, borderColor: '#e7e5e4', backgroundColor: '#f5f5f4', paddingHorizontal: 12, paddingVertical: 7, borderRadius: 999 },
  chipActive: { backgroundColor: '#92400e', borderColor: '#92400e' },
  chipText: { color: '#57534e', fontSize: 12, fontWeight: '700' },
  chipTextActive: { color: '#fff' },
  row2: { flexDirection: 'row', gap: 8 },
  halfInput: { flex: 1 },
  dateBtn: { justifyContent: 'center' },
  dateText: { color: '#111827' },
  datePlaceholder: { color: '#9ca3af' },
  resetBtn: { alignSelf: 'flex-start', marginTop: 2, backgroundColor: '#f3f4f6', borderRadius: 8, paddingHorizontal: 12, paddingVertical: 8 },
  resetBtnText: { color: '#4b5563', fontWeight: '600', fontSize: 12 },
  row: { backgroundColor: '#fff', padding: 12, borderRadius: 10, marginBottom: 8, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  title: { fontWeight: '700', color: '#1f2937' },
  meta: { color: '#6b7280', marginTop: 4 },
  metaSecondary: { color: '#9ca3af', marginTop: 2, fontSize: 12 },
  empty: { textAlign: 'center', marginTop: 24, color: '#9ca3af' },
  fab: { position: 'absolute', right: 16, bottom: 20, width: 56, height: 56, borderRadius: 28, backgroundColor: '#92400e', justifyContent: 'center', alignItems: 'center', elevation: 6 },
  fabText: { color: '#fff', fontSize: 28, lineHeight: 28 },
  statusBadge: { borderRadius: 999, paddingHorizontal: 10, paddingVertical: 4 },
  statusBadgeText: { fontSize: 12, fontWeight: '700' },
  statusUsed: { backgroundColor: '#fee2e2' },
  statusUsedText: { color: '#b91c1c' },
  statusExpired: { backgroundColor: '#fef3c7' },
  statusExpiredText: { color: '#92400e' },
  statusActive: { backgroundColor: '#dcfce7' },
  statusActiveText: { color: '#166534' },
});
