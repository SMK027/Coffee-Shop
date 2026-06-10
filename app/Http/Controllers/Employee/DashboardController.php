<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Drink;
use App\Models\Order;
use App\Models\Testimonial;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'orders_today'        => Order::whereDate('created_at', today())->count(),
            'orders_active'       => Order::active()->count(),
            'orders_completed'    => Order::where('status', 'completed')->whereDate('completed_at', today())->count(),
            'revenue_today'       => Order::where('status', 'completed')->whereDate('completed_at', today())->sum('total_amount'),
            'pending_testimonials'=> Testimonial::pending()->count(),
            'new_contacts'        => Contact::where('status', 'new')->count(),
            'drinks_unavailable'  => Drink::where('available', false)->count(),
        ];

        $recent_orders = Order::with('items.drink')
            ->active()
            ->latest()
            ->take(10)
            ->get();

        return view('employee.dashboard', compact('stats', 'recent_orders'));
    }
}
