import React, { useRef, useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, StyleSheet,
  ActivityIndicator, KeyboardAvoidingView, Platform, Alert, ScrollView, Modal,
} from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { useAuth } from '../context/AuthContext';
import ServerManager from '../components/ServerManager';
import api from '../api/client';

export default function LoginScreen() {
  const { login, loginWithQr } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [showServers, setShowServers] = useState(false);
  const [loginMode, setLoginMode] = useState<'password' | 'qr'>('password');

  // Connexion par QR code : 1) scan utilisateur, 2) vérification en base, 3) authentification superviseur
  const [qrUserToken, setQrUserToken] = useState('');
  const [qrIdentifiedName, setQrIdentifiedName] = useState<string | null>(null);
  const [qrIdentifying, setQrIdentifying] = useState(false);
  const [qrSupervisorNumber, setQrSupervisorNumber] = useState('');
  const [qrSupervisorPin, setQrSupervisorPin] = useState('');
  const [qrSupervisorToken, setQrSupervisorToken] = useState('');
  const [qrScannerVisible, setQrScannerVisible] = useState(false);
  const [qrScannerTarget, setQrScannerTarget] = useState<'user' | 'supervisor'>('user');
  const [cameraPermission, requestCameraPermission] = useCameraPermissions();
  const scannerLocked = useRef(false);

  const handleLogin = async () => {
    if (!email.trim() || !password) {
      Alert.alert('Erreur', 'Veuillez saisir votre email et votre mot de passe.');
      return;
    }
    setLoading(true);
    try {
      await login(email.trim(), password);
    } catch (err: any) {
      const status = err?.response?.status;
      const data = err?.response?.data;
      const serverMsg = data?.message;
      let msg: string;
      if (status === 401) {
        msg = 'Identifiants incorrects.';
      } else if (status === 403) {
        msg = serverMsg ?? 'Accès refusé.';
      } else if (status === 422 && data?.errors) {
        msg = Object.values(data.errors).flat().join('\n');
      } else if (status) {
        msg = `Erreur ${status}${serverMsg ? ' : ' + serverMsg : ''}`;
      } else if (err?.message) {
        msg = `Impossible de joindre le serveur.\n${err.message}`;
      } else {
        msg = 'Erreur inconnue.';
      }
      Alert.alert('Connexion échouée', msg);
    } finally {
      setLoading(false);
    }
  };

  const resetQrState = () => {
    setQrUserToken('');
    setQrIdentifiedName(null);
    setQrSupervisorNumber('');
    setQrSupervisorPin('');
    setQrSupervisorToken('');
  };

  const openQrScanner = async (target: 'user' | 'supervisor') => {
    if (!cameraPermission?.granted) {
      const result = await requestCameraPermission();
      if (!result.granted) {
        Alert.alert('Permission refusée', "L'accès à la caméra est requis pour scanner le QR code.");
        return;
      }
    }
    scannerLocked.current = false;
    setQrScannerTarget(target);
    setQrScannerVisible(true);
  };

  const identifyQrUser = async (token: string) => {
    setQrIdentifying(true);
    try {
      const { data } = await api.post('/auth/login/qr/identifier', { token });
      setQrIdentifiedName(data.name);
    } catch (e: any) {
      Alert.alert('QR code invalide', e?.response?.data?.message ?? 'Impossible de vérifier ce QR code.');
      setQrUserToken('');
    } finally {
      setQrIdentifying(false);
    }
  };

  const onQrScanned = ({ data }: { data: string }) => {
    if (scannerLocked.current) return;
    const value = data.trim();
    if (!value) return;
    scannerLocked.current = true;
    setQrScannerVisible(false);

    if (qrScannerTarget === 'user') {
      setQrUserToken(value);
      identifyQrUser(value);
    } else {
      setQrSupervisorToken(value);
    }
  };

  const submitQrLogin = async () => {
    if (!qrSupervisorToken && (!qrSupervisorNumber.trim() || !qrSupervisorPin.trim())) {
      Alert.alert('Authentification requise', 'Scannez le QR code superviseur ou renseignez son identifiant et son PIN.');
      return;
    }
    setLoading(true);
    try {
      await loginWithQr(
        qrUserToken,
        qrSupervisorToken
          ? { token: qrSupervisorToken }
          : { number: qrSupervisorNumber.trim(), pin: qrSupervisorPin.trim() }
      );
    } catch (err: any) {
      const message = err?.response?.data?.message ?? 'Connexion par QR code impossible.';
      Alert.alert('Connexion échouée', message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView
        contentContainerStyle={styles.scroll}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.card}>
          <Text style={styles.title}>☕ Coffee Shop</Text>
          <Text style={styles.subtitle}>Espace salarié</Text>

          {loginMode === 'password' ? (
            <>
              <TextInput
                style={styles.input}
                placeholder="Adresse e-mail"
                placeholderTextColor="#9ca3af"
                autoCapitalize="none"
                keyboardType="email-address"
                textContentType="emailAddress"
                value={email}
                onChangeText={setEmail}
              />
              <TextInput
                style={styles.input}
                placeholder="Mot de passe"
                placeholderTextColor="#9ca3af"
                secureTextEntry
                textContentType="password"
                value={password}
                onChangeText={setPassword}
                onSubmitEditing={handleLogin}
              />

              <TouchableOpacity
                style={[styles.button, loading && styles.buttonDisabled]}
                onPress={handleLogin}
                disabled={loading}
              >
                {loading ? (
                  <ActivityIndicator color="#fff" />
                ) : (
                  <Text style={styles.buttonText}>Se connecter</Text>
                )}
              </TouchableOpacity>

              <TouchableOpacity
                style={styles.qrModeToggle}
                onPress={() => { resetQrState(); setLoginMode('qr'); }}
              >
                <Text style={styles.qrModeToggleText}>📷 Connexion par QR code</Text>
              </TouchableOpacity>
            </>
          ) : (
            <>
              <Text style={styles.qrStepTitle}>1. Scanner votre QR code personnel</Text>
              <TouchableOpacity style={styles.qrScanBtn} onPress={() => openQrScanner('user')} disabled={qrIdentifying}>
                <Text style={styles.qrScanBtnText}>{qrIdentifying ? 'Vérification...' : 'Scanner mon QR code'}</Text>
              </TouchableOpacity>

              {qrIdentifiedName && (
                <>
                  <View style={styles.qrIdentifiedBox}>
                    <Text style={styles.qrIdentifiedText}>2. Compte vérifié : {qrIdentifiedName}</Text>
                  </View>

                  <Text style={styles.qrStepTitle}>3. Authentification superviseur obligatoire</Text>
                  <TouchableOpacity style={styles.qrScanBtn} onPress={() => openQrScanner('supervisor')}>
                    <Text style={styles.qrScanBtnText}>Scanner le QR superviseur</Text>
                  </TouchableOpacity>
                  {qrSupervisorToken ? <Text style={styles.qrScanSuccess}>QR superviseur détecté.</Text> : null}

                  <TextInput
                    style={styles.input}
                    placeholder="Identifiant superviseur"
                    placeholderTextColor="#9ca3af"
                    autoCapitalize="none"
                    value={qrSupervisorNumber}
                    onChangeText={setQrSupervisorNumber}
                  />
                  <TextInput
                    style={styles.input}
                    placeholder="PIN superviseur"
                    placeholderTextColor="#9ca3af"
                    secureTextEntry
                    keyboardType="number-pad"
                    maxLength={6}
                    value={qrSupervisorPin}
                    onChangeText={setQrSupervisorPin}
                  />

                  <TouchableOpacity
                    style={[styles.button, loading && styles.buttonDisabled]}
                    onPress={submitQrLogin}
                    disabled={loading}
                  >
                    {loading ? (
                      <ActivityIndicator color="#fff" />
                    ) : (
                      <Text style={styles.buttonText}>Se connecter</Text>
                    )}
                  </TouchableOpacity>
                </>
              )}

              <TouchableOpacity
                style={styles.qrModeToggle}
                onPress={() => { resetQrState(); setLoginMode('password'); }}
              >
                <Text style={styles.qrModeToggleText}>← Connexion par mot de passe</Text>
              </TouchableOpacity>
            </>
          )}

          <TouchableOpacity
            style={styles.serverToggle}
            onPress={() => setShowServers((v) => !v)}
          >
            <Text style={styles.serverToggleText}>
              {showServers ? '▲ Masquer les serveurs' : '⚙️ Gérer les serveurs'}
            </Text>
          </TouchableOpacity>

          {showServers && (
            <View style={styles.serverSection}>
              <ServerManager onSelect={() => setShowServers(false)} />
            </View>
          )}
        </View>
      </ScrollView>

      <Modal visible={qrScannerVisible} animationType="slide" presentationStyle="fullScreen">
        <View style={styles.scannerScreen}>
          <View style={styles.scannerHeader}>
            <Text style={styles.scannerTitle}>
              {qrScannerTarget === 'user' ? 'Scanner votre QR code' : 'Scanner le QR superviseur'}
            </Text>
            <TouchableOpacity onPress={() => setQrScannerVisible(false)} style={styles.scannerCloseButton}>
              <Text style={styles.scannerCloseText}>Annuler</Text>
            </TouchableOpacity>
          </View>
          <CameraView style={styles.camera} facing="back" barcodeScannerSettings={{ barcodeTypes: ['qr'] }} onBarcodeScanned={onQrScanned} />
        </View>
      </Modal>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  scroll: { flexGrow: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
  card: {
    width: '100%', maxWidth: 400, backgroundColor: '#fff',
    borderRadius: 16, padding: 32,
    shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.1, shadowRadius: 8, elevation: 4,
  },
  title: { fontSize: 28, fontWeight: '700', color: '#78350f', textAlign: 'center', marginBottom: 4 },
  subtitle: { fontSize: 14, color: '#92400e', textAlign: 'center', marginBottom: 28 },
  input: {
    borderWidth: 1, borderColor: '#d1d5db', borderRadius: 10,
    paddingHorizontal: 16, paddingVertical: 12, fontSize: 16,
    color: '#111827', backgroundColor: '#f9fafb', marginBottom: 14,
  },
  button: { backgroundColor: '#92400e', borderRadius: 10, paddingVertical: 14, alignItems: 'center', marginTop: 8 },
  buttonDisabled: { opacity: 0.6 },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: '600' },
  qrModeToggle: { alignItems: 'center', marginTop: 16 },
  qrModeToggleText: { fontSize: 13, color: '#92400e', fontWeight: '600' },
  qrStepTitle: { fontSize: 14, fontWeight: '600', color: '#374151', marginBottom: 8, marginTop: 4 },
  qrScanBtn: { borderWidth: 1, borderColor: '#92400e', borderRadius: 10, paddingVertical: 12, alignItems: 'center', marginBottom: 8 },
  qrScanBtnText: { color: '#92400e', fontWeight: '700' },
  qrScanSuccess: { color: '#15803d', fontSize: 13, fontWeight: '600', marginBottom: 10 },
  qrIdentifiedBox: { backgroundColor: '#ecfdf5', borderWidth: 1, borderColor: '#86efac', borderRadius: 10, padding: 12, marginBottom: 16 },
  qrIdentifiedText: { color: '#166534', fontSize: 14, fontWeight: '600' },
  serverToggle: { alignItems: 'center', marginTop: 20 },
  serverToggleText: { fontSize: 13, color: '#92400e', fontWeight: '600' },
  serverSection: { marginTop: 16 },
  scannerScreen: { flex: 1, backgroundColor: '#000' },
  scannerHeader: { paddingTop: 52, paddingHorizontal: 20, paddingBottom: 14, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  scannerTitle: { color: '#fff', fontSize: 18, fontWeight: '700', flexShrink: 1 },
  scannerCloseButton: { paddingHorizontal: 12, paddingVertical: 8 },
  scannerCloseText: { color: '#fbbf24', fontSize: 16, fontWeight: '600' },
  camera: { flex: 1 },
});
