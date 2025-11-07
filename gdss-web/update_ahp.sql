-- ==================================================
-- GDSS Database Update - Add AHP Tables
-- Menambahkan tabel untuk Analytic Hierarchy Process
-- ==================================================

USE gdss_db;

-- ==================================================
-- Tabel AHP Pairwise Comparisons - Perbandingan berpasangan
-- ==================================================
CREATE TABLE IF NOT EXISTS ahp_comparisons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    part ENUM('teknis', 'administrasi', 'keuangan') NOT NULL,
    comparison_type ENUM('criteria', 'alternatives') NOT NULL,
    criteria_id INT NULL, -- NULL untuk criteria comparison, filled untuk alternatives comparison
    item1_id INT NOT NULL, -- ID kriteria atau alternatif pertama
    item2_id INT NOT NULL, -- ID kriteria atau alternatif kedua
    value DECIMAL(4,2) NOT NULL, -- Nilai perbandingan (1-9, bisa pecahan)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (criteria_id) REFERENCES criteria(id) ON DELETE CASCADE,
    UNIQUE KEY unique_comparison (user_id, project_id, part, comparison_type, criteria_id, item1_id, item2_id),
    INDEX idx_user_project (user_id, project_id),
    INDEX idx_part_type (part, comparison_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================
-- Tabel AHP Results - Hasil perhitungan AHP
-- ==================================================
CREATE TABLE IF NOT EXISTS ahp_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    part ENUM('teknis', 'administrasi', 'keuangan') NOT NULL,
    result_type ENUM('criteria_weights', 'alternative_scores', 'final_scores') NOT NULL,
    criteria_id INT NULL, -- NULL untuk criteria_weights dan final_scores
    item_id INT NOT NULL, -- ID kriteria atau alternatif
    weight_or_score DECIMAL(10,6) NOT NULL,
    priority_vector JSON, -- Untuk menyimpan seluruh priority vector
    consistency_ratio DECIMAL(5,3) NULL, -- CR untuk validasi
    lambda_max DECIMAL(10,6) NULL, -- Eigenvalue maksimum
    ci_value DECIMAL(5,3) NULL, -- Consistency Index
    is_consistent BOOLEAN DEFAULT FALSE, -- CR <= 0.1
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (criteria_id) REFERENCES criteria(id) ON DELETE CASCADE,
    INDEX idx_user_project_part (user_id, project_id, part),
    INDEX idx_result_type (result_type),
    INDEX idx_consistency (is_consistent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================
-- Tabel AHP Final Rankings - Ranking akhir per decision maker
-- ==================================================
CREATE TABLE IF NOT EXISTS ahp_rankings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    part ENUM('teknis', 'administrasi', 'keuangan') NOT NULL,
    final_score DECIMAL(10,6) NOT NULL, -- Skor global per DM
    rank_position INT NOT NULL, -- Ranking dalam bidang ini
    global_weights JSON, -- Bobot kriteria
    alternative_scores JSON, -- Skor alternatif per kriteria
    calculation_details JSON, -- Detail perhitungan untuk audit
    is_consistent BOOLEAN DEFAULT FALSE, -- Semua CR <= 0.1
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_ranking (user_id, project_id, part),
    INDEX idx_part_rank (part, rank_position),
    INDEX idx_score (final_score DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================
-- Random Consistency Index (RI) - Tabel referensi
-- ==================================================
CREATE TABLE IF NOT EXISTS ahp_random_index (
    n INT PRIMARY KEY, -- Ukuran matriks
    ri_value DECIMAL(4,2) NOT NULL -- Nilai Random Index
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Random Index values (Saaty)
INSERT INTO ahp_random_index (n, ri_value) VALUES
(1, 0.00), (2, 0.00), (3, 0.52), (4, 0.89), (5, 1.11),
(6, 1.25), (7, 1.35), (8, 1.40), (9, 1.45), (10, 1.49),
(11, 1.52), (12, 1.54), (13, 1.56), (14, 1.58), (15, 1.59)
ON DUPLICATE KEY UPDATE ri_value = VALUES(ri_value);

-- ==================================================
-- Update part_weights untuk AHP (tetap sama untuk BORDA)
-- ==================================================
UPDATE part_weights SET description = 'Bobot bidang untuk agregasi BORDA (dari ranking AHP)' WHERE part IN ('teknis', 'administrasi', 'keuangan');

-- ==================================================
-- View untuk mempermudah query AHP results
-- ==================================================
CREATE OR REPLACE VIEW v_ahp_summary AS
SELECT 
    r.user_id,
    u.fullname,
    u.role,
    r.project_id,
    p.code as project_code,
    p.name as project_name,
    r.part,
    r.final_score,
    r.rank_position,
    r.is_consistent,
    AVG(res.consistency_ratio) as avg_cr,
    COUNT(CASE WHEN res.is_consistent = 1 THEN 1 END) as consistent_matrices,
    COUNT(res.id) as total_matrices,
    r.created_at
FROM ahp_rankings r
JOIN users u ON r.user_id = u.id
JOIN projects p ON r.project_id = p.id
LEFT JOIN ahp_results res ON r.user_id = res.user_id AND r.project_id = res.project_id AND r.part = res.part
GROUP BY r.id;

SELECT 'AHP tables created successfully!' as status;