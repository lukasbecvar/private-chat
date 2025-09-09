# Symfony API Base
This is a base application for a REST API in the Symfony framework that I created to speed up backend development.

## Index
* [Features](#features)
* [Getting Started](#getting-started)
* [API Documentation](#api-documentation)
* [Authentication](#authentication)
* [Error handling](#error-handling)
* [Logging system](#logging-system)
* [User system](#user-system)
* [CLI commands](#cli-commands)
* [Environment configuration variables](#environment-configuration-variables)
* [Request examples](#request-examples)
* [Dependencies & requirements](#dependencies--requirements)
* [License](#license)

## Features
The base contains essential utilities and functions for validation, logging, error handling, and the user system.

## Getting Started
1. Clone the repository:
```bash
git clone https://github.com/your-username/api-base.git
cd api-base
```
3. Build and run containers:
This command will build the Docker images and start all services (PHP/Apache, MySQL, Redis).
```bash
sh scripts/docker-start-dev.sh
```
4. Install dependencies:
Use Composer inside the PHP container to install all required packages (this makes docker-start automatically).
```bash
sh scripts/install.sh
```
5. Run database migrations:
This will create the database schema based on your entities.
```bash
sh scripts/migrate.sh
```
6. Generate JWT keys:
Create the public and private keys required for signing JWT tokens (this makes docker-start automatically).
```bash
docker-compose exec php php bin/console lexik:jwt:generate-keypair
```
7. The API is now running at `http://localhost`
8. Develop your API
Now you can rename ```api-base``` to whatever you need, rewrite the configurations, and start developing your API solution.

## API-Documentation
##### Nelmio UI
The documentation for API endpoints can be found at the route `/api/doc`, with Nelmio UI complete documentation and also allows testing requests directly in the web browser. Alternatively, you can find documentation in JSON format at `/api/doc.json` (You can use this JSON response to import the configuration into Postman).

## Authentication 
##### API-Token
All API requests must have static X-API-Token header set, which is used for validating the request. The token is set in the .env file.
##### JWT
JWT token is used for user authentication.

## Error handling
Error handling is managed by the ```handleError``` function in the ```ErrorManager class```, which triggers an exception that is listened to by the ```ExceptionEventSubscriber```. The subscriber logs the exception into the exception log and displays an error response for the user.

## Logging system
For logging, there is the ```LogManager class```, which contains functions for saving and reading logs from the database through the Log entity.

## User system
The user system is managed by the ```UserManager class```, and login works using a JWT token in the authorization header, thanks to Symfony Security and Lexik JWT.

## CLI commands
The application has CLI commands for the LogManager and UserManager, and overall system management through the CLI.

## Environment configuration variables
| Variable | Description | Example value |
| --- | --- | --- |
| `APP_ENV` | Specific environment name | `dev` |
| `APP_SECRET` | Session & token encryption key | `369af56dccfce490cb9325e8b4b59a90` |
| `API_TOKEN` | API access token for authentication | `1234` |
| `APP_VERSION` | App version identifier | `1.0` |
| `TRUSTED_HOSTS` | Trusted domain names | `^.*$` |
| `ALLOWED_IP_ADDRESSES` | Allowed ip addresses (use % for all IP addresses) | `%` |
| `SSL_ONLY` | Enable only SSL traffic (true/false) | `false` |
| `MAINTENANCE_MODE` | Enable maintenance mode (true/false) | `false` |
| `LIMIT_CONTENT_PER_PAGE` | Pagination config (int value) | `10` |
| `DATABASE_LOGGING` | Log manager config | `true` |
| `LOG_LEVEL` | Log level (1: CRITICAL, 2: WARNING, 3: NOTICE, 4: INFO) | `4` |
| `RATE_LIMIT_ENABLED` | Enable rate limit (true/false) | `false` |
| `RATE_LIMIT_INTERVAL` | Rate limit interval (seconds) | `60` |
| `RATE_LIMIT_LIMIT` | Rate limit limit | `100` |
| `DATABASE_DRIVER` | Database driver | `pdo_mysql` |
| `DATABASE_VERSION` | Database version | `8.0.43` |
| `DATABASE_HOST` | Database host | `127.0.0.1` |
| `DATABASE_PORT` | Database port | `3306` |
| `DATABASE_NAME` | Database name | `product_vault` |
| `DATABASE_USERNAME` | Database username | `root` |
| `DATABASE_PASSWORD` | Database password | `root` |
| `REDIS_SCHEME` | Redis scheme | `redis` |
| `REDIS_HOST` | Redis host | `127.0.0.1` |
| `REDIS_PORT` | Redis port | `6379` |
| `REDIS_USER` | Redis user | `default` |
| `REDIS_PASSWORD` | Redis password | `redis_test_password` |
| `JWT_TOKEN_TTL` | JWT token TTL (in seconds) | `2629536` (1 month token expiration) |
| `JWT_SECRET_KEY` | JWT secret key | `%kernel.project_dir%/config/jwt/private.pem` |
| `JWT_PUBLIC_KEY` | JWT public key | `%kernel.project_dir%/config/jwt/public.pem` |
| `JWT_PASSPHRASE` | JWT passphrase | `f82fdd5f4644df4ba8fe9df82fdd5f4644df4ba8fe9d` |

## Request examples
All requests accept input data in JSON format and return JSON data back to the client.
##### register request
```
curl -X POST http://localhost/api/auth/register \
    -H "Content-Type: application/json" \
    -H "X-API-TOKEN: 1234" \
    -d '{
        "email": "test@example.com",
        "first-name": "John",
        "last-name": "Doe",
        "password": "securePassword123"
    }'
```
##### login request
```
curl -X POST http://localhost/api/auth/login \
    -H "Content-Type: application/json" \
    -H "X-API-TOKEN: 1234" \
    -d '{
        "email": "test@test.test",
        "password": "test"
    }'
```
##### logout request
```
curl -X POST http://localhost/api/auth/logout \
    -H "Content-Type: application/json" \
    -H "X-API-TOKEN: 1234" \
    -H "Authorization: Bearer <token>"
```
##### user info request
```
curl -X GET http://localhost/api/user/info \
    -H "Content-Type: application/json" \
    -H "X-API-TOKEN: 1234" \
    -H "Authorization: Bearer <token>"
```
##### update user password request
```
curl -X PATCH http://localhost/api/user/data/update/password \
    -H "Content-Type: application/json" \
    -H "X-API-TOKEN: 1234" \
    -H "Authorization: Bearer <token>" \
    -d '{
        "new-password": "asdfghjkoiuzrewq"
    }'
```
##### update user role request
```
curl -X PATCH http://localhost/api/user/data/update/role \
    -H "Content-Type: application/json" \
    -H "X-API-TOKEN: 1234" \
    -H "Authorization: Bearer <token>" \
    -d '{
        "user-id": 1,
        "task": "add",
        "role": "ROLE_TEST"
    }'
```
##### update user status request
```
curl -X PATCH http://localhost/api/user/data/update/status \
    -H "Content-Type: application/json" \
    -H "X-API-TOKEN: 1234" \
    -H "Authorization: Bearer <token>" \
    -d '{
        "user-id": 2,
        "status": "idk"
    }'
```
##### delete user request
```
curl -X PATCH http://localhost/api/user/delete \
    -H "Content-Type: application/json" \
    -H "X-API-TOKEN: 1234" \
    -H "Authorization: Bearer <token>" \
    -d '{
        "user-id": 3
    }'
```
##### get users list request
```
curl -X GET http://localhost/api/user/list \
    -H "Content-Type: application/json" \
    -H "X-API-TOKEN: 1234" \
    -H "Authorization: Bearer <token>"
```

## Dependencies & requirements
* PHP
    * [Website](https://php.net)
* Redis
    * [Website](https://redis.io)
* MySQL
    * [Website](https://www.mysql.com)
* Symfony framework
    * [Website](https://symfony.com)
* Doctrine ORM
    * [Github](https://github.com/doctrine/orm)
* Lexik JWT Authentication Bundle
    * [Github](https://github.com/lexik/LexikJWTAuthenticationBundle)
* PHPUnit
    * [Github](https://github.com/sebastianbergmann/phpunit)
* Better PHPUnit CLI output
    * [Github](https://github.com/robiningelbrecht/phpunit-pretty-print)
* PHPStan
    * [Github](https://github.com/phpstan/phpstan)

## License
This software is licensed under the [MIT license](LICENSE).
