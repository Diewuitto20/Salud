<?php
defined('RAIZ') or exit;

/* Borrado de datos de prueba; solo con la clave de moderación */
class SistemaControlador extends Controlador
{
    public function __construct()
    {
        $this->exigirModerador();
    }

    public function confirmar(): void
    {
        $this->vista('sistema/reiniciar', ['titulo' => 'Reiniciar datos']);
    }

    public function reiniciar(): void
    {
        validarToken();
        Usuario::reiniciarTodo();
        session_destroy();
        session_start();
        aviso('Listo. Se borraron cuentas, notas, cuestionarios y vacantes. El catálogo de cursos se conserva.');
        redirigir('index.php');
    }
}
