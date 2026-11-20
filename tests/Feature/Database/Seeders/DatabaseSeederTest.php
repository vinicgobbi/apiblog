<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_admin_from_configuration_with_hashed_password(): void
    {
        $this->configureAdmin('admin@blog.test', 'senha-secreta');

        $this->seed(DatabaseSeeder::class);

        $admin = User::sole();
        $this->assertSame('admin@blog.test', $admin->email);
        $this->assertSame('Administrador', $admin->name);
        $this->assertTrue(Hash::check('senha-secreta', $admin->password));
    }

    public function test_running_again_updates_existing_admin_instead_of_duplicating(): void
    {
        $this->configureAdmin('admin@blog.test', 'senha-antiga');
        $this->seed(DatabaseSeeder::class);

        $this->configureAdmin('admin@blog.test', 'senha-nova');
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Hash::check('senha-nova', User::sole()->password));
    }

    public function test_does_not_create_admin_without_email_or_password(): void
    {
        $this->configureAdmin('admin@blog.test', null);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }

    private function configureAdmin(?string $email, ?string $password): void
    {
        config(['blog.admin' => ['name' => 'Administrador', 'email' => $email, 'password' => $password]]);
    }
}
