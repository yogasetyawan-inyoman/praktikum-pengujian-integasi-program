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

## 🚀 Quick Start (untuk peserta)

### 1. Clone Repository
```bash
git clone <repository-url>
cd praktikum-cuti
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Setup Environment
```bash
# Copy environment file
cp .env.example .env

# Generate APP_KEY
php artisan key:generate
```

### 4. Database Setup
```bash
# Buat database MySQL terlebih dahulu:
# mysql> CREATE DATABASE praktikum_cuti;

# Jalankan migrations
php artisan migrate
```

### 5. Jalankan Server
```bash
php artisan serve
```
Server berjalan di: **http://localhost:8000**

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
