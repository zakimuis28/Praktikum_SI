-- ==================================================
-- UPDATE DATABASE SCHEMA FOR AHP IMPLEMENTATION
-- Tambahan tabel untuk Analytic Hierarchy Process
-- ==================================================

USE gdss_db;

-- Table untuk menyimpan pairwise comparison matrix
CREATE TABLE IF NOT EXISTS ahp_pairwise_comparisons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    comparison_type ENUM('criteria', 'alternatives') NOT NULL,
    criteria_id INT NULL COMMENT 'NULL untuk comparison criteria, filled untuk comparison alternatives under criteria',
    project_id INT NULL COMMENT 'NULL untuk criteria comparison, filled untuk alternatives comparison',
    element_i INT NOT NULL COMMENT 'ID kriteria/alternatif i',
    element_j INT NOT NULL COMMENT 'ID kriteria/alternatif j', 
    comparison_value DECIMAL(5,3) NOT NULL COMMENT 'Nilai perbandingan i terhadap j (skala Saaty 1-9)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (criteria_id) REFERENCES criteria(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_comparison (user_id, comparison_type, criteria_id, project_id, element_i, element_j),
    INDEX idx_user_type (user_id, comparison_type),
    INDEX idx_criteria_project (criteria_id, project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table untuk menyimpan hasil perhitungan AHP
CREATE TABLE IF NOT EXISTS ahp_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    project_id INT NULL COMMENT 'NULL untuk criteria weights, filled untuk alternatives scores',
    criteria_id INT NULL COMMENT 'NULL untuk criteria weights, filled untuk alternatives under criteria',
    calculation_type ENUM('criteria_weights', 'alternatives_scores', 'global_scores') NOT NULL,
    matrix_data JSON NOT NULL COMMENT 'Pairwise comparison matrix',
    priority_vector JSON NOT NULL COMMENT 'Hasil perhitungan priority vector',
    lambda_max DECIMAL(10,6) NOT NULL COMMENT 'Maximum eigenvalue',
    consistency_index DECIMAL(10,6) NOT NULL COMMENT 'CI = (lambda_max - n)/(n-1)',
    consistency_ratio DECIMAL(10,6) NOT NULL COMMENT 'CR = CI/RI',
    is_consistent BOOLEAN NOT NULL COMMENT 'CR <= 0.1',
    global_scores JSON NULL COMMENT 'Skor global untuk setiap alternatif (hanya untuk global_scores)',
    ranking JSON NULL COMMENT 'Ranking alternatif (hanya untuk global_scores)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (criteria_id) REFERENCES criteria(id) ON DELETE CASCADE,
    INDEX idx_user_project (user_id, project_id),
    INDEX idx_calculation_type (calculation_type),
    INDEX idx_consistency (is_consistent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table untuk Random Index (RI) values untuk konsistensi check
CREATE TABLE IF NOT EXISTS ahp_random_index (
    matrix_size INT PRIMARY KEY,
    random_index DECIMAL(4,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Random Index values (Saaty's RI table)
INSERT INTO ahp_random_index (matrix_size, random_index) VALUES
(1, 0.000),
(2, 0.000), 
(3, 0.520),
(4, 0.890),
(5, 1.110),
(6, 1.250),
(7, 1.350),
(8, 1.400),
(9, 1.450),
(10, 1.490),
(11, 1.510),
(12, 1.540),
(13, 1.560),
(14, 1.570),
(15, 1.580)
ON DUPLICATE KEY UPDATE random_index = VALUES(random_index);

-- Update existing consensus_results table untuk AHP
ALTER TABLE consensus_results 
ADD COLUMN IF NOT EXISTS ahp_teknis_score DECIMAL(10,6) NULL COMMENT 'AHP global score for teknis',
ADD COLUMN IF NOT EXISTS ahp_administrasi_score DECIMAL(10,6) NULL COMMENT 'AHP global score for administrasi', 
ADD COLUMN IF NOT EXISTS ahp_keuangan_score DECIMAL(10,6) NULL COMMENT 'AHP global score for keuangan',
ADD COLUMN IF NOT EXISTS ahp_consistency_status JSON NULL COMMENT 'Consistency status for each DM';

-- Verification queries
SELECT 'AHP schema update completed!' as Status;
SELECT 'Total tables:' as Info, COUNT(*) as Count FROM information_schema.tables WHERE table_schema = 'gdss_db';
SELECT 'AHP tables:' as Info, table_name FROM information_schema.tables 
WHERE table_schema = 'gdss_db' AND table_name LIKE 'ahp%';