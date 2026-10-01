<?php
defined('RAIZ') or exit;

class AyudaControlador extends Controlador
{
    public function index(): void
    {
        $this->vista('ayuda/index', [
            'titulo' => 'Ayuda',
            'estado' => $this->estadoActual(),
            'centros' => CentroAyuda::CENTROS,
            'enlacesEstado' => CentroAyuda::ENLACES,
        ]);
    }
}
