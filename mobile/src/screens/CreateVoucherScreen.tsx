import React, { useEffect, useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator, ScrollView } from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import api from '../api/client';
import { useAuth } from '../context/AuthContext';

export default function CreateVoucherScreen() {
  const navigation = useNavigation<any>();
  const route = useRoute<any>();
  const { user } = useAuth();
  const voucher = route.params?.voucher ?? null;
  const isEditing = !!voucher?.id;
  const [amount, setAmount] = useState('');
  const [validityDays, setValidityDays] = useState('7');
  const [expiresAt, setExpiresAt] = useState('');
  const [restrictionType, setRestrictionType] = useState<'none'|'card'|'name'>('none');
  const [restrictedCardNumber, setRestrictedCardNumber] = useState('');
  const [restrictedName, setRestrictedName] = useState('');
  const [supervisorNumber, setSupervisorNumber] = useState('');
  const [supervisorPin, setSupervisorPin] = useState('');
  const [supervisorToken, setSupervisorToken] = useState('');
  const [scannerVisible, setScannerVisible] = useState(false);
  const [cameraPermission, requestCameraPermission] = useCameraPermissions();
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!voucher) return;

    setAmount(String(voucher.amount ?? ''));
    setRestrictionType(voucher.restricted_card_id ? 'card' : (voucher.restricted_name ? 'name' : 'none'));
    setRestrictedCardNumber(voucher.restricted_card_number ?? '');
    setRestrictedName(voucher.restricted_name ?? '');
    setExpiresAt(voucher.expires_at ?? '');
    if (voucher.expires_at) {
      const today = new Date();
      const expires = new Date(voucher.expires_at);
      const diff = Math.max(1, Math.ceil((expires.getTime() - today.getTime()) / (1000 * 60 * 60 * 24)));
      setValidityDays(String(diff));
    }
  }, [voucher]);

  const submit = async () => {
    if (!amount || Number(amount) <= 0) {
      Alert.alert('Erreur', 'Montant invalide.');
      return;
    }
    setLoading(true);
    try {
      const payload: any = {
        amount: Number(amount),
        restriction_type: restrictionType,
      };

      if (!isEditing) {
        payload.validity_days = Number(validityDays);
      } else {
        payload.expires_at = expiresAt;
      }

      if (restrictionType === 'card') payload.restricted_card_number = restrictedCardNumber.trim();
      if (restrictionType === 'name') payload.restricted_name = restrictedName.trim();
      if (!user?.global_role || user.global_role !== 'superadmin') {
        const normalizedToken = supervisorToken.replace(/\s+/g, '').trim();
        if (normalizedToken) {
          payload.supervisor_token = normalizedToken;
        } else {
          payload.supervisor_number = supervisorNumber;
          payload.supervisor_pin = supervisorPin;
        }
      }

      const { data } = isEditing
        ? await api.put(`/vouchers/${voucher.id}`, payload)
        : await api.post('/vouchers', payload);

      Alert.alert('Succès', isEditing ? 'Bon mis à jour.' : `Bon créé : ${data.code}`);
      navigation.goBack();
    } catch (e: any) {
      Alert.alert('Erreur', e.response?.data?.message ?? (isEditing ? 'Impossible de modifier le bon.' : 'Impossible de créer le bon.'));
    } finally {
      setLoading(false);
    }
  };

  const openScanner = async () => {
    if (!cameraPermission?.granted) {
      const result = await requestCameraPermission();
      if (!result.granted) {
        Alert.alert('Permission refusée', "L'accès à la caméra est requis pour scanner le QR code superviseur.");
        return;
      }
    }
    setScannerVisible(true);
  };

  const onScanned = ({ data }: { data: string }) => {
    const token = data.replace(/\s+/g, '').trim();
    if (!token) return;
    setSupervisorToken(token);
    setScannerVisible(false);
  };

  return (
    <ScrollView contentContainerStyle={{ padding: 16 }} style={{ flex: 1, backgroundColor: '#fdf8f3' }}>
      <Text style={styles.title}>{isEditing ? 'Modifier un bon d\'achat' : 'Créer un bon d\'achat'}</Text>

      <View style={styles.card}>
        <Text style={styles.label}>Montant (€)</Text>
        <TextInput style={styles.input} keyboardType="numeric" value={amount} onChangeText={setAmount} />

        {!isEditing && (
          <>
            <Text style={styles.label}>Validité (jours)</Text>
            <TextInput style={styles.input} keyboardType="numeric" value={validityDays} onChangeText={setValidityDays} />
          </>
        )}

        {isEditing && (
          <>
            <Text style={styles.label}>Date d'expiration</Text>
            <TextInput style={styles.input} value={expiresAt} onChangeText={setExpiresAt} placeholder="AAAA-MM-JJ" />
          </>
        )}

        <Text style={styles.label}>Restriction d'utilisation</Text>
        <View style={{ flexDirection: 'row', gap: 8, marginBottom: 8 }}>
          {(['none','card','name'] as const).map((t) => (
            <TouchableOpacity key={t} onPress={() => setRestrictionType(t)} style={[styles.chip, restrictionType === t && styles.chipActive]}>
              <Text style={[styles.chipText, restrictionType === t && styles.chipTextActive]}>{t === 'none' ? 'Aucune' : t === 'card' ? 'Carte' : 'Nom'}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {restrictionType === 'card' && (
          <>
            <Text style={styles.label}>Numéro de carte (sans espaces)</Text>
            <TextInput style={styles.input} value={restrictedCardNumber} onChangeText={setRestrictedCardNumber} placeholder="Ex. 1234-5678-9012" />
          </>
        )}

        {restrictionType === 'name' && (
          <>
            <Text style={styles.label}>Nom complet du bénéficiaire</Text>
            <TextInput style={styles.input} value={restrictedName} onChangeText={setRestrictedName} placeholder="Ex. Jean Dupont" />
          </>
        )}

        {!user?.global_role || user.global_role !== 'superadmin' ? (
          <>
            <Text style={styles.label}>QR code superviseur (optionnel)</Text>
            <TextInput style={styles.input} value={supervisorToken} onChangeText={setSupervisorToken} placeholder="SUPERVISOR:..." autoCapitalize="none" />
            <TouchableOpacity style={styles.scanBtn} onPress={openScanner}>
              <Text style={styles.scanBtnText}>Scanner le QR code superviseur</Text>
            </TouchableOpacity>
            <Text style={styles.label}>Identifiant du superviseur</Text>
            <TextInput style={styles.input} value={supervisorNumber} onChangeText={setSupervisorNumber} />
            <Text style={styles.label}>Mot de passe du superviseur</Text>
            <TextInput style={styles.input} value={supervisorPin} onChangeText={setSupervisorPin} secureTextEntry keyboardType="numeric" />
          </>
        ) : null}

        <TouchableOpacity style={styles.submit} onPress={submit} disabled={loading}>
          {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.submitText}>{isEditing ? 'Mettre à jour' : 'Créer'}</Text>}
        </TouchableOpacity>
      </View>

      <View>
        {scannerVisible ? (
          <View style={styles.scannerWrap}>
            <CameraView
              style={styles.scanner}
              facing="back"
              barcodeScannerSettings={{ barcodeTypes: ['qr', 'code128'] }}
              onBarcodeScanned={onScanned}
            />
            <TouchableOpacity style={styles.scanCancelBtn} onPress={() => setScannerVisible(false)}>
              <Text style={styles.scanCancelText}>Annuler le scan</Text>
            </TouchableOpacity>
          </View>
        ) : null}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  title: { fontSize: 20, fontWeight: '700', marginBottom: 12, color: '#1f2937' },
  card: { backgroundColor: '#fff', borderRadius: 10, padding: 12, shadowColor: '#000', shadowOpacity: 0.05, elevation: 2 },
  label: { fontSize: 13, color: '#6b7280', marginBottom: 6 },
  input: { borderWidth: 1, borderColor: '#e5e7eb', padding: 10, borderRadius: 8, marginBottom: 10, backgroundColor: '#f9fafb' },
  chip: { paddingVertical: 8, paddingHorizontal: 12, borderRadius: 8, backgroundColor: '#f3f4f6' },
  chipActive: { backgroundColor: '#92400e' },
  chipText: { color: '#1f2937' },
  chipTextActive: { color: '#fff' },
  scanBtn: { backgroundColor: '#fff7ed', borderColor: '#fdba74', borderWidth: 1, padding: 10, borderRadius: 8, marginBottom: 10, alignItems: 'center' },
  scanBtnText: { color: '#9a3412', fontWeight: '700' },
  scannerWrap: { marginTop: 12, borderRadius: 10, overflow: 'hidden', borderWidth: 1, borderColor: '#e5e7eb' },
  scanner: { width: '100%', height: 280 },
  scanCancelBtn: { backgroundColor: '#111827', padding: 10, alignItems: 'center' },
  scanCancelText: { color: '#fff', fontWeight: '600' },
  submit: { backgroundColor: '#92400e', padding: 12, borderRadius: 8, alignItems: 'center', marginTop: 8 },
  submitText: { color: '#fff', fontWeight: '700' },
});
