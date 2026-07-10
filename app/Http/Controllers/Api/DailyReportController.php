<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use App\Models\OrderPayment;
use App\Models\OrderRefund;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DailyReportController extends Controller
{
    public function index(): JsonResponse
    {
        $reports = DailyReport::where('generated_by', Auth::id())
            ->orderByDesc('report_date')
            ->paginate(20);

        return response()->json([
            'data'         => $reports->map(fn (DailyReport $r) => $this->formatReport($r)),
            'current_page' => $reports->currentPage(),
            'last_page'    => $reports->lastPage(),
            'total'        => $reports->total(),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate(['date' => ['required', 'date']]);
        $date = $request->input('date');

        [$totalCollected, $totalRefunded, $breakdown, $refundBreakdown] = $this->computeData($date);

        $existing = DailyReport::where('generated_by', Auth::id())
            ->where('report_date', $date)
            ->first();

        return response()->json([
            'date'             => $date,
            'total_collected'  => $totalCollected,
            'total_refunded'   => $totalRefunded,
            'breakdown'        => $breakdown,
            'refund_breakdown' => $refundBreakdown,
            'existing'         => $existing ? $this->formatReport($existing) : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['date' => ['required', 'date']]);
        $date = $request->input('date');

        [$totalCollected, $totalRefunded, $breakdown, $refundBreakdown] = $this->computeData($date);

        $reportData = [
            'report_date'      => $date,
            'generated_by'     => Auth::id(),
            'total_collected'  => $totalCollected,
            'total_refunded'   => $totalRefunded,
            'breakdown'        => $breakdown,
            'refund_breakdown' => $refundBreakdown,
        ];

        $existing = DailyReport::where('generated_by', Auth::id())
            ->where('report_date', $date)
            ->first();

        if ($existing) {
            $existing->update($reportData);
            $report = $existing;
        } else {
            $report = DailyReport::create($reportData);
        }

        return response()->json([
            'message' => $existing ? 'Récapitulatif mis à jour.' : 'Récapitulatif généré.',
            'report'  => $this->formatReport($report->fresh()),
        ], $existing ? 200 : 201);
    }

    public function show(DailyReport $dailyReport): JsonResponse
    {
        abort_unless($dailyReport->generated_by === Auth::id(), 403);

        return response()->json(['report' => $this->formatReport($dailyReport)]);
    }

    private function computeData(string $date): array
    {
        $paymentsRaw = OrderPayment::query()
            ->join('orders', 'order_payments.order_id', '=', 'orders.id')
            ->join('payment_methods', 'order_payments.payment_method_id', '=', 'payment_methods.id')
            ->whereDate('orders.completed_at', $date)
            ->where('orders.status', 'completed')
            ->where('orders.handled_by', Auth::id())
            ->select(
                'payment_methods.id as method_id',
                'payment_methods.name as method_name',
                DB::raw('SUM(order_payments.amount) as total')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->get();

        $refundsRaw = OrderRefund::query()
            ->join('orders', 'order_refunds.order_id', '=', 'orders.id')
            ->join('payment_methods', 'order_refunds.payment_method_id', '=', 'payment_methods.id')
            ->whereDate('order_refunds.created_at', $date)
            ->where('orders.handled_by', Auth::id())
            ->select(
                'payment_methods.id as method_id',
                'payment_methods.name as method_name',
                DB::raw('SUM(order_refunds.amount) as total')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->get();

        $breakdown       = $paymentsRaw->map(fn ($r) => ['method_id' => $r->method_id, 'method_name' => $r->method_name, 'total' => round($r->total, 2)])->values()->toArray();
        $refundBreakdown = $refundsRaw->map(fn ($r) => ['method_id' => $r->method_id, 'method_name' => $r->method_name, 'total' => round($r->total, 2)])->values()->toArray();

        return [
            round($paymentsRaw->sum('total'), 2),
            round($refundsRaw->sum('total'), 2),
            $breakdown,
            $refundBreakdown,
        ];
    }

    private function formatReport(DailyReport $report): array
    {
        return [
            'id'               => $report->id,
            'report_date'      => $report->report_date->toDateString(),
            'total_collected'  => (float) $report->total_collected,
            'total_refunded'   => (float) $report->total_refunded,
            'net'              => round((float) $report->total_collected - (float) $report->total_refunded, 2),
            'breakdown'        => $report->breakdown ?? [],
            'refund_breakdown' => $report->refund_breakdown ?? [],
            'generated_at'     => $report->created_at?->toIso8601String(),
            'updated_at'       => $report->updated_at?->toIso8601String(),
        ];
    }
}
