import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  View,
  Text,
  FlatList,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  Alert,
  Modal,
  ScrollView,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import api from '../api/client';
import { LoyaltyCard } from '../types';

export default function LoyaltyCardsScreen() {
  const navigation = useNavigation<any>();
  const [cards, setCards] = useState<LoyaltyCard[]>([]);
  const [search, setSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loadingMore, setLoadingMore] = useState(false);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const [createVisible, setCreateVisible] = useState(false);

  useEffect(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => setDebouncedSearch(search), 400);
    return () => { if (debounceRef.current) clearTimeout(debounceRef.current); };
  }, [search]);

  const loadCards = useCallback(async (p = 1, replace = true) => {
    const params: Record<string, any> = { page: p };
    if (debouncedSearch.trim()) params.q = debouncedSearch.trim();
    const { data } = await api.get('/loyalty-cards', { params });
    if (replace) {
      setCards(data.data);
    } else {
      setCards((prev) => [...prev, ...data.data]);
    }
    setLastPage(data.last_page);
    setPage(p);
  }, [debouncedSearch]);

  useEffect(() => {
    setLoading(true);
    loadCards(1, true).finally(() => setLoading(false));
  }, [loadCards]);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadCards(1, true);
    setRefreshing(false);
  };

  const loadMore = async () => {
    if (loadingMore || page >= lastPage) return;
    setLoadingMore(true);
    await loadCards(page + 1, false);
    setLoadingMore(false);
  };

  const onCardCreated = (card: LoyaltyCard) => {
    setCreateVisible(false);
    Alert.alert(
      'Carte créée',
      `Numéro : ${card.card_number}\n\nCommuniquez ce numéro et le code PIN au titulaire.`,
      [
        { text: 'Voir la fiche', onPress: () => navigation.navigate('LoyaltyCardDetail', { cardId: card.id, fullName: card.full_name }) },
        { text: 'OK', onPress: () => loadCards(1, true) },
      ]
    );
  };

  return (
    <View style={styles.container}>
      <View style={styles.searchBar}>
        <TextInput
          style={styles.searchInput}
          placeholder="Rechercher par nom, e-mail, n° de carte…"
          placeholderTextColor="#9ca3af"
          value={search}
          onChangeText={setSearch}
          returnKeyType="search"
          clearButtonMode="while-editing"
        />
        <TouchableOpacity style={styles.createBtn} onPress={() => setCreateVisible(true)}>
          <Text style={styles.createBtnText}>+ Nouvelle carte</Text>
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color="#92400e" />
        </View>
      ) : (
        <FlatList
          data={cards}
          keyExtractor={(item) => String(item.id)}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
          onEndReached={loadMore}
          onEndReachedThreshold={0.3}
          ListEmptyComponent={<Text style={styles.empty}>Aucune carte trouvée.</Text>}
          ListFooterComponent={loadingMore ? <ActivityIndicator style={{ marginVertical: 16 }} color="#92400e" /> : null}
          renderItem={({ item: card }) => (
            <TouchableOpacity
              style={styles.card}
              onPress={() => navigation.navigate('LoyaltyCardDetail', { cardId: card.id, fullName: card.full_name })}
              activeOpacity={0.7}
            >
              <View style={styles.cardHeader}>
                <Text style={styles.fullName}>{card.full_name}</Text>
                <View style={[styles.badge, card.has_employee_benefits ? styles.badgeEmployee : styles.badgeClient]}>
                  <Text style={[styles.badgeText, card.has_employee_benefits ? styles.badgeEmployeeText : styles.badgeClientText]}>
                    {card.has_employee_benefits ? '👤 Salarié' : 'Client'}
                  </Text>
                </View>
              </View>
              <Text style={styles.cardNumber}>{card.card_number}</Text>
              {card.email && <Text style={styles.contact}>{card.email}</Text>}
              {card.phone && <Text style={styles.contact}>{card.phone}</Text>}
              <View style={styles.pointsRow}>
                <Text style={styles.points}>🏆 {card.points} point{card.points > 1 ? 's' : ''}</Text>
                <Text style={styles.chevron}>›</Text>
              </View>
            </TouchableOpacity>
          )}
          contentContainerStyle={{ padding: 16, paddingBottom: 40 }}
        />
      )}

      <CreateCardModal
        visible={createVisible}
        onClose={() => setCreateVisible(false)}
        onCreated={onCardCreated}
      />
    </View>
  );
}

