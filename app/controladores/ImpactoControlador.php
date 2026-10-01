<?php
defined('RAIZ') or exit;

/* Resultados del reto: cifras agregadas y anónimas, reporte PDF con código y verificación de reportes */
class ImpactoControlador extends Controlador
{
    public function index(): void
    {
        if (($_GET['formato'] ?? '') === 'pdf') {
            if (excedeLimite('reporte', 10, 60)) {
                aviso('Ya se generaron varios reportes desde esta conexión. Intenta de nuevo en una hora.', 'cuidado');
                redirigir('impacto.php');
            }
            $cifras = Impacto::cifras();
            $codigo = Impacto::registrarReporte($cifras);
            $pdf = Impacto::reportePdf($cifras, $codigo);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="ReActivaT_Resultados_' . date('Y-m-d') . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
        }

        $codigo = trim($_GET['codigo'] ?? '');
        $reporte = null;
        if ($codigo !== '') {
            $reporte = preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/i', $codigo) && !excedeLimite('verificar', 30, 60)
                ? Impacto::buscarReporte($codigo) : null;
        }
        $this->vista('impacto/index', [
            'titulo' => 'Resultados del reto',
            'c' => $reporte['cifras'] ?? Impacto::cifras(),
            'codigo' => $codigo,
            'reporte' => $reporte,
        ]);
    }
}
