# Project Structure Specification

## Overview
The project follows a well-organized structure that separates concerns between frontend and backend while maintaining a cohesive development experience through Inertia.js.

## Directory Structure

### Root Level
- `.github/` - GitHub workflows and configurations
- `.git/` - Git repository data
- `app/` - Core Laravel application code
- `bootstrap/` - Framework bootstrap files
- `config/` - Application configuration files
- `database/` - Database migrations and seeders
- `node_modules/` - Node.js dependencies
- `public/` - Publicly accessible files
- `resources/` - Frontend assets and views
- `routes/` - Application routes
- `storage/` - Application storage
- `tests/` - Test files
- `vendor/` - Composer dependencies

### Key Configuration Files
- `.editorconfig` - Editor configuration
- `.gitattributes` - Git attributes
- `.gitignore` - Git ignore rules
- `.prettierrc` - Prettier configuration
- `.prettierignore` - Prettier ignore rules
- `composer.json` - PHP dependencies
- `package.json` - Node.js dependencies
- `tsconfig.json` - TypeScript configuration
- `vite.config.ts` - Vite configuration
- `phpunit.xml` - PHPUnit configuration
- `eslint.config.js` - ESLint configuration

### Application Structure

#### Backend (`app/`)
- `Console/` - Artisan commands
- `Exceptions/` - Exception handlers
- `Http/` - Controllers, middleware, requests
- `Models/` - Eloquent models
- `Providers/` - Service providers
- `Services/` - Business logic services

#### Frontend (`resources/`)
- `js/` - JavaScript/TypeScript files
- `css/` - Stylesheets
- `views/` - Blade templates
- `components/` - Vue components

#### Routes (`routes/`)
- `web.php` - Web routes
- `auth.php` - Authentication routes
- `settings.php` - Settings routes
- `console.php` - Console commands

#### Tests (`tests/`)
- `Feature/` - Feature tests
- `Unit/` - Unit tests
- `Pest.php` - Pest configuration

### Development Tools
- `Makefile` - Build automation
- `artisan` - Laravel CLI tool

### Build Output
- `public/build/` - Compiled assets
- `storage/app/` - Application storage
- `storage/framework/` - Framework files
- `storage/logs/` - Application logs

### Configuration
- `config/` - Environment-specific configurations
- `.env` - Environment variables
- `.env.example` - Example environment variables

### Documentation
- `specs/` - Project specifications
- `README.md` - Project documentation 