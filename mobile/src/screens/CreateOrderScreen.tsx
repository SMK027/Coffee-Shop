import React, { useEffect, useRef, useState } from 'react';
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
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import api from '../api/client';
import { Drink, LoyaltyCard, LoyaltyDiscount } from '../types';

type CartItem =
  | { uid: string; type: 'drink'; drink: Drink; quantity: number }
  | { uid: string; type: 'custom'; label: string; unitPrice: number; quantity: number };

let cartUidCounter = 0;
const nextCartUid = () => `item-${++cartUidCounter}`;

export default function CreateOrderScreen() {
  const navigation = useNavigation<any>();

  // Données chargées depuis l'API
  const [drinks, setDrinks] = useState<Drink[]>([]);
  const [discounts, setDiscounts] = useState<LoyaltyDiscount[]>([]);
  const [loadingDrinks, setLoadingDrinks] = useState(true);

  // Panier
  const [cart, setCart] = useState<CartItem[]>([]);

  // Client
  const [customerName, setCustomerName] = useState('');
  const [notes, setNotes] = useState('');
  const [isEmployeeOrder, setIsEmployeeOrder] = useState(false);

  // Carte de fidélité
  const [loyaltyCardNumber, setLoyaltyCardNumber] = useState('');
  const [loyaltyCard, setLoyaltyCard] = useState<LoyaltyCard | null>(null);
  const [loyaltyCardError, setLoyaltyCardError] = useState('');
  const [checkingCard, setCheckingCard] = useState(false);

  // PIN
  const [cardPin, setCardPin] = useState('');
  const [selectedDiscountIds, setSelectedDiscountIds] = useState<number[]>([]);

  // Modal sélection boisson
  const [drinkModalVisible, setDrinkModalVisible] = useState(false);
  const [drinkSearch, setDrinkSearch] = useState('');

  // Modal article libre
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
    ]).finally(() => setLoadingDrinks(false));
  }, []);

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
    setSelectedDiscountIds([]);
    setIsEmployeeOrder(false);
  };

  const toggleDiscount = (id: number) => {
    setSelectedDiscountIds((prev) =>
      prev.includes(id) ? prev.filter((d) => d !== id) : [...prev, id]
    );
  };

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

  const computeTotals = () => {
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
  };

  const handleSubmit = async () => {
    if (cart.length === 0) {
      Alert.alert('Panier vide', 'Ajoutez au moins une boisson.');
      return;
    }
    if (selectedDiscountIds.length > 0 && !cardPin) {
      Alert.alert('Code PIN requis', 'Saisissez le PIN de la carte pour utiliser des réductions.');
      return;
    }

    setSubmitting(true);
    try {
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
      } else if (customerName.trim()) {
        payload.customer_name = customerName.trim();
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

  const totals = computeTotals();
  const filteredDrinks = drinks.filter((d) =>
    d.name.toLowerCase().includes(drinkSearch.toLowerCase()) ||
    (d.category?.name ?? '').toLowerCase().includes(drinkSearch.toLowerCase())
  );

  if (loadingDrinks) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#92400e" />
      </View>
    );
  }

  return (
    <ScrollView style={styles.container} contentContainerStyle={{ padding: 16, paddingBottom: 60 }}>

      {/* ── Section client ── */}
      <Text style={styles.sectionTitle}>Client</Text>
      <View style={styles.card}>
        {!loyaltyCard ? (
          <>
            <TextInput
              style={styles.input}
              placeholder="Nom du client (optionnel)"
              placeholderTextColor="#9ca3af"
              value={customerName}
              onChangeText={setCustomerName}
            />
            <View style={styles.cardNumberRow}>
              <TextInput
                style={[styles.input, { flex: 1, marginBottom: 0 }]}
                placeholder="N° carte fidélité"
                placeholderTextColor="#9ca3af"
                value={loyaltyCardNumber}
                onChangeText={setLoyaltyCardNumber}
                keyboardType="numeric"
              />
              <TouchableOpacity
                style={styles.checkBtn}
                onPress={checkLoyaltyCard}
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
          </>
        ) : (
          <View>
            <View style={styles.loyaltyCardFound}>
              <View style={{ flex: 1 }}>
                <Text style={styles.loyaltyCardName}>🎁 {loyaltyCard.full_name}</Text>
                <Text style={styles.loyaltyCardNum}>{loyaltyCard.card_number}</Text>
                <Text style={styles.loyaltyPoints}>
                  {loyaltyCard.points} point{loyaltyCard.points > 1 ? 's' : ''}
                  {loyaltyCard.has_employee_benefits ? ' · Salarié' : ''}
                </Text>
              </View>
              <TouchableOpacity onPress={clearLoyaltyCard}>
                <Text style={styles.clearBtn}>✕</Text>
              </TouchableOpacity>
            </View>

            {/* Réductions */}
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
                      onPress={() => toggleDiscount(d.id)}
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
                  <TextInput
                    style={[styles.input, { marginTop: 8 }]}
                    placeholder="Code PIN de la carte"
                    placeholderTextColor="#9ca3af"
                    secureTextEntry
                    keyboardType="numeric"
                    value={cardPin}
                    onChangeText={setCardPin}
                  />
                )}
              </>
            )}
          </View>
        )}

        {/* Commande salarié */}
        <View style={styles.switchRow}>
          <Text style={styles.switchLabel}>Commande salarié (-15%)</Text>
          <Switch
            value={isEmployeeOrder}
            onValueChange={setIsEmployeeOrder}
            trackColor={{ true: '#92400e' }}
            thumbColor="#fff"
            disabled={loyaltyCard?.has_employee_benefits}
          />
        </View>
      </View>

      {/* ── Panier ── */}
      <View style={styles.cartHeader}>
        <Text style={styles.sectionTitle}>Articles</Text>
        <View style={styles.cartHeaderBtns}>
          <TouchableOpacity style={styles.addCustomBtn} onPress={openCustomModal}>
            <Text style={styles.addCustomBtnText}>+ Libre</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.addDrinkBtn} onPress={() => setDrinkModalVisible(true)}>
            <Text style={styles.addDrinkBtnText}>+ Boisson</Text>
          </TouchableOpacity>
        </View>
      </View>

      {cart.length === 0 ? (
        <View style={[styles.card, styles.emptyCart]}>
          <Text style={styles.emptyCartText}>Aucun article ajouté</Text>
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
                <TouchableOpacity style={styles.qtyBtn} onPress={() => updateQuantity(item.uid, -1)}>
                  <Text style={styles.qtyBtnText}>−</Text>
                </TouchableOpacity>
                <Text style={styles.qty}>{item.quantity}</Text>
                <TouchableOpacity style={styles.qtyBtn} onPress={() => updateQuantity(item.uid, 1)}>
                  <Text style={styles.qtyBtnText}>+</Text>
                </TouchableOpacity>
                <TouchableOpacity style={styles.removeBtn} onPress={() => removeItem(item.uid)}>
                  <Text style={styles.removeBtnText}>✕</Text>
                </TouchableOpacity>
              </View>
            </View>
          ))}
        </View>
      )}

      {/* ── Notes ── */}
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

      {/* ── Récap ── */}
      {cart.length > 0 && (
        <View style={styles.card}>
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

      {/* ── Bouton créer ── */}
      <TouchableOpacity
        style={[styles.submitBtn, (submitting || cart.length === 0) && styles.submitBtnDisabled]}
        onPress={handleSubmit}
        disabled={submitting || cart.length === 0}
      >
        {submitting ? (
          <ActivityIndicator color="#fff" />
        ) : (
          <Text style={styles.submitBtnText}>Créer la commande · {totals.total.toFixed(2)} €</Text>
        )}
      </TouchableOpacity>

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
        <View style={styles.modalContainer}>
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitle}>Article libre</Text>
            <TouchableOpacity onPress={() => setCustomModalVisible(false)}>
              <Text style={styles.modalClose}>Annuler</Text>
            </TouchableOpacity>
          </View>
          <View style={{ padding: 16 }}>
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
          </View>
        </View>
      </Modal>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fdf8f3' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  sectionTitle: { fontSize: 13, fontWeight: '700', color: '#92400e', textTransform: 'uppercase', letterSpacing: 1, marginTop: 20, marginBottom: 8 },
  card: { backgroundColor: '#fff', borderRadius: 12, padding: 14, marginBottom: 8, shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.06, shadowRadius: 4, elevation: 2 },
  input: { borderWidth: 1, borderColor: '#d1d5db', borderRadius: 8, paddingHorizontal: 12, paddingVertical: 10, fontSize: 15, color: '#111827', backgroundColor: '#f9fafb', marginBottom: 10 },
  cardNumberRow: { flexDirection: 'row', gap: 8, alignItems: 'center', marginBottom: 10 },
  checkBtn: { backgroundColor: '#92400e', paddingHorizontal: 14, paddingVertical: 10, borderRadius: 8 },
  checkBtnText: { color: '#fff', fontWeight: '600' },
  error: { color: '#ef4444', fontSize: 13, marginBottom: 8 },
  loyaltyCardFound: { flexDirection: 'row', alignItems: 'center', marginBottom: 12 },
  loyaltyCardName: { fontSize: 16, fontWeight: '700', color: '#1f2937' },
  loyaltyCardNum: { fontSize: 13, color: '#9ca3af', fontFamily: 'monospace' },
  loyaltyPoints: { fontSize: 14, color: '#d97706', fontWeight: '600' },
  clearBtn: { fontSize: 18, color: '#9ca3af', padding: 4 },
  subLabel: { fontSize: 13, fontWeight: '600', color: '#6b7280', marginBottom: 8, marginTop: 4 },
  discountRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 8, borderTopWidth: 1, borderTopColor: '#f3f4f6' },
  checkbox: { width: 22, height: 22, borderRadius: 5, borderWidth: 2, borderColor: '#d1d5db', justifyContent: 'center', alignItems: 'center', marginRight: 10 },
  checkboxChecked: { backgroundColor: '#92400e', borderColor: '#92400e' },
  checkmark: { color: '#fff', fontWeight: '700', fontSize: 13 },
  discountName: { fontSize: 14, fontWeight: '600', color: '#1f2937' },
  discountDetail: { fontSize: 12, color: '#6b7280' },
  switchRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingTop: 12, marginTop: 4 },
  switchLabel: { fontSize: 15, color: '#374151' },
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
  summaryRow: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 4 },
  summaryLabel: { fontSize: 14, color: '#6b7280' },
  summaryValue: { fontSize: 14, color: '#374151', fontWeight: '500' },
  submitBtn: { backgroundColor: '#92400e', borderRadius: 12, paddingVertical: 16, alignItems: 'center', marginTop: 16 },
  submitBtnDisabled: { opacity: 0.5 },
  submitBtnText: { color: '#fff', fontSize: 16, fontWeight: '700' },
  // Modal
  modalContainer: { flex: 1, backgroundColor: '#fff' },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 16, borderBottomWidth: 1, borderBottomColor: '#e5e7eb' },
  modalTitle: { fontSize: 18, fontWeight: '700', color: '#1f2937' },
  modalClose: { fontSize: 16, color: '#92400e', fontWeight: '600' },
  modalSearch: { margin: 12, borderWidth: 1, borderColor: '#d1d5db', borderRadius: 10, paddingHorizontal: 14, paddingVertical: 10, fontSize: 15, color: '#111827', backgroundColor: '#f9fafb' },
  modalDrinkRow: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: '#f3f4f6' },
  modalDrinkName: { fontSize: 16, fontWeight: '600', color: '#1f2937' },
  modalDrinkCat: { fontSize: 13, color: '#9ca3af' },
  modalDrinkPrice: { fontSize: 16, fontWeight: '700', color: '#92400e' },
  empty: { textAlign: 'center', color: '#9ca3af', marginTop: 40, fontSize: 15 },
});
