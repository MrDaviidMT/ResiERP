# Versiones fijadas: con "latest" la imagen de hoy no sería la de mañana
FROM node:22.23-alpine AS build
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci
COPY . .
RUN npm run build

# nginx lleva los archivos públicos en la imagen: un volumen compartido serviría assets antiguos
FROM nginxinc/nginx-unprivileged:1.29-alpine AS web
# La imagen base trae los paquetes del día en que se publicó: `apk upgrade` aplica los parches
# de seguridad al construir. A cambio, dos construcciones en fechas distintas no son idénticas.
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
# Parches del sistema, igual que en la imagen de nginx
RUN apk upgrade --no-cache && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_mysql intl && \
    rm /usr/local/bin/install-php-extensions

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/99-resierp.ini

WORKDIR /var/www/html
COPY . .
COPY --from=build /app/public/build /var/www/html/public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction && \
    rm /usr/bin/composer

# Permisos en la imagen: el contenedor no necesita root. Las carpetas de los volúmenes deben
# existir aquí para que estos hereden el propietario
RUN mkdir -p storage/app/private storage/logs && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
