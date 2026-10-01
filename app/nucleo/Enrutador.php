<?php
defined('RAIZ') or exit;

class Enrutador
{
    /* página => [controlador, acción GET, acción POST] */
    private const RUTAS = [
        'index.php' => ['InicioControlador', 'index', null],
        'entrar.php' => ['AccesoControlador', 'formulario', 'procesar'],
        'salir.php' => ['AccesoControlador', 'salir', null],
        'empresas.php' => ['EmpresaControlador', 'presentacion', 'acceso'],
        'empresa.php' => ['EmpresaControlador', 'panel', 'gestionar'],
        'vacantes.php' => ['VacanteControlador', 'index', 'procesar'],
        'foro.php' => ['ForoControlador', 'index', 'procesar'],
        'cuestionario.php' => ['CuestionarioControlador', 'formulario', 'evaluar'],
        'resultado.php' => ['CuestionarioControlador', 'resultado', null],
        'cursos.php' => ['CursoControlador', 'index', 'alternar'],
        'apoyos.php' => ['ApoyoControlador', 'index', 'alternar'],
        'avance.php' => ['RutaControlador', 'index', 'procesar'],
        'valia.php' => ['ValiaControlador', 'index', 'procesar'],
        'ayuda.php' => ['AyudaControlador', 'index', null],
        'privacidad.php' => ['PaginaControlador', 'privacidad', null],
        'seguimiento.php' => ['PaginaControlador', 'seguimiento', null],
        'moderacion.php' => ['ModeracionControlador', 'index', 'procesar'],
        'reiniciar.php' => ['SistemaControlador', 'confirmar', 'reiniciar'],
    ];

    public static function despachar(string $pagina, string $metodo): void
    {
        if (!isset(self::RUTAS[$pagina])) {
            http_response_code(404);
            exit('Página no encontrada.');
        }
        [$clase, $get, $post] = self::RUTAS[$pagina];
        $accion = $metodo === 'POST' && $post ? $post : $get;
        (new $clase())->$accion();
    }
}
