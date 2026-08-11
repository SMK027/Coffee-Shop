import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator, ScrollView } from 'react-native';
import api from '../api/client';
import { useNavigation } from '@react-navigation/native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { useAuth } from '../context/AuthContext';

export default function CreateLoyaltyDiscountScreen() {
  const navigation = useNavigation<any>();
  const { user } = useAuth();
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [pointsCost, setPointsCost] = useState('');
  const [discountType, setDiscountType] = useState<'fixed'|'percent'>('fixed');
  const [discountValue, setDiscountValue] = useState('');
  const [maxDiscountAmount, setMaxDiscountAmount] = useState('');
  const [isActive, setIsActive] = useState(true);
  const [employeeOnly, setEmployeeOnly] = useState(false);
  const [supervisorNumber, setSupervisorNumber] = useState('');
  const [supervisorPin, setSupervisorPin] = useState('');
  const [supervisorToken, setSupervisorToken] = useState('');
  const [scannerVisible, setScannerVisible] = useState(false);
  const [cameraPermission, requestCameraPermission] = useCameraPermissions();
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    if (!name.trim()) { Alert.alert('Erreur', 'Nom requis'); return; }
    if (!pointsCost || Number(pointsCost) < 1) { Alert.alert('Erreur', 'Coût en points invalide'); return; }
    setLoading(true);
    try {
      const payload: any = {
        name: name.trim(),
        description: description.trim() || undefined,
        points_cost: Number(pointsCost),
        discount_type: discountType,
        discount_value: Number(discountValue),
        max_discount_amount: maxDiscountAmount ? Number(maxDiscountAmount) : undefined,
        is_active: isActive,
        employee_only: employeeOnly,
      };
      if (!user?.global_role || user.global_role !== 'superadmin') {
        const normalizedToken = supervisorToken.replace(/\s+/g, '').trim();
        if (normalizedToken) {
          payload.supervisor_token = normalizedToken;
        } else {
          payload.supervisor_number = supervisorNumber;
          payload.supervisor_pin = supervisorPin;
        }
      }
      const { data } = await api.post('/loyalty-discounts', payload);
      Alert.alert('Succès', 'Réduction créée');
      navigation.goBack();
    } catch (e: any) {
      Alert.alert('Erreur', e.response?.data?.message ?? 'Impossible de créer la réduction.');
    } finally { setLoading(false); }
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
      <Text style={styles.title}>Créer une réduction personnalisée</Text>
      <View style={styles.card}>
        <Text style={styles.label}>Nom</Text>
        <TextInput style={styles.input} value={name} onChangeText={setName} />
        <Text style={styles.label}>Description (optionnelle)</Text>
        <TextInput style={styles.input} value={description} onChangeText={setDescription} />
        <Text style={styles.label}>Coût en points</Text>
        <TextInput style={styles.input} keyboardType="numeric" value={pointsCost} onChangeText={setPointsCost} />

        <Text style={styles.label}>Type</Text>
        <View style={{ flexDirection: 'row', gap: 8, marginBottom: 8 }}>
          {(['fixed','percent'] as const).map((t) => (
            <TouchableOpacity key={t} onPress={() => setDiscountType(t)} style={[styles.chip, discountType === t && styles.chipActive]}>
              <Text style={[styles.chipText, discountType === t && styles.chipTextActive]}>{t}</Text>
            </TouchableOpacity>
          ))}
        </View>

        <Text style={styles.label}>Valeur</Text>
        <TextInput style={styles.input} keyboardType="numeric" value={discountValue} onChangeText={setDiscountValue} />
        {discountType === 'percent' && (
          <>
            <Text style={styles.label}>Plafond (€) (optionnel)</Text>
            <TextInput style={styles.input} keyboardType="numeric" value={maxDiscountAmount} onChangeText={setMaxDiscountAmount} />
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
          {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.submitText}>Créer</Text>}
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
