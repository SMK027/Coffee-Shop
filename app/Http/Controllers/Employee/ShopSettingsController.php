<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class ShopSettingsController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('employee.shop-settings.index', [
            'address' => Setting::get(Setting::KEY_SHOP_ADDRESS, Setting::DEFAULTS[Setting::KEY_SHOP_ADDRESS]),
            'phone'   => Setting::get(Setting::KEY_SHOP_PHONE,   Setting::DEFAULTS[Setting::KEY_SHOP_PHONE]),
            'email'   => Setting::get(Setting::KEY_SHOP_EMAIL,   Setting::DEFAULTS[Setting::KEY_SHOP_EMAIL]),
            'hours'   => Setting::get(Setting::KEY_SHOP_HOURS,   Setting::DEFAULTS[Setting::KEY_SHOP_HOURS]),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'address' => ['required', 'string', 'max:300'],
            'phone'   => ['nullable', 'string', 'max:50'],
            'email'   => ['required', 'email', 'max:150'],
            'hours'   => ['required', 'string', 'max:500'],
        ]);

        Setting::set(Setting::KEY_SHOP_ADDRESS, $validated['address']);
        Setting::set(Setting::KEY_SHOP_PHONE,   $validated['phone'] ?? '');
        Setting::set(Setting::KEY_SHOP_EMAIL,   $validated['email']);
        Setting::set(Setting::KEY_SHOP_HOURS,   $validated['hours']);

        return back()->with('success', 'Informations de la boutique mises à jour.');
    }
}
