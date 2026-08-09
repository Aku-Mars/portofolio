# Mars Personal Portfolio

Website portofolio profesional yang dirancang untuk menampilkan proyek unggulan, keahlian teknis, dan layanan secara interaktif dan estetis.

## 🚀 Fitur Utama
- **Etalase Proyek**: Menampilkan berbagai studi kasus proyek (Kecerdasan Buatan, Pengembangan Web, dan Automasi).
- **Desain Responsif**: Tampilan yang dioptimalkan untuk berbagai ukuran layar (Desktop, Tablet, dan Mobile).
- **Animasi Interaktif**: Pengalaman pengguna (UX) yang dinamis dengan performa tinggi.

## 🛠️ Teknologi yang Digunakan
- **Struktur & Gaya**: HTML5, CSS3, JavaScript
- **Arsitektur**: Static Site (Dapat disajikan langsung melalui Web Server standar tanpa runtime backend khusus).

## 🌍 Panduan Deployment
1. **Lokal (Development)**: 
   Buka file `index.html` menggunakan browser, atau jalankan melalui ekstensi *Live Server* di editor kode Anda.
2. **Produksi (VPS/Hosting)**:
   Clone repository ke `/var/www/html/portofolio`. Root repository `Aku-Mars/page` menulis ulang request `/` secara internal ke `/portofolio/index.html`, sehingga portofolio tersedia di `https://marsy.my.id/` dan `https://marsy.my.id/portofolio/` tanpa duplikasi file.

### Redeploy

Push perubahan repository ini terlebih dahulu, lalu jalankan dari root workspace `page`:

```powershell
.\deploy.ps1 -Portofolio
```

Jika perubahan juga menyentuh root `.htaccess`, deploy kedua repository:

```powershell
.\deploy.ps1 -Static -Portofolio
```

Alternatif manual di VPS:

```bash
git -C /var/www/html pull --ff-only origin main
git -C /var/www/html/portofolio pull --ff-only origin main
sudo apache2ctl configtest
sudo systemctl reload apache2
```

Seluruh aset lokal, CV, manifest, service worker, dan endpoint notifikasi menggunakan prefix absolut `/portofolio/`. Jangan mengubahnya menjadi path relatif karena halaman root juga menggunakan file `index.html` yang sama.

## Notifikasi Pengunjung Discord

Notifikasi dikirim oleh `api/visitor-notification.php`, sehingga URL webhook tidak terekspos di source browser. Atur webhook sebagai environment variable pada konfigurasi Apache/PHP, lalu reload web server:

```apache
SetEnv DISCORD_WEBHOOK_URL "https://discord.com/api/webhooks/..."
```

Notifikasi menyertakan sumber kunjungan dari referrer dan parameter UTM. Untuk memperoleh atribusi yang konsisten saat membagikan tautan, gunakan contoh berikut:

```text
https://marsy.my.id/portofolio/?utm_source=linkedin&utm_medium=social&utm_campaign=profile
```

Endpoint membatasi pengiriman menjadi satu notifikasi per alamat IP setiap 15 menit. Pastikan ekstensi PHP cURL tersedia di server.

Setelah redeploy, verifikasi:

```bash
curl --fail --silent --show-error --connect-timeout 3 --max-time 5 https://marsy.my.id/ > /dev/null
curl --fail --silent --show-error --connect-timeout 3 --max-time 5 https://marsy.my.id/portofolio/ > /dev/null
curl --silent --output /dev/null --write-out '%{http_code}\n' --connect-timeout 3 --max-time 5 https://marsy.my.id/portofolio/api/visitor-notification.php
```

Endpoint notifikasi harus mengembalikan `405` untuk request GET; ini menandakan endpoint tersedia dan hanya menerima POST.
