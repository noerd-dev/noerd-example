# noerd Example Project

This is an example project built with the [noerd](https://github.com/noerd-dev/noerd) framework.

A live demo is available at [demo.noerd.dev](https://demo.noerd.dev).

## Setup

### 1. Clone the Repository

```bash
git clone git@github.com:noerd-dev/noerd-example.git
cd noerd-example
```

### 2. Initialize Submodules

This project uses git submodules. After cloning, initialize and fetch them:

```bash
git submodule init
git submodule update
```

### 3. Install PHP Dependencies

```bash
composer install
```

### 4. Configure Environment

```bash
cp .env.example .env
```

Sqlite is used per default. But feel free to change it. 

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Run Database Migrations

```bash
php artisan migrate
```

### 7. Install and Build Frontend Assets

```bash
npm install && npm run build
```

### 8. Create an Admin User

```bash
php artisan noerd:create-user
```

