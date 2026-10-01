<?php
defined('RAIZ') or exit;

/* Inicio de sesión, registro y salida de personas */
class AccesoControlador extends Controlador
{
    private string $volver;
    private string $modo;

    public function __construct()
    {
        $this->volver = preg_match('/^[a-z]+\.php(\?[\w=&%-]*)?$/', $_GET['volver'] ?? '') ? $_GET['volver'] : '';
        $this->modo = ($_GET['modo'] ?? $_POST['modo'] ?? '') === 'registro' ? 'registro' : 'entrar';
        if (($_GET['tipo'] ?? '') === 'empresa') {
            redirigir('empresas.php#acceso');
        }
    }

    public function formulario(string $error = ''): void
    {
        $modo = $this->modo;
        $volver = $this->volver;
        $this->vista('acceso/entrar', [
            'titulo' => 'Entrar',
            'modo' => $modo,
            'error' => $error,
            'previo' => $_POST,
            'enlace' => fn(array $cambios) => 'entrar.php?' . http_build_query(array_merge(['modo' => $modo, 'volver' => $volver], $cambios)),
        ]);
    }

    public function procesar(): void
    {
        validarToken();
        $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
        $clave = $_POST['clave'] ?? '';

        if ($this->modo === 'entrar') {
            $u = Usuario::buscarPorCorreo($correo, 'persona');
            if ($u && password_verify($clave, $u['clave'])) {
                $this->iniciarSesion($u);
            }
            $this->formulario('Correo o contraseña incorrectos. Si tienes una cuenta de empresa, entra desde «Para empresas».');
            return;
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $estado = array_key_exists($_POST['estado'] ?? '', ESTADOS) ? $_POST['estado'] : null;
        $error = '';
        if (!preg_match('/^[\p{L}\p{N} ]{3,15}$/u', $nombre)) {
            $error = 'Tu alias debe tener de 3 a 15 letras o números, sin símbolos.';
        } elseif (tieneGroserias($nombre)) {
            $error = 'Elige un alias sin palabras ofensivas, por favor.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = 'Escribe un correo válido.';
        } elseif (mb_strlen($clave) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif (!isset($_POST['privacidad'])) {
            $error = 'Para crear tu cuenta necesitas aceptar el aviso de privacidad.';
        } else {
            try {
                $id = Usuario::crearPersona($nombre, $correo, $clave, $estado);
                $this->iniciarSesion(['id' => $id, 'tipo' => 'persona', 'nombre' => $nombre, 'estado' => $estado]);
            } catch (PDOException $ex) {
                $error = 'Ya existe una cuenta con ese correo. Inicia sesión.';
                $this->modo = 'entrar';
            }
        }
        $this->formulario($error);
    }

    public function salir(): void
    {
        $_SESSION = [];
        setcookie(session_name(), '', time() - 3600, '/');
        session_destroy();
        header('Location: index.php?salida=1');
    }

    private function iniciarSesion(array $u): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario'] = ['id' => (int) $u['id'], 'tipo' => $u['tipo'], 'nombre' => $u['nombre']];
        $_SESSION['estado'] = $u['estado'] ?? '';
        redirigir($this->volver ?: 'avance.php');
    }
}
