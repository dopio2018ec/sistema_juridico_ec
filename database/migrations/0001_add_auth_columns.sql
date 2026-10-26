-- ============================================================
-- Migracion 0001: columnas necesarias para autenticacion segura
-- No modifica ni elimina columnas existentes; solo agrega.
--
-- Justificacion:
--  - ultimo_acceso: requerido por el requerimiento ("Registro de
--    ultimo acceso, si se incorpora el campo correspondiente").
--  - intentos_fallidos / bloqueado_hasta: requeridos para el
--    bloqueo temporal ante multiples intentos fallidos de login.
-- ============================================================

USE sistema_juridico_ec;

ALTER TABLE usuarios
    ADD COLUMN ultimo_acceso DATETIME NULL AFTER estado,
    ADD COLUMN intentos_fallidos SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER ultimo_acceso,
    ADD COLUMN bloqueado_hasta DATETIME NULL AFTER intentos_fallidos;
