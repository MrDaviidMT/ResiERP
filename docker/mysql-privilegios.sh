#!/bin/bash
# Privilegios mínimos para el usuario de la aplicación (hallazgo S12 de la auditoría).
# Se ejecuta una sola vez, al inicializar la base de datos.
#
# La imagen de MySQL concede ALL PRIVILEGES al usuario de MYSQL_USER sobre su base de datos,
# lo que incluye vaciar la auditoría. Aquí se recorta a lo que la aplicación necesita, y se le
# quita la posibilidad de MODIFICAR registros de auditoría: una vez escritos no se alteran.
#
# Se conserva el permiso de borrado porque la política de conservación tiene que poder
# eliminar la auditoría vencida. Separar eso en un segundo usuario con más privilegios queda
# anotado como mejora futura: cambiaría poco el riesgo y añadiría otra credencial que custodiar.
set -eu

mysql --protocol=socket -u root -p"${MYSQL_ROOT_PASSWORD}" <<SQL
REVOKE ALL PRIVILEGES ON \`${MYSQL_DATABASE}\`.* FROM '${MYSQL_USER}'@'%';

-- Incluye lo que necesitan las migraciones de Laravel
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
    ON \`${MYSQL_DATABASE}\`.* TO '${MYSQL_USER}'@'%';

-- La inmutabilidad de la auditoría no se impone aquí: MySQL no permite revocar a nivel de tabla
-- un permiso concedido sobre toda la base de datos, y crear un disparador exigiría darle a la
-- aplicación el privilegio TRIGGER (y SUPER, con el registro binario activado), que es más poder
-- del que debe tener. Se impone en el modelo App\Models\Log, que rechaza cualquier modificación.
-- Endurecimiento opcional en el despliegue: crear el disparador a mano con el usuario administrador.

FLUSH PRIVILEGES;
SQL
