FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev \
    && docker-php-ext-install intl pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

WORKDIR /app

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["symfony", "server:start", "--no-tls", "--port=8000", "--allow-http", "--allow-all-ip"]
