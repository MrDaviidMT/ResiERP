#!/bin/sh
set -e

# --isolated: si hubiera varias instancias, solo una ejecuta las migraciones.
# CACHE_STORE=file solo para este comando: el bloqueo no puede ir en la base de datos, porque
# con una base recién creada la tabla de bloqueos todavía no existe.
CACHE_STORE=file php artisan migrate --force --isolated
php artisan optimize

exec "$@"
