<?php
defined('RAIZ') or exit;

/* Revisión de notas reportadas; solo con la clave de moderación */
class ModeracionControlador extends Controlador
{
    public function __construct()
    {
        $this->exigirModerador();
    }

    public function index(): void
    {
        $this->vista('moderacion/index', ['titulo' => 'Moderación', 'denunciadas' => Nota::reportadas()]);
    }

    public function procesar(): void
    {
        validarToken();
        $nota = (int) ($_POST['nota'] ?? 0);
        if (($_POST['accion'] ?? '') === 'restaurar') {
            Nota::restaurar($nota);
            aviso('La nota volvió a publicarse.');
        }
        if (($_POST['accion'] ?? '') === 'eliminar') {
            Nota::eliminar($nota);
            aviso('La nota se eliminó.');
        }
        redirigir('moderacion.php');
    }
}
