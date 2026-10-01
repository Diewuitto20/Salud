#!/bin/bash
cd "$(dirname "$0")"
PHP=$(ls -d /Applications/MAMP/bin/php/php8*/bin/php 2>/dev/null | sort -V | tail -1)
PHP=${PHP:-php}
PUERTO=8001

"$PHP" -S 127.0.0.1:$PUERTO app/servidor.php >/dev/null 2>&1 &
SERVIDOR=$!
caffeinate -dims -w $$ &
trap 'kill $SERVIDOR 2>/dev/null' EXIT

echo ""
echo "  Creando un enlace seguro (https) para ReActiva-T…"
echo "  La Mac no se suspenderá mientras esta ventana esté abierta."
echo "  Si la conexión se cae, se reconecta sola y te muestra un ENLACE NUEVO."
echo "  Para apagarlo, cierra esta ventana."
echo ""

while true; do
  ssh -o StrictHostKeyChecking=accept-new -o ServerAliveInterval=15 -o ServerAliveCountMax=3 \
      -o ExitOnForwardFailure=yes -R 80:localhost:$PUERTO nokey@localhost.run
  echo ""
  echo "  Se perdió la conexión. Reconectando en 5 segundos… (el enlace va a cambiar)"
  echo ""
  sleep 5
done
