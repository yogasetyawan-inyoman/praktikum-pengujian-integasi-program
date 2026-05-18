# 🏢 Praktikum Cuti - Leave Request API

**RESTful API untuk sistem pengajuan cuti karyawan** dengan role-based authorization dan comprehensive integration testing sebagai materi pembelajaran.

## 📋 Fitur

- ✅ User registration & login dengan API Token (Sanctum)
- ✅ CRUD leave request dengan role-based filtering
- ✅ Workflow: Submit → Pending → Approve/Reject
- ✅ Role-based authorization (pegawai, atasan, admin)
- ✅ **30 comprehensive tests** (12 feature + 6 unauthorized + 3 integration workflows)
- ✅ MySQL database dengan migrations
- ✅ Detailed integration test examples untuk pembelajaran

## 🚀 Setup Lengkap untuk Peserta (Termasuk Lingkungan Testing)

Ikuti langkah-langkah ini untuk menyiapkan proyek secara lengkap, termasuk konfigurasi database untuk pengembangan dan pengujian otomatis.

### 1. Clone Repository
Gunakan git untuk menyalin proyek ke komputer lokal Anda.

```bash
git clone <repository-url>
cd praktikum-cuti
```

### 2. Install & Update Dependencies
Install semua library PHP (via Composer) dan JavaScript (via NPM).

```bash
# Install dependencies
composer install
npm install

# Opsional: Update libraries ke versi terbaru jika diperlukan
composer update
```

### 3. Konfigurasi Environment Utama
Salin file `.env.example` menjadi `.env` yang akan digunakan untuk konfigurasi lokal Anda, lalu generate `APP_KEY`.

```bash
# Salin file environment
cp .env.example .env

# Generate APP_KEY
php artisan key:generate
```

### 4. Buat Database MySQL
Proyek ini memerlukan dua database terpisah:
1.  `praktikum_cuti` untuk data pengembangan (real).
2.  `praktikum_cuti_test` untuk data pengujian otomatis (testing).

```sql
-- Jalankan perintah ini di MySQL client Anda (misal: HeidiSQL, phpMyAdmin)
CREATE DATABASE praktikum_cuti;
CREATE DATABASE praktikum_cuti_test;
```

### 5. Konfigurasi Koneksi Database
Buka file `.env` yang baru Anda buat dan sesuaikan konfigurasi database utama.

```ini
# Contoh konfigurasi di file .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=praktikum_cuti
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Konfigurasi Database Testing
Saat menjalankan PHPUnit, Laravel secara otomatis akan memuat file `.env.testing`. Buat file ini dengan menyalin `.env`, lalu ubah nama databasenya.

```bash
# Salin file .env menjadi .env.testing
cp .env .env.testing
```

Sekarang, buka file **`.env.testing`** dan ubah nilai `DB_DATABASE` agar mengarah ke database pengujian.

```ini
# Ubah baris ini di dalam file .env.testing
DB_DATABASE=praktikum_cuti_test
```
Dengan cara ini, semua tes otomatis tidak akan mengganggu data di database `praktikum_cuti` Anda.

### 7. Jalankan Migrasi & Seeder
Jalankan migrasi untuk membuat struktur tabel dan isi data awal (seed) hanya untuk database pengembangan (`praktikum_cuti`).

```bash
# Menjalankan migrasi untuk database utama (praktikum_cuti)
php artisan migrate

# Mengisi data awal (user, dll) ke database utama
php artisan db:seed
```
*Catatan: Migrasi untuk database testing (`praktikum_cuti_test`) akan dijalankan secara otomatis oleh PHPUnit setiap kali Anda menjalankan tes.*

### 8. Jalankan Server & Tests
Setelah semua langkah selesai, Anda siap menjalankan server pengembangan dan seluruh rangkaian pengujian.

```bash
# Jalankan server lokal (biasanya di http://localhost:8000)
php artisan serve

# Jalankan semua test untuk memastikan setup berhasil
# Perintah ini akan menggunakan .env.testing dan database praktikum_cuti_test
composer test
```

---

## 🧪 Menjalankan Tests

```bash
# ✅ Jalankan semua tests (30 tests)
composer test

# ✅ Jalankan integration tests saja (3 workflows)
composer test:integration

# ✅ Jalankan feature tests saja (16 tests)
composer test:feature

# ✅ Jalankan unit tests saja (1 test)
composer test:unit

# Atau langsung artisan
php artisan test tests/Integration/LeaveRequestWorkflowTest.php
```

---

## 📚 API Endpoints

### 🔐 Authentication
- `POST /api/register` - Register user baru
- `POST /api/login` - Login & dapatkan token
- `POST /api/logout` - Logout (auth:sanctum)

### 📋 Leave Requests
- `GET /api/leave-requests` - Daftar pengajuan (auth:sanctum)
- `POST /api/leave-requests` - Buat pengajuan (auth:sanctum, pegawai only)
- `GET /api/leave-requests/{id}` - Detail pengajuan
- `PUT /api/leave-requests/{id}` - Update pengajuan (pegawai only)
- `DELETE /api/leave-requests/{id}` - Hapus pengajuan (pegawai only)
- `POST /api/leave-requests/{id}/approve` - Approve (auth:sanctum, atasan/admin only)
- `POST /api/leave-requests/{id}/reject` - Reject (auth:sanctum, atasan/admin only)

---

## 👥 Roles & Permissions

| Role | Permissions |
|------|------------|
| **Pegawai** | Submit pengajuan, lihat pengajuan sendiri, update/delete pending requests |
| **Atasan** | Lihat pending requests saja, approve/reject pengajuan |
| **Admin** | Lihat SEMUA requests, approve/reject semua |

---

## 📁 Project Structure

```
app/
├── Http/
│   └── Controllers/API/
│       ├── AuthController.php
│       └── LeaveRequestController.php
└── Models/
    ├── User.php
    └── LeaveRequest.php

routes/
└── api.php

database/
├── migrations/
│   └── *_create_leave_requests_table.php
└── factories/
    ├── UserFactory.php
    └── LeaveRequestFactory.php

tests/
├── Feature/
│   ├── AuthTest.php (6 tests)
│   ├── LeaveRequestTest.php (16 tests)
│   └── LeaveRequestUnauthorizedTest.php (6 tests)
└── Integration/
    └── LeaveRequestWorkflowTest.php (3 workflows)
```

---

## 🎓 Integration Testing - Materi Pembelajaran

### 📖 3 Complete Workflows dengan Detailed Comments

File: `tests/Integration/LeaveRequestWorkflowTest.php`

#### **Workflow #1: End-to-End - Pegawai Submit Cuti**
```
GIVEN: Pegawai berhasil login
WHEN: Submit pengajuan cuti 5 hari (15-19 Juni)
THEN: 
  - Response status 201 (Created)
  - Status dalam DB = 'pending'
  - Pegawai bisa lihat pengajuannya di daftar
```

#### **Workflow #2: Role-Based Authorization - Atasan Approve**
```
GIVEN: Pegawai sudah submit pengajuan pending
AND: Atasan berhasil login
WHEN: Atasan lihat daftar pengajuan
THEN:
  - Atasan hanya lihat status 'pending' (role-based filtering)
  - Atasan approve pengajuan
  - Status berubah jadi 'approved'
  - approved_by terisi dengan ID atasan (audit trail)
```

#### **Workflow #3: Validation & Error Handling**
```
GIVEN: Pegawai siap submit pengajuan
WHEN: Submit dengan tanggal invalid (end_date < start_date)
THEN:
  - Response status 422 (Unprocessable Entity)
  - Data TIDAK masuk database
  - Pegawai bisa retry dengan data valid
  - Response status 201 (berhasil)
  - Data tersimpan dengan benar
```

Setiap test dilengkapi:
- ✅ GIVEN-WHEN-THEN comments
- ✅ Testing layers explanation (Auth, Validation, DB, Query)
- ✅ Educational descriptions
- ✅ Real-world business scenarios

---

## 🔧 Tech Stack

- **PHP** 8.3
- **Laravel** 13.8
- **MySQL** 8.0
- **Laravel Sanctum** - API token authentication
- **PHPUnit** 12.5 - Testing framework
- **Carbon** 3.x - Date/time handling
- **Faker** - Test data generation

---

## 📖 Dokumentasi Lengkap

### Login & Dapatkan Token
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "budi@example.com",
    "password": "password"
  }'
```

**Response:**
```json
{
  "token": "3|xxxx...",
  "user": {
    "id": 1,
    "name": "Budi",
    "email": "budi@example.com",
    "role": "pegawai"
  }
}
```

### Submit Pengajuan Cuti (dengan token)
```bash
curl -X POST http://localhost:8000/api/leave-requests \
  -H "Authorization: Bearer 3|xxxx..." \
  -H "Content-Type: application/json" \
  -d '{
    "start_date": "2026-06-15",
    "end_date": "2026-06-19",
    "type": "tahunan",
    "reason": "Liburan keluarga"
  }'
```

---

## 🛠️ Setup untuk Pengembang

```bash
# Development server + watcher
composer dev

# Format code dengan Pint
./vendor/bin/pint

# Tinker - Interactive shell
php artisan tinker
```

---

## ✨ Testing Highlights

- **30 Total Tests** dengan 100% pass rate
- **BDD Pattern** (Behavior-Driven Development) untuk clarity
- **Factory Pattern** untuk test data generation
- **Role-based testing** untuk authorization scenarios
- **Integration tests** menunjukkan real-world workflows

---

## 📝 License

MIT
