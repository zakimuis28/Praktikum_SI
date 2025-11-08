-- ==================================================
-- GDSS Database Update - Admin to Supervisor Role Migration
-- Mengubah role "admin" menjadi "supervisor" untuk konsistensi
-- ==================================================

USE gdss_db;

-- 1. Update existing users with 'admin' role to 'supervisor'
UPDATE users SET role = 'supervisor' WHERE role = 'admin';

-- 2. Update fullname for supervisor users to be more appropriate
UPDATE users SET fullname = 'Supervisor Sistem' WHERE role = 'supervisor' AND fullname = 'Administrator Sistem';

-- 3. Modify the ENUM column to use 'supervisor' instead of 'admin'
ALTER TABLE users MODIFY COLUMN role ENUM('supervisor', 'teknis', 'administrasi', 'keuangan') NOT NULL;

-- 4. Verify the changes
SELECT id, username, fullname, role FROM users WHERE role = 'supervisor';

-- ==================================================
-- Optional: Add new supervisor user if needed
-- ==================================================
-- INSERT INTO users (username, password, fullname, role) VALUES
-- ('supervisor', 'supervisor123', 'Supervisor Proyek IT', 'supervisor');

-- ==================================================
-- Verification Query - Check all users
-- ==================================================
-- SELECT * FROM users ORDER BY role;