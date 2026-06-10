<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::with('handler')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $contacts = $query->paginate(20);
        $statusLabels = Contact::STATUS_LABELS;

        return view('employee.contacts.index', compact('contacts', 'statusLabels'));
    }

    public function show(Contact $contact)
    {
        if ($contact->status === 'new') {
            $contact->update(['status' => 'read', 'handled_by' => auth()->id()]);
        }

        $statusLabels = Contact::STATUS_LABELS;

        return view('employee.contacts.show', compact('contact', 'statusLabels'));
    }

    public function reply(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'reply' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        $contact->update([
            'reply'       => $validated['reply'],
            'status'      => 'replied',
            'handled_by'  => auth()->id(),
            'replied_at'  => now(),
        ]);

        return redirect()->route('employee.contacts.show', $contact)
            ->with('success', 'Réponse enregistrée avec succès.');
    }

    public function archive(Contact $contact)
    {
        $contact->update(['status' => 'archived', 'handled_by' => auth()->id()]);

        return redirect()->back()->with('success', 'Message archivé.');
    }
}
