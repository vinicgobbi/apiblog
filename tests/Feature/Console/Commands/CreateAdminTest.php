<?php

namespace Tests\Feature\Console\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminTest extends TestCase
{
    use RefreshDatabase;

    private const string PASSWORD = 'senha-segura-123';

    protected function setUp(): void
    {
        parent::setUp();

        config(['blog.admin.name' => 'Administrador', 'blog.admin.email' => null]);
    }

    public function test_creates_admin_asking_every_field(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('E-mail', ' Admin@Blog.test ')
            ->expectsQuestion('Nome', 'Vinícius')
            ->expectsQuestion('Senha', self::PASSWORD)
            ->expectsQuestion('Confirme a senha', self::PASSWORD)
            ->expectsOutputToContain('Administrador admin@blog.test criado.')
            ->assertSuccessful();

        $admin = User::sole();
        $this->assertSame('admin@blog.test', $admin->email);
        $this->assertSame('Vinícius', $admin->name);
        $this->assertTrue(Hash::check(self::PASSWORD, $admin->password));
    }

    public function test_admin_created_by_the_command_can_log_in(): void
    {
        $this->artisan('admin:create', ['--name' => 'Admin', '--email' => 'admin@blog.test'])
            ->expectsQuestion('Senha', self::PASSWORD)
            ->expectsQuestion('Confirme a senha', self::PASSWORD)
            ->assertSuccessful();

        $this->postJson('/api/auth/login', ['email' => 'admin@blog.test', 'password' => self::PASSWORD])
            ->assertOk()
            ->assertJsonStructure(['access_token']);
    }

    public function test_fails_when_passwords_do_not_match(): void
    {
        $this->artisan('admin:create', ['--name' => 'Admin', '--email' => 'admin@blog.test'])
            ->expectsQuestion('Senha', self::PASSWORD)
            ->expectsQuestion('Confirme a senha', 'outra-senha-123')
            ->expectsOutputToContain('As senhas não conferem.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_rejects_invalid_email_option(): void
    {
        $this->artisan('admin:create', ['--name' => 'Admin', '--email' => 'nao-e-email'])
            ->expectsOutputToContain('Informe um e-mail válido.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_resets_name_and_password_of_existing_user_after_confirmation(): void
    {
        $user = User::factory()->create(['email' => 'admin@blog.test', 'name' => 'Antigo', 'password' => 'senha-antiga-1']);

        $this->artisan('admin:create', ['--email' => 'admin@blog.test'])
            ->expectsConfirmation('Já existe um usuário com o e-mail admin@blog.test. Redefinir o nome e a senha?', 'yes')
            ->expectsQuestion('Nome', 'Novo nome')
            ->expectsQuestion('Senha', self::PASSWORD)
            ->expectsQuestion('Confirme a senha', self::PASSWORD)
            ->expectsOutputToContain('Administrador admin@blog.test atualizado.')
            ->assertSuccessful();

        $user->refresh();
        $this->assertSame('Novo nome', $user->name);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_keeps_existing_user_when_reset_is_declined(): void
    {
        $user = User::factory()->create(['email' => 'admin@blog.test', 'password' => 'senha-antiga-1']);

        $this->artisan('admin:create', ['--email' => 'admin@blog.test'])
            ->expectsConfirmation('Já existe um usuário com o e-mail admin@blog.test. Redefinir o nome e a senha?', 'no')
            ->expectsOutputToContain('Nada foi alterado.')
            ->assertFailed();

        $this->assertTrue(Hash::check('senha-antiga-1', $user->fresh()->password));
    }

    public function test_refuses_to_run_without_interaction(): void
    {
        $this->artisan('admin:create', ['--name' => 'Admin', '--email' => 'admin@blog.test', '--no-interaction' => true])
            ->expectsOutputToContain('db:seed')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
