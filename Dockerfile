# Usa la imagen base de PHP 8.4 con Apache
FROM php:8.4-apache

# Actualiza el índice de paquetes y instala herramientas necesarias
RUN apt-get update && apt-get install -y \
    zip unzip git curl libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql zip gd mbstring xml

# Copia Composer desde la imagen oficial
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Habilita mod_rewrite para Laravel
RUN a2enmod rewrite

# Copia configuración personalizada de Apache
COPY /apache/laravel.conf /etc/apache2/sites-available/000-default.conf

# Define el directorio de trabajo
WORKDIR /var/www/html

# Cambia permisos a usuario de Apache
RUN chown -R www-data:www-data /var/www/html

# Expone el puerto 80
EXPOSE 80