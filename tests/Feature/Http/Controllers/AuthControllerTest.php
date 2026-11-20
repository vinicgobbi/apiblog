<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private const string PASSWORD = 'senha-correta';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['email' => 'admin@blog.test', 'password' => self::PASSWORD]);
    }

    public function test_valid_credentials_return_bearer_token(): void
    {
        $response = $this->postJson('/api/auth/login', ['email' => 'admin@blog.test', 'password' => self::PASSWORD]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in'])
            ->assertJsonPath('token_type', 'bearer')
            ->assertJsonPath('expires_in', config('jwt.ttl') * 60);
    }

    public function test_wrong_password_returns_401(): void
    {
        $response = $this->postJson('/api/auth/login', ['email' => 'admin@blog.test', 'password' => 'errada']);

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Credenciais inválidas.')
            ->assertJsonMissingPath('access_token');
    }

    public function test_missing_or_malformed_fields_return_422(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'nao-e-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email' => 'Informe um e-mail válido',
                'password' => 'A senha é obrigatória',
            ]);
    }

    public function test_sixth_login_attempt_within_a_minute_returns_429(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/auth/login', ['email' => 'admin@blog.test', 'password' => 'errada'])->assertUnauthorized();
        }

        $this->postJson('/api/auth/login', ['email' => 'admin@blog.test', 'password' => self::PASSWORD])
            ->assertTooManyRequests();
    }

    public function test_authenticated_user_endpoint_returns_admin_data_without_password(): void
    {
        $this->withToken($this->login())
            ->getJson('/api/auth/user')
            ->assertOk()
            ->assertExactJson(['data' => ['id' => $this->admin->id, 'name' => $this->admin->name, 'email' => 'admin@blog.test']]);
    }

    public function test_protected_endpoints_return_401_without_token(): void
    {
        $this->getJson('/api/auth/user')->assertUnauthorized();
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }

    public function test_protected_endpoint_returns_401_with_invalid_token(): void
    {
        $this->withToken('token.invalido.qualquer')->getJson('/api/auth/user')->assertUnauthorized();
    }

    public function test_logout_invalidates_the_token(): void
    {
        $token = $this->login();

        $this->withToken($token)->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout realizado com sucesso.');

        $this->forgetAuthenticatedUser();
        $this->withToken($token)->getJson('/api/auth/user')->assertUnauthorized();
    }

    public function test_refresh_returns_new_token_and_invalidates_the_old_one(): void
    {
        $oldToken = $this->login();

        $newToken = $this->withToken($oldToken)->postJson('/api/auth/refresh')
            ->assertOk()
            ->json('access_token');

        $this->assertNotSame($oldToken, $newToken);
        $this->forgetAuthenticatedUser();
        $this->withToken($oldToken)->getJson('/api/auth/user')->assertUnauthorized();
        $this->forgetAuthenticatedUser();
        $this->withToken($newToken)->getJson('/api/auth/user')->assertOk();
    }

    public function test_expired_access_token_can_be_refreshed_within_refresh_window(): void
    {
        $token = $this->login();
        $this->travel(config('jwt.ttl') + 1)->minutes();

        $this->withToken($token)->getJson('/api/auth/user')->assertUnauthorized();
        $this->forgetAuthenticatedUser();
        $this->withToken($token)->postJson('/api/auth/refresh')->assertOk();
    }

    public function test_refresh_after_refresh_window_returns_401(): void
    {
        $token = $this->login();
        $this->travel(config('jwt.refresh_ttl') + 1)->minutes();

        $this->withToken($token)->postJson('/api/auth/refresh')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Token inválido ou expirado.');
    }

    public function test_refresh_without_token_returns_401(): void
    {
        $this->postJson('/api/auth/refresh')->assertUnauthorized();
    }

    private function login(): string
    {
        $token = $this->postJson('/api/auth/login', ['email' => 'admin@blog.test', 'password' => self::PASSWORD])->json('access_token');
        $this->forgetAuthenticatedUser();

        return $token;
    }

    /**
     * O guard e o singleton do JWT guardam o usuário e o token em memória
     * entre requisições do mesmo teste; descartá-los simula uma nova
     * requisição real (a blacklist, no cache, é mantida).
     */
    private function forgetAuthenticatedUser(): void
    {
        $this->app->forgetInstance('tymon.jwt');
        $this->app->forgetInstance('tymon.jwt.auth');
        $this->app['auth']->forgetGuards();
    }
}
