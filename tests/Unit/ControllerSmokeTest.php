<?php

namespace Tests\Unit;

use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\HomeController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Tests\TestCase;

class ControllerSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_controller_returns_home_view(): void
    {
        $response = (new HomeController)->index();

        $this->assertInstanceOf(View::class, $response);
        $this->assertSame('home', $response->name());
    }

    public function test_legacy_login_controller_sets_home_redirect(): void
    {
        $controller = new LoginController;

        $this->assertSame('/home', $this->readProperty($controller, 'redirectTo'));
    }

    public function test_legacy_confirm_password_controller_sets_home_redirect(): void
    {
        $controller = new ConfirmPasswordController;

        $this->assertSame('/home', $this->readProperty($controller, 'redirectTo'));
    }

    public function test_legacy_verification_controller_sets_home_redirect(): void
    {
        $controller = new VerificationController;

        $this->assertSame('/home', $this->readProperty($controller, 'redirectTo'));
    }

    public function test_legacy_register_controller_validates_and_creates_user(): void
    {
        $controller = new class extends RegisterController
        {
            public function exposedValidator(array $data)
            {
                return $this->validator($data);
            }

            public function exposedCreate(array $data): User
            {
                return $this->create($data);
            }
        };

        $validator = $controller->exposedValidator([
            'name' => 'Test User',
            'email' => 'test@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertFalse($validator->fails());

        $user = $controller->exposedCreate([
            'name' => 'Test User',
            'email' => 'test@example.test',
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.test']);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
