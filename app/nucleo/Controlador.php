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
        if (esModerador()) {
            $enlaces['moderacion.php'] = 'Moderación';
        }
        return ['u' => usuario(), 'actual' => $actual, 'zonaEmpresas' => $zonaEmpresas, 'enlaces' => $enlaces];
    }

    /* Estado elegido por la persona (?estado=…), recordado en la sesión; en la cuenta solo se guarda por POST (registro y plan) */
    protected function estadoActual(): string
    {
        if (isset($_GET['estado']) && array_key_exists($_GET['estado'], ESTADOS)) {
            $_SESSION['estado'] = $_GET['estado'];
        }
        if (empty($_SESSION['estado']) && usuario()) {
            $_SESSION['estado'] = Usuario::estadoDe(usuario()['id']);
        }
        return $_SESSION['estado'] ?? '';
    }

    /* Moderación y reinicio: piden la clave de moderación; sin clave configurada quedan apagados */
    protected function exigirModerador(): void
    {
        if (claveModeracion() === '') {
            http_response_code(403);
            exit('La moderación está desactivada: falta configurar la variable MODERACION_CLAVE (mínimo 12 caracteres).');
        }
        if (esModerador()) return;
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'entrar_moderacion') {
            validarToken();
            if (excedeLimite('moderacion', 5, 15)) {
                $error = 'Demasiados intentos. Espera 15 minutos.';
            } elseif (hash_equals(claveModeracion(), (string) ($_POST['clave'] ?? ''))) {
                session_regenerate_id(true);
                $_SESSION['moderador'] = true;
                redirigir(basename($_SERVER['PHP_SELF']));
            } else {
                $error = 'Clave incorrecta.';
            }
        }
        $this->vista('moderacion/entrar', ['titulo' => 'Acceso de moderación', 'error' => $error]);
        exit;
    }
}
