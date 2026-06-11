<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Drink;
use App\Models\DrinkCategory;
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
        $categories = DrinkCategory::orderBy('sort_order')->get();

        return view('employee.drinks.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:drink_categories,id'],
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'price'       => ['required', 'numeric', 'min:0.01', 'max:99.99'],
            'available'   => ['boolean'],
            'sort_order'  => ['integer', 'min:0'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['available'] = $request->boolean('available', true);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('drinks', 'public');
        }

        Drink::create($validated);

        return redirect()->route('employee.drinks.index')
            ->with('success', 'Boisson ajoutée avec succès.');
    }

    public function edit(Drink $drink)
    {
        $categories = DrinkCategory::orderBy('sort_order')->get();

        return view('employee.drinks.edit', compact('drink', 'categories'));
    }

    public function update(Request $request, Drink $drink)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:drink_categories,id'],
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'price'       => ['required', 'numeric', 'min:0.01', 'max:99.99'],
            'available'   => ['boolean'],
            'sort_order'  => ['integer', 'min:0'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['available'] = $request->boolean('available');

        if ($request->hasFile('image')) {
            if ($drink->image) {
                \Storage::disk('public')->delete($drink->image);
            }
            $validated['image'] = $request->file('image')->store('drinks', 'public');
        }

        $drink->update($validated);

        return redirect()->route('employee.drinks.index')
            ->with('success', 'Boisson mise à jour avec succès.');
    }

    public function destroy(Drink $drink)
    {
        if ($drink->image) {
            \Storage::disk('public')->delete($drink->image);
        }
        $drink->delete();

        return redirect()->route('employee.drinks.index')
            ->with('success', 'Boisson supprimée avec succès.');
    }

    public function toggleAvailability(Drink $drink)
    {
        $drink->update(['available' => !$drink->available]);

        return redirect()->back()->with('success', 'Disponibilité mise à jour.');
    }
}
