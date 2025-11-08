-- ==================================================
-- GDSS (Group Decision Support System) Database Setup
-- Sistem pendukung keputusan untuk prioritas proyek IT
-- ==================================================

-- Drop database jika sudah ada (untuk development)
DROP DATABASE IF EXISTS gdss_db;

-- Buat database baru
CREATE DATABASE gdss_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gdss_db;

-- ==================================================
-- Tabel Users - Sistem login multi-role
-- ==================================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    role ENUM('supervisor', 'teknis', 'administrasi', 'keuangan') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Data contoh users
INSERT INTO users (username, password, fullname, role) VALUES
('supervisor', 'supervisor123', 'Supervisor Sistem', 'supervisor'),
('teknis', 'teknis123', 'Bidang Teknis', 'teknis'),
('administrasi', 'admin123', 'Bidang Administrasi', 'administrasi'),
('keuangan', 'keuangan123', 'Bidang Keuangan', 'keuangan');

-- ==================================================
-- Tabel Projects - Daftar proyek yang akan dinilai
-- ==================================================
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    location VARCHAR(100) NOT NULL,
    date_offer DATE NOT NULL,
    description TEXT,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Data contoh proyek
INSERT INTO projects (code, name, location, date_offer, description) VALUES
('PRJ001', 'Sistem Informasi Manajemen Karyawan', 'Kantor Pusat Jakarta', '2024-01-15', 'Pengembangan SIMPEG untuk mengelola data karyawan dan absensi'),
('PRJ002', 'Upgrade Infrastructure Server', 'Data Center Bandung', '2024-01-20', 'Peningkatan kapasitas server dan storage untuk mendukung operasional'),
('PRJ003', 'Aplikasi Mobile Customer Service', 'Semua Cabang', '2024-02-01', 'Aplikasi mobile untuk layanan customer service dan complaint handling'),
('PRJ004', 'Sistem Monitoring Keamanan', 'Kantor Pusat & Cabang', '2024-02-10', 'Implementasi sistem CCTV dan monitoring keamanan terintegrasi'),
('PRJ005', 'Portal E-Learning Karyawan', 'Online Platform', '2024-02-15', 'Platform pembelajaran online untuk training dan development karyawan');

-- ==================================================
-- Tabel Criteria - Kriteria penilaian per bidang
-- ==================================================
CREATE TABLE criteria (
    id INT PRIMARY KEY AUTO_INCREMENT,
    part ENUM('teknis', 'administrasi', 'keuangan') NOT NULL,
    name VARCHAR(100) NOT NULL,
    weight DECIMAL(5,3) NOT NULL,
    type ENUM('benefit', 'cost') DEFAULT 'benefit',
    description TEXT
);

-- Kriteria penilaian per bidang
INSERT INTO criteria (part, name, weight, type, description) VALUES
-- Kriteria Teknis
('teknis', 'Kompleksitas Teknis', 0.300, 'cost', 'Tingkat kesulitan implementasi teknis (semakin rendah semakin baik)'),
('teknis', 'Kesesuaian Teknologi', 0.250, 'benefit', 'Kesesuaian dengan teknologi yang sudah ada'),
('teknis', 'Keamanan Sistem', 0.200, 'benefit', 'Tingkat keamanan dan security yang dihasilkan'),
('teknis', 'Skalabilitas', 0.150, 'benefit', 'Kemampuan sistem untuk dikembangkan di masa depan'),
('teknis', 'Maintenance', 0.100, 'cost', 'Tingkat kesulitan maintenance (semakin rendah semakin baik)'),

-- Kriteria Administrasi
('administrasi', 'Kesesuaian Regulasi', 0.350, 'benefit', 'Tingkat kesesuaian dengan regulasi dan standar yang berlaku'),
('administrasi', 'Dampak Operasional', 0.250, 'benefit', 'Dampak positif terhadap operasional perusahaan'),
('administrasi', 'Waktu Implementasi', 0.200, 'cost', 'Lamanya waktu implementasi (semakin cepat semakin baik)'),
('administrasi', 'Risiko Proyek', 0.200, 'cost', 'Tingkat risiko kegagalan proyek (semakin rendah semakin baik)'),

