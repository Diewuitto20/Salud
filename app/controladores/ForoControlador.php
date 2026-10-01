<?php
defined('RAIZ') or exit;

class ForoControlador extends Controlador
{
    public function index(): void
    {
        $orden = ($_GET['orden'] ?? '') === 'apoyadas' ? 'apoyadas' : '';
        $borrador = $_SESSION['borrador'] ?? '';
        unset($_SESSION['borrador']);
        $this->vista('foro/index', [
            'titulo' => 'Foro',
            'orden' => $orden,
            'borrador' => $borrador,
            'colores' => Nota::COLORES,
            'notas' => Nota::muro($orden === 'apoyadas', usuario()['id'] ?? 0),
            'comentarios' => Nota::comentarios(),
        ]);
    }

    public function procesar(): void
    {
        exigir();
        validarToken();
        $yo = usuario()['id'];
        $accion = $_POST['accion'] ?? '';
        $texto = trim($_POST['texto'] ?? '');
        $nota = (int) ($_POST['nota'] ?? 0);
        $destino = 'foro.php';

        if ($texto !== '' && tieneGroserias($texto)) {
            $_SESSION['borrador'] = $texto;
            aviso('Tu mensaje tiene palabras ofensivas. Cámbialas para que este siga siendo un espacio seguro.', 'cuidado');
            redirigir($accion === 'nota' ? 'foro.php#nueva-nota' : 'foro.php#nota-' . $nota);
        }
        if ($accion === 'nota' && $texto !== '' && esTextoBasura($texto)) {
            $_SESSION['borrador'] = $texto;
            aviso('Escribe una frase completa, aunque sea corta. Palabras breves y sinceras ayudan mucho.', 'cuidado');
            redirigir('foro.php#nueva-nota');
        }

        if ($accion === 'denunciar') {
            Nota::denunciar($nota, $yo);
            aviso('Gracias por avisarnos. El equipo revisará la nota; si varias personas la reportan, se oculta mientras tanto.');
        }
        if ($accion === 'nota' && $texto !== '') {
            $color = in_array($_POST['color'] ?? '', Nota::COLORES, true) ? $_POST['color'] : 'amarillo';
            Nota::crear($yo, $texto, $color, isset($_POST['sensible']) || temaSensible($texto));
            hayCrisis($texto) ? aviso(avisoCrisis(), 'cuidado') : aviso('Tu nota ya está en el muro. Gracias por compartir.');
        }
        if ($accion === 'apoyo') {
            Nota::alternarApoyo($nota, $yo);
            $destino .= '#nota-' . $nota;
        }
        if ($accion === 'comentario' && $texto !== '') {
            Nota::comentar($nota, $yo, $texto);
            if (hayCrisis($texto)) aviso(avisoCrisis(), 'cuidado');
            $destino .= '#nota-' . $nota;
        }
        redirigir($destino);
    }
}
