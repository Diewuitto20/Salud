<?php
require 'includes/funciones.php';
if (esEmpresa()) redirigir('empresa.php');
$titulo = 'Para empresas';
$modo = ($_GET['modo'] ?? $_POST['modo'] ?? '') === 'registro' ? 'registro' : 'entrar';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarToken();
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
    $clave = $_POST['clave'] ?? '';
    if ($modo === 'entrar') {
        $st = bd()->prepare("SELECT * FROM usuarios WHERE correo = ? AND tipo = 'empresa'");
        $st->execute([$correo]);
        $u = $st->fetch();
        if ($u && password_verify($clave, $u['clave'])) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = ['id' => (int) $u['id'], 'tipo' => 'empresa', 'nombre' => $u['nombre']];
            redirigir('empresa.php');
        }
        $error = 'Correo o contraseña incorrectos.';
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        if (mb_strlen($nombre) < 2 || tieneGroserias($nombre)) {
            $error = 'Escribe el nombre de tu empresa o negocio.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = 'Escribe un correo válido.';
        } elseif (mb_strlen($clave) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif (!isset($_POST['privacidad'])) {
            $error = 'Necesitas aceptar el aviso de privacidad.';
        } else {
            try {
                bd()->prepare("INSERT INTO usuarios (tipo, nombre, correo, telefono, clave) VALUES ('empresa', ?, ?, ?, ?)")
                    ->execute([$nombre, $correo, $telefono ?: null, password_hash($clave, PASSWORD_DEFAULT)]);
                session_regenerate_id(true);
                $_SESSION['usuario'] = ['id' => (int) bd()->lastInsertId(), 'tipo' => 'empresa', 'nombre' => $nombre];
                aviso('Tu empresa ya está registrada. Publica tu primera vacante.');
                redirigir('empresa.php');
            } catch (PDOException $ex) {
                $error = 'Ya existe una cuenta con ese correo.';
                $modo = 'entrar';
            }
        }
    }
}
require 'includes/cabecera.php';
?>
<section class="portada portada-empresas">
  <div class="contenedor portada-dentro">
    <div>
      <p class="antetitulo">Vacantes solidarias</p>
      <h1>Un lugar en tu equipo puede cambiarle el rumbo a alguien.</h1>
      <p class="entrada">Publica vacantes para personas de Puebla, Veracruz, Oaxaca y Tlaxcala que perdieron su empleo y se están preparando para volver.</p>
      <ul class="beneficios">
        <li><b>Publicar es gratis</b> y tú decides cuántos lugares ofreces.</li>
        <li><b>Reconocimiento:</b> tu negocio aparece como empresa solidaria.</li>
        <li><b>Trato digno:</b> marcamos las vacantes con prestaciones de ley para que las personas elijan con confianza.</li>
        <li><b>Privacidad:</b> solo ves el alias, el contacto y el mensaje de quien se postula. Nunca sus resultados emocionales.</li>
      </ul>
    </div>

    <div class="panel" id="acceso">
      <div class="pestanas">
        <a href="empresas.php?modo=entrar#acceso" class="<?= $modo === 'entrar' ? 'activo' : '' ?>">Iniciar sesión</a>
        <a href="empresas.php?modo=registro#acceso" class="<?= $modo === 'registro' ? 'activo' : '' ?>">Registrar empresa</a>
      </div>
      <?php if ($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
      <form method="post" class="formulario" action="empresas.php#acceso">
        <?= campoToken() ?>
        <input type="hidden" name="modo" value="<?= $modo ?>">
        <?php if ($modo === 'registro'): ?>
          <label>Nombre de la empresa o negocio<input type="text" name="nombre" maxlength="80" required value="<?= e($_POST['nombre'] ?? '') ?>"></label>
        <?php endif; ?>
        <label>Correo<input type="email" name="correo" required autocomplete="email" value="<?= e($_POST['correo'] ?? '') ?>"></label>
        <?php if ($modo === 'registro'): ?>
          <label>Teléfono <span class="opcional">(opcional)</span><input type="text" name="telefono" maxlength="30" value="<?= e($_POST['telefono'] ?? '') ?>"></label>
        <?php endif; ?>
        <label>Contraseña<input type="password" name="clave" minlength="6" required></label>
        <?php if ($modo === 'registro'): ?>
          <label class="casilla"><input type="checkbox" name="privacidad" required> <span>Acepto el <a href="privacidad.php" target="_blank">aviso de privacidad</a>.</span></label>
        <?php endif; ?>
        <button class="boton" type="submit"><?= $modo === 'registro' ? 'Registrar empresa' : 'Entrar' ?></button>
      </form>
    </div>
  </div>
</section>
<?php require 'includes/pie.php'; ?>
