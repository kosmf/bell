# 🔔 Smart Factory Bell Automation System (ESP32 & webERP)

Sistem otomatisasi dan kontrol bel pabrik berbasis **ESP32** yang terintegrasi langsung dengan platform **webERP / PHP (MySQL)**. Sistem ini dirancang untuk menangani jadwal otomatis, kontrol manual secara *real-time*, mode offline (cadangan jadwal lokal), penanganan hari libur rutin/nasional, serta berbagai pola suara (kontinu dan putus-putus).

---

## 🚀 Fitur Utama
* **Jadwal Otomatis & Manual:** Atur jadwal jam istirahat/masuk kerja secara fleksibel lewat dashboard webERP atau picu bel secara instan menggunakan kontrol manual (*Bunyikan Sekarang*).
* **Mode Ganda Suara:** Mendukung pola suara **Kontinu** (menyala terus) dan **Putus-putus** (bip... bip...) dengan durasi dan jeda yang dapat disesuaikan.
* **Offline Mode (Cache Lokal):** ESP32 secara otomatis mengunduh daftar jadwal harian ke memori lokal. Jika koneksi internet atau server terputus, ESP32 tetap mandiri membunyikan bel tepat waktu menggunakan sinkronisasi waktu NTP.
* **Manajemen Hari Libur Dinamis:** Pengaturan libur rutin mingguan (misalnya Minggu atau Sabtu-Minggu) melalui centang konfigurasi di dashboard, serta integrasi tabel libur nasional.
* **Log Aktivitas & Queue Monitor:** Memantau status antrean perintah manual dan riwayat log pemicuan bel secara *real-time*.

---

## 🛠️ Struktur Berkas Repository
* `BellControl.php` : Dashboard antarmuka webERP untuk manajemen jadwal, kontrol manual, dan pengaturan libur.
* `check_bell.php` : API server untuk merespons status pengecekan dari ESP32 (mengecek antrean, jadwal, dan hari libur).
* `log_bell.php` : API server untuk mencatat log aktivitas perangkat/pemberitahuan status.
* `database.sql` : Skema struktur basis data MySQL yang dibutuhkan.

---

## 📋 Persiapan & Instalasi

### 1. Konfigurasi Database (MySQL)
Jalankan skema SQL berikut pada database server Anda untuk membuat tabel yang diperlukan:
```sql
-- Tabel Jadwal Bel
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jam TIME NOT NULL,
    keterangan VARCHAR(100),
    duration INT DEFAULT 5,
    bell_type VARCHAR(20) DEFAULT 'kontinu'
);

-- Tabel Antrean Manual
CREATE TABLE IF NOT EXISTS bell_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mac_address VARCHAR(50) NOT NULL,
    duration INT DEFAULT 5,
    bell_type VARCHAR(20) DEFAULT 'kontinu'
);

-- Tabel Log Aktivitas
CREATE TABLE IF NOT EXISTS bell_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mac_address VARCHAR(50) NOT NULL,
    status VARCHAR(50),
    triggered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Hari Libur Nasional
CREATE TABLE IF NOT EXISTS holiday (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    nama VARCHAR(100)
);

-- Tabel Pengaturan Sistem
CREATE TABLE IF NOT EXISTS bell_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255)
);

2. Konfigurasi Perangkat Keras (ESP32)

    Pin Relay: Hubungkan modul relay pada pin GPIO 16 (pastikan menghubungkan beban sirine/bel melalui terminal COM dan NO).

    Pin Buzzer: Hubungkan buzzer pasif pada pin GPIO 13.

    Library Arduino IDE yang Dibutuhkan:

        WiFi.h & HTTPClient.h (bawaan ESP32)

        ArduinoJson (oleh Benoit Blanchon)

        NTPClient (oleh Fabrice Weinberg)
