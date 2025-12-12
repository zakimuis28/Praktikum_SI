# 🎯 GDSS - Group Decision Support System

> **Sistem Pendukung Keputusan Kelompok untuk Evaluasi dan Prioritas Proyek IT menggunakan TOPSIS & BORDA Count**

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.0-38B2AC?logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 📋 Tentang Proyek

**GDSS (Group Decision Support System)** adalah sistem pendukung keputusan kelompok berbasis web yang mengimplementasikan dua metode Multi-Criteria Decision Making (MCDM):
- **TOPSIS** (Technique for Order Preference by Similarity to Ideal Solution) untuk evaluasi individual
- **BORDA Count** untuk agregasi konsensus kelompok dengan pembobotan

Sistem ini dirancang untuk membantu organisasi dalam mengambil keputusan kolektif yang objektif, terstruktur, dan transparan dalam memilih prioritas proyek IT berdasarkan multiple criteria dari berbagai perspektif decision maker.

---

## ✨ Fitur Utama

### 🔐 Multi-Role Authentication System
- **4 Role Pengguna**: Admin, Supervisor, Teknis, dan Keuangan
- Session management dengan security timeout
- Role-based access control (RBAC)
- Profile management dengan password update

### 📊 Metode TOPSIS (Individual Evaluation)
- Normalisasi matriks keputusan menggunakan vector normalization
- Pembobotan kriteria berdasarkan importance level
- Perhitungan jarak Euclidean ke solusi ideal positif (D⁺) dan negatif (D⁻)
- Ranking berdasarkan nilai preferensi (Ci = D⁻ / (D⁺ + D⁻))
- Evaluasi terpisah untuk setiap bidang: Supervisor, Teknis, Keuangan

### 🎯 Metode BORDA Count (Group Consensus)
- Konversi ranking TOPSIS ke poin BORDA (Poin = N - Rank + 1)
- Pembobotan berdasarkan authority level:
  - **Supervisor**: 7 (54%)
  - **Teknis**: 4 (31%)
  - **Keuangan**: 2 (15%)
- Agregasi weighted sum untuk konsensus final
- Transparansi kontribusi setiap decision maker

### 📈 Visualisasi & Reporting
- Interactive bar charts menggunakan Chart.js
- Color-coded ranking badges (🥇 Gold, 🥈 Silver, 🥉 Bronze)
- Detailed calculation matrices (normalisasi, weighted, distances)
- Comprehensive conclusion sections dengan rekomendasi
- Modal popup untuk metodologi dan detail perhitungan

### 📱 Modern User Interface
- Responsive design dengan Tailwind CSS v3
- Gradient color themes per feature:
  - **TOPSIS**: Blue-Cyan gradient
  - **BORDA**: Purple-Pink gradient
  - **Conclusions**: Emerald-Teal gradient
- Smooth transitions dan hover effects
- Mobile-first approach

### 📋 Management Features (Admin)
- **Project Management**: CRUD operations untuk proyek IT
- **Criteria Management**: Kelola kriteria per bidang dengan type (benefit/cost)
- **User Management**: Manage decision makers dan admin
- **Progress Tracking**: Monitor status evaluasi real-time
- **Result Analysis**: View detailed TOPSIS & BORDA calculations

---

## 🏗️ Arsitektur Sistem

### Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Backend** | PHP 8.0+ | Server-side logic & API |
| **Database** | MySQL 8.0+ | Data persistence |
| **Frontend** | Tailwind CSS 3.0 | Styling & responsive design |
| **Charts** | Chart.js 4.0 | Data visualization |
| **Icons** | Heroicons (SVG) | UI icons |
| **Server** | Apache/Nginx | Web server |

### Project Structure

