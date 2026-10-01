<?php
$carpetaSesiones = sys_get_temp_dir() . '/retoma_sesiones';
if (!is_dir($carpetaSesiones)) mkdir($carpetaSesiones, 0700, true);
session_save_path($carpetaSesiones);
session_name('retoma');
session_start();
require __DIR__ . '/../config/conexion.php';

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

function esLocal(): bool
{
    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
        && in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)
        && empty($_SERVER['HTTP_X_FORWARDED_FOR']);
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

function hace(string $fecha): string
{
    $s = time() - strtotime($fecha);
    if ($s < 60) return 'hace un momento';
    if ($s < 3600) return 'hace ' . floor($s / 60) . ' min';
    if ($s < 86400) return 'hace ' . floor($s / 3600) . ' h';
    $d = floor($s / 86400);
    return $d == 1 ? 'ayer' : "hace $d días";
}

function sinAcentos(string $texto): string
{
    return strtr(mb_strtolower($texto, 'UTF-8'), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u']);
}

function hayCrisis(string $texto): bool
{
    $t = sinAcentos($texto);
    $frases = ['no quiero vivir', 'quitarme la vida', 'suicid', 'matarme', 'mejor muerto', 'mejor muerta',
        'hacerme dano', 'ya no quiero estar aqui', 'acabar con todo', 'no vale la pena vivir', 'desaparecer para siempre'];
    foreach ($frases as $f) {
        if (str_contains($t, $f)) return true;
    }
    return false;
}

function temaSensible(string $texto): bool
{
    if (hayCrisis($texto)) return true;
    $t = sinAcentos($texto);
    foreach (['autoles', 'cortarme', 'abuso', 'abusaron', 'violaci', 'violencia', 'me golpea', 'golpeaba', 'drogas', 'sobredosis'] as $f) {
        if (str_contains($t, $f)) return true;
    }
    return false;
}

function tieneGroserias(string $texto): bool
{
    $t = strtr(sinAcentos($texto), ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '@' => 'a', '$' => 's', '5' => 's', '7' => 't']);
    $t = preg_replace('/(.)\1{2,}/u', '$1', $t);
    $raices = ['pendej', 'pinche', 'chinga', 'chingue', 'chingon', 'chingad', 'verga', 'culer', 'cabron', 'mierda',
        'puto', 'puta', 'putos', 'putas', 'joto', 'maric', 'mamada', 'mamon', 'mames', 'ojete', 'perra', 'zorra',
        'idiota', 'estupid', 'imbecil', 'huevon', 'jodid', 'joder', 'culo', 'panocha', 'naco', 'nacos', 'retrasad', 'mongol'];
    foreach (preg_split('/[^a-zñ]+/u', $t) as $palabra) {
        foreach ($raices as $r) {
            if ($palabra !== '' && str_starts_with($palabra, $r)) return true;
        }
    }
    return false;
}

function avisoCrisis(): string
{
    return 'Lo que escribiste nos importa. Si estás pasando por un momento muy difícil, habla ahora con alguien: '
        . '<a href="tel:8009112000"><b>Línea de la Vida 800 911 2000</b></a> (gratuita, 24 horas). '
        . '<a href="ayuda.php">Ver más opciones de ayuda</a>.';
}

const DIMENSIONES = ['estres' => 'Estrés', 'ansiedad' => 'Ansiedad', 'autoestima' => 'Autoestima', 'familia' => 'Vida familiar'];

function gradoDimension(int $puntos): string
{
    return $puntos >= 7 ? 'alto' : ($puntos >= 4 ? 'medio' : 'bajo');
}

function nivelTexto(string $nivel): string
{
    return ['minimo' => 'Te afecta poco', 'leve' => 'Te afecta un poco', 'moderado' => 'Te está afectando', 'grave' => 'Te está afectando mucho'][$nivel] ?? $nivel;
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

const ESTADOS = ['puebla' => 'Puebla', 'veracruz' => 'Veracruz', 'oaxaca' => 'Oaxaca', 'tlaxcala' => 'Tlaxcala', 'otro' => 'Otro estado'];

function estadoActual(): string
{
    if (isset($_GET['estado']) && array_key_exists($_GET['estado'], ESTADOS)) {
        $_SESSION['estado'] = $_GET['estado'];
        if (esPersona()) {
            bd()->prepare('UPDATE usuarios SET estado = ? WHERE id = ?')->execute([$_GET['estado'], usuario()['id']]);
        }
    }
    if (empty($_SESSION['estado']) && usuario()) {
        $st = bd()->prepare('SELECT estado FROM usuarios WHERE id = ?');
        $st->execute([usuario()['id']]);
        $_SESSION['estado'] = $st->fetchColumn() ?: '';
    }
    return $_SESSION['estado'] ?? '';
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

function esTextoBasura(string $texto): bool
{
    $t = sinAcentos(trim($texto));
    if (mb_strlen($t) < 8) return true;
    $palabras = preg_split('/\s+/u', $t);
    foreach ($palabras as $p) {
        $letras = preg_replace('/[^a-zñ]/u', '', $p);
        if (mb_strlen($letras) >= 5 && !preg_match('/[aeiouy]/', $letras)) return true;
        if (preg_match('/[bcdfghjklmnñpqrstvwxz]{5,}/u', $letras)) return true;
    }
    return count($palabras) < 2;
}

function soloPersonas(): void
{
    if (esEmpresa()) {
        aviso('Esta sección es solo para personas que buscan empleo.', 'cuidado');
        redirigir('empresa.php');
    }
}
