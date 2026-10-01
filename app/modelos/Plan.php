<?php
defined('RAIZ') or exit;

/*
 * Plan personalizado de 30 días: cuatro semanas con cuatro metas cada una.
 * Las postulaciones y el avance del curso se comprueban con lo que la persona ya hizo
 * en Vacantes y en Mis cursos; además, cualquier meta se puede marcar a mano como hecha.
 */
class Plan extends Modelo
{
    public const DIAS = 30;
    public const INTERESES = ['oficio' => 'Aprender un oficio', 'emprender' => 'Emprender mi propio negocio'];
    public const RESPIRAR = ['minimo' => 3, 'leve' => 4, 'moderado' => 5, 'grave' => 7];
    public const POSTULACIONES = 2;
    public const HORAS_CURSO = [0 => 2, 1 => 4, 2 => 6];
    /* «respirar» es la marca diaria; las demás marcan la meta completa de la semana */
    public const MARCABLES = ['respirar', 'respirar_listo', 'curso', 'postulaciones', 'tramite'];

    public static function activo(int $usuario): ?array
    {
        return self::consultar('SELECT * FROM planes WHERE usuario_id = ? ORDER BY id DESC LIMIT 1', [$usuario])->fetch() ?: null;
    }

    public static function crear(int $usuario, string $nivel, string $estado, string $interes): void
    {
        $ritmo = Evaluacion::NIVELES[$nivel][2] ?? 1;
        $curso = self::consultar('SELECT id FROM cursos WHERE tipo = ? AND (estado IS NULL OR estado = ?)
            ORDER BY ABS(ritmo - ?), estado IS NULL, id LIMIT 1', [$interes, $estado, $ritmo])->fetchColumn();

        $guardados = Apoyo::clavesGuardadas($usuario);
        $claves = array_keys(Apoyo::visibles($estado));
        usort($claves, fn($a, $b) => in_array($a, $guardados, true) <=> in_array($b, $guardados, true));
        $tramites = [];
        for ($i = 0; $i < 4 && $claves; $i++) $tramites[] = $claves[$i % count($claves)];

        self::consultar('INSERT INTO planes (usuario_id, nivel, estado, interes, curso_id, tramites, inicio) VALUES (?, ?, ?, ?, ?, ?, CURDATE())',
            [$usuario, $nivel, $estado, $interes, $curso ?: null, implode(',', $tramites)]);
    }

    /* Día del plan (1 a 30) y semana (1 a 4); semana 0 si ya terminó */
    public static function hoy(array $plan): array
    {
        $dia = (int) floor((strtotime(date('Y-m-d')) - strtotime($plan['inicio'])) / 86400) + 1;
        $semana = $dia > self::DIAS ? 0 : min(4, intdiv($dia - 1, 7) + 1);
        return [$dia, $semana];
    }

    public static function rango(array $plan, int $semana): array
    {
        $desde = strtotime($plan['inicio'] . ' +' . (($semana - 1) * 7) . ' days');
        $hasta = $semana < 4 ? strtotime('+6 days', $desde) : strtotime($plan['inicio'] . ' +' . (self::DIAS - 1) . ' days');
        return [date('Y-m-d', $desde), date('Y-m-d', $hasta)];
    }

    /* Marca o desmarca una meta en la semana en curso; el trámite marcado también se guarda en «Mis trámites» */
    public static function alternar(array $plan, string $meta): bool
    {
        [, $semana] = self::hoy($plan);
        if (!$semana || !in_array($meta, self::MARCABLES, true)) return false;
        $condicion = $meta === 'respirar' ? 'fecha = CURDATE()' : 'semana = ?';
        $parametros = $meta === 'respirar' ? [$plan['id'], $meta] : [$plan['id'], $meta, $semana];
        if (self::consultar("DELETE FROM plan_marcas WHERE plan_id = ? AND meta = ? AND $condicion", $parametros)->rowCount()) {
            return false;
        }
        self::consultar('INSERT INTO plan_marcas (plan_id, meta, semana, fecha) VALUES (?, ?, ?, CURDATE())', [$plan['id'], $meta, $semana]);
        if ($meta === 'tramite') {
            $clave = explode(',', $plan['tramites'])[$semana - 1] ?? '';
            if (Apoyo::existe($clave)) {
                self::consultar('INSERT IGNORE INTO apoyos_guardados (usuario_id, apoyo) VALUES (?, ?)', [$plan['usuario_id'], $clave]);
            }
        }
        return true;
    }

    /* Calendario con el avance de cada meta, semana por semana */
    public static function calendario(array $plan): array
    {
        $yo = (int) $plan['usuario_id'];
        [, $semanaActual] = self::hoy($plan);
        $curso = $plan['curso_id'] ? self::consultar('SELECT c.*, g.estado AS avance FROM cursos c
            LEFT JOIN cursos_guardados g ON g.curso_id = c.id AND g.usuario_id = ? WHERE c.id = ?', [$yo, $plan['curso_id']])->fetch() : null;
        $horas = self::HORAS_CURSO[Evaluacion::NIVELES[$plan['nivel']][2] ?? 1];
        $tramites = explode(',', $plan['tramites']);
        $marcas = self::consultar('SELECT meta, semana, fecha FROM plan_marcas WHERE plan_id = ?', [$plan['id']])->fetchAll();
        $postulaciones = self::consultar('SELECT DATE(creado) FROM postulaciones WHERE usuario_id = ? AND creado >= ?', [$yo, $plan['inicio']])->fetchAll(PDO::FETCH_COLUMN);
        $respirarHoy = in_array(date('Y-m-d'), array_column(array_filter($marcas, fn($m) => $m['meta'] === 'respirar'), 'fecha'), true);

        $semanas = [];
        for ($s = 1; $s <= 4; $s++) {
            [$desde, $hasta] = self::rango($plan, $s);
            $enSemana = fn($fecha) => $fecha >= $desde && $fecha <= $hasta;
            $marcada = fn($meta) => (bool) array_filter($marcas, fn($m) => $m['meta'] === $meta && (int) $m['semana'] === $s);
            $diasRespiro = count(array_filter($marcas, fn($m) => $m['meta'] === 'respirar' && $enSemana($m['fecha'])));
            $hechas = count(array_filter($postulaciones, $enSemana));

            $metas = [];
            $metas['respirar'] = [
                'titulo' => 'Ejercicio de respiración',
                'detalle' => 'Hazlo ' . self::RESPIRAR[$plan['nivel']] . ' días esta semana: 4 tiempos al inhalar, 6 al soltar.',
                'meta' => self::RESPIRAR[$plan['nivel']], 'hecho' => $diasRespiro, 'enlace' => 'ayuda.php', 'textoEnlace' => 'Respirar ahora',
                'marcable' => $s === $semanaActual, 'marcadaHoy' => $respirarHoy, 'auto' => false, 'manual' => $marcada('respirar_listo'),
            ];
            if ($curso) {
                $terminado = $curso['avance'] === 'Terminado';
                $inscrito = in_array($curso['avance'], ['Inscrito', 'Terminado'], true);
                $detalle = match ($s) {
                    1 => 'Guarda el curso en «Mis cursos» y márcalo como «Inscrito».',
                    4 => "Dedica $horas horas y, si lo terminas, márcalo como «Terminado».",
                    default => "Dedica $horas horas a avanzar en el curso.",
                };
                $metas['curso'] = [
                    'titulo' => $curso['nombre'], 'detalle' => $detalle,
                    'meta' => 1, 'hecho' => ($terminado || ($s === 1 && $inscrito)) ? 1 : 0,
                    'enlace' => $curso['enlace'], 'textoEnlace' => 'Abrir curso', 'externo' => true,
                    'marcable' => $s === $semanaActual, 'auto' => $s === 1 || $terminado, 'manual' => $marcada('curso'),
                ];
            }
            $metas['postulaciones'] = [
                'titulo' => 'Dos postulaciones',
                'detalle' => 'Postúlate a ' . self::POSTULACIONES . ' vacantes. Solo cuentan las postulaciones hechas en ReActiva-T.',
                'meta' => self::POSTULACIONES, 'hecho' => min($hechas, self::POSTULACIONES), 'enlace' => 'vacantes.php', 'textoEnlace' => 'Ver vacantes',
                'marcable' => $s === $semanaActual, 'auto' => true, 'manual' => $marcada('postulaciones'),
            ];
            $apoyo = Apoyo::CATALOGO[$tramites[$s - 1] ?? ''] ?? null;
            if ($apoyo) {
                $metas['tramite'] = [
                    'titulo' => $apoyo['nombre'],
                    'detalle' => 'Reúne: ' . implode(', ', $apoyo['documentos']) . '.',
                    'meta' => 1, 'hecho' => 0, 'enlace' => $apoyo['enlace'], 'textoEnlace' => 'Página oficial', 'externo' => true,
                    'marcable' => $s === $semanaActual, 'auto' => false, 'manual' => $marcada('tramite'),
                ];
            }
            foreach ($metas as &$m) {
                $m['sola'] = $m['hecho'] >= $m['meta'];
                $m['cumplida'] = $m['sola'] || $m['manual'];
            }
            unset($m);
            $semanas[$s] = ['desde' => $desde, 'hasta' => $hasta, 'actual' => $s === $semanaActual, 'pasada' => $hasta < date('Y-m-d'), 'metas' => $metas];
        }
        return $semanas;
    }

    /* Metas cumplidas contra metas de las semanas que ya empezaron */
    public static function avance(array $semanas): array
    {
        $total = $cumplidas = 0;
        foreach ($semanas as $semana) {
            if ($semana['desde'] > date('Y-m-d')) continue;
            $total += count($semana['metas']);
            $cumplidas += count(array_filter($semana['metas'], fn($m) => $m['cumplida']));
        }
        return [$cumplidas, $total];
    }
}
