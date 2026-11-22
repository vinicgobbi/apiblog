## v0.4.1 (2026-11-22)

### Fix

- **posts**: converte published_at para o fuso da aplicação

## v0.4.0 (2026-11-22)

### Feat

- **media**: aceita imagens SVG no upload

## v0.3.1 (2026-11-22)

### Fix

- **media**: aumenta limites de upload e de memória para imagens

## v0.3.0 (2026-11-22)

### Feat

- **pages**: adiciona ordenação das páginas no menu

## v0.2.0 (2026-11-21)

### Feat

- **pages**: adiciona listagem pública das páginas publicadas
- **pages**: adiciona ícone obrigatório às páginas

## v0.1.1 (2026-11-21)

### Fix

- **deploy**: mantém resources/views para o view:cache do servidor

## v0.1.0 (2026-11-21)

### Feat

- **console**: adiciona comando admin:create para criar o administrador
- **pages**: bloqueia slugs de página que colidem com rotas do front
- **media**: remove metadados EXIF das imagens enviadas
- **http**: envia Cache-Control público com ETag nos endpoints de leitura
- **comments**: adiciona moderação de comentários
- **comments**: adiciona envio e listagem pública de comentários
- **media**: adiciona upload e gestão de imagens
- **admin**: adiciona crud administrativo de posts, categorias, tags e páginas
- **rss**: adiciona feeds rss geral, por tag e por categoria
- **api**: adiciona endpoints públicos de posts, categorias, tags e páginas
- **auth**: implementa autenticação jwt do admin
- **markdown**: adiciona comando posts:rerender
- **markdown**: renderiza html e tempo de leitura ao salvar posts e páginas
- **markdown**: adiciona renderizador de markdown com escape de html
- **db**: adiciona seeder do admin e de conteúdo de demonstração
- **models**: adiciona models, enums, factories e geração de slug
- **db**: adiciona migrations do blog
- **cors**: libera apenas a origem do front no cors

### Fix

- **posts**: considera publicado o post com data igual ao instante atual
- **swagger**: importa Controller base em controllers de subnamespace no make:swagger

### Refactor

- **posts**: move a busca de posts para o scope Post::search
- **swagger**: gera documentação com OpenApi\Spec no make:swagger
- **docs**: migra documentação swagger para atributos OpenApi\Spec

### Perf

- **http**: envia cache público no GET /api
