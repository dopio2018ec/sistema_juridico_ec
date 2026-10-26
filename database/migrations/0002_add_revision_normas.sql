-- ============================================================
-- Migracion 0002: marca de revision/validacion juridica
-- No modifica columnas existentes; solo agrega.
--
-- Justificacion: el requerimiento pide una segunda revision antes
-- de considerar confiable el estado de vigencia ("Confirmacion de
-- cambios juridicos") y prohibe afirmar vigencia sin evidencia
-- suficiente. Estas columnas permiten registrar que un REVISOR
-- valido la norma, y la aplicacion las usa para decidir si puede
-- mostrar "vigente" o debe mostrar "pendiente de verificacion".
-- ============================================================

USE sistema_juridico_ec;

ALTER TABLE normas
    ADD COLUMN revisado_por BIGINT UNSIGNED NULL AFTER creado_por,
    ADD COLUMN fecha_revision DATETIME NULL AFTER revisado_por,
    ADD CONSTRAINT fk_normas_revisor
        FOREIGN KEY (revisado_por) REFERENCES usuarios(id)
        ON DELETE RESTRICT ON UPDATE CASCADE;
