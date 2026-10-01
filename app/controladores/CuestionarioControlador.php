<?php
defined('RAIZ') or exit;

class CuestionarioControlador extends Controlador
{
    public function __construct()
    {
        soloPersonas();
    }

    public function formulario(): void
    {
        $this->vista('cuestionario/formulario', [
            'titulo' => 'Cuestionario',
            'preguntas' => Evaluacion::PREGUNTAS,
            'opciones' => Evaluacion::OPCIONES,
            'meses' => Evaluacion::MESES,
            'impactos' => Evaluacion::IMPACTOS,
        ]);
    }

    public function evaluar(): void
    {
        validarToken();
        $resultado = Evaluacion::calificar($_POST);
        if (esPersona()) {
            Evaluacion::guardar(usuario()['id'], $resultado, $_POST);
        }
        $_SESSION['resultado'] = $resultado;
        redirigir('resultado.php');
    }

    public function resultado(): void
    {
        $r = $_SESSION['resultado'] ?? null;
        if (!$r || !isset($r['puntos'])) redirigir('cuestionario.php');
        [$encabezado, $mensaje, $ritmo] = Evaluacion::NIVELES[$r['nivel']];
        $this->vista('cuestionario/resultado', [
            'titulo' => 'Tu resultado',
            'r' => $r,
            'encabezado' => $encabezado,
            'mensaje' => $mensaje,
            'ritmo' => $ritmo,
            'oficios' => Curso::recomendados('oficio', $ritmo, 3),
            'emprender' => Curso::recomendados('emprender', $ritmo, 2),
            'vacantes' => Vacante::conLugares(3),
            'nombresAnimo' => [1 => 'muy mal', 2 => 'mal', 3 => 'más o menos', 4 => 'bien', 5 => 'muy bien'],
            'ritmos' => Curso::RITMOS,
        ]);
    }
}
