import React, { useCallback, useEffect, useState } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator, Alert } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import api from '../api/client';

type SupervisorItem = {
  id: number;
  supervisor_number: string;
  is_active: boolean;
  created_at?: string | null;
};

export default function SupervisorsListScreen() {
  const navigation = useNavigation<any>();
  const [items, setItems] = useState<SupervisorItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    else setLoading(true);

    try {
      const { data } = await api.get('/supervisors');
      setItems(Array.isArray(data) ? data : []);
    } catch (e) {
      Alert.alert('Erreur', 'Impossible de charger les superviseurs.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    load(false);
  }, [load]);

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color="#92400e" size="large" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={items}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: 12 }}
        renderItem={({ item }) => (
          <TouchableOpacity
            style={[styles.row, !item.is_active && styles.rowDisabled]}
            onPress={() => navigation.navigate('SupervisorQr', { supervisorId: item.id, supervisorNumber: item.supervisor_number })}
          >
            <View style={{ flex: 1 }}>
              <Text style={styles.number}>{item.supervisor_number}</Text>
              <Text style={styles.meta}>
                {item.created_at ? `Créé le ${new Date(item.created_at).toLocaleDateString()}` : 'Date inconnue'}
              </Text>
            </View>
            <View style={[styles.badge, item.is_active ? styles.badgeActive : styles.badgeInactive]}>
              <Text style={[styles.badgeText, item.is_active ? styles.badgeTextActive : styles.badgeTextInactive]}>
                {item.is_active ? 'Actif' : 'Désactivé'}
              </Text>
            </View>
          </TouchableOpacity>
        )}
        ListEmptyComponent={<Text style={styles.empty}>Aucun superviseur rattaché.</Text>}
        onRefresh={() => load(true)}
        refreshing={refreshing}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#fdf8f3' },
  row: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  rowDisabled: { opacity: 0.65 },
  number: { color: '#1f2937', fontWeight: '700', fontSize: 16 },
  meta: { color: '#9ca3af', marginTop: 4, fontSize: 12 },
  badge: { borderRadius: 999, paddingHorizontal: 10, paddingVertical: 4 },
  badgeText: { fontSize: 12, fontWeight: '700' },
  badgeActive: { backgroundColor: '#dcfce7' },
  badgeTextActive: { color: '#166534' },
  badgeInactive: { backgroundColor: '#f3f4f6' },
  badgeTextInactive: { color: '#6b7280' },
  empty: { textAlign: 'center', marginTop: 24, color: '#9ca3af' },
});
