<?php
defined('RAIZ') or exit;

/* Base de los modelos: única puerta de acceso a la base de datos. */
abstract class Modelo
{
    protected static function bd(): PDO
    {
        return bd();
    }

    protected static function consultar(string $sql, array $parametros = []): PDOStatement
    {
        $st = self::bd()->prepare($sql);
        $st->execute($parametros);
        return $st;
    }
}
