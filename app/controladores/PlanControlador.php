<?php
defined('RAIZ') or exit;

/* «Mi plan»: calendario de 30 días armado con el cuestionario, el estado y los intereses */
class PlanControlador extends Controlador
{
    public function __construct()
    {
        soloPersonas();
        exigir('persona');
    }

    public function index(): void
    {
        $yo = usuario()['id'];
        $evaluaciones = Evaluacion::historialDe($yo);
        $ultima = end($evaluaciones) ?: null;
        $plan = Plan::activo($yo);
        $semanas = $plan ? Plan::calendario($plan) : [];
        [$dia, $semanaActual] = $plan ? Plan::hoy($plan) : [0, 0];

        $this->vista('plan/index', [
            'titulo' => 'Mi plan de 30 días',
            'ultima' => $ultima,
            'plan' => $plan,
            'semanas' => $semanas,
            'dia' => $dia,
            'semanaActual' => $semanaActual,
            'avance' => Plan::avance($semanas),
            'estado' => $this->estadoActual(),
            'intereses' => Plan::INTERESES,
            'resultadoNuevo' => $plan && $ultima && $ultima['creado'] > $plan['creado'],
        ]);
    }

    public function procesar(): void
    {
        validarToken();
        $yo = usuario()['id'];
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'crear') {
            $evaluaciones = Evaluacion::historialDe($yo);
            $ultima = end($evaluaciones);
            $estado = $_POST['estado'] ?? '';
            $interes = $_POST['interes'] ?? '';
            if (!$ultima) redirigir('cuestionario.php');
            if (!array_key_exists($estado, ESTADOS) || !array_key_exists($interes, Plan::INTERESES)) {
                aviso('Elige tu estado y lo que te interesa para armar tu plan.', 'cuidado');
                redirigir('plan.php');
            }
            Usuario::cambiarEstado($yo, $estado);
            $_SESSION['estado'] = $estado;
            Plan::crear($yo, $ultima['nivel'], $estado, $interes);
            aviso('Tu plan de 30 días está listo. Empieza por la semana 1.');
            redirigir('plan.php');
        }

        $plan = Plan::activo($yo);
        if ($plan && $accion === 'marcar') {
            $meta = $_POST['meta'] ?? '';
            if (Plan::alternar($plan, $meta) && $meta === 'respirar') aviso('Bien hecho. Un minuto para ti también cuenta.');
        }
        redirigir('plan.php#semana-actual');
    }
}
