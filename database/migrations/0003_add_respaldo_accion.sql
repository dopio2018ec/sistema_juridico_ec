-- ============================================================
-- Migracion 0003: accion de auditoria para respaldos de BD
-- No modifica columnas existentes ni elimina valores del ENUM;
-- solo agrega 'RESPALDO' a los ya existentes.
--
-- Justificacion: se agrega la funcionalidad de generar un respaldo
-- de la base de datos desde el panel de administracion (solo rol
-- ADMINISTRADOR). Cada respaldo generado debe quedar registrado en
-- auditoria igual que el resto de acciones sensibles del sistema.
-- ============================================================

USE sistema_juridico_ec;

ALTER TABLE auditoria
    MODIFY accion ENUM(
        'INSERT',
        'UPDATE',
        'DELETE',
        'PUBLICACION',
        'APROBACION',
        'RESPALDO'
    ) NOT NULL;
