<?php
defined('RAIZ') or exit;

/* Notas del foro, con sus apoyos, respuestas y reportes */
class Nota extends Modelo
{
    public const COLORES = ['amarillo', 'rosa', 'verde', 'azul', 'lila'];

    public static function paraPortada(): array
    {
        return self::bd()->query("SELECT n.texto, n.color, u.nombre FROM notas n JOIN usuarios u ON u.id = n.usuario_id
    WHERE u.tipo = 'persona' AND n.sensible = 0 AND n.oculta = 0 ORDER BY n.creado DESC LIMIT 3")->fetchAll();
    }

    public static function muro(bool $apoyadas, int $yo): array
    {
        $orden = $apoyadas ? 'apoyos DESC, n.creado DESC' : 'n.creado DESC';
        return self::consultar("SELECT n.*, u.nombre, u.tipo,
        (SELECT COUNT(*) FROM apoyos a WHERE a.nota_id = n.id) AS apoyos,
        (SELECT COUNT(*) FROM apoyos a WHERE a.nota_id = n.id AND a.usuario_id = ?) AS mio
        , (SELECT COUNT(*) FROM denuncias d WHERE d.nota_id = n.id AND d.usuario_id = ?) AS denunciada
    FROM notas n JOIN usuarios u ON u.id = n.usuario_id WHERE n.oculta = 0 ORDER BY $orden LIMIT 60", [$yo, $yo])->fetchAll();
    }

    /* Respuestas agrupadas por nota */
    public static function comentarios(): array
    {
        $comentarios = [];
        foreach (self::bd()->query('SELECT c.nota_id, c.texto, c.creado, u.nombre FROM comentarios c JOIN usuarios u ON u.id = c.usuario_id ORDER BY c.creado') as $c) {
            $comentarios[$c['nota_id']][] = $c;
        }
        return $comentarios;
    }

    public static function crear(int $yo, string $texto, string $color, bool $sensible): void
    {
        self::consultar('INSERT INTO notas (usuario_id, texto, color, sensible) VALUES (?, ?, ?, ?)',
            [$yo, mb_substr($texto, 0, 280), $color, $sensible ? 1 : 0]);
    }

    public static function comentar(int $nota, int $yo, string $texto): void
    {
        self::consultar('INSERT INTO comentarios (nota_id, usuario_id, texto) VALUES (?, ?, ?)', [$nota, $yo, mb_substr($texto, 0, 280)]);
    }

    /* Da o quita el «Te apoyo» */
    public static function alternarApoyo(int $nota, int $yo): void
    {
        if (!self::consultar('DELETE FROM apoyos WHERE nota_id = ? AND usuario_id = ?', [$nota, $yo])->rowCount()) {
            self::consultar('INSERT IGNORE INTO apoyos (nota_id, usuario_id) VALUES (?, ?)', [$nota, $yo]);
        }
    }

    /* Un reporte por persona; con 3 reportes la nota se oculta */
    public static function denunciar(int $nota, int $yo): void
    {
        self::consultar('INSERT IGNORE INTO denuncias (nota_id, usuario_id) SELECT id, ? FROM notas WHERE id = ? AND usuario_id <> ?', [$yo, $nota, $yo]);
        self::consultar('UPDATE notas SET oculta = 1 WHERE id = ? AND (SELECT COUNT(*) FROM denuncias WHERE nota_id = ?) >= 3', [$nota, $nota]);
    }

    public static function reportadas(): array
    {
        return self::bd()->query('SELECT n.id, n.texto, n.oculta, u.nombre, COUNT(d.usuario_id) AS reportes FROM notas n
    JOIN usuarios u ON u.id = n.usuario_id JOIN denuncias d ON d.nota_id = n.id GROUP BY n.id ORDER BY reportes DESC')->fetchAll();
    }

    public static function restaurar(int $nota): void
    {
        self::consultar('DELETE FROM denuncias WHERE nota_id = ?', [$nota]);
        self::consultar('UPDATE notas SET oculta = 0 WHERE id = ?', [$nota]);
    }

    public static function eliminar(int $nota): void
    {
        self::consultar('DELETE FROM notas WHERE id = ?', [$nota]);
    }

    /* Participación de una persona en el foro */
    public static function comunidadDe(int $yo): array
    {
        return self::consultar('SELECT
    (SELECT COUNT(*) FROM notas WHERE usuario_id = ?) AS notas,
    (SELECT COUNT(*) FROM apoyos a JOIN notas n ON n.id = a.nota_id WHERE n.usuario_id = ?) AS apoyos,
    (SELECT COUNT(*) FROM comentarios c JOIN notas n ON n.id = c.nota_id WHERE n.usuario_id = ? AND c.usuario_id <> ?) AS respuestas', [$yo, $yo, $yo, $yo])->fetch();
    }
}
