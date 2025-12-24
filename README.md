# Featured GitHub PHP Projects

A Symfony web application that displays the most-starred PHP projects from GitHub with filtering, pagination, and database persistence.

## Overview

This application integrates with the GitHub API to fetch and display popular PHP repositories. Users can browse projects, filter by name and star count, view detailed information, and refresh the database with the latest data from GitHub.

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- [Docker Compose](https://docs.docker.com/compose/) (included with Docker Desktop)
- GitHub Personal Access Token ([Create one here](https://github.com/settings/tokens))

## Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/featured_github_php_projects.git
cd featured_github_php_projects
```

### 2. Configure Environment

Create a local environment file:

```bash
cp .env .env.local
```

Edit `.env.local` and set your GitHub API token:

```env
GITHUB_API_TOKEN=your_github_personal_access_token_here
```

**How to get a GitHub token:**
1. Go to [GitHub Settings > Developer settings > Personal access tokens](https://github.com/settings/tokens)
2. Click "Generate new token (classic)"
3. Give it a name (e.g., "Featured PHP Projects")
4. No special scopes needed for public repositories
5. Click "Generate token" and copy the token

### 3. Start the Application

```bash
docker compose up --build
```

Wait for the containers to start (first build may take 2-3 minutes).

### 4. Access the Application

Open your browser and navigate to:

```
http://localhost:8080
```

## Features

- **Browse Repositories**: View the most-starred PHP projects from GitHub
- **Real-time Data**: Fetches data directly from GitHub API
- **Filtering**:
  - Search by repository name
  - Filter by minimum star count
  - Adjustable results per page (10, 30, 60, 100)
- **Pagination**: Navigate through results efficiently
- **Repository Details**: Click any repository to view:
  - Full description
  - Star count
  - Creation date
  - Last push date
  - Direct link to GitHub
- **Database Refresh**: Persist GitHub data to local database with background job processing
- **Responsive Design**: Mobile and desktop friendly interface

## Usage

### Browsing Repositories

1. The main page displays PHP repositories sorted by stars
2. Use the left sidebar to apply filters:
   - **Search by name**: Type repository name
   - **Min stars**: Set minimum star threshold
   - **Items per page**: Choose page size
3. Click "Apply Filters" to update results

### Viewing Repository Details

- Click on any repository row or card to open a detailed modal
- View complete information including description and important dates
- Click "View on GitHub" to visit the repository

### Refreshing Database

- Click the "Refresh Database" button in the top-right corner
- The system will fetch latest data from GitHub in the background
- Refresh the page after a few moments to see updated data

## Technology Stack

### Backend
- **PHP 8.2** with Alpine Linux
- **Symfony 7.4** framework
- **Doctrine ORM** for database management
- **Symfony Messenger** for async job processing
- **Symfony HttpClient** with retry logic

### Frontend
- **Twig** templating engine
- **Tailwind CSS** for styling
- **Vanilla JavaScript** for interactivity

### Infrastructure
- **MariaDB 10.4** database
- **Nginx** web server
- **Supervisor** for background worker management
- **Docker Compose** for orchestration

## Architecture Highlights

### Background Job Processing

This application uses **Symfony Messenger** with **Supervisor** to handle GitHub API refresh operations asynchronously. While not strictly required for this scope, this demonstrates:

- Scalable architecture for production environments
- Non-blocking user experience during data refresh
- Proper error handling and retry logic (3 retries with exponential backoff)
- Process management best practices with supervisor

The application runs 2 worker processes that handle repository data fetching and database updates in the background.

### Database Schema

The application stores repositories with the following fields:
- Repository ID (from GitHub)
- Name
- URL
- Stars count
- Description
- Created date
- Last pushed date
- Updated timestamp

Indexes are optimized for filtering and sorting by star count.

### Caching Strategy

- API results are cached for 1 hour to minimize GitHub API calls
- Filter combinations use SHA256 hashing for efficient cache key generation
- Paginated results are cached separately for performance

## Configuration

### Environment Variables

Key configuration options in `.env.local`:

```env
# Application
APP_ENV=dev
APP_SECRET=change_me_to_random_secret

# Database
DB_DATABASE=symfony_app
DB_USER=symfony
DB_PASSWORD=symfony
DB_ROOT_PASSWORD=root

# GitHub API
GITHUB_API_TOKEN=your_token_here
GITHUB_API_BASE_URL=https://api.github.com
GITHUB_API_SEARCH_REPOS_ENDPOINT=/search/repositories

# Server
ENV_NGINX_PORT=8080
MAPPED_DB_PORT=3306
```

### Changing the Port

If port 8080 is already in use:

1. Edit `.env.local`
2. Set `ENV_NGINX_PORT=8081` (or any available port)
3. Restart containers: `docker compose down && docker compose up`

## Development

### Running Console Commands

Execute Symfony commands inside the PHP container:

```bash
# Fetch repositories manually
docker exec -it symfony_php php bin/console app:github:fetch-repositories

# Clear cache
docker exec -it symfony_php php bin/console cache:clear

# Run database migrations
docker exec -it symfony_php php bin/console doctrine:migrations:migrate
```

### Accessing the Database

```bash
# Via Docker
docker exec -it symfony_mariadb mysql -u symfony -psymfony symfony_app

# Via host (if port 3306 is mapped)
mysql -h 127.0.0.1 -P 3306 -u symfony -psymfony symfony_app
```

### Viewing Logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f php
docker compose logs -f nginx

# Supervisor worker logs
docker exec -it symfony_php supervisorctl tail -f messenger-consume:messenger-consume_00
```

### Tailwind CSS Watch Mode

For automatic Tailwind CSS rebuilds during development:

```bash
# Start watch mode (recommended)
docker exec -it symfony_php bash docker/watch-tailwind.sh

# Or run in background
docker exec -d symfony_php bash docker/watch-tailwind.sh

# Manual rebuild
docker exec symfony_php php bin/console tailwind:build
```

### Stopping the Application

```bash
# Stop containers (preserves data)
docker compose down

# Stop and remove volumes (deletes database)
docker compose down -v
```

## Troubleshooting

### Port Already in Use

```bash
# Change the port in .env.local
ENV_NGINX_PORT=8081

# Restart
docker compose down
docker compose up
```

### Database Connection Issues

```bash
# Reset database
docker compose down -v
docker compose up --build
```

### GitHub API Rate Limiting

Without authentication, GitHub limits to 60 requests/hour. With a token, you get 5,000 requests/hour.

**Solution**: Ensure `GITHUB_API_TOKEN` is set in `.env.local`

### Cache Issues

```bash
# Clear application cache
docker exec -it symfony_php php bin/console cache:clear

# Rebuild containers
docker compose down
docker compose up --build
```

### Supervisor Not Running Workers

```bash
# Check supervisor status
docker exec -it symfony_php supervisorctl status

# Restart workers
docker exec -it symfony_php supervisorctl restart all

# View supervisor logs
docker exec -it symfony_php cat /var/log/supervisor/supervisord.log
```

## Project Structure

```
.
├── config/                 # Symfony configuration
├── docker/                 # Docker configuration files
│   ├── nginx/             # Nginx configuration
│   ├── supervisor/        # Supervisor configuration
│   └── start.sh           # Container startup script
├── migrations/            # Database migrations
├── public/                # Web root
├── src/
│   ├── Actions/           # Business logic layer
│   ├── Command/           # Console commands
│   ├── Controller/        # HTTP controllers
│   ├── DataTransferObjects/ # DTOs for type safety
│   ├── Entity/            # Doctrine entities
│   ├── Enums/             # Typed enums
│   ├── Message/           # Async messages
│   ├── MessageHandler/    # Message handlers
│   ├── Repository/        # Database repositories
│   └── Services/          # External service integrations
├── templates/             # Twig templates
├── compose.yaml           # Docker Compose configuration
├── Dockerfile             # Production Docker image
├── Dockerfile.dev         # Development Docker image
└── README.md              # This file
```

## Testing

The application includes PHPUnit for testing:

```bash
# Run tests
docker exec -it symfony_php php bin/phpunit

# Run with coverage
docker exec -it symfony_php php bin/phpunit --coverage-html coverage
```

## Code Quality Tools

```bash
# PHP CS Fixer (code style)
docker exec -it symfony_php vendor/bin/php-cs-fixer fix

# PHPStan (static analysis)
docker exec -it symfony_php vendor/bin/phpstan analyse
```

## License

This project is created as a coding challenge demonstration.

## Credits

Built with:
- [Symfony Framework](https://symfony.com/)
- [GitHub REST API](https://docs.github.com/en/rest)
- [Tailwind CSS](https://tailwindcss.com/)
- [Docker](https://www.docker.com/)
