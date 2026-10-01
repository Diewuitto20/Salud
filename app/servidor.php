<?php
/*
 * Enrutador para el servidor integrado de PHP (iniciar_servidor.command y compartir_https.command).
 * Solo deja pasar las páginas de la raíz y los archivos de css/ y js/; todo lo demás
 * (app/, _version_anterior/, documentos, respaldos, .git) responde 404.
 */
$ruta = rawurldecode(strtok($_SERVER['REQUEST_URI'], '?'));
if ($ruta === '/') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    require __DIR__ . '/../index.php';
    return true;
}
if (preg_match('#^/[a-z]+\.php$#', $ruta) && is_file(__DIR__ . '/..' . $ruta)) {
    return false;
}
if (preg_match('#^/(css|js)/[\w.-]+\.(css|js)$#', $ruta) && is_file(__DIR__ . '/..' . $ruta)) {
    return false;
}
http_response_code(404);
exit('Página no encontrada.');
