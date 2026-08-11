import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator, ScrollView } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import api from '../api/client';
import { useAuth } from '../context/AuthContext';

export default function CreateVoucherScreen() {
  const navigation = useNavigation<any>();
  const { user } = useAuth();
  const [amount, setAmount] = useState('');
  const [validityDays, setValidityDays] = useState('7');
  const [restrictionType, setRestrictionType] = useState<'none'|'card'|'name'>('none');
  const [restrictedCardNumber, setRestrictedCardNumber] = useState('');
  const [restrictedName, setRestrictedName] = useState('');
  const [supervisorNumber, setSupervisorNumber] = useState('');
  const [supervisorPin, setSupervisorPin] = useState('');
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    if (!amount || Number(amount) <= 0) {
      Alert.alert('Erreur', 'Montant invalide.');
      return;
    }
    setLoading(true);
    try {
      const payload: any = {
        amount: Number(amount),
        validity_days: Number(validityDays),
        restriction_type: restrictionType,
      };
      if (restrictionType === 'card') payload.restricted_card_number = restrictedCardNumber;
      if (restrictionType === 'name') payload.restricted_name = restrictedName;
      if (!user?.global_role || user.global_role !== 'superadmin') {
        payload.supervisor_number = supervisorNumber;
        payload.supervisor_pin = supervisorPin;
      }
      const { data } = await api.post('/vouchers', payload);
      Alert.alert('Succès', `Bon créé : ${data.code}`);
      navigation.goBack();
    } catch (e: any) {
      Alert.alert('Erreur', e.response?.data?.message ?? 'Impossible de créer le bon.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <ScrollView contentContainerStyle={{ padding: 16 }} style={{ flex: 1, backgroundColor: '#fdf8f3' }}>
      <Text style={styles.title}>Créer un bon d'achat</Text>

      <View style={styles.card}>
        <Text style={styles.label}>Montant (€)</Text>
        <TextInput style={styles.input} keyboardType="numeric" value={amount} onChangeText={setAmount} />

        <Text style={styles.label}>Validité (jours)</Text>
        <TextInput style={styles.input} keyboardType="numeric" value={validityDays} onChangeText={setValidityDays} />

        <Text style={styles.label}>Restriction</Text>
        <View style={{ flexDirection: 'row', gap: 8, marginBottom: 8 }}>
          {(['none','card','name'] as const).map((t) => (
            <TouchableOpacity key={t} onPress={() => setRestrictionType(t)} style={[styles.chip, restrictionType === t && styles.chipActive]}>
              <Text style={[styles.chipText, restrictionType === t && styles.chipTextActive]}>{t}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {restrictionType === 'card' && (
          <>
            <Text style={styles.label}>Numéro de carte</Text>
            <TextInput style={styles.input} value={restrictedCardNumber} onChangeText={setRestrictedCardNumber} />
          </>
        )}

        {restrictionType === 'name' && (
          <>
            <Text style={styles.label}>Nom complet</Text>
            <TextInput style={styles.input} value={restrictedName} onChangeText={setRestrictedName} />
          </>
        )}

        {!user?.global_role || user.global_role !== 'superadmin' ? (
          <>
            <Text style={styles.label}>Numéro du superviseur</Text>
            <TextInput style={styles.input} value={supervisorNumber} onChangeText={setSupervisorNumber} />
            <Text style={styles.label}>PIN du superviseur</Text>
            <TextInput style={styles.input} value={supervisorPin} onChangeText={setSupervisorPin} secureTextEntry keyboardType="numeric" />
          </>
        ) : null}

        <TouchableOpacity style={styles.submit} onPress={submit} disabled={loading}>
          {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.submitText}>Créer</Text>}
        </TouchableOpacity>
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
  submit: { backgroundColor: '#92400e', padding: 12, borderRadius: 8, alignItems: 'center', marginTop: 8 },
  submitText: { color: '#fff', fontWeight: '700' },
});
