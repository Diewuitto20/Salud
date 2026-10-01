<?php
defined('RAIZ') or exit;

class CursoControlador extends Controlador
{
    private string $tipo;

    public function __construct()
    {
        $this->tipo = ($_GET['tipo'] ?? '') === 'emprender' ? 'emprender' : 'oficio';
    }

    public function index(): void
    {
        $estado = $this->estadoActual();
        $this->vista('cursos/index', [
            'titulo' => 'Cursos',
            'tipo' => $this->tipo,
            'estado' => $estado,
            'ritmos' => Curso::RITMOS,
            'cursos' => Curso::catalogo($this->tipo, $estado),
            'guardados' => esPersona() ? Curso::idsGuardados(usuario()['id']) : [],
        ]);
    }

    public function alternar(): void
    {
        $this->estadoActual();
        exigir('persona');
        validarToken();
        $curso = (int) ($_POST['curso'] ?? 0);
        if (Curso::alternarGuardado(usuario()['id'], $curso)) {
            aviso('Guardado en <a href="avance.php#cursos">Mi ruta</a>. Ahí puedes marcar cuando te inscribas o lo termines.');
        }
        redirigir('cursos.php?tipo=' . $this->tipo . '#curso-' . $curso);
    }
}
