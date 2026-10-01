<?php
defined('RAIZ') or exit;

class Usuario extends Modelo
{
    public static function buscarPorCorreo(string $correo, string $tipo): ?array
    {
        return self::consultar('SELECT * FROM usuarios WHERE correo = ? AND tipo = ?', [$correo, $tipo])->fetch() ?: null;
    }

    /* Lanza PDOException si el correo ya existe */
    public static function crearPersona(string $nombre, string $correo, string $clave, ?string $estado): int
    {
        self::consultar("INSERT INTO usuarios (tipo, nombre, correo, clave, estado) VALUES ('persona', ?, ?, ?, ?)",
            [$nombre, $correo, password_hash($clave, PASSWORD_DEFAULT), $estado]);
        return (int) self::bd()->lastInsertId();
    }

    /* Lanza PDOException si el correo ya existe */
    public static function crearEmpresa(string $nombre, string $correo, ?string $telefono, string $clave): int
    {
        self::consultar("INSERT INTO usuarios (tipo, nombre, correo, telefono, clave) VALUES ('empresa', ?, ?, ?, ?)",
            [$nombre, $correo, $telefono, password_hash($clave, PASSWORD_DEFAULT)]);
        return (int) self::bd()->lastInsertId();
    }

    public static function cambiarEstado(int $id, string $estado): void
    {
        self::consultar('UPDATE usuarios SET estado = ? WHERE id = ?', [$estado, $id]);
    }

    public static function estadoDe(int $id): string
    {
        return self::consultar('SELECT estado FROM usuarios WHERE id = ?', [$id])->fetchColumn() ?: '';
    }

    /* Borra todos los datos de prueba; conserva el catálogo de cursos */
    public static function reiniciarTodo(): void
    {
        $pdo = self::bd();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['plan_marcas', 'planes', 'preferencias', 'apoyos_guardados', 'valia', 'denuncias', 'cursos_guardados', 'postulaciones', 'vacantes', 'evaluaciones', 'comentarios', 'apoyos', 'notas', 'usuarios'] as $tabla) {
            $pdo->exec("TRUNCATE TABLE $tabla");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
