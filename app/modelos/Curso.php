<?php
defined('RAIZ') or exit;

class Curso extends Modelo
{
    public const RITMOS = ['A tu ritmo', 'Ritmo medio', 'Intensivo'];
    public const ESTADOS_AVANCE = ['Me interesa', 'Inscrito', 'Terminado'];

    /* Cursos del tipo pedido con el ritmo más cercano al recomendado */
    public static function recomendados(string $tipo, int $ritmo, int $cuantos): array
    {
        $st = self::bd()->prepare('SELECT * FROM cursos WHERE tipo = ? ORDER BY ABS(ritmo - ?), id LIMIT ?');
        $st->bindValue(1, $tipo);
        $st->bindValue(2, $ritmo, PDO::PARAM_INT);
        $st->bindValue(3, $cuantos, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /* Primero los del estado de la persona, luego los nacionales */
    public static function catalogo(string $tipo, string $estado): array
    {
        return self::consultar('SELECT * FROM cursos WHERE tipo = ? AND (estado IS NULL OR estado = ?) ORDER BY estado IS NULL, ritmo, nombre', [$tipo, $estado])->fetchAll();
    }

    public static function idsGuardados(int $usuario): array
    {
        return self::consultar('SELECT curso_id FROM cursos_guardados WHERE usuario_id = ?', [$usuario])->fetchAll(PDO::FETCH_COLUMN);
    }

    /* Guarda o quita el curso; devuelve true si quedó guardado */
    public static function alternarGuardado(int $usuario, int $curso): bool
    {
        if (self::consultar('DELETE FROM cursos_guardados WHERE usuario_id = ? AND curso_id = ?', [$usuario, $curso])->rowCount()) {
            return false;
        }
        self::consultar('INSERT INTO cursos_guardados (usuario_id, curso_id) VALUES (?, ?)', [$usuario, $curso]);
        return true;
    }

    public static function guardadosDe(int $usuario): array
    {
        return self::consultar('SELECT g.estado, c.id, c.nombre, c.institucion, c.enlace FROM cursos_guardados g
    JOIN cursos c ON c.id = g.curso_id WHERE g.usuario_id = ? ORDER BY FIELD(g.estado, \'Inscrito\', \'Me interesa\', \'Terminado\'), c.nombre', [$usuario])->fetchAll();
    }

    public static function cambiarAvance(int $usuario, int $curso, string $estado): void
    {
        self::consultar('UPDATE cursos_guardados SET estado = ? WHERE usuario_id = ? AND curso_id = ?', [$estado, $usuario, $curso]);
    }

    public static function quitar(int $usuario, int $curso): void
    {
        self::consultar('DELETE FROM cursos_guardados WHERE usuario_id = ? AND curso_id = ?', [$usuario, $curso]);
    }
}
