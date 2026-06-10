<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function index(Request $request)
    {
        $query = Testimonial::latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $testimonials = $query->paginate(20);

        return view('employee.testimonials.index', compact('testimonials'));
    }

    public function publish(Testimonial $testimonial)
    {
        $testimonial->update([
            'status'        => 'published',
            'moderated_by'  => auth()->id(),
            'moderated_at'  => now(),
        ]);

        return redirect()->back()->with('success', 'Témoignage publié.');
    }

    public function reject(Testimonial $testimonial)
    {
        $testimonial->update([
            'status'        => 'rejected',
            'moderated_by'  => auth()->id(),
            'moderated_at'  => now(),
        ]);

        return redirect()->back()->with('success', 'Témoignage rejeté.');
    }

    public function destroy(Testimonial $testimonial)
    {
        $testimonial->delete();

        return redirect()->back()->with('success', 'Témoignage supprimé.');
    }
}
