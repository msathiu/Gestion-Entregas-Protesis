FROM php:8.2-apache

# Instalar dependencias del sistema y extensión pdo_pgsql
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Ajustes de seguridad y sesión de PHP
RUN echo "session.cookie_httponly = 1" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "session.use_only_cookies = 1" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "session.cookie_samesite = 'Lax'" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "expose_php = Off" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "date.timezone = 'America/Caracas'" >> /usr/local/etc/php/conf.d/security.ini

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

