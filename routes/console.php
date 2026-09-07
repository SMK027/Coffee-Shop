<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Models\InternalNote;
use App\Models\Supervisor;

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

Artisan::command('supervisors:disable-expired-temporary', function () {
    $count = Supervisor::query()
        ->where('is_temporary', true)
        ->where('is_active', true)
        ->whereNotNull('temporary_expires_at')
        ->where('temporary_expires_at', '<=', now())
        ->update(['is_active' => false]);

    $this->info($count . ' superviseur(s) temporaire(s) désactivé(s).');
})->purpose('Désactive les superviseurs temporaires expirés');
