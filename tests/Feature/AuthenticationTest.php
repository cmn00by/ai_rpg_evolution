<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Personnage;
use Spatie\Permission\Models\Role;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les rôles de base
        Role::create(['name' => 'player']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'super-admin']);
    }

    public function test_user_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_user_can_access_character_selection()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get('/character/select');
            
        $response->assertStatus(200);
    }

    public function test_user_can_access_character_creation()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get('/character/create');
            
        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_character_routes()
    {
        $response = $this->get('/character/select');
        $response->assertRedirect('/login');
        
        $response = $this->get('/character/create');
        $response->assertRedirect('/login');
    }

    public function test_dashboard_is_accessible()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get('/dashboard');
            
        $response->assertStatus(200);
    }
}