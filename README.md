# PDF Merger Web App

Aplikasi web sederhana berbasis PHP untuk menggabungkan beberapa file PDF menjadi satu dokumen.

## Prasyarat

- PHP 8.1 atau lebih baru dengan ekstensi `fileinfo`
- Composer untuk mengelola dependensi

## Instalasi

```bash
composer install
```

Perintah tersebut akan mengunduh dependensi `setasign/fpdi` yang digunakan untuk menggabungkan PDF.

## Menjalankan Aplikasi

Jalankan server pengembangan PHP dengan perintah berikut:

```bash
php -S 0.0.0.0:8000 -t public
```

Setelah server berjalan, buka `http://localhost:8000` di peramban dan unggah minimal dua file PDF untuk digabungkan.

## Struktur Proyek

- `public/index.php` – Antarmuka web dan logika pengunggahan
- `public/styles.css` – Gaya antarmuka
- `src/PdfMerger.php` – Kelas pembantu untuk menggabungkan PDF menggunakan FPDI
- `composer.json` – Definisi dependensi dan autoloading

## Lisensi

Dirilis di bawah lisensi MIT.
