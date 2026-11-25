-- Migración de verificación de correo para CompraVenta OMT
-- Ejecutar UNA SOLA VEZ en MySQL Workbench sobre compraventa_omt.
USE compraventa_omt;

-- Las cuentas que ya existían se consideran verificadas para no bloquear
-- a los usuarios actuales. Los registros nuevos quedarán en 0 desde PHP.
ALTER TABLE usuarios
  ADD COLUMN correo_verificado TINYINT(1) NOT NULL DEFAULT 0
  AFTER password_hash;

UPDATE usuarios
SET correo_verificado = 1;

CREATE TABLE IF NOT EXISTS verificaciones_correo (
  id_usuario BIGINT UNSIGNED NOT NULL,
  codigo_hash VARCHAR(255) NOT NULL,
  expira_en DATETIME NOT NULL,
  intentos TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ultimo_envio_en DATETIME NOT NULL,
  usado_en DATETIME NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  CONSTRAINT fk_verificaciones_correo_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    ON DELETE CASCADE
) ENGINE=InnoDB;
