import React, { useRef, useState } from 'react';
import { ActivityIndicator, Alert, Modal, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { useAuth } from '../context/AuthContext';

export default function PermanentSupervisionScreen() {
  const { isPermanentSupervisionEnabled, enablePermanentSupervision, disablePermanentSupervision } = useAuth();
  const [supervisorNumber, setSupervisorNumber] = useState('');
  const [supervisorPin, setSupervisorPin] = useState('');
  const [supervisorToken, setSupervisorToken] = useState('');
  const [scannerVisible, setScannerVisible] = useState(false);
  const [permission, requestPermission] = useCameraPermissions();
  const [loading, setLoading] = useState(false);
  const scannerLocked = useRef(false);

  const openScanner = async () => {
    if (!permission?.granted) {
      const result = await requestPermission();
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

  const enable = async () => {
    if (!supervisorToken && (!supervisorNumber.trim() || !supervisorPin.trim())) {
      Alert.alert('Authentification requise', 'Scannez un QR code superviseur ou renseignez son identifiant et son PIN.');
      return;
    }

    setLoading(true);
    try {
      await enablePermanentSupervision(
        supervisorToken
          ? { token: supervisorToken }
          : { number: supervisorNumber.trim(), pin: supervisorPin.trim() }
      );
      setSupervisorNumber('');
      setSupervisorPin('');
      setSupervisorToken('');
      Alert.alert('Mode activé', 'Les opérations sensibles ne demanderont plus d’authentification superviseur pour cette session.');
    } catch (error: any) {
      Alert.alert('Activation impossible', error?.response?.data?.message ?? 'L’authentification superviseur a échoué.');
    } finally {
      setLoading(false);
    }
  };

  const disable = () => {
    Alert.alert('Désactiver le mode', 'Les prochaines opérations sensibles demanderont à nouveau une authentification superviseur.', [
      { text: 'Annuler', style: 'cancel' },
      {
        text: 'Désactiver',
        style: 'destructive',
        onPress: async () => {
          setLoading(true);
          try {
            await disablePermanentSupervision();
          } catch {
            Alert.alert('Erreur', 'Impossible de désactiver le mode sur le serveur.');
          } finally {
            setLoading(false);
          }
        },
      },
    ]);
  };

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      {isPermanentSupervisionEnabled ? (
        <View style={[styles.card, styles.activeCard]}>
          <Text style={styles.activeTitle}>Mode actif</Text>
          <Text style={styles.activeText}>Les opérations sensibles ne demanderont pas d’authentification superviseur supplémentaire durant cette session.</Text>
          <TouchableOpacity style={styles.disableButton} onPress={disable} disabled={loading}>
            {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.disableButtonText}>Désactiver le mode</Text>}
          </TouchableOpacity>
        </View>
      ) : (
        <>
          <View style={[styles.card, styles.inactiveCard]}>
            <Text style={styles.inactiveTitle}>Mode inactif</Text>
            <Text style={styles.inactiveText}>Chaque opération sensible nécessite une authentification superviseur.</Text>
          </View>

          <View style={styles.card}>
            <Text style={styles.sectionTitle}>Activer le mode</Text>
            <Text style={styles.sectionText}>L’activation nécessite une authentification superviseur.</Text>
            <TouchableOpacity style={styles.scanButton} onPress={openScanner} disabled={loading}>
              <Text style={styles.scanButtonText}>Scanner le QR code superviseur</Text>
            </TouchableOpacity>
            {supervisorToken ? <Text style={styles.scanSuccess}>QR superviseur détecté.</Text> : null}
            <Text style={styles.label}>Identifiant superviseur</Text>
            <TextInput style={styles.input} value={supervisorNumber} onChangeText={setSupervisorNumber} autoCapitalize="none" editable={!loading} />
            <Text style={styles.label}>PIN superviseur</Text>
            <TextInput style={styles.input} value={supervisorPin} onChangeText={setSupervisorPin} secureTextEntry keyboardType="numeric" maxLength={6} editable={!loading} />
            <TouchableOpacity style={styles.enableButton} onPress={enable} disabled={loading}>
              {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.enableButtonText}>Activer le mode</Text>}
            </TouchableOpacity>
          </View>
        </>
      )}

      <Modal visible={scannerVisible} animationType="slide" presentationStyle="fullScreen">
        <View style={styles.scannerScreen}>
          <View style={styles.scannerHeader}>
            <Text style={styles.scannerTitle}>Scanner le superviseur</Text>
            <TouchableOpacity onPress={() => setScannerVisible(false)} style={styles.scannerCloseButton}>
              <Text style={styles.scannerCloseText}>Annuler</Text>
            </TouchableOpacity>
          </View>
          <CameraView style={styles.camera} facing="back" barcodeScannerSettings={{ barcodeTypes: ['qr'] }} onBarcodeScanned={onScanned} />
          <View style={styles.scannerHintWrap}>
            <Text style={styles.scannerHint}>Cadrez le QR code superviseur</Text>
          </View>
        </View>
      </Modal>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  content: { padding: 16, paddingBottom: 40, gap: 14 },
  card: { backgroundColor: '#fff', borderRadius: 8, padding: 16, borderWidth: 1, borderColor: '#ede7df' },
  activeCard: { backgroundColor: '#ecfdf5', borderColor: '#86efac' },
  inactiveCard: { backgroundColor: '#fffbeb', borderColor: '#fcd34d' },
  activeTitle: { color: '#166534', fontSize: 17, fontWeight: '700' },
  activeText: { color: '#166534', fontSize: 14, lineHeight: 21, marginTop: 8 },
  inactiveTitle: { color: '#92400e', fontSize: 17, fontWeight: '700' },
  inactiveText: { color: '#92400e', fontSize: 14, lineHeight: 21, marginTop: 8 },
  sectionTitle: { color: '#1f2937', fontSize: 17, fontWeight: '700' },
  sectionText: { color: '#6b7280', fontSize: 14, lineHeight: 20, marginTop: 6, marginBottom: 16 },
  label: { color: '#374151', fontSize: 13, fontWeight: '600', marginBottom: 6 },
  input: { borderWidth: 1, borderColor: '#d1d5db', borderRadius: 8, paddingHorizontal: 12, paddingVertical: 10, color: '#111827', marginBottom: 12 },
  scanButton: { borderWidth: 1, borderColor: '#d97706', borderRadius: 8, paddingVertical: 11, alignItems: 'center', marginBottom: 12 },
  scanButtonText: { color: '#92400e', fontWeight: '700' },
  scanSuccess: { color: '#15803d', fontSize: 13, fontWeight: '600', marginBottom: 12 },
  enableButton: { backgroundColor: '#92400e', borderRadius: 8, paddingVertical: 12, alignItems: 'center', marginTop: 4 },
  enableButtonText: { color: '#fff', fontWeight: '700' },
  disableButton: { backgroundColor: '#374151', borderRadius: 8, paddingVertical: 12, alignItems: 'center', marginTop: 16 },
  disableButtonText: { color: '#fff', fontWeight: '700' },
  scannerScreen: { flex: 1, backgroundColor: '#000' },
  scannerHeader: { paddingTop: 52, paddingHorizontal: 20, paddingBottom: 14, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  scannerTitle: { color: '#fff', fontSize: 18, fontWeight: '700' },
  scannerCloseButton: { paddingHorizontal: 12, paddingVertical: 8 },
  scannerCloseText: { color: '#fbbf24', fontSize: 16, fontWeight: '600' },
  camera: { flex: 1 },
  scannerHintWrap: { position: 'absolute', left: 0, right: 0, bottom: 44, alignItems: 'center' },
  scannerHint: { color: '#fff', fontSize: 14, fontWeight: '600' },
});
