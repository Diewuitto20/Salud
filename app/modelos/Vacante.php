<?php
defined('RAIZ') or exit;

class Vacante extends Modelo
{
    public const MODALIDADES = ['Presencial', 'Remoto', 'Mixto'];
    public const PERIODOS = ['al día', 'a la semana', 'a la quincena', 'al mes'];
    public const MAX_LUGARES = 50;

    /* Lugares libres en todas las vacantes abiertas */
    public static function lugaresLibres(): int
    {
        return (int) self::bd()->query('SELECT COALESCE(SUM(v.cupos), 0) - (SELECT COUNT(*) FROM postulaciones p JOIN vacantes x ON x.id = p.vacante_id WHERE x.activa = 1)
    FROM vacantes v WHERE v.activa = 1')->fetchColumn();
    }

    public static function conLugares(int $cuantas): array
    {
        return self::bd()->query('SELECT v.titulo, v.ubicacion, u.nombre AS empresa FROM vacantes v JOIN usuarios u ON u.id = v.empresa_id
    WHERE v.activa = 1 AND v.cupos > (SELECT COUNT(*) FROM postulaciones p WHERE p.vacante_id = v.id) ORDER BY v.creado DESC LIMIT ' . $cuantas)->fetchAll();
    }

    /* Búsqueda para personas; las empresas ven todas las abiertas */
    public static function buscar(int $yo, string $estado, string $modalidad, string $texto, bool $todas): array
    {
        $condiciones = ['v.activa = 1'];
        $parametros = [$yo];
        if ($estado && $estado !== 'otro') {
            $condiciones[] = '(v.estado = ? OR v.modalidad = \'Remoto\')';
            $parametros[] = $estado;
        }
        if ($modalidad) {
            $condiciones[] = 'v.modalidad = ?';
            $parametros[] = $modalidad;
        }
        if ($texto !== '') {
            $condiciones[] = '(v.titulo LIKE ? OR v.descripcion LIKE ?)';
            $parametros[] = "%$texto%";
            $parametros[] = "%$texto%";
        }
        if ($todas) {
            $condiciones = ['v.activa = 1'];
            $parametros = [0];
        }
        return self::consultar('SELECT v.*, u.nombre AS empresa,
        v.cupos - (SELECT COUNT(*) FROM postulaciones p WHERE p.vacante_id = v.id) AS libres,
        (SELECT COUNT(*) FROM postulaciones p WHERE p.vacante_id = v.id AND p.usuario_id = ?) AS postulado
    FROM vacantes v JOIN usuarios u ON u.id = v.empresa_id WHERE ' . implode(' AND ', $condiciones) . '
    ORDER BY libres > 0 DESC, v.prestaciones DESC, v.creado DESC', $parametros)->fetchAll();
    }

    public static function libres(int $vacante): int
    {
        return (int) self::consultar('SELECT v.cupos - (SELECT COUNT(*) FROM postulaciones p WHERE p.vacante_id = v.id) FROM vacantes v WHERE v.id = ? AND v.activa = 1', [$vacante])->fetchColumn();
    }

    /* Revisa los datos del formulario; devuelve el mensaje de error o null si todo está bien */
    public static function validar(array $datos): ?string
    {
        if (in_array('', [$datos['titulo'], $datos['descripcion'], $datos['ubicacion'], $datos['modalidad'], $datos['estado'], $datos['periodo']], true) || $datos['monto'] < 1) {
            return 'Completa todos los campos marcados con *.';
        }
        if ($datos['monto'] > 999999) {
            return 'Revisa el sueldo: la cantidad es demasiado alta.';
        }
        if ($datos['cupos'] === false || $datos['cupos'] < 1 || $datos['cupos'] > self::MAX_LUGARES) {
            return 'El número de lugares debe ser de 1 a 50.';
        }
        if (tieneGroserias($datos['titulo'] . ' ' . $datos['descripcion'])) {
            return 'La vacante tiene palabras ofensivas. Cámbialas, por favor.';
        }
        return null;
    }

    public static function publicar(int $empresa, array $d): void
    {
        self::consultar('INSERT INTO vacantes (empresa_id, titulo, descripcion, ubicacion, modalidad, estado, sueldo, prestaciones, cupos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$empresa, $d['titulo'], $d['descripcion'], $d['ubicacion'], $d['modalidad'], $d['estado'], '$' . number_format($d['monto']) . ' ' . $d['periodo'], $d['prestaciones'], $d['cupos']]);
    }

    public static function deEmpresa(int $empresa): array
    {
        return self::consultar('SELECT v.*, (SELECT COUNT(*) FROM postulaciones p WHERE p.vacante_id = v.id) AS postulados
    FROM vacantes v WHERE v.empresa_id = ? ORDER BY v.activa DESC, v.creado DESC', [$empresa])->fetchAll();
    }

    public static function cerrar(int $vacante, int $empresa): void
    {
        self::consultar('UPDATE vacantes SET activa = 0 WHERE id = ? AND empresa_id = ?', [$vacante, $empresa]);
    }

    /* Vacantes publicadas desde la última visita, en el estado de la persona o remotas */
    public static function nuevasDesde(string $desde, string $estado): array
    {
        return self::consultar("SELECT v.titulo, v.ubicacion, u.nombre AS empresa FROM vacantes v JOIN usuarios u ON u.id = v.empresa_id
        WHERE v.activa = 1 AND v.creado > ? AND (v.estado = ? OR v.modalidad = 'Remoto') ORDER BY v.creado DESC LIMIT 5", [$desde, $estado])->fetchAll();
    }
}
