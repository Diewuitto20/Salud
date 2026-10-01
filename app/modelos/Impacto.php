<?php
defined('RAIZ') or exit;

/* Cifras de impacto agregadas y anónimas, calculadas en vivo desde la base de datos */
class Impacto extends Modelo
{
    public static function cifras(): array
    {
        $bd = self::bd();
        $n = fn(string $sql) => (int) $bd->query($sql)->fetchColumn();

        $porPersona = [];
        foreach ($bd->query('SELECT usuario_id, total, nivel FROM evaluaciones WHERE total IS NOT NULL ORDER BY usuario_id, creado, id') as $ev) {
            $porPersona[$ev['usuario_id']][] = $ev;
        }
        $niveles = array_fill_keys(array_keys(Evaluacion::NIVELES), 0);
        $seguimiento = $mejoraron = $cambio = 0;
        foreach ($porPersona as $lista) {
            $primera = $lista[0];
            $ultima = end($lista);
            $niveles[$ultima['nivel']] = ($niveles[$ultima['nivel']] ?? 0) + 1;
            if (count($lista) > 1) {
                $seguimiento++;
                $cambio += $primera['total'] - $ultima['total'];
                if ($ultima['total'] < $primera['total']) $mejoraron++;
            }
        }

        return [
            'personas' => $n("SELECT COUNT(*) FROM usuarios WHERE tipo = 'persona'"),
            'empresas' => $n("SELECT COUNT(*) FROM usuarios WHERE tipo = 'empresa'"),
            'cuestionarios' => $n('SELECT COUNT(*) FROM evaluaciones WHERE total IS NOT NULL'),
            'evaluadas' => count($porPersona),
            'seguimiento' => $seguimiento,
            'mejoraron' => $mejoraron,
            'cambio_promedio' => $seguimiento ? round($cambio / $seguimiento, 1) : 0,
            'niveles' => $niveles,
            'orientadas' => $niveles['moderado'] + $niveles['grave'],
            'notas' => $n('SELECT COUNT(*) FROM notas'),
            'autores' => $n('SELECT COUNT(DISTINCT usuario_id) FROM notas'),
            'apoyos' => $n('SELECT COUNT(*) FROM apoyos'),
            'respuestas' => $n('SELECT COUNT(*) FROM comentarios'),
            'valia' => $n('SELECT COUNT(*) FROM valia'),
            'cursos_guardados' => $n('SELECT COUNT(*) FROM cursos_guardados'),
            'cursos_inscritos' => $n("SELECT COUNT(*) FROM cursos_guardados WHERE estado = 'Inscrito'"),
            'cursos_terminados' => $n("SELECT COUNT(*) FROM cursos_guardados WHERE estado = 'Terminado'"),
            'tramites' => $n('SELECT COUNT(*) FROM apoyos_guardados'),
            'vacantes' => $n('SELECT COUNT(*) FROM vacantes'),
            'lugares' => $n('SELECT COALESCE(SUM(cupos), 0) FROM vacantes WHERE activa = 1'),
            'empresas_activas' => $n('SELECT COUNT(DISTINCT empresa_id) FROM vacantes'),
            'postulaciones' => $n('SELECT COUNT(*) FROM postulaciones'),
            'entrevistas' => $n("SELECT COUNT(*) FROM postulaciones WHERE estado IN ('Entrevista', 'Contratado')"),
            'contratados' => $n("SELECT COUNT(*) FROM postulaciones WHERE estado = 'Contratado'"),
            'planes' => $n('SELECT COUNT(DISTINCT usuario_id) FROM planes'),
            'dias_respiro' => $n("SELECT COUNT(*) FROM plan_marcas WHERE meta = 'respirar'"),
            'metas_marcadas' => $n("SELECT COUNT(*) FROM plan_marcas WHERE meta <> 'respirar'"),
        ];
    }

    /* Huella para comparar el PDF con la página en vivo: cambia si cambia cualquier cifra */
    public static function huella(array $cifras): string
    {
        return implode('-', str_split(strtoupper(substr(hash('sha256', json_encode($cifras)), 0, 16)), 4));
    }

