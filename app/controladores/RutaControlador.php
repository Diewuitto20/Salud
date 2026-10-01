<?php
defined('RAIZ') or exit;

/* «Mi ruta»: el avance personal y privado de cada persona */
class RutaControlador extends Controlador
{
    public function __construct()
    {
        soloPersonas();
        exigir('persona');
    }

    public function index(): void
    {
        if (($_GET['formato'] ?? '') === 'pdf') {
            if (excedeLimite('reporte_personal', 10, 60)) {
                aviso('Ya descargaste varios reportes. Intenta de nuevo en una hora.', 'cuidado');
                redirigir('avance.php');
            }
            $pdf = Reporte::personal(usuario());
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="ReActivaT_Mi_reporte_' . date('Y-m-d') . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
            header('Cache-Control: no-store, private');
            echo $pdf;
            exit;
        }

        $yo = usuario()['id'];
        $evaluaciones = Evaluacion::historialDe($yo);
        $ultima = end($evaluaciones) ?: null;
        $preferencias = Preferencia::deUsuario($yo);
        $estadoUsuario = $this->estadoActual();
        $novedades = $preferencias['avisar_vacantes'] ? Vacante::nuevasDesde($preferencias['ultima_visita'] ?? '2000-01-01', $estadoUsuario) : [];
        Preferencia::registrarVisita($yo);
        $plan = Plan::activo($yo);

        $this->vista('ruta/index', [
            'titulo' => 'Mi ruta',
            'evaluaciones' => $evaluaciones,
            'ultima' => $ultima,
            'primera' => $evaluaciones[0] ?? null,
            'dias' => $ultima ? (int) floor((time() - strtotime($ultima['creado'])) / 86400) : null,
            'postulaciones' => Postulacion::deUsuario($yo),
            'cursos' => Curso::guardadosDe($yo),
            'estadosCurso' => Curso::ESTADOS_AVANCE,
            'comunidad' => Nota::comunidadDe($yo),
            'tramites' => Apoyo::tramitesDe($yo),
            'valia' => Valia::conteo($yo),
            'preferencias' => $preferencias,
            'novedades' => $novedades,
            'plan' => $plan,
            'avancePlan' => $plan ? Plan::avance(Plan::calendario($plan)) : [0, 0],
            'diaPlan' => $plan ? Plan::hoy($plan)[0] : 0,
        ]);
    }

    public function procesar(): void
    {
        validarToken();
        $yo = usuario()['id'];
        $accion = $_POST['accion'] ?? '';
        if ($accion === 'avisos') {
            Preferencia::guardarAvisos($yo, isset($_POST['vacantes']), isset($_POST['cursos']));
            aviso('Guardamos tus preferencias de avisos.');
            redirigir('avance.php#avisos');
        }
        if ($accion === 'quitar_tramite') {
            Apoyo::quitar($yo, $_POST['apoyo'] ?? '');
            redirigir('avance.php#tramites');
        }
        $curso = (int) ($_POST['curso'] ?? 0);
        $estado = $_POST['estado'] ?? '';
        if ($estado === 'quitar') {
            Curso::quitar($yo, $curso);
        } elseif (in_array($estado, Curso::ESTADOS_AVANCE, true)) {
            Curso::cambiarAvance($yo, $curso, $estado);
            if ($estado === 'Terminado') aviso('¡Felicidades por terminar tu curso! Es un paso enorme.');
        }
        redirigir('avance.php#cursos');
    }
}