-- Kriteria Keuangan
('keuangan', 'Biaya Investasi', 0.400, 'cost', 'Total biaya investasi awal (semakin rendah semakin baik)'),
('keuangan', 'ROI Estimasi', 0.300, 'benefit', 'Estimasi return on investment'),
('keuangan', 'Biaya Operasional', 0.200, 'cost', 'Biaya operasional tahunan (semakin rendah semakin baik)'),
('keuangan', 'Payback Period', 0.100, 'cost', 'Waktu balik modal (semakin cepat semakin baik)');

-- ==================================================
-- Tabel Evaluations - Hasil penilaian user terhadap proyek
-- ==================================================
CREATE TABLE evaluations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    criteria_id INT NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (criteria_id) REFERENCES criteria(id) ON DELETE CASCADE,
    UNIQUE KEY unique_evaluation (project_id, user_id, criteria_id)
);

-- ==================================================
-- Tabel Part Weights - Bobot kepentingan per bidang untuk metode BORDA
-- ==================================================
CREATE TABLE part_weights (
    part ENUM('teknis', 'administrasi', 'keuangan') PRIMARY KEY,
    weight DECIMAL(5,3) NOT NULL,
    description TEXT
);

-- Bobot bidang untuk aggregasi BORDA
INSERT INTO part_weights (part, weight, description) VALUES
('teknis', 0.538, 'Bobot kepentingan bidang teknis (7/13)'),
('administrasi', 0.308, 'Bobot kepentingan bidang administrasi (4/13)'),
('keuangan', 0.154, 'Bobot kepentingan bidang keuangan (2/13)');

-- ==================================================
-- View untuk laporan hasil evaluasi
-- ==================================================
CREATE VIEW v_evaluation_summary AS
SELECT 
    p.code as project_code,
    p.name as project_name,
    u.fullname as evaluator,
    u.role as evaluator_role,
    c.name as criteria_name,
    e.score,
    e.created_at as evaluation_date
FROM evaluations e
JOIN projects p ON e.project_id = p.id
JOIN users u ON e.user_id = u.id
JOIN criteria c ON e.criteria_id = c.id
ORDER BY p.code, u.role, c.name;

-- ==================================================
-- Stored Procedure untuk reset evaluasi (development purpose)
-- ==================================================
DELIMITER //
CREATE PROCEDURE ResetEvaluations()
BEGIN
    DELETE FROM evaluations;
    ALTER TABLE evaluations AUTO_INCREMENT = 1;
    SELECT 'All evaluations have been reset' as message;
END//
DELIMITER ;

-- ==================================================
-- Index untuk optimasi query
-- ==================================================
CREATE INDEX idx_evaluations_project ON evaluations(project_id);
CREATE INDEX idx_evaluations_user ON evaluations(user_id);
CREATE INDEX idx_evaluations_criteria ON evaluations(criteria_id);
CREATE INDEX idx_criteria_part ON criteria(part);
CREATE INDEX idx_projects_status ON projects(status);

-- ==================================================
-- Contoh data evaluasi untuk testing (opsional)
-- ==================================================
-- Bisa dijalankan setelah sistem selesai untuk testing
/*
INSERT INTO evaluations (project_id, user_id, criteria_id, score) VALUES
-- Evaluasi dari user 'teknis' (id=2) untuk proyek 1
(1, 2, 1, 7.5), -- Kompleksitas Teknis
(1, 2, 2, 8.0), -- Kesesuaian Teknologi
(1, 2, 3, 9.0), -- Keamanan Sistem
(1, 2, 4, 8.5), -- Skalabilitas
(1, 2, 5, 7.0); -- Maintenance
*/

SELECT 'GDSS Database setup completed successfully!' as status;