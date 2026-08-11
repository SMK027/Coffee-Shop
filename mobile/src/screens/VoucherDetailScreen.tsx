import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ActivityIndicator, TouchableOpacity, Alert } from 'react-native';
import { useRoute, useNavigation, RouteProp } from '@react-navigation/native';
import * as Clipboard from 'expo-clipboard';
import api from '../api/client';

type ParamList = { VoucherDetail: { voucherId: number } };

export default function VoucherDetailScreen() {
  const route = useRoute<RouteProp<ParamList, 'VoucherDetail'>>();
  const navigation = useNavigation<any>();
  const { voucherId } = route.params;
  const [item, setItem] = useState<any|null>(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  const getVoucherStatus = (voucher: any) => {
    if (voucher.is_used) {
      return { label: 'Déjà utilisé', container: styles.statusUsed, text: styles.statusUsedText };
    }
    if (!voucher.active) {
      return { label: 'Expiré', container: styles.statusExpired, text: styles.statusExpiredText };
    }
    return { label: 'Actif', container: styles.statusActive, text: styles.statusActiveText };
  };

  const load = async () => {
    setLoading(true);
    try { const { data } = await api.get(`/vouchers/${voucherId}`); setItem(data); }
    catch (e) { Alert.alert('Erreur', 'Impossible de charger le bon.'); }
    finally { setLoading(false); }
  };

  useEffect(() => { load(); }, [voucherId]);

  const remove = async () => {
    Alert.alert('Supprimer', 'Voulez-vous supprimer ce bon ?', [
      { text: 'Annuler', style: 'cancel' },
      { text: 'Supprimer', style: 'destructive', onPress: async () => {
        setSubmitting(true);
        try { await api.delete(`/vouchers/${voucherId}`); Alert.alert('Supprimé'); navigation.goBack(); }
        catch (e) { Alert.alert('Erreur', 'Impossible de supprimer.'); }
        finally { setSubmitting(false); }
      } }
    ]);
  };

  const copyIdentifier = async () => {
    try {
      await Clipboard.setStringAsync(item.code);
      Alert.alert('Copié', 'L’identifiant du bon a été copié dans le presse-papiers.');
    } catch (e) {
      Alert.alert('Erreur', 'Impossible de copier l’identifiant du bon.');
    }
  };

  const toggleActive = async () => {
    if (!item) return;
    setSubmitting(true);
    try {
      await api.put(`/vouchers/${voucherId}`, { is_active: !item.is_active });
      await load();
    } catch (e) { Alert.alert('Erreur', 'Impossible de mettre à jour.'); }
    finally { setSubmitting(false); }
  };

  if (loading || !item) return <View style={styles.center}><ActivityIndicator color="#92400e" /></View>;

  return (
    <View style={styles.container}>
      <View style={styles.card}>
        <Text style={styles.code}>{item.code}</Text>
        <Text style={styles.meta}>{item.amount} € — {item.description ?? ''}</Text>
        <Text style={styles.meta}>Expire: {item.expires_at ? new Date(item.expires_at).toLocaleDateString() : '—'}</Text>
      </View>

      <TouchableOpacity style={styles.btn} onPress={() => navigation.navigate('CreateVoucher', { voucher: item })} disabled={submitting}>
        <Text style={styles.btnText}>Modifier</Text>
      </TouchableOpacity>
      <TouchableOpacity style={[styles.btn, styles.copyBtn]} onPress={copyIdentifier} disabled={submitting}>
        <Text style={[styles.btnText, styles.copyBtnText]}>Copier l'identifiant</Text>
      </TouchableOpacity>
      <TouchableOpacity style={[styles.btn, { backgroundColor: '#fff', borderWidth: 1, borderColor: '#ef4444' }]} onPress={remove} disabled={submitting}>
        <Text style={[styles.btnText, { color: '#ef4444' }]}>Supprimer</Text>
      </TouchableOpacity>

      <View style={{ marginTop: 12 }}>
        <View style={[styles.statusBadge, getVoucherStatus(item).container]}>
          <Text style={[styles.statusBadgeText, getVoucherStatus(item).text]}>{getVoucherStatus(item).label}</Text>
        </View>
        {item.restricted_card_id && <Text style={styles.meta}>Restreint à la carte #{item.restricted_card_id}</Text>}
        {item.restricted_name && <Text style={styles.meta}>Restreint à : {item.restricted_name}</Text>}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3', padding: 16 },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  card: { backgroundColor: '#fff', borderRadius: 12, padding: 12, marginBottom: 12 },
  code: { fontSize: 20, fontWeight: '700', color: '#1f2937' },
  meta: { color: '#6b7280', marginTop: 6 },
  btn: { backgroundColor: '#92400e', padding: 12, borderRadius: 10, alignItems: 'center', marginTop: 8 },
  btnText: { color: '#fff', fontWeight: '700' },
  copyBtn: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#92400e' },
  copyBtnText: { color: '#92400e' },
  statusBadge: { alignSelf: 'flex-start', borderRadius: 999, paddingHorizontal: 10, paddingVertical: 4, marginBottom: 6 },
  statusBadgeText: { fontSize: 12, fontWeight: '700' },
  statusUsed: { backgroundColor: '#fee2e2' },
  statusUsedText: { color: '#b91c1c' },
  statusExpired: { backgroundColor: '#fef3c7' },
  statusExpiredText: { color: '#92400e' },
  statusActive: { backgroundColor: '#dcfce7' },
  statusActiveText: { color: '#166534' },
});
