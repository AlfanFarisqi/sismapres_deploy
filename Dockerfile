FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    nginx \
    wget \
    curl \
    git \
    unzip

RUN docker-php-ext-install pdo pdo_mysql

# Copy composer dari image resmi
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache

COPY nginx.conf /etc/nginx/nginx.conf

EXPOSE 80

CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
