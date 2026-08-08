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
   Unggah atau tarik (pull) seluruh aset statis repositori ini ke dalam direktori *document root* (misal: `/var/www/html/portofolio`) dan pastikan Web Server (Nginx/Apache) diarahkan dengan benar ke direktori tersebut.

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
