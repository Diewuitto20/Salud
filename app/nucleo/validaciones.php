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
