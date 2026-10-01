<?php
defined('RAIZ') or exit;

class VacanteControlador extends Controlador
{
    public function index(): void
    {
        $estado = $this->estadoActual();
        $modalidad = in_array($_GET['modalidad'] ?? '', Vacante::MODALIDADES, true) ? $_GET['modalidad'] : '';
        $buscar = trim($_GET['q'] ?? '');
        $this->vista('vacantes/index', [
            'titulo' => 'Vacantes',
            'estado' => $estado,
            'modalidad' => $modalidad,
            'buscar' => $buscar,
            'vacantes' => Vacante::buscar(usuario()['id'] ?? 0, $estado, $modalidad, $buscar, esEmpresa()),
            'avisando' => esPersona() && Preferencia::avisaVacantes(usuario()['id']),
        ]);
    }

    public function procesar(): void
    {
        exigir('persona');
        validarToken();
        $yo = usuario()['id'];

        if (($_POST['accion'] ?? '') === 'avisos') {
            $activar = (bool) (int) ($_POST['activar'] ?? 0);
            Preferencia::avisarVacantes($yo, $activar);
            aviso($activar ? 'Listo. Cuando haya vacantes nuevas en tu estado te avisaremos en <a href="avance.php">Mi ruta</a>.' : 'Ya no te avisaremos de vacantes nuevas.');
            redirigir('vacantes.php');
        }

        $vacante = (int) ($_POST['vacante'] ?? 0);
        $contacto = trim($_POST['contacto'] ?? '');
        if ($contacto === '') {
            aviso('Déjanos un teléfono o correo para que la empresa te contacte.', 'cuidado');
        } else {
            $resultado = Postulacion::crear($vacante, $yo, $contacto, trim($_POST['mensaje'] ?? ''));
            if ($resultado === 'ok') {
                aviso('¡Listo! La empresa recibió tu postulación. Puedes ver su respuesta en <a href="avance.php">Mi ruta</a>.');
            } elseif ($resultado === 'repetida') {
                aviso('Ya te habías postulado a esta vacante. Puedes ver cómo va en <a href="avance.php">Mi ruta</a>.', 'cuidado');
            } else {
                aviso('Esta vacante ya no tiene lugares disponibles.', 'cuidado');
            }
        }
        redirigir('vacantes.php#vacante-' . $vacante);
    }
}
