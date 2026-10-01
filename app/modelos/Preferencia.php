<?php
defined('RAIZ') or exit;

/* Avisos que cada persona quiere recibir y su última visita a «Mi ruta» */
class Preferencia extends Modelo
{
    public static function deUsuario(int $usuario): array
    {
        return self::consultar('SELECT * FROM preferencias WHERE usuario_id = ?', [$usuario])->fetch()
            ?: ['avisar_vacantes' => 0, 'avisar_cursos' => 0, 'ultima_visita' => null];
    }

    public static function avisaVacantes(int $usuario): bool
    {
        return (bool) self::consultar('SELECT avisar_vacantes FROM preferencias WHERE usuario_id = ?', [$usuario])->fetchColumn();
    }

    public static function guardarAvisos(int $usuario, bool $vacantes, bool $cursos): void
    {
        self::consultar('INSERT INTO preferencias (usuario_id, avisar_vacantes, avisar_cursos) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE avisar_vacantes = VALUES(avisar_vacantes), avisar_cursos = VALUES(avisar_cursos)', [$usuario, $vacantes ? 1 : 0, $cursos ? 1 : 0]);
    }

    public static function avisarVacantes(int $usuario, bool $activar): void
    {
        self::consultar('INSERT INTO preferencias (usuario_id, avisar_vacantes) VALUES (?, ?) ON DUPLICATE KEY UPDATE avisar_vacantes = VALUES(avisar_vacantes)', [$usuario, $activar ? 1 : 0]);
    }

    public static function registrarVisita(int $usuario): void
    {
        self::consultar('INSERT INTO preferencias (usuario_id, ultima_visita) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE ultima_visita = NOW()', [$usuario]);
    }
}
