import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
} from 'react-native';
import { useRoute } from '@react-navigation/native';
import api from '../api/client';
import { DailyReport } from '../types';

function displayDate(dateStr: string): string {
  const [y, m, d] = dateStr.split('-');
  return `${d}/${m}/${y}`;
}

export default function DailyReportDetailScreen() {
  const route = useRoute<any>();
  const { reportId } = route.params;
  const [report, setReport] = useState<DailyReport | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api
      .get(`/daily-reports/${reportId}`)
      .then(({ data }) => setReport(data.report))
      .finally(() => setLoading(false));
  }, [reportId]);

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color="#92400e" size="large" />
      </View>
    );
  }

  if (!report) return null;

  return (
    <ScrollView style={styles.container} contentContainerStyle={{ padding: 16, paddingBottom: 40 }}>
      {/* En-tête */}
      <View style={styles.header}>
        <View>
          <Text style={styles.headerDate}>{displayDate(report.report_date)}</Text>
          {report.generated_at && (
            <Text style={styles.headerSub}>
              Généré le {new Date(report.generated_at).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
            </Text>
          )}
        </View>
        <View style={styles.netBadge}>
          <Text style={styles.netBadgeLabel}>Net</Text>
          <Text style={styles.netBadgeValue}>{report.net.toFixed(2).replace('.', ',')} €</Text>
        </View>
      </View>

      {/* Encaissements */}
      <View style={styles.card}>
        <Text style={styles.cardTitle}>Encaissements</Text>
        {report.breakdown.length === 0 ? (
          <Text style={styles.empty}>Aucune transaction enregistrée.</Text>
        ) : (
          <>
            {report.breakdown.map((r) => (
              <View key={r.method_id} style={styles.row}>
                <Text style={styles.rowLabel}>{r.method_name}</Text>
                <Text style={styles.rowValue}>{r.total.toFixed(2).replace('.', ',')} €</Text>
              </View>
            ))}
            <View style={[styles.row, styles.totalRow]}>
              <Text style={styles.totalLabel}>Total encaissé</Text>
              <Text style={[styles.totalValue, { color: '#16a34a' }]}>
                {report.total_collected.toFixed(2).replace('.', ',')} €
              </Text>
            </View>
          </>
        )}
      </View>

      {/* Remboursements */}
      <View style={styles.card}>
        <Text style={styles.cardTitle}>Remboursements</Text>
        {report.refund_breakdown.length === 0 ? (
          <Text style={styles.empty}>Aucun remboursement ce jour.</Text>
        ) : (
          <>
            {report.refund_breakdown.map((r) => (
              <View key={r.method_id} style={styles.row}>
                <Text style={styles.rowLabel}>{r.method_name}</Text>
                <Text style={[styles.rowValue, { color: '#dc2626' }]}>
                  -{r.total.toFixed(2).replace('.', ',')} €
                </Text>
              </View>
            ))}
            <View style={[styles.row, styles.totalRow]}>
              <Text style={styles.totalLabel}>Total remboursé</Text>
              <Text style={[styles.totalValue, { color: '#dc2626' }]}>
                {report.total_refunded.toFixed(2).replace('.', ',')} €
              </Text>
            </View>
          </>
        )}
      </View>

      {/* Bilan */}
      <View style={styles.bilan}>
        <View style={styles.bilanInner}>
          <View>
            <Text style={styles.bilanLabel}>Bilan net de la journée</Text>
            <Text style={styles.bilanSub}>Encaissements − Remboursements</Text>
          </View>
          <Text style={styles.bilanValue}>{report.net.toFixed(2).replace('.', ',')} €</Text>
        </View>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f5f4' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 2,
  },
  headerDate: { fontSize: 18, fontWeight: '800', color: '#1c1917' },
  headerSub: { fontSize: 12, color: '#a8a29e', marginTop: 2 },
  netBadge: { alignItems: 'flex-end' },
  netBadgeLabel: { fontSize: 11, color: '#78716c', fontWeight: '600' },
  netBadgeValue: { fontSize: 22, fontWeight: '900', color: '#92400e' },
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOpacity: 0.04,
    shadowRadius: 3,
    elevation: 1,
  },
  cardTitle: { fontSize: 14, fontWeight: '700', color: '#1c1917', marginBottom: 10 },
  row: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 6, borderBottomWidth: 1, borderBottomColor: '#f5f5f4' },
  rowLabel: { fontSize: 14, color: '#57534e' },
  rowValue: { fontSize: 14, fontWeight: '600', color: '#1c1917' },
  totalRow: { borderBottomWidth: 0, borderTopWidth: 2, borderTopColor: '#e7e5e4', marginTop: 4, paddingTop: 10 },
  totalLabel: { fontSize: 14, fontWeight: '700', color: '#1c1917' },
  totalValue: { fontSize: 14, fontWeight: '800' },
  empty: { fontSize: 14, color: '#a8a29e', fontStyle: 'italic' },
  bilan: {
    backgroundColor: '#fffbeb',
    borderWidth: 1.5,
    borderColor: '#fcd34d',
    borderRadius: 12,
    padding: 16,
  },
  bilanInner: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  bilanLabel: { fontSize: 15, fontWeight: '700', color: '#92400e' },
  bilanSub: { fontSize: 12, color: '#b45309', marginTop: 2 },
  bilanValue: { fontSize: 26, fontWeight: '900', color: '#78350f' },
});
