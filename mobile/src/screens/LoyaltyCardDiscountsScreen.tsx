import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator, Alert } from 'react-native';
import { useRoute, RouteProp, useNavigation } from '@react-navigation/native';
import api from '../api/client';

type ParamList = { LoyaltyCardDiscounts: { cardId: number } };

export default function LoyaltyCardDiscountsScreen() {
  const route = useRoute<RouteProp<ParamList, 'LoyaltyCardDiscounts'>>();
  const navigation = useNavigation<any>();
  const { cardId } = route.params;
  const [items, setItems] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const load = async () => {
    setLoading(true);
    try {
      const { data } = await api.get(`/loyalty-cards/${cardId}/discounts`);
      setItems(data);
    } catch (e) { Alert.alert('Erreur', 'Impossible de charger les réductions.'); }
    finally { setLoading(false); }
  };

  useEffect(() => { load(); }, []);

  const remove = (id: number) => {
    Alert.alert('Supprimer', 'Supprimer cette réduction ?', [
      { text: 'Annuler', style: 'cancel' },
      { text: 'Supprimer', style: 'destructive', onPress: async () => {
        try { await api.delete(`/loyalty-discounts/${id}`); Alert.alert('Supprimé'); load(); }
        catch { Alert.alert('Erreur', 'Impossible de supprimer.'); }
      } }
    ]);
  };

  if (loading) return <View style={styles.center}><ActivityIndicator color="#92400e" size="large" /></View>;

  return (
    <View style={styles.container}>
      <FlatList
        data={items}
        keyExtractor={(i) => String(i.id)}
        contentContainerStyle={{ padding: 12 }}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View>
              <Text style={styles.title}>{item.name}</Text>
              <Text style={styles.meta}>{item.points_cost} pts — {item.discount_type === 'percent' ? `${item.discount_value}%` : `${item.discount_value}€`}</Text>
            </View>
            <View style={{ alignItems: 'flex-end' }}>
              <TouchableOpacity style={styles.smallBtn} onPress={() => navigation.navigate('CreateLoyaltyDiscount', { discount: item })}>
                <Text style={styles.smallBtnText}>Modifier</Text>
              </TouchableOpacity>
              <TouchableOpacity style={[styles.smallBtn, { marginTop: 6, backgroundColor: '#fff', borderWidth: 1, borderColor: '#ef4444' }]} onPress={() => remove(item.id)}>
                <Text style={[styles.smallBtnText, { color: '#ef4444' }]}>Supprimer</Text>
              </TouchableOpacity>
            </View>
          </View>
        )}
        ListEmptyComponent={<Text style={styles.empty}>Aucune réduction définie.</Text>}
      />
      <TouchableOpacity style={styles.fab} onPress={() => navigation.navigate('CreateLoyaltyDiscount', { cardId })}>
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
  smallBtn: { backgroundColor: '#92400e', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 8 },
  smallBtnText: { color: '#fff', fontWeight: '700' },
  empty: { textAlign: 'center', marginTop: 24, color: '#9ca3af' },
  fab: { position: 'absolute', right: 16, bottom: 20, width: 56, height: 56, borderRadius: 28, backgroundColor: '#92400e', justifyContent: 'center', alignItems: 'center', elevation: 6 },
  fabText: { color: '#fff', fontSize: 28, lineHeight: 28 },
});
