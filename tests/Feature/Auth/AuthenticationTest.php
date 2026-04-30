<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_authenticated_users_are_redirected_away_from_login_screen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
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

    public function test_login_request_rate_limits_repeated_failures(): void
    {
        Event::fake();
        $request = LoginRequest::create('/login', 'POST', [
            'email' => 'blocked@example.test',
            'password' => 'password',
        ]);
        $request->setLaravelSession(app('session')->driver());
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            RateLimiter::hit($request->throttleKey());
        }

        $this->expectException(ValidationException::class);

        try {
            $request->ensureIsNotRateLimited();
        } finally {
            Event::assertDispatched(Lockout::class);
            RateLimiter::clear($request->throttleKey());
        }
    }
}
