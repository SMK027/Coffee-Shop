import React, { useRef, useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator, ScrollView, Modal } from 'react-native';
import api from '../api/client';
import { useNavigation } from '@react-navigation/native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { useAuth } from '../context/AuthContext';

export default function CreateLoyaltyDiscountScreen() {
  const navigation = useNavigation<any>();
  const { isPermanentSupervisionEnabled } = useAuth();
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
  const scannerLocked = useRef(false);
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
      if (!isPermanentSupervisionEnabled) {
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
    scannerLocked.current = false;
    setScannerVisible(true);
  };

  const onScanned = ({ data }: { data: string }) => {
    if (scannerLocked.current) return;
    const token = data.replace(/\s+/g, '').trim();
    if (!token) return;
    scannerLocked.current = true;
    setSupervisorToken(token);
    setScannerVisible(false);
  };

  return (
    <ScrollView contentContainerStyle={{ padding: 16 }} style={{ flex: 1, backgroundColor: '#fdf8f3' }}>
      <Text style={styles.title}>Créer une réduction personnalisée</Text>
      <View style={styles.card}>
        <Text style={styles.label}>Nom</Text>
        <TextInput style={styles.input} value={name} onChangeText={setName} placeholderTextColor="#9ca3af" selectionColor="#92400e" />
        <Text style={styles.label}>Description (optionnelle)</Text>
        <TextInput style={styles.input} value={description} onChangeText={setDescription} placeholderTextColor="#9ca3af" selectionColor="#92400e" />
        <Text style={styles.label}>Coût en points</Text>
        <TextInput style={styles.input} keyboardType="numeric" value={pointsCost} onChangeText={setPointsCost} placeholderTextColor="#9ca3af" selectionColor="#92400e" />

        <Text style={styles.label}>Type</Text>
        <View style={{ flexDirection: 'row', gap: 8, marginBottom: 8 }}>
          {(['fixed','percent'] as const).map((t) => (
            <TouchableOpacity key={t} onPress={() => setDiscountType(t)} style={[styles.chip, discountType === t && styles.chipActive]}>
              <Text style={[styles.chipText, discountType === t && styles.chipTextActive]}>{t}</Text>
            </TouchableOpacity>
          ))}
        </View>

        <Text style={styles.label}>Valeur</Text>
        <TextInput style={styles.input} keyboardType="numeric" value={discountValue} onChangeText={setDiscountValue} placeholderTextColor="#9ca3af" selectionColor="#92400e" />
        {discountType === 'percent' && (
          <>
            <Text style={styles.label}>Plafond (€) (optionnel)</Text>
            <TextInput style={styles.input} keyboardType="numeric" value={maxDiscountAmount} onChangeText={setMaxDiscountAmount} placeholderTextColor="#9ca3af" selectionColor="#92400e" />
          </>
        )}

        <>
            <TouchableOpacity style={styles.scanBtn} onPress={openScanner}>
              <Text style={styles.scanBtnText}>Scanner le QR code superviseur</Text>
            </TouchableOpacity>
            {supervisorToken ? <Text style={styles.scanSuccess}>Code superviseur scanné.</Text> : null}
            <Text style={styles.label}>Identifiant du superviseur</Text>
            <TextInput style={styles.input} value={supervisorNumber} onChangeText={setSupervisorNumber} placeholderTextColor="#9ca3af" selectionColor="#92400e" />
            <Text style={styles.label}>Mot de passe du superviseur</Text>
            <TextInput style={styles.input} value={supervisorPin} onChangeText={setSupervisorPin} secureTextEntry keyboardType="numeric" placeholderTextColor="#9ca3af" selectionColor="#92400e" />
        </>

        <TouchableOpacity style={styles.submit} onPress={submit} disabled={loading}>
          {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.submitText}>Créer</Text>}
        </TouchableOpacity>
      </View>

      <View />

      <Modal visible={scannerVisible} animationType="slide" presentationStyle="fullScreen">
        <View style={{ flex: 1, backgroundColor: '#000' }}>
          <View style={styles.scannerHeader}>
            <Text style={styles.scannerTitle}>Scanner le code superviseur</Text>
            <TouchableOpacity onPress={() => setScannerVisible(false)} style={styles.scannerCloseBtn}>
              <Text style={styles.scannerCloseText}>Annuler</Text>
            </TouchableOpacity>
          </View>
          <CameraView
            style={{ flex: 1 }}
            facing="back"
            barcodeScannerSettings={{ barcodeTypes: ['qr', 'code128'] }}
            onBarcodeScanned={onScanned}
          />
          <View style={styles.scannerOverlay}>
            <View style={styles.scannerFrame} />
            <Text style={styles.scannerHint}>Pointez vers le QR code superviseur</Text>
          </View>
        </View>
      </Modal>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  title: { fontSize: 20, fontWeight: '700', marginBottom: 12, color: '#1f2937' },
  card: { backgroundColor: '#fff', borderRadius: 10, padding: 12, shadowColor: '#000', shadowOpacity: 0.05, elevation: 2 },
  label: { fontSize: 13, color: '#6b7280', marginBottom: 6 },
  input: { borderWidth: 1, borderColor: '#e5e7eb', padding: 10, borderRadius: 8, marginBottom: 10, backgroundColor: '#f9fafb', color: '#111827' },
  chip: { paddingVertical: 8, paddingHorizontal: 12, borderRadius: 8, backgroundColor: '#f3f4f6' },
  chipActive: { backgroundColor: '#92400e' },
  chipText: { color: '#1f2937' },
  chipTextActive: { color: '#fff' },
  scanBtn: { backgroundColor: '#fff7ed', borderColor: '#fdba74', borderWidth: 1, padding: 10, borderRadius: 8, marginBottom: 10, alignItems: 'center' },
  scanBtnText: { color: '#9a3412', fontWeight: '700' },
  scanSuccess: { color: '#15803d', fontSize: 13, marginBottom: 8 },
  scannerHeader: { paddingTop: 52, paddingHorizontal: 20, paddingBottom: 14, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  scannerTitle: { color: '#fff', fontSize: 18, fontWeight: '700' },
  scannerCloseBtn: { paddingVertical: 6, paddingHorizontal: 10, borderRadius: 8, backgroundColor: 'rgba(0,0,0,0.25)' },
  scannerCloseText: { color: '#fbbf24', fontSize: 16, fontWeight: '600' },
  scannerOverlay: { position: 'absolute', left: 0, right: 0, bottom: 44, alignItems: 'center' },
  scannerFrame: { width: 240, height: 240, borderWidth: 2, borderColor: '#fbbf24', borderRadius: 16, backgroundColor: 'transparent' },
  scannerHint: { color: '#fff', marginTop: 14, fontSize: 14, fontWeight: '500' },
  submit: { backgroundColor: '#92400e', padding: 12, borderRadius: 8, alignItems: 'center', marginTop: 8 },
  submitText: { color: '#fff', fontWeight: '700' },
});
