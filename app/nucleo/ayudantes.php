<?php
defined('RAIZ') or exit;
/* Ayudantes de sesión y de presentación que usan controladores y vistas. */

function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function usuario(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function esPersona(): bool
{
    return (usuario()['tipo'] ?? '') === 'persona';
}

function esEmpresa(): bool
{
    return (usuario()['tipo'] ?? '') === 'empresa';
}

function exigir(string $tipo = ''): void
{
    if (!usuario() || ($tipo && usuario()['tipo'] !== $tipo)) {
        redirigir('entrar.php?volver=' . urlencode(basename($_SERVER['REQUEST_URI'])));
    }
}

function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function token(): string
{
    if (empty($_SESSION['token'])) {
        $_SESSION['token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['token'];
}

function campoToken(): string
{
    return '<input type="hidden" name="token" value="' . token() . '">';
}

function validarToken(): void
{
    if (!hash_equals(token(), $_POST['token'] ?? '')) {
        aviso('La página llevaba mucho tiempo abierta. Vuelve a intentarlo, por favor.', 'cuidado');
        redirigir(basename($_SERVER['PHP_SELF']));
    }
}

function aviso(string $texto, string $tipo = 'ok'): void
{
    $_SESSION['aviso'] = ['texto' => $texto, 'tipo' => $tipo];
}

function mostrarAviso(): string
{
    if (empty($_SESSION['aviso'])) {
        return '';
    }
    $a = $_SESSION['aviso'];
    unset($_SESSION['aviso']);
    return '<div class="aviso aviso-' . e($a['tipo']) . '">' . $a['texto'] . '</div>';
}

function soloPersonas(): void
{
    if (esEmpresa()) {
        aviso('Esta sección es solo para personas que buscan empleo.', 'cuidado');
        redirigir('empresa.php');
    }
}

const ESTADOS = ['puebla' => 'Puebla', 'veracruz' => 'Veracruz', 'oaxaca' => 'Oaxaca', 'tlaxcala' => 'Tlaxcala', 'otro' => 'Otro estado'];

const DIMENSIONES = ['estres' => 'Estrés', 'ansiedad' => 'Ansiedad', 'autoestima' => 'Autoestima', 'familia' => 'Vida familiar'];

function gradoDimension(int $puntos): string
{
    return $puntos >= 7 ? 'alto' : ($puntos >= 4 ? 'medio' : 'bajo');
}

function nivelTexto(string $nivel): string
{
    return ['minimo' => 'Te afecta poco', 'leve' => 'Te afecta un poco', 'moderado' => 'Te está afectando', 'grave' => 'Te está afectando mucho'][$nivel] ?? $nivel;
}

function hace(string $fecha): string
{
    $s = time() - strtotime($fecha);
    if ($s < 60) return 'hace un momento';
    if ($s < 3600) return 'hace ' . floor($s / 60) . ' min';
    if ($s < 86400) return 'hace ' . floor($s / 3600) . ' h';
    $d = floor($s / 86400);
    return $d == 1 ? 'ayer' : "hace $d días";
}

function fecha(string $fecha): string
{
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($fecha);
    return date('j', $t) . ' ' . $meses[date('n', $t) - 1];
}

function graficaEvaluaciones(array $evaluaciones): string
{
    $ancho = 600;
    $alto = 180;
    $paso = $ancho / max(count($evaluaciones), 6);
    $svg = '<svg viewBox="0 0 ' . $ancho . ' ' . ($alto + 30) . '" role="img" aria-label="Puntaje de ánimo en cada cuestionario">';
    $evaluaciones = array_values(array_filter($evaluaciones, fn($ev) => $ev['total'] !== null));
    foreach ([0, 9, 18, 27, 36] as $linea) {
        $y = $alto - $linea / 36 * $alto;
        $svg .= '<line x1="0" x2="' . $ancho . '" y1="' . $y . '" y2="' . $y . '" class="guia"/>';
    }
    foreach ($evaluaciones as $i => $ev) {
        $h = max(4, $ev['total'] / 36 * $alto);
        $x = $i * $paso + $paso * 0.2;
        $svg .= '<rect x="' . $x . '" y="' . ($alto - $h) . '" width="' . ($paso * 0.6) . '" height="' . $h . '" rx="6" class="barra-' . e($ev['nivel']) . '"/>';
        $svg .= '<text x="' . ($x + $paso * 0.3) . '" y="' . ($alto - $h - 6) . '" class="valor">' . (int) $ev['total'] . '</text>';
        $svg .= '<text x="' . ($x + $paso * 0.3) . '" y="' . ($alto + 22) . '" class="dia">' . fecha($ev['creado']) . '</text>';
    }
    return $svg . '</svg>';
}

function selectorEstado(string $actual, array $extra = []): string
{
    $html = '<form method="get" class="selector-estado">';
    foreach ($extra as $k => $v) {
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    $html .= '<label for="estado">Estoy en</label><select id="estado" name="estado" onchange="this.form.submit()">';
    $html .= '<option value="">Elige tu estado</option>';
    foreach (ESTADOS as $clave => $nombre) {
        $html .= '<option value="' . $clave . '"' . ($actual === $clave ? ' selected' : '') . '>' . $nombre . '</option>';
    }
    return $html . '</select><noscript><button type="submit">Ver</button></noscript></form>';
}
