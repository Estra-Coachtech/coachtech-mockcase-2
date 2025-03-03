<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function validation_message_displayed_when_email_is_empty()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123'
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertContains('メールアドレスを入力してください' , session()->get('errors')->get('email'));
    }

    /** @test */
    public function validation_message_displayed_when_password_is_empty()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => ''
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertContains('パスワードを入力してください' , session()->get('errors')->get('password'));
    }

    /** @test */
    public function validation_message_displayed_when_registration_data_mismatch()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example',
            'password' => 'password123'
        ]);

        $response->assertSessionHasErrors(['email']);
        $errors = session('errors')->get('email');
        $this->assertContains('ログイン情報が登録されていません', $errors);
    }
}
