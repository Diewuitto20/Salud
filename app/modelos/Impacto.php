<?php
defined('RAIZ') or exit;

/* Cifras de impacto agregadas y anónimas, calculadas en vivo desde la base de datos */
class Impacto extends Modelo
{
    /* Debajo de este número, una cifra de salud mental podría señalar a alguien: no se publica */
    public const MINIMO = 5;

    /* Descripción, color de acento (PDF) y clase CSS de cada sección */
    public const SECCIONES_INFO = [
        'Alcance' => ['Cuántas personas y empresas usan la plataforma.', '2A9D8F', '1D7A6F', 'alcance'],
        'Bienestar emocional' => ['Cómo cambia la afectación de quienes repiten el cuestionario.', '7CC6A4', '2E7D5B', 'bienestar'],
        'Comunidad de apoyo' => ['Actividad en el foro entre personas que viven lo mismo.', '9B8AD1', '5E4BA8', 'comunidad'],
        'Reinserción laboral' => ['Vacantes solidarias y el camino de las postulaciones.', 'FFB547', 'B0651A', 'laboral'],
        'Capacitación y plan de 30 días' => ['Cursos elegidos y metas cumplidas en el plan personal.', '6CA8D8', '2D6E9E', 'capacitacion'],
    ];

    public static function esCero($valor): bool
    {
        return in_array((string) $valor, ['0', '—', '0%'], true);
    }

    /* Parte un texto en renglones que quepan en el ancho dado */
    private static function renglones(Pdf $pdf, string $texto, float $tam, float $ancho): array
    {
        $lineas = [''];
        foreach (explode(' ', $texto) as $palabra) {
            $prueba = trim(end($lineas) . ' ' . $palabra);
            if ($pdf->ancho($prueba, $tam) <= $ancho || end($lineas) === '') $lineas[count($lineas) - 1] = $prueba;
            else $lineas[] = $palabra;
        }
        return $lineas;
    }