    public static function porcentaje(int $parte, int $total): string
    {
        return $total ? round($parte / $total * 100) . '%' : '—';
    }

    /* Secciones del reporte: [título, [[cifra, etiqueta], ...]] */
    public static function secciones(array $c): array
    {
        return [
            ['Alcance', [
                [$c['personas'], 'personas registradas'],
                [$c['empresas'], 'empresas solidarias'],
                [$c['cuestionarios'], 'cuestionarios contestados'],
            ]],
            ['Bienestar emocional', [
                [$c['seguimiento'], 'personas con 2 o más cuestionarios'],
                [self::porcentaje($c['mejoraron'], $c['seguimiento']), 'de ellas bajó su puntaje de afectación'],
                [abs($c['cambio_promedio']), $c['cambio_promedio'] >= 0 ? 'puntos de mejora promedio (escala 0 a 36)' : 'puntos de aumento promedio (escala 0 a 36)'],
                [$c['orientadas'], 'personas orientadas a ayuda profesional'],
                [$c['valia'], 'logros y cualidades en el mapa de valía'],
                [$c['dias_respiro'], 'días de respiración registrados en planes'],
            ]],
            ['Comunidad de apoyo', [
                [$c['notas'], 'notas publicadas en el foro'],
                [$c['apoyos'], '«Te apoyo» dados'],
                [$c['respuestas'], 'respuestas entre personas'],
            ]],
            ['Reinserción laboral', [
                [$c['vacantes'], 'vacantes publicadas'],
                [$c['lugares'], 'lugares de trabajo disponibles'],
                [$c['postulaciones'], 'postulaciones enviadas'],
                [$c['entrevistas'], 'postulaciones que llegaron a entrevista'],
                [$c['contratados'], 'personas contratadas'],
                [$c['tramites'], 'trámites de apoyo guardados'],
            ]],
            ['Capacitación y plan de 30 días', [
                [$c['cursos_guardados'], 'cursos elegidos'],
                [$c['cursos_inscritos'] + $c['cursos_terminados'], 'cursos con inscripción'],
                [$c['cursos_terminados'], 'cursos terminados'],
                [$c['planes'], 'personas con plan de 30 días'],
                [$c['metas_marcadas'], 'metas de curso y trámite cumplidas'],
                [$c['evaluadas'], 'personas con diagnóstico de afectación'],
            ]],
        ];
    }

