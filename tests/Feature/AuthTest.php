<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    /**
     * Skenario 1: Register user baru
     */
    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => 'pegawai'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user', 'token']);
        
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => 'pegawai'
        ]);
    }

    /**
     * Skenario 2: Register validasi error (email duplikat)
     */
    public function test_register_fails_with_duplicate_email()
    {
        User::factory()->create(['email' => 'exist@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Another User',
            'email' => 'exist@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Skenario 3: Register validasi role invalid
     */
    public function test_register_fails_with_invalid_role()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'invalid_role'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    /**
     * Skenario 4: Login dengan kredensial valid
     */
    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'pegawai@example.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'pegawai@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user' => ['id','name','email','role'], 'token']);
    }

    /**
     * Skenario 5: Login gagal - password salah
     */
    public function test_login_fails_with_wrong_password()
    {
        User::factory()->create([
            'email' => 'pegawai@example.com',
            'password' => Hash::make('correct_password')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'pegawai@example.com',
            'password' => 'wrong_password'
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Invalid credentials');
    }

    /**
     * Skenario 6: Logout (revoke token)
     */
    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Logged out');
    }
}