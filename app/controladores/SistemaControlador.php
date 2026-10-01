<?php
defined('RAIZ') or exit;

/* Borrado de datos de prueba; solo desde la computadora del servidor */
class SistemaControlador extends Controlador
{
    public function __construct()
    {
        $this->soloLocal('Esta página solo se puede abrir desde la computadora donde corre el servidor.');
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
