# GDSS - Group Decision Support System

Website sistem pendukung keputusan kelompok untuk menentukan prioritas proyek IT menggunakan metode **Weighted Product (WP)** dan agregasi **BORDA** berdasarkan penelitian terakreditasi SINTA.

## 📚 Referensi Ilmiah

**Artikel Referensi:**
> Cahyana, N. H., & Aribowo, A. S. (2014). *Group Decision Support System (GDSS) untuk Menentukan Prioritas Proyek*. **Jurnal Teknik Informatika dan Sistem Informasi**, 1(2), 45-58.

**Status Akreditasi:** SINTA (Sistem Informasi Nasional Penelitian dan Pengabdian Masyarakat)

**Metodologi yang Diadopsi:**
- Metode Weighted Product (WP) untuk evaluasi multi-kriteria per bidang
- Metode BORDA untuk agregasi consensus decision making
- Framework Group Decision Support System (GDSS)

## 🎯 Deskripsi Proyek

GDSS adalah implementasi sistem pendukung keputusan kelompok yang memfasilitasi pengambilan keputusan bersama antara tiga decision maker: **Tim Teknis, Tim Administrasi, dan Tim Keuangan** dalam menentukan prioritas proyek IT berdasarkan kriteria yang telah ditetapkan dalam penelitian referensi.

## ⚙️ Spesifikasi Teknis

- **Backend:** PHP 8+ dengan PDO MySQL
- **Frontend:** HTML5, CSS3, Bootstrap 5 (CDN)
- **Database:** MySQL/MariaDB
- **Server:** PHP Built-in Server atau Apache/Nginx
- **Tools:** VSCode, Git, Laragon/XAMPP

## 📋 Fitur Sesuai Requirements UAS

### ✅ **1. Referensi Jurnal Terakreditasi SINTA**
- Berdasarkan artikel Cahyana & Aribowo (2014)
- Mengadopsi metodologi WP + BORDA yang terbukti efektif
- Data proyek dan kriteria sesuai dengan case study artikel

### ✅ **2. Minimal 3 Decision Maker**
- **Tim Teknis:** Evaluasi aspek teknis (5 kriteria)
- **Tim Administrasi:** Evaluasi aspek administrasi (4 kriteria)  
- **Tim Keuangan:** Evaluasi aspek keuangan (3 kriteria)
- **Administrator:** Mengelola sistem dan proyek

### ✅ **3. Website dengan 3 Akun Decision Maker**
- Interface web responsive dengan Bootstrap 5
- Role-based access control untuk setiap decision maker
- Session management dengan CSRF protection

### ✅ **4. Penilaian Individual + Konsensus BORDA**
- Setiap decision maker melakukan evaluasi secara independen
- Metode Weighted Product untuk ranking per bidang
- Agregasi final menggunakan metode BORDA tertimbang
- **Konsensus difinalisasi oleh decision maker dengan jabatan tertinggi (Administrator)**

### ✅ **5. Semua Decision Maker Melihat Hasil Konsensus**
- Dashboard hasil evaluasi untuk semua user
- Visualisasi ranking final dengan BORDA score
- Breakdown hasil per bidang evaluasi

## 🛠️ Instalasi

### Persyaratan Sistem
- PHP 8.0+
- MySQL 5.7+ atau MariaDB 10.3+
- Web browser modern
- Laragon/XAMPP/WAMP

### Langkah Instalasi

1. **Setup Environment**
   ```bash
   # Clone atau extract project
   cd d:\laragon\www\
   git clone <repository-url> gdss-web
   cd gdss-web
   ```

2. **Setup Database**
   ```sql
   -- Buat database di MySQL
   CREATE DATABASE gdss_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   
   -- Import script database original
   mysql -u root -p gdss_db < install_gdss.sql
   
   -- Update ke data artikel referensi
   mysql -u root -p gdss_db < update_to_article_reference.sql
   ```

3. **Konfigurasi Database**
   Edit file `config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'gdss_db');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Password MySQL Anda
   ```

4. **Jalankan Server**
   ```bash
   # Menggunakan PHP Built-in Server
   php -S localhost:8000
   
   # Atau akses via Laragon
   http://gdss-web.test
   ```

5. **Testing Setup**
   - Akses: `http://localhost:8000/test.php`
   - Pastikan semua checklist ✅
   - Login dengan akun demo

## 👥 Akun Demo (Sesuai Artikel)

| Role | Username | Password | Deskripsi |
|------|----------|----------|-----------|
| **Administrator** | `admin` | `admin123` | Kelola proyek, finalisasi konsensus |
| **Tim Teknis** | `teknis` | `teknis123` | Evaluasi 5 kriteria teknis |
| **Tim Administrasi** | `administrasi` | `admin123` | Evaluasi 4 kriteria administrasi |
| **Tim Keuangan** | `keuangan` | `keuangan123` | Evaluasi 3 kriteria keuangan |

## 📊 Data Sesuai Artikel Referensi

### **Proyek yang Dievaluasi (ID 1201-1205):**
1. **ID1201** - SIPPK Kementerian Kesehatan RI
2. **ID1202** - SMS Gateway PPK Kemenkes RI  
3. **ID1203** - Sistem Data Center/Warehouse
4. **ID1204** - Sistem Informasi Akademik STIE
5. **ID1205** - Network Monitoring System

### **Kriteria Evaluasi:**

**🔧 Teknis (5 kriteria, bobot 53.8%):**
- Kemampuan Teknis (Bobot: 5 - Benefit)
- Ketersediaan SDM (Bobot: 5 - Benefit)
- Sumber Daya Pendukung (Bobot: 4 - Benefit)
- Kelayakan Penjadwalan (Bobot: 5 - Benefit)
- Tingkat Kesulitan (Bobot: 5 - Cost)

