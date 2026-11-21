# Blog API

API REST do blog, feita em Laravel. Ela fornece os posts, as páginas, as categorias, as tags e os comentários para o front ([blog-front](https://github.com/vinicgobbi/blog-front)), além dos feeds RSS e dos endpoints da área administrativa.

## Tecnologias

- **PHP** 8.4+
- **Laravel** 13
- **MySQL** 8+ ou **MariaDB** 10.6+
- **JWT** (`tymon/jwt-auth`) para autenticar o admin
- **Swagger** (`darkaonline/l5-swagger`) para a documentação interativa
- **Markdown** renderizado com `league/commonmark`, que já vem com o Laravel

## Pré-requisitos

- PHP 8.4 ou superior, com as extensões `pdo_mysql`, `mbstring`, `fileinfo` e `gd`
- Composer 2.8 ou superior
- MySQL ou MariaDB

Se você ainda não tem o banco rodando, dá para subir um MariaDB em container (Docker ou Podman):

```sh
podman run -d --name blog-db \
  -e MARIADB_DATABASE=blog \
  -e MARIADB_USER=blog \
  -e MARIADB_PASSWORD=<sua-senha> \
  -e MARIADB_RANDOM_ROOT_PASSWORD=1 \
  -p 3306:3306 \
  docker.io/library/mariadb:11
```

## Instalação

```sh
git clone https://github.com/vinicgobbi/blog-api.git
cd blog-api
composer setup
```

O `composer setup` faz quatro coisas: instala as dependências, cria o `.env` a partir do `.env.example`, gera o `APP_KEY` e o `JWT_SECRET` e cria o link `public/storage`, por onde as imagens enviadas são servidas.

Depois, edite o `.env` (principalmente banco e admin) e rode as migrations com o seeder:

```sh
php artisan migrate --seed
```

## Variáveis de ambiente do blog

| Variável | Descrição |
|---|---|
| `DB_CONNECTION` | `mysql` ou `mariadb` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Conexão com o banco |
| `FRONT_URL` | URL pública do front. É a origem liberada no CORS e a base dos links do RSS |
| `BLOG_TITLE`, `BLOG_DESCRIPTION` | Título e descrição do blog exibidos nos leitores de RSS |
| `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Dados do administrador criado pelo seeder |
| `JWT_TTL`, `JWT_REFRESH_TTL` | Validade do token e do refresh, em minutos |
| `L5_SWAGGER_GENERATE_ALWAYS` | Regera a documentação Swagger a cada requisição (use `true` só em desenvolvimento) |

Em produção, use o `.env.prod.example` como base: `composer setup-prod`.

> Nunca versione o `.env`. A senha do admin e as chaves devem existir apenas no ambiente.

## Executando

```sh
composer dev
```

A API fica disponível em `http://localhost:8000/api` e a documentação Swagger em `http://localhost:8000/api/documentation`.

### Feeds RSS

| URL | Conteúdo |
|---|---|
| `/feed` | Últimos 20 posts publicados |
| `/feed/tags/{slug}` | Posts de uma tag |
| `/feed/categories/{slug}` | Posts de uma categoria |

Os feeds ficam em cache por até 1 hora. O cache é invalidado automaticamente quando um post, tag ou categoria é alterado (e pelo `php artisan posts:rerender`).

### Cache HTTP

Os `GET` públicos (`/api/posts`, `/api/categories`, `/api/tags`, `/api/pages` e os comentários de um post) respondem com `Cache-Control: public, max-age=60` e `ETag`; uma requisição com `If-None-Match` igual recebe `304`. Os feeds usam `max-age=300`. Ajuste os tempos com `HTTP_CACHE_MAX_AGE` e `FEED_HTTP_CACHE_MAX_AGE`. Mantenha valores curtos: um post recém-publicado pode levar esse tempo para aparecer para quem já tinha a resposta em cache.

As rotas `/api/admin/*` e `/api/auth/*` nunca são cacheáveis (`no-store, private`).

## Testes e qualidade

Os testes usam SQLite em memória e ignoram o `.env`. Toda a configuração deles fica no `phpunit.xml`.

```sh
php artisan test     # suíte de testes
vendor/bin/pint      # formatação de código
```

## Roadmap

O desenvolvimento é organizado por [issues e marcos](https://github.com/vinicgobbi/blog-api/milestones):

- **M1 – Fundação:** configuração, modelagem do banco, Markdown e autenticação
- **M2 – Blog público:** endpoints públicos, comentários e RSS
- **M3 – Administração:** CRUD de conteúdo, upload de mídia e moderação de comentários
