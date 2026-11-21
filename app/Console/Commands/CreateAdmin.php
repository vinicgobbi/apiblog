<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Cria um administrador do blog (ou redefine a senha de um existente).
 *
 * A senha é sempre pedida de forma interativa e oculta, nunca por opção,
 * para não ficar no histórico do shell. Sem interação (deploy), use as
 * variáveis ADMIN_* com `php artisan db:seed`.
 */
#[Signature('admin:create
    {--name= : Nome exibido do administrador}
    {--email= : E-mail usado no login}')]
#[Description('Cria um administrador do blog ou redefine a senha de um existente')]
class CreateAdmin extends Command
{
    public const int MIN_PASSWORD_LENGTH = 8;

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('A senha é pedida de forma interativa. Sem interação, defina ADMIN_EMAIL e ADMIN_PASSWORD e rode php artisan db:seed.');

            return self::FAILURE;
        }

        $email = $this->option('email') ?? text(
            label: 'E-mail',
            default: (string) config('blog.admin.email'),
            required: 'Informe o e-mail.',
            validate: fn (string $value): ?string => $this->emailError($value),
        );
        $email = mb_strtolower(trim((string) $email));

        if (($error = $this->emailError($email)) !== null) {
            $this->error($error);

            return self::FAILURE;
        }

        $existing = User::query()->where('email', $email)->first();

        if ($existing && ! confirm("Já existe um usuário com o e-mail {$email}. Redefinir o nome e a senha?", default: false)) {
            $this->warn('Nada foi alterado.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?? text(
            label: 'Nome',
            default: $existing->name ?? (string) config('blog.admin.name'),
            required: 'Informe o nome.',
            validate: fn (string $value): ?string => mb_strlen(trim($value)) > 255 ? 'O nome pode ter no máximo 255 caracteres.' : null,
        );
        $name = trim((string) $name);

        if ($name === '' || mb_strlen($name) > 255) {
            $this->error('Informe um nome com até 255 caracteres.');

            return self::FAILURE;
        }

        $password = password(
            label: 'Senha',
            required: 'Informe a senha.',
            validate: fn (string $value): ?string => mb_strlen($value) < self::MIN_PASSWORD_LENGTH
                ? 'A senha precisa ter pelo menos '.self::MIN_PASSWORD_LENGTH.' caracteres.'
                : null,
        );

        if (password(label: 'Confirme a senha', required: 'Confirme a senha.') !== $password) {
            $this->error('As senhas não conferem. Nada foi alterado.');

            return self::FAILURE;
        }

        User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => $password]);

        $this->info($existing ? "Administrador {$email} atualizado." : "Administrador {$email} criado.");

        return self::SUCCESS;
    }

    private function emailError(string $email): ?string
    {
        $validator = Validator::make(['email' => trim($email)], ['email' => 'required|email|max:255']);

        return $validator->fails() ? 'Informe um e-mail válido.' : null;
    }
}
