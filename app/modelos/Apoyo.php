<?php
defined('RAIZ') or exit;

class Apoyo extends Modelo
{
    public const CATALOGO = [
        'imss' => [
            'nombre' => 'Atención médica del IMSS después de la baja',
            'quien' => 'IMSS',
            'para' => 'Quien cotizó al menos 8 semanas seguidas antes de perder su empleo.',
            'que' => 'Tú y tus beneficiarios conservan la atención médica hasta 8 semanas después de la baja. Úsala para tus consultas pendientes.',
            'documentos' => ['CURP', 'Número de Seguridad Social (NSS)'],
            'enlace' => 'https://www.imss.gob.mx/',
            'estado' => null,
            'regla' => 'imss',
        ],
        'jcf' => [
            'nombre' => 'Jóvenes Construyendo el Futuro',
            'quien' => 'Gobierno de México (STPS)',
            'para' => 'Personas de 18 a 29 años que no estudian ni trabajan.',
            'que' => 'Capacitación en un centro de trabajo hasta por 12 meses, con beca mensual de $9,582.47 (monto 2026) y seguro médico del IMSS.',
            'documentos' => ['CURP', 'Identificación oficial', 'Comprobante de domicilio'],
            'enlace' => 'https://jovenesconstruyendoelfuturo.stps.gob.mx/',
            'estado' => null,
            'regla' => 'jcf',
        ],
        'pae-puebla' => [
            'nombre' => 'Programa de Apoyo al Empleo en Puebla',
            'quien' => 'Servicio Nacional de Empleo Puebla',
            'para' => 'Mayores de 18 años que buscan trabajo en Puebla.',
            'que' => 'Asesoría personalizada y vinculación gratuita con empresas. Oficina central en Paseo de San Francisco (Puebla) y módulos en Tehuacán, San Javier, Angelópolis y Teziutlán.',
            'documentos' => ['CURP', 'Identificación oficial', 'Registro en el Portal del Empleo'],
            'enlace' => 'https://www.empleo.gob.mx/',
            'estado' => 'puebla',
            'regla' => 'adulto',
        ],
        'pae-veracruz' => [
            'nombre' => 'Programa de Apoyo al Empleo en Veracruz',
            'quien' => 'Secretaría de Trabajo de Veracruz · Servicio Nacional de Empleo',
            'para' => 'Mayores de 18 años que buscan trabajo o quieren iniciar un negocio en Veracruz.',
            'que' => 'Vinculación con empresas, capacitación y apoyos para autoempleo según la convocatoria vigente.',
            'documentos' => ['CURP', 'Identificación oficial', 'Registro en el Portal del Empleo'],
            'enlace' => 'https://www.veracruz.gob.mx/trabajo/programas-subsidios-estimulos-y-apoyos/',
            'estado' => 'veracruz',
            'regla' => 'adulto',
        ],
        'pae-oaxaca' => [
            'nombre' => 'Programa de Apoyo al Empleo en Oaxaca',
            'quien' => 'Servicio Nacional de Empleo Oaxaca',
            'para' => 'Mayores de 18 años que buscan trabajo en Oaxaca.',
            'que' => 'Asesoría y vinculación con empresas. Oficinas en Ciudad Administrativa (Oaxaca de Juárez), Puerto Escondido, Salina Cruz, Huajuapan de León y Tuxtepec.',
            'documentos' => ['CURP', 'Identificación oficial', 'Registro en el Portal del Empleo'],
            'enlace' => 'https://www.empleo.gob.mx/',
            'estado' => 'oaxaca',
            'regla' => 'adulto',
        ],
        'pae-tlaxcala' => [
            'nombre' => 'Apoyo al Empleo y Fomento al Autoempleo en Tlaxcala',
            'quien' => 'Secretaría de Trabajo y Competitividad de Tlaxcala',
            'para' => 'Mayores de 18 años que buscan trabajo o quieren iniciar un negocio en Tlaxcala.',
            'que' => 'Vinculación con empresas y apoyo en mobiliario, maquinaria o equipo para empezar un negocio propio.',
            'documentos' => ['CURP', 'Identificación oficial', 'Registro en el Portal del Empleo'],
            'enlace' => 'https://www.tlaxcaladigital.gob.mx/ficha/subprogramafomentodeautoempleo',
            'estado' => 'tlaxcala',
            'regla' => 'adulto',
        ],
        'portal' => [
            'nombre' => 'Portal del Empleo',
            'quien' => 'Servicio Nacional de Empleo (STPS)',
            'para' => 'Cualquier persona que busca trabajo, en todo el país.',
            'que' => 'Bolsa de trabajo gratuita, ferias de empleo y orientación en las oficinas del Servicio Nacional de Empleo de tu estado.',
            'documentos' => ['CURP', 'Correo electrónico', 'Tu currículum, si lo tienes'],
            'enlace' => 'https://www.empleo.gob.mx/',
            'estado' => null,
            'regla' => 'todos',
        ],
        'afore' => [
            'nombre' => 'Retiro parcial por desempleo de tu AFORE',
            'quien' => 'Tu AFORE (CONSAR)',
            'para' => 'Quien lleva al menos 46 días naturales sin empleo y no ha usado este retiro en los últimos 5 años.',
            'que' => 'Puedes sacar una parte de tu ahorro para el retiro.',
            'advertencia' => 'Impacta tu pensión: te descuenta semanas cotizadas y reduce lo que recibirás al jubilarte. Úsalo solo en una urgencia.',
            'documentos' => ['Identificación oficial', 'Estado de cuenta bancario', 'CUSS (lo tramitas en aforeweb.com.mx)'],
            'enlace' => 'https://www.aforeweb.com.mx/',
            'estado' => null,
            'regla' => 'afore',
        ],
    ];

    /* Nacionales más los del estado elegido */
    public static function visibles(string $estado): array
    {
        return array_filter(self::CATALOGO, fn($a) => $a['estado'] === null || $a['estado'] === $estado);
    }

    public static function existe(string $clave): bool
    {
        return isset(self::CATALOGO[$clave]);
    }

    public static function clavesGuardadas(int $usuario): array
    {
        return self::consultar('SELECT apoyo FROM apoyos_guardados WHERE usuario_id = ?', [$usuario])->fetchAll(PDO::FETCH_COLUMN);
    }

    /* Guarda o quita el apoyo; devuelve true si quedó guardado */
    public static function alternarGuardado(int $usuario, string $clave): bool
    {
        if (self::consultar('DELETE FROM apoyos_guardados WHERE usuario_id = ? AND apoyo = ?', [$usuario, $clave])->rowCount()) {
            return false;
        }
        self::consultar('INSERT INTO apoyos_guardados (usuario_id, apoyo) VALUES (?, ?)', [$usuario, $clave]);
        return true;
    }

    public static function quitar(int $usuario, string $clave): void
    {
        self::consultar('DELETE FROM apoyos_guardados WHERE usuario_id = ? AND apoyo = ?', [$usuario, $clave]);
    }

    /* Trámites guardados, en orden, con sus datos del catálogo */
    public static function tramitesDe(int $usuario): array
    {
        $tramites = [];
        foreach (self::consultar('SELECT apoyo FROM apoyos_guardados WHERE usuario_id = ? ORDER BY creado', [$usuario])->fetchAll(PDO::FETCH_COLUMN) as $clave) {
            if (isset(self::CATALOGO[$clave])) $tramites[$clave] = self::CATALOGO[$clave];
        }
        return $tramites;
    }
}
