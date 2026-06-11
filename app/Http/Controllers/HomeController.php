<?php

namespace App\Http\Controllers;

use App\Models\DrinkCategory;
use App\Models\Testimonial;
use App\Services\CaptchaService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $testimonials = Testimonial::published()
            ->latest()
            ->take(6)
            ->get();

        return view('visitor.home', compact('testimonials'));
    }

    public function menu()
    {
        $categories = DrinkCategory::with('availableDrinks')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn($cat) => $cat->availableDrinks->isNotEmpty());

        return view('visitor.menu', compact('categories'));
    }

    public function contact(Request $request, CaptchaService $captcha)
    {
        return view('visitor.contact', [
            'captchaQuestion' => $captcha->refreshChallenge($request, 'contact_form'),
        ]);
    }

    public function submitContact(Request $request, CaptchaService $captcha)
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:150'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:20', 'max:2000'],
            'captcha' => $captcha->validationRules($request, 'contact_form'),
        ]);

        unset($validated['captcha']);

        \App\Models\Contact::create($validated);

        return redirect()->route('contact')
            ->with('success', 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.');
    }

    public function submitTestimonial(Request $request)
    {
        $validated = $request->validate([
            'author_name' => ['required', 'string', 'max:100'],
            'rating'      => ['required', 'integer', 'min:1', 'max:5'],
            'content'     => ['required', 'string', 'min:20', 'max:1000'],
        ]);

        \App\Models\Testimonial::create($validated);

        return redirect()->route('home')
            ->with('success', 'Merci pour votre témoignage ! Il sera publié après modération.');
    }
}
