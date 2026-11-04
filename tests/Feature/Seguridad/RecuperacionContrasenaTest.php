<?php

namespace Tests\Feature\Seguridad;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

// Sin enumeración de cuentas y con límite de intentos por IP
class RecuperacionContrasenaTest extends TestCase
{
    use RefreshDatabase;

    public function test_response_is_identical_whether_or_not_the_email_exists(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();
        $mensajeExistente = session('status');
        $this->flushSession();

        $this->post('/forgot-password', ['email' => 'nadie@example.test'])->assertSessionHasNoErrors();
        $mensajeInexistente = session('status');

        $this->assertNotEmpty($mensajeExistente);
        $this->assertSame($mensajeExistente, $mensajeInexistente);
    }

    public function test_deactivated_accounts_do_not_receive_a_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->forceFill(['activo' => false])->save();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();

        Notification::assertNotSentTo($user, ResetPassword::class);
    }

    public function test_reset_link_requests_are_rate_limited_by_ip(): void
    {
        Notification::fake();

        foreach (range(1, 5) as $i) {
            $this->post('/forgot-password', ['email' => "persona{$i}@example.test"])->assertRedirect();
        }

        $this->post('/forgot-password', ['email' => 'otra@example.test'])->assertTooManyRequests();
    }

    public function test_password_confirmation_is_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 6) as $i) {
            $this->actingAs($user)->post('/confirm-password', ['password' => 'incorrecta']);
        }

        $this->actingAs($user)->post('/confirm-password', ['password' => 'incorrecta'])->assertTooManyRequests();
    }
}
