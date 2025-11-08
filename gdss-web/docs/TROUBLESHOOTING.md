# GDSS Troubleshooting Guide

## 🚨 Masalah Login? Ikuti panduan ini:

### 1. **Cek Database Setup**
Pastikan Anda sudah menjalankan script database:

```sql
-- 1. Buat database
CREATE DATABASE gdss_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Gunakan database
USE gdss_db;

-- 3. Import script SQL
-- Buka file install_gdss.sql dan jalankan semua query di dalamnya
-- Atau gunakan command: mysql -u root -p gdss_db < install_gdss.sql
```

### 2. **Periksa Konfigurasi Database**
Edit file `config.php` sesuai setup MySQL Anda:

```php
define('DB_HOST', 'localhost');    // Host MySQL
define('DB_NAME', 'gdss_db');      // Nama database
define('DB_USER', 'root');         // Username MySQL
define('DB_PASS', '');             // Password MySQL (kosong untuk XAMPP/Laragon default)
```

### 3. **Test Koneksi System**
1. Akses: `http://localhost:8000/test.php`
2. Periksa semua checklist harus ✅
3. Jika ada ❌, ikuti instruksi perbaikan

### 4. **Akun Demo untuk Testing**
Pastikan menggunakan akun yang benar:

| Username | Password | Role |
|----------|----------|------|
| admin | admin123 | Administrator |
| teknis01 | teknis123 | Teknis |
| admin01 | admin123 | Administrasi |
| keuangan01 | keuangan123 | Keuangan |

### 5. **Debug Mode**
Jika masih tidak bisa login:
1. Akses: `http://localhost:8000/?debug=1`
2. Coba login dengan akun demo
3. Lihat pesan error detail yang muncul

### 6. **Cek Service MySQL**
Pastikan MySQL/MariaDB berjalan:

**Windows (XAMPP/Laragon):**
- Buka control panel XAMPP/Laragon
- Start service MySQL

**Windows (MySQL Service):**
```cmd
net start mysql
```

**Linux/Mac:**
```bash
sudo service mysql start
# atau
brew services start mysql
```

### 7. **Cek File Permissions**
Pastikan semua file PHP dapat dibaca:
- Folder `gdss-web/` harus memiliki permission read
- File `config.php`, `functions.php`, dll harus readable

### 8. **Langkah Manual Setup Database**

Jika masih bermasalah, setup manual:

```sql
-- 1. Buat database
CREATE DATABASE gdss_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gdss_db;

-- 2. Buat tabel users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    role ENUM('admin', 'teknis', 'administrasi', 'keuangan') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Insert user admin
INSERT INTO users (username, password, fullname, role) VALUES
('admin', 'admin123', 'Administrator System', 'admin');

-- 4. Test login dengan admin/admin123
```

### 9. **Error Messages & Solutions**

**"Koneksi database gagal"**
- Cek MySQL service running
- Periksa credentials di config.php
- Pastikan database gdss_db ada

**"Username atau password salah"**
- Pastikan menggunakan akun demo yang benar
- Cek caps lock
- Gunakan debug mode untuk detail

**"Table doesn't exist"**
- Jalankan script install_gdss.sql lengkap
- Pastikan semua tabel terbuat

**"Session error"**
- Restart browser
- Clear cookies/cache
- Cek permission folder temp

### 10. **Kontak Support**
Jika masih bermasalah:
1. Screenshot error message
2. Copy hasil dari test.php
3. Kirim detail konfigurasi database Anda

---

**Quick Test:** Buka `test.php` untuk diagnostic lengkap!