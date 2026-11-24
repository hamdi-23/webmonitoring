<?php

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $monitor = Monitor::factory()->create();

        $this->get('/')->assertRedirect('/login');
        $this->get('/monitors')->assertRedirect('/login');
        $this->get(route('monitors.show', $monitor))->assertRedirect('/login');
        $this->post(route('monitors.check', $monitor))->assertRedirect('/login');
        $this->delete(route('monitors.destroy', $monitor))->assertRedirect('/login');
        $this->assertModelExists($monitor);
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk');
    }

    public function test_user_can_log_in_and_out(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertRedirect(route('monitors.index'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertSessionHasErrors('email');
        $this->assertStringStartsWith('Terlalu banyak percobaan login', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_user_create_command(): void
    {
        $this->artisan('user:create', ['--name' => 'Admin', '--email' => 'admin@example.com', '--password' => 'rahasia123'])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);

        $this->artisan('user:create', ['--name' => 'Admin', '--email' => 'admin@example.com', '--password' => 'rahasia123'])
            ->assertFailed();
        $this->artisan('user:create', ['--name' => 'X', '--email' => 'x@example.com', '--password' => 'pendek'])
            ->assertFailed();
    }
}
