<?php
// download_template.php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="Template_Tamu.csv"');

$output = fopen('php://output', 'w');

// Header Kolom (Sesuai permintaan)
fputcsv($output, array('Nama', 'Tanggal', 'Jam', 'Whatsapp', 'Kategori Tamu', 'Jumlah Tamu'));

// Contoh Data (Opsional, biar user paham formatnya)
// Menggunakan ="..." untuk memaksa Excel membaca sebagai teks agar angka 0 di depan WA tidak hilang
fputcsv($output, array('Contoh Nama', '="25-12-2025"', '09:00', '="08123456789"', 'Keluarga', '2'));

fclose($output);
exit;
?>