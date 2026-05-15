# 📖 Panduan Setup untuk Peserta

Ikuti langkah-langkah di bawah ini untuk menjalankan project **Praktikum Cuti** di komputer Anda.

---

## 📋 Prerequisites (Persyaratan)

Pastikan sudah install:
- ✅ PHP 8.3+ (dengan extensions: mbstring, pdo, mysql, json)
- ✅ Composer (https://getcomposer.org)
- ✅ MySQL 8.0+ (atau MariaDB)
- ✅ Node.js & npm (optional, untuk Vite)
- ✅ Git

---

## 🚀 Step-by-Step Setup

### **Step 1: Clone Repository**
```bash
git clone <URL-REPOSITORY-INI>
cd praktikum-cuti
```

### **Step 2: Install Dependencies**
```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies (optional)
npm install
```

### **Step 3: Copy Environment File**
```bash
# Copy file .env.example ke .env
cp .env.example .env
```

### **Step 4: Generate Application Key**
```bash
php artisan key:generate
```

Anda akan lihat output:
```
Application key set successfully.
```

### **Step 5: Buat Database di MySQL**

Buka MySQL client (MySQL Workbench, phpMyAdmin, atau command line):

```sql
CREATE DATABASE praktikum_cuti;
```

**Atau via terminal:**
```bash
mysql -u root -p -e "CREATE DATABASE praktikum_cuti;"
```

### **Step 6: Run Migrations**
```bash
php artisan migrate
```

Output yang diharapkan:
```
Migration table created successfully.
Migrating: 2014_10_12_000000_create_users_table
...
Migrated:  [timestamp]_create_leave_requests_table
```

### **Step 7: Jalankan Server**
```bash
php artisan serve
```

Buka browser dan akses: **http://localhost:8000**

---

## ✅ Verifikasi Setup Berhasil

### **1. Test API (Postman / curl)**

**Register User:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "pegawai"
  }'
```

**Expected Response:**
```json
{
  "message": "User registered successfully",
  "token": "3|ABC123...",
  "user": {
    "id": 1,
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "role": "pegawai"
  }
}
```

### **2. Jalankan Tests**

```bash
# Jalankan semua tests (harus 30 tests pass)
composer test
```

Expected output:
```
Tests: 30 passed
```

---

## 🧪 Menjalankan Integration Tests (Materi Pembelajaran)

```bash
# Jalankan hanya integration tests (3 workflows)
composer test:integration
```

Lihat hasil dari 3 skenario:
1. ✓ workflow 1 pegawai submit leave request
2. ✓ workflow 2 atasan approve leave request
3. ✓ workflow 3 validation error and retry

---

## 🛠️ Troubleshooting

### **Error: SQLSTATE[HY000] [2002] Connection refused**
→ MySQL belum jalan. Jalankan MySQL server Anda.

### **Error: Class 'PDO' not found**
→ PHP tidak ada extension PDO. Install atau enable di php.ini

### **Error: Access denied for user 'root'@'localhost'**
→ Password database salah. Update di file `.env` atau gunakan `phpMyAdmin`

### **Error: Undefined variable: DB_DATABASE**
→ Belum run `php artisan key:generate`. Coba lagi step 4.

### **Tests Failed**
→ Jalankan: `php artisan migrate` dan `php artisan migrate --env=testing`

---

## 📚 Struktur Project yang Penting

```
app/Http/Controllers/API/
├── AuthController.php ..................... Handle login/register
└── LeaveRequestController.php ............. Handle leave request CRUD

app/Models/
├── User.php .............................. User model dengan roles
└── LeaveRequest.php ...................... Leave request model

routes/api.php ............................ Semua API endpoints

tests/
├── Feature/ ............................. Tests untuk endpoints
└── Integration/
    └── LeaveRequestWorkflowTest.php ....... 3 skenario pembelajaran
```

---

## 🎯 Recommended Learning Path

1. **Pahami API Flow:**
   - Baca `routes/api.php` → lihat endpoints
   - Baca `app/Http/Controllers/API/AuthController.php`
   - Baca `app/Http/Controllers/API/LeaveRequestController.php`

2. **Pelajari Testing:**
   - Baca `tests/Feature/AuthTest.php` → test auth
   - Baca `tests/Integration/LeaveRequestWorkflowTest.php` → integration tests
   - Jalankan: `php artisan test` dan perhatikan output

3. **Eksperimen:**
   - Edit `.env` untuk ubah APP_DEBUG=true
   - Jalankan: `php artisan tinker` untuk interact dengan database
   - Test API endpoints via Postman/curl

---

## 💡 Useful Commands

```bash
# Clear cache
php artisan cache:clear
php artisan config:clear

# Database reset (WARNING: hapus semua data!)
php artisan migrate:reset
php artisan migrate

# Interactive shell
php artisan tinker

# Lihat semua routes
php artisan route:list

# Jalankan test dengan verbose
php artisan test tests/Integration/LeaveRequestWorkflowTest.php --verbose
```

---

## 📞 Pertanyaan / Bantuan

Jika ada error atau pertanyaan:
1. Lihat error message dengan teliti
2. Cek file `.env` → pastikan DB_* config benar
3. Pastikan MySQL running
4. Coba clear cache: `php artisan cache:clear`

---

**Happy Learning! 🎓**
