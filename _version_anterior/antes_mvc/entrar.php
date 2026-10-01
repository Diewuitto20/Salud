<?php
require 'includes/funciones.php';
$titulo = 'Entrar';
$volver = preg_match('/^[a-z]+\.php(\?[\w=&%-]*)?$/', $_GET['volver'] ?? '') ? $_GET['volver'] : '';
$modo = ($_GET['modo'] ?? $_POST['modo'] ?? '') === 'registro' ? 'registro' : 'entrar';
$error = '';

if (($_GET['tipo'] ?? '') === 'empresa') {
    redirigir('empresas.php#acceso');
}

function iniciarSesion(array $u, string $volver): void
{
    session_regenerate_id(true);
    $_SESSION['usuario'] = ['id' => (int) $u['id'], 'tipo' => $u['tipo'], 'nombre' => $u['nombre']];
    $_SESSION['estado'] = $u['estado'] ?? '';
    redirigir($volver ?: 'avance.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarToken();
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
    $clave = $_POST['clave'] ?? '';

    if ($modo === 'entrar') {
        $st = bd()->prepare("SELECT * FROM usuarios WHERE correo = ? AND tipo = 'persona'");
        $st->execute([$correo]);
        $u = $st->fetch();
        if ($u && password_verify($clave, $u['clave'])) {
            iniciarSesion($u, $volver);
        }
        $error = 'Correo o contraseña incorrectos. Si tienes una cuenta de empresa, entra desde «Para empresas».';
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $estado = array_key_exists($_POST['estado'] ?? '', ESTADOS) ? $_POST['estado'] : null;
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
                bd()->prepare("INSERT INTO usuarios (tipo, nombre, correo, clave, estado) VALUES ('persona', ?, ?, ?, ?)")
                    ->execute([$nombre, $correo, password_hash($clave, PASSWORD_DEFAULT), $estado]);
                iniciarSesion(['id' => bd()->lastInsertId(), 'tipo' => 'persona', 'nombre' => $nombre, 'estado' => $estado], $volver);
            } catch (PDOException $ex) {
                $error = 'Ya existe una cuenta con ese correo. Inicia sesión.';
                $modo = 'entrar';
            }
        }
    }
}
$enlace = fn(array $cambios) => 'entrar.php?' . http_build_query(array_merge(['modo' => $modo, 'volver' => $volver], $cambios));
require 'includes/cabecera.php';
?>
<section class="seccion">
  <div class="contenedor acceso">
    <div class="pestanas">
      <a href="<?= e($enlace(['modo' => 'entrar'])) ?>" class="<?= $modo === 'entrar' ? 'activo' : '' ?>">Iniciar sesión</a>
      <a href="<?= e($enlace(['modo' => 'registro'])) ?>" class="<?= $modo === 'registro' ? 'activo' : '' ?>">Crear cuenta</a>
    </div>

    <div class="panel">
      <?php if ($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>

      <?php if ($modo === 'entrar'): ?>
        <h1 class="titulo-panel">Qué gusto verte</h1>
        <form method="post" class="formulario">
          <?= campoToken() ?>
          <input type="hidden" name="modo" value="entrar">
          <label>Correo<input type="email" name="correo" required autocomplete="email" value="<?= e($_POST['correo'] ?? '') ?>"></label>
          <label>Contraseña<input type="password" name="clave" required autocomplete="current-password"></label>
          <button class="boton" type="submit">Entrar</button>
        </form>
        <p class="chico centrado">¿No tienes cuenta? <a href="<?= e($enlace(['modo' => 'registro'])) ?>">Créala aquí</a></p>
      <?php else: ?>
        <h1 class="titulo-panel">Crea tu cuenta</h1>
        <p class="suave">Para quien perdió su empleo. Es gratis.</p>
        <form method="post" class="formulario">
          <?= campoToken() ?>
          <input type="hidden" name="modo" value="registro">
          <label>Alias
            <input type="text" name="nombre" minlength="3" maxlength="15" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9 ]{3,15}" required placeholder="Ej. Lalo, Caro22" value="<?= e($_POST['nombre'] ?? '') ?>">
            <span class="ayuda-campo">De 3 a 15 letras o números. Es lo único que se ve en el foro: tu nombre completo y tu correo nunca se muestran.</span>
          </label>
          <label>¿En qué estado vives?
            <select name="estado" required>
              <option value="">Elige tu estado</option>
              <?php foreach (ESTADOS as $clave => $nombreEstado): ?>
                <option value="<?= $clave ?>" <?= ($_POST['estado'] ?? '') === $clave ? 'selected' : '' ?>><?= $nombreEstado ?></option>
              <?php endforeach; ?>
            </select>
            <span class="ayuda-campo">Así te mostramos cursos, apoyos y centros de atención cerca de ti.</span>
          </label>
          <label>Correo<input type="email" name="correo" required autocomplete="email" value="<?= e($_POST['correo'] ?? '') ?>"></label>
          <label>Contraseña<input type="password" name="clave" minlength="6" required autocomplete="new-password"></label>
          <label class="casilla"><input type="checkbox" name="privacidad" required> <span>Leí y acepto el <a href="privacidad.php" target="_blank">aviso de privacidad</a>, incluido el uso de mis respuestas del cuestionario, que son datos sensibles.</span></label>
          <button class="boton" type="submit">Crear cuenta</button>
        </form>
      <?php endif; ?>
    </div>
    <p class="chico centrado">¿Eres empresa o negocio? <a href="empresas.php#acceso">Entra por aquí</a></p>
  </div>
</section>
<?php require 'includes/pie.php'; ?>