    public static function reportePdf(array $c, string $huella): string
    {
        $pdf = new Pdf();
        $pdf->pagina();
        $margen = 46;
        $ancho = Pdf::ANCHO - 2 * $margen;

        $pdf->rect(0, 0, Pdf::ANCHO, 104, '2A9D8F');
        $pdf->texto($margen, 46, 'ReActiva-T', 26, true, 'FFFFFF');
        $pdf->texto($margen + $pdf->ancho('ReActiva-T', 26, true) + 10, 46, 'Resultados verificables', 15, false, 'DCF3EE');
        $pdf->texto($margen, 72, 'HackaTec 2026 · Reto: Salud Mental y Bienestar Comunitario', 11, false, 'FFFFFF');
        $pdf->texto($margen, 90, 'Personas que perdieron su empleo: apoyo emocional, capacitación y vacantes en un solo lugar.', 9.5, false, 'DCF3EE');

        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $generado = date('j') . ' de ' . $meses[date('n') - 1] . ' de ' . date('Y') . ', ' . date('H:i') . ' h (hora del centro de México)';
        $pdf->texto($margen, 128, 'Generado el ' . $generado, 9.5, false, '55696C');
        $pdf->textoAlineado($margen, 128, $ancho, 'Huella: ' . $huella, 9.5, true, '1D7A6F', 'derecha');

        $y = 150;
        foreach (self::secciones($c) as [$titulo, $datos]) {
            $filas = (int) ceil(count($datos) / 3);
            if ($y + 30 + $filas * 70 > Pdf::ALTO - 60) {
                $pdf->pagina();
                $y = 50;
            }
            $pdf->texto($margen, $y + 12, $titulo, 13, true, '22393D');
            $pdf->linea($margen, $y + 20, $margen + $ancho, $y + 20, 'EFE5D7');
            $y += 30;
            $col = ($ancho - 2 * 10) / 3;
            foreach ($datos as $i => [$valor, $etiqueta]) {
                $x = $margen + ($i % 3) * ($col + 10);
                $yy = $y + intdiv($i, 3) * 70;
                $pdf->rect($x, $yy, $col, 60, 'FFF8EC');
                $pdf->textoAlineado($x, $yy + 28, $col, (string) $valor, 22, true, '1D7A6F');
                $pdf->textoAlineado($x, $yy + 46, $col, $etiqueta, 8.2, false, '55696C');
            }
            $y += $filas * 70 + 8;
        }

        if ($y + 150 > Pdf::ALTO - 60) {
            $pdf->pagina();
            $y = 50;
        }
        $pdf->texto($margen, $y + 12, 'Cómo están las personas hoy (último cuestionario de cada una)', 13, true, '22393D');
        $pdf->linea($margen, $y + 20, $margen + $ancho, $y + 20, 'EFE5D7');
        $y += 34;
        $colores = ['minimo' => '2A9D8F', 'leve' => '7CC6A4', 'moderado' => 'FFB547', 'grave' => 'E0563F'];
        $maximo = max(1, max($c['niveles']));
        foreach ($c['niveles'] as $nivel => $cuantos) {
            $pdf->texto($margen, $y + 11, nivelTexto($nivel), 10, false, '22393D');
            $largo = ($ancho - 190) * $cuantos / $maximo;
            $pdf->rect($margen + 140, $y, max(2, $largo), 14, $colores[$nivel] ?? '8A9A9C');
            $pdf->texto($margen + 146 + $largo, $y + 11, $cuantos . ' · ' . self::porcentaje($cuantos, $c['evaluadas']), 10, true, '22393D');
            $y += 24;
        }

        if ($y + 170 > Pdf::ALTO - 40) {
            $pdf->pagina();
            $y = 50;
        }
        $y += 12;
        $pdf->texto($margen, $y + 12, 'Cómo se calculan y cómo verificarlas', 13, true, '22393D');
        $pdf->linea($margen, $y + 20, $margen + $ancho, $y + 20, 'EFE5D7');
        $y += 36;
        $notas = [
            'Todas las cifras se consultan en vivo en la base de datos de la plataforma al momento de generar este reporte. Son agregadas y anónimas: no incluyen nombres, correos ni respuestas individuales.',
            'El cuestionario suma 12 preguntas de estrés, ansiedad, autoestima y vida familiar (0 a 36 puntos; menos es mejor). «Bajó su puntaje» compara el primer y el último cuestionario de cada persona que lo contestó dos veces o más.',
            '«Orientadas a ayuda profesional» son personas cuyo último resultado fue «Te está afectando» o «Te está afectando mucho»; la plataforma les muestra la Línea de la Vida (800 911 2000) y centros de atención de su estado.',
            'Las postulaciones, entrevistas y contrataciones las registran las propias empresas en su panel. Los días de respiración y las metas los marca cada persona en su plan de 30 días.',
            'Para verificar: abra la página «Resultados del reto» de la plataforma (impacto.php). Si la huella coincide con la de este documento, las cifras no han cambiado desde que se generó.',
        ];
        foreach ($notas as $nota) {
            $pdf->rect($margen, $y - 7, 3, 3, '2A9D8F');
            $y = $pdf->parrafo($margen + 10, $y, $ancho - 10, $nota, 9.3) + 5;
        }
        $pdf->texto($margen, Pdf::ALTO - 30, 'ReActiva-T · reactiva tu vida y tu trabajo · salud-production-18e5.up.railway.app', 8.5, false, '8A9A9C');
        return $pdf->salida();
    }
}
