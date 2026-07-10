<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderRefund;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DailyReportController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $isSuperAdmin = auth()->user()->isSuperAdmin();

        $query = DailyReport::with('generator')->orderByDesc('report_date');

        if (! $isSuperAdmin) {
            $query->where('generated_by', auth()->id());
        }

        $reports = $query->paginate(20);

        return view('employee.daily-reports.index', compact('reports', 'isSuperAdmin'));
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $date = $request->input('date', today()->toDateString());

        // Vérifier si un rapport existe déjà pour cet admin + cette date
        $existing = DailyReport::where('generated_by', auth()->id())
            ->where('report_date', $date)
            ->first();

        // Calcul des encaissements
        $paymentsRaw = OrderPayment::query()
            ->join('orders', 'order_payments.order_id', '=', 'orders.id')
            ->join('payment_methods', 'order_payments.payment_method_id', '=', 'payment_methods.id')
            ->whereDate('orders.completed_at', $date)
            ->where('orders.status', 'completed')
            ->where('orders.handled_by', auth()->id())
            ->select(
                'payment_methods.id as method_id',
                'payment_methods.name as method_name',
                DB::raw('SUM(order_payments.amount) as total')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->get();

        // Calcul des remboursements
        $refundsRaw = OrderRefund::query()
            ->join('orders', 'order_refunds.order_id', '=', 'orders.id')
            ->join('payment_methods', 'order_refunds.payment_method_id', '=', 'payment_methods.id')
            ->whereDate('order_refunds.created_at', $date)
            ->where('orders.handled_by', auth()->id())
            ->select(
                'payment_methods.id as method_id',
                'payment_methods.name as method_name',
                DB::raw('SUM(order_refunds.amount) as total')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->get();

        $totalCollected = $paymentsRaw->sum('total');
        $totalRefunded  = $refundsRaw->sum('total');

        $breakdown       = $paymentsRaw->map(fn ($r) => ['method_id' => $r->method_id, 'method_name' => $r->method_name, 'total' => round($r->total, 2)])->toArray();
        $refundBreakdown = $refundsRaw->map(fn ($r) => ['method_id' => $r->method_id, 'method_name' => $r->method_name, 'total' => round($r->total, 2)])->toArray();

        return view('employee.daily-reports.create', compact(
            'date', 'totalCollected', 'totalRefunded', 'breakdown', 'refundBreakdown', 'existing'
        ));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $request->validate(['date' => ['required', 'date']]);
        $date = $request->input('date');

        // Recalcul des données
        $paymentsRaw = OrderPayment::query()
            ->join('orders', 'order_payments.order_id', '=', 'orders.id')
            ->join('payment_methods', 'order_payments.payment_method_id', '=', 'payment_methods.id')
            ->whereDate('orders.completed_at', $date)
            ->where('orders.status', 'completed')
            ->where('orders.handled_by', auth()->id())
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
            ->where('orders.handled_by', auth()->id())
            ->select(
                'payment_methods.id as method_id',
                'payment_methods.name as method_name',
                DB::raw('SUM(order_refunds.amount) as total')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->get();

        $reportData = [
            'report_date'      => $date,
            'generated_by'     => auth()->id(),
            'total_collected'  => round($paymentsRaw->sum('total'), 2),
            'total_refunded'   => round($refundsRaw->sum('total'), 2),
            'breakdown'        => $paymentsRaw->map(fn ($r) => ['method_id' => $r->method_id, 'method_name' => $r->method_name, 'total' => round($r->total, 2)])->toArray(),
            'refund_breakdown' => $refundsRaw->map(fn ($r) => ['method_id' => $r->method_id, 'method_name' => $r->method_name, 'total' => round($r->total, 2)])->toArray(),
        ];

        $existing = DailyReport::where('generated_by', auth()->id())
            ->where('report_date', $date)
            ->first();

        if ($existing) {
            $existing->update($reportData);
            $report = $existing;
        } else {
            $report = DailyReport::create($reportData);
        }

        $message = $existing ? 'Récapitulatif mis à jour avec succès.' : 'Récapitulatif généré avec succès.';

        return redirect()->route('employee.daily-reports.show', $report)
            ->with('success', $message);
    }

    public function show(DailyReport $dailyReport)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        // Seul le générateur ou un super admin peut consulter un rapport
        abort_unless(
            $dailyReport->generated_by === auth()->id() || auth()->user()->isSuperAdmin(),
            403
        );

        return view('employee.daily-reports.show', compact('dailyReport'));
    }
}
