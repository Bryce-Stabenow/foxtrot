# Development Workflow Specification

## Overview
The project implements a comprehensive development workflow that supports both frontend and backend development, with tools for testing, code quality, and deployment.

## Development Environment

### Setup
1. Clone repository
2. Install PHP dependencies: `composer install`
3. Install Node.js dependencies: `npm install`
4. Copy `.env.example` to `.env`
5. Generate application key: `php artisan key:generate`
6. Run migrations: `php artisan migrate`

### Development Server
- **Full Stack Development**:
  ```bash
  composer dev
  ```
  This runs:
  - Laravel development server
  - Queue worker
  - Log watcher
  - Vite development server

- **SSR Development**:
  ```bash
  composer dev:ssr
  ```
  This runs:
  - Laravel development server
  - Queue worker
  - Log watcher
  - SSR server

## Development Tools

### Code Quality
- **PHP Code Style**:
  - Laravel Pint for PHP code formatting
  - EditorConfig for consistent coding styles
  - PHP CS Fixer integration

- **JavaScript/TypeScript Code Style**:
  - ESLint for linting
  - Prettier for formatting
  - TypeScript for type checking

### Testing
- **PHP Testing**:
  - Pest PHP for testing
  - PHPUnit configuration
  - Feature and unit tests

- **Frontend Testing**:
  - Vue component testing
  - TypeScript type checking
  - ESLint for code quality

### Build Process
- **Development**:
  ```bash
  npm run dev
  ```
  - Hot Module Replacement
  - Type checking
  - Asset compilation

- **Production**:
  ```bash
  npm run build
  ```
  - Asset optimization
  - Type checking
  - Code splitting

- **SSR Build**:
  ```bash
  npm run build:ssr
  ```
  - Server-side rendering build
  - Client-side hydration

## Workflow Commands

### Development
```bash
# Start development servers
composer dev

# Start SSR development
composer dev:ssr

# Run tests
composer test

# Format code
composer format
npm run format

# Lint code
npm run lint
```

### Database
```bash
# Run migrations
php artisan migrate

# Run seeders
php artisan db:seed

# Create migration
php artisan make:migration

# Create seeder
php artisan make:seeder
```

### Frontend
```bash
# Development
npm run dev

# Production build
npm run build

# SSR build
npm run build:ssr

# Type checking
npm run type-check
```

## Best Practices

### Code Organization
- Follow PSR-4 autoloading
- Use proper namespacing
- Maintain component structure
- Follow Vue.js style guide

### Version Control
- Use feature branches
- Write meaningful commit messages
- Follow Git flow workflow
- Review code before merging

### Testing
- Write tests for new features
- Maintain test coverage
- Run tests before committing
- Use test-driven development

### Documentation
- Keep documentation updated
- Document new features
- Maintain API documentation
- Update README as needed 