<?php
defined('RAIZ') or exit;
/* Reglas para revisar lo que escriben las personas: crisis, temas sensibles, groserías y texto sin sentido. */

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

/*
 * Groserías. Las listas se escriben normal; el texto y las listas pasan por la misma limpieza para que no se pueda
 * esquivar el filtro con trucos: números por letras (p3nd3jo), k/q por c, v por b, z por s, x por ch, letras repetidas
 * (puuuto), símbolos entre letras (p.u.t.o), letras sueltas (p u t o), asterisco por vocal (p*to) o sin vocales (pndjo).
 */
const GROSERIAS_RAICES = ['pendej', 'pinche', 'chinga', 'chingu', 'chingo', 'cabron', 'mierd', 'puta', 'puto', 'culer',
    'culon', 'mamad', 'mamon', 'mames', 'ojete', 'jodid', 'joder', 'panoch', 'idiot', 'estupid', 'imbecil', 'huevon',
    'guevon', 'retrasad', 'malparid', 'gilipoll', 'cagad', 'cagon', 'vergaz', 'vergot', 'putaz', 'cogert', 'pelotud', 'cojud'];
const GROSERIAS_EXACTAS = ['pinchi', 'pinchis', 'naco', 'nacos', 'naca', 'nacas', 'joto', 'jotos', 'mongol', 'mongolo', 'mongola', 'marica', 'maricas',
    'maricon', 'maricones', 'mariconada', 'culo', 'culos', 'perra', 'perras', 'zorra', 'zorras', 'verga', 'vergas', 'coño',
    'carajo', 'ptm', 'ptmr', 'alv', 'hdp', 'hdtpm', 'ctm', 'vrg', 'pndj', 'pndjo', 'pndja', 'qlo', 'qlero', 'hpta',
    'fuck', 'fucking', 'shit', 'bitch', 'asshole', 'wtf', 'stfu'];
const GROSERIAS_DENTRO = ['pendej', 'chingad', 'chingues', 'cabron', 'mierd', 'putamadre', 'hijodeput', 'hijueput',
    'alaverga', 'valemadre', 'malparid'];
const GROSERIAS_FRASES = ['vale madre', 'valio madre', 'valer madre', 'me la pelas', 'te la pelas', 'me la pela',
    'chinga tu', 'tu puta', 'hijo de tu', 'hija de tu', 'mentada de madre', 'mentar la madre'];
/* Insultos que también se usan contra uno mismo («me siento un idiota»): en ese caso no se bloquean */
const GROSERIAS_SUAVES = ['idiot', 'estupid', 'imbecil'];

function normalizarFiltro(string $texto): string
{
    $t = sinAcentos($texto);
    $t = preg_replace('/(?<=[a-zñ])[!|](?=[a-zñ])/u', 'i', $t);
    $t = strtr($t, ['0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '@' => 'a', '$' => 's', '5' => 's', '7' => 't',
        '8' => 'b', 'k' => 'c', 'q' => 'c', 'v' => 'b', 'z' => 's', 'x' => 'ch']);
    return preg_replace('/(?<=[a-zñ*])[.\-_·\'"´`~^+]+(?=[a-zñ*])/u', '', $t);
}

/* Palabras del texto ya limpias; las letras sueltas seguidas (p u t o) se unen en una palabra extra */
function palabrasFiltro(string $texto): array
{
    $palabras = preg_split('/[^a-zñ*]+/u', normalizarFiltro($texto), -1, PREG_SPLIT_NO_EMPTY);
    $sueltas = '';
    foreach (array_merge($palabras, ['  ']) as $p) {
        if (mb_strlen($p) === 1) {
            $sueltas .= $p;
            continue;
        }
        if (mb_strlen($sueltas) >= 3) $palabras[] = $sueltas;
        $sueltas = '';
    }
    return $palabras;
}

/* Convierte una raíz en regex: cada letra puede repetirse, «*» cuenta como vocal y en raíces largas las vocales internas son opcionales */
function patronFiltro(string $raiz, bool $vocalesOpcionales): string
{
    $letras = mb_str_split(implode('', palabrasFiltro($raiz)));
    $patron = '';
    foreach ($letras as $i => $c) {
        $vocal = str_contains('aeiou', $c);
        $clase = $vocal ? "[$c*]" : preg_quote($c, '/');
        $opcional = $vocal && $vocalesOpcionales && $i > 0 && $i < count($letras) - 1;
        $patron .= $clase . ($opcional ? '*' : '+');
    }
    return $patron;
}

