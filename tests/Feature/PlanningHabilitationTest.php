<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ScheduleShift;
use App\Models\Setting;
use App\Models\Supervisor;
use App\Models\User;
use App\Support\SupervisorOperation;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlanningHabilitationTest extends TestCase
{
    use RefreshDatabase;

    private const DENIAL_MESSAGE = 'Echec authentification superviseur: fonctionnalité non autorisée';

    public function test_moderator_cannot_access_the_planning_management_page(): void
    {
        $moderator = User::factory()->create(['global_role' => 'moderator']);

        $this->actingAs($moderator)
            ->get(route('employee.plannings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_the_planning_management_page(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        User::factory()->create(['global_role' => 'moderator', 'name' => 'Salarié Test']);

        $this->actingAs($admin)
            ->get(route('employee.plannings.index'))
            ->assertOk()
            ->assertSee('Salarié Test');
    }

    public function test_supervisor_without_planning_edit_habilitation_cannot_unlock_the_editor(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        Supervisor::create([
            'supervisor_number' => 'PLN100',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_PDF],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $response = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.edit'), [
                'user_id' => $employee->id,
                'week' => $weekStart,
                'supervisor_number' => 'PLN100',
                'supervisor_pin' => '1234',
            ]);

        $response->assertSessionHasErrors(['supervisor_pin' => self::DENIAL_MESSAGE]);
        $this->assertDatabaseHas(ActivityLog::class, ['action' => 'auth.supervisor_denied']);
    }

    public function test_supervisor_with_planning_edit_habilitation_can_save_the_week_without_reauthenticating(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        Supervisor::create([
            'supervisor_number' => 'PLN101',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.edit'), [
                'user_id' => $employee->id,
                'week' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN101',
                'supervisor_pin' => '1234',
            ])
            ->assertOk();

        // La sauvegarde n'exige pas de nouvelle authentification : le verrou de
        // session posé par l'étape précédente couvre l'enregistrement.
        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_WORK,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '09:00',
                        'end_time' => '12:00',
                        'title' => 'Service matin',
                    ],
                ],
            ])
            ->assertRedirect(route('employee.plannings.index', [
                'user_id' => $employee->id,
                'week' => $weekStart->toDateString(),
            ]));

        $this->assertDatabaseHas(ScheduleShift::class, [
            'user_id' => $employee->id,
            'date' => $weekStart->toDateString(),
            'title' => 'Service matin',
        ]);
        $this->assertDatabaseHas(ActivityLog::class, ['action' => 'planning.updated']);
    }

    public function test_a_full_day_leave_can_be_saved_without_hours(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        Supervisor::create([
            'supervisor_number' => 'PLN104',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN104',
                'supervisor_pin' => '1234',
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_LEAVE,
                        'date' => $weekStart->toDateString(),
                        'title' => 'Congés payés',
                    ],
                ],
            ])
            ->assertRedirect(route('employee.plannings.index', [
                'user_id' => $employee->id,
                'week' => $weekStart->toDateString(),
            ]));

        $this->assertDatabaseHas(ScheduleShift::class, [
            'user_id' => $employee->id,
            'type' => ScheduleShift::TYPE_LEAVE,
            'date' => $weekStart->toDateString(),
            'start_time' => null,
            'end_time' => null,
            'title' => 'Congés payés',
        ]);
    }

    public function test_a_leave_with_hours_is_rejected(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        Supervisor::create([
            'supervisor_number' => 'PLN105',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN105',
                'supervisor_pin' => '1234',
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_LEAVE,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '09:00',
                        'end_time' => '12:00',
                    ],
                ],
            ])
            ->assertSessionHasErrors('events');

        $this->assertDatabaseMissing(ScheduleShift::class, ['user_id' => $employee->id]);
    }

    public function test_employee_search_returns_matching_employees(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        User::factory()->create(['global_role' => 'moderator', 'name' => 'Alice Martin']);
        User::factory()->create(['global_role' => 'moderator', 'name' => 'Bob Dupont']);

        $response = $this->actingAs($admin)
            ->getJson(route('employee.plannings.employees.search', ['q' => 'Alice']));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Alice Martin']);
    }

    public function test_moderator_cannot_use_employee_search(): void
    {
        $moderator = User::factory()->create(['global_role' => 'moderator']);

        $this->actingAs($moderator)
            ->getJson(route('employee.plannings.employees.search', ['q' => 'a']))
            ->assertForbidden();
    }

    public function test_saving_a_past_week_is_rejected(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);

        $pastWeek = now()->startOfWeek(Carbon::MONDAY)->subWeek();

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $pastWeek->toDateString(),
                'events' => [],
            ])
            ->assertSessionHasErrors('week_start');

        $this->assertDatabaseMissing(ScheduleShift::class, ['user_id' => $employee->id]);
    }

    public function test_supervisor_without_planning_pdf_habilitation_cannot_generate_the_pdf(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        Supervisor::create([
            'supervisor_number' => 'PLN102',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $response = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.pdf'), [
                'selected_users' => [$employee->id],
                'week_start' => $weekStart,
                'supervisor_number' => 'PLN102',
                'supervisor_pin' => '1234',
            ]);

        $response->assertSessionHasErrors(['supervisor_pin' => self::DENIAL_MESSAGE]);
        $this->assertDatabaseHas(ActivityLog::class, ['action' => 'auth.supervisor_denied']);
    }

    public function test_active_permanent_supervision_bypasses_planning_pdf_validation(): void
    {
        $superAdmin = User::factory()->create(['global_role' => 'superadmin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        $supervisor = Supervisor::create([
            'supervisor_number' => 'PLN106',
            'password' => Hash::make('1234'),
            'superadmin_id' => $superAdmin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_PDF],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $response = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($superAdmin)
            ->withSession([
                'supervision.permanent' => [
                    'user_id' => $superAdmin->id,
                    'supervisor_id' => $supervisor->id,
                    'enabled_at' => time(),
                ],
            ])
            ->post(route('employee.plannings.pdf'), [
                'selected_users' => [$employee->id],
                'week_start' => $weekStart,
            ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_a_work_shift_within_the_opening_hours_margin_is_accepted(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        Supervisor::create([
            'supervisor_number' => 'PLN108',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        // Horaires par défaut du lundi : 07:00 – 19:00. La marge de 30 minutes autorise donc
        // un service commençant à 06:30 et se terminant à 19:30.
        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN108',
                'supervisor_pin' => '1234',
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_WORK,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '06:30',
                        'end_time' => '19:30',
                        'title' => 'Ouverture',
                    ],
                ],
            ])
            ->assertRedirect(route('employee.plannings.index', [
                'user_id' => $employee->id,
                'week' => $weekStart->toDateString(),
            ]));

        $this->assertDatabaseHas(ScheduleShift::class, [
            'user_id' => $employee->id,
            'date' => $weekStart->toDateString(),
            'title' => 'Ouverture',
        ]);
    }

    public function test_a_work_shift_outside_the_opening_hours_margin_is_rejected(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        // Un compte non-modérateur reste soumis aux horaires d'ouverture.
        $employee = User::factory()->create(['global_role' => 'admin']);
        Supervisor::create([
            'supervisor_number' => 'PLN109',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        // Le lundi ouvre à 07:00 : un service débutant à 06:00 dépasse la marge de 30 minutes.
        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN109',
                'supervisor_pin' => '1234',
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_WORK,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '06:00',
                        'end_time' => '12:00',
                    ],
                ],
            ])
            ->assertSessionHasErrors('events');

        $this->assertDatabaseMissing(ScheduleShift::class, ['user_id' => $employee->id]);
    }

    public function test_a_work_shift_is_rejected_on_an_exceptionally_closed_day(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        // Un compte non-modérateur reste soumis aux horaires d'ouverture.
        $employee = User::factory()->create(['global_role' => 'admin']);
        Supervisor::create([
            'supervisor_number' => 'PLN110',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $hours = Setting::getHours();
        $hours['exceptions'][] = [
            'date' => $weekStart->toDateString(),
            'label' => 'Fermeture exceptionnelle',
            'open' => false,
        ];
        Setting::set(Setting::KEY_SHOP_HOURS, json_encode($hours));

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN110',
                'supervisor_pin' => '1234',
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_WORK,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '09:00',
                        'end_time' => '12:00',
                    ],
                ],
            ])
            ->assertSessionHasErrors('events');

        $this->assertDatabaseMissing(ScheduleShift::class, ['user_id' => $employee->id]);
    }

    public function test_an_exceptional_opening_allows_a_work_shift_on_a_normally_closed_day(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        Supervisor::create([
            'supervisor_number' => 'PLN111',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $hours = Setting::getHours();
        $hours['regular']['monday'] = ['open' => false, 'from' => null, 'to' => null];
        $hours['exceptions'][] = [
            'date' => $weekStart->toDateString(),
            'label' => 'Ouverture exceptionnelle',
            'open' => true,
            'from' => '10:00',
            'to' => '14:00',
        ];
        Setting::set(Setting::KEY_SHOP_HOURS, json_encode($hours));

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN111',
                'supervisor_pin' => '1234',
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_WORK,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '10:00',
                        'end_time' => '13:00',
                        'title' => 'Vente spéciale',
                    ],
                ],
            ])
            ->assertRedirect(route('employee.plannings.index', [
                'user_id' => $employee->id,
                'week' => $weekStart->toDateString(),
            ]));

        $this->assertDatabaseHas(ScheduleShift::class, [
            'user_id' => $employee->id,
            'date' => $weekStart->toDateString(),
            'title' => 'Vente spéciale',
        ]);
    }

    public function test_an_exceptional_opening_still_enforces_its_own_hours(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        // Un compte non-modérateur reste soumis aux horaires d'ouverture.
        $employee = User::factory()->create(['global_role' => 'admin']);
        Supervisor::create([
            'supervisor_number' => 'PLN112',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $hours = Setting::getHours();
        $hours['regular']['monday'] = ['open' => false, 'from' => null, 'to' => null];
        $hours['exceptions'][] = [
            'date' => $weekStart->toDateString(),
            'label' => 'Ouverture exceptionnelle',
            'open' => true,
            'from' => '10:00',
            'to' => '14:00',
        ];
        Setting::set(Setting::KEY_SHOP_HOURS, json_encode($hours));

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN112',
                'supervisor_pin' => '1234',
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_WORK,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '08:00',
                        'end_time' => '09:00',
                    ],
                ],
            ])
            ->assertSessionHasErrors('events');

        $this->assertDatabaseMissing(ScheduleShift::class, ['user_id' => $employee->id]);
    }

    public function test_a_moderator_account_can_have_work_hours_saved_even_when_the_shop_is_closed(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        Supervisor::create([
            'supervisor_number' => 'PLN113',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $hours = Setting::getHours();
        $hours['exceptions'][] = [
            'date' => $weekStart->toDateString(),
            'label' => 'Fermeture exceptionnelle',
            'open' => false,
        ];
        Setting::set(Setting::KEY_SHOP_HOURS, json_encode($hours));

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN113',
                'supervisor_pin' => '1234',
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_WORK,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '02:00',
                        'end_time' => '04:00',
                        'title' => 'Préparation nocturne',
                    ],
                ],
            ])
            ->assertRedirect(route('employee.plannings.index', [
                'user_id' => $employee->id,
                'week' => $weekStart->toDateString(),
            ]));

        $this->assertDatabaseHas(ScheduleShift::class, [
            'user_id' => $employee->id,
            'title' => 'Préparation nocturne',
        ]);
    }

    public function test_work_and_leave_cannot_be_saved_on_the_same_day(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        Supervisor::create([
            'supervisor_number' => 'PLN114',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN114',
                'supervisor_pin' => '1234',
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_WORK,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '09:00',
                        'end_time' => '12:00',
                    ],
                    [
                        'type' => ScheduleShift::TYPE_LEAVE,
                        'date' => $weekStart->toDateString(),
                    ],
                ],
            ])
            ->assertSessionHasErrors('events');

        $this->assertDatabaseMissing(ScheduleShift::class, ['user_id' => $employee->id]);
    }

    public function test_leave_and_absence_cannot_be_saved_on_the_same_day(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        Supervisor::create([
            'supervisor_number' => 'PLN115',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN115',
                'supervisor_pin' => '1234',
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_LEAVE,
                        'date' => $weekStart->toDateString(),
                    ],
                    [
                        'type' => ScheduleShift::TYPE_ABSENCE,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '09:00',
                        'end_time' => '12:00',
                    ],
                ],
            ])
            ->assertSessionHasErrors('events');

        $this->assertDatabaseMissing(ScheduleShift::class, ['user_id' => $employee->id]);
    }

    public function test_a_partial_absence_can_coexist_with_a_work_shift_on_the_same_day(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        Supervisor::create([
            'supervisor_number' => 'PLN119',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN119',
                'supervisor_pin' => '1234',
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_WORK,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '09:00',
                        'end_time' => '17:00',
                    ],
                    [
                        'type' => ScheduleShift::TYPE_ABSENCE,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '14:00',
                        'end_time' => '15:00',
                        'title' => 'Rendez-vous médical',
                    ],
                ],
            ])
            ->assertRedirect(route('employee.plannings.index', [
                'user_id' => $employee->id,
                'week' => $weekStart->toDateString(),
            ]));

        $this->assertDatabaseHas(ScheduleShift::class, [
            'user_id' => $employee->id,
            'type' => ScheduleShift::TYPE_WORK,
            'date' => $weekStart->toDateString(),
        ]);
        $this->assertDatabaseHas(ScheduleShift::class, [
            'user_id' => $employee->id,
            'type' => ScheduleShift::TYPE_ABSENCE,
            'title' => 'Rendez-vous médical',
        ]);
    }

    public function test_a_full_day_absence_cannot_coexist_with_a_work_shift_on_the_same_day(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        Supervisor::create([
            'supervisor_number' => 'PLN120',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN120',
                'supervisor_pin' => '1234',
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_WORK,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '09:00',
                        'end_time' => '17:00',
                    ],
                    [
                        'type' => ScheduleShift::TYPE_ABSENCE,
                        'date' => $weekStart->toDateString(),
                    ],
                ],
            ])
            ->assertSessionHasErrors('events');

        $this->assertDatabaseMissing(ScheduleShift::class, ['user_id' => $employee->id]);
    }

    public function test_a_meeting_can_be_added_alongside_a_work_shift_on_the_same_day(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        Supervisor::create([
            'supervisor_number' => 'PLN116',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN116',
                'supervisor_pin' => '1234',
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_WORK,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '09:00',
                        'end_time' => '17:00',
                    ],
                    [
                        'type' => ScheduleShift::TYPE_MEETING,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '10:00',
                        'end_time' => '11:00',
                        'title' => 'Réunion hebdomadaire',
                    ],
                ],
            ])
            ->assertRedirect(route('employee.plannings.index', [
                'user_id' => $employee->id,
                'week' => $weekStart->toDateString(),
            ]));

        $this->assertDatabaseHas(ScheduleShift::class, [
            'user_id' => $employee->id,
            'type' => ScheduleShift::TYPE_MEETING,
            'title' => 'Réunion hebdomadaire',
        ]);
    }

    public function test_a_meeting_cannot_be_added_on_a_leave_day(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        Supervisor::create([
            'supervisor_number' => 'PLN117',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN117',
                'supervisor_pin' => '1234',
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_LEAVE,
                        'date' => $weekStart->toDateString(),
                    ],
                    [
                        'type' => ScheduleShift::TYPE_MEETING,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '10:00',
                        'end_time' => '11:00',
                    ],
                ],
            ])
            ->assertSessionHasErrors('events');

        $this->assertDatabaseMissing(ScheduleShift::class, ['user_id' => $employee->id]);
    }

    public function test_a_meeting_outside_opening_hours_is_rejected_for_non_moderator_accounts(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        // Un compte non-modérateur reste soumis aux horaires d'ouverture, y compris pour les réunions.
        $employee = User::factory()->create(['global_role' => 'admin']);
        Supervisor::create([
            'supervisor_number' => 'PLN118',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_EDIT],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.update'), [
                'user_id' => $employee->id,
                'week_start' => $weekStart->toDateString(),
                'supervisor_number' => 'PLN118',
                'supervisor_pin' => '1234',
                'events' => [
                    [
                        'type' => ScheduleShift::TYPE_MEETING,
                        'date' => $weekStart->toDateString(),
                        'start_time' => '05:00',
                        'end_time' => '06:00',
                    ],
                ],
            ])
            ->assertSessionHasErrors('events');

        $this->assertDatabaseMissing(ScheduleShift::class, ['user_id' => $employee->id]);
    }

    public function test_supervisor_with_planning_pdf_habilitation_can_generate_the_pdf(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $employee = User::factory()->create(['global_role' => 'moderator']);
        Supervisor::create([
            'supervisor_number' => 'PLN103',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PLANNING_PDF],
        ]);

        $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();

        $response = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.plannings.pdf'), [
                'selected_users' => [$employee->id],
                'week_start' => $weekStart,
                'supervisor_number' => 'PLN103',
                'supervisor_pin' => '1234',
            ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertDatabaseHas(ActivityLog::class, ['action' => 'planning.pdf_created']);
    }
}
