import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ActivityIndicator, TouchableOpacity, Alert } from 'react-native';
import { useRoute, useNavigation, RouteProp } from '@react-navigation/native';
import api from '../api/client';

type ParamList = { VoucherDetail: { voucherId: number } };

export default function VoucherDetailScreen() {
  const route = useRoute<RouteProp<ParamList, 'VoucherDetail'>>();
  const navigation = useNavigation<any>();
  const { voucherId } = route.params;
  const [item, setItem] = useState<any|null>(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);

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
      <TouchableOpacity style={[styles.btn, { backgroundColor: '#fff', borderWidth: 1, borderColor: '#ef4444' }]} onPress={remove} disabled={submitting}>
        <Text style={[styles.btnText, { color: '#ef4444' }]}>Supprimer</Text>
      </TouchableOpacity>

      <View style={{ marginTop: 12 }}>
        <Text style={{ color: item.active ? '#16a34a' : '#ef4444', fontWeight: '700' }}>{item.active ? 'Valide' : (item.is_used ? 'Utilisé' : 'Expiré')}</Text>
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
});
