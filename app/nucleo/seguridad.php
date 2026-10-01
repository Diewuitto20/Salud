<?php
defined('RAIZ') or exit;
/* Defensas comunes: cabeceras, IP real, límite de intentos y acceso de moderación con clave. */

function esHttps(): bool
{
    return ($_SERVER['HTTPS'] ?? '') === 'on' || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function cabecerasSeguridad(): void
{
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/* Detrás del proxy de Railway la IP real es la última que agregó el proxy en X-Forwarded-For */
function ipCliente(): string
{
    $remota = $_SERVER['REMOTE_ADDR'] ?? '';
    $privada = !filter_var($remota, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) || str_starts_with($remota, '100.');
    if ($privada && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $lista = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        $ultima = end($lista);
        if (filter_var($ultima, FILTER_VALIDATE_IP)) return $ultima;
    }
    return $remota;
}

function claveIntento(string $accion, string $extra): string
{
    return substr($accion . ':' . ($extra !== '' ? mb_strtolower($extra) : ipCliente()), 0, 150);
}

/* ¿Ya hubo $maximo intentos en los últimos $minutos? (solo consulta) */
function bloqueado(string $accion, int $maximo, int $minutos, string $extra = ''): bool
{
    $st = bd()->prepare('SELECT COUNT(*) FROM intentos WHERE clave = ? AND creado > NOW() - INTERVAL ? MINUTE');
    $st->execute([claveIntento($accion, $extra), $minutos]);
    return (int) $st->fetchColumn() >= $maximo;
}

function registrarIntento(string $accion, string $extra = ''): void
{
    bd()->prepare('DELETE FROM intentos WHERE creado < NOW() - INTERVAL 1 DAY')->execute();
    bd()->prepare('INSERT INTO intentos (clave) VALUES (?)')->execute([claveIntento($accion, $extra)]);
}

/* Consulta y registra en un paso: para acciones donde cada intento cuenta */
function excedeLimite(string $accion, int $maximo, int $minutos, string $extra = ''): bool
{
    if (bloqueado($accion, $maximo, $minutos, $extra)) return true;
    registrarIntento($accion, $extra);
    return false;
}

function limpiarIntentos(string $accion, string $extra): void
{
    bd()->prepare('DELETE FROM intentos WHERE clave = ?')->execute([claveIntento($accion, $extra)]);
}

/* El correo ya registrado es el único error de base de datos que se explica a la persona */
function esDuplicado(PDOException $e): bool
{
    return ($e->errorInfo[1] ?? 0) === 1062;
}

/* La clave de moderación viene de la variable MODERACION_CLAVE o, en local, de app/config/moderacion.local.php */
function claveModeracion(): string
{
    $clave = (string) getenv('MODERACION_CLAVE');
    if ($clave === '' && is_file(RAIZ . '/app/config/moderacion.local.php')) {
        $clave = (string) require RAIZ . '/app/config/moderacion.local.php';
    }
    return strlen($clave) >= 12 ? $clave : '';
}

function esModerador(): bool
{
    return !empty($_SESSION['moderador']) && claveModeracion() !== '';
}
