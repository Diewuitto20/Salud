<?php
defined('RAIZ') or exit;

/* Páginas informativas */
class PaginaControlador extends Controlador
{
    public function privacidad(): void
    {
        $this->vista('paginas/privacidad', [
            'titulo' => 'Aviso de privacidad',
            'responsable' => 'Equipo ReActiva-T (proyecto HackaTec 2026)',
            'correoContacto' => 'l23240012@smartin.tecnm.mx',
        ]);
    }

    /* El antiguo panel de seguimiento ya no existe: cada persona ve solo su ruta */
    public function seguimiento(): void
    {
        redirigir(esPersona() ? 'avance.php' : 'index.php');
    }
}
