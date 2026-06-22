<?php
if (isset($_GET['file'])) {
    // Gunakan urldecode untuk mengubah %20 menjadi spasi normal kembali
    $filename = basename(urldecode($_GET['file']));
    
    // Daftar folder yang akan diperiksa (Tetap sesuai kode asli Anda)
    $folders = [
        'uploads/surat_masuk/',
        'uploads/surat_keluar/',
        'uploads/arsip/'
    ];

    $path = "";
    foreach ($folders as $folder) {
        if (file_exists($folder . $filename)) {
            $path = $folder . $filename;
            break;
        }
    }

    if ($path != "" && filesize($path) > 0) {
        // Pembersihan buffer agar tidak ada spasi/karakter liar dari PHP yang ikut terbaca
        if (ob_get_length()) ob_end_clean();
        flush();

        // Mengirimkan header secara kolektif
        header('Content-Type: application/pdf');
        header('Accept-Ranges: bytes'); 
        header('Access-Control-Allow-Origin: *');
        header('Content-Length: ' . filesize($path)); 
        header('Cache-Control: public, max-age=86400');
        
        readfile($path);
        exit;
    } else {
        http_response_code(404);
        echo "File tidak ditemukan atau ukuran file 0 byte.";
        exit;
    }
}
?>
