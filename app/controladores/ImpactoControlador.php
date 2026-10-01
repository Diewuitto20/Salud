<?php
defined('RAIZ') or exit;

/* Resultados verificables para el reto: cifras agregadas y anónimas, en página y en PDF */
class ImpactoControlador extends Controlador
{
    public function index(): void
    {
        $cifras = Impacto::cifras();
        $huella = Impacto::huella($cifras);
        if (($_GET['formato'] ?? '') === 'pdf') {
            $pdf = Impacto::reportePdf($cifras, $huella);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="ReActivaT_Resultados_' . date('Y-m-d') . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
        }
        $this->vista('impacto/index', ['titulo' => 'Resultados del reto', 'c' => $cifras, 'huella' => $huella]);
    }
}
