#!/bin/sh
set -e
PUERTO="${PORT:-8080}"
# Apache con mod_php solo admite prefork; se descartan otros MPM que aparezcan en el contenedor.
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*
ln -sf ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf
sed -i "s/^Listen .*/Listen ${PUERTO}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PUERTO}>/" /etc/apache2/sites-enabled/000-default.conf
echo "ServerName localhost" > /etc/apache2/conf-enabled/servername.conf
echo "MYSQLHOST=${MYSQLHOST:-(vacía)} MYSQLPORT=${MYSQLPORT:-(vacía)} MYSQLUSER=${MYSQLUSER:-(vacía)} MYSQLPASSWORD=$([ -n "$MYSQLPASSWORD" ] && echo definida || echo vacía)"
echo "Apache escuchando en el puerto ${PUERTO}"
exec apache2-foreground
