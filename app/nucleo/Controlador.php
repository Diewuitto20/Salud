<?php
defined('RAIZ') or exit;

/* Base de los controladores: arma la plantilla y entrega los datos a la vista. */
abstract class Controlador
{
    protected function vista(string $vista, array $datos = []): void
    {
        extract($this->datosPlantilla() + $datos);
        require RAIZ . '/app/vistas/plantilla/cabecera.php';
        require RAIZ . '/app/vistas/' . $vista . '.php';
        require RAIZ . '/app/vistas/plantilla/pie.php';
    }

    /* Menú según el tipo de cuenta y la página actual */
    private function datosPlantilla(): array
    {
        $actual = basename($_SERVER['PHP_SELF']);
        $zonaEmpresas = esEmpresa() || in_array($actual, ['empresas.php', 'empresa.php'], true);
        if (esEmpresa()) {
            $enlaces = ['empresa.php' => 'Mi empresa', 'vacantes.php' => 'Vacantes publicadas', 'foro.php' => 'Foro'];
        } elseif ($zonaEmpresas) {
            $enlaces = ['empresas.php' => 'Cómo funciona', 'vacantes.php' => 'Vacantes publicadas'];
        } else {
            $enlaces = ['foro.php' => 'Foro', 'cuestionario.php' => 'Cuestionario', 'cursos.php' => 'Cursos', 'apoyos.php' => 'Apoyos', 'vacantes.php' => 'Vacantes'];
            if (esPersona()) {
                $enlaces = ['avance.php' => 'Mi ruta', 'plan.php' => 'Mi plan', 'valia.php' => 'Mi valía'] + $enlaces;
            }
        }
        if (esLocal() && !usuario()) {
            $enlaces['moderacion.php'] = 'Moderación';
        }
        return ['u' => usuario(), 'actual' => $actual, 'zonaEmpresas' => $zonaEmpresas, 'enlaces' => $enlaces];
    }

    /* Estado elegido por la persona (?estado=…), recordado en la sesión y en su cuenta */
    protected function estadoActual(): string
    {
        if (isset($_GET['estado']) && array_key_exists($_GET['estado'], ESTADOS)) {
            $_SESSION['estado'] = $_GET['estado'];
            if (esPersona()) {
                Usuario::cambiarEstado(usuario()['id'], $_GET['estado']);
            }
        }
        if (empty($_SESSION['estado']) && usuario()) {
            $_SESSION['estado'] = Usuario::estadoDe(usuario()['id']);
        }
        return $_SESSION['estado'] ?? '';
    }

    protected function soloLocal(string $mensaje): void
    {
        if (!esLocal()) {
            http_response_code(403);
            exit($mensaje);
        }
    }
}
