SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS retoma CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE retoma;

DROP TABLE IF EXISTS reportes, intentos, plan_marcas, planes, preferencias, apoyos_guardados, valia, denuncias, cursos_guardados, postulaciones, vacantes, evaluaciones, comentarios, apoyos, notas, usuarios;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('persona', 'empresa') NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  correo VARCHAR(120) NOT NULL UNIQUE,
  telefono VARCHAR(30) NULL,
  clave VARCHAR(255) NOT NULL,
  acepta_seguimiento TINYINT(1) NOT NULL DEFAULT 0,
  estado VARCHAR(20) NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE notas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  texto VARCHAR(280) NOT NULL,
  color ENUM('amarillo', 'rosa', 'verde', 'azul', 'lila') NOT NULL DEFAULT 'amarillo',
  sensible TINYINT(1) NOT NULL DEFAULT 0,
  oculta TINYINT(1) NOT NULL DEFAULT 0,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE apoyos (
  nota_id INT NOT NULL,
  usuario_id INT NOT NULL,
  PRIMARY KEY (nota_id, usuario_id),
  FOREIGN KEY (nota_id) REFERENCES notas(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE comentarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nota_id INT NOT NULL,
  usuario_id INT NOT NULL,
  texto VARCHAR(280) NOT NULL,
  sensible TINYINT(1) NOT NULL DEFAULT 0,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (nota_id) REFERENCES notas(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE evaluaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  meses VARCHAR(10) NOT NULL,
  impacto TINYINT NOT NULL,
  estres TINYINT NOT NULL,
  ansiedad TINYINT NOT NULL,
  autoestima TINYINT NOT NULL,
  familia TINYINT NOT NULL,
  total TINYINT NOT NULL,
  nivel VARCHAR(12) NOT NULL,
  animo_detectado TINYINT NULL,
  animo_confirmado TINYINT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE vacantes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  titulo VARCHAR(100) NOT NULL,
  descripcion TEXT NOT NULL,
  ubicacion VARCHAR(100) NOT NULL,
  modalidad ENUM('Presencial', 'Remoto', 'Mixto') NOT NULL,
  estado VARCHAR(20) NULL,
  sueldo VARCHAR(60) NULL,
  prestaciones TINYINT(1) NOT NULL DEFAULT 0,
  cupos SMALLINT NOT NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (empresa_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE postulaciones (
  vacante_id INT NOT NULL,
  usuario_id INT NOT NULL,
  contacto VARCHAR(120) NOT NULL,
  mensaje VARCHAR(300) NULL,
  estado ENUM('Recibida', 'En revisión', 'Entrevista', 'Contratado', 'No seleccionado') NOT NULL DEFAULT 'Recibida',
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (vacante_id, usuario_id),
  FOREIGN KEY (vacante_id) REFERENCES vacantes(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cursos_guardados (
  usuario_id INT NOT NULL,
  curso_id INT NOT NULL,
  estado ENUM('Me interesa', 'Inscrito', 'Terminado') NOT NULL DEFAULT 'Me interesa',
  actualizado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, curso_id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE denuncias (
  nota_id INT NOT NULL,
  usuario_id INT NOT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (nota_id, usuario_id),
  FOREIGN KEY (nota_id) REFERENCES notas(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE valia (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  tipo ENUM('logro', 'cualidad') NOT NULL,
  texto VARCHAR(200) NOT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE apoyos_guardados (
  usuario_id INT NOT NULL,
  apoyo VARCHAR(30) NOT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, apoyo),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE preferencias (
  usuario_id INT PRIMARY KEY,
  avisar_vacantes TINYINT(1) NOT NULL DEFAULT 0,
  avisar_cursos TINYINT(1) NOT NULL DEFAULT 0,
  ultima_visita DATETIME NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE planes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  nivel VARCHAR(12) NOT NULL,
  estado VARCHAR(20) NOT NULL,
  interes ENUM('oficio', 'emprender') NOT NULL,
  curso_id INT NULL,
  tramites VARCHAR(200) NOT NULL,
  inicio DATE NOT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE plan_marcas (
  plan_id INT NOT NULL,
  meta ENUM('respirar', 'curso', 'tramite') NOT NULL,
  semana TINYINT NOT NULL,
  fecha DATE NOT NULL,
  PRIMARY KEY (plan_id, meta, fecha),
  FOREIGN KEY (plan_id) REFERENCES planes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE intentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(150) NOT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (clave, creado)
) ENGINE=InnoDB;

CREATE TABLE reportes (
  codigo CHAR(14) PRIMARY KEY,
  cifras TEXT NOT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
