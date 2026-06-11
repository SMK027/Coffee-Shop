<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Mail\ContactReplyMail;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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

        // Envoie la réponse par email à l'auteur du message de contact.
        Mail::to($contact->email)->send(new ContactReplyMail($contact, $validated['reply']));

        return redirect()->route('employee.contacts.show', $contact)
            ->with('success', "Réponse enregistrée et envoyée par email à {$contact->email}.");
    }

    public function archive(Contact $contact)
    {
        $contact->update(['status' => 'archived', 'handled_by' => auth()->id()]);

        return redirect()->back()->with('success', 'Message archivé.');
    }
}
