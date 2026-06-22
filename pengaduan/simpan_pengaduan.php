<?php
session_start();
include '../config/koneksi.php'; 

// 1. Cek apakah user sudah login
if (!isset($_SESSION['status_login']) || !isset($_SESSION['user_id'])) {
    echo "<script>alert('Sesi Anda telah habis. Silakan login kembali.'); window.location.href='login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user        = $_SESSION['user_id'];
    $subjek         = mysqli_real_escape_string($koneksi, $_POST['subjek']);
    $level_gangguan = mysqli_real_escape_string($koneksi, $_POST['level_gangguan']);
    $deskripsi      = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    // =========================================================
    // 2. LOGIKA PENOMORAN TIKET OTOMATIS (Format: TKT-YYYYMMDD-001)
    // =========================================================
    $hari_ini = date('Ymd');
    // Cari nomor tiket terakhir di hari yang sama
    $query_tiket = mysqli_query($koneksi, "SELECT MAX(RIGHT(no_tiket, 3)) AS nomor_terakhir FROM pengaduan WHERE no_tiket LIKE 'TKT-$hari_ini-%'");
    $data_tiket = mysqli_fetch_array($query_tiket);
    
    if ($data_tiket['nomor_terakhir']) {
        $urutan = (int) $data_tiket['nomor_terakhir'];
        $urutan++;
    } else {
        $urutan = 1;
    }
    // Gabungkan menjadi nomor tiket baru (sprintf %03d untuk format 3 digit, cth: 001)
    $no_tiket = "TKT-" . $hari_ini . "-" . sprintf("%03d", $urutan);

    // =========================================================
    // 3. LOGIKA UPLOAD FILE LAMPIRAN / SCREENSHOT
    // =========================================================
    $nama_file = $_FILES['file_lampiran']['name'];
    $ukuran_file = $_FILES['file_lampiran']['size'];
    $tmp_file = $_FILES['file_lampiran']['tmp_name'];
    $error_file = $_FILES['file_lampiran']['error'];

    // Validasi apakah ada file yang diunggah dan tidak error
    if ($error_file === 4) {
        echo "<script>alert('GAGAL: Anda wajib melampirkan Screenshot Error sesuai kebijakan SLA!'); window.history.back();</script>";
        exit;
    }

    // Validasi Ekstensi File (Hanya perbolehkan Gambar & PDF)
    $ekstensi_valid = ['jpg', 'jpeg', 'png', 'pdf'];
    $ekstensi_file = explode('.', $nama_file);
    $ekstensi_file = strtolower(end($ekstensi_file));

    if (!in_array($ekstensi_file, $ekstensi_valid)) {
        echo "<script>alert('GAGAL: Format file tidak didukung! Hanya perbolehkan JPG, PNG, atau PDF.'); window.history.back();</script>";
        exit;
    }

    // Validasi Ukuran File (Maksimal 2 MB)
    if ($ukuran_file > 2000000) {
        echo "<script>alert('GAGAL: Ukuran file terlalu besar! Maksimal 2 MB.'); window.history.back();</script>";
        exit;
    }

    // Generate Nama File Baru (Biar tidak bentrok di server)
    $nama_file_baru = "Lampiran_" . $no_tiket . "_" . uniqid() . "." . $ekstensi_file;
    $folder_tujuan = '../uploads/pengaduan/'; // Pastikan folder ini sudah Anda buat!

    // Cek apakah folder uploads/pengaduan/ sudah ada, jika belum buat otomatis
    if (!is_dir($folder_tujuan)) {
        mkdir($folder_tujuan, 0777, true);
    }

    // Pindahkan file ke folder server
    if (move_uploaded_file($tmp_file, $folder_tujuan . $nama_file_baru)) {
        
        // =========================================================
        // 4. INSERT KE DATABASE
        // =========================================================
        $query_insert = "INSERT INTO pengaduan (no_tiket, id_user, subjek, level_gangguan, deskripsi, file_lampiran, status, waktu_lapor) 
                         VALUES ('$no_tiket', '$id_user', '$subjek', '$level_gangguan', '$deskripsi', '$nama_file_baru', 'Open', NOW())";

        if (mysqli_query($koneksi, $query_insert)) {
            
            // =========================================================
            // 5. [OPSIONAL] FIRE AND FORGET: Notifikasi Bot Telegram ke Tim IT
            // (Opsional: Jika Anda punya ID Telegram grup khusus IT)
            // =========================================================
            /*
            $id_grup_it = "ID_TELEGRAM_GRUP_IT_ANDA";
            $pesan_it = "🚨 *TIKET PENGADUAN BARU* 🚨\n\n";
            $pesan_it .= "▪️ *No Tiket:* `$no_tiket`\n";
            $pesan_it .= "▪️ *Level:* `$level_gangguan`\n";
            $pesan_it .= "▪️ *Subjek:* $subjek\n";
            $pesan_it .= "\nHarap segera direspon sesuai batas waktu SLA!";
            
            mysqli_query($koneksi, "INSERT INTO antrean_telegram (telegram_id, pesan, status_kirim) VALUES ('$id_grup_it', '$pesan_it', 'pending')");
            */

            echo "<script>
                alert('Berhasil! Tiket pengaduan Anda dengan nomor [$no_tiket] telah diteruskan ke Tim IT. Anda dapat memantau statusnya di menu riwayat.');
                window.location.href='riwayat_pengaduan.php'; // Arahkan user ke halaman riwayat
            </script>";
        } else {
            echo "<script>alert('Gagal menyimpan data ke database. Hubungi Administrator.'); window.history.back();</script>";
        }

    } else {
        echo "<script>alert('Gagal mengunggah file screenshot. Periksa hak akses folder server.'); window.history.back();</script>";
    }
} else {
    // Jika diakses tidak melalui metode POST
    header("Location: tambah_pengaduan.php");
}
?>