# Docker Setup for Symfony 7.4 Application

This Symfony 7.4 application is fully dockerized with MariaDB 10.4 and Nginx stable alpine.

## Stack Components

- **PHP 8.2-FPM Alpine** - With all required Symfony extensions
- **MariaDB 10.4** - Database server
- **Nginx Stable Alpine** - Web server
- **Mailpit** - Email testing tool (optional, currently disabled)

## PHP Extensions Installed

- PDO & PDO MySQL
- MySQLi
- Intl
- Zip
- OPcache
- GD (with FreeType and JPEG support)
- BCMath
- Exif
- PCNTL
- Sockets
- APCu (caching)
- Xdebug (development only)

## Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+

## Quick Start

### 1. Set Up Local Environment

First, create your local environment file:

```bash
# Copy the template file
cp .env.dev.local.dist .env.dev.local

# (Optional) Edit .env.dev.local to customize settings
# For example: change database credentials, ports, or enable Xdebug
```

**Important:** The `.env.dev.local` file is NOT committed to git and contains your local Docker configuration.

### 2. Build and Start Containers

```bash
# Build and start all containers in detached mode
docker compose up -d --build
```

### 3. Install PHP Dependencies

```bash
# Install Composer dependencies
docker compose exec php composer install
```

### 4. Set Up Database

```bash
# Create the database (if not exists)
docker compose exec php php bin/console doctrine:database:create --if-not-exists

# Run migrations
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# (Optional) Load fixtures
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

### 5. Clear Cache

```bash
# Clear Symfony cache
docker compose exec php php bin/console cache:clear
```

### 6. Access the Application

- **Web Application**: http://localhost:8080
- **Database**: localhost:3306

## Environment Variables

Symfony uses an environment file hierarchy:

- **[`.env`](.env)** - Default values (committed to git)
- **`.env.local`** - Local overrides for all environments (NOT committed)
- **[`.env.dev.local`](.env.dev.local)** - Development environment overrides (NOT committed)
- **[`.env.dev.local.dist`](.env.dev.local.dist)** - Template for local development (committed to git)

### Docker Configuration

All Docker-specific settings are in **`.env.dev.local`** (not committed to git):

```env
# Application
APP_ENV=dev
APP_SECRET=your_random_secret_key

# Docker MariaDB connection
DATABASE_URL="mysql://symfony:symfony@database:3306/symfony_app?serverVersion=10.4-MariaDB&charset=utf8mb4"

# Database credentials (for docker-compose)
DB_ROOT_PASSWORD=root
DB_DATABASE=symfony_app
DB_USER=symfony
DB_PASSWORD=symfony

# Docker settings
NGINX_PORT=8080
XDEBUG_MODE=off
```

### First Time Setup

Copy the template to create your local configuration:

```bash
cp .env.dev.local.dist .env.dev.local
```

Then customize values in `.env.dev.local` as needed. This file is ignored by git, so your local settings won't be committed.

## Docker Commands

### Container Management

```bash
# Start containers
docker compose up -d

# Stop containers
docker compose down

# Restart containers
docker compose restart

# View logs
docker compose logs -f

# View logs for specific service
docker compose logs -f php
docker compose logs -f nginx
docker compose logs -f database
```

### Access Container Shells

```bash
# Access PHP container
docker compose exec php sh

# Access Nginx container
docker compose exec nginx sh

# Access MariaDB container
docker compose exec database sh
```

### Database Operations

```bash
# Access MySQL CLI
docker compose exec database mysql -u symfony -p
# Password: symfony

# Access MySQL as root
docker compose exec database mysql -u root -p
# Password: root

# Backup database
docker compose exec database mysqldump -u root -proot symfony_app > backup.sql

# Restore database
docker compose exec -T database mysql -u root -proot symfony_app < backup.sql
```

### Symfony Console Commands

```bash
# Run any Symfony console command
docker compose exec php php bin/console [command]

# Examples:
docker compose exec php php bin/console debug:router
docker compose exec php php bin/console cache:clear
docker compose exec php php bin/console doctrine:migrations:status
docker compose exec php php bin/console make:entity
```

### Composer Commands

```bash
# Install dependencies
docker compose exec php composer install

# Update dependencies
docker compose exec php composer update

# Require new package
docker compose exec php composer require [package-name]

# Remove package
docker compose exec php composer remove [package-name]
```

## Development vs Production

### Development (Default)

The default setup uses `Dockerfile.dev` which includes:
- Xdebug for debugging
- Development PHP settings (error display, higher limits)
- OPcache with file validation enabled
- Symfony CLI
- All dev dependencies

### Production

For production, modify `compose.yaml` to use `Dockerfile` instead:

```yaml
php:
  build:
    dockerfile: Dockerfile  # Change from Dockerfile.dev
