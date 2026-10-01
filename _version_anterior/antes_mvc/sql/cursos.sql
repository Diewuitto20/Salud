SET NAMES utf8mb4;
DROP TABLE IF EXISTS cursos;

CREATE TABLE cursos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  tipo ENUM('oficio', 'emprender') NOT NULL,
  descripcion VARCHAR(300) NOT NULL,
  modalidad VARCHAR(20) NOT NULL,
  costo VARCHAR(40) NOT NULL,
  ritmo TINYINT NOT NULL,
  institucion VARCHAR(100) NOT NULL,
  estado VARCHAR(20) NULL,
  enlace VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

INSERT INTO cursos (nombre, tipo, descripcion, modalidad, costo, ritmo, institucion, estado, enlace) VALUES
('Cursos de oficios en línea', 'oficio', 'Cursos cortos para aprender un oficio desde el celular o la computadora, a tu ritmo y con constancia al terminar.', 'En línea', 'Gratuito', 0, 'Capacítate para el Empleo (Fundación Carlos Slim)', NULL, 'https://capacitateparaelempleo.org'),
('Cursos del ICATEP', 'oficio', 'Confección de ropa, cosmetología, sistemas eléctricos, soldadura, barbería, carpintería, plomería y más, en las unidades del ICATEP.', 'Presencial', 'Cuota simbólica', 1, 'ICATEP · Gobierno de Puebla', 'puebla', 'https://icatep.edu.mx/'),
('Cursos del ICATVER', 'oficio', 'Cursos y talleres con certificación en las unidades de capacitación del ICATVER en todo el estado.', 'Presencial', 'Cuota simbólica', 1, 'ICATVER · Gobierno de Veracruz', 'veracruz', 'https://www.icatver.gob.mx/nuestros-servicios/'),
('Cursos del ICAPET', 'oficio', 'Más de 300 cursos con validez oficial para aprender o certificar un oficio.', 'Presencial', 'Cuota simbólica', 1, 'ICAPET · Gobierno de Oaxaca', 'oaxaca', 'https://www.oaxaca.gob.mx/icapet/'),
('Cursos de extensión del ICATLAX', 'oficio', 'Cursos cortos de oficios que el ICATLAX lleva a comunidades y municipios.', 'Presencial', 'Cuota simbólica', 1, 'ICATLAX · Gobierno de Tlaxcala', 'tlaxcala', 'https://www.tlaxcaladigital.gob.mx/ficha/cursodeextension'),
('Capacitación acelerada del ICATLAX', 'oficio', 'Cursos intensivos pensados para lo que piden las empresas de la región.', 'Presencial', 'Cuota simbólica', 2, 'ICATLAX · Gobierno de Tlaxcala', 'tlaxcala', 'https://www.tlaxcaladigital.gob.mx/ficha/cursoscae1'),
('Alimentos y Bebidas', 'oficio', 'Preparación de alimentos, panadería y repostería. Útil para trabajar en cocina o vender por tu cuenta.', 'Presencial', 'Cuota simbólica', 1, 'CECATI (SEP)', NULL, 'https://www.dgcft.sems.gob.mx/buscador_cecati/especialidad/58'),
('Estilismo y Diseño de Imagen', 'oficio', 'Corte, peinado y cuidado de la imagen. Puedes trabajar en una estética o atender por tu cuenta.', 'Presencial', 'Cuota simbólica', 1, 'CECATI (SEP)', NULL, 'https://www.dgcft.sems.gob.mx/buscador_cecati/especialidad/1115'),
('Mantenimiento a Equipos y Sistemas Electrónicos', 'oficio', 'Diagnóstico y reparación de aparatos electrónicos.', 'Presencial', 'Cuota simbólica', 1, 'CECATI (SEP)', NULL, 'https://www.dgcft.sems.gob.mx/buscador_cecati/especialidad/1102'),
('Electricidad', 'oficio', 'Instalaciones eléctricas en casas y negocios. Oficio con demanda constante.', 'Presencial', 'Cuota simbólica', 2, 'CECATI (SEP)', NULL, 'https://www.dgcft.sems.gob.mx/buscador_cecati/especialidad/2'),
('Refrigeración y Aire Acondicionado', 'oficio', 'Instalación y mantenimiento de equipos de refrigeración y aire acondicionado.', 'Presencial', 'Cuota simbólica', 2, 'CECATI (SEP)', NULL, 'https://www.dgcft.sems.gob.mx/buscador_cecati/especialidad/2202'),
('Soldadura y Pailería', 'oficio', 'Soldadura y trabajo con metales, con práctica en taller.', 'Presencial', 'Cuota simbólica', 2, 'CECATI (SEP)', NULL, 'https://www.dgcft.sems.gob.mx/buscador_cecati/especialidad/2226'),
('Mecánica Automotriz', 'oficio', 'Mantenimiento y reparación de automóviles.', 'Presencial', 'Cuota simbólica', 2, 'CECATI (SEP)', NULL, 'https://www.dgcft.sems.gob.mx/buscador_cecati/especialidad/8'),
('Habilidades para emprender', 'emprender', 'Guías y cursos en línea para organizar un negocio, vender y manejar tu dinero, a tu ritmo.', 'En línea', 'Gratuito', 0, 'Capacítate para el Empleo (Fundación Carlos Slim)', NULL, 'https://capacitateparaelempleo.org'),
('Informática', 'emprender', 'Manejo de computadora y herramientas digitales para administrar y promocionar tu negocio.', 'Presencial', 'Cuota simbólica', 1, 'CECATI (SEP)', NULL, 'https://www.dgcft.sems.gob.mx/buscador_cecati/especialidad/2232'),
('Abre tu cafetería', 'emprender', 'Capacitación del ICAPET para montar y operar un pequeño negocio de café.', 'Presencial', 'Consulta en el sitio', 1, 'ICAPET · Gobierno de Oaxaca', 'oaxaca', 'https://www.oaxaca.gob.mx/comunicacion/abre-tu-cafeteria-nueva-forma-de-capacitar-del-icapet/'),
('Fomento al Autoempleo (Tlaxcala)', 'emprender', 'Apoyo en mobiliario, maquinaria o equipo para iniciar un negocio propio. Es parte del Programa de Apoyo al Empleo.', 'Trámite', 'Gratuito', 2, 'Secretaría de Trabajo y Competitividad de Tlaxcala', 'tlaxcala', 'https://www.tlaxcaladigital.gob.mx/ficha/subprogramafomentodeautoempleo'),
('Programas de apoyo al empleo y autoempleo (Veracruz)', 'emprender', 'Convocatorias estatales para iniciar un negocio y apoyos del Programa de Apoyo al Empleo.', 'Trámite', 'Gratuito', 2, 'Secretaría de Trabajo de Veracruz', 'veracruz', 'https://www.veracruz.gob.mx/trabajo/programas-subsidios-estimulos-y-apoyos/');
