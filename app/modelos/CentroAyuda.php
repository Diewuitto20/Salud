<?php
defined('RAIZ') or exit;

/* Directorio de centros de salud mental (CECOSAMA) por estado */
class CentroAyuda extends Modelo
{
    public const CENTROS = [
        'puebla' => [
            ['Puebla Norte', 'Diagonal de la 88 Pte. y 9 Nte., Col. Infonavit San Pedro', '2222884431'],
            ['Puebla Sur', 'Antiguo Camino a Guadalupe Hidalgo 11350, Col. Agua Santa', '2223996588'],
            ['San Pedro Cholula', '17 Nte. 202, esq. Vicente Guerrero, Col. San Cristóbal Tepontla', '2222617327'],
            ['San Martín Texmelucan', 'Camino al Moral s/n, Col. El Moral', '2481122493'],
            ['Tehuacán', '19 Pte. 3800, Col. México en la Exhacienda El Riego', '2381071832'],
            ['Tepeaca', 'Blvd. Dr. Antonio López Rosas s/n, Col. San Isidro', '2234782099'],
            ['Chalchicomula de Sesma', '3 Nte. 1, Col. Centro', '2454524011'],
            ['Libres', '14 Sur 1104, Barrio Tetela', '2764732034'],
        ],
        'oaxaca' => [
            ['Santa Cruz Xoxocotlán', 'Hornos y Progreso s/n, Col. Centro', '9515172448'],
            ['Trinidad de Viguera', 'Av. Los Higos s/n', '9515204501'],
            ['Huajuapan', 'Vicente Suárez Lt. 1, 2 y 3, Col. El Rosario', '9535552320'],
            ['Tuxtepec', 'Carretera a Loma Alta s/n, Col. El Bosque', '2878748790'],
            ['Puerto Escondido', 'Raúl González s/n, Col. Jardines', '9541150300'],
            ['Pinotepa Nacional', '2a Pte. s/n esq. 15 Sur, Col. Aviación', '9545432916'],
            ['Tehuantepec', 'Av. Universidad s/n, Barrio Santa Cruz Tagolaba', '9717136202'],
        ],
    ];
    public const ENLACES = [
        'veracruz' => ['Centros de salud mental (UNEME-CECOSAMA) de Veracruz', 'https://www.ssaver.gob.mx/ceca/uneme-cecosama-centros-comunitarios-de-salud-mental-y-adicciones/'],
        'tlaxcala' => ['Centros de salud mental y adicciones de Tlaxcala', 'https://www.tlaxcala.gob.mx/index.php/tramites-y-servicios/salud'],
    ];
}
