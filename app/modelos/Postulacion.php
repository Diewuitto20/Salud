<?php
defined('RAIZ') or exit;

class Postulacion extends Modelo
{
    public const ESTADOS = ['Recibida', 'En revisión', 'Entrevista', 'Contratado', 'No seleccionado'];

    public static function crear(int $vacante, int $usuario, string $contacto, string $mensaje): void
    {
        self::consultar('INSERT IGNORE INTO postulaciones (vacante_id, usuario_id, contacto, mensaje) VALUES (?, ?, ?, ?)',
            [$vacante, $usuario, mb_substr($contacto, 0, 120), mb_substr($mensaje, 0, 300)]);
    }

    public static function deUsuario(int $usuario): array
    {
        return self::consultar('SELECT p.estado, p.creado, v.titulo, u.nombre AS empresa FROM postulaciones p
    JOIN vacantes v ON v.id = p.vacante_id JOIN usuarios u ON u.id = v.empresa_id WHERE p.usuario_id = ? ORDER BY p.creado DESC', [$usuario])->fetchAll();
    }

    /* Postulaciones recibidas por la empresa, agrupadas por vacante */
    public static function deEmpresa(int $empresa): array
    {
        $agrupadas = [];
        foreach (self::consultar('SELECT p.*, u.nombre FROM postulaciones p JOIN usuarios u ON u.id = p.usuario_id
    JOIN vacantes v ON v.id = p.vacante_id WHERE v.empresa_id = ? ORDER BY p.creado DESC', [$empresa]) as $p) {
            $agrupadas[$p['vacante_id']][] = $p;
        }
        return $agrupadas;
    }

    /* Solo la empresa dueña de la vacante puede cambiar el estado */
    public static function cambiarEstado(int $vacante, int $persona, int $empresa, string $estado): void
    {
        self::consultar('UPDATE postulaciones p JOIN vacantes v ON v.id = p.vacante_id SET p.estado = ?
            WHERE p.vacante_id = ? AND p.usuario_id = ? AND v.empresa_id = ?', [$estado, $vacante, $persona, $empresa]);
    }
}
