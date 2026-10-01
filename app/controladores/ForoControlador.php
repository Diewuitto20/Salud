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
            'notas' => $notas = Nota::muro($orden === 'apoyadas', usuario()['id'] ?? 0),
            'comentarios' => Nota::comentarios($notas),
        ]);
    }

    public function procesar(): void
    {
        exigir('persona');
        validarToken();
        $yo = usuario()['id'];
        $accion = $_POST['accion'] ?? '';
        $texto = trim($_POST['texto'] ?? '');
        $nota = (int) ($_POST['nota'] ?? 0);
        $destino = 'foro.php';

        if ($texto !== '' && agresionEn($texto) !== null) {
            $_SESSION['borrador'] = $accion === 'nota' ? $texto : '';
            aviso('Tu mensaje puede lastimar a alguien que ya está pasando por un momento difícil, así que no se publicó. Aquí nos apoyamos: escribe como te gustaría que te hablaran.', 'cuidado');
            redirigir($accion === 'nota' ? 'foro.php#nueva-nota' : 'foro.php#nota-' . $nota);
        }
        if ($texto !== '' && ($groseria = groseriaEn($texto)) !== null) {
            $_SESSION['borrador'] = $texto;
            aviso('Tu mensaje tiene lenguaje ofensivo («' . e($groseria) . '»). Cámbialo para que este siga siendo un espacio seguro.', 'cuidado');
            redirigir($accion === 'nota' ? 'foro.php#nueva-nota' : 'foro.php#nota-' . $nota);
        }
        if ($accion === 'nota' && $texto !== '' && esTextoBasura($texto)) {
            $_SESSION['borrador'] = $texto;
            aviso('Escribe una frase completa, aunque sea corta. Palabras breves y sinceras ayudan mucho.', 'cuidado');
            redirigir('foro.php#nueva-nota');
        }

        if ($accion === 'denunciar') {
            if (excedeLimite('denuncia', 10, 1440, 'u' . $yo)) {
                aviso('Ya enviaste muchos reportes hoy. El equipo de moderación los está revisando.', 'cuidado');
                redirigir('foro.php#nota-' . $nota);
            }
            Nota::denunciar($nota, $yo);
            aviso('Gracias por avisarnos. El equipo revisará la nota; si varias personas la reportan, se oculta mientras tanto.');
        }
        if ($accion === 'nota' && $texto !== '') {
            $color = in_array($_POST['color'] ?? '', Nota::COLORES, true) ? $_POST['color'] : 'amarillo';
            Nota::crear($yo, $texto, $color, isset($_POST['sensible']) || temaSensible($texto));
            hayCrisis($texto) ? aviso(avisoCrisis(), 'cuidado') : aviso('Tu nota ya está en el muro. Gracias por compartir.');
        }
        if ($accion === 'borrar_nota') {
            Nota::borrarPropia($nota, $yo) ? aviso('Borraste tu nota.') : aviso('Solo puedes borrar tus propias notas.', 'cuidado');
        }
        if ($accion === 'borrar_comentario') {
            $destino .= '#nota-' . $nota;
            Nota::borrarComentarioPropio((int) ($_POST['comentario'] ?? 0), $yo) ? aviso('Borraste tu respuesta.') : aviso('Solo puedes borrar tus propias respuestas.', 'cuidado');
        }
        if ($accion === 'apoyo') {
            Nota::alternarApoyo($nota, $yo);
            $destino .= '#nota-' . $nota;
        }
        if ($accion === 'comentario' && $texto !== '') {
            $destino .= '#nota-' . $nota;
            if (sinSentido($texto)) {
                aviso('Escribe unas palabras con sentido para responder.', 'cuidado');
            } elseif (!Nota::comentar($nota, $yo, $texto, temaSensible($texto))) {
                aviso('Esa nota ya no está disponible.', 'cuidado');
            } elseif (hayCrisis($texto)) {
                aviso(avisoCrisis(), 'cuidado');
            }
        }
        redirigir($destino);
    }
}
