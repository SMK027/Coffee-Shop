<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Drink;
use App\Models\DrinkCategory;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DrinkController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $categoriesQuery = DrinkCategory::query()->orderBy('sort_order');

        if ($search !== '') {
            $categoriesQuery->whereHas('drinks', function ($drinkQuery) use ($search) {
                $drinkQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $categories = $categoriesQuery
            ->with(['drinks' => function ($drinkQuery) use ($search) {
                if ($search !== '') {
                    $drinkQuery->where(function ($filter) use ($search) {
                        $filter->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }

                $drinkQuery->orderBy('sort_order');
            }])
            ->get();

        return view('employee.drinks.index', compact('categories'));
    }

    public function create()
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $categories = DrinkCategory::orderBy('sort_order')->get();

        return view('employee.drinks.create', compact('categories'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'category_id'    => ['required', 'exists:drink_categories,id'],
            'name'           => ['required', 'string', 'max:150'],
            'description'    => ['nullable', 'string', 'max:500'],
            'price'          => ['required', 'numeric', 'min:0.01', 'max:99.99'],
            'available'      => ['boolean'],
            'sort_order'     => ['integer', 'min:0'],
            'loyalty_points' => ['integer', 'min:0', 'max:9999'],
            'image'          => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $this->requireSuperAdminOrSupervisor($request, 'La définition d\'un prix nécessite la validation d\'un superviseur.');

        $validated['slug'] = Str::slug($validated['name']);
        $validated['available'] = $request->boolean('available', true);
        $validated['loyalty_points'] = (int) ($validated['loyalty_points'] ?? 0);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('drinks', 'public');
        }

        Drink::create($validated);

        ActivityLogger::log('drink.created', "Boisson créée : {$validated['name']} ({$validated['price']} €)");

        return redirect()->route('employee.drinks.index')
            ->with('success', 'Boisson ajoutée avec succès.');
    }

    public function edit(Drink $drink)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $categories = DrinkCategory::orderBy('sort_order')->get();

        return view('employee.drinks.edit', compact('drink', 'categories'));
    }

    public function update(Request $request, Drink $drink)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'category_id'    => ['required', 'exists:drink_categories,id'],
            'name'           => ['required', 'string', 'max:150'],
            'description'    => ['nullable', 'string', 'max:500'],
            'price'          => ['required', 'numeric', 'min:0.01', 'max:99.99'],
            'available'      => ['boolean'],
            'sort_order'     => ['integer', 'min:0'],
            'loyalty_points' => ['integer', 'min:0', 'max:9999'],
            'image'          => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ((float) $request->price !== (float) $drink->price) {
            $this->requireSuperAdminOrSupervisor($request, 'La modification du prix nécessite la validation d\'un superviseur.');
        }

        $validated['slug'] = Str::slug($validated['name']);
        $validated['available'] = $request->boolean('available');
        $validated['loyalty_points'] = (int) ($validated['loyalty_points'] ?? 0);

        if ($request->hasFile('image')) {
            if ($drink->image) {
                \Storage::disk('public')->delete($drink->image);
            }
            $validated['image'] = $request->file('image')->store('drinks', 'public');
        }

        $drink->update($validated);

        ActivityLogger::log('drink.updated', "Boisson mise à jour : {$drink->name}", 'drink', $drink->id);

        return redirect()->route('employee.drinks.index')
            ->with('success', 'Boisson mise à jour avec succès.');
    }

    public function destroy(Drink $drink)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if ($drink->image) {
            \Storage::disk('public')->delete($drink->image);
        }
        $drinkName = $drink->name;
        $drink->delete();

        ActivityLogger::log('drink.deleted', "Boisson supprimée : {$drinkName}");

        return redirect()->route('employee.drinks.index')
            ->with('success', 'Boisson supprimée avec succès.');
    }

    public function toggleAvailability(Drink $drink)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $drink->update(['available' => !$drink->available]);

        return redirect()->back()->with('success', 'Disponibilité mise à jour.');
    }

    /**
     * Désactive en masse des boissons sélectionnées.
     */
    public function bulkDisable(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'drink_ids'   => ['required', 'array', 'min:1'],
            'drink_ids.*' => ['integer', 'distinct', 'exists:drinks,id'],
        ], [
            'drink_ids.required' => 'Veuillez sélectionner au moins une boisson à désactiver.',
            'drink_ids.min'      => 'Veuillez sélectionner au moins une boisson à désactiver.',
        ]);

        $updated = Drink::whereIn('id', $validated['drink_ids'])
            ->where('available', true)
            ->update(['available' => false]);

        if ($updated === 0) {
            return back()->with('success', 'Aucune boisson active à désactiver parmi la sélection.');
        }

        return back()->with('success', "{$updated} boisson(s) désactivée(s) avec succès.");
    }

    /**
     * Réactive en masse des boissons sélectionnées.
     */
    public function bulkEnable(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'drink_ids'   => ['required', 'array', 'min:1'],
            'drink_ids.*' => ['integer', 'distinct', 'exists:drinks,id'],
        ], [
            'drink_ids.required' => 'Veuillez sélectionner au moins une boisson à réactiver.',
            'drink_ids.min'      => 'Veuillez sélectionner au moins une boisson à réactiver.',
        ]);

        $updated = Drink::whereIn('id', $validated['drink_ids'])
            ->where('available', false)
            ->update(['available' => true]);

        if ($updated === 0) {
            return back()->with('success', 'Aucune boisson indisponible à réactiver parmi la sélection.');
        }

        return back()->with('success', "{$updated} boisson(s) réactivée(s) avec succès.");
    }
}
