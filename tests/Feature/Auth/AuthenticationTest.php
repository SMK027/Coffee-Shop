<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->withSession([
            'captcha.employee_login_form' => [
                'question' => 'Combien font 2 + 2 ?',
                'answer' => '4',
            ],
        ])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'captcha' => '4',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->withSession([
            'captcha.employee_login_form' => [
                'question' => 'Combien font 2 + 2 ?',
                'answer' => '4',
            ],
        ])->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'captcha' => '4',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
