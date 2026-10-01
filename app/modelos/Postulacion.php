<?php
defined('RAIZ') or exit;

class Postulacion extends Modelo
{
    public const ESTADOS = ['Recibida', 'En revisión', 'Entrevista', 'Contratado', 'No seleccionado'];

    /*
     * Bloquea la vacante mientras cuenta los lugares, así dos postulaciones al mismo tiempo no rebasan los cupos.
     * Devuelve 'ok', 'repetida' o 'llena'.
     */
    public static function crear(int $vacante, int $usuario, string $contacto, string $mensaje): string
    {
        $bd = self::bd();
        $bd->beginTransaction();
        try {
            $cupos = self::consultar('SELECT cupos FROM vacantes WHERE id = ? AND activa = 1 FOR UPDATE', [$vacante])->fetchColumn();
            $ocupados = (int) self::consultar('SELECT COUNT(*) FROM postulaciones WHERE vacante_id = ?', [$vacante])->fetchColumn();
            if (self::consultar('SELECT 1 FROM postulaciones WHERE vacante_id = ? AND usuario_id = ?', [$vacante, $usuario])->fetchColumn()) {
                $resultado = 'repetida';
            } elseif ($cupos === false || $ocupados >= (int) $cupos) {
                $resultado = 'llena';
            } else {
                self::consultar('INSERT INTO postulaciones (vacante_id, usuario_id, contacto, mensaje) VALUES (?, ?, ?, ?)',
                    [$vacante, $usuario, mb_substr($contacto, 0, 120), mb_substr($mensaje, 0, 300)]);
                $resultado = 'ok';
            }
            $bd->commit();
            return $resultado;
        } catch (Throwable $e) {
            $bd->rollBack();
            throw $e;
        }
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
