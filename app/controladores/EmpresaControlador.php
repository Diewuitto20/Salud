<?php
defined('RAIZ') or exit;

/* Presentación para empresas, su acceso y su panel de vacantes */
class EmpresaControlador extends Controlador
{
    private string $modo;

    public function __construct()
    {
        $this->modo = ($_GET['modo'] ?? $_POST['modo'] ?? '') === 'registro' ? 'registro' : 'entrar';
    }

    public function presentacion(string $error = ''): void
    {
        if (esEmpresa()) redirigir('empresa.php');
        $this->vista('empresas/presentacion', ['titulo' => 'Para empresas', 'modo' => $this->modo, 'error' => $error, 'previo' => $_POST]);
    }

    public function acceso(): void
    {
        if (esEmpresa()) redirigir('empresa.php');
        validarToken();
        $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
        $clave = $_POST['clave'] ?? '';

        if ($this->modo === 'entrar') {
            if (bloqueado('login', 20, 15) || bloqueado('login-correo', 5, 15, $correo)) {
                $this->presentacion('Demasiados intentos. Espera 15 minutos y vuelve a intentarlo.');
                return;
            }
            $u = Usuario::buscarPorCorreo($correo, 'empresa');
            if ($u && password_verify($clave, $u['clave'])) {
                limpiarIntentos('login-correo', $correo);
                $this->iniciarSesion((int) $u['id'], $u['nombre']);
            }
            registrarIntento('login');
            registrarIntento('login-correo', $correo);
            $this->presentacion('Correo o contraseña incorrectos.');
            return;
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $error = '';
        if (mb_strlen($nombre) < 2 || tieneGroserias($nombre)) {
            $error = 'Escribe el nombre de tu empresa o negocio.';
        } elseif (mb_strlen($nombre) > 80) {
            $error = 'El nombre de la empresa puede tener hasta 80 caracteres.';
        } elseif ($telefono !== '' && !preg_match('/^[0-9 +()-]{7,30}$/', $telefono)) {
            $error = 'Escribe el teléfono solo con números (de 7 a 30 caracteres).';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 120) {
            $error = 'Escribe un correo válido.';
        } elseif (mb_strlen($clave) < 8 || mb_strlen($clave) > 72) {
            $error = 'La contraseña debe tener de 8 a 72 caracteres.';
        } elseif (!isset($_POST['privacidad'])) {
            $error = 'Necesitas aceptar el aviso de privacidad.';
        } elseif (excedeLimite('registro', 5, 60)) {
            $error = 'Se crearon demasiadas cuentas desde esta conexión. Intenta de nuevo en una hora.';
        } else {
            try {
                $id = Usuario::crearEmpresa($nombre, $correo, $telefono ?: null, $clave);
                aviso('Tu empresa ya está registrada. Publica tu primera vacante.');
                $this->iniciarSesion($id, $nombre);
            } catch (PDOException $ex) {
                if (!esDuplicado($ex)) throw $ex;
                $error = 'Ya existe una cuenta con ese correo.';
                $this->modo = 'entrar';
            }
        }
        $this->presentacion($error);
    }

    public function panel(): void
    {
        $this->exigirEmpresa();
        $yo = usuario()['id'];
        $this->vista('empresas/panel', [
            'titulo' => 'Mi empresa',
            'vacantes' => Vacante::deEmpresa($yo),
            'postulaciones' => Postulacion::deEmpresa($yo),
            'estadosPostulacion' => Postulacion::ESTADOS,
            'periodos' => Vacante::PERIODOS,
        ]);
    }

    public function gestionar(): void
    {
        $this->exigirEmpresa();
        validarToken();
        $yo = usuario()['id'];
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'publicar') {
            $datos = [
                'titulo' => trim($_POST['titulo'] ?? ''),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'ubicacion' => trim($_POST['ubicacion'] ?? ''),
                'modalidad' => in_array($_POST['modalidad'] ?? '', Vacante::MODALIDADES, true) ? $_POST['modalidad'] : '',
                'estado' => array_key_exists($_POST['estado'] ?? '', ESTADOS) ? $_POST['estado'] : '',
                'monto' => (int) preg_replace('/\D/', '', $_POST['sueldo'] ?? ''),
                'periodo' => in_array($_POST['periodo'] ?? '', Vacante::PERIODOS, true) ? $_POST['periodo'] : '',
                'prestaciones' => isset($_POST['prestaciones']) ? 1 : 0,
                'cupos' => filter_var($_POST['cupos'] ?? '', FILTER_VALIDATE_INT),
            ];
            $error = Vacante::validar($datos);
            if ($error) {
                aviso($error, 'cuidado');
            } else {
                Vacante::publicar($yo, $datos);
                aviso($datos['prestaciones'] ? 'Tu vacante ya está publicada. Gracias por abrir un lugar.'
                    : 'Tu vacante está publicada, pero aparecerá con el aviso «Sin prestaciones de ley declaradas». Si las ofreces, publícala de nuevo marcando la casilla.', $datos['prestaciones'] ? 'ok' : 'cuidado');
            }
        }
        if ($accion === 'estado' && in_array($_POST['estado'] ?? '', Postulacion::ESTADOS, true)) {
            Postulacion::cambiarEstado((int) $_POST['vacante'], (int) $_POST['persona'], $yo, $_POST['estado']);
            aviso('Actualizamos el estado. La persona lo verá en su ruta.');
        }
        if ($accion === 'cerrar') {
            Vacante::cerrar((int) $_POST['vacante'], $yo);
            aviso('La vacante se cerró.');
        }
        redirigir('empresa.php');
    }

    private function exigirEmpresa(): void
    {
        if (!esEmpresa()) redirigir('empresas.php#acceso');
    }

    private function iniciarSesion(int $id, string $nombre): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario'] = ['id' => $id, 'tipo' => 'empresa', 'nombre' => $nombre];
        redirigir('empresa.php');
    }
}