**📋 Administrasi (4 kriteria, bobot 30.8%):**
- Administrasi (Bobot: 3 - Cost)
- Transportasi (Bobot: 3 - Cost)
- Akomodasi (Bobot: 3 - Cost)
- Perijinan (Bobot: 5 - Cost)

**💰 Keuangan (3 kriteria, bobot 15.4%):**
- Nilai Proyek (Bobot: 5 - Benefit)
- Fee SDM (Bobot: 4 - Cost)
- Biaya Operasional (Bobot: 4 - Cost)

## 🧮 Metodologi Perhitungan

### 1. **Weighted Product (WP) per Bidang**

**Formula:**
```
V_i = ∏ (x_ij ^ w_j)
```

**Normalisasi:**
- **Benefit:** x_ij = nilai / max_nilai
- **Cost:** x_ij = min_nilai / nilai

### 2. **Metode BORDA untuk Agregasi**

**Formula:**
```
S_i = Σ (n - r_ij + 1) × w_j
```

**Bobot Bidang (sesuai artikel):**
- Teknis: 7/13 = 53.8%
- Administrasi: 4/13 = 30.8%  
- Keuangan: 2/13 = 15.4%

### 3. **Finalisasi Konsensus**
- Administrator sebagai decision maker dengan jabatan tertinggi
- Memiliki wewenang untuk memfinalisasi hasil konsensus
- Semua decision maker dapat melihat hasil final

## 📁 Struktur Project

```
gdss-web/
├── index.php                    # Halaman login dengan demo accounts
├── dashboard.php                # Dashboard utama dengan statistik
├── projects.php                 # Manajemen proyek (CRUD) - Admin only
├── evaluate.php                 # Form evaluasi individual per bidang
├── results.php                  # Hasil WP + BORDA + Finalisasi
├── logout.php                   # Logout handler
├── config.php                   # Konfigurasi database & session
├── functions.php                # Fungsi utama WP + BORDA
├── install_gdss.sql             # Script setup database original
├── update_to_article_reference.sql # Update ke data artikel
├── test.php                     # System diagnostic tool
├── assets/
│   ├── css/style.css           # Modern CSS dengan animasi
│   └── images/                 # Assets (kosong)
├── README.md                   # Dokumentasi lengkap
└── TROUBLESHOOTING.md          # Panduan troubleshooting
```

## 🎨 Design System

### Color Palette (Professional Academic)
- **Primary Teal:** `#06b6d4` (Kepercayaan, Teknologi)
- **Primary Amber:** `#f59e0b` (Optimisme, Inovasi)  
- **Dark Navy:** `#0f172a` (Profesional, Stabil)
- **Success Green:** `#10b981` (Berhasil, Positif)

### UI/UX Features
- Responsive design dengan Bootstrap 5
- Modern glassmorphism effects
- Smooth animations dan transitions
- Dark mode ready CSS variables

## 🚀 Workflow Penggunaan

### **Untuk Administrator:**
1. Login → Dashboard overview
2. Kelola proyek via menu "Proyek" 
3. Monitor progress evaluasi
4. **Finalisasi konsensus** hasil BORDA
5. Export dan print laporan

### **Untuk Decision Maker (Teknis/Admin/Keuangan):**
1. Login sesuai role → Dashboard personal
2. Menu "Evaluasi" → Pilih proyek
3. Berikan skor 1-10 untuk setiap kriteria
4. Simpan evaluasi → Lanjut proyek berikutnya
5. Lihat hasil per bidang dan final ranking

## 📈 Validasi Penelitian

**Konsistensi dengan Artikel:**
- ✅ Menggunakan metode WP + BORDA sesuai referensi
- ✅ Data proyek dan kriteria identik dengan case study
- ✅ Bobot kriteria dan bidang sesuai perhitungan artikel
- ✅ Framework GDSS dengan 3+ decision maker
- ✅ Individual assessment + group consensus

**Value Added:**
- Interface web modern dan user-friendly
- Real-time calculation dan visualization
- Session management dan security features
- Responsive design untuk berbagai device

## 🔧 Customization

### Menambah Decision Maker Baru:
```sql
-- Tambah role baru
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teknis', 'administrasi', 'keuangan', 'role_baru');

-- Insert user baru
INSERT INTO users (username, password, fullname, role) 
VALUES ('username_baru', 'password123', 'Nama Lengkap', 'role_baru');
```

### Menambah Kriteria Evaluasi:
```sql
INSERT INTO criteria (part, name, weight, type, description) 
VALUES ('teknis', 'Kriteria Baru', 0.100, 'benefit', 'Deskripsi kriteria');

-- Update bobot kriteria lain agar total = 1.0
```

## 🐛 Troubleshooting

**Masalah Umum:**
- Database connection error → Cek `config.php`
- Login gagal → Gunakan akun demo yang benar
- Error perhitungan → Pastikan semua kriteria sudah dievaluasi

**Development Mode:**
- Akses `test.php` untuk diagnostic lengkap
- Check error logs di browser console
- Aktifkan debug mode di `config.php`

## 📄 Lisensi & Credit

**Academic Use License**  
Project ini dibuat untuk keperluan UAS dan penelitian akademik.

**Based on Research:**  
Cahyana, N. H., & Aribowo, A. S. (2014). Group Decision Support System (GDSS) untuk Menentukan Prioritas Proyek.

**Implementation:**  
PHP & MySQL Implementation of GDSS with WP + BORDA Methods

---

**© 2024 GDSS Implementation**  
*Faithful implementation of SINTA-accredited research for academic purposes*