<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    public function index(): JsonResponse
    {
        $methods = PaymentMethod::active()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);

        return response()->json(['payment_methods' => $methods]);
    }
}
