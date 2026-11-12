#!/bin/bash
# Privilegios mínimos para el usuario de la aplicación.
# Se ejecuta una sola vez, al inicializar la base de datos.
#
# La imagen de MySQL concede ALL PRIVILEGES al usuario de MYSQL_USER sobre su base de datos.
# Aquí se recorta a lo que la aplicación y sus migraciones necesitan.
#
# Que la auditoría no se modifique no se puede imponer desde aquí (ver abajo): lo impide el modelo.
# Se conserva el permiso de borrado porque la política de conservación elimina la auditoría vencida.
set -eu

mysql --protocol=socket -u root -p"${MYSQL_ROOT_PASSWORD}" <<SQL
REVOKE ALL PRIVILEGES ON \`${MYSQL_DATABASE}\`.* FROM '${MYSQL_USER}'@'%';

-- Incluye lo que necesitan las migraciones de Laravel
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
    ON \`${MYSQL_DATABASE}\`.* TO '${MYSQL_USER}'@'%';

-- La inmutabilidad de la auditoría se impone en el modelo App\Models\Log: MySQL no permite revocar
-- a nivel de tabla un permiso concedido sobre toda la base de datos, y un disparador exigiría darle
-- a la aplicación privilegios que no debe tener.

FLUSH PRIVILEGES;
SQL
