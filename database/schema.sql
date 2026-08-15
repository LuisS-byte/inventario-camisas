CREATE DATABASE IF NOT EXISTS inventario_camisas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventario_camisas;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS camisas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  categoria VARCHAR(50) NOT NULL DEFAULT 'Camisa',
  talla VARCHAR(20) NOT NULL,
  color VARCHAR(60) NOT NULL,
  cantidad INT UNSIGNED NOT NULL DEFAULT 0,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  descripcion TEXT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO usuarios (usuario, password, nombre)
SELECT 'Lsarmiento', '$2y$12$9qpE5ZlDxvy949kxr8iEE.2O56lHKEbV8iOG8BIIDbirTrrVxNLwm', 'Luis Sarmiento'
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'Lsarmiento');

INSERT INTO camisas (nombre, categoria, talla, color, cantidad, precio, descripcion)
SELECT * FROM (
  SELECT 'Camisa basica cuello redondo', 'Camisa', 'M', 'Blanco', 25, 12.99, 'Camisa casual de algodon.'
  UNION ALL SELECT 'Camisa tipo polo', 'Camisa', 'L', 'Azul', 14, 18.50, 'Polo para uso diario.'
  UNION ALL SELECT 'Camisa formal manga larga', 'Camisa', 'M', 'Negro', 8, 29.99, 'Camisa formal para oficina.'
) AS datos
WHERE NOT EXISTS (SELECT 1 FROM camisas);