// ─────────── Modal de création ───────────

type Errors = Partial<Record<'first_name' | 'last_name' | 'email' | 'phone' | 'birth_date' | 'pin' | 'general', string>>;

function CreateCardModal({
  visible,
  onClose,
  onCreated,
}: {
  visible: boolean;
  onClose: () => void;
  onCreated: (card: LoyaltyCard) => void;
}) {
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [birthDate, setBirthDate] = useState(''); // format libre DD/MM/YYYY ou YYYY-MM-DD
  const [pin, setPin] = useState('');
  const [pinConfirmation, setPinConfirmation] = useState('');
  const [errors, setErrors] = useState<Errors>({});
  const [submitting, setSubmitting] = useState(false);

  const maxBirthDate = useMemo(() => {
    const d = new Date();
    d.setFullYear(d.getFullYear() - 15);
    return d;
  }, []);

  const reset = () => {
    setFirstName(''); setLastName(''); setEmail(''); setPhone('');
    setBirthDate(''); setPin(''); setPinConfirmation('');
    setErrors({}); setSubmitting(false);
  };

  const close = () => { reset(); onClose(); };

  // Normalise "31/12/2000" ou "31-12-2000" ou "2000-12-31" en "YYYY-MM-DD"
  const normalizeBirthDate = (raw: string): string | null => {
    const s = raw.trim();
    if (!s) return null;
    const isoMatch = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (isoMatch) {
      const [, y, m, d] = isoMatch;
      return `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
    }
    const frMatch = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
    if (frMatch) {
      const [, d, m, y] = frMatch;
      return `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
    }
    return null;
  };

  const validateLocal = (): boolean => {
    const e: Errors = {};
    if (!firstName.trim()) e.first_name = 'Le prénom est obligatoire.';
    if (!lastName.trim()) e.last_name = 'Le nom est obligatoire.';
    if (!email.trim()) e.email = 'L\'e-mail est obligatoire.';
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) e.email = 'Adresse e-mail invalide.';
    if (!phone.trim()) e.phone = 'Le téléphone est obligatoire.';
    else if (!/^[0-9 +().-]{6,30}$/.test(phone.trim())) e.phone = 'Format de téléphone invalide.';

    const iso = normalizeBirthDate(birthDate);
    if (!iso) {
      e.birth_date = 'Format attendu : JJ/MM/AAAA';
    } else {
      const d = new Date(iso + 'T00:00:00');
      if (isNaN(d.getTime())) e.birth_date = 'Date invalide.';
      else if (d > maxBirthDate) e.birth_date = 'Le titulaire doit avoir au moins 15 ans.';
    }

    if (!/^\d{4,6}$/.test(pin)) e.pin = 'Le PIN doit contenir 4 à 6 chiffres.';
    else if (pin !== pinConfirmation) e.pin = 'La confirmation du PIN ne correspond pas.';

    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const submit = async () => {
    if (!validateLocal()) return;
    setSubmitting(true);
    try {
      const iso = normalizeBirthDate(birthDate)!;
      const { data } = await api.post('/loyalty-cards', {
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim(),
        phone: phone.trim(),
        birth_date: iso,
        pin,
        pin_confirmation: pinConfirmation,
      });
      onCreated(data.card);
      reset();
    } catch (err: any) {
      const respErrors = err?.response?.data?.errors ?? {};
      const mapped: Errors = {};
      for (const [k, v] of Object.entries(respErrors)) {
        const first = Array.isArray(v) ? v[0] : String(v);
        if (['first_name', 'last_name', 'email', 'phone', 'birth_date', 'pin'].includes(k)) {
          (mapped as any)[k] = first;
        }
      }
      if (Object.keys(mapped).length === 0) {
        mapped.general = err?.response?.data?.message ?? 'Impossible de créer la carte.';
      }
      setErrors(mapped);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Modal visible={visible} animationType="slide" presentationStyle="pageSheet" onRequestClose={close}>
      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <View style={styles.modalContainer}>
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitle}>Nouvelle carte de fidélité</Text>
            <TouchableOpacity onPress={close} disabled={submitting}>
              <Text style={styles.modalClose}>Annuler</Text>
            </TouchableOpacity>
          </View>

          <ScrollView contentContainerStyle={{ padding: 16, paddingBottom: 40 }} keyboardShouldPersistTaps="handled">
            {errors.general && <Text style={styles.errorBanner}>{errors.general}</Text>}

            <Field label="Prénom">
              <TextInput
                style={[styles.input, errors.first_name && styles.inputError]}
                placeholder="Prénom"
                placeholderTextColor="#9ca3af"
                value={firstName}
                onChangeText={setFirstName}
                autoCapitalize="words"
                editable={!submitting}
              />
              {errors.first_name && <Text style={styles.error}>{errors.first_name}</Text>}
            </Field>

            <Field label="Nom">
              <TextInput
                style={[styles.input, errors.last_name && styles.inputError]}
                placeholder="Nom"
                placeholderTextColor="#9ca3af"
                value={lastName}
                onChangeText={setLastName}
                autoCapitalize="words"
                editable={!submitting}
              />
              {errors.last_name && <Text style={styles.error}>{errors.last_name}</Text>}
            </Field>

            <Field label="E-mail">
              <TextInput
                style={[styles.input, errors.email && styles.inputError]}
                placeholder="exemple@domaine.fr"
                placeholderTextColor="#9ca3af"
                value={email}
                onChangeText={setEmail}
                keyboardType="email-address"
                autoCapitalize="none"
                autoCorrect={false}
                editable={!submitting}
              />
              {errors.email && <Text style={styles.error}>{errors.email}</Text>}
            </Field>

            <Field label="Téléphone">
              <TextInput
                style={[styles.input, errors.phone && styles.inputError]}
                placeholder="06 12 34 56 78"
                placeholderTextColor="#9ca3af"
                value={phone}
                onChangeText={setPhone}
                keyboardType="phone-pad"
                editable={!submitting}
              />
              {errors.phone && <Text style={styles.error}>{errors.phone}</Text>}
            </Field>

            <Field label="Date de naissance" hint="Format : JJ/MM/AAAA — âge minimum 15 ans">
              <TextInput
                style={[styles.input, errors.birth_date && styles.inputError]}
                placeholder="JJ/MM/AAAA"
                placeholderTextColor="#9ca3af"
                value={birthDate}
                onChangeText={setBirthDate}
                keyboardType="numbers-and-punctuation"
                editable={!submitting}
              />
              {errors.birth_date && <Text style={styles.error}>{errors.birth_date}</Text>}
            </Field>

            <Field label="Code PIN" hint="4 à 6 chiffres — à communiquer au client">
              <TextInput
                style={[styles.input, errors.pin && styles.inputError]}
                placeholder="••••"
                placeholderTextColor="#9ca3af"
                value={pin}
                onChangeText={(v) => setPin(v.replace(/\D/g, '').slice(0, 6))}
                keyboardType="number-pad"
                secureTextEntry
                editable={!submitting}
                maxLength={6}
              />
            </Field>

            <Field label="Confirmer le PIN">
              <TextInput
                style={[styles.input, errors.pin && styles.inputError]}
                placeholder="••••"
                placeholderTextColor="#9ca3af"
                value={pinConfirmation}
                onChangeText={(v) => setPinConfirmation(v.replace(/\D/g, '').slice(0, 6))}
                keyboardType="number-pad"
                secureTextEntry
                editable={!submitting}
                maxLength={6}
              />
              {errors.pin && <Text style={styles.error}>{errors.pin}</Text>}
            </Field>

            <TouchableOpacity
              style={[styles.submitBtn, submitting && styles.submitBtnDisabled]}
              onPress={submit}
              disabled={submitting}
            >
              {submitting ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={styles.submitBtnText}>Créer la carte</Text>
              )}
            </TouchableOpacity>
          </ScrollView>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
}