/* Devuelve la grosería encontrada (tal como está en la lista) o null */
function groseriaEn(string $texto): ?string
{
    static $reglas = null;
    if ($reglas === null) {
        $reglas = [];
        foreach (GROSERIAS_EXACTAS as $g) $reglas[] = [$g, '/^' . patronFiltro($g, false) . '$/u'];
        foreach (GROSERIAS_RAICES as $g) $reglas[] = [$g, '/^' . patronFiltro($g, mb_strlen($g) >= 5) . '/u'];
        foreach (GROSERIAS_DENTRO as $g) $reglas[] = [$g, '/' . patronFiltro($g, false) . '/u'];
    }
    if (str_contains($texto, '🖕')) return '🖕';

    $palabras = palabrasFiltro($texto);
    foreach ($palabras as $i => $palabra) {
        foreach ($reglas as [$g, $regex]) {
            if (!preg_match($regex, $palabra)) continue;
            $antes = ' ' . implode(' ', array_slice($palabras, max(0, $i - 3), min($i, 3))) . ' ';
            if (in_array($g, GROSERIAS_SUAVES, true) && preg_match('/ (soy|siento|senti|sentia|sentir|fui) /', $antes)) continue;
            return $g;
        }
    }
    $frase = ' ' . implode(' ', $palabras) . ' ';
    foreach (GROSERIAS_FRASES as $f) {
        if (str_contains($frase, ' ' . implode(' ', palabrasFiltro($f)) . ' ')) return $f;
    }
    return null;
}

/*
 * Agresiones contra otra persona: incitar a hacerse daño, desearle la muerte o humillarla. En un espacio de salud mental
 * se bloquean aunque no lleven groserías. Lo que alguien dice de sí mismo («me siento un fracaso») no entra aquí.
 */
const AGRESIONES_RAICES = ['matat', 'matese', 'suicidat', 'suicidese', 'muerete', 'muerase', 'cuelgat', 'ahorcat', 'aventat', 'tirat'];
const AGRESIONES_FRASES = ['ojala te mueras', 'ojala te murieras', 'deberias morirte', 'deberias matarte', 'te deberias morir',
    'te deberias matar', 'por que no te matas', 'porque no te matas', 'vete a morir', 'mejor muerete', 'quitate la vida',
    'no mereces vivir', 'nadie te quiere', 'nadie te va a querer', 'no sirves', 'no vales nada', 'das asco', 'das lastima',
    'eres un fracaso', 'eres una fracasada', 'eres un fracasado', 'eres basura', 'eres una basura', 'eres una carga',
    'eres un inutil', 'eres una inutil', 'eres un estorbo', 'eres una lacra', 'por eso te corrieron',
    'deja de llorar', 'no te hagas la victima', 'callate'];

function agresionEn(string $texto): ?string
{
    $palabras = palabrasFiltro($texto);
    foreach ($palabras as $palabra) {
        foreach (AGRESIONES_RAICES as $raiz) {
            if (preg_match('/^' . patronFiltro($raiz, false) . '/u', $palabra)) return $raiz;
        }
    }
    $frase = ' ' . implode(' ', $palabras) . ' ';
    foreach (AGRESIONES_FRASES as $f) {
        if (str_contains($frase, ' ' . implode(' ', palabrasFiltro($f)) . ' ')) return $f;
    }
    return null;
}

function tieneGroserias(string $texto): bool
{
    return groseriaEn($texto) !== null;
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

/* Para respuestas cortas: rechaza solo teclazos (sin vocales o con 5 consonantes seguidas) y texto sin letras */
function sinSentido(string $texto): bool
{
    $t = sinAcentos(trim($texto));
    if (!preg_match('/[a-zñ]{2}/u', $t)) return true;
    foreach (preg_split('/\s+/u', $t) as $p) {
        $letras = preg_replace('/[^a-zñ]/u', '', $p);
        if (mb_strlen($letras) >= 5 && !preg_match('/[aeiouy]/', $letras)) return true;
        if (preg_match('/[bcdfghjklmnñpqrstvwxz]{5,}/u', $letras)) return true;
    }
    return false;
}

function avisoCrisis(): string
{
    return 'Lo que escribiste nos importa. Si estás pasando por un momento muy difícil, habla ahora con alguien: '
        . '<a href="tel:8009112000"><b>Línea de la Vida 800 911 2000</b></a> (gratuita, 24 horas). '
        . '<a href="ayuda.php">Ver más opciones de ayuda</a>.';
}
