<?php
/*
 * Enrutador para el servidor integrado de PHP (iniciar_servidor.command y compartir_https.command).
 * Bloquea la carpeta app/, igual que app/.htaccess lo hace en Apache (MAMP).
 */
$pedido = realpath($_SERVER['DOCUMENT_ROOT'] . rawurldecode(strtok($_SERVER['REQUEST_URI'], '?')));
$privada = realpath(__DIR__);
if ($pedido && str_starts_with(strtolower($pedido . '/'), strtolower($privada . '/'))) {
    http_response_code(403);
    exit('Acceso denegado.');
}
return false;
