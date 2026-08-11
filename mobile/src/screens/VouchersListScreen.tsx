import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator, Alert } from 'react-native';
import api from '../api/client';
import { useNavigation } from '@react-navigation/native';

export default function VouchersListScreen() {
  const navigation = useNavigation<any>();
  const [items, setItems] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const load = async () => {
    setLoading(true);
    try {
      const { data } = await api.get('/vouchers');
      setItems(data);
    } catch (e) {
      Alert.alert('Erreur', 'Impossible de récupérer les bons.');
    } finally { setLoading(false); }
  };

  useEffect(() => { load(); }, []);

  if (loading) return (
    <View style={styles.center}><ActivityIndicator color="#92400e" size="large" /></View>
  );

  return (
    <View style={styles.container}>
      <FlatList
        data={items}
        keyExtractor={(i) => String(i.id)}
        contentContainerStyle={{ padding: 12 }}
        renderItem={({ item }) => (
          <TouchableOpacity style={styles.row} onPress={() => navigation.navigate('VoucherDetail', { voucherId: item.id })}>
            <View>
              <Text style={styles.title}>{item.code}</Text>
              <Text style={styles.meta}>{item.amount} € • Expire: {item.expires_at ? new Date(item.expires_at).toLocaleDateString() : '—'}</Text>
            </View>
            <Text style={styles.right}>{item.active ? 'Valide' : (item.is_used ? 'Utilisé' : 'Expiré')}</Text>
          </TouchableOpacity>
        )}
        ListEmptyComponent={<Text style={styles.empty}>Aucun bon trouvé.</Text>}
        onRefresh={load}
        refreshing={loading}
      />
      <TouchableOpacity style={styles.fab} onPress={() => navigation.navigate('CreateVoucher')}>
        <Text style={styles.fabText}>＋</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  row: { backgroundColor: '#fff', padding: 12, borderRadius: 10, marginBottom: 8, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  title: { fontWeight: '700', color: '#1f2937' },
  meta: { color: '#6b7280', marginTop: 4 },
  right: { color: '#9ca3af', fontWeight: '700' },
  empty: { textAlign: 'center', marginTop: 24, color: '#9ca3af' },
  fab: { position: 'absolute', right: 16, bottom: 20, width: 56, height: 56, borderRadius: 28, backgroundColor: '#92400e', justifyContent: 'center', alignItems: 'center', elevation: 6 },
  fabText: { color: '#fff', fontSize: 28, lineHeight: 28 },
});
