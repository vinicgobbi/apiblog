<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Front-end URL
    |--------------------------------------------------------------------------
    |
    | Endereço público do front do blog. Usado para montar os links dos posts
    | no RSS e como origem liberada no CORS (ver config/cors.php).
    |
    */

    'front_url' => rtrim(env('FRONT_URL', 'http://localhost:4200'), '/'),

    /*
    |--------------------------------------------------------------------------
    | Administrador
    |--------------------------------------------------------------------------
    |
    | Dados do usuário administrador criado pelo DatabaseSeeder. A senha deve
    | vir sempre do ambiente; nunca a deixe fixa no código.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrador'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
