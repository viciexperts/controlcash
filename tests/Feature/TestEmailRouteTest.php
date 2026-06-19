<?php

namespace Tests\Feature;

use App\Mail\TestEmail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TestEmailRouteTest extends TestCase
{
    public function test_test_email_route_is_hidden_without_token_configured(): void
    {
        config(['services.test_email.token' => null]);

        $this
            ->get(route('ops.test-email', ['token' => 'anything', 'to' => 'omar@example.com']))
            ->assertNotFound();
    }

    public function test_test_email_route_requires_matching_token(): void
    {
        config(['services.test_email.token' => 'secret-token']);

        $this
            ->get(route('ops.test-email', ['token' => 'wrong-token', 'to' => 'omar@example.com']))
            ->assertForbidden();
    }

    public function test_test_email_route_sends_email_with_valid_token(): void
    {
        Mail::fake();
        config([
            'services.test_email.token' => 'secret-token',
            'services.test_email.to' => 'fallback@example.com',
        ]);

        $this
            ->get(route('ops.test-email', ['token' => 'secret-token', 'to' => 'omar@example.com']))
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'to' => 'omar@example.com',
            ]);

        Mail::assertSent(TestEmail::class, 1);
    }
}
