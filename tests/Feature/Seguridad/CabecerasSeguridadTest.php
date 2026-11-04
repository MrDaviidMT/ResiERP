<?php

namespace Tests\Feature\Seguridad;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// S8 y S13 (auditoría): cabeceras de seguridad en todas las respuestas
class CabecerasSeguridadTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_response_carries_the_security_headers(): void
    {
        $respuesta = $this->actingAs(User::factory()->role(Role::Director)->create())->get(route('dashboard'));

        $respuesta->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'same-origin');

        $csp = $respuesta->headers->get('Content-Security-Policy');
        foreach (["default-src 'self'", "frame-ancestors 'none'", "object-src 'none'", "connect-src 'self'"] as $directiva) {
            $this->assertStringContainsString($directiva, $csp);
        }
    }

    public function test_the_login_page_is_protected_too(): void
    {
        $this->get(route('login'))->assertOk()->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_hsts_is_only_sent_over_https(): void
    {
        $this->get(route('login'))->assertHeaderMissing('Strict-Transport-Security');

        $this->get(str_replace('http://', 'https://', route('login')))
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
