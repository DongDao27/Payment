<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_subscribe_with_explicit_consent_and_normalized_email(): void
    {
        $this->postJson('/api/v1/newsletter/subscriptions', ['email' => ' Reader@Example.Test ', 'consent' => true])
            ->assertStatus(202)->assertJsonPath('data.message', 'Đã ghi nhận đăng ký nhận bản tin.')
            ->assertJsonMissingPath('data.email')->assertJsonMissingPath('data.id');
        $this->assertDatabaseHas('newsletter_subscriptions', ['email' => 'reader@example.test']);
        $this->assertNotNull(NewsletterSubscription::first()->consented_at);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_duplicate_is_idempotent_and_does_not_reveal_existing_subscription(): void
    {
        $body = ['email' => 'reader@example.test', 'consent' => true];
        $first = $this->postJson('/api/v1/newsletter/subscriptions', $body)->assertStatus(202)->json('data');
        $consent = NewsletterSubscription::first()->consented_at;
        $this->travel(5)->minutes();
        $again = $this->postJson('/api/v1/newsletter/subscriptions', ['email' => 'READER@EXAMPLE.TEST', 'consent' => true])->assertStatus(202)->json('data');
        $this->assertSame($first, $again);
        $this->assertDatabaseCount('newsletter_subscriptions', 1);
        $this->assertTrue(NewsletterSubscription::first()->consented_at->equalTo($consent));
    }

    public function test_invalid_email_missing_consent_and_unexpected_fields_do_not_write(): void
    {
        foreach ([['email' => 'not-an-email', 'consent' => true], ['email' => 'reader@example.test'], ['email' => 'reader@example.test', 'consent' => false], ['email' => 'reader@example.test', 'consent' => true, 'role' => 'admin']] as $body) {
            $this->postJson('/api/v1/newsletter/subscriptions', $body)->assertUnprocessable();
        }
        $this->assertDatabaseCount('newsletter_subscriptions', 0);
    }

    public function test_newsletter_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/newsletter/subscriptions', ['email' => 'reader@example.test', 'consent' => true])->assertStatus(202);
        }
        $this->postJson('/api/v1/newsletter/subscriptions', ['email' => 'reader@example.test', 'consent' => true])->assertStatus(429);
        $this->assertDatabaseCount('newsletter_subscriptions', 1);
    }

    public function test_web_csrf_middleware_is_present_and_rejects_a_request_without_token(): void
    {
        // Laravel bypasses CSRF in testing by default; exercise the real guard.
        $this->app['env'] = 'local';
        $this->postJson('/api/v1/newsletter/subscriptions', ['email' => 'reader@example.test', 'consent' => true])->assertStatus(419);
        $this->assertDatabaseCount('newsletter_subscriptions', 0);
    }
}
