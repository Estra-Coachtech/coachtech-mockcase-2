<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function registration_sends_verification_email_and_redirects_to_verification_notice()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'verify@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 会員登録後はメール認証誘導画面へ遷移する
        $response->assertRedirect('/email/verify');

        $user = User::where('email', 'verify@example.com')->first();
        $this->assertNotNull($user);
        // まだ認証は完了していない
        $this->assertNull($user->email_verified_at);

        // 認証メールが送信されている
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** @test */
    public function verification_notice_screen_is_displayed_for_authenticated_user()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        $response->assertSee('メール認証を完了してください');
    }

    /** @test */
    public function email_is_verified_and_user_is_redirected_to_attendance_after_clicking_link()
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        // メール認証が完了し、勤怠登録画面へ遷移する
        $this->assertNotNull($user->fresh()->email_verified_at);
        $response->assertRedirect('/attendance?verified=1');
    }

    /** @test */
    public function verification_link_requires_authentication()
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        // 未ログイン状態ではログイン画面へリダイレクトされる（getKey() on null エラーは発生しない）
        $response = $this->get($verificationUrl);

        $response->assertRedirect('/login');
        $this->assertNull($user->fresh()->email_verified_at);
    }
}
