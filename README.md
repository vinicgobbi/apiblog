# Template de API (Back-End)

Este é um template genérico de API REST desenvolvido em Laravel. O projeto serve como base (boilerplate) para a criação de novos projetos, já contando com as configurações iniciais necessárias para utilização de autenticação via JWT e documentação com Swagger.

## Tecnologias

- **PHP:** 8.4
- **Laravel:** 13.0
- **JWT (JSON Web Token):** Configurado para implementações futuras de autenticação stateless.
- **Swagger:** Configurado para geração de documentação interativa da API.

## Como utilizar

### Pré-requisitos

Para rodar o projeto será necessário ter instalado:

- PHP: 8.4 ou Superior
- Composer: 2.8 ou Superior
- Banco de dados de sua preferência (ex: SQL Server)

### Instalação e Configuração

Clone o repositório na sua máquina e acesse a pasta do projeto:

```sh
git clone <URL_DO_SEU_REPOSITORIO>
cd <NOME_DA_PASTA_DO_PROJETO>

```

Para instalar e configurar o projeto de uma só vez, basta rodar o comando:

```sh
composer setup

```

Após isso, edite as variáveis de ambiente específicas do seu projeto no arquivo `.env` gerado. O template base seguirá o padrão abaixo:

```env
##############################
#  Configurações do Sistema  #
##############################
APP_NAME="Template API - FAESA"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database
# PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12

##############################
#  Configurações de Região   #
##############################
APP_TIMEZONE=America/Sao_Paulo
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR

##############################
#   Configurações de Logs    #
##############################
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

##############################
# Configurações de Banco     #
##############################
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=example
DB_USERNAME=example
DB_PASSWORD=example
DB_SECONDARY_TRUST_SERVER_CERTIFICATE=true

##############################

DB_SECONDARY_CONNECTION=sqlsrv
DB_SECONDARY_HOST=127.0.0.1
DB_SECONDARY_PORT=1433
DB_SECONDARY_DATABASE=example
DB_SECONDARY_USERNAME=example
DB_SECONDARY_PASSWORD=example
DB_SECONDARY_TRUST_SERVER_CERTIFICATE=true


##############################
#  Serviços, Filas e Discos  #
##############################
# BROADCAST_CONNECTION=log
# FILESYSTEM_DISK=local
# QUEUE_CONNECTION=database

##############################
#  Configurações de Cache    #
##############################
# CACHE_STORE=database
# CACHE_PREFIX=
# MEMCACHED_HOST=127.0.0.1

##############################
#  Configurações do Redis    #
##############################
# REDIS_CLIENT=phpredis
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=6379

##############################
#  Configurações de E-mail   #
##############################
# MAIL_MAILER=log
# MAIL_SCHEME=null
# MAIL_HOST=127.0.0.1
# MAIL_PORT=2525
# MAIL_USERNAME=null
# MAIL_PASSWORD=null
# MAIL_FROM_ADDRESS="hello@example.com"
# MAIL_FROM_NAME="${APP_NAME}"

##############################
#    Autenticação JWT        #
##############################
JWT_SECRET=
JWT_TTL=120
JWT_REFRESH_TTL=43800

##############################
#   Documentação Swagger     #
##############################
L5_SWAGGER_GENERATE_ALWAYS=true

```

> **Timezone:** o fuso horário da aplicação é definido pela variável `APP_TIMEZONE` (ex: `America/Sao_Paulo`). Caso não seja informada, será utilizado `UTC`. A lista de valores aceitos pode ser consultada na [documentação do PHP](https://www.php.net/manual/pt_BR/timezones.php).

### Executando o projeto

Para iniciar o servidor de desenvolvimento do Laravel, execute o comando:

```sh
php artisan serve

```

O projeto estará rodando por padrão em `http://localhost:8000`.

**Acessando a documentação:**
Com o servidor rodando, a interface interativa do Swagger poderá ser acessada em:
`http://localhost:8000/api/documentation`
