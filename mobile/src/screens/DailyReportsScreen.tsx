import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  Alert,
  RefreshControl,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import api from '../api/client';
import { DailyReport, DailyReportPreview } from '../types';

function formatDate(d: Date): string {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function displayDate(dateStr: string): string {
  const [y, m, d] = dateStr.split('-');
  return `${d}/${m}/${y}`;
}

export default function DailyReportsScreen() {
  const navigation = useNavigation<any>();
  const [reports, setReports] = useState<DailyReport[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loadingMore, setLoadingMore] = useState(false);

  // Génération
  const [selectedDate, setSelectedDate] = useState<Date>(new Date());
  const [preview, setPreview] = useState<DailyReportPreview | null>(null);
  const [loadingPreview, setLoadingPreview] = useState(false);
  const [generating, setGenerating] = useState(false);

  const shiftDate = (days: number) => {
    const d = new Date(selectedDate);
    d.setDate(d.getDate() + days);
    // Ne pas dépasser aujourd'hui
    if (d > new Date()) return;
    setSelectedDate(d);
    loadPreviewForDate(d);
  };

  const loadReports = useCallback(async (p = 1, reset = false) => {
    try {
      const { data } = await api.get('/daily-reports', { params: { page: p } });
      setReports((prev) => (reset ? data.data : [...prev, ...data.data]));
      setLastPage(data.last_page);
      setPage(p);
    } finally {
      setLoading(false);
      setRefreshing(false);
      setLoadingMore(false);
    }
  }, []);

  useEffect(() => {
    loadReports(1, true);
    loadPreviewForDate(selectedDate);
  }, []);

  const loadPreviewForDate = async (date: Date) => {
    setLoadingPreview(true);
    try {
      const { data } = await api.get('/daily-reports/preview', { params: { date: formatDate(date) } });
      setPreview(data);
    } catch {
      setPreview(null);
    } finally {
      setLoadingPreview(false);
    }
  };

  const handleGenerate = async () => {
    if (!preview) return;

    const isUpdate = !!preview.existing;
    if (isUpdate) {
      Alert.alert(
        'Mettre à jour le récapitulatif ?',
        `Un récapitulatif existe déjà pour le ${displayDate(preview.date)}. Il sera écrasé avec les données actuelles.`,
        [
          { text: 'Annuler', style: 'cancel' },
          { text: 'Mettre à jour', style: 'destructive', onPress: doGenerate },
        ]
      );
    } else {
      doGenerate();
    }
  };

  const doGenerate = async () => {
    setGenerating(true);
    try {
      const { data } = await api.post('/daily-reports', { date: formatDate(selectedDate) });
      Alert.alert('Succès', data.message);
      loadReports(1, true);
      loadPreviewForDate(selectedDate);
    } catch (e: any) {
      Alert.alert('Erreur', e.response?.data?.message ?? 'Erreur lors de la génération.');
    } finally {
      setGenerating(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadReports(1, true);
  };

  const loadMore = () => {
    if (page < lastPage && !loadingMore) {
      setLoadingMore(true);
      loadReports(page + 1);
    }
  };

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color="#92400e" size="large" />
      </View>
    );
  }

  return (
    <FlatList
      style={styles.container}
      contentContainerStyle={{ padding: 16, paddingBottom: 40 }}
      data={reports}
      keyExtractor={(item) => String(item.id)}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={['#92400e']} />}
      onEndReached={loadMore}
      onEndReachedThreshold={0.3}
      ListHeaderComponent={
        <>
          {/* Générateur */}
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Générer un récapitulatif</Text>

            {/* Sélecteur de date avec boutons */}
            <View style={styles.dateNav}>
              <TouchableOpacity style={styles.dateNavBtn} onPress={() => shiftDate(-1)}>
                <Text style={styles.dateNavBtnText}>‹</Text>
              </TouchableOpacity>
              <Text style={styles.dateDisplay}>📅  {displayDate(formatDate(selectedDate))}</Text>
              <TouchableOpacity
                style={[styles.dateNavBtn, formatDate(selectedDate) === formatDate(new Date()) && { opacity: 0.3 }]}
                onPress={() => shiftDate(1)}
                disabled={formatDate(selectedDate) === formatDate(new Date())}
              >
                <Text style={styles.dateNavBtnText}>›</Text>
              </TouchableOpacity>
            </View>

            {loadingPreview ? (
              <ActivityIndicator color="#92400e" style={{ marginVertical: 12 }} />
            ) : preview ? (
              <View style={styles.preview}>
                {preview.existing && (
                  <View style={styles.warningBanner}>
                    <Text style={styles.warningText}>
                      ⚠️  Un récapitulatif existe déjà pour cette date (généré le{' '}
                      {preview.existing.generated_at
                        ? new Date(preview.existing.generated_at).toLocaleDateString('fr-FR')
                        : '—'}
                      ). Il sera écrasé.
                    </Text>
                  </View>
                )}

                <Text style={styles.previewSection}>Encaissements</Text>
                {preview.breakdown.length === 0 ? (
                  <Text style={styles.emptyLine}>Aucune transaction ce jour</Text>
                ) : (
                  preview.breakdown.map((r) => (
                    <View key={r.method_id} style={styles.breakdownRow}>
                      <Text style={styles.breakdownLabel}>{r.method_name}</Text>
                      <Text style={styles.breakdownValue}>{r.total.toFixed(2).replace('.', ',')} €</Text>
                    </View>
                  ))
                )}

                <Text style={[styles.previewSection, { marginTop: 10 }]}>Remboursements</Text>
                {preview.refund_breakdown.length === 0 ? (
                  <Text style={styles.emptyLine}>Aucun remboursement ce jour</Text>
                ) : (
                  preview.refund_breakdown.map((r) => (
                    <View key={r.method_id} style={styles.breakdownRow}>
                      <Text style={styles.breakdownLabel}>{r.method_name}</Text>
                      <Text style={[styles.breakdownValue, { color: '#dc2626' }]}>
                        -{r.total.toFixed(2).replace('.', ',')} €
                      </Text>
                    </View>
                  ))
                )}

                <View style={styles.netRow}>
                  <Text style={styles.netLabel}>Net encaissé</Text>
                  <Text style={styles.netValue}>
                    {Math.max(0, preview.total_collected - preview.total_refunded).toFixed(2).replace('.', ',')} €
                  </Text>
                </View>
              </View>
            ) : null}

            <TouchableOpacity
              style={[styles.generateBtn, (!preview || generating) && { opacity: 0.5 }]}
              onPress={handleGenerate}
              disabled={!preview || generating}
            >
              {generating ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={styles.generateBtnText}>
                  {preview?.existing ? 'Mettre à jour' : 'Enregistrer le récapitulatif'}
                </Text>
              )}
            </TouchableOpacity>
          </View>

          <Text style={styles.sectionTitle}>Historique</Text>
        </>
      }
      renderItem={({ item }) => (
        <TouchableOpacity
          style={styles.reportCard}
          onPress={() => navigation.navigate('DailyReportDetail', { reportId: item.id })}
        >
          <View style={styles.reportHeader}>
            <Text style={styles.reportDate}>{displayDate(item.report_date)}</Text>
            <Text style={styles.reportNet}>{item.net.toFixed(2).replace('.', ',')} €</Text>
          </View>
          <View style={styles.reportSub}>
            <Text style={styles.reportSubText}>
              Encaissé : <Text style={{ color: '#16a34a' }}>{item.total_collected.toFixed(2).replace('.', ',')} €</Text>
            </Text>
            <Text style={styles.reportSubText}>
              Remboursé : <Text style={{ color: '#dc2626' }}>{item.total_refunded.toFixed(2).replace('.', ',')} €</Text>
            </Text>
          </View>
        </TouchableOpacity>
      )}
      ListFooterComponent={
        loadingMore ? <ActivityIndicator color="#92400e" style={{ marginVertical: 12 }} /> : null
      }
      ListEmptyComponent={
        <View style={styles.empty}>
          <Text style={styles.emptyText}>Aucun récapitulatif enregistré.</Text>
        </View>
      }
    />
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f5f4' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 2,
  },
  cardTitle: { fontSize: 15, fontWeight: '700', color: '#1c1917', marginBottom: 12 },
  dateNav: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12, backgroundColor: '#fafaf9', borderRadius: 10, borderWidth: 1.5, borderColor: '#d6d3d1', paddingHorizontal: 8, paddingVertical: 4 },
  dateNavBtn: { width: 36, height: 36, justifyContent: 'center', alignItems: 'center' },
  dateNavBtnText: { fontSize: 24, color: '#92400e', fontWeight: '700' },
  dateDisplay: { fontSize: 15, fontWeight: '700', color: '#1c1917' },
  preview: { marginBottom: 12 },
  warningBanner: {
    backgroundColor: '#fffbeb',
    borderColor: '#fcd34d',
    borderWidth: 1,
    borderRadius: 8,
    padding: 10,
    marginBottom: 10,
  },
  warningText: { fontSize: 13, color: '#92400e' },
  previewSection: { fontSize: 13, fontWeight: '700', color: '#78716c', marginBottom: 4 },
  emptyLine: { fontSize: 13, color: '#a8a29e', fontStyle: 'italic', marginBottom: 4 },
  breakdownRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 3 },
  breakdownLabel: { fontSize: 14, color: '#57534e' },
  breakdownValue: { fontSize: 14, fontWeight: '600', color: '#1c1917' },
  netRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 10,
    paddingTop: 10,
    borderTopWidth: 2,
    borderTopColor: '#e7e5e4',
  },
  netLabel: { fontSize: 15, fontWeight: '700', color: '#1c1917' },
  netValue: { fontSize: 15, fontWeight: '800', color: '#92400e' },
  generateBtn: {
    backgroundColor: '#92400e',
    borderRadius: 10,
    padding: 14,
    alignItems: 'center',
    marginTop: 4,
  },
  generateBtnText: { color: '#fff', fontWeight: '700', fontSize: 15 },
  sectionTitle: { fontSize: 14, fontWeight: '700', color: '#78716c', marginBottom: 10, marginTop: 4 },
  reportCard: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
    shadowColor: '#000',
    shadowOpacity: 0.04,
    shadowRadius: 3,
    elevation: 1,
  },
  reportHeader: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 6 },
  reportDate: { fontSize: 15, fontWeight: '700', color: '#1c1917' },
  reportNet: { fontSize: 15, fontWeight: '800', color: '#92400e' },
  reportSub: { flexDirection: 'row', gap: 16 },
  reportSubText: { fontSize: 13, color: '#78716c' },
  empty: { alignItems: 'center', paddingVertical: 20 },
  emptyText: { color: '#a8a29e', fontSize: 14 },
});
