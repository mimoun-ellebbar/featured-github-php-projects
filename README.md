# Featured GitHub PHP Projects

A Symfony 7.4 application demonstrating GitHub API integration, DTO-driven design, and database persistence for PHP repositories.


## Video Setup Guide

**Watch the setup process:** [Setup Video on Streamable](https://streamable.com/0k7rzp)

## Installation

**Prerequisites:** Docker & Docker Compose

**1. Clone the repository:**
```bash
git clone <repository-url>
cd featured_github_php_projects
```

**2. (Optional) Set up GitHub token for higher rate limits (30/min vs 10/min):**
```
# Copy Docker-specific environment template (contains database config, ports, etc.)
```
```bash
cp .env.dev.local.dist .env.dev.local
```
Then you could
```
# Edit .env.dev.local and add: GITHUB_API_TOKEN=your_token_here
```

**3. Build and start Docker containers:**
```bash
docker compose build --no-cache
```
Then
```bash
docker compose up -d
```
Wait 2-3 minutes for the build. You'll see "ready to handle connections" when it's done.


**4. Open your browser:**
```
http://localhost:8080
```

**5. View the code challenge page:**
Click "Code Challenge" in the navigation or go to `http://localhost:8080/github-repositories`

That's it! The application is now running.

## Architecture

### Stack
- **PHP 8.2** (Alpine Linux)
- **Symfony 7.4** (Framework Bundle, Doctrine ORM, HttpClient)
- **MariaDB 10.4**
- **Nginx** (stable-alpine)
- **Tailwind CSS** (via SymfonyCasts bundle)

### Key Patterns
- **Service Layer**: `GitHubApiService` handles all API communication with retry logic
- **Action Layer**: Business logic separated from controllers (`FetchRepositoriesAction`)
- **DTOs**: Type-safe data transfer (`GitHubRepositoryDTO`)
- **Repository Pattern**: Doctrine repositories with custom query methods

## Project Structure

```
src/
├── Actions/              # Business logic layer
├── Command/              # Console commands
├── Controller/           # HTTP request handlers
├── DataTransferObjects/  # Type-safe DTOs
├── Entity/               # Doctrine entities
├── Repository/           # Data access layer
└── Services/             # External integrations (GitHubApiService)
```

## Configuration

All configuration via `.env` (defaults) and `.env.local` (local overrides).

**Key Variables:**
```env
GITHUB_API_TOKEN=             # Optional, increases rate limit
ENV_NGINX_PORT=8080           # Change if port conflict
DATABASE_URL=                 # Auto-configured via compose
```

## Docker Services

| Service | Container | Port |
|---------|-----------|------|
| Nginx | symfony_nginx | 8080 |
| PHP-FPM | symfony_php | 9000 |
| MariaDB | symfony_mariadb | 3306 |