```
uas_gdss/
├── api/
│   └── handler.php              # AJAX API endpoint
├── assets/
│   ├── css/
│   │   └── style.css            # Custom styles & animations
│   └── js/
│       └── gdss.js              # Frontend JavaScript logic
├── config/
│   └── config.php               # Database & system config
├── controllers/
│   ├── auth.php                 # Authentication logic
│   ├── borda_controller.php     # BORDA calculation engine
│   ├── criteria_controller.php  # Criteria CRUD
│   ├── project_controller.php   # Project CRUD
│   ├── score_controller.php     # Evaluation score management
│   └── topsis_controller.php    # TOPSIS calculation engine
├── database/
│   └── gdss_topsis.sql          # Database schema & sample data
├── includes/
│   └── layout.php               # Reusable UI components
├── views/
│   ├── admin/                   # Admin-only pages
│   │   ├── manage_criteria.php
│   │   ├── manage_projects.php
│   │   ├── manage_users.php
│   │   └── progress.php
│   └── results/                 # Calculation detail pages
│       ├── borda_detail.php
│       ├── topsis_detail.php
│       └── topsis_matrix.php
├── borda_result.php             # BORDA consensus results
├── dashboard.php                # Main dashboard
├── evaluate.php                 # Evaluation input form
├── index.php                    # Login page
├── logout.php                   # Logout handler
├── profile.php                  # User profile management
├── register.php                 # User registration
└── topsis_results.php           # TOPSIS individual results
```

### Database Schema

#### Tables Overview
- **users**: User accounts dengan role (admin, supervisor, teknis, keuangan)
- **projects**: Proyek IT yang akan dievaluasi
- **criteria**: Kriteria evaluasi per bidang (benefit/cost type)
- **scores**: Penilaian decision maker untuk setiap project-criteria
- **topsis_results**: Hasil perhitungan TOPSIS per bidang
- **borda_results**: Hasil konsensus BORDA final

#### Entity Relationship
```
users (1) ──→ (N) scores
projects (1) ──→ (N) scores
criteria (1) ──→ (N) scores
projects (1) ──→ (N) topsis_results
projects (1) ──→ (1) borda_results
```

---

## 🚀 Instalasi & Setup

### Prerequisites
- PHP >= 8.0 dengan ekstensi PDO & MySQL
- MySQL/MariaDB >= 8.0
- Apache/Nginx web server
- Composer (optional, untuk dependencies)

### Langkah Instalasi

#### 1️⃣ Clone Repository
```bash
git clone https://github.com/yourusername/gdss-topsis-borda.git
cd gdss-topsis-borda
```

#### 2️⃣ Setup Database
```bash
# Buat database baru
mysql -u root -p
CREATE DATABASE gdss_topsis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# Import schema & sample data
mysql -u root -p gdss_topsis < database/gdss_topsis.sql
```

#### 3️⃣ Konfigurasi Aplikasi
Edit file `config/config.php`:
```php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gdss_topsis');
define('DB_USER', 'root');          // Sesuaikan dengan user MySQL Anda
define('DB_PASS', 'your_password'); // Sesuaikan dengan password MySQL Anda
define('DB_CHARSET', 'utf8mb4');

// System configuration
define('SITE_URL', 'http://localhost/gdss/uas_gdss'); // Sesuaikan dengan URL Anda
define('SITE_NAME', 'GDSS - Group Decision Support System');
```

#### 4️⃣ Setup Web Server

**Untuk Laragon (Windows):**
1. Copy folder project ke `C:\laragon\www\`
2. Akses via browser: `http://localhost/gdss/uas_gdss`

**Untuk XAMPP:**
1. Copy folder project ke `C:\xampp\htdocs\`
2. Akses via browser: `http://localhost/gdss/uas_gdss`

**Untuk Linux/Mac (Apache):**
```bash
# Copy project ke document root
sudo cp -r uas_gdss /var/www/html/

# Set permissions
sudo chown -R www-data:www-data /var/www/html/uas_gdss
sudo chmod -R 755 /var/www/html/uas_gdss

# Restart Apache
sudo systemctl restart apache2
```

#### 5️⃣ Login Awal

Default credentials (setelah import database):
- **Admin**: `admin` / `admin123`
- **Supervisor**: `supervisor` / `supervisor123`
- **Teknis**: `teknis` / `teknis123`
- **Keuangan**: `keuangan` / `keuangan123`

⚠️ **Penting**: Segera ubah password default setelah login pertama!

---

## 📖 Cara Penggunaan

### 1. Setup Awal (Admin)
1. Login sebagai **Admin**
2. Buka **Kelola User** → Tambah decision makers (Supervisor, Teknis, Keuangan)
3. Buka **Kelola Kriteria** → Tambah kriteria untuk setiap bidang dengan bobot
4. Buka **Kelola Proyek** → Tambah proyek IT yang akan dievaluasi

### 2. Evaluasi (Decision Makers)
1. Login sebagai **Supervisor/Teknis/Keuangan**
2. Buka **Evaluasi Proyek**
3. Pilih proyek yang akan dinilai
4. Berikan skor (1-10) untuk setiap kriteria
5. Klik **Simpan Evaluasi**

