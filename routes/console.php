<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Models\InternalNote;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('internal-notes:purge-expired', function () {
    $notes = InternalNote::with('attachments')->whereNotNull('expires_at')->where('expires_at', '<=', now())->get();

    foreach ($notes as $note) {
        Storage::disk('local')->delete($note->attachments->pluck('path')->all());
        $note->delete();
    }

    $this->info($notes->count() . ' note(s) interne(s) expirée(s) supprimée(s).');
})->purpose('Supprime les notes internes expirées et leurs pièces jointes');
