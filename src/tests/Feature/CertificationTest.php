<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class CertificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_validation_message_displayed_when_name_is_empty()
    {
        $response = $this->post('/register', [
            'email' => 'test@example',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'name' => '',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertContains('お名前を入力してください' , session()->get('errors')->get('name'));
    }

    /** @test */
    public function test_validation_message_displayed_when_email_is_empty()
    {
        $response = $this->post('/register', [
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'name' => 'テスト名前',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertContains('メールアドレスを入力してください' , session()->get('errors')->get('email'));
    }

    /** @test */
    public function test_validation_message_displayed_when_password_is_less_than_8_characters()
    {
        $response = $this->post('/register', [
            'email' => 'test@example',
            'password' => 'pass',
            'password_confirmation' => 'pass',
            'name' => 'テスト名前',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertContains('パスワードは8文字以上で入力してください' , session()->get('errors')->get('password'));
    }

    /** @test */
    public function test_validation_message_displayed_when_password_confirmation_does_not_match()
    {
        $response = $this->post('/register', [
            'email' => 'test@example',
            'password' => 'password123',
            'password_confirmation' => 'password456',
            'name' => 'テスト名前',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertContains('パスワードと一致しません' , session()->get('errors')->get('password'));
    }

    /** @test */
    public function test_validation_message_displayed_when_password_is_empty()
    {
        $response = $this->post('/register', [
            'email' => 'test@example',
            'password' => '',
            'password_confirmation' => 'password123',
            'name' => 'テスト名前',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertContains('パスワードを入力してください' , session()->get('errors')->get('password'));
    }

    /** @test */
    public function test_data_saved_successfully_when_form_is_filled_correctly()
    {
        $response = $this->post('/register', [
            'email' => 'test@example',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'name' => 'テスト名前',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'テスト名前',
            'email' => 'test@example',
        ]);
    }
}