    public static function cifras(): array
    {
        $bd = self::bd();
        $porPersona = [];
        foreach ($bd->query('SELECT usuario_id, total, nivel, creado FROM evaluaciones WHERE total IS NOT NULL ORDER BY usuario_id, creado, id') as $ev) {
            $porPersona[$ev['usuario_id']][] = $ev;
        }
        $niveles = array_fill_keys(array_keys(Evaluacion::NIVELES), 0);
        $seguimiento = $mejoraron = $cambio = 0;
        foreach ($porPersona as $lista) {
            $primera = $lista[0];
            $ultima = end($lista);
            $niveles[$ultima['nivel']] = ($niveles[$ultima['nivel']] ?? 0) + 1;
            /* Seguimiento real: al menos una semana entre el primer y el último cuestionario */
            if (strtotime($ultima['creado']) - strtotime($primera['creado']) >= 7 * 86400) {
                $seguimiento++;
                $cambio += $primera['total'] - $ultima['total'];
                if ($ultima['total'] < $primera['total']) $mejoraron++;
            }
        }

        $c = $bd->query("SELECT
            (SELECT COUNT(*) FROM usuarios WHERE tipo = 'persona') AS personas,
            (SELECT COUNT(*) FROM usuarios WHERE tipo = 'empresa') AS empresas,
            (SELECT COUNT(*) FROM evaluaciones WHERE total IS NOT NULL) AS cuestionarios,
            (SELECT COUNT(*) FROM notas) AS notas,
            (SELECT COUNT(*) FROM apoyos) AS apoyos,
            (SELECT COUNT(*) FROM comentarios) AS respuestas,
            (SELECT COUNT(*) FROM valia) AS valia,
            (SELECT COUNT(*) FROM cursos_guardados g JOIN cursos c ON c.id = g.curso_id) AS cursos_guardados,
            (SELECT COUNT(*) FROM cursos_guardados g JOIN cursos c ON c.id = g.curso_id WHERE g.estado = 'Inscrito') AS cursos_inscritos,
            (SELECT COUNT(*) FROM cursos_guardados g JOIN cursos c ON c.id = g.curso_id WHERE g.estado = 'Terminado') AS cursos_terminados,
            (SELECT COUNT(*) FROM apoyos_guardados) AS tramites,
            (SELECT COUNT(*) FROM vacantes) AS vacantes,
            (SELECT COALESCE(SUM(cupos), 0) FROM vacantes WHERE activa = 1) AS lugares,
            (SELECT COUNT(*) FROM postulaciones) AS postulaciones,
            (SELECT COUNT(*) FROM postulaciones WHERE estado IN ('Entrevista', 'Contratado')) AS entrevistas,
            (SELECT COUNT(*) FROM postulaciones WHERE estado = 'Contratado') AS contratados,
            (SELECT COUNT(DISTINCT usuario_id) FROM planes) AS planes,
            (SELECT COUNT(*) FROM plan_marcas WHERE meta = 'respirar') AS dias_respiro,
            (SELECT COUNT(*) FROM plan_marcas WHERE meta <> 'respirar') AS metas_marcadas")->fetch();

        return array_map('intval', $c) + [
            'evaluadas' => count($porPersona),
            'seguimiento' => $seguimiento,
            'mejoraron' => $mejoraron,
            'cambio_promedio' => $seguimiento ? round($cambio / $seguimiento, 1) : 0,
            'niveles' => $niveles,
            'orientadas' => $niveles['moderado'] + $niveles['grave'],
        ];
    }

    /* Cifra de salud mental lista para publicar: entre 1 y 4 se muestra como «menos de 5» */
    public static function protegido(int $n): string
    {
        return $n > 0 && $n < self::MINIMO ? 'menos de ' . self::MINIMO : (string) $n;
    }

    public static function porcentaje(int $parte, int $total): string
    {
        return $total ? round($parte / $total * 100) . '%' : '—';
    }

    /* Guarda una copia de las cifras con un código; así cualquiera puede comprobar un PDF en la plataforma */
    public static function registrarReporte(array $cifras): string
    {
        $letras = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $codigo = '';
        for ($i = 0; $i < 12; $i++) $codigo .= $letras[random_int(0, strlen($letras) - 1)];
        $codigo = implode('-', str_split($codigo, 4));
        self::consultar('INSERT INTO reportes (codigo, cifras) VALUES (?, ?)', [$codigo, json_encode($cifras)]);
        return $codigo;
    }

    public static function buscarReporte(string $codigo): ?array
    {
        $fila = self::consultar('SELECT cifras, creado FROM reportes WHERE codigo = ?', [strtoupper(trim($codigo))])->fetch();
        return $fila ? ['cifras' => json_decode($fila['cifras'], true), 'creado' => $fila['creado']] : null;
    }

    /* Secciones del reporte: [título, [[cifra, etiqueta], ...]] */
    public static function secciones(array $c): array
    {
        $suficientes = $c['seguimiento'] >= self::MINIMO;
        return [
            ['Alcance', [
                [$c['personas'], 'personas registradas'],
                [$c['empresas'], 'empresas solidarias'],
                [$c['cuestionarios'], 'cuestionarios contestados'],
            ]],
            ['Bienestar emocional', [
                [self::protegido($c['seguimiento']), 'personas con seguimiento de una semana o más'],
                [$suficientes ? self::porcentaje($c['mejoraron'], $c['seguimiento']) : '—', 'de ellas bajó su puntaje de afectación'],
                [$suficientes ? abs($c['cambio_promedio']) : '—', $c['cambio_promedio'] >= 0 ? 'puntos de mejora promedio (escala 0 a 36)' : 'puntos de aumento promedio (escala 0 a 36)'],
                [self::protegido($c['orientadas']), 'personas orientadas a ayuda profesional'],
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
                [self::protegido($c['evaluadas']), 'personas que contestaron el cuestionario'],
            ]],
        ];
    }

    /* Filas de la gráfica de niveles: [nivel, texto de la cifra, ancho 0 a 1]; vacío si hay muy pocas personas */
    public static function filasNiveles(array $c): array
    {
        if ($c['evaluadas'] < self::MINIMO) return [];
        $maximo = max(1, max($c['niveles']));
        $filas = [];
        foreach ($c['niveles'] as $nivel => $cuantos) {
            $visible = $cuantos === 0 || $cuantos >= self::MINIMO;
            $texto = $visible ? $cuantos . ' · ' . self::porcentaje($cuantos, $c['evaluadas']) : self::protegido($cuantos);
            $filas[] = [$nivel, $texto, $visible ? $cuantos / $maximo : 0];
        }
        return $filas;
    }

    public static function reportePdf(array $c, string $codigo): string
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
        $pdf->textoAlineado($margen, 128, $ancho, 'Código de verificación: ' . $codigo, 9.5, true, '1D7A6F', 'derecha');

        $y = 150;
        foreach (self::secciones($c) as [$titulo, $datos]) {
            [, $acento, $oscuro, $clase] = self::SECCIONES_INFO[$titulo] ?? ['', '2A9D8F', '1D7A6F', ''];
            $destacada = $clase === 'alcance';
            $alto = $destacada ? 72 : 66;
            $filas = (int) ceil(count($datos) / 3);
            if ($y + 34 + $filas * ($alto + 10) > Pdf::ALTO - 60) {
                $pdf->pagina();
                $y = 50;
            }
            $pdf->rect($margen, $y, 4, 16, $acento);
            $pdf->texto($margen + 12, $y + 13, $titulo, 13, true, '22393D');
            $y += 26;
            $col = ($ancho - 2 * 10) / 3;
            foreach ($datos as $i => [$valor, $etiqueta]) {
                $x = $margen + ($i % 3) * ($col + 10);
                $yy = $y + intdiv($i, 3) * ($alto + 10);
                $cero = self::esCero($valor);
                $pdf->rect($x, $yy, $col, $alto, $destacada ? 'DCF3EE' : 'FFF8EC');
                $pdf->rect($x, $yy, $col, 3, $cero ? 'D9DFE0' : $acento);
                $pdf->textoAlineado($x, $yy + ($destacada ? 34 : 30), $col, (string) $valor, $destacada ? 26 : 21, true, $cero ? 'A9B5B7' : $oscuro);
                $lineas = self::renglones($pdf, $etiqueta, 8.2, $col - 16);
                foreach ($lineas as $n => $linea) {
                    $pdf->textoAlineado($x, $yy + ($destacada ? 50 : 45) + $n * 10, $col, $linea, 8.2, false, '55696C');
                }
            }
            $y += $filas * ($alto + 10) + 12;
        }

        if ($y + 150 > Pdf::ALTO - 60) {
            $pdf->pagina();
            $y = 50;
        }
        $pdf->rect($margen, $y, 4, 16, '22393D');
        $pdf->texto($margen + 12, $y + 13, 'Cómo están las personas hoy (último cuestionario de cada una)', 13, true, '22393D');
        $y += 34;
        $colores = ['minimo' => '2A9D8F', 'leve' => '7CC6A4', 'moderado' => 'FFB547', 'grave' => 'E0563F'];
        $filas = self::filasNiveles($c);
        if (!$filas) {
            $pdf->texto($margen, $y + 11, 'Se publicará cuando al menos ' . self::MINIMO . ' personas hayan contestado el cuestionario.', 10, false, '55696C');
            $y += 24;
        }
        foreach ($filas as [$nivel, $texto, $proporcion]) {
            $pdf->texto($margen, $y + 11, nivelTexto($nivel), 10, false, '22393D');
            $largo = ($ancho - 190) * $proporcion;
            $pdf->rect($margen + 140, $y, max(2, $largo), 14, $colores[$nivel] ?? '8A9A9C');
            $pdf->texto($margen + 146 + $largo, $y + 11, $texto, 10, true, '22393D');
            $y += 24;
        }

        if ($y + 170 > Pdf::ALTO - 40) {
            $pdf->pagina();
            $y = 50;
        }
        $y += 12;
        $pdf->rect($margen, $y, 4, 16, '22393D');
        $pdf->texto($margen + 12, $y + 13, 'Cómo se calculan y cómo verificarlas', 13, true, '22393D');
        $y += 36;
        $notas = [
            'Todas las cifras se consultan en vivo en la base de datos de la plataforma al momento de generar este reporte. Son agregadas y anónimas: no incluyen nombres, correos ni respuestas individuales.',
            'El cuestionario suma 12 preguntas de estrés, ansiedad, autoestima y vida familiar (0 a 36 puntos; menos es mejor). «Bajó su puntaje» compara el primer y el último cuestionario de cada persona, solo si hay al menos una semana entre ambos; si alguien lo repite el mismo día, cuenta una sola vez.',
            '«Orientadas a ayuda profesional» son personas cuyo último resultado fue «Te está afectando» o «Te está afectando mucho»; la plataforma les muestra la Línea de la Vida (800 911 2000) y centros de atención de su estado.',
            'Las postulaciones, entrevistas y contrataciones las registran las propias empresas en su panel. Los días de respiración y las metas los marca cada persona en su plan de 30 días.',
            'Para proteger a las personas, las cifras de salud mental entre 1 y 4 se publican como «menos de 5», y la distribución por niveles solo aparece con 5 personas o más.',
            'Para verificar este documento: abra impacto.php?codigo=' . $codigo . ' en la plataforma. La página muestra las cifras que se guardaron en el servidor al generarlo; si coinciden con estas, el documento es auténtico y no fue alterado.',
        ];
        foreach ($notas as $nota) {
            $pdf->rect($margen, $y - 7, 3, 3, '2A9D8F');
            $y = $pdf->parrafo($margen + 10, $y, $ancho - 10, $nota, 9.3) + 5;
        }
        $pdf->texto($margen, Pdf::ALTO - 30, 'ReActiva-T · reactiva tu vida y tu trabajo · salud-production-18e5.up.railway.app', 8.5, false, '8A9A9C');
        return $pdf->salida();
    }
}