```

Production Dockerfile includes:
- No Xdebug
- Production-optimized PHP settings
- OPcache without file validation
- Smaller image size
- No dev dependencies

## Xdebug Configuration

Xdebug is installed in development mode but disabled by default.

### Enable Xdebug

```bash
# Set XDEBUG_MODE in .env
XDEBUG_MODE=debug,develop,coverage

# Or set when starting containers
XDEBUG_MODE=debug docker compose up -d

# Or enable temporarily
docker compose exec -e XDEBUG_MODE=debug php php [your-script.php]
```

### IDE Configuration

Xdebug is configured to connect to `host.docker.internal:9003`

**VS Code** - Add to `.vscode/launch.json`:

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/var/www/html": "${workspaceFolder}"
      }
    }
  ]
}
```

**PhpStorm** - Configure under Settings → PHP → Debug:
- Port: 9003
- Path mappings: `/var/www/html` → `[your-project-path]`

## Mailpit Email Testing (Optional)

Mailpit is currently disabled. To enable email testing in development:

### Enable Mailpit

1. Uncomment the mailer service in `compose.override.yaml`:

```yaml
###> symfony/mailer ###
  mailer:
    image: axllent/mailpit
    ports:
      - "1025"
      - "8025"
    environment:
      MP_SMTP_AUTH_ACCEPT_ANY: 1
      MP_SMTP_AUTH_ALLOW_INSECURE: 1
###< symfony/mailer ###
```

2. Update your `.env` file to use Mailpit:

```env
MAILER_DSN=smtp://mailer:1025
```

3. Restart containers:

```bash
docker compose down
docker compose up -d
```

4. Access Mailpit UI at http://localhost:8025 to view test emails

## Performance Optimization

### Cached Volumes

The setup uses `:cached` flag for volume mounts on macOS/Windows to improve performance:

```yaml
volumes:
  - ./:/var/www/html:cached
```

### Vendor Volume

A separate volume is used for `vendor/` directory to improve performance:

```yaml
volumes:
  - php_vendor:/var/www/html/vendor
```

After composer install, you may need to sync vendor folder:

```bash
docker compose exec php composer install
```

## Troubleshooting

### Permission Issues

```bash
# Fix permissions on var directory
docker compose exec php chown -R www-data:www-data var
docker compose exec php chmod -R 775 var
```

### Database Connection Issues

```bash
# Check database is running and healthy
docker compose ps

# Test database connection
docker compose exec php php bin/console dbal:run-sql "SELECT 1"

# Check database logs
docker compose logs database
```

### Nginx 502 Bad Gateway

```bash
# Check PHP-FPM is running
docker compose ps php

# Check PHP-FPM logs
docker compose logs php

# Restart PHP container
docker compose restart php
```

### Clear Everything and Start Fresh

```bash
# Stop and remove all containers, networks, and volumes
docker compose down -v

# Remove images
docker compose down --rmi all

# Rebuild from scratch
docker compose up -d --build --force-recreate
```

## File Structure

```
.
├── docker/
│   └── nginx/
│       └── default.conf       # Nginx configuration for Symfony
├── Dockerfile                 # Production PHP-FPM image
├── Dockerfile.dev             # Development PHP-FPM image with Xdebug
├── compose.yaml               # Main Docker Compose configuration
├── compose.override.yaml      # Development overrides (ports, mailpit)
├── .dockerignore              # Files to exclude from Docker build
├── .env                       # Default environment variables (committed)
├── .env.dev.local.dist        # Template for local dev config (committed)
├── .env.dev.local             # Your local dev config (NOT committed)
└── DOCKER_README.md           # This file
```

## Ports

| Service  | Internal Port | External Port (Host)        |
|----------|---------------|-----------------------------|
| Nginx    | 80            | 8080 (configurable)         |
| PHP-FPM  | 9000          | Not exposed                 |
| MariaDB  | 3306          | 3306 (dev only)             |
| Mailpit  | 8025 (UI)     | 8025 (disabled, optional)   |
| Mailpit  | 1025 (SMTP)   | 1025 (disabled, optional)   |

## Security Notes

- **Change default passwords** in `.env` for production
- **Generate a strong APP_SECRET** using: `php -r "echo bin2hex(random_bytes(16));"`
- Database is not exposed to host in production (remove port mapping)
- Consider using Docker secrets for sensitive data in production

## Additional Resources

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [MariaDB Documentation](https://mariadb.com/kb/en/documentation/)
- [Nginx Documentation](https://nginx.org/en/docs/)
