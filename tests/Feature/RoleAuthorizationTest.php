<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleAuthorizationTest extends TestCase
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

    public function test_new_user_gets_player_role_automatically()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue($user->hasRole('player'));
    }

    public function test_user_with_player_role_can_access_character_routes()
    {
        $player = User::factory()->create();
        $player->assignRole('player');

        $response = $this->actingAs($player)
            ->get('/character/select');
            
        $response->assertStatus(200);
    }

    public function test_user_can_access_character_routes_without_role_middleware()
    {
        $user = User::factory()->create();
        // Pas de rôle assigné, mais les routes character n'ont pas de middleware role

        $response = $this->actingAs($user)
            ->get('/character/select');
            
        // Les routes character sont accessibles sans middleware role
        $response->assertStatus(200);
    }

    public function test_role_assignment_works_correctly()
    {
        $user = User::factory()->create();
        
        // Vérifier qu'initialement l'utilisateur n'a pas de rôle
        $this->assertFalse($user->hasRole('player'));
        $this->assertFalse($user->hasRole('admin'));
        
        // Assigner le rôle player
        $user->assignRole('player');
        
        // Vérifier que le rôle a été assigné
        $this->assertTrue($user->hasRole('player'));
        $this->assertFalse($user->hasRole('admin'));
    }
    
    public function test_admin_can_access_admin_routes()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        $response = $this->actingAs($user)
            ->get('/admin/dashboard');
            
        $response->assertStatus(200);
    }
    
    public function test_regular_user_cannot_access_admin_routes()
    {
        $user = User::factory()->create();
        // L'utilisateur a seulement le rôle 'player' par défaut
        
        $response = $this->actingAs($user)
            ->get('/admin/dashboard');
            
        $response->assertStatus(403);
    }
}