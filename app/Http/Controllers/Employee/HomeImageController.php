<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomeImageController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $images = [];
        foreach (Setting::HOME_IMAGE_KEYS as $key => $label) {
            $images[$key] = [
                'label' => $label,
                'path'  => Setting::get($key),
            ];
        }

        return view('employee.home-images.index', compact('images'));
    }

    public function update(Request $request, string $key)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless(array_key_exists($key, Setting::HOME_IMAGE_KEYS), 404);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);

        // Supprime l'ancienne image
        $old = Setting::get($key);
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $path = $request->file('image')->store('home', 'public');
        Setting::set($key, $path);

        return back()->with('success', Setting::HOME_IMAGE_KEYS[$key] . ' mise à jour.');
    }

    public function destroy(string $key)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless(array_key_exists($key, Setting::HOME_IMAGE_KEYS), 404);

        $path = Setting::get($key);
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        Setting::set($key, '');

        return back()->with('success', Setting::HOME_IMAGE_KEYS[$key] . ' supprimée.');
    }
}
