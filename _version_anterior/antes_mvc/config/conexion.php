<?php
const BD_HOST = '127.0.0.1';
const BD_PUERTO = 8889;
const BD_NOMBRE = 'retoma';
const BD_USUARIO = 'root';
const BD_CLAVE = 'root';

date_default_timezone_set('America/Mexico_City');

function bd(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $opciones = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
        $servidor = 'mysql:host=' . BD_HOST . ';port=' . BD_PUERTO . ';charset=utf8mb4';
        try {
            $pdo = new PDO($servidor . ';dbname=' . BD_NOMBRE, BD_USUARIO, BD_CLAVE, $opciones);
        } catch (PDOException $e) {
            if (!str_contains($e->getMessage(), '1049')) throw $e;
            $instalador = new PDO($servidor, BD_USUARIO, BD_CLAVE, $opciones);
            $instalador->exec(file_get_contents(__DIR__ . '/../sql/retoma.sql'));
            $pdo = new PDO($servidor . ';dbname=' . BD_NOMBRE, BD_USUARIO, BD_CLAVE, $opciones);
        }
        $pdo->exec("SET time_zone = '" . date('P') . "'");
        actualizarEsquema($pdo);
    }
    return $pdo;
}

function existe(PDO $pdo, string $tabla, string $columna = ''): bool
{
    $sql = 'SELECT COUNT(*) FROM information_schema.' . ($columna ? 'COLUMNS' : 'TABLES')
        . ' WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?' . ($columna ? ' AND COLUMN_NAME = ?' : '');
    $st = $pdo->prepare($sql);
    $st->execute(array_filter([BD_NOMBRE, $tabla, $columna]));
    return (bool) $st->fetchColumn();
}

function actualizarEsquema(PDO $pdo): void
{
    if (!existe($pdo, 'cursos', 'estado')) {
        $pdo->exec(file_get_contents(__DIR__ . '/../sql/cursos.sql'));
        if (existe($pdo, 'cursos_guardados')) $pdo->exec('DELETE FROM cursos_guardados');
    }
    if (!existe($pdo, 'usuarios', 'acepta_seguimiento')) {
        $indice = $pdo->query("SHOW INDEX FROM usuarios WHERE Key_name = 'nombre_por_tipo'")->fetch();
        if ($indice) $pdo->exec('ALTER TABLE usuarios DROP INDEX nombre_por_tipo');
        $pdo->exec('ALTER TABLE usuarios ADD acepta_seguimiento TINYINT(1) NOT NULL DEFAULT 0');
    }
    if (!existe($pdo, 'postulaciones', 'estado')) {
        $pdo->exec("ALTER TABLE postulaciones ADD estado ENUM('Recibida', 'En revisión', 'Entrevista', 'Contratado', 'No seleccionado') NOT NULL DEFAULT 'Recibida' AFTER mensaje");
    }
    if (!existe($pdo, 'notas', 'sensible')) {
        $pdo->exec('ALTER TABLE notas ADD sensible TINYINT(1) NOT NULL DEFAULT 0 AFTER color');
    }
    if (!existe($pdo, 'evaluaciones', 'total')) {
        $pdo->exec('ALTER TABLE evaluaciones MODIFY phq TINYINT NULL, MODIFY gad TINYINT NULL,
            ADD estres TINYINT NULL AFTER impacto, ADD ansiedad TINYINT NULL AFTER estres,
            ADD autoestima TINYINT NULL AFTER ansiedad, ADD familia TINYINT NULL AFTER autoestima,
            ADD total TINYINT NULL AFTER familia');
    }
    if (!existe($pdo, 'usuarios', 'estado')) {
        $pdo->exec('ALTER TABLE usuarios ADD estado VARCHAR(20) NULL AFTER acepta_seguimiento');
    }
    if (!existe($pdo, 'notas', 'oculta')) {
        $pdo->exec('ALTER TABLE notas ADD oculta TINYINT(1) NOT NULL DEFAULT 0 AFTER sensible');
    }
    if (!existe($pdo, 'vacantes', 'prestaciones')) {
        $pdo->exec('ALTER TABLE vacantes ADD estado VARCHAR(20) NULL AFTER modalidad, ADD sueldo VARCHAR(60) NULL AFTER estado,
            ADD prestaciones TINYINT(1) NOT NULL DEFAULT 0 AFTER sueldo');
    }
    if (!existe($pdo, 'preferencias')) {
        $sql = file_get_contents(__DIR__ . '/../sql/retoma.sql');
        $pdo->exec(substr($sql, strpos($sql, 'CREATE TABLE denuncias')));
    }
    if (!existe($pdo, 'cursos_guardados')) {
        $pdo->exec("CREATE TABLE cursos_guardados (
            usuario_id INT NOT NULL,
            curso_id INT NOT NULL,
            estado ENUM('Me interesa', 'Inscrito', 'Terminado') NOT NULL DEFAULT 'Me interesa',
            actualizado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (usuario_id, curso_id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");
    }
}
