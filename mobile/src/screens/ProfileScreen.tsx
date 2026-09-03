import React from 'react';
import { View, Text, ScrollView, StyleSheet, TouchableOpacity, Alert } from 'react-native';
import { useAuth } from '../context/AuthContext';
import ServerManager from '../components/ServerManager';
import { useNavigation } from '@react-navigation/native';

const roleLabels: Record<string, string> = {
  superadmin: 'Super administrateur',
  admin: 'Administrateur',
  moderator: 'Modérateur',
  employee: 'Salarié',
  user: 'Utilisateur',
};

const roleColors: Record<string, string> = {
  superadmin: '#7c3aed',
  admin: '#92400e',
  moderator: '#2563eb',
  employee: '#0369a1',
  user: '#6b7280',
};

export default function ProfileScreen() {
  const { user, logout, isPermanentSupervisionEnabled } = useAuth();
  const navigation = useNavigation<any>();

  if (!user) {
    return (
      <View style={styles.center}>
        <Text style={styles.empty}>Aucun utilisateur connecté.</Text>
      </View>
    );
  }

  const initials = user.name
    .split(' ').filter(Boolean).slice(0, 2)
    .map((s) => s[0].toUpperCase()).join('');

  const confirmLogout = () => {
    Alert.alert('Déconnexion', 'Voulez-vous vraiment vous déconnecter ?', [
      { text: 'Annuler', style: 'cancel' },
      { text: 'Se déconnecter', style: 'destructive', onPress: logout },
    ]);
  };

  const roleLabel = roleLabels[user.global_role] ?? user.global_role;
  const roleColor = roleColors[user.global_role] ?? '#6b7280';
  const canAccessSupervisors = user.global_role === 'admin' || user.global_role === 'superadmin';

  return (
    <ScrollView style={styles.container} contentContainerStyle={{ padding: 16, paddingBottom: 40 }}>
      <View style={styles.avatarWrap}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{initials || '?'}</Text>
        </View>
        <Text style={styles.name}>{user.name}</Text>
        <View style={[styles.roleBadge, { backgroundColor: roleColor + '22' }]}>
          <Text style={[styles.roleBadgeText, { color: roleColor }]}>{roleLabel}</Text>
        </View>
      </View>

      <View style={styles.card}>
        <Field label="Nom complet" value={user.name} />
        {user.username && <Field label="Identifiant" value={user.username} />}
        <Field label="E-mail" value={user.email} />
        <Field label="Rôle" value={roleLabel} last />
      </View>

      <View style={styles.serverSection}>
        <ServerManager />
      </View>

      {user.global_role === 'superadmin' && (
        <TouchableOpacity style={styles.supervisionBtn} onPress={() => navigation.navigate('PermanentSupervision')}>
          <View>
            <Text style={styles.supervisionBtnTitle}>Mode superviseur permanent</Text>
            <Text style={styles.supervisionBtnSubtitle}>{isPermanentSupervisionEnabled ? 'Actif pour cette session' : 'Inactif'}</Text>
          </View>
          <Text style={[styles.supervisionStatus, isPermanentSupervisionEnabled ? styles.supervisionStatusActive : styles.supervisionStatusInactive]}>
            {isPermanentSupervisionEnabled ? 'Actif' : 'Gérer'}
          </Text>
        </TouchableOpacity>
      )}

      {canAccessSupervisors && (
        <TouchableOpacity style={styles.supervisorsBtn} onPress={() => navigation.navigate('SupervisorsList')}>
          <Text style={styles.supervisorsBtnText}>Consulter les superviseurs raccordés</Text>
        </TouchableOpacity>
      )}

      <TouchableOpacity style={styles.logoutBtn} onPress={confirmLogout}>
        <Text style={styles.logoutBtnText}>Se déconnecter</Text>
      </TouchableOpacity>
    </ScrollView>
  );
}

function Field({ label, value, last }: { label: string; value: string; last?: boolean }) {
  return (
    <View style={[styles.field, last && { borderBottomWidth: 0 }]}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <Text style={styles.fieldValue} selectable>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#fdf8f3' },
  empty: { color: '#9ca3af', fontSize: 15 },
  avatarWrap: { alignItems: 'center', paddingVertical: 24 },
  avatar: {
    width: 96, height: 96, borderRadius: 48, backgroundColor: '#92400e',
    justifyContent: 'center', alignItems: 'center', marginBottom: 12,
    shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.15, shadowRadius: 6, elevation: 4,
  },
  avatarText: { color: '#fff', fontSize: 36, fontWeight: '700' },
  name: { fontSize: 22, fontWeight: '700', color: '#1f2937', marginBottom: 6 },
  roleBadge: { paddingHorizontal: 12, paddingVertical: 4, borderRadius: 12 },
  roleBadgeText: { fontSize: 13, fontWeight: '600' },
  card: {
    backgroundColor: '#fff', borderRadius: 12, padding: 4, marginTop: 8,
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.06, shadowRadius: 4, elevation: 2,
  },
  field: { paddingHorizontal: 14, paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#f3f4f6' },
  fieldLabel: { fontSize: 12, color: '#9ca3af', textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 2 },
  fieldValue: { fontSize: 15, color: '#1f2937', fontWeight: '500' },
  serverSection: { marginTop: 24 },
  supervisionBtn: { backgroundColor: '#fff', borderRadius: 8, padding: 14, marginTop: 16, borderWidth: 1, borderColor: '#d1d5db', flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 },
  supervisionBtnTitle: { color: '#1f2937', fontSize: 15, fontWeight: '700' },
  supervisionBtnSubtitle: { color: '#6b7280', fontSize: 13, marginTop: 3 },
  supervisionStatus: { fontSize: 13, fontWeight: '700' },
  supervisionStatusActive: { color: '#15803d' },
  supervisionStatusInactive: { color: '#92400e' },
  supervisorsBtn: {
    backgroundColor: '#fff',
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 16,
    borderWidth: 1,
    borderColor: '#92400e',
  },
  supervisorsBtnText: { color: '#92400e', fontSize: 15, fontWeight: '700' },
  logoutBtn: {
    backgroundColor: '#fff', borderRadius: 12, paddingVertical: 14,
    alignItems: 'center', marginTop: 24, borderWidth: 1, borderColor: '#ef4444',
  },
  logoutBtnText: { color: '#ef4444', fontSize: 15, fontWeight: '700' },
});
