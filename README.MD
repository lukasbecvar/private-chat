# Private Chat
Private Chat is application designed to ensure fully anonymous and secure communication between users completely under your control.
The project was created in response to the growing number of systems monitoring online communication and user activity on the internet.
This app is intended for users who value their privacy for any reason. Private Chat is a fully self-hosted solution, meaning you run it on your own server and decide who can access it. New accounts can only be created manually by an administrator. You can restrict access, for example, to users connected via a VPN or a private network, allowing you to communicate securely with your family, friends, or colleagues.

# Index
- [Security](#security)
- [Tech stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Contributing](#contributing)
- [Dependencies](#dependencies)
- [License](#license)

# Security
### Encryption
All chat messages are encrypted using AES encryption algorithm.
### Authentication
User authentication using JWT tokens via symfony security and lexik JWT authentication bundle.
### New user registration
New users can only be created only manually by administrator.
### Access
How the application will be accessible is up to you—whether you open it to the internet or use a VPN or other security measures.

# Tech stack
### Frontend
The frontend part is built on React as a web application using Tailwind CSS for design.
### Backend
The backend is built on Symfony as REST API.

# Requirements
### Software
* PHP 8.4 and compatible web server
* MySQL 8 or higher
* Composer
* NPM
### PHP extensions
* PHP-PDO extension (db driver)
* PHP-Intl extension

# Installation
### Development
For development setup only start script ``docker-start-dev.sh``
### Production
* bare-metal
    Setup PHP and compatible web server
    Setup MySQL
    Setup Redis
    Setup JWT keys
    Create database with command ``php bin/console doctrine:database:create``
    Migrate database with command ``php bin/console doctrine:migrations:migrate``
* docker
    For docker deployment use this example [docker-compose.yml](.github/assets/docker-compose.example.yml) and this command for setup:
    ```bash
        docker-compose run php bash -c "
            php bin/console doctrine:database:create --if-not-exists &&
            php bin/console doctrine:database:create --if-not-exists --env=test &&
            php bin/console doctrine:migrations:migrate --no-interaction &&
            php bin/console doctrine:migrations:migrate --no-interaction --env=test &&
            php bin/console doctrine:fixtures:load --no-interaction &&
            php bin/console doctrine:fixtures:load --no-interaction --env=test
        "
        docker-compose run php ./bin/console lexik:jwt:generate-keypair
    ```

# Configuration
### Environment variables
The environment variables are loaded from .env file. You can create .env file in root directory with your configuration.
| Variable | Description | Example value |
| --- | --- | --- |
| APP_SECRET | Session & token encryption key | 369af56dccfce490cb9325e8b4b59a90 |
| API_TOKEN | API access token for authentication | 1234 |
| APP_VERSION | App version identifier | 1.0 |
| TRUSTED_HOSTS | Trusted domain names (^.*$ = allow all) | ^.*$ |
| ALLOWED_ORIGINS | Allowed origins (use * for all origins) | * |
| ALLOWED_IP_ADDRESSES | Allowed ip addresses (use % for all ip-addresses) | % |
| SSL_ONLY | Enable only SSL traffic (true/false) | false |
| MAINTENANCE_MODE | Enable maintenance mode (true/false) | false |
| LIMIT_CONTENT_PER_PAGE | Pagination config (int value) | 10 |
| DATABASE_LOGGING | Log manager config | true |
| LOG_LEVEL | Log level (1: CRITICAL, 2: WARNING, 3: NOTICE, 4: INFO) | 4 |
| RATE_LIMIT_ENABLED | Rate limit config | false |
| RATE_LIMIT_INTERVAL | Rate limit interval (seconds) | 60 |
| RATE_LIMIT_LIMIT | Rate limit limit | 100 |
| DATABASE_DRIVER | Database driver | pdo_mysql |
| DATABASE_VERSION | Database version | 8.0.43 |
| DATABASE_HOST | Database host | 127.0.0.1 |
| DATABASE_PORT | Database port | 3306 |
| DATABASE_NAME | Database name | private_chat |
| DATABASE_USERNAME | Database username | root |
| DATABASE_PASSWORD | Database password | root |
| REDIS_SCHEME | Redis scheme | redis |
| REDIS_HOST | Redis host | 127.0.0.1 |
| REDIS_PORT | Redis port | 6379 |
| REDIS_USER | Redis user | default |
| REDIS_PASSWORD | Redis password | redis_test_password |
| JWT_TOKEN_TTL | JWT token ttl | 2629536 |
| JWT_SECRET_KEY | JWT secret key | %kernel.project_dir%/config/jwt/private.pem |
| JWT_PUBLIC_KEY | JWT public key | %kernel.project_dir%/config/jwt/public.pem |
| JWT_PASSPHRASE | JWT passphrase | f82fdd5f4644df4ba8fe9d600c5026abc7d7e97cb4413607073d9fc03aa85ba4 |
### Frontend configuration
| Variable | Description | Example value |
| --- | --- | --- |
| DEV_MODE | Enable development mode (true/false) | true |
| CHAT_LIMIT | Chat limit | 20 |
| CHAT_POLLING_TIME | Chat polling time (ms) | 1000 |
| API_TIMEOUT_SEC | API timeout (sec) | 5 |
| API_TOKEN | API access token | 1234 |
| API_URL | API url | http://localhost:1337 |

# Contributing
Contributions are welcome! Please fork this repository and submit pull requests for any features, bug fixes, or enhancements. If you have any questions or need help, feel free to open an issue or contact me directly.

# Dependencies
### Backend
* PHP
    * [Website](https://php.net)
* Symfony
    * [Website](https://symfony.com)
* PHPUnit
    * [Github](https://github.com/sebastianbergmann/phpunit)
* Better PHPUnit CLI output
    * [Github](https://github.com/robiningelbrecht/phpunit-pretty-print)
* PHPStan
    * [Github](https://github.com/phpstan/phpstan)
### Frontend
* NPM
    * [NPM](https://docs.npmjs.com)
    * [Encore](https://symfony.com/doc/current/frontend/encore/index.html)
* React
    * [Website](https://reactjs.org)
* Tailwind
    * [Website](https://tailwindcss.com)

# License
This software is licensed under the [MIT license](LICENSE). 

# ToDo-List
- [ ] API version check (check if backend version is compatible with frontend version)
- [ ] Global chat for all users
- [ ] hyperlink handling support
- [ ] Ban system (non active status)
    - [ ] Command for ban/unban user
    - [ ] Show banned status in user list and chat list
- [ ] User online/offline status (show online status in chat list)
- [ ] Account settings
    - [ ] Change profile picture (only for self)
    - [ ] Change username (only for self)
    - [ ] Change password (only for self)
- [ ] User info view
    - [ ] Show user info in chat component on right side after clicking on ? button
        - [ ] User info
            - [ ] Profile picture
            - [ ] Username
            - [ ] Role
            - [ ] Registered time
            - [ ] Last login time
- [ ] File sharing
    - [ ] Image viewer
    - [ ] Video player
    - [ ] Audo player
    - [ ] Shared file downloading
- [ ] Admin system
    - [ ] Dashboard
        - [ ] Count of users (online/offline)
        - [ ] Count of chats
        - [ ] Count of messages
    - [ ] Log manager
        - [ ] Log list
        - [ ] Mark log as read
        - [ ] Delete log
    - [ ] User management
        - [ ] User list
            - [ ] Username
            - [ ] Role
            - [ ] Registered time
            - [ ] Last login time
        - [ ] Edit username
        - [ ] Edit user role
        - [ ] Ban user
        - [ ] Delete user
        - [ ] New user registration
