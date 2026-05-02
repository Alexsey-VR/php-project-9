FROM php:8.5-cli


RUN apt-get update && apt-get install -y --no-install-recommends \
    $(echo "libzip-dev libpq-dev postgresql-client"  | tr ' ' '\n' | sort | paste -sd ' ') && \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip pdo pdo_pgsql

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

WORKDIR /app

COPY . .

RUN composer install

CMD ["bash", "-c", "make start"]
