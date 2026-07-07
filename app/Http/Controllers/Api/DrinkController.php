<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drink;
use App\Models\DrinkCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DrinkController extends Controller
{
    /**
     * Liste toutes les boissons groupées par catégorie.
     */
    public function index(): JsonResponse
    {
        $drinks = Drink::with('category')
            ->orderBy('category_id')
            ->orderBy('sort_order')
            ->get()
            ->map(fn(Drink $d) => [
                'id'              => $d->id,
                'name'            => $d->name,
                'slug'            => $d->slug,
                'description'     => $d->description,
                'price'           => (float) $d->price,
                'available'       => (bool) $d->available,
                'loyalty_points'  => (int) $d->loyalty_points,
                'sort_order'      => (int) $d->sort_order,
                'category'        => $d->category ? [
                    'id'   => $d->category->id,
                    'name' => $d->category->name,
                ] : null,
                'image_url'       => $d->image ? url('storage/' . $d->image) : null,
            ]);

        return response()->json(['drinks' => $drinks]);
    }

    /**
     * Active ou désactive une boisson.
     */
    public function toggleAvailability(Drink $drink): JsonResponse
    {
        $drink->update(['available' => !$drink->available]);

        return response()->json([
            'id'        => $drink->id,
            'available' => (bool) $drink->available,
            'message'   => $drink->available
                ? "« {$drink->name} » est maintenant disponible."
                : "« {$drink->name} » est maintenant indisponible.",
        ]);
    }
}
