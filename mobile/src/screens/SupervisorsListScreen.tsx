import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { View, Text, SectionList, TouchableOpacity, StyleSheet, ActivityIndicator, Alert } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import api from '../api/client';

type SupervisorItem = {
  id: number;
  supervisor_number: string;
  is_active: boolean;
  superadmin_name?: string | null;
  holder_admin_name?: string | null;
  relation_type?: 'holder' | 'responsible' | 'visible';
  created_at?: string | null;
};

export default function SupervisorsListScreen() {
  const navigation = useNavigation<any>();
  const [items, setItems] = useState<SupervisorItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const sections = useMemo(() => {
    const byHolder = items.filter((item) => item.relation_type === 'holder');
    const byResponsible = items.filter((item) => item.relation_type === 'responsible');
    const fallback = items.filter((item) => item.relation_type !== 'holder' && item.relation_type !== 'responsible');
    return [
      { title: 'Superviseurs dont vous êtes le détenteur', data: byHolder },
      { title: 'Superviseurs dont vous êtes le responsable', data: byResponsible },
      { title: 'Autres superviseurs accessibles', data: fallback },
    ].filter((section) => section.data.length > 0);
  }, [items]);

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
      <SectionList
        sections={sections}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: 12 }}
        renderSectionHeader={({ section }) => (
          <Text style={styles.sectionTitle}>{section.title}</Text>
        )}
        renderItem={({ item }) => (
          <TouchableOpacity
            style={[styles.row, !item.is_active && styles.rowDisabled]}
            onPress={() => navigation.navigate('SupervisorQr', {
              supervisorId: item.id,
              supervisorNumber: item.supervisor_number,
              superadminName: item.superadmin_name,
              holderAdminName: item.holder_admin_name,
              relationType: item.relation_type,
            })}
          >
            <View style={{ flex: 1 }}>
              <Text style={styles.number}>{item.supervisor_number}</Text>
              <Text style={styles.meta}>
                {item.created_at ? `Créé le ${new Date(item.created_at).toLocaleDateString()}` : 'Date inconnue'}
              </Text>
              <Text style={styles.meta}>Responsable: {item.superadmin_name ?? '—'}</Text>
              <Text style={styles.meta}>Détenteur: {item.holder_admin_name ?? 'Super administrateur'}</Text>
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
        stickySectionHeadersEnabled={false}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#fdf8f3' },
  sectionTitle: { color: '#78350f', fontSize: 13, fontWeight: '700', marginBottom: 8, marginTop: 6 },
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
