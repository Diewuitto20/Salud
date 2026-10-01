#!/bin/sh
set -e
PUERTO="${PORT:-8080}"
sed -i "s/^Listen .*/Listen ${PUERTO}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PUERTO}>/" /etc/apache2/sites-enabled/000-default.conf
echo "ServerName localhost" > /etc/apache2/conf-enabled/servername.conf
echo "Apache escuchando en el puerto ${PUERTO}"
exec apache2-foreground
