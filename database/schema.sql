CREATE DATABASE IF NOT EXISTS inventario_camisas
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE inventario_camisas;

CREATE TABLE IF NOT EXISTS camisas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  talla VARCHAR(10) NOT NULL,
  color VARCHAR(60) NOT NULL,
  cantidad INT UNSIGNED NOT NULL DEFAULT 0,
  precio DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  descripcion TEXT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO camisas (nombre, talla, color, cantidad, precio, descripcion) VALUES
('Camisa basica cuello redondo', 'M', 'Blanco', 25, 12.99, 'Camisa casual de algodon.'),
('Camisa tipo polo', 'L', 'Azul', 14, 18.50, 'Polo para uso diario.'),
('Camisa formal manga larga', 'M', 'Negro', 8, 29.99, 'Camisa formal para oficina.');

