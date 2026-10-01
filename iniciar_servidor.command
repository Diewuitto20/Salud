#!/bin/bash
cd "$(dirname "$0")"
PHP=$(ls -d /Applications/MAMP/bin/php/php8*/bin/php 2>/dev/null | sort -V | tail -1)
PHP=${PHP:-php}
IP=$(ipconfig getifaddr en0 || ipconfig getifaddr en1)
PUERTO=8000

echo ""
echo "  ReActiva-T está corriendo."
echo ""
echo "  En esta computadora:      http://localhost:$PUERTO"
echo "  En celulares y otras PC:  http://$IP:$PUERTO"
echo ""
echo "  Los otros dispositivos deben estar en la misma red Wi-Fi."
echo "  MySQL de MAMP debe estar encendido."
echo "  Para apagar el servidor, cierra esta ventana o presiona Ctrl + C."
echo ""
"$PHP" -S 0.0.0.0:$PUERTO app/servidor.php
