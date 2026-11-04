#!/bin/sh
set -e

# Los permisos de storage y bootstrap/cache ya se dejan puestos en la imagen: el contenedor
# arranca como www-data y no necesita root (hallazgo S9)

# --isolated: si hubiera varias instancias, solo una ejecuta las migraciones (hallazgo S11).
# CACHE_STORE=file solo para este comando: el bloqueo no puede ir en la base de datos, porque
# con una base recién creada la tabla de bloqueos todavía no existe.
CACHE_STORE=file php artisan migrate --force --isolated
php artisan optimize

exec "$@"
