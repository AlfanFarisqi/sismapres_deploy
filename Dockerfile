FROM php:8.3-cli

# Install dependency sistem
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install ekstensi PHP
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Working directory
WORKDIR /app

# Copy source code
COPY . .

# Install dependency Laravel
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Permission
RUN chmod -R 775 storage bootstrap/cache || true

# Railway menggunakan PORT dari environment
EXPOSE 8080

# Buat symlink storage
RUN php artisan storage:link

# Jalankan Laravel
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
