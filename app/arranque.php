<?php
/*
 * Arranque de la aplicación: sesión, conexión, carga automática de clases y despacho.
 * Cada archivo .php de la raíz solo incluye este archivo; el enrutador decide qué
 * controlador atiende la petición.
 */
define('RAIZ', dirname(__DIR__));

$carpetaSesiones = sys_get_temp_dir() . '/retoma_sesiones';
if (!is_dir($carpetaSesiones)) mkdir($carpetaSesiones, 0700, true);
session_save_path($carpetaSesiones);
session_name('retoma');
session_start();

require RAIZ . '/app/config/conexion.php';
require RAIZ . '/app/nucleo/ayudantes.php';
require RAIZ . '/app/nucleo/validaciones.php';

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
