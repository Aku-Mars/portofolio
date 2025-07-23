# Portofolio Web dengan Penghitung Pengunjung Berbasis Database

Ini adalah proyek web portofolio pribadi yang dilengkapi dengan fitur penghitung jumlah pengunjung dinamis. Jumlah pengunjung disimpan dan diambil dari database MySQL, dan setiap kunjungan baru akan mengirimkan notifikasi ke server Discord melalui webhook dengan detail kunjungan yang lebih lengkap.

## Fitur Utama

-   **Portofolio Dinamis:** Menampilkan profil, pengalaman, proyek, dan sertifikat.
-   **Penghitung Pengunjung:** Melacak jumlah pengunjung unik menggunakan database MySQL.
-   **Notifikasi Discord Lanjutan:** Memberikan notifikasi real-time untuk setiap pengunjung baru, termasuk **Hari, Jam Akses, Jenis Perangkat, Sistem Operasi, Browser, Lokasi (Kota, Wilayah, Negara), ISP, dan jumlah kunjungan IP pada hari itu**.
-   **Desain Responsif:** Dibuat dengan Tailwind CSS untuk tampilan optimal di semua perangkat.
-   **Animasi Scroll:** Menggunakan AOS (Animate On Scroll) untuk efek visual yang menarik.

## Prasyarat

Sebelum memulai, pastikan Anda memiliki lingkungan server yang mendukung:

-   **Web Server:** Apache, Nginx, atau sejenisnya.
-   **PHP:** Versi 7.4 atau lebih baru, dengan ekstensi `mysqli` aktif.
-   **Database:** MySQL atau MariaDB.

## Instruksi Instalasi

Ikuti langkah-langkah berikut untuk menjalankan proyek ini di server Anda.

### 1. Penyiapan Database

Jalankan skrip SQL berikut di antarmuka manajemen MySQL Anda (misalnya phpMyAdmin, DBeaver, atau command line) untuk secara otomatis membuat database, tabel, dan data awal yang diperlukan.

```sql
-- Membuat database 'porto_db' jika belum ada.
CREATE DATABASE IF NOT EXISTS porto_db;

-- Menggunakan database 'porto_db' untuk perintah selanjutnya.
USE porto_db;

-- Membuat tabel 'views' untuk menyimpan jumlah pengunjung total.
CREATE TABLE IF NOT EXISTS views (
  id INT PRIMARY KEY,
  count INT NOT NULL
);

-- Memasukkan data awal (id=1, count=0) jika belum ada untuk tabel 'views'.
INSERT INTO views (id, count) VALUES (1, 0)
ON DUPLICATE KEY UPDATE count = count;

-- Membuat tabel 'visit_logs' untuk mencatat setiap kunjungan dengan IP dan waktu.
CREATE TABLE IF NOT EXISTS visit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    visit_time DATETIME NOT NULL
);
```

### 2. Konfigurasi Koneksi

Selanjutnya, konfigurasikan skrip backend untuk terhubung ke database Anda.

1.  Buka file `api/counter.php`.
2.  Ubah nilai variabel berikut sesuai dengan kredensial database Anda:

    ```php
    // Konfigurasi database
    $host = 'ganti_dengan_host_anda';         // Contoh: 'localhost'
    $user = 'ganti_dengan_user_anda';         // Contoh: 'root'
    $password = 'ganti_dengan_password_anda'; // Contoh: ''
    $dbname = 'porto_db';                     // Nama database yang Anda buat
    ```

### 3. Konfigurasi Webhook Discord (Opsional)

Jika Anda ingin menerima notifikasi pengunjung, perbarui URL webhook di file `api/counter.php`.

**Penting:** Pastikan URL webhook Discord Anda lengkap dan tidak terpotong. URL yang salah dapat menyebabkan notifikasi gagal terkirim.

1.  Buka file `api/counter.php`.
2.  Ganti nilai variabel `webhook_url` dengan URL webhook Discord Anda:

    ```php
    $webhook_url = 'https://discord.com/api/webhooks/your/webhook_url';
    ```

### 4. Unggah File

Unggah semua file dan direktori (`index.html`, `CV.pdf`, direktori `api`, `css`, `img`, dan `js`) ke direktori root server web Anda (misalnya `public_html`, `www`, atau `htdocs`).

## Struktur File

```
/
├── api/
│   └── counter.php       # Backend: Logika database & penghitung, notifikasi webhook
├── css/
│   └── app.css           # Styling kustom
├── img/
│   └── ...               # Aset gambar
├── js/
│   └── script.js         # Frontend: Fetch data & notifikasi Discord
├── CV.pdf
├── index.html            # Halaman utama portofolio
└── README.md             # File ini
```

Setelah semua langkah di atas selesai, buka situs web Anda di browser. Penghitung pengunjung seharusnya sudah berfungsi dan menampilkan data dari database, serta mengirimkan notifikasi Discord yang lebih detail.