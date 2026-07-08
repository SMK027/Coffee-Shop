import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  Alert,
  Switch,
  Modal,
  FlatList,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import api from '../api/client';
import { Drink, LoyaltyCard, LoyaltyDiscount } from '../types';

type CartItem =
  | { uid: string; type: 'drink'; drink: Drink; quantity: number }
  | { uid: string; type: 'custom'; label: string; unitPrice: number; quantity: number };

let cartUidCounter = 0;
const nextCartUid = () => `item-${++cartUidCounter}`;

type Step = 1 | 2;

export default function CreateOrderScreen() {
  const navigation = useNavigation<any>();

  // Étape courante
  const [step, setStep] = useState<Step>(1);

  // Données API
  const [drinks, setDrinks] = useState<Drink[]>([]);
  const [discounts, setDiscounts] = useState<LoyaltyDiscount[]>([]);
  const [loading, setLoading] = useState(true);

  // Étape 1 — Client
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [isEmployeeOrder, setIsEmployeeOrder] = useState(false);
  const [loyaltyCardNumber, setLoyaltyCardNumber] = useState('');
  const [loyaltyCard, setLoyaltyCard] = useState<LoyaltyCard | null>(null);
  const [loyaltyCardError, setLoyaltyCardError] = useState('');
  const [checkingCard, setCheckingCard] = useState(false);
  const [selectedDiscountIds, setSelectedDiscountIds] = useState<number[]>([]);
  const [cardPin, setCardPin] = useState('');
  const [pinError, setPinError] = useState('');

  // Étape 2 — Panier & notes
  const [cart, setCart] = useState<CartItem[]>([]);
  const [notes, setNotes] = useState('');

  // Modal recherche carte fidélité
  const [cardSearchVisible, setCardSearchVisible] = useState(false);
  const [cardSearchQuery, setCardSearchQuery] = useState('');
  const [cardSearchResults, setCardSearchResults] = useState<LoyaltyCard[]>([]);

  // Scanner QR carte fidélité
  const [scannerVisible, setScannerVisible] = useState(false);
  const [cameraPermission, requestCameraPermission] = useCameraPermissions();
  const scannerLocked = useRef(false);
  const [cardSearchLoading, setCardSearchLoading] = useState(false);
  const [cardSearched, setCardSearched] = useState(false);
  const cardSearchDebounce = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Modaux
  const [drinkModalVisible, setDrinkModalVisible] = useState(false);
  const [drinkSearch, setDrinkSearch] = useState('');
  const [customModalVisible, setCustomModalVisible] = useState(false);
  const [customLabel, setCustomLabel] = useState('');
  const [customPrice, setCustomPrice] = useState('');
  const [customQuantity, setCustomQuantity] = useState('1');
  const [customError, setCustomError] = useState('');

  // Soumission
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    Promise.all([
      api.get('/drinks').then(({ data }) => setDrinks(data.drinks.filter((d: Drink) => d.available))),
      api.get('/loyalty-discounts').then(({ data }) => setDiscounts(data.discounts)),
    ]).finally(() => setLoading(false));
  }, []);

  // ─────────── Étape 1 : Client ───────────

  const checkLoyaltyCard = async () => {
    const number = loyaltyCardNumber.replace(/\s/g, '');
    if (!number) return;
    setCheckingCard(true);
    setLoyaltyCardError('');
    try {
      const { data } = await api.post('/loyalty-cards/check', { card_number: number });
      if (data.found) {
        setLoyaltyCard(data.card);
        if (data.card.has_employee_benefits) setIsEmployeeOrder(true);
        // Pré-remplit le nom/prénom si vide
        const [fn, ...rest] = String(data.card.full_name || '').split(' ');
        if (!firstName && fn) setFirstName(fn);
        if (!lastName && rest.length) setLastName(rest.join(' '));
      } else {
        setLoyaltyCard(null);
        setLoyaltyCardError(data.message);
      }
    } catch {
      setLoyaltyCardError('Erreur lors de la vérification.');
    } finally {
      setCheckingCard(false);
    }
  };

  const clearLoyaltyCard = () => {
    setLoyaltyCard(null);
    setLoyaltyCardNumber('');
    setLoyaltyCardError('');
    setCardPin('');
    setPinError('');
    setSelectedDiscountIds([]);
    if (!loyaltyCard?.has_employee_benefits) setIsEmployeeOrder(false);
  };

  const selectLoyaltyCard = (card: LoyaltyCard) => {
    setLoyaltyCard(card);
    setLoyaltyCardNumber(card.card_number);
    setLoyaltyCardError('');
    if (card.has_employee_benefits) setIsEmployeeOrder(true);
    const [fn, ...rest] = String(card.full_name || '').split(' ');
    if (!firstName && fn) setFirstName(fn);
    if (!lastName && rest.length) setLastName(rest.join(' '));
    setCardSearchVisible(false);
    setCardSearchQuery('');
    setCardSearchResults([]);
    setCardSearched(false);
  };

  const openCardSearch = () => {
    setCardSearchQuery('');
    setCardSearchResults([]);
    setCardSearched(false);
    setCardSearchVisible(true);
  };

  const openQrScanner = async () => {
    if (!cameraPermission?.granted) {
      const result = await requestCameraPermission();
      if (!result.granted) {
        Alert.alert('Permission refusée', 'L\'accès à la caméra est requis pour scanner le QR code.');
        return;
      }
    }
    scannerLocked.current = false;
    setScannerVisible(true);
  };

  const onQrScanned = async ({ data }: { data: string }) => {
    if (scannerLocked.current) return;
    const cleaned = data.replace(/\s/g, '');
    if (!/^\d{12}$/.test(cleaned)) return; // pas un numéro de carte valide
    scannerLocked.current = true;
    setScannerVisible(false);
    setLoyaltyCardNumber(cleaned);
    // Vérification automatique
    setCheckingCard(true);
    setLoyaltyCardError('');
    try {
      const { data: res } = await api.post('/loyalty-cards/check', { card_number: cleaned });
      if (res.found) {
        setLoyaltyCard(res.card);
        if (res.card.has_employee_benefits) setIsEmployeeOrder(true);
        const [fn, ...rest] = String(res.card.full_name || '').split(' ');
        if (!firstName && fn) setFirstName(fn);
        if (!lastName && rest.length) setLastName(rest.join(' '));
      } else {
        setLoyaltyCard(null);
        setLoyaltyCardError(res.message);
      }
    } catch {
      setLoyaltyCardError('Erreur lors de la vérification.');
    } finally {
      setCheckingCard(false);
    }
  };

  // Debounce recherche carte fidélité
  useEffect(() => {
    if (!cardSearchVisible) return;
    if (cardSearchDebounce.current) clearTimeout(cardSearchDebounce.current);
    const q = cardSearchQuery.trim();
    if (q.length < 2) {
      setCardSearchResults([]);
      setCardSearched(false);
      return;
    }
    cardSearchDebounce.current = setTimeout(async () => {
      setCardSearchLoading(true);
      try {
        const { data } = await api.get('/loyalty-cards', { params: { q } });
        setCardSearchResults(data.data ?? []);
        setCardSearched(true);
      } catch {
        setCardSearchResults([]);
        setCardSearched(true);
      } finally {
        setCardSearchLoading(false);
      }
    }, 350);
    return () => {
      if (cardSearchDebounce.current) clearTimeout(cardSearchDebounce.current);
    };
  }, [cardSearchQuery, cardSearchVisible]);

  const toggleDiscount = (id: number) => {
    setPinError('');
    setSelectedDiscountIds((prev) =>
      prev.includes(id) ? prev.filter((d) => d !== id) : [...prev, id]
    );
  };

  const goToStep2 = () => {
    if (selectedDiscountIds.length > 0 && !cardPin.trim()) {
      setPinError('Le code PIN est requis pour utiliser une réduction.');
      return;
    }
    setPinError('');
    setStep(2);
  };

  // ─────────── Étape 2 : Panier ───────────

  const addToCart = (drink: Drink) => {
    setCart((prev) => {
      const existing = prev.find((i) => i.type === 'drink' && i.drink.id === drink.id);
      if (existing) {
        return prev.map((i) =>
          i.uid === existing.uid ? { ...i, quantity: i.quantity + 1 } : i
        );
      }
      return [...prev, { uid: nextCartUid(), type: 'drink', drink, quantity: 1 }];
    });
    setDrinkModalVisible(false);
    setDrinkSearch('');
  };

  const openCustomModal = () => {
    setCustomLabel('');
    setCustomPrice('');
    setCustomQuantity('1');
    setCustomError('');
    setCustomModalVisible(true);
  };

  const addCustomItem = () => {
    const label = customLabel.trim();
    const price = parseFloat(customPrice.replace(',', '.'));
    const qty = parseInt(customQuantity, 10);

    if (!label) {
      setCustomError('Le libellé est obligatoire.');
      return;
    }
    if (!Number.isFinite(price) || price < 0.01 || price > 999.99) {
      setCustomError('Le tarif doit être compris entre 0,01 € et 999,99 €.');
      return;
    }
    if (!Number.isFinite(qty) || qty < 1 || qty > 20) {
      setCustomError('La quantité doit être comprise entre 1 et 20.');
      return;
    }

    setCart((prev) => [
      ...prev,
      { uid: nextCartUid(), type: 'custom', label, unitPrice: Math.round(price * 100) / 100, quantity: qty },
    ]);
    setCustomModalVisible(false);
  };

  const updateQuantity = (uid: string, delta: number) => {
    setCart((prev) =>
      prev
        .map((i) => (i.uid === uid ? { ...i, quantity: i.quantity + delta } : i))
        .filter((i) => i.quantity > 0)
    );
  };

  const removeItem = (uid: string) => {
    setCart((prev) => prev.filter((i) => i.uid !== uid));
  };

  const itemUnitPrice = (i: CartItem) => (i.type === 'drink' ? i.drink.price : i.unitPrice);
  const itemLabel = (i: CartItem) => (i.type === 'drink' ? i.drink.name : i.label);

  const totals = useMemo(() => {
    const subtotal = cart.reduce((sum, i) => sum + itemUnitPrice(i) * i.quantity, 0);
    let remaining = subtotal;
    let loyaltyDiscount = 0;

    if (loyaltyCard && selectedDiscountIds.length > 0) {
      const sel = discounts.filter((d) => selectedDiscountIds.includes(d.id));
      for (const d of sel) {
        let amount = d.discount_type === 'percent'
          ? remaining * (d.discount_value / 100)
          : Math.min(remaining, d.discount_value);
        if (d.max_discount_amount) amount = Math.min(amount, d.max_discount_amount);
        remaining -= amount;
        loyaltyDiscount += amount;
      }
    }

    const employeeDiscount = isEmployeeOrder ? Math.round(remaining * 0.15 * 100) / 100 : 0;
    const total = Math.max(0, remaining - employeeDiscount);
    const pointsCost = selectedDiscountIds.reduce((sum, id) => {
      const d = discounts.find((disc) => disc.id === id);
      return sum + (d?.points_cost ?? 0);
    }, 0);
    return { subtotal, loyaltyDiscount, employeeDiscount, total, pointsCost };
  }, [cart, loyaltyCard, selectedDiscountIds, discounts, isEmployeeOrder]);

  const handleSubmit = async () => {
    if (cart.length === 0) {
      Alert.alert('Panier vide', 'Ajoutez au moins un article.');
      return;
    }

    setSubmitting(true);
    try {
      const combinedName = `${firstName.trim()} ${lastName.trim()}`.trim();
      const payload: Record<string, any> = {
        items: cart.map((i) =>
          i.type === 'drink'
            ? { drink_id: i.drink.id, quantity: i.quantity }
            : { custom_label: i.label, custom_price: i.unitPrice, quantity: i.quantity }
        ),
        is_employee_order: isEmployeeOrder,
        notes: notes.trim() || undefined,
      };
      if (loyaltyCard) {
        payload.loyalty_card_number = loyaltyCard.card_number;
      } else if (combinedName) {
        payload.customer_name = combinedName;
      }
      if (selectedDiscountIds.length > 0) {
        payload.loyalty_discount_ids = selectedDiscountIds;
        payload.card_pin = cardPin;
      }

      await api.post('/orders', payload);
      Alert.alert('Succès', 'Commande créée avec succès.', [
        { text: 'OK', onPress: () => navigation.goBack() },
      ]);
    } catch (err: any) {
      const msg = err?.response?.data?.message
        ?? Object.values(err?.response?.data?.errors ?? {}).flat().join('\n')
        ?? 'Une erreur est survenue.';
      Alert.alert('Erreur', msg);
    } finally {
      setSubmitting(false);
    }
  };

  const filteredDrinks = drinks.filter((d) =>
    d.name.toLowerCase().includes(drinkSearch.toLowerCase()) ||
    (d.category?.name ?? '').toLowerCase().includes(drinkSearch.toLowerCase())
  );

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#92400e" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* ── Stepper ── */}
      <View style={styles.stepper}>
        <StepDot n={1} label="Client" active={step === 1} done={step > 1} onPress={() => setStep(1)} />
        <View style={[styles.stepLine, step > 1 && styles.stepLineActive]} />
        <StepDot n={2} label="Articles" active={step === 2} done={false} onPress={() => cart.length > 0 || step === 2 ? setStep(2) : undefined} />
      </View>

      <ScrollView
        style={{ flex: 1 }}
        contentContainerStyle={{ padding: 16, paddingBottom: 40 }}
        keyboardShouldPersistTaps="handled"
      >
        {step === 1 ? (
          <Step1Client
            firstName={firstName} setFirstName={setFirstName}
            lastName={lastName} setLastName={setLastName}
            loyaltyCardNumber={loyaltyCardNumber} setLoyaltyCardNumber={setLoyaltyCardNumber}
            loyaltyCard={loyaltyCard} loyaltyCardError={loyaltyCardError}
            checkingCard={checkingCard} onCheck={checkLoyaltyCard} onClearCard={clearLoyaltyCard}
            onOpenCardSearch={openCardSearch}
            onOpenScanner={openQrScanner}
            discounts={discounts} selectedDiscountIds={selectedDiscountIds} onToggleDiscount={toggleDiscount}
            cardPin={cardPin} setCardPin={setCardPin} pinError={pinError}
            isEmployeeOrder={isEmployeeOrder} setIsEmployeeOrder={setIsEmployeeOrder}
          />
        ) : (
          <Step2Cart
            firstName={firstName} lastName={lastName} loyaltyCard={loyaltyCard} isEmployeeOrder={isEmployeeOrder}
            cart={cart} onOpenDrinkModal={() => setDrinkModalVisible(true)} onOpenCustomModal={openCustomModal}
            onUpdateQty={updateQuantity} onRemove={removeItem}
            itemLabel={itemLabel} itemUnitPrice={itemUnitPrice}
            notes={notes} setNotes={setNotes}
            totals={totals}
          />
        )}
      </ScrollView>

      {/* ── Barre navigation ── */}
      <View style={styles.footer}>
        {step === 1 ? (
          <TouchableOpacity style={styles.nextBtn} onPress={goToStep2}>
            <Text style={styles.nextBtnText}>Suivant · Articles →</Text>
          </TouchableOpacity>
        ) : (
          <View style={styles.footerRow}>
            <TouchableOpacity style={styles.backBtn} onPress={() => setStep(1)} disabled={submitting}>
              <Text style={styles.backBtnText}>← Client</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.submitBtn, (submitting || cart.length === 0) && styles.submitBtnDisabled]}
              onPress={handleSubmit}
              disabled={submitting || cart.length === 0}
            >
              {submitting ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={styles.submitBtnText}>Créer · {totals.total.toFixed(2)} €</Text>
              )}
            </TouchableOpacity>
          </View>
        )}
      </View>

      {/* ── Modal sélection boisson ── */}
      <Modal visible={drinkModalVisible} animationType="slide" presentationStyle="pageSheet">
        <View style={styles.modalContainer}>
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitle}>Choisir une boisson</Text>
            <TouchableOpacity onPress={() => { setDrinkModalVisible(false); setDrinkSearch(''); }}>
              <Text style={styles.modalClose}>Fermer</Text>
            </TouchableOpacity>
          </View>
          <TextInput
            style={styles.modalSearch}
            placeholder="Rechercher…"
            placeholderTextColor="#9ca3af"
            value={drinkSearch}
            onChangeText={setDrinkSearch}
          />
          <FlatList
            data={filteredDrinks}
            keyExtractor={(d) => String(d.id)}
            ListEmptyComponent={<Text style={styles.empty}>Aucune boisson disponible.</Text>}
            renderItem={({ item: drink }) => (
              <TouchableOpacity style={styles.modalDrinkRow} onPress={() => addToCart(drink)}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.modalDrinkName}>{drink.name}</Text>
                  {drink.category && <Text style={styles.modalDrinkCat}>{drink.category.name}</Text>}
                </View>
                <Text style={styles.modalDrinkPrice}>{drink.price.toFixed(2)} €</Text>
              </TouchableOpacity>
            )}
          />
        </View>
      </Modal>

      {/* ── Modal article libre ── */}
      <Modal visible={customModalVisible} animationType="slide" presentationStyle="pageSheet">
        <KeyboardAvoidingView
          style={{ flex: 1 }}
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        >
          <View style={styles.modalContainer}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Article libre</Text>
              <TouchableOpacity onPress={() => setCustomModalVisible(false)}>
                <Text style={styles.modalClose}>Annuler</Text>
              </TouchableOpacity>
            </View>
            <ScrollView contentContainerStyle={{ padding: 16 }} keyboardShouldPersistTaps="handled">
              <Text style={styles.subLabel}>Libellé</Text>
              <TextInput
                style={styles.input}
                placeholder="Ex. Supplément lait végétal"
                placeholderTextColor="#9ca3af"
                value={customLabel}
                onChangeText={setCustomLabel}
                maxLength={150}
                autoFocus
              />
              <Text style={styles.subLabel}>Tarif unitaire (€)</Text>
              <TextInput
                style={styles.input}
                placeholder="0.00"
                placeholderTextColor="#9ca3af"
                value={customPrice}
                onChangeText={setCustomPrice}
                keyboardType="decimal-pad"
              />
              <Text style={styles.subLabel}>Quantité</Text>
              <TextInput
                style={styles.input}
                placeholder="1"
                placeholderTextColor="#9ca3af"
                value={customQuantity}
                onChangeText={setCustomQuantity}
                keyboardType="number-pad"
              />
              {customError ? <Text style={styles.error}>{customError}</Text> : null}
              <TouchableOpacity style={styles.submitBtn} onPress={addCustomItem}>
                <Text style={styles.submitBtnText}>Ajouter au panier</Text>
              </TouchableOpacity>
            </ScrollView>
          </View>
        </KeyboardAvoidingView>
      </Modal>

      {/* ── Modal recherche client (carte fidélité) ── */}
      <Modal visible={cardSearchVisible} animationType="slide" presentationStyle="pageSheet">
        <View style={styles.modalContainer}>
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitle}>Rechercher un client</Text>
            <TouchableOpacity onPress={() => setCardSearchVisible(false)}>
              <Text style={styles.modalClose}>Fermer</Text>
            </TouchableOpacity>
          </View>
          <TextInput
            style={styles.modalSearch}
            placeholder="Nom, prénom, e-mail, téléphone ou n° de carte…"
            placeholderTextColor="#9ca3af"
            value={cardSearchQuery}
            onChangeText={setCardSearchQuery}
            autoFocus
            autoCapitalize="none"
            autoCorrect={false}
          />
          {cardSearchLoading ? (
            <ActivityIndicator style={{ marginTop: 20 }} color="#92400e" />
          ) : cardSearchQuery.trim().length < 2 ? (
            <Text style={styles.empty}>Saisissez au moins 2 caractères pour lancer la recherche.</Text>
          ) : cardSearched && cardSearchResults.length === 0 ? (
            <Text style={styles.empty}>Aucun client trouvé.</Text>
          ) : (
            <FlatList
              data={cardSearchResults}
              keyExtractor={(c) => String(c.id)}
              keyboardShouldPersistTaps="handled"
              renderItem={({ item: c }) => (
                <TouchableOpacity style={styles.modalCardRow} onPress={() => selectLoyaltyCard(c)}>
                  <View style={{ flex: 1 }}>
                    <View style={styles.modalCardHeader}>
                      <Text style={styles.modalCardName}>{c.full_name}</Text>
                      {c.has_employee_benefits && (
                        <View style={styles.badgeEmployee}>
                          <Text style={[styles.badgeText, { color: '#92400e' }]}>Salarié</Text>
                        </View>
                      )}
                    </View>
                    <Text style={styles.modalCardNum}>{c.card_number}</Text>
                    {c.email && <Text style={styles.modalCardContact}>{c.email}</Text>}
                    {c.phone && <Text style={styles.modalCardContact}>{c.phone}</Text>}
                    <Text style={styles.modalCardPoints}>🏆 {c.points} point{c.points > 1 ? 's' : ''}</Text>
                  </View>
                  <Text style={styles.chevron}>›</Text>
                </TouchableOpacity>
              )}
            />
          )}
        </View>
      </Modal>

      {/* ── Modal scanner QR carte fidélité ── */}
      <Modal visible={scannerVisible} animationType="slide" presentationStyle="fullScreen">
        <View style={{ flex: 1, backgroundColor: '#000' }}>
          <View style={styles.scannerHeader}>
            <Text style={styles.scannerTitle}>Scanner la carte de fidélité</Text>
            <TouchableOpacity onPress={() => setScannerVisible(false)} style={styles.scannerCloseBtn}>
              <Text style={styles.scannerCloseText}>Annuler</Text>
            </TouchableOpacity>
          </View>
          <CameraView
            style={{ flex: 1 }}
            facing="back"
            barcodeScannerSettings={{ barcodeTypes: ['qr', 'code128'] }}
            onBarcodeScanned={onQrScanned}
          />
          <View style={styles.scannerOverlay}>
            <View style={styles.scannerFrame} />
            <Text style={styles.scannerHint}>Pointez vers le QR code ou le code-barres de la carte</Text>
          </View>
        </View>
      </Modal>
    </View>
  );
}

// ─────────── Composants ───────────

function StepDot({ n, label, active, done, onPress }: {
  n: number; label: string; active: boolean; done: boolean; onPress?: () => void;
}) {
  return (
    <TouchableOpacity style={styles.stepItem} onPress={onPress} disabled={!onPress}>
      <View style={[styles.stepCircle, active && styles.stepCircleActive, done && styles.stepCircleDone]}>
        <Text style={[styles.stepCircleText, (active || done) && styles.stepCircleTextActive]}>
          {done ? '✓' : n}
        </Text>
      </View>
      <Text style={[styles.stepLabel, active && styles.stepLabelActive]}>{label}</Text>
    </TouchableOpacity>
  );
}

function Step1Client(props: {
  firstName: string; setFirstName: (v: string) => void;
  lastName: string; setLastName: (v: string) => void;
  loyaltyCardNumber: string; setLoyaltyCardNumber: (v: string) => void;
  loyaltyCard: LoyaltyCard | null; loyaltyCardError: string;
  checkingCard: boolean; onCheck: () => void; onClearCard: () => void;
  onOpenCardSearch: () => void;
  onOpenScanner: () => void;
  discounts: LoyaltyDiscount[]; selectedDiscountIds: number[]; onToggleDiscount: (id: number) => void;
  cardPin: string; setCardPin: (v: string) => void; pinError: string;
  isEmployeeOrder: boolean; setIsEmployeeOrder: (v: boolean) => void;
}) {
  const {
    firstName, setFirstName, lastName, setLastName,
    loyaltyCardNumber, setLoyaltyCardNumber, loyaltyCard, loyaltyCardError,
    checkingCard, onCheck, onClearCard, onOpenCardSearch, onOpenScanner,
    discounts, selectedDiscountIds, onToggleDiscount,
    cardPin, setCardPin, pinError,
    isEmployeeOrder, setIsEmployeeOrder,
  } = props;

  return (
    <View>
      <Text style={styles.sectionTitle}>Identité du client</Text>
      <View style={styles.card}>
        <Text style={styles.subLabel}>Prénom</Text>
        <TextInput
          style={styles.input}
          placeholder="Prénom"
          placeholderTextColor="#9ca3af"
          value={firstName}
          onChangeText={setFirstName}
          autoCapitalize="words"
        />
        <Text style={styles.subLabel}>Nom</Text>
        <TextInput
          style={[styles.input, { marginBottom: 0 }]}
          placeholder="Nom"
          placeholderTextColor="#9ca3af"
          value={lastName}
          onChangeText={setLastName}
          autoCapitalize="words"
        />
      </View>

      <Text style={styles.sectionTitle}>Carte de fidélité</Text>
      <View style={styles.card}>
        {!loyaltyCard ? (
          <>
            <TouchableOpacity style={styles.searchClientBtn} onPress={onOpenCardSearch}>
              <Text style={styles.searchClientBtnIcon}>🔍</Text>
              <Text style={styles.searchClientBtnText}>Rechercher un client (nom, e-mail, tél.)</Text>
            </TouchableOpacity>

            <View style={styles.divider}>
              <View style={styles.dividerLine} />
              <Text style={styles.dividerText}>ou par n° de carte</Text>
              <View style={styles.dividerLine} />
            </View>

            <TouchableOpacity style={styles.scanQrBtn} onPress={onOpenScanner}>
              <Text style={styles.scanQrIcon}>📷</Text>
              <Text style={styles.scanQrText}>Scanner le QR code / code-barres</Text>
            </TouchableOpacity>

            <View style={styles.cardNumberRow}>
              <TextInput
                style={[styles.input, { flex: 1, marginBottom: 0 }]}
                placeholder="N° de carte (12 chiffres)"
                placeholderTextColor="#9ca3af"
                value={loyaltyCardNumber}
                onChangeText={setLoyaltyCardNumber}
                keyboardType="numeric"
              />
              <TouchableOpacity
                style={styles.checkBtn}
                onPress={onCheck}
                disabled={checkingCard || !loyaltyCardNumber}
              >
                {checkingCard ? (
                  <ActivityIndicator size="small" color="#fff" />
                ) : (
                  <Text style={styles.checkBtnText}>Vérifier</Text>
                )}
              </TouchableOpacity>
            </View>
            {loyaltyCardError ? <Text style={styles.error}>{loyaltyCardError}</Text> : null}
            <Text style={styles.hint}>Facultatif — laissez vide pour un client anonyme.</Text>
          </>
        ) : (
          <>
            <View style={styles.loyaltyCardFound}>
              <View style={{ flex: 1 }}>
                <Text style={styles.loyaltyCardName}>🎁 {loyaltyCard.full_name}</Text>
                <Text style={styles.loyaltyCardNum}>{loyaltyCard.card_number}</Text>
                <Text style={styles.loyaltyPoints}>
                  {loyaltyCard.points} point{loyaltyCard.points > 1 ? 's' : ''}
                  {loyaltyCard.has_employee_benefits ? ' · Salarié' : ''}
                </Text>
              </View>
              <TouchableOpacity onPress={onClearCard}>
                <Text style={styles.clearBtn}>✕</Text>
              </TouchableOpacity>
            </View>

            {discounts.length > 0 && (
              <>
                <Text style={styles.subLabel}>Réductions disponibles</Text>
                {discounts
                  .filter((d) => !d.employee_only || loyaltyCard.has_employee_benefits)
                  .filter((d) => d.points_cost <= loyaltyCard.points)
                  .map((d) => (
                    <TouchableOpacity
                      key={d.id}
                      style={styles.discountRow}
                      onPress={() => onToggleDiscount(d.id)}
                    >
                      <View style={[styles.checkbox, selectedDiscountIds.includes(d.id) && styles.checkboxChecked]}>
                        {selectedDiscountIds.includes(d.id) && <Text style={styles.checkmark}>✓</Text>}
                      </View>
                      <View style={{ flex: 1 }}>
                        <Text style={styles.discountName}>{d.name}</Text>
                        <Text style={styles.discountDetail}>
                          {d.points_cost} pts —{' '}
                          {d.discount_type === 'percent'
                            ? `${d.discount_value}%${d.max_discount_amount ? ` (max ${d.max_discount_amount}€)` : ''}`
                            : `−${d.discount_value}€`}
                        </Text>
                      </View>
                    </TouchableOpacity>
                  ))}
                {selectedDiscountIds.length > 0 && (
                  <>
                    <Text style={[styles.subLabel, { marginTop: 12 }]}>Code PIN de la carte</Text>
                    <TextInput
                      style={[styles.input, { marginBottom: 0 }]}
                      placeholder="Saisir le PIN"
                      placeholderTextColor="#9ca3af"
                      secureTextEntry
                      keyboardType="numeric"
                      value={cardPin}
                      onChangeText={setCardPin}
                    />
                    {pinError ? <Text style={[styles.error, { marginTop: 6 }]}>{pinError}</Text> : null}
                  </>
                )}
                {discounts.filter((d) => !d.employee_only || loyaltyCard.has_employee_benefits).filter((d) => d.points_cost <= loyaltyCard.points).length === 0 && (
                  <Text style={styles.hint}>Aucune réduction disponible avec le solde de points actuel.</Text>
                )}
              </>
            )}
          </>
        )}
      </View>

      <Text style={styles.sectionTitle}>Options</Text>
      <View style={styles.card}>
        <View style={styles.switchRow}>
          <View style={{ flex: 1 }}>
            <Text style={styles.switchLabel}>Commande salarié</Text>
            <Text style={styles.hint}>Applique automatiquement −15 % sur le total.</Text>
          </View>
          <Switch
            value={isEmployeeOrder}
            onValueChange={setIsEmployeeOrder}
            trackColor={{ true: '#92400e' }}
            thumbColor="#fff"
            disabled={loyaltyCard?.has_employee_benefits}
          />
        </View>
      </View>
    </View>
  );
}

function Step2Cart(props: {
  firstName: string; lastName: string; loyaltyCard: LoyaltyCard | null; isEmployeeOrder: boolean;
  cart: CartItem[]; onOpenDrinkModal: () => void; onOpenCustomModal: () => void;
  onUpdateQty: (uid: string, delta: number) => void; onRemove: (uid: string) => void;
  itemLabel: (i: CartItem) => string; itemUnitPrice: (i: CartItem) => number;
  notes: string; setNotes: (v: string) => void;
  totals: { subtotal: number; loyaltyDiscount: number; employeeDiscount: number; total: number; pointsCost: number };
}) {
  const {
    firstName, lastName, loyaltyCard, isEmployeeOrder,
    cart, onOpenDrinkModal, onOpenCustomModal, onUpdateQty, onRemove,
    itemLabel, itemUnitPrice, notes, setNotes, totals,
  } = props;

  const clientDisplayName = loyaltyCard
    ? loyaltyCard.full_name
    : `${firstName} ${lastName}`.trim() || 'Client anonyme';

  return (
    <View>
      {/* Récap client */}
      <View style={styles.clientRecap}>
        <Text style={styles.clientRecapLabel}>Client</Text>
        <Text style={styles.clientRecapName}>{clientDisplayName}</Text>
        <View style={styles.clientRecapBadges}>
          {loyaltyCard && (
            <View style={styles.badge}><Text style={styles.badgeText}>🎁 Fidélité</Text></View>
          )}
          {isEmployeeOrder && (
            <View style={[styles.badge, styles.badgeEmployee]}>
              <Text style={[styles.badgeText, { color: '#92400e' }]}>−15 % salarié</Text>
            </View>
          )}
        </View>
      </View>

      <View style={styles.cartHeader}>
        <Text style={styles.sectionTitle}>Articles</Text>
        <View style={styles.cartHeaderBtns}>
          <TouchableOpacity style={styles.addCustomBtn} onPress={onOpenCustomModal}>
            <Text style={styles.addCustomBtnText}>+ Libre</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.addDrinkBtn} onPress={onOpenDrinkModal}>
            <Text style={styles.addDrinkBtnText}>+ Boisson</Text>
          </TouchableOpacity>
        </View>
      </View>

      {cart.length === 0 ? (
        <View style={[styles.card, styles.emptyCart]}>
          <Text style={styles.emptyCartText}>Aucun article ajouté</Text>
          <Text style={styles.hint}>Utilisez « + Boisson » pour choisir dans le menu ou « + Libre » pour saisir un article manuellement.</Text>
        </View>
      ) : (
        <View style={styles.card}>
          {cart.map((item) => (
            <View key={item.uid} style={styles.cartRow}>
              <View style={{ flex: 1 }}>
                <Text style={styles.cartItemName} numberOfLines={1}>
                  {itemLabel(item)}
                  {item.type === 'custom' && <Text style={styles.customBadge}>  libre</Text>}
                </Text>
                <Text style={styles.cartItemPrice}>{itemUnitPrice(item).toFixed(2)} €</Text>
              </View>
              <View style={styles.qtyControls}>
                <TouchableOpacity style={styles.qtyBtn} onPress={() => onUpdateQty(item.uid, -1)}>
                  <Text style={styles.qtyBtnText}>−</Text>
                </TouchableOpacity>
                <Text style={styles.qty}>{item.quantity}</Text>
                <TouchableOpacity style={styles.qtyBtn} onPress={() => onUpdateQty(item.uid, 1)}>
                  <Text style={styles.qtyBtnText}>+</Text>
                </TouchableOpacity>
                <TouchableOpacity style={styles.removeBtn} onPress={() => onRemove(item.uid)}>
                  <Text style={styles.removeBtnText}>✕</Text>
                </TouchableOpacity>
              </View>
            </View>
          ))}
        </View>
      )}

      <Text style={styles.sectionTitle}>Notes</Text>
      <View style={styles.card}>
        <TextInput
          style={[styles.input, { marginBottom: 0, height: 72, textAlignVertical: 'top' }]}
          placeholder="Instructions particulières…"
          placeholderTextColor="#9ca3af"
          multiline
          value={notes}
          onChangeText={setNotes}
        />
      </View>

      {cart.length > 0 && (
        <View style={[styles.card, { marginTop: 8 }]}>
          <View style={styles.summaryRow}>
            <Text style={styles.summaryLabel}>Sous-total</Text>
            <Text style={styles.summaryValue}>{totals.subtotal.toFixed(2)} €</Text>
          </View>
          {totals.loyaltyDiscount > 0 && (
            <View style={styles.summaryRow}>
              <Text style={styles.summaryLabel}>Réductions fidélité</Text>
              <Text style={[styles.summaryValue, { color: '#22c55e' }]}>−{totals.loyaltyDiscount.toFixed(2)} €</Text>
            </View>
          )}
          {totals.employeeDiscount > 0 && (
            <View style={styles.summaryRow}>
              <Text style={styles.summaryLabel}>Réduction salarié (15%)</Text>
              <Text style={[styles.summaryValue, { color: '#22c55e' }]}>−{totals.employeeDiscount.toFixed(2)} €</Text>
            </View>
          )}
          {totals.pointsCost > 0 && (
            <View style={styles.summaryRow}>
              <Text style={styles.summaryLabel}>Points utilisés</Text>
              <Text style={[styles.summaryValue, { color: '#d97706' }]}>−{totals.pointsCost} pts</Text>
            </View>
          )}
          <View style={[styles.summaryRow, { borderTopWidth: 1, borderTopColor: '#e5e7eb', paddingTop: 8, marginTop: 4 }]}>
            <Text style={{ fontSize: 16, fontWeight: '700', color: '#1f2937' }}>Total</Text>
            <Text style={{ fontSize: 18, fontWeight: '700', color: '#92400e' }}>{totals.total.toFixed(2)} €</Text>
          </View>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },

  // Stepper
  stepper: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    backgroundColor: '#fff', paddingVertical: 12, paddingHorizontal: 20,
    borderBottomWidth: 1, borderBottomColor: '#e5e7eb',
  },
  stepItem: { alignItems: 'center', width: 90 },
  stepCircle: {
    width: 32, height: 32, borderRadius: 16,
    backgroundColor: '#f3f4f6', borderWidth: 2, borderColor: '#d1d5db',
    justifyContent: 'center', alignItems: 'center', marginBottom: 4,
  },
  stepCircleActive: { backgroundColor: '#92400e', borderColor: '#92400e' },
  stepCircleDone: { backgroundColor: '#16a34a', borderColor: '#16a34a' },
  stepCircleText: { fontSize: 14, fontWeight: '700', color: '#6b7280' },
  stepCircleTextActive: { color: '#fff' },
  stepLabel: { fontSize: 12, color: '#9ca3af', fontWeight: '600' },
  stepLabelActive: { color: '#1f2937' },
  stepLine: { width: 40, height: 2, backgroundColor: '#e5e7eb', marginBottom: 20 },
  stepLineActive: { backgroundColor: '#16a34a' },

  // Sections
  sectionTitle: { fontSize: 13, fontWeight: '700', color: '#92400e', textTransform: 'uppercase', letterSpacing: 1, marginTop: 20, marginBottom: 8 },
  card: { backgroundColor: '#fff', borderRadius: 12, padding: 14, marginBottom: 8, shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.06, shadowRadius: 4, elevation: 2 },

  // Inputs
  input: { borderWidth: 1, borderColor: '#d1d5db', borderRadius: 8, paddingHorizontal: 12, paddingVertical: 10, fontSize: 15, color: '#111827', backgroundColor: '#f9fafb', marginBottom: 10 },
  subLabel: { fontSize: 13, fontWeight: '600', color: '#6b7280', marginBottom: 6, marginTop: 4 },
  hint: { fontSize: 12, color: '#9ca3af', marginTop: 6 },
  error: { color: '#ef4444', fontSize: 13, marginTop: 6 },

  // Carte fidélité
  cardNumberRow: { flexDirection: 'row', gap: 8, alignItems: 'center' },
  checkBtn: { backgroundColor: '#92400e', paddingHorizontal: 14, paddingVertical: 10, borderRadius: 8 },
  checkBtnText: { color: '#fff', fontWeight: '600' },
  loyaltyCardFound: { flexDirection: 'row', alignItems: 'center', marginBottom: 12 },
  loyaltyCardName: { fontSize: 16, fontWeight: '700', color: '#1f2937' },
  loyaltyCardNum: { fontSize: 13, color: '#9ca3af', fontFamily: 'monospace' },
  loyaltyPoints: { fontSize: 14, color: '#d97706', fontWeight: '600' },
  clearBtn: { fontSize: 18, color: '#9ca3af', padding: 4 },

  // Réductions
  discountRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 8, borderTopWidth: 1, borderTopColor: '#f3f4f6' },
  checkbox: { width: 22, height: 22, borderRadius: 5, borderWidth: 2, borderColor: '#d1d5db', justifyContent: 'center', alignItems: 'center', marginRight: 10 },
  checkboxChecked: { backgroundColor: '#92400e', borderColor: '#92400e' },
  checkmark: { color: '#fff', fontWeight: '700', fontSize: 13 },
  discountName: { fontSize: 14, fontWeight: '600', color: '#1f2937' },
  discountDetail: { fontSize: 12, color: '#6b7280' },

  // Switch
  switchRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  switchLabel: { fontSize: 15, color: '#374151', fontWeight: '600' },

  // Récap client (étape 2)
  clientRecap: {
    backgroundColor: '#fff', borderRadius: 12, padding: 14, marginTop: 8,
    borderLeftWidth: 4, borderLeftColor: '#92400e',
  },
  clientRecapLabel: { fontSize: 11, fontWeight: '700', color: '#92400e', textTransform: 'uppercase', letterSpacing: 1 },
  clientRecapName: { fontSize: 17, fontWeight: '700', color: '#1f2937', marginTop: 2 },
  clientRecapBadges: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginTop: 8 },
  badge: { paddingHorizontal: 10, paddingVertical: 3, borderRadius: 10, backgroundColor: '#f3f4f6' },
  badgeEmployee: { backgroundColor: '#fef3c7' },
  badgeText: { fontSize: 12, fontWeight: '600', color: '#374151' },

  // Panier
  cartHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  cartHeaderBtns: { flexDirection: 'row', gap: 8 },
  addDrinkBtn: { backgroundColor: '#d97706', paddingHorizontal: 14, paddingVertical: 6, borderRadius: 16 },
  addDrinkBtnText: { color: '#fff', fontWeight: '700', fontSize: 13 },
  addCustomBtn: { backgroundColor: '#fff', borderWidth: 1, borderColor: '#d97706', paddingHorizontal: 14, paddingVertical: 6, borderRadius: 16 },
  addCustomBtnText: { color: '#d97706', fontWeight: '700', fontSize: 13 },
  emptyCart: { alignItems: 'center', paddingVertical: 20 },
  emptyCartText: { color: '#9ca3af', fontSize: 15 },
  cartRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: '#f3f4f6' },
  cartItemName: { fontSize: 15, color: '#1f2937' },
  customBadge: { fontSize: 11, color: '#d97706', fontWeight: '600', textTransform: 'uppercase' },
  cartItemPrice: { fontSize: 13, color: '#6b7280', marginTop: 2 },
  qtyControls: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  qtyBtn: { width: 28, height: 28, borderRadius: 14, backgroundColor: '#f3f4f6', justifyContent: 'center', alignItems: 'center' },
  qtyBtnText: { fontSize: 18, fontWeight: '700', color: '#374151', lineHeight: 22 },
  qty: { fontSize: 16, fontWeight: '700', color: '#1f2937', minWidth: 20, textAlign: 'center' },
  removeBtn: { width: 28, height: 28, borderRadius: 14, justifyContent: 'center', alignItems: 'center', marginLeft: 4 },
  removeBtnText: { fontSize: 14, color: '#ef4444', fontWeight: '700' },

  // Récap totaux
  summaryRow: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 4 },
  summaryLabel: { fontSize: 14, color: '#6b7280' },
  summaryValue: { fontSize: 14, color: '#374151', fontWeight: '500' },

  // Footer / navigation
  footer: {
    backgroundColor: '#fff', paddingHorizontal: 16, paddingVertical: 12,
    borderTopWidth: 1, borderTopColor: '#e5e7eb',
    shadowColor: '#000', shadowOffset: { width: 0, height: -2 }, shadowOpacity: 0.06, shadowRadius: 4, elevation: 4,
  },
  footerRow: { flexDirection: 'row', gap: 10 },
  backBtn: { paddingHorizontal: 20, paddingVertical: 14, borderRadius: 12, borderWidth: 1, borderColor: '#d1d5db', justifyContent: 'center' },
  backBtnText: { color: '#6b7280', fontSize: 15, fontWeight: '600' },
  nextBtn: { backgroundColor: '#92400e', borderRadius: 12, paddingVertical: 16, alignItems: 'center' },
  nextBtnText: { color: '#fff', fontSize: 16, fontWeight: '700' },
  submitBtn: { backgroundColor: '#92400e', borderRadius: 12, paddingVertical: 16, alignItems: 'center', flex: 1 },
  submitBtnDisabled: { opacity: 0.5 },
  submitBtnText: { color: '#fff', fontSize: 16, fontWeight: '700' },

  // Modaux
  modalContainer: { flex: 1, backgroundColor: '#fff' },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 16, borderBottomWidth: 1, borderBottomColor: '#e5e7eb' },
  modalTitle: { fontSize: 18, fontWeight: '700', color: '#1f2937' },
  modalClose: { fontSize: 16, color: '#92400e', fontWeight: '600' },
  modalSearch: { margin: 12, borderWidth: 1, borderColor: '#d1d5db', borderRadius: 10, paddingHorizontal: 14, paddingVertical: 10, fontSize: 15, color: '#111827', backgroundColor: '#f9fafb' },
  modalDrinkRow: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: '#f3f4f6' },
  modalDrinkName: { fontSize: 16, fontWeight: '600', color: '#1f2937' },
  modalDrinkCat: { fontSize: 13, color: '#9ca3af' },
  modalDrinkPrice: { fontSize: 16, fontWeight: '700', color: '#92400e' },
  modalCardRow: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#f3f4f6' },
  modalCardHeader: { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 2 },
  modalCardName: { fontSize: 16, fontWeight: '700', color: '#1f2937' },
  modalCardNum: { fontSize: 12, color: '#9ca3af', fontFamily: 'monospace', marginBottom: 2 },
  modalCardContact: { fontSize: 13, color: '#6b7280' },
  modalCardPoints: { fontSize: 13, color: '#d97706', fontWeight: '600', marginTop: 4 },
  chevron: { fontSize: 24, color: '#d1d5db', fontWeight: '400', marginLeft: 8 },
  searchClientBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 10,
    backgroundColor: '#fef3c7', borderRadius: 10,
    paddingHorizontal: 14, paddingVertical: 12, marginBottom: 12,
  },
  searchClientBtnIcon: { fontSize: 18 },
  searchClientBtnText: { color: '#92400e', fontWeight: '600', fontSize: 14, flex: 1 },
  divider: { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 10 },
  dividerLine: { flex: 1, height: 1, backgroundColor: '#e5e7eb' },
  dividerText: { fontSize: 11, color: '#9ca3af', textTransform: 'uppercase', letterSpacing: 0.5 },
  empty: { textAlign: 'center', color: '#9ca3af', marginTop: 40, fontSize: 15, paddingHorizontal: 20 },

  // Scanner QR
  scanQrBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 10,
    backgroundColor: '#e0f2fe', borderRadius: 10,
    paddingHorizontal: 14, paddingVertical: 12, marginBottom: 10,
  },
  scanQrIcon: { fontSize: 20 },
  scanQrText: { color: '#0369a1', fontWeight: '600', fontSize: 14, flex: 1 },
  scannerHeader: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingHorizontal: 16, paddingTop: 56, paddingBottom: 16,
    backgroundColor: 'rgba(0,0,0,0.8)',
  },
  scannerTitle: { color: '#fff', fontSize: 18, fontWeight: '700' },
  scannerCloseBtn: { padding: 8 },
  scannerCloseText: { color: '#fbbf24', fontSize: 16, fontWeight: '600' },
  scannerOverlay: {
    position: 'absolute', bottom: 0, left: 0, right: 0,
    alignItems: 'center', paddingBottom: 60,
  },
  scannerFrame: {
    width: 220, height: 220,
    borderWidth: 2, borderColor: '#fbbf24', borderRadius: 12,
    marginBottom: 24,
  },
  scannerHint: { color: '#fff', fontSize: 14, textAlign: 'center', paddingHorizontal: 40, opacity: 0.85 },
});
