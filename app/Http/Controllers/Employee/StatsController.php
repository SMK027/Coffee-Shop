<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Drink;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', '7');
        $days   = in_array($period, ['7', '30', '90']) ? (int) $period : 7;
        $from   = now()->subDays($days - 1)->startOfDay();

        // Revenus par jour
        $dailyRevenue = Order::where('status', 'completed')
            ->where('completed_at', '>=', $from)
            ->selectRaw('DATE(completed_at) as date, SUM(total_amount) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top boissons
        $topDrinks = OrderItem::join('drinks', 'order_items.drink_id', '=', 'drinks.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->where('orders.completed_at', '>=', $from)
            ->selectRaw('drinks.name, SUM(order_items.quantity) as total_qty, SUM(order_items.quantity * order_items.unit_price) as total_revenue')
            ->groupBy('drinks.id', 'drinks.name')
            ->orderByDesc('total_qty')
            ->take(10)
            ->get();

        // Totaux
        $totals = [
            'orders'   => Order::where('status', 'completed')->where('completed_at', '>=', $from)->count(),
            'revenue'  => Order::where('status', 'completed')->where('completed_at', '>=', $from)->sum('total_amount'),
            'avg'      => Order::where('status', 'completed')->where('completed_at', '>=', $from)->avg('total_amount') ?? 0,
            'cancelled'=> Order::where('status', 'cancelled')->where('created_at', '>=', $from)->count(),
        ];

        return view('employee.stats.index', compact('dailyRevenue', 'topDrinks', 'totals', 'period'));
    }
}
