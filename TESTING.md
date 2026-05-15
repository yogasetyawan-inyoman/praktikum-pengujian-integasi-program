# Panduan Pengujian Integrasi Program

## Konsep Pengujian Integrasi
- **Unit Test**: Test satu method/fungsi dalam isolasi
- **Feature/Integration Test**: Test alur lengkap (workflow) dengan HTTP requests
- **Test Coverage**: Mengukur seberapa banyak kode yang ter-cover oleh test

## Struktur Test di Project Ini
- `tests/Feature/AuthTest.php` — Test alur autentikasi (register, login, logout)
- `tests/Feature/LeaveRequestTest.php` — Test alur pengajuan cuti end-to-end
- `database/factories/` — Factory untuk generate data test

## Skenario Utama yang Ditest

### 1. Authentication Flow
- [x] User dapat register dengan role valid
- [x] Validasi email duplikat
- [x] Validasi role invalid
- [x] User dapat login dengan kredensial valid
- [x] Login gagal dengan password salah
- [x] User dapat logout

### 2. Leave Request Workflow
- [x] Pegawai submit pengajuan
- [x] Validasi tanggal (end_date >= start_date)
- [x] Hanya pegawai/admin bisa submit
- [x] Pegawai lihat hanya pengajuan miliknya
- [x] Atasan lihat hanya pengajuan pending
- [x] Admin lihat semua pengajuan
- [x] Atasan approve pengajuan
- [x] Atasan reject pengajuan
- [x] Pegawai tidak bisa approve/reject
- [x] Pegawai hapus pengajuan pending miliknya
- [x] Pegawai tidak bisa hapus pengajuan orang lain
- [x] Perhitungan hari cuti otomatis

## Cara Menjalankan Test

### Menjalankan semua test
\`\`\`bash
php artisan test
\`\`\`

### Test file tertentu
\`\`\`bash
php artisan test tests/Feature/AuthTest.php
php artisan test tests/Feature/LeaveRequestTest.php
\`\`\`

### Dengan output verbose
\`\`\`bash
php artisan test --verbose
\`\`\`

### Coverage report
\`\`\`bash
php artisan test --coverage
\`\`\`

## Tugas Peserta

### Level 1 (Dasar)
- [ ] Jalankan semua test dan pastikan passing
- [ ] Analisis test case mana saja yang gagal dan perbaiki code

### Level 2 (Menengah)
- [ ] Tambahkan 3 test case baru (contoh: validasi type cuti, upload attachment, dll)
- [ ] Pastikan test coverage minimal 80%

### Level 3 (Lanjut)
- [ ] Tambahkan feature baru: limit sisa cuti per tahun
- [ ] Buat test untuk memvalidasi logic tersebut
- [ ] Implementasikan API endpoint untuk get sisa cuti