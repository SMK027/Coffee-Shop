import React, { useState } from 'react';
import {
  View, Text, StyleSheet, TouchableOpacity, Alert,
  TextInput, Modal, KeyboardAvoidingView, Platform, ScrollView,
} from 'react-native';
import { useServer } from '../context/ServerContext';
import { useAuth } from '../context/AuthContext';
import { Server } from '../api/servers';

type ModalMode = { type: 'add' } | { type: 'edit'; server: Server };

interface Props {
  onSelect?: (server: Server) => void; // callback optionnel après sélection
}

export default function ServerManager({ onSelect }: Props) {
  const { server, servers, setServer, addServer, updateServer, deleteServer } = useServer();
  const { logout } = useAuth();

  const [modalVisible, setModalVisible] = useState(false);
  const [modalMode, setModalMode] = useState<ModalMode>({ type: 'add' });
  const [formLabel, setFormLabel] = useState('');
  const [formUrl, setFormUrl] = useState('');
  const [formError, setFormError] = useState('');
  const [saving, setSaving] = useState(false);

  const openAdd = () => {
    setFormLabel(''); setFormUrl(''); setFormError('');
    setModalMode({ type: 'add' });
    setModalVisible(true);
  };

  const openEdit = (s: Server) => {
    setFormLabel(s.label); setFormUrl(s.url); setFormError('');
    setModalMode({ type: 'edit', server: s });
    setModalVisible(true);
  };

  const validateUrl = (url: string) => {
    try { new URL(url); return true; } catch { return false; }
  };

  const handleSave = async () => {
    const label = formLabel.trim();
    const url = formUrl.trim().replace(/\/$/, '');
    if (!label) { setFormError('Le nom est obligatoire.'); return; }
    if (!validateUrl(url)) { setFormError("L'URL est invalide (ex: http://192.168.1.10:8099)."); return; }
    setSaving(true);
    try {
      if (modalMode.type === 'add') {
        await addServer(label, url);
      } else {
        await updateServer(modalMode.server.id, label, url);
      }
      setModalVisible(false);
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = (s: Server) => {
    Alert.alert('Supprimer', `Supprimer le serveur « ${s.label} » ?`, [
      { text: 'Annuler', style: 'cancel' },
      { text: 'Supprimer', style: 'destructive', onPress: () => deleteServer(s.id) },
    ]);
  };

  const handleSelect = async (s: Server) => {
    const isSwitchingServer = s.id !== server.id;
    await setServer(s);

    // Un token d'authentification n'est valide que pour le serveur qui l'a émis :
    // le réutiliser sur un autre serveur pourrait authentifier le mauvais compte.
    if (isSwitchingServer) {
      await logout();
      Alert.alert('Serveur changé', 'Reconnectez-vous pour accéder à ce serveur.');
    }

    onSelect?.(s);
  };

  return (
    <>
      <View style={styles.sectionHeader}>
        <Text style={styles.sectionTitle}>Serveur</Text>
        <TouchableOpacity style={styles.addBtn} onPress={openAdd}>
          <Text style={styles.addBtnText}>+ Ajouter</Text>
        </TouchableOpacity>
      </View>

      <View style={styles.card}>
        {servers.map((s, i) => {
          const isActive = s.id === server.id;
          const isLast = i === servers.length - 1;
          return (
            <View key={s.id} style={[styles.serverRow, isLast && { borderBottomWidth: 0 }]}>
              <TouchableOpacity style={styles.serverRowMain} onPress={() => handleSelect(s)}>
                <View style={[styles.serverDot, isActive && styles.serverDotActive]} />
                <View style={{ flex: 1 }}>
                  <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                    <Text style={[styles.serverLabel, isActive && styles.serverLabelActive]}>
                      {s.label}
                    </Text>
                    {s.readonly && (
                      <View style={styles.lockedBadge}>
                        <Text style={styles.lockedBadgeText}>prod</Text>
                      </View>
                    )}
                    {isActive && (
                      <View style={styles.activeBadge}>
                        <Text style={styles.activeBadgeText}>actif</Text>
                      </View>
                    )}
                  </View>
                  <Text style={styles.serverUrl} numberOfLines={1}>{s.url}</Text>
                </View>
              </TouchableOpacity>
              {!s.readonly && (
                <View style={styles.serverActions}>
                  <TouchableOpacity style={styles.actionBtn} onPress={() => openEdit(s)}>
                    <Text style={styles.actionBtnText}>✏️</Text>
                  </TouchableOpacity>
                  <TouchableOpacity style={styles.actionBtn} onPress={() => handleDelete(s)}>
                    <Text style={styles.actionBtnText}>🗑️</Text>
                  </TouchableOpacity>
                </View>
              )}
            </View>
          );
        })}
      </View>

      <Modal visible={modalVisible} animationType="slide" presentationStyle="pageSheet">
        <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
          <View style={styles.modalContainer}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>
                {modalMode.type === 'add' ? 'Ajouter un serveur' : 'Modifier le serveur'}
              </Text>
              <TouchableOpacity onPress={() => setModalVisible(false)}>
                <Text style={styles.modalClose}>Annuler</Text>
              </TouchableOpacity>
            </View>
            <ScrollView contentContainerStyle={{ padding: 20 }} keyboardShouldPersistTaps="handled">
              <Text style={styles.inputLabel}>Nom du serveur</Text>
              <TextInput
                style={styles.input}
                placeholder="Ex. Bureau, Maison, Test…"
                placeholderTextColor="#9ca3af"
                value={formLabel}
                onChangeText={(v) => { setFormLabel(v); setFormError(''); }}
                autoFocus
              />
              <Text style={styles.inputLabel}>URL</Text>
              <TextInput
                style={styles.input}
                placeholder="http://192.168.1.10:8099"
                placeholderTextColor="#9ca3af"
                value={formUrl}
                onChangeText={(v) => { setFormUrl(v); setFormError(''); }}
                autoCapitalize="none"
                autoCorrect={false}
                keyboardType="url"
              />
              {formError ? <Text style={styles.formError}>{formError}</Text> : null}
              <TouchableOpacity
                style={[styles.saveBtn, saving && { opacity: 0.6 }]}
                onPress={handleSave}
                disabled={saving}
              >
                <Text style={styles.saveBtnText}>
                  {modalMode.type === 'add' ? 'Ajouter' : 'Enregistrer'}
                </Text>
              </TouchableOpacity>
            </ScrollView>
          </View>
        </KeyboardAvoidingView>
      </Modal>
    </>
  );
}

const styles = StyleSheet.create({
  sectionHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
  sectionTitle: { fontSize: 12, fontWeight: '700', color: '#6b7280', textTransform: 'uppercase', letterSpacing: 0.5 },
  addBtn: { backgroundColor: '#92400e', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 7 },
  addBtnText: { color: '#fff', fontSize: 12, fontWeight: '600' },

  card: {
    backgroundColor: '#fff', borderRadius: 12, padding: 4,
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.06, shadowRadius: 4, elevation: 2,
  },
  serverRow: { flexDirection: 'row', alignItems: 'center', borderBottomWidth: 1, borderBottomColor: '#f3f4f6' },
  serverRowMain: { flex: 1, flexDirection: 'row', alignItems: 'center', paddingHorizontal: 12, paddingVertical: 10, gap: 10 },
  serverDot: { width: 9, height: 9, borderRadius: 5, backgroundColor: '#d1d5db', flexShrink: 0 },
  serverDotActive: { backgroundColor: '#16a34a' },
  serverLabel: { fontSize: 14, fontWeight: '500', color: '#374151' },
  serverLabelActive: { color: '#92400e', fontWeight: '700' },
  serverUrl: { fontSize: 11, color: '#9ca3af', marginTop: 1 },
  lockedBadge: { backgroundColor: '#f3f4f6', borderRadius: 4, paddingHorizontal: 5, paddingVertical: 1 },
  lockedBadgeText: { fontSize: 10, color: '#6b7280', fontWeight: '600', textTransform: 'uppercase' },
  activeBadge: { backgroundColor: '#dcfce7', borderRadius: 4, paddingHorizontal: 5, paddingVertical: 1 },
  activeBadgeText: { fontSize: 10, color: '#16a34a', fontWeight: '700', textTransform: 'uppercase' },
  serverActions: { flexDirection: 'row', paddingRight: 6, gap: 0 },
  actionBtn: { padding: 8 },
  actionBtnText: { fontSize: 15 },

  modalContainer: { flex: 1, backgroundColor: '#fff' },
  modalHeader: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    padding: 16, borderBottomWidth: 1, borderBottomColor: '#e5e7eb',
  },
  modalTitle: { fontSize: 18, fontWeight: '700', color: '#1f2937' },
  modalClose: { fontSize: 16, color: '#92400e', fontWeight: '600' },
  inputLabel: { fontSize: 13, fontWeight: '600', color: '#6b7280', marginBottom: 6, marginTop: 4 },
  input: {
    borderWidth: 1, borderColor: '#d1d5db', borderRadius: 10,
    paddingHorizontal: 14, paddingVertical: 11, fontSize: 15,
    color: '#111827', backgroundColor: '#f9fafb', marginBottom: 14,
  },
  formError: { color: '#ef4444', fontSize: 13, marginBottom: 12 },
  saveBtn: { backgroundColor: '#92400e', borderRadius: 10, paddingVertical: 14, alignItems: 'center', marginTop: 4 },
  saveBtnText: { color: '#fff', fontSize: 16, fontWeight: '600' },
});
