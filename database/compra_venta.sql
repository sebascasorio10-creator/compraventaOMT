-- =========================================================
-- COMPRAVENTA OMT - Esquema inicial de base de datos
-- Motor: MySQL 8.0+
-- Ejecutar desde MySQL Workbench.
-- =========================================================

CREATE DATABASE IF NOT EXISTS compraventa_omt
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE compraventa_omt;

-- 1. Roles: comprador, vendedor y administrador.
CREATE TABLE IF NOT EXISTS roles (
  id_rol TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(30) NOT NULL UNIQUE,
  descripcion VARCHAR(150) NULL
) ENGINE=InnoDB;

-- 2. Usuarios: datos compartidos por compradores y vendedores.
CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_rol TINYINT UNSIGNED NOT NULL,
  nombres VARCHAR(80) NOT NULL,
  apellidos VARCHAR(80) NULL,
  tipo_documento VARCHAR(20) NULL,
  numero_documento VARCHAR(30) NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  celular VARCHAR(25) NULL,
  password_hash VARCHAR(255) NOT NULL,
  estado ENUM('activo','pendiente','suspendido') NOT NULL DEFAULT 'activo',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_roles FOREIGN KEY (id_rol)
    REFERENCES roles(id_rol),
  UNIQUE KEY uq_usuario_documento (tipo_documento, numero_documento),
  INDEX idx_usuarios_rol_estado (id_rol, estado)
) ENGINE=InnoDB;

