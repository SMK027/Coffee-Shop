import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, TextInput, Alert, Image, ScrollView, ActivityIndicator } from 'react-native';
import { RouteProp, useRoute } from '@react-navigation/native';
import * as Clipboard from 'expo-clipboard';
import api from '../api/client';

type ParamList = {
  SupervisorQr: {
    supervisorId: number;
    supervisorNumber: string;
    superadminName?: string | null;
    holderAdminName?: string | null;
    relationType?: 'holder' | 'responsible' | 'visible';
  };
};

export default function SupervisorQrScreen() {
  const route = useRoute<RouteProp<ParamList, 'SupervisorQr'>>();
  const { supervisorId, supervisorNumber, superadminName, holderAdminName, relationType } = route.params;

  const [pin, setPin] = useState('');
  const [barcodeValue, setBarcodeValue] = useState<string | null>(null);
  const [details, setDetails] = useState<{ superadminName?: string | null; holderAdminName?: string | null } | null>(null);
  const [loading, setLoading] = useState(false);

  const fetchBarcode = async () => {
    if (!/^\d{4,6}$/.test(pin.trim())) {
      Alert.alert('Code invalide', 'Le code superviseur doit contenir entre 4 et 6 chiffres.');
      return;
    }

    setLoading(true);
    try {
      const { data } = await api.post(`/supervisors/${supervisorId}/barcode`, { supervisor_pin: pin.trim() });
      setBarcodeValue(data?.barcode_value ?? null);
      setDetails({
        superadminName: data?.superadmin_name ?? superadminName ?? null,
        holderAdminName: data?.holder_admin_name ?? holderAdminName ?? null,
      });
    } catch (e: any) {
      const message = e?.response?.data?.message || 'Impossible de vérifier le code superviseur.';
      Alert.alert('Accès refusé', message);
      setBarcodeValue(null);
    } finally {
      setLoading(false);
    }
  };

  const copyBarcode = async () => {
    if (!barcodeValue) return;
    try {
      await Clipboard.setStringAsync(barcodeValue);
      Alert.alert('Copié', 'Le code QR a été copié dans le presse-papiers.');
    } catch {
      Alert.alert('Erreur', 'Impossible de copier le code QR.');
    }
  };

  const qrUrl = barcodeValue
    ? `https://api.qrserver.com/v1/create-qr-code/?size=280x280&margin=0&data=${encodeURIComponent(barcodeValue)}`
    : null;

  const resolvedResponsible = details?.superadminName ?? superadminName ?? '—';
  const resolvedHolder = details?.holderAdminName ?? holderAdminName ?? 'Super administrateur';
  const relationLabel = relationType === 'holder'
    ? 'Vous êtes le détenteur de ce superviseur.'
    : relationType === 'responsible'
      ? 'Vous êtes le responsable de ce superviseur.'
      : null;

  return (
    <ScrollView style={styles.container} contentContainerStyle={{ padding: 16, paddingBottom: 40 }}>
      <View style={styles.card}>
        <Text style={styles.title}>Superviseur {supervisorNumber}</Text>
        <Text style={styles.subtitle}>Saisissez le code superviseur pour afficher le QR code.</Text>
        <View style={styles.infoBlock}>
          <Text style={styles.infoLine}>Responsable: {resolvedResponsible}</Text>
          <Text style={styles.infoLine}>Détenteur: {resolvedHolder}</Text>
          {relationLabel ? <Text style={styles.infoHint}>{relationLabel}</Text> : null}
        </View>

        <TextInput
          style={styles.input}
          value={pin}
          onChangeText={(value) => {
            setPin(value.replace(/[^\d]/g, '').slice(0, 6));
            if (barcodeValue) setBarcodeValue(null);
          }}
          placeholder="Code (4 à 6 chiffres)"
          keyboardType="number-pad"
          secureTextEntry
          maxLength={6}
        />

        <TouchableOpacity style={[styles.btn, loading && { opacity: 0.7 }]} onPress={fetchBarcode} disabled={loading}>
          <Text style={styles.btnText}>Afficher le QR code</Text>
        </TouchableOpacity>
      </View>

      {loading && (
        <View style={styles.loadingWrap}>
          <ActivityIndicator color="#92400e" />
        </View>
      )}

      {barcodeValue && qrUrl && (
        <View style={styles.qrCard}>
          <Text style={styles.qrTitle}>QR code de bypass</Text>
          <Image source={{ uri: qrUrl }} style={styles.qrImage} />
          <TouchableOpacity style={styles.copyBtn} onPress={copyBarcode}>
            <Text style={styles.copyBtnText}>Copier la valeur du QR</Text>
          </TouchableOpacity>
          <Text style={styles.barcodeValue} selectable>{barcodeValue}</Text>
        </View>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: '#ede7df',
  },
  title: { color: '#1f2937', fontSize: 18, fontWeight: '700' },
  subtitle: { color: '#6b7280', marginTop: 6, marginBottom: 12 },
  infoBlock: { backgroundColor: '#f9fafb', borderRadius: 10, padding: 10, marginBottom: 12 },
  infoLine: { color: '#374151', fontSize: 13, marginBottom: 4 },
  infoHint: { color: '#9a3412', fontSize: 12, marginTop: 2 },
  input: {
    borderWidth: 1,
    borderColor: '#e5e7eb',
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    marginBottom: 10,
    color: '#111827',
  },
  btn: {
    backgroundColor: '#92400e',
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
  },
  btnText: { color: '#fff', fontWeight: '700' },
  loadingWrap: { paddingVertical: 20, alignItems: 'center' },
  qrCard: {
    marginTop: 14,
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: '#ede7df',
    alignItems: 'center',
  },
  qrTitle: { color: '#1f2937', fontWeight: '700', marginBottom: 10 },
  qrImage: { width: 280, height: 280, borderRadius: 8, backgroundColor: '#fff' },
  copyBtn: {
    marginTop: 12,
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#92400e',
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  copyBtnText: { color: '#92400e', fontWeight: '700' },
  barcodeValue: { marginTop: 10, color: '#6b7280', fontSize: 12, textAlign: 'center' },
});
