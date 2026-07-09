<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class ShopSettingsController extends Controller
{
    private const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public function index()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('employee.shop-settings.index', [
            'address' => Setting::get(Setting::KEY_SHOP_ADDRESS, Setting::DEFAULTS[Setting::KEY_SHOP_ADDRESS]),
            'phone'   => Setting::get(Setting::KEY_SHOP_PHONE,   Setting::DEFAULTS[Setting::KEY_SHOP_PHONE]),
            'email'   => Setting::get(Setting::KEY_SHOP_EMAIL,   Setting::DEFAULTS[Setting::KEY_SHOP_EMAIL]),
            'hours'   => Setting::getHours(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->requireSuperAdminOrSupervisor($request);

        $request->validate([
            'address'      => ['required', 'string', 'max:300'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'email'        => ['required', 'email', 'max:150'],
            'hours'        => ['nullable', 'array'],
            'hours.*.from' => ['nullable', 'date_format:H:i'],
            'hours.*.to'   => ['nullable', 'date_format:H:i'],
        ]);

        Setting::set(Setting::KEY_SHOP_ADDRESS, $request->input('address'));
        Setting::set(Setting::KEY_SHOP_PHONE,   $request->input('phone', ''));
        Setting::set(Setting::KEY_SHOP_EMAIL,   $request->input('email'));

        $current = Setting::getHours();
        $regular = [];
        foreach (self::DAYS as $day) {
            $dayData        = $request->input("hours.{$day}", []);
            $open           = !empty($dayData['open']);
            $regular[$day]  = [
                'open' => $open,
                'from' => $open ? ($dayData['from'] ?? '08:00') : null,
                'to'   => $open ? ($dayData['to']   ?? '18:00') : null,
            ];
        }
        $current['regular'] = $regular;
        Setting::set(Setting::KEY_SHOP_HOURS, json_encode($current));

        return back()->with('success', 'Paramètres mis à jour.');
    }

    public function addException(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->requireSuperAdminOrSupervisor($request);

        $request->validate([
            'date'  => ['required', 'date_format:Y-m-d'],
            'label' => ['required', 'string', 'max:100'],
            'open'  => ['required', 'in:0,1'],
            'from'  => ['nullable', 'date_format:H:i', 'required_if:open,1'],
            'to'    => ['nullable', 'date_format:H:i', 'required_if:open,1'],
        ], [
            'from.required_if' => "L'heure d'ouverture est requise pour une ouverture exceptionnelle.",
            'to.required_if'   => "L'heure de fermeture est requise pour une ouverture exceptionnelle.",
        ]);

        $hours = Setting::getHours();

        // Supprime une exception existante pour la même date
        $hours['exceptions'] = array_values(array_filter(
            $hours['exceptions'],
            fn($e) => $e['date'] !== $request->date
        ));

        $exc = ['date' => $request->date, 'label' => $request->label, 'open' => (bool)(int)$request->open];
        if ($exc['open']) {
            $exc['from'] = $request->from;
            $exc['to']   = $request->to;
        }
        $hours['exceptions'][] = $exc;
        usort($hours['exceptions'], fn($a, $b) => strcmp($a['date'], $b['date']));

        Setting::set(Setting::KEY_SHOP_HOURS, json_encode($hours));

        return back()->with('success', 'Exception ajoutée.');
    }

    public function removeException(string $date)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $hours = Setting::getHours();
        $hours['exceptions'] = array_values(array_filter(
            $hours['exceptions'],
            fn($e) => $e['date'] !== $date
        ));
        Setting::set(Setting::KEY_SHOP_HOURS, json_encode($hours));

        return back()->with('success', 'Exception supprimée.');
    }
}
