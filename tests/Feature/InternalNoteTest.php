<?php

namespace Tests\Feature;

use App\Models\InternalNote;
use App\Models\Supervisor;
use App\Models\User;
use App\Support\SupervisorOperation;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InternalNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_only_read_notes_addressed_to_them(): void
    {
        $author = User::factory()->create(['global_role' => 'admin']);
        $recipient = User::factory()->create(['global_role' => 'moderator']);
        $otherUser = User::factory()->create(['global_role' => 'admin']);

        $note = InternalNote::create([
            'author_id' => $author->id,
            'title' => 'Note confidentielle',
            'description' => 'Contenu réservé.',
            'display_location' => 'inbox',
            'background_color' => '#FEF3C7',
            'text_color' => '#78350F',
            'font_family' => 'sans-serif',
        ]);
        $note->recipients()->attach($recipient->id);

        $this->actingAs($recipient)
            ->get(route('employee.internal-notes.index'))
            ->assertOk()
            ->assertSee('Note confidentielle');

        $this->actingAs($otherUser)
            ->get(route('employee.internal-notes.index'))
            ->assertOk()
            ->assertDontSee('Note confidentielle');
    }

    public function test_opening_note_editor_requires_supervisor_validation(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->get(route('employee.internal-notes.create'))
            ->assertRedirect(route('employee.supervision.challenge'));
    }

    public function test_validated_supervisor_can_open_editor_and_send_note(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $recipient = User::factory()->create(['global_role' => 'moderator']);
        $supervisor = Supervisor::create([
            'supervisor_number' => 'SUP001',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::INTERNAL_NOTES],
        ]);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->get(route('employee.internal-notes.create'));

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.supervision.approve'), [
                'supervisor_number' => $supervisor->supervisor_number,
                'supervisor_pin' => '1234',
            ]);

        $nonce = array_key_first(session('supervision.bypasses', []));

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->get(route('employee.internal-notes.create', [
                '__supervision_bypass_nonce' => $nonce,
            ]))
            ->assertOk()
            ->assertViewIs('employee.internal-notes.create');

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.internal-notes.store'), [
                'title' => 'Information équipe',
                'description' => '<p>Réunion à 10h.</p>',
                'display_location' => 'inbox',
                'audience' => 'targeted',
                'recipient_ids' => [$recipient->id],
                'background_color' => '#FEF3C7',
                'text_color' => '#78350F',
                'font_family' => 'Figtree, ui-sans-serif, system-ui, sans-serif',
            ])
            ->assertRedirect(route('employee.internal-notes.index'));

        $this->assertDatabaseHas(InternalNote::class, ['title' => 'Information équipe']);
    }

    public function test_expired_note_is_not_visible_and_can_be_purged(): void
    {
        $author = User::factory()->create(['global_role' => 'admin']);
        $recipient = User::factory()->create(['global_role' => 'moderator']);
        $note = InternalNote::create([
            'author_id' => $author->id,
            'title' => 'Note expirée',
            'description' => '<p>Ne plus afficher.</p>',
            'display_location' => 'inbox',
            'is_for_all' => true,
            'background_color' => '#FEF3C7',
            'text_color' => '#78350F',
            'font_family' => 'Figtree, ui-sans-serif, system-ui, sans-serif',
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($recipient)
            ->get(route('employee.internal-notes.index'))
            ->assertOk()
            ->assertDontSee('Note expirée');

        $this->artisan('internal-notes:purge-expired')->assertSuccessful();
        $this->assertDatabaseMissing(InternalNote::class, ['id' => $note->id]);
    }

    public function test_author_can_edit_and_delete_note_after_supervisor_validation(): void
    {
        $author = User::factory()->create(['global_role' => 'admin']);
        $recipient = User::factory()->create(['global_role' => 'moderator']);
        $supervisor = Supervisor::create([
            'supervisor_number' => 'SUP009',
            'password' => Hash::make('1234'),
            'superadmin_id' => $author->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::INTERNAL_NOTES],
        ]);
        $note = InternalNote::create([
            'author_id' => $author->id,
            'title' => 'Version initiale',
            'description' => '<p>Initiale</p>',
            'display_location' => 'inbox',
            'background_color' => '#FEF3C7',
            'text_color' => '#78350F',
            'font_family' => 'Figtree, ui-sans-serif, system-ui, sans-serif',
        ]);
        $note->recipients()->attach($recipient->id);

        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($author)
            ->get(route('employee.internal-notes.edit', $note))
            ->assertRedirect(route('employee.supervision.challenge'));

        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($author)
            ->post(route('employee.supervision.approve'), ['supervisor_number' => 'SUP009', 'supervisor_pin' => '1234']);
        $editNonce = array_key_first(session('supervision.bypasses', []));

        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($author)
            ->get(route('employee.internal-notes.edit', ['note' => $note, '__supervision_bypass_nonce' => $editNonce]))
            ->assertOk();
        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($author)
            ->put(route('employee.internal-notes.update', $note), [
                'title' => 'Version modifiée', 'description' => '<p>Modifiée</p>',
                'display_location' => 'inbox', 'audience' => 'all',
                'background_color' => '#FEF3C7', 'text_color' => '#78350F',
                'font_family' => 'Figtree, ui-sans-serif, system-ui, sans-serif',
            ])->assertRedirect(route('employee.internal-notes.index'));
        $this->assertDatabaseHas(InternalNote::class, ['id' => $note->id, 'title' => 'Version modifiée', 'is_for_all' => true]);

        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($author)
            ->delete(route('employee.internal-notes.destroy', $note))
            ->assertRedirect(route('employee.supervision.challenge'));
        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($author)
            ->post(route('employee.supervision.approve'), ['supervisor_number' => 'SUP009', 'supervisor_pin' => '1234']);
        $deleteNonce = array_key_first(session('supervision.bypasses', []));
        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($author)
            ->delete(route('employee.internal-notes.destroy', $note), ['__supervision_bypass_nonce' => $deleteNonce])
            ->assertRedirect(route('employee.internal-notes.index'));

        $this->assertDatabaseMissing(InternalNote::class, ['id' => $note->id]);
    }
}