-- 3. Tiendas: información adicional que solo necesita un vendedor.
CREATE TABLE IF NOT EXISTS tiendas (
  id_tienda BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_vendedor BIGINT UNSIGNED NOT NULL UNIQUE,
  nombre_tienda VARCHAR(120) NOT NULL,
  descripcion TEXT NULL,
  pais VARCHAR(80) NOT NULL DEFAULT 'Colombia',
  departamento VARCHAR(100) NULL,
  ciudad VARCHAR(100) NULL,
  direccion VARCHAR(180) NULL,
  estado_verificacion ENUM('pendiente','verificada','rechazada') NOT NULL DEFAULT 'pendiente',
  documento_frontal_ruta VARCHAR(255) NULL,
  documento_reverso_ruta VARCHAR(255) NULL,
  creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tiendas_vendedor FOREIGN KEY (id_vendedor)
    REFERENCES usuarios(id_usuario)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Categorías disponibles en el catálogo.
CREATE TABLE IF NOT EXISTS categorias (
  id_categoria SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE,
  descripcion VARCHAR(180) NULL,
  activa BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- 5. Productos publicados por vendedores.
CREATE TABLE IF NOT EXISTS productos (
  id_producto BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_vendedor BIGINT UNSIGNED NOT NULL,
  id_categoria SMALLINT UNSIGNED NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT NOT NULL,
  precio DECIMAL(12,2) NOT NULL,
  cantidad_disponible INT UNSIGNED NOT NULL DEFAULT 1,
  condicion ENUM('nuevo','usado','reacondicionado') NOT NULL DEFAULT 'nuevo',
  ciudad VARCHAR(100) NULL,
  estado ENUM('borrador','publicado','pausado','vendido','eliminado') NOT NULL DEFAULT 'borrador',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_producto_precio CHECK (precio > 0),
  CONSTRAINT fk_productos_vendedor FOREIGN KEY (id_vendedor)
    REFERENCES usuarios(id_usuario),
  CONSTRAINT fk_productos_categoria FOREIGN KEY (id_categoria)
    REFERENCES categorias(id_categoria),
  INDEX idx_productos_catalogo (estado, id_categoria, creado_en),
  INDEX idx_productos_vendedor (id_vendedor, estado),
  FULLTEXT KEY ft_productos_busqueda (nombre, descripcion)
) ENGINE=InnoDB;

-- 6. Imágenes: se guarda la ruta del archivo, no la imagen binaria.
CREATE TABLE IF NOT EXISTS producto_imagenes (
  id_imagen BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_producto BIGINT UNSIGNED NOT NULL,
  ruta_imagen VARCHAR(255) NOT NULL,
  texto_alternativo VARCHAR(180) NULL,
  es_principal BOOLEAN NOT NULL DEFAULT FALSE,
  orden SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  CONSTRAINT fk_imagenes_producto FOREIGN KEY (id_producto)
    REFERENCES productos(id_producto)
    ON DELETE CASCADE,
  INDEX idx_imagenes_producto (id_producto, orden)
) ENGINE=InnoDB;

-- 7. Favoritos de cada comprador.
CREATE TABLE IF NOT EXISTS favoritos (
  id_usuario BIGINT UNSIGNED NOT NULL,
  id_producto BIGINT UNSIGNED NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario, id_producto),
  CONSTRAINT fk_favoritos_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  CONSTRAINT fk_favoritos_producto FOREIGN KEY (id_producto)
    REFERENCES productos(id_producto) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Un carrito activo por usuario.
CREATE TABLE IF NOT EXISTS carritos (
  id_carrito BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario BIGINT UNSIGNED NOT NULL UNIQUE,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_carritos_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Productos y cantidades dentro del carrito.
CREATE TABLE IF NOT EXISTS carrito_detalles (
  id_carrito BIGINT UNSIGNED NOT NULL,
  id_producto BIGINT UNSIGNED NOT NULL,
  cantidad INT UNSIGNED NOT NULL DEFAULT 1,
  agregado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_carrito, id_producto),
  CONSTRAINT chk_carrito_cantidad CHECK (cantidad > 0),
  CONSTRAINT fk_detalle_carrito FOREIGN KEY (id_carrito)
    REFERENCES carritos(id_carrito) ON DELETE CASCADE,
  CONSTRAINT fk_detalle_producto FOREIGN KEY (id_producto)
    REFERENCES productos(id_producto)
) ENGINE=InnoDB;

-- 10. Pedidos: se crean cuando el comprador confirma una compra.
CREATE TABLE IF NOT EXISTS pedidos (
  id_pedido BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_comprador BIGINT UNSIGNED NOT NULL,
  fecha_pedido TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('pendiente','confirmado','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  costo_envio DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  direccion_entrega VARCHAR(255) NULL,
  observaciones VARCHAR(500) NULL,
  CONSTRAINT chk_pedido_totales CHECK (subtotal >= 0 AND costo_envio >= 0 AND total >= 0),
  CONSTRAINT fk_pedidos_comprador FOREIGN KEY (id_comprador)
    REFERENCES usuarios(id_usuario),
  INDEX idx_pedidos_comprador_fecha (id_comprador, fecha_pedido)
) ENGINE=InnoDB;

-- 11. Detalle del pedido: conserva precio y nombre al momento de la compra.
CREATE TABLE IF NOT EXISTS pedido_detalles (
  id_detalle BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pedido BIGINT UNSIGNED NOT NULL,
  id_producto BIGINT UNSIGNED NOT NULL,
  id_vendedor BIGINT UNSIGNED NOT NULL,
  nombre_producto VARCHAR(150) NOT NULL,
  precio_unitario DECIMAL(12,2) NOT NULL,
  cantidad INT UNSIGNED NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  CONSTRAINT chk_detalle_pedido CHECK (
    precio_unitario >= 0 AND cantidad > 0 AND subtotal >= 0
  ),
  CONSTRAINT fk_detalles_pedido FOREIGN KEY (id_pedido)
    REFERENCES pedidos(id_pedido) ON DELETE CASCADE,
  CONSTRAINT fk_detalles_producto FOREIGN KEY (id_producto)
    REFERENCES productos(id_producto),
  CONSTRAINT fk_detalles_vendedor FOREIGN KEY (id_vendedor)
    REFERENCES usuarios(id_usuario),
  INDEX idx_detalles_vendedor (id_vendedor)
) ENGINE=InnoDB;

-- Datos iniciales: roles.
INSERT INTO roles (nombre, descripcion) VALUES
  ('comprador', 'Puede explorar productos y realizar pedidos'),
  ('vendedor', 'Puede publicar y administrar productos'),
  ('administrador', 'Puede administrar la plataforma')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- Categorías iniciales basadas en las opciones del formulario y catálogo.
INSERT INTO categorias (nombre, descripcion) VALUES
  ('Tecnología', 'Celulares, consolas y dispositivos tecnológicos'),
  ('Electrónica', 'Equipos y accesorios electrónicos'),
  ('Accesorios', 'Accesorios personales y tecnológicos'),
  ('Computadores', 'Portátiles, computadores y periféricos'),
  ('Fotografía', 'Cámaras y equipos fotográficos'),
  ('Hogar y Muebles', 'Artículos para el hogar y mobiliario'),
  ('Moda', 'Ropa, calzado y complementos'),
  ('Otros', 'Productos de otras categorías')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- NOTA: no se crean usuarios de prueba ni contraseñas por defecto.
-- Las contraseñas deben guardarse desde el backend con password_hash()
-- de PHP y verificarse con password_verify().
