<?php
defined('RAIZ') or exit;

/* Apoyos del gobierno para quien perdió su empleo */
class ApoyoControlador extends Controlador
{
    public function index(): void
    {
        $estado = $this->estadoActual();
        $this->vista('apoyos/index', [
            'titulo' => 'Apoyos del gobierno',
            'estado' => $estado,
            'visibles' => Apoyo::visibles($estado),
            'guardados' => esPersona() ? Apoyo::clavesGuardadas(usuario()['id']) : [],
        ]);
    }

    public function alternar(): void
    {
        $this->estadoActual();
        exigir('persona');
        validarToken();
        $apoyo = $_POST['apoyo'] ?? '';
        if (Apoyo::existe($apoyo) && Apoyo::alternarGuardado(usuario()['id'], $apoyo)) {
            aviso('Guardado en <a href="avance.php#tramites">Mi ruta</a> con la lista de documentos que necesitas.');
        }
        redirigir('apoyos.php#apoyo-' . $apoyo);
    }
}
