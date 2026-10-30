-- ============================================================
-- Migración: foto de perfil del responsable (solo SIAP ya instalado)
-- Ejecutar UNA VEZ contra la base de datos existente (siap_db).
-- En instalaciones nuevas no hace falta: siap_db.sql ya incluye
-- la columna `foto` en el CREATE TABLE de `responsables`.
-- ============================================================

ALTER TABLE `responsables`
  ADD COLUMN `foto` VARCHAR(150) DEFAULT NULL AFTER `password`;
