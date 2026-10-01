<?php
defined('RAIZ') or exit;

/* Cuestionario de afectación: preguntas, calificación y resultados guardados */
class Evaluacion extends Modelo
{
    public const PREGUNTAS = [
        'estres' => [
            'Sentir que no puedes controlar las cosas importantes de tu vida',
            'Sentir presión o agobio por tus gastos y pendientes',
            'Tensión en el cuerpo, irritabilidad o dolores de cabeza o cuello',
        ],
        'ansiedad' => [
            'Nervios, ansiedad o sentirte al límite',
            'No poder dejar de preocuparte por el futuro o por conseguir trabajo',
            'Dificultad para relajarte o para dormir por pensar en tus problemas',
        ],
        'autoestima' => [
            'Sentir que vales menos desde que perdiste tu empleo',
            'Sentirte un fracaso o que le has fallado a tu familia',
            'Dudar de tus capacidades para conseguir o hacer un buen trabajo',
        ],
        'familia' => [
            'Discusiones o tensión en casa por el dinero o por tu situación',
            'Sentir que tu familia no te entiende o no te apoya',
            'Alejarte de tu familia o amistades, o evitar convivir con ellos',
        ],
    ];
    public const OPCIONES = ['Nunca', 'Varios días', 'Más de la mitad de los días', 'Casi todos los días'];
    public const MESES = ['0-1' => 'Menos de 1 mes', '1-3' => '1 a 3 meses', '3-6' => '3 a 6 meses', '6-12' => '6 a 12 meses', '12+' => 'Más de un año'];
    public const IMPACTOS = ['Nada', 'Poco', 'Bastante', 'Mucho'];

    /* Mensaje y ritmo de cursos recomendado para cada nivel */
    public const NIVELES = [
        'minimo' => ['Te afecta poco', 'Estás llevando esta etapa con bastante fuerza. Aprovecha la energía para prepararte y avanzar.', 2],
        'leve' => ['Te afecta un poco', 'Es normal sentir el golpe. Cuida tu rutina, habla con alguien y date pasos pequeños.', 2],
        'moderado' => ['Te está afectando', 'Lo que sientes ya pesa en tu día a día. Hablar con un profesional te puede ayudar mucho, y no tienes que esperar a sentirte peor.', 1],
        'grave' => ['Te está afectando mucho', 'Tus respuestas muestran que estás pasando por un momento muy difícil. Te recomendamos buscar ayuda profesional pronto.', 0],
    ];

    /*
     * Cada dimensión suma de 0 a 9. Grave: crisis, dos dimensiones altas o 24 puntos o más;
     * moderado: una dimensión alta o 15 o más; leve: 8 o más.
     */
    public static function calificar(array $respuestas): array
    {
        $puntos = [];
        $casiDiario = 0;
        foreach (self::PREGUNTAS as $clave => $lista) {
            $puntos[$clave] = 0;
            foreach ($lista as $n => $p) {
                $valor = (int) ($respuestas[$clave . $n] ?? 0);
                $puntos[$clave] += max(0, min(3, $valor));
                if ($valor === 3) $casiDiario++;
            }
        }
        $total = array_sum($puntos);
        $altos = count(array_filter($puntos, fn($p) => $p >= 7));
        $crisis = hayCrisis($respuestas['relato'] ?? '');

        if ($crisis || $altos >= 2 || $total >= 24) $nivel = 'grave';
        elseif ($altos >= 1 || $total >= 15) $nivel = 'moderado';
        elseif ($total >= 8) $nivel = 'leve';
        else $nivel = 'minimo';

        $confirmado = self::animo($respuestas['animo_confirmado'] ?? null);
        $impacto = max(0, min(3, (int) ($respuestas['impacto'] ?? 0)));
        return compact('puntos', 'total', 'nivel', 'crisis', 'impacto', 'confirmado', 'casiDiario');
    }

    public static function guardar(int $usuario, array $resultado, array $respuestas): void
    {
        $mes = array_key_exists($respuestas['meses'] ?? '', self::MESES) ? $respuestas['meses'] : '0-1';
        $detectado = self::animo($respuestas['animo_detectado'] ?? null);
        $valores = [$mes, $resultado['impacto'], ...array_values($resultado['puntos']), $resultado['total'], $resultado['nivel'], $detectado, $resultado['confirmado']];
        $hoy = self::consultar('SELECT id FROM evaluaciones WHERE usuario_id = ? AND creado > NOW() - INTERVAL 1 DAY ORDER BY id DESC LIMIT 1', [$usuario])->fetchColumn();
        if ($hoy) {
            self::consultar('UPDATE evaluaciones SET meses = ?, impacto = ?, estres = ?, ansiedad = ?, autoestima = ?, familia = ?, total = ?, nivel = ?,
                animo_detectado = ?, animo_confirmado = ? WHERE id = ?', [...$valores, $hoy]);
            return;
        }
        self::consultar('INSERT INTO evaluaciones (usuario_id, meses, impacto, estres, ansiedad, autoestima, familia, total, nivel, animo_detectado, animo_confirmado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$usuario, ...$valores]);
    }

    /* Ánimo del medidor: 1 a 5, cualquier otro valor se descarta */
    private static function animo($valor): ?int
    {
        $n = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
        return $n === false ? null : $n;
    }

    public static function historialDe(int $usuario): array
    {
        return self::consultar('SELECT * FROM evaluaciones WHERE usuario_id = ? AND total IS NOT NULL ORDER BY creado', [$usuario])->fetchAll();
    }
}
