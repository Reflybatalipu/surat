<?php
// Mencegah akses dari browser
// if (php_sapi_name() !== 'cli') {
//     die("Akses ditolak. Harus dari CLI.");
// }

require __DIR__ . '/config/koneksi.php'; 
require __DIR__ . '/fungsi_telegram.php';

// Mengmbil maksimal 15 pesan agar tidak timeout
$query = mysqli_query($koneksi, "SELECT * FROM antrean_telegram WHERE status_kirim = 'pending' LIMIT 15");

if (mysqli_num_rows($query) == 0) {
    exit;
}

while ($row = mysqli_fetch_assoc($query)) {
    $id_antrean = $row['id'];
    $chat_id = $row['telegram_id'];
    $pesan = $row['pesan'];
    $file_path = $row['file_path'];

    
    $berhasil = false;
    if (!empty($file_path) && file_exists(__DIR__ . '/modul_surat_masuk/' . $file_path)) {
        // Asumsi path file relatif terhadap pemanggilan ini
        $path_lengkap = __DIR__ . '/modul_surat_masuk/' . $file_path; 
        $hasil = kirim_dokumen_telegram($chat_id, $path_lengkap, $pesan);
        $berhasil = ($hasil !== false); // Cek fungsi return sukses
    } else {
        $hasil = kirim_telegram($chat_id, $pesan);
        $berhasil = ($hasil !== false);
    }

    if ($berhasil) {
        mysqli_query($koneksi, "UPDATE antrean_telegram SET status_kirim = 'sent' WHERE id = '$id_antrean'");
    } else {
        mysqli_query($koneksi, "UPDATE antrean_telegram SET status_kirim = 'failed' WHERE id = '$id_antrean'");
    }

    
    sleep(1);


// Hapus data antrean yang sudah berstatus 'sent' atau 'failed' 
// dan umurnya sudah lebih dari 7 hari.
mysqli_query($koneksi, "DELETE FROM antrean_telegram WHERE status_kirim IN ('sent', 'failed') AND created_at < NOW() - INTERVAL 7 DAY");
}