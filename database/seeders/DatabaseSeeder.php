<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Cria (ou atualiza) o administrador do blog a partir das variáveis
     * ADMIN_NAME, ADMIN_EMAIL e ADMIN_PASSWORD.
     *
     * Para popular o banco com conteúdo de exemplo, rode separadamente:
     * php artisan db:seed --class=DemoContentSeeder
     */
    public function run(): void
    {
        /** @var array{name: string, email: ?string, password: ?string} $admin */
        $admin = config('blog.admin');

        if (blank($admin['email']) || blank($admin['password'])) {
            $this->command?->warn('ADMIN_EMAIL e ADMIN_PASSWORD não estão configurados: o administrador não foi criado.');

            return;
        }

        User::updateOrCreate(
            ['email' => $admin['email']],
            ['name' => $admin['name'], 'password' => $admin['password']],
        );
    }
}
