<?php
defined('RAIZ') or exit;

/* Reporte personal en PDF: el proceso de cada persona, solo para ella */
class Reporte extends Modelo
{
    private const COLORES = ['minimo' => '2A9D8F', 'leve' => '7CC6A4', 'moderado' => 'FFB547', 'grave' => 'E0563F'];
    private const M = 46;

    public static function personal(array $usuario): string
    {
        $yo = (int) $usuario['id'];
        $historial = Evaluacion::historialDe($yo);
        $valia = Valia::deUsuario($yo);
        $plan = Plan::activo($yo);
        $avancePlan = $plan ? Plan::avance(Plan::calendario($plan)) : [0, 0];
        $cursos = Curso::guardadosDe($yo);
        $tramites = Apoyo::tramitesDe($yo);
        $postulaciones = Postulacion::deUsuario($yo);
        $comunidad = Nota::comunidadDe($yo);

        $pdf = new Pdf();
        $pdf->pagina();
        $ancho = Pdf::ANCHO - 2 * self::M;
        $y = self::encabezado($pdf, $usuario['nombre']);

        $espacio = function (float $alto) use ($pdf, &$y): void {
            if ($y + $alto > Pdf::ALTO - 50) {
                $pdf->pagina();
                $y = 50;
            }
        };
        $titulo = function (string $texto, string $color) use ($pdf, &$y, $espacio): void {
            $espacio(60);
            $pdf->rect(self::M, $y, 4, 16, $color);
            $pdf->texto(self::M + 12, $y + 13, $texto, 13, true, '22393D');
            $y += 28;
        };

        /* 1. Bienestar emocional */
        $titulo('Cómo te has sentido', '2A9D8F');
        if (!$historial) {
            $y = $pdf->parrafo(self::M, $y + 4, $ancho, 'Aún no contestas el cuestionario. Cuando lo hagas, aquí verás tu puntaje y cómo cambia con el tiempo.', 10) + 8;
        } else {
            $primera = $historial[0];
            $ultima = end($historial);
            $cambio = $primera['total'] - $ultima['total'];
            $tarjetas = [
                [$ultima['total'] . ' de 36', 'tu último puntaje (' . fecha($ultima['creado']) . ')', self::COLORES[$ultima['nivel']] ?? '2A9D8F'],
                [nivelTexto($ultima['nivel']), 'tu nivel actual', self::COLORES[$ultima['nivel']] ?? '2A9D8F'],
                [count($historial) < 2 ? '—' : ($cambio > 0 ? "Bajaste $cambio puntos" : ($cambio < 0 ? 'Subiste ' . abs($cambio) . ' puntos' : 'Sin cambio')),
                    count($historial) < 2 ? 'repite el cuestionario para comparar' : 'desde tu primer cuestionario (menos es mejor)', $cambio > 0 ? '2A9D8F' : 'FFB547'],
            ];
            $col = ($ancho - 20) / 3;
            foreach ($tarjetas as $i => [$valor, $etiqueta, $color]) {
                $x = self::M + $i * ($col + 10);
                $pdf->rect($x, $y, $col, 62, 'FFF8EC');
                $pdf->rect($x, $y, $col, 3, $color);
                $pdf->textoAlineado($x, $y + 31, $col, $valor, strlen($valor) > 16 ? 13 : 17, true, '22393D');
                $pdf->textoAlineado($x, $y + 49, $col, $etiqueta, 8.2, false, '55696C');
            }
            $y += 78;

            /* Dimensiones: primer contra último cuestionario */
            $espacio(130);
            $pdf->texto(self::M, $y + 10, 'Por área (0 a 9 puntos, menos es mejor)', 10, true, '22393D');
            if (count($historial) > 1) {
                $pdf->rect(self::M + $ancho - 200, $y + 3, 8, 8, 'D9DFE0');
                $pdf->texto(self::M + $ancho - 188, $y + 10, 'primer cuestionario', 8, false, '55696C');
                $pdf->rect(self::M + $ancho - 95, $y + 3, 8, 8, '2A9D8F');
                $pdf->texto(self::M + $ancho - 83, $y + 10, 'último', 8, false, '55696C');
            }
            $y += 22;
            $largo = $ancho - 210;
            foreach (DIMENSIONES as $clave => $nombre) {
                $antes = (int) $primera[$clave];
                $ahora = (int) $ultima[$clave];
                $grado = gradoDimension($ahora);
                $color = ['bajo' => '2A9D8F', 'medio' => 'FFB547', 'alto' => 'E0563F'][$grado];
                $pdf->texto(self::M, $y + 11, $nombre, 10, false, '22393D');
                $x = self::M + 110;
                if (count($historial) > 1) {
                    $pdf->rect($x, $y, $largo, 6, 'F3EEE6');
                    $pdf->rect($x, $y, $largo * $antes / 9, 6, 'D9DFE0');
                    $pdf->rect($x, $y + 8, $largo, 8, 'F3EEE6');
                    $pdf->rect($x, $y + 8, max(2, $largo * $ahora / 9), 8, $color);
                } else {
                    $pdf->rect($x, $y + 3, $largo, 10, 'F3EEE6');
                    $pdf->rect($x, $y + 3, max(2, $largo * $ahora / 9), 10, $color);
                }
                $texto = (count($historial) > 1 ? "de $antes a $ahora" : (string) $ahora) . ' · ' . $grado;
                $pdf->texto($x + $largo + 10, $y + 12, $texto, 9, true, '22393D');
                $y += 26;
            }

            /* Historial */
            $y += 6;
            $recientes = array_slice($historial, -8);
            $espacio(30 + count($recientes) * 16);
            $pdf->texto(self::M, $y + 10, 'Tus cuestionarios' . (count($historial) > 8 ? ' (los 8 más recientes)' : ''), 10, true, '22393D');
            $y += 20;
            foreach ($recientes as $ev) {
                $pdf->texto(self::M, $y + 10, fecha($ev['creado']) . ' ' . date('Y', strtotime($ev['creado'])), 9, false, '55696C');
                $pdf->rect(self::M + 110, $y + 3, ($ancho - 270) * $ev['total'] / 36 + 2, 8, self::COLORES[$ev['nivel']] ?? '2A9D8F');
                $pdf->texto(self::M + $ancho - 150, $y + 10, $ev['total'] . '/36 · ' . nivelTexto($ev['nivel']), 9, false, '22393D');
                $y += 16;
            }
            $y += 10;
        }

        /* 2. Mapa de valía */
        $titulo('Mi mapa de valía', 'FFB547');
        if (!$valia['logro'] && !$valia['cualidad']) {
            $y = $pdf->parrafo(self::M, $y + 4, $ancho, 'Todavía no anotas logros ni cualidades. Lo que vales no depende de tener empleo: empieza con algo que hiciste bien hoy.', 10) + 8;
        } else {
            $mitad = ($ancho - 20) / 2;
            $inicio = $y;
            $fin = $y;
            foreach (['logro' => 'Mis logros', 'cualidad' => 'Mis cualidades'] as $tipo => $encabezado) {
                $x = self::M + ($tipo === 'logro' ? 0 : $mitad + 20);
                $yy = $inicio;
                $pdf->texto($x, $yy + 10, $encabezado, 10, true, 'B0651A');
                $yy += 27;
                $lista = array_slice($valia[$tipo], 0, 10);
                if (!$lista) $yy = $pdf->parrafo($x, $yy + 4, $mitad, 'Aún no hay.', 9);
                foreach ($lista as $item) {
                    $pdf->rect($x, $yy - 3, 3, 3, 'FFB547');
                    $yy = $pdf->parrafo($x + 9, $yy, $mitad - 9, $item['texto'], 9.3, '22393D') + 3;
                }
                $fin = max($fin, $yy);
            }
            $y = $fin + 10;
        }

        /* 3. Avance hacia el trabajo */
        $titulo('Mi avance hacia el trabajo', '6CA8D8');
        $filas = [];
        if ($plan) $filas[] = ['Plan de 30 días', "{$avancePlan[0]} de {$avancePlan[1]} metas cumplidas hasta hoy"];
        foreach ($cursos as $c) $filas[] = ['Curso', $c['nombre'] . ' (' . $c['institucion'] . ') · ' . $c['estado']];
        foreach ($tramites as $t) $filas[] = ['Trámite', $t['nombre']];
        foreach ($postulaciones as $p) $filas[] = ['Postulación', $p['titulo'] . ' · ' . $p['empresa'] . ' · ' . $p['estado']];
        if (!$filas) {
            $y = $pdf->parrafo(self::M, $y + 4, $ancho, 'Aún no guardas cursos, trámites ni postulaciones. Cada paso pequeño cuenta.', 10) + 8;
        }
        foreach ($filas as [$tipo, $texto]) {
            $espacio(20);
            $pdf->texto(self::M, $y + 10, $tipo, 9, true, '2D6E9E');
            $y = $pdf->parrafo(self::M + 90, $y + 10, $ancho - 90, $texto, 9.3, '22393D') + 3;
        }
        $y += 8;

        /* 4. Comunidad */
        $titulo('Mi comunidad', '9B8AD1');
        $y = $pdf->parrafo(self::M, $y + 4, $ancho, sprintf('Publicaste %d notas en el foro, recibiste %d «Te apoyo» y %d respuestas de otras personas.',
            $comunidad['notas'], $comunidad['apoyos'], $comunidad['respuestas']), 10, '22393D') + 14;

        /* Cierre: contención y aviso */
        $serio = $historial && in_array(end($historial)['nivel'], ['moderado', 'grave'], true);
        $espacio(90);
        $pdf->rect(self::M, $y, $ancho, $serio ? 78 : 64, $serio ? 'FDE7E3' : 'DCF3EE');
        $texto = $serio
            ? 'Tus últimas respuestas muestran que esta etapa te está pesando. Te recomendamos llevar este reporte a un profesional de la salud: te ayudará a entender cómo has estado. Línea de la Vida: 800 911 2000, gratis y las 24 horas.'
            : 'Este reporte es orientativo y no sustituye la valoración de un profesional de la salud. Si en algún momento lo necesitas, la Línea de la Vida atiende gratis las 24 horas: 800 911 2000.';
        $pdf->parrafo(self::M + 14, $y + 20, $ancho - 28, $texto, 9.8, $serio ? '9A2E1C' : '1D7A6F', $serio);

        $pdf->texto(self::M, Pdf::ALTO - 30, 'Documento privado de ' . $usuario['nombre'] . ' · ReActiva-T no comparte tus resultados con empresas ni con nadie más.', 8, false, '8A9A9C');
        return $pdf->salida();
    }

    private static function encabezado(Pdf $pdf, string $nombre): float
    {
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $pdf->rect(0, 0, Pdf::ANCHO, 100, '2A9D8F');
        $pdf->texto(self::M, 44, 'ReActiva-T', 24, true, 'FFFFFF');
        $pdf->texto(self::M + $pdf->ancho('ReActiva-T', 24, true) + 10, 44, 'Mi reporte personal', 14, false, 'DCF3EE');
        $pdf->texto(self::M, 70, 'Preparado para ' . $nombre, 12, true, 'FFFFFF');
        $pdf->texto(self::M, 88, 'Generado el ' . date('j') . ' de ' . $meses[date('n') - 1] . ' de ' . date('Y') . ' · Solo tú puedes descargarlo', 9.5, false, 'DCF3EE');
        return 124;
    }
}
