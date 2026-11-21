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
    | Rotas do front-end
    |--------------------------------------------------------------------------
    |
    | Caminhos das páginas do front, usados para montar links (ex.: no RSS).
    | Devem acompanhar as rotas definidas no blog-front.
    |
    */

    'front_paths' => [
        'post' => '/posts/{slug}',
        'tag' => '/tags/{slug}',
        'category' => '/categorias/{slug}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Slugs reservados para páginas
    |--------------------------------------------------------------------------
    |
    | As páginas estáticas ficam na raiz do front (/{slug}). Estes caminhos
    | são rotas de primeiro nível do blog-front (app.routes.ts) e esconderiam
    | uma página com o mesmo slug. Inclua aqui toda nova rota de primeiro
    | nível criada no front.
    |
    */

    'reserved_page_slugs' => ['admin', 'busca', 'categorias', 'posts', 'tags'],

    /*
    |--------------------------------------------------------------------------
    | Identidade do blog
    |--------------------------------------------------------------------------
    |
    | Título e descrição exibidos nos leitores de RSS.
    |
    */

    'title' => env('BLOG_TITLE', 'Blog'),

    'description' => env('BLOG_DESCRIPTION', ''),

    /*
    |--------------------------------------------------------------------------
    | Feeds RSS
    |--------------------------------------------------------------------------
    |
    | Quantidade de posts por feed e tempo de cache do XML, em segundos. O
    | cache é invalidado sempre que um post, tag ou categoria é alterado.
    |
    */

    'feed' => [
        'items' => 20,
        'cache_ttl' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache HTTP
    |--------------------------------------------------------------------------
    |
    | max-age, em segundos, enviado no Cache-Control das rotas públicas de
    | leitura. Mantenha valores curtos: um post recém-publicado pode levar até
    | esse tempo para aparecer para quem já tinha a resposta em cache.
    |
    */

    'http_cache' => [
        'api' => (int) env('HTTP_CACHE_MAX_AGE', 60),
        'feed' => (int) env('FEED_HTTP_CACHE_MAX_AGE', 300),
    ],

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
