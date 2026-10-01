<?php
/*
 * Arranque de la aplicación: sesión, conexión, carga automática de clases y despacho.
 * Cada archivo .php de la raíz solo incluye este archivo; el enrutador decide qué
 * controlador atiende la petición.
 */
define('RAIZ', dirname(__DIR__));

ini_set('display_errors', '0');
ini_set('log_errors', '1');

require RAIZ . '/app/config/conexion.php';
require RAIZ . '/app/nucleo/ayudantes.php';
require RAIZ . '/app/nucleo/validaciones.php';
require RAIZ . '/app/nucleo/seguridad.php';

/* Cualquier error inesperado se registra en el log y la persona ve un mensaje amable, nunca el SQL */
set_exception_handler(function (Throwable $e): void {
    error_log('ReActiva-T: ' . $e);
    if (!headers_sent()) http_response_code(500);
    echo '<!DOCTYPE html><meta charset="UTF-8"><title>Algo salió mal · ReActiva-T</title>'
        . '<p style="font:18px system-ui;max-width:560px;margin:60px auto;padding:0 20px">Algo salió mal de nuestro lado. '
        . 'Vuelve a intentarlo en un momento o <a href="index.php">regresa al inicio</a>.<br><br>'
        . 'Si necesitas hablar con alguien ahora: <a href="tel:8009112000"><b>Línea de la Vida 800 911 2000</b></a>.</p>';
});

$carpetaSesiones = sys_get_temp_dir() . '/retoma_sesiones';
if (!is_dir($carpetaSesiones)) mkdir($carpetaSesiones, 0700, true);
session_save_path($carpetaSesiones);
session_name('retoma');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => esHttps(), 'httponly' => true, 'samesite' => 'Lax']);
session_start();
cabecerasSeguridad();

spl_autoload_register(function (string $clase): void {
    foreach (['nucleo', 'modelos', 'controladores'] as $carpeta) {
        $archivo = RAIZ . "/app/$carpeta/$clase.php";
        if (is_file($archivo)) {
            require $archivo;
            return;
        }
    }
});

Enrutador::despachar(basename($_SERVER['SCRIPT_NAME']), $_SERVER['REQUEST_METHOD']);
