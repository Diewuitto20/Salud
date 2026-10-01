<?php
defined('RAIZ') or exit;

/* Mapa de valía: logros y cualidades privados de cada persona */
class Valia extends Modelo
{
    public const SUGERENCIAS = ['Soy responsable', 'Aprendo rápido', 'Sé trabajar en equipo', 'Soy puntual', 'Cuido a mi familia', 'No me rindo fácil', 'Sé tratar con clientes', 'Soy creativo'];

    public static function deUsuario(int $usuario): array
    {
        $items = ['logro' => [], 'cualidad' => []];
        foreach (self::consultar('SELECT * FROM valia WHERE usuario_id = ? ORDER BY creado DESC', [$usuario]) as $fila) {
            $items[$fila['tipo']][] = $fila;
        }
        return $items;
    }

    public static function conteo(int $usuario): array
    {
        return self::consultar('SELECT tipo, COUNT(*) FROM valia WHERE usuario_id = ? GROUP BY tipo', [$usuario])->fetchAll(PDO::FETCH_KEY_PAIR) + ['logro' => 0, 'cualidad' => 0];
    }

    public static function agregar(int $usuario, string $tipo, string $texto): void
    {
        self::consultar('INSERT INTO valia (usuario_id, tipo, texto) VALUES (?, ?, ?)', [$usuario, $tipo, mb_substr($texto, 0, 200)]);
    }

    public static function borrar(int $id, int $usuario): void
    {
        self::consultar('DELETE FROM valia WHERE id = ? AND usuario_id = ?', [$id, $usuario]);
    }
}
