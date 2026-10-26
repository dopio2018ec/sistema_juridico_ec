-- ============================================================
-- Sistema Juridico de Ecuador - MariaDB
-- Modelo: almacenar unicamente el texto vigente de los articulos
-- Charset: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS sistema_juridico_ec
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sistema_juridico_ec;

-- ------------------------------------------------------------
-- 1. Roles
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. Usuarios
-- password_hash debe contener un hash seguro, nunca texto plano.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    correo VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. Relacion muchos-a-muchos entre usuarios y roles
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios_roles (
    usuario_id BIGINT UNSIGNED NOT NULL,
    rol_id BIGINT UNSIGNED NOT NULL,
    fecha_asignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, rol_id),
    CONSTRAINT fk_usuarios_roles_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_usuarios_roles_rol
        FOREIGN KEY (rol_id) REFERENCES roles(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. Catalogo de tipos de norma
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tipos_norma (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO tipos_norma (codigo, nombre, descripcion) VALUES
('CONSTITUCION', 'Constitución', 'Norma constitucional'),
('CODIGO', 'Código', 'Código legal'),
('LEY_ORGANICA', 'Ley orgánica', 'Ley de carácter orgánico'),
('LEY_ORDINARIA', 'Ley ordinaria', 'Ley ordinaria'),
('ACUERDO_MINISTERIAL', 'Acuerdo ministerial', 'Acuerdo emitido por un ministerio'),
('RESOLUCION', 'Resolución', 'Resolución de una autoridad competente');

-- ------------------------------------------------------------
-- 5. Normas juridicas
-- El estado es una clasificación administrativa; la vigencia legal
-- debe verificarse con fuentes oficiales y las disposiciones aplicables.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS normas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_norma_id BIGINT UNSIGNED NOT NULL,
    titulo VARCHAR(500) NOT NULL,
    numero_norma VARCHAR(100) NULL,
    siglas VARCHAR(100) NULL,
    entidad_emisora VARCHAR(255) NULL,
    fecha_emision DATE NULL,
    fecha_publicacion DATE NULL,
    fecha_vigencia DATE NULL,
    fecha_derogatoria DATE NULL,
    estado ENUM(
        'BORRADOR',
        'PUBLICADA',
        'VIGENTE',
        'DEROGADA',
        'ARCHIVADA'
    ) NOT NULL DEFAULT 'BORRADOR',
    observaciones TEXT NULL,
    creado_por BIGINT UNSIGNED NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_normas_tipo
        FOREIGN KEY (tipo_norma_id) REFERENCES tipos_norma(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_normas_creador
        FOREIGN KEY (creado_por) REFERENCES usuarios(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_normas_titulo (titulo(191)),
    INDEX idx_normas_tipo_estado (tipo_norma_id, estado),
    INDEX idx_normas_fecha_publicacion (fecha_publicacion),
    INDEX idx_normas_numero (numero_norma)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. Publicaciones oficiales y fuentes documentales
-- Una norma puede tener varias referencias documentales.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS publicaciones_oficiales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    norma_id BIGINT UNSIGNED NOT NULL,
    nombre_publicacion VARCHAR(150) NOT NULL DEFAULT 'Registro Oficial',
    numero VARCHAR(50) NULL,
    suplemento VARCHAR(100) NULL,
    fecha_publicacion DATE NULL,
    pagina_inicial VARCHAR(30) NULL,
    pagina_final VARCHAR(30) NULL,
    url_fuente VARCHAR(2048) NULL,
    observaciones TEXT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_publicaciones_norma
        FOREIGN KEY (norma_id) REFERENCES normas(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_publicaciones_numero_fecha (numero, fecha_publicacion),
    INDEX idx_publicaciones_norma (norma_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. Articulos, considerandos y disposiciones
-- Se almacena directamente el texto vigente en contenido.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS articulos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    norma_id BIGINT UNSIGNED NOT NULL,
    articulo_padre_id BIGINT UNSIGNED NULL,
    articulo_numero VARCHAR(50) NOT NULL,
    titulo VARCHAR(500) NULL,
    tipo_contenido ENUM(
        'CONSIDERANDO',
        'ARTICULO',
        'DISPOSICION_GENERAL',
        'DISPOSICION_TRANSITORIA',
        'DISPOSICION_REFORMATORIA',
        'DISPOSICION_DEROGATORIA',
        'DISPOSICION_FINAL',
        'OTRO'
    ) NOT NULL DEFAULT 'ARTICULO',
    contenido LONGTEXT NOT NULL,
    orden INT UNSIGNED NOT NULL DEFAULT 0,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    creado_por BIGINT UNSIGNED NULL,
    actualizado_por BIGINT UNSIGNED NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_articulos_norma
        FOREIGN KEY (norma_id) REFERENCES normas(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_articulos_padre
        FOREIGN KEY (articulo_padre_id) REFERENCES articulos(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_articulos_creador
        FOREIGN KEY (creado_por) REFERENCES usuarios(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_articulos_actualizador
        FOREIGN KEY (actualizado_por) REFERENCES usuarios(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_articulos_norma_orden (norma_id, orden),
    INDEX idx_articulos_numero (articulo_numero),
    INDEX idx_articulos_tipo (tipo_contenido),
    FULLTEXT INDEX ft_articulos_contenido (contenido)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 8. Relaciones entre normas
-- norma_origen_id: norma que reforma/deroga.
-- norma_destino_id: norma afectada.
-- Los articulos asociados son opcionales.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS relaciones_normativas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    norma_origen_id BIGINT UNSIGNED NOT NULL,
    norma_destino_id BIGINT UNSIGNED NOT NULL,
    tipo_relacion ENUM(
        'REFORMA',
        'DEROGACION_TOTAL',
        'DEROGACION_PARCIAL',
        'SUSTITUCION',
        'ACLARACION',
        'OTRA'
    ) NOT NULL,
    articulo_origen_id BIGINT UNSIGNED NULL,
    articulo_destino_id BIGINT UNSIGNED NULL,
    fecha_efecto DATE NULL,
    descripcion TEXT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_relaciones_norma_origen
        FOREIGN KEY (norma_origen_id) REFERENCES normas(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_relaciones_norma_destino
        FOREIGN KEY (norma_destino_id) REFERENCES normas(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_relaciones_articulo_origen
        FOREIGN KEY (articulo_origen_id) REFERENCES articulos(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_relaciones_articulo_destino
        FOREIGN KEY (articulo_destino_id) REFERENCES articulos(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_relaciones_origen (norma_origen_id),
    INDEX idx_relaciones_destino (norma_destino_id),
    INDEX idx_relaciones_fecha_efecto (fecha_efecto)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 9. Auditoria
-- detalle JSON requiere una version de MariaDB compatible con JSON.
-- La aplicacion debe insertar eventos; la tabla no audita por si sola.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auditoria (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NULL,
    tabla_afectada VARCHAR(100) NOT NULL,
    registro_id BIGINT UNSIGNED NOT NULL,
    accion ENUM(
        'INSERT',
        'UPDATE',
        'DELETE',
        'PUBLICACION',
        'APROBACION'
    ) NOT NULL,
    detalle JSON NULL,
    fecha_evento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_auditoria_registro (tabla_afectada, registro_id),
    INDEX idx_auditoria_usuario_fecha (usuario_id, fecha_evento)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 10. Roles iniciales
-- ------------------------------------------------------------
INSERT IGNORE INTO roles (nombre, descripcion) VALUES
('ADMINISTRADOR', 'Administra usuarios, permisos y configuracion'),
('EDITOR_JURIDICO', 'Registra y actualiza normas y articulos'),
('REVISOR', 'Revisa y valida el contenido juridico'),
('CONSULTA', 'Puede consultar la legislacion');

-- ------------------------------------------------------------
-- Consultas de ejemplo (ejecutar por separado si se desea)
-- ------------------------------------------------------------
-- Listar normas por tipo:
-- SELECT n.id, tn.nombre AS tipo_norma, n.titulo, n.numero_norma,
--        n.fecha_publicacion, n.estado
-- FROM normas n
-- JOIN tipos_norma tn ON tn.id = n.tipo_norma_id
-- WHERE tn.codigo = 'LEY_ORGANICA'
-- ORDER BY n.fecha_publicacion DESC;

-- Buscar palabras en articulos:
-- SELECT n.titulo, a.articulo_numero, a.contenido
-- FROM articulos a
-- JOIN normas n ON n.id = a.norma_id
-- WHERE MATCH(a.contenido) AGAINST ('lavado activos' IN BOOLEAN MODE);

-- NOTA:
-- Este esquema guarda el texto actual, no el historial de textos anteriores.
-- Verificar siempre las reformas y vigencia con fuentes oficiales del Ecuador.
