<?php
defined('RAIZ') or exit;

class InicioControlador extends Controlador
{
    public function index(): void
    {
        $this->vista('inicio/index', [
            'titulo' => 'Inicio',
            'notas' => Nota::paraPortada(),
            'vacantes' => Vacante::lugaresLibres(),
        ]);
    }
}
