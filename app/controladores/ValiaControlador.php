<?php
defined('RAIZ') or exit;

/* «Mi mapa de valía»: logros y cualidades que solo ve su dueño */
class ValiaControlador extends Controlador
{
    public function __construct()
    {
        exigir('persona');
    }

    public function index(): void
    {
        $this->vista('valia/index', [
            'titulo' => 'Mi mapa de valía',
            'items' => Valia::deUsuario(usuario()['id']),
            'sugerencias' => Valia::SUGERENCIAS,
        ]);
    }

    public function procesar(): void
    {
        validarToken();
        $yo = usuario()['id'];
        if (($_POST['accion'] ?? '') === 'agregar') {
            $tipo = ($_POST['tipo'] ?? '') === 'cualidad' ? 'cualidad' : 'logro';
            $texto = trim($_POST['texto'] ?? '');
            if (mb_strlen($texto) < 3) {
                aviso('Escribe al menos unas palabras.', 'cuidado');
            } elseif (tieneGroserias($texto)) {
                aviso('Escríbelo sin palabras ofensivas, por favor.', 'cuidado');
            } else {
                Valia::agregar($yo, $tipo, $texto);
                aviso($tipo === 'logro' ? 'Anotado. Ese logro es tuyo y nadie te lo quita.' : 'Anotado. Esa cualidad sigue contigo, con o sin empleo.');
            }
        }
        if (($_POST['accion'] ?? '') === 'borrar') {
            Valia::borrar((int) $_POST['id'], $yo);
        }
        redirigir('valia.php');
    }
}
