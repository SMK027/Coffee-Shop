<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $query = ActivityLog::orderByDesc('created_at');

        // Filtre par catégorie
        $category = $request->query('category', '');
        if ($category !== '') {
            $query->where('action', 'like', "{$category}.%");
        }

        // Filtre par utilisateur
        $userId = $request->query('user_id', '');
        if ($userId !== '') {
            $query->where('user_id', (int) $userId);
        }

        // Filtre par période
        $period = $request->query('period', '7d');
        match ($period) {
            'today' => $query->whereDate('created_at', today()),
            '7d'    => $query->where('created_at', '>=', now()->subDays(7)),
            '30d'   => $query->where('created_at', '>=', now()->subDays(30)),
            'all'   => null,
            default => $query->where('created_at', '>=', now()->subDays(7)),
        };

        // Recherche textuelle
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%");
            });
        }

        $logs       = $query->paginate(30)->withQueryString();
        $categories = ActivityLog::categoryConfig();
        $users      = User::whereIn('global_role', ['admin', 'superadmin'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('employee.activity-logs.index', compact('logs', 'categories', 'users', 'category', 'userId', 'period', 'search'));
    }
}