### 3. Hitung TOPSIS (Per Bidang)
1. Setelah semua DM menyelesaikan evaluasi
2. Buka **Hasil TOPSIS** → Pilih bidang
3. Klik **Hitung TOPSIS**
4. Lihat ranking dan detail perhitungan

### 4. Hitung BORDA (Konsensus Final)
1. Pastikan TOPSIS sudah dihitung untuk semua bidang
2. Login sebagai **Supervisor**
3. Buka **Hasil BORDA**
4. Klik **Hitung BORDA Consensus**
5. Lihat ranking final dan kontribusi setiap DM

---

## 🔬 Metodologi

### TOPSIS Algorithm

**Step 1: Decision Matrix (X)**
```
        C1   C2   C3   ...  Cn
P1     x11  x12  x13  ...  x1n
P2     x21  x22  x23  ...  x2n
...
Pm     xm1  xm2  xm3  ...  xmn
```

**Step 2: Normalized Matrix (R)**
```
rij = xij / √(Σ xij²)
```

**Step 3: Weighted Normalized Matrix (V)**
```
vij = wj × rij
```

**Step 4: Ideal Solutions**
```
A+ = {v1+, v2+, ..., vn+} = {max(vij)|j∈benefit, min(vij)|j∈cost}
A- = {v1-, v2-, ..., vn-} = {min(vij)|j∈benefit, max(vij)|j∈cost}
```

**Step 5: Distance Calculation**
```
D+i = √(Σ(vij - vj+)²)  // Distance to ideal positive
D-i = √(Σ(vij - vj-)²)  // Distance to ideal negative
```

**Step 6: Preference Value**
```
Ci = D-i / (D+i + D-i)
```
**Ranking**: Nilai Ci tertinggi = Alternatif terbaik

---

### BORDA Count Algorithm

**Step 1: Get TOPSIS Rankings**
```
Supervisor: [P2=1, P1=2, P3=3, P5=4, P4=5]
Teknis:     [P2=1, P3=2, P1=3, P4=4, P5=5]
Keuangan:   [P2=1, P1=2, P5=3, P3=4, P4=5]
```

**Step 2: Convert Rank to Points**
```
Poin = N - Rank + 1
(N = total projects)

Example: P2 untuk Supervisor
Poin = 5 - 1 + 1 = 5
```

**Step 3: Apply DM Weights**
```
Kontribusi = Poin × Bobot DM

Bobot DM:
- Supervisor: 7 (54%)
- Teknis: 4 (31%)
- Keuangan: 2 (15%)
```

**Step 4: Sum Weighted Contributions**
```
Skor BORDA = Σ(Kontribusi dari semua DM)

Example: P2
= (5×7) + (5×4) + (5×2)
= 35 + 20 + 10
= 65
```

**Step 5: Final Ranking**
Skor BORDA tertinggi = Proyek terbaik (konsensus kelompok)

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

### Coding Standards
- Follow PSR-12 for PHP code
- Use meaningful variable/function names
- Add comments for complex logic
- Test before submitting PR

---

## 📝 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

---

## 👥 Authors & Contributors

- **Your Name** - *Initial work & Development* - [@yourusername](https://github.com/yourusername)

---

## 🙏 Acknowledgments

- TOPSIS methodology by Hwang & Yoon (1981)
- BORDA Count by Jean-Charles de Borda (1770)
- Tailwind CSS for beautiful UI framework
- Chart.js for data visualization
- PHP & MySQL community

---

## 📞 Contact & Support

- **Email**: your.email@example.com
- **GitHub Issues**: [Submit Issue](https://github.com/yourusername/gdss-topsis-borda/issues)
- **Documentation**: [Wiki](https://github.com/yourusername/gdss-topsis-borda/wiki)

---

## 🔄 Changelog

### Version 1.0.0 (December 2024)
- ✅ Initial release
- ✅ TOPSIS calculation engine
- ✅ BORDA consensus aggregation
- ✅ Multi-role authentication system
- ✅ Responsive UI with Tailwind CSS
- ✅ Interactive charts & visualizations
- ✅ Complete CRUD management
- ✅ Detailed calculation matrices
- ✅ Comprehensive documentation

---

<div align="center">

**Made with ❤️ for better decision making**

⭐ Star this repo if you find it helpful!

</div>
