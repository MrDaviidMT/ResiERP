# Versiones fijadas (hallazgo S5): una etiqueta móvil como "8.4" o "latest" cambia bajo los pies
# y hace que la imagen de hoy no sea la de mañana. Para actualizar: subir estas versiones a propósito.
FROM node:22.23-alpine AS build
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci
COPY . .
RUN npm run build

# nginx con los archivos públicos dentro de la imagen (hallazgo S9): nada de montar la carpeta
# del host ni volúmenes compartidos, que servirían los assets de una compilación anterior
FROM nginxinc/nginx-unprivileged:1.29-alpine AS web
# Las imágenes base se publican con los paquetes del sistema del día en que se construyeron:
# esta traía 46 vulnerabilidades HIGH y 1 CRITICAL con parche ya disponible (pcre2, libtiff...).
# `apk upgrade` las aplica al construir, sin esperar a que upstream publique la imagen nueva.
# A cambio, dos construcciones en fechas distintas no son idénticas: lo que fija la versión
# es la etiqueta de la base; esto solo adelanta los parches de seguridad de esa misma rama.
USER root
RUN apk upgrade --no-cache
USER 101
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/public /var/www/html/public

FROM composer:2.10 AS composer

FROM php:8.4.27-fpm-alpine

# El instalador se fija a una versión y se comprueba su firma, en lugar de descargar "latest"
ADD --checksum=sha256:3c3f3de26458405199f1f3a4c7130fdd2056daf596c05512badd769a77019b59 \
    https://github.com/mlocati/docker-php-extension-installer/releases/download/2.13.1/install-php-extensions \
    /usr/local/bin/install-php-extensions
# Mismo motivo que en la imagen de nginx: la base traía curl/libcurl con un HIGH ya parcheado
RUN apk upgrade --no-cache && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_mysql bcmath gd zip intl redis && \
    rm /usr/local/bin/install-php-extensions

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/99-resierp.ini

WORKDIR /var/www/html
COPY . .
COPY --from=build /app/public/build /var/www/html/public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction && \
    rm /usr/bin/composer

# Permisos en la imagen (hallazgo S9): el contenedor no necesita arrancar como root para hacer chown
RUN chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache && \
    php artisan storage:link

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
