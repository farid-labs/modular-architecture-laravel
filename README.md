![Weather App Preview](./architecture-diagram.svg)<br>

# Modular Architecture Laravel (Monolith API)

A production-grade Laravel modular monolith demonstrating **Domain-Driven Design**, **Clean Architecture**, **Hexagonal Architecture**, and **Enterprise Patterns**. Designed for scalability, maintainability, testability, and developer experience. <br>

[![CI](https://github.com/farid-labs/modular-architecture-laravel/workflows/CI/badge.svg)](https://github.com/farid-labs/modular-architecture-laravel/actions)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHPStan Level 8](https://img.shields.io/badge/PHPStan-Level%208-brightgreen)](https://phpstan.org/)

## 🎯 Architecture Highlights

### Core Principles

- **Bounded Contexts** — Isolated modules: Users, Workspace, Notifications
- **Domain-Driven Design** — Rich domain models, value objects, domain events
- **Clean/Hexagonal Architecture** — Separation of concerns across layers
- **Vertical Slice Architecture** — Features organized by business capability
- **Test-Driven Development** — Comprehensive unit, feature, and integration tests

### Layered Structure (per module)

Each module follows the same architectural pattern:

```bash
Module/
├── Application/          # Use cases, services, DTOs, commands
├── Domain/               # Entities, value objects, domain events, enums, repositories
├── Infrastructure/       # Persistence (Eloquent), jobs, listeners, caching, policies
└── Presentation/         # API controllers, routes, resources, requests
```

### Key Patterns Implemented

- **Entities** & **Value Objects** — Immutable domain models with business invariants
- **Repository Pattern** — Interface-based data access with Eloquent implementation
- **Domain Events** — Event-driven communication within and across modules
- **Application Services** — Orchestration of use cases with transaction boundaries
- **Policies & Gates** — Fine-grained authorization
- **Jobs & Queues** — Asynchronous processing (e.g., notifications, file processing)
- **OpenAPI / Swagger** — Auto-generated API documentation

## 🗄️ Current Modules

- **Users** — Authentication, authorization, roles & permissions (Spatie)
- **Workspace** — Projects, tasks, comments, attachments, real-time events
- **Notifications** — Multi-channel notifications, queued delivery

## 🚀 Quick Start

### Prerequisites

- Docker & Docker Compose
- Git

### Installation

# Clone the repository

```bash
git clone https://github.com/farid-labs/modular-architecture-laravel.git
cd modular-architecture-laravel

# Copy environment file

cp .env.example .env

# Start all services

docker compose up -d --build

# Install PHP dependencies

docker compose exec app composer install --optimize-autoloader --no-dev

# Generate application key

docker compose exec app php artisan key:generate

# Run migrations and seed test data

docker compose exec app php artisan migrate:fresh --seed

# (Optional) Generate API documentation

docker compose exec app php artisan l5-swagger:generate

```

Application will be available at: `http://localhost:8080`

### Default Credentials (after seeding)

#### After running migrations with seeders, you can use this user for testing:

```json
{
    "email": "admin@faridlabs.com",
    "password": "password"
}
```

#### API Base URL: http://localhost:8080/api/v1

#### Swagger Docs: http://localhost:8080/api/documentation

### 🧪 Testing Strategy

```bash
# Run all tests
docker compose exec app php artisan test

# Run specific module tests
docker compose exec app php artisan test --filter=Workspace

# Run only unit tests
docker compose exec app php artisan test --testsuite=Unit

# Run only feature/API tests
docker compose exec app php artisan test --testsuite=Feature

# Run with code coverage (HTML report in storage/logs/coverage)
docker compose exec app php artisan test --coverage-html storage/logs/coverage
```

## 📊 Code Quality & Tooling

- PHPStan — Level 8 static analysis
- Laravel Pint — Code style fixer (PSR-12)
- PHPUnit — Unit & feature testing
- PHP_CodeSniffer — Enforce coding standards
- Larastan — PHPStan integration for Laravel

Run full quality check PHPStan:

```bash
docker compose exec app vendor/bin/phpstan analyse -vv
```

## 🔐 Security Features

- Sanctum API authentication
- Rate limiting on all endpoints
- Policy-based authorization
- Secure password hashing (bcrypt/argon2)
- Input validation & sanitization
- CSRF & XSS protection

## 📈 Performance Optimizations

- Redis caching (query & response level)
- Eager loading & query optimization
- Async jobs & queued notifications
- Database indexing on foreign keys
- Response compression (Gzip/Brotli)

## 🤝 Contributing

### Contributions that preserve architectural integrity are welcome.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'feat: add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open Pull Request
   Please follow Conventional Commits:

- `feat`: new feature
- `fix`: bug fix
- `refactor`: code restructuring
- `test`: adding/updating tests
- `docs`: documentation changes
- `chore`: maintenance tasks

## 📄 License

MIT License - see <a href="https://raw.githubusercontent.com/farid-labs/modular-architecture-laravel/refs/heads/main/LICENSE" >LICENSE</a> file for details

## 👨‍💻 About Farid Labs

#### Engineering-focused open-source repositories exploring scalable architectures, modern PHP practices, and enterprise patterns.

#### 🔗 Portfolio: https://faridteymouri.vercel.app/

---

Built with precision and care for long-term maintainability.