function Field({ label, hint, children }: { label: string; hint?: string; children: React.ReactNode }) {
  return (
    <View style={{ marginBottom: 12 }}>
      <Text style={styles.fieldLabel}>{label}</Text>
      {hint && <Text style={styles.fieldHint}>{hint}</Text>}
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  searchBar: {
    padding: 12,
    gap: 8,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  searchInput: {
    backgroundColor: '#f3f4f6',
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 10,
    fontSize: 15,
    color: '#111827',
  },
  createBtn: {
    backgroundColor: '#d97706',
    borderRadius: 10,
    paddingVertical: 10,
    alignItems: 'center',
  },
  createBtnText: { color: '#fff', fontWeight: '700', fontSize: 14 },
  empty: { textAlign: 'center', color: '#9ca3af', marginTop: 48, fontSize: 15 },
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    marginBottom: 10,
    padding: 14,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.06,
    shadowRadius: 4,
    elevation: 2,
  },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  fullName: { fontSize: 17, fontWeight: '700', color: '#1f2937', flex: 1 },
  badge: { paddingHorizontal: 10, paddingVertical: 3, borderRadius: 12, marginLeft: 8 },
  badgeEmployee: { backgroundColor: '#fef3c7' },
  badgeClient: { backgroundColor: '#f3f4f6' },
  badgeText: { fontSize: 12, fontWeight: '600' },
  badgeEmployeeText: { color: '#92400e' },
  badgeClientText: { color: '#6b7280' },
  cardNumber: { fontSize: 13, color: '#9ca3af', fontFamily: 'monospace', marginBottom: 4 },
  contact: { fontSize: 14, color: '#6b7280', marginTop: 2 },
  pointsRow: { marginTop: 8, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  points: { fontSize: 15, fontWeight: '600', color: '#d97706' },
  chevron: { fontSize: 22, color: '#d1d5db', fontWeight: '400' },

  // Modal
  modalContainer: { flex: 1, backgroundColor: '#fff' },
  modalHeader: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    padding: 16, borderBottomWidth: 1, borderBottomColor: '#e5e7eb',
  },
  modalTitle: { fontSize: 18, fontWeight: '700', color: '#1f2937' },
  modalClose: { fontSize: 16, color: '#92400e', fontWeight: '600' },
  fieldLabel: { fontSize: 13, fontWeight: '600', color: '#374151', marginBottom: 4 },
  fieldHint: { fontSize: 12, color: '#9ca3af', marginBottom: 6 },
  input: {
    borderWidth: 1, borderColor: '#d1d5db', borderRadius: 8,
    paddingHorizontal: 12, paddingVertical: 10, fontSize: 15,
    color: '#111827', backgroundColor: '#f9fafb',
  },
  inputError: { borderColor: '#ef4444' },
  error: { color: '#ef4444', fontSize: 13, marginTop: 4 },
  errorBanner: {
    backgroundColor: '#fee2e2', color: '#b91c1c',
    padding: 10, borderRadius: 8, marginBottom: 12, fontSize: 13,
  },
  submitBtn: {
    backgroundColor: '#92400e', borderRadius: 12,
    paddingVertical: 14, alignItems: 'center', marginTop: 10,
  },
  submitBtnDisabled: { opacity: 0.5 },
  submitBtnText: { color: '#fff', fontSize: 16, fontWeight: '700' },
});
