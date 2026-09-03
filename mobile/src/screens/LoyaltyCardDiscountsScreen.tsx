import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator, Alert, Modal, TextInput } from 'react-native';
import { useRoute, RouteProp, useNavigation } from '@react-navigation/native';
import DateTimePicker, { DateTimePickerEvent } from '@react-native-community/datetimepicker';
import { CameraView, useCameraPermissions } from 'expo-camera';
import api from '../api/client';
import { useAuth } from '../context/AuthContext';

type ParamList = { LoyaltyCardDiscounts: { cardId: number } };

type CardOffer = {
  id: number;
  label: string;
  discount_type: 'fixed' | 'percent';
  discount_value: number;
  max_discount_amount: number | null;
  display_value: string;
  expires_at: string | null;
  is_used?: boolean;
  is_valid?: boolean;
};

export default function LoyaltyCardDiscountsScreen() {
  const route = useRoute<RouteProp<ParamList, 'LoyaltyCardDiscounts'>>();
  const navigation = useNavigation<any>();
  const { cardId } = route.params;
  const { user, isPermanentSupervisionEnabled } = useAuth();
  const isSuperAdmin = user?.global_role === 'superadmin';

  const [items, setItems] = useState<CardOffer[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [modalVisible, setModalVisible] = useState(false);
  const [editingOffer, setEditingOffer] = useState<CardOffer | null>(null);
  const [label, setLabel] = useState('');
  const [discountType, setDiscountType] = useState<'fixed' | 'percent'>('fixed');
  const [discountValue, setDiscountValue] = useState('');
  const [maxDiscountAmount, setMaxDiscountAmount] = useState('');
  const [expiresAt, setExpiresAt] = useState('');
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [supervisorNumber, setSupervisorNumber] = useState('');
  const [supervisorPin, setSupervisorPin] = useState('');
  const [supervisorToken, setSupervisorToken] = useState('');
  const [supervisorScannerVisible, setSupervisorScannerVisible] = useState(false);
  const [cameraPermission, requestCameraPermission] = useCameraPermissions();
  const scannerLocked = React.useRef(false);

  const getOfferStatus = (offer: CardOffer) => {
    if (offer.is_used) {
      return { label: 'Déjà utilisée', container: styles.statusUsed, text: styles.statusUsedText };
    }
    if (!offer.is_valid) {
      return { label: 'Expirée', container: styles.statusExpired, text: styles.statusExpiredText };
    }
    return { label: 'Disponible', container: styles.statusAvailable, text: styles.statusAvailableText };
  };

  const load = async () => {
    setLoading(true);
    try {
      const { data } = await api.get(`/loyalty-cards/${cardId}/offers`, { params: { all: true } });
      setItems(data);
    } catch (e) { Alert.alert('Erreur', 'Impossible de charger les réductions.'); }
    finally { setLoading(false); }
  };

  useEffect(() => { load(); }, []);

  const resetForm = () => {
    setEditingOffer(null);
    setLabel('');
    setDiscountType('fixed');
    setDiscountValue('');
    setMaxDiscountAmount('');
    setExpiresAt('');
    setSupervisorNumber('');
    setSupervisorPin('');
    setSupervisorToken('');
  };

  const openCreate = () => {
    resetForm();
    setModalVisible(true);
  };

  const openEdit = (offer: CardOffer) => {
    setEditingOffer(offer);
    setLabel(offer.label);
    setDiscountType(offer.discount_type);
    setDiscountValue(String(offer.discount_value));
    setMaxDiscountAmount(offer.max_discount_amount ? String(offer.max_discount_amount) : '');
    setExpiresAt(offer.expires_at ? offer.expires_at.slice(0, 10) : '');
    setModalVisible(true);
  };

  const formatDateFr = (date: Date) => date.toLocaleDateString('fr-FR');

  const parseDateInput = (value: string): Date => {
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      return tomorrow;
    }
    return parsed;
  };

  const onDateChange = (event: DateTimePickerEvent, selectedDate?: Date) => {
    setShowDatePicker(false);
    if (event.type === 'dismissed' || !selectedDate) return;

    const yyyy = selectedDate.getFullYear();
    const mm = String(selectedDate.getMonth() + 1).padStart(2, '0');
    const dd = String(selectedDate.getDate()).padStart(2, '0');
    setExpiresAt(`${yyyy}-${mm}-${dd}`);
  };

  const saveOffer = async () => {
    if (!label.trim() || !discountValue || !expiresAt) {
      Alert.alert('Erreur', 'Veuillez remplir les champs requis.');
      return;
    }

    const payload: any = {
      label: label.trim(),
      discount_type: discountType,
      discount_value: Number(discountValue),
      max_discount_amount: discountType === 'percent' && maxDiscountAmount ? Number(maxDiscountAmount) : null,
      expires_at: expiresAt,
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

    setSaving(true);
    try {
      if (editingOffer) {
        await api.put(`/loyalty-cards/${cardId}/offers/${editingOffer.id}`, payload);
      } else {
        await api.post(`/loyalty-cards/${cardId}/offers`, payload);
      }
      setModalVisible(false);
      resetForm();
      await load();
    } catch (e: any) {
      Alert.alert('Erreur', e?.response?.data?.message || 'Impossible d’enregistrer la réduction.');
    } finally {
      setSaving(false);
    }
  };

  const remove = (id: number) => {
    Alert.alert('Supprimer', 'Supprimer cette réduction ?', [
      { text: 'Annuler', style: 'cancel' },
      { text: 'Supprimer', style: 'destructive', onPress: async () => {
        try {
          const payload = !isPermanentSupervisionEnabled
            ? {
                data: supervisorToken.replace(/\s+/g, '').trim()
                  ? { supervisor_token: supervisorToken.replace(/\s+/g, '').trim() }
                  : { supervisor_number: supervisorNumber, supervisor_pin: supervisorPin },
              }
            : undefined;
          await api.delete(`/loyalty-cards/${cardId}/offers/${id}`, payload as any);
          Alert.alert('Supprimé');
          load();
        }
        catch { Alert.alert('Erreur', 'Impossible de supprimer.'); }
      } }
    ]);
  };

  const openSupervisorScanner = async () => {
    if (!cameraPermission?.granted) {
      const result = await requestCameraPermission();
      if (!result.granted) {
        Alert.alert('Permission refusée', "L'accès à la caméra est requis pour scanner le code superviseur.");
        return;
      }
    }
    scannerLocked.current = false;
    setSupervisorScannerVisible(true);
  };

  const onSupervisorScanned = ({ data }: { data: string }) => {
    if (scannerLocked.current) return;
    const token = data.replace(/\s+/g, '').trim();
    if (!token) return;
    scannerLocked.current = true;
    setSupervisorScannerVisible(false);
    setSupervisorToken(token);
  };

  if (loading) return <View style={styles.center}><ActivityIndicator color="#92400e" size="large" /></View>;

  return (
    <View style={styles.container}>
      <FlatList
        data={items}
        keyExtractor={(i) => String(i.id)}
        contentContainerStyle={{ padding: 12 }}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View>
              <Text style={styles.title}>{item.label}</Text>
              <Text style={styles.meta}>{item.display_value}</Text>
              <Text style={styles.meta}>Expire le {item.expires_at ? new Date(item.expires_at).toLocaleDateString('fr-FR') : '—'}</Text>
            </View>
            <View style={{ alignItems: 'flex-end' }}>
              <View style={[styles.statusBadge, getOfferStatus(item).container]}>
                <Text style={[styles.statusBadgeText, getOfferStatus(item).text]}>{getOfferStatus(item).label}</Text>
              </View>
              <TouchableOpacity style={styles.smallBtn} onPress={() => openEdit(item)}>
                <Text style={styles.smallBtnText}>Modifier</Text>
              </TouchableOpacity>
              <TouchableOpacity style={[styles.smallBtn, { marginTop: 6, backgroundColor: '#fff', borderWidth: 1, borderColor: '#ef4444' }]} onPress={() => remove(item.id)}>
                <Text style={[styles.smallBtnText, { color: '#ef4444' }]}>Supprimer</Text>
              </TouchableOpacity>
            </View>
          </View>
        )}
        ListEmptyComponent={<Text style={styles.empty}>Aucune réduction définie.</Text>}
      />
      <TouchableOpacity style={styles.fab} onPress={openCreate}>
        <Text style={styles.fabText}>＋</Text>
      </TouchableOpacity>

      <Modal visible={modalVisible} transparent animationType="slide" onRequestClose={() => setModalVisible(false)}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>{editingOffer ? 'Modifier la réduction' : 'Nouvelle réduction'}</Text>

            <TextInput style={styles.input} placeholder="Libellé" value={label} onChangeText={setLabel} placeholderTextColor="#9ca3af" selectionColor="#92400e" />

            <View style={styles.typeRow}>
              <TouchableOpacity style={[styles.typeBtn, discountType === 'fixed' && styles.typeBtnActive]} onPress={() => setDiscountType('fixed')}>
                <Text style={[styles.typeBtnText, discountType === 'fixed' && styles.typeBtnTextActive]}>Montant fixe</Text>
              </TouchableOpacity>
              <TouchableOpacity style={[styles.typeBtn, discountType === 'percent' && styles.typeBtnActive]} onPress={() => setDiscountType('percent')}>
                <Text style={[styles.typeBtnText, discountType === 'percent' && styles.typeBtnTextActive]}>Pourcentage</Text>
              </TouchableOpacity>
            </View>

            <TextInput style={styles.input} placeholder={discountType === 'percent' ? 'Valeur (%)' : 'Valeur (€)'} keyboardType="numeric" value={discountValue} onChangeText={setDiscountValue} placeholderTextColor="#9ca3af" selectionColor="#92400e" />
            {discountType === 'percent' && (
              <TextInput style={styles.input} placeholder="Plafond (€) (optionnel)" keyboardType="numeric" value={maxDiscountAmount} onChangeText={setMaxDiscountAmount} placeholderTextColor="#9ca3af" selectionColor="#92400e" />
            )}
            <Text style={styles.inputLabel}>Date d'expiration</Text>
            <TouchableOpacity style={styles.datePickerBtn} onPress={() => setShowDatePicker(true)}>
              <Text style={styles.datePickerBtnText}>{expiresAt ? formatDateFr(parseDateInput(expiresAt)) : 'Sélectionner une date'}</Text>
            </TouchableOpacity>
            {showDatePicker && (
              <DateTimePicker
                value={parseDateInput(expiresAt)}
                mode="date"
                display="default"
                minimumDate={(() => {
                  const tomorrow = new Date();
                  tomorrow.setDate(tomorrow.getDate() + 1);
                  return tomorrow;
                })()}
                onChange={onDateChange}
              />
            )}

            <>
                <TouchableOpacity style={styles.scanBtn} onPress={openSupervisorScanner}>
                  <Text style={styles.scanBtnText}>Scanner le QR code superviseur</Text>
                </TouchableOpacity>
                {supervisorToken ? <Text style={styles.scanSuccess}>Code superviseur scanné.</Text> : null}
                <TextInput style={styles.input} placeholder="Identifiant superviseur" value={supervisorNumber} onChangeText={setSupervisorNumber} placeholderTextColor="#9ca3af" selectionColor="#92400e" />
                <TextInput style={styles.input} placeholder="Mot de passe superviseur" value={supervisorPin} onChangeText={setSupervisorPin} secureTextEntry keyboardType="numeric" placeholderTextColor="#9ca3af" selectionColor="#92400e" />
            </>

            <View style={styles.actionsRow}>
              <TouchableOpacity style={[styles.actionBtn, styles.cancelBtn]} onPress={() => setModalVisible(false)} disabled={saving}>
                <Text style={styles.cancelBtnText}>Annuler</Text>
              </TouchableOpacity>
              <TouchableOpacity style={[styles.actionBtn, styles.saveBtn]} onPress={saveOffer} disabled={saving}>
                <Text style={styles.saveBtnText}>{saving ? 'Enregistrement...' : 'Enregistrer'}</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      <Modal visible={supervisorScannerVisible} animationType="slide" presentationStyle="fullScreen">
        <View style={{ flex: 1, backgroundColor: '#000' }}>
          <View style={styles.scannerHeader}>
            <Text style={styles.scannerTitle}>Scanner le code superviseur</Text>
            <TouchableOpacity onPress={() => setSupervisorScannerVisible(false)} style={styles.scannerCloseBtn}>
              <Text style={styles.scannerCloseText}>Annuler</Text>
            </TouchableOpacity>
          </View>
          <CameraView
            style={{ flex: 1 }}
            facing="back"
            barcodeScannerSettings={{ barcodeTypes: ['qr', 'code128'] }}
            onBarcodeScanned={onSupervisorScanned}
          />
          <View style={styles.scannerOverlay}>
            <View style={styles.scannerFrame} />
            <Text style={styles.scannerHint}>Pointez vers le QR code superviseur</Text>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  row: { backgroundColor: '#fff', padding: 12, borderRadius: 10, marginBottom: 8, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  title: { fontWeight: '700', color: '#1f2937' },
  meta: { color: '#6b7280', marginTop: 4 },
  smallBtn: { backgroundColor: '#92400e', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 8 },
  smallBtnText: { color: '#fff', fontWeight: '700' },
  empty: { textAlign: 'center', marginTop: 24, color: '#9ca3af' },
  fab: { position: 'absolute', right: 16, bottom: 20, width: 56, height: 56, borderRadius: 28, backgroundColor: '#92400e', justifyContent: 'center', alignItems: 'center', elevation: 6 },
  fabText: { color: '#fff', fontSize: 28, lineHeight: 28 },
  modalOverlay: { flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(0,0,0,0.25)' },
  modalCard: { backgroundColor: '#fff', padding: 16, borderTopLeftRadius: 16, borderTopRightRadius: 16 },
  modalTitle: { fontSize: 18, fontWeight: '700', color: '#1f2937', marginBottom: 12 },
  input: { borderWidth: 1, borderColor: '#e5e7eb', borderRadius: 10, padding: 10, marginBottom: 10, backgroundColor: '#f9fafb', color: '#111827' },
  inputLabel: { fontSize: 13, color: '#6b7280', marginBottom: 6 },
  datePickerBtn: { borderWidth: 1, borderColor: '#e5e7eb', borderRadius: 10, padding: 12, marginBottom: 10, backgroundColor: '#f9fafb' },
  datePickerBtnText: { color: '#1f2937', fontSize: 14 },
  scanBtn: { backgroundColor: '#fff7ed', borderColor: '#fdba74', borderWidth: 1, borderRadius: 10, paddingVertical: 10, alignItems: 'center', marginBottom: 10 },
  scanBtnText: { color: '#b45309', fontWeight: '700' },
  scanSuccess: { color: '#15803d', fontSize: 13, marginBottom: 10 },
  typeRow: { flexDirection: 'row', gap: 8, marginBottom: 10 },
  typeBtn: { flex: 1, padding: 10, borderRadius: 8, backgroundColor: '#f3f4f6', alignItems: 'center' },
  typeBtnActive: { backgroundColor: '#92400e' },
  typeBtnText: { color: '#1f2937', fontWeight: '700' },
  typeBtnTextActive: { color: '#fff' },
  actionsRow: { flexDirection: 'row', gap: 8, marginTop: 6 },
  actionBtn: { flex: 1, borderRadius: 10, padding: 12, alignItems: 'center' },
  cancelBtn: { backgroundColor: '#f3f4f6' },
  cancelBtnText: { color: '#374151', fontWeight: '700' },
  saveBtn: { backgroundColor: '#92400e' },
  saveBtnText: { color: '#fff', fontWeight: '700' },
  statusBadge: { borderRadius: 999, paddingHorizontal: 10, paddingVertical: 4, marginBottom: 8 },
  statusBadgeText: { fontSize: 12, fontWeight: '700' },
  statusUsed: { backgroundColor: '#fee2e2' },
  statusUsedText: { color: '#b91c1c' },
  statusExpired: { backgroundColor: '#fef3c7' },
  statusExpiredText: { color: '#92400e' },
  statusAvailable: { backgroundColor: '#dcfce7' },
  statusAvailableText: { color: '#166534' },
  scannerHeader: { paddingTop: 52, paddingHorizontal: 20, paddingBottom: 14, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  scannerTitle: { color: '#fff', fontSize: 18, fontWeight: '700' },
  scannerCloseBtn: { paddingVertical: 6, paddingHorizontal: 10, borderRadius: 8, backgroundColor: 'rgba(0,0,0,0.25)' },
  scannerCloseText: { color: '#fbbf24', fontSize: 16, fontWeight: '600' },
  scannerOverlay: { position: 'absolute', left: 0, right: 0, bottom: 44, alignItems: 'center' },
  scannerFrame: { width: 240, height: 240, borderWidth: 2, borderColor: '#fbbf24', borderRadius: 16, backgroundColor: 'transparent' },
  scannerHint: { color: '#fff', marginTop: 14, fontSize: 14, fontWeight: '500' },
});
