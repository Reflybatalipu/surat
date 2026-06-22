<?php
// simpan_pengaduan_publik.php
include '../config/koneksi.php'; // Sesuaikan path koneksi

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Ambil data dari form publik
    $nip_input      = mysqli_real_escape_string($koneksi, $_POST['nip_manual']);
    $subjek         = mysqli_real_escape_string($koneksi, $_POST['subjek']);
    $kontak_wa      = mysqli_real_escape_string($koneksi, $_POST['kontak_wa']);
    $deskripsi_user = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    
    // Gabungkan deskripsi dengan nomor WA agar IT mudah menghubungi balik
    $deskripsi = "Kontak WA Pelapor: $kontak_wa | Kendala: " . $deskripsi_user;
    
    $level_gangguan = "S2"; 

    // =========================================================
    // 2. CARI ID_USER BERDASARKAN NIP
    // =========================================================
    $query_cek_user = mysqli_query($koneksi, "SELECT id FROM users WHERE nip = '$nip_input' LIMIT 1");
    $data_user = mysqli_fetch_array($query_cek_user);

    if (!$data_user) {
        echo "<script>alert('GAGAL: NIP [$nip_input] tidak ditemukan di sistem. Pastikan NIP Anda benar!'); window.history.back();</script>";
        exit;
    }
    $id_user = $data_user['id'];

    // =========================================================
    // 3. LOGIKA PENOMORAN TIKET OTOMATIS (Sama dengan kode Anda)
    // =========================================================
    $hari_ini = date('Ymd');
    $query_tiket = mysqli_query($koneksi, "SELECT MAX(RIGHT(no_tiket, 3)) AS nomor_terakhir FROM pengaduan WHERE no_tiket LIKE 'TKT-$hari_ini-%'");
    $data_tiket = mysqli_fetch_array($query_tiket);
    
    $urutan = ($data_tiket['nomor_terakhir']) ? (int) $data_tiket['nomor_terakhir'] + 1 : 1;
    $no_tiket = "TKT-" . $hari_ini . "-" . sprintf("%03d", $urutan);

    // =========================================================
    // 4. LOGIKA UPLOAD FILE LAMPIRAN (Sama dengan kode Anda)
    // =========================================================
    $nama_file   = $_FILES['file_lampiran']['name'];
    $ukuran_file = $_FILES['file_lampiran']['size'];
    $tmp_file    = $_FILES['file_lampiran']['tmp_name'];
    $error_file  = $_FILES['file_lampiran']['error'];

    if ($error_file === 4) {
        echo "<script>alert('GAGAL: Anda wajib melampirkan Screenshot Error!'); window.history.back();</script>";
        exit;
    }

    $ekstensi_valid = ['jpg', 'jpeg', 'png', 'pdf'];
    $ekstensi_file  = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));

    if (!in_array($ekstensi_file, $ekstensi_valid)) {
        echo "<script>alert('GAGAL: Format file tidak didukung! Gunakan JPG, PNG, atau PDF.'); window.history.back();</script>";
        exit;
    }

    if ($ukuran_file > 2000000) { // 2MB
        echo "<script>alert('GAGAL: Ukuran file maksimal 2 MB.'); window.history.back();</script>";
        exit;
    }

    $nama_file_baru = "Lampiran_LUAR_" . $no_tiket . "_" . uniqid() . "." . $ekstensi_file;
    $folder_tujuan = '../uploads/pengaduan/';

    if (!is_dir($folder_tujuan)) {
        mkdir($folder_tujuan, 0777, true);
    }

    // =========================================================
    // 5. PROSES SIMPAN
    // =========================================================
    if (move_uploaded_file($tmp_file, $folder_tujuan . $nama_file_baru)) {
        
        $query_insert = "INSERT INTO pengaduan (no_tiket, id_user, subjek, level_gangguan, deskripsi, file_lampiran, status, waktu_lapor) 
                         VALUES ('$no_tiket', '$id_user', '$subjek', '$level_gangguan', '$deskripsi', '$nama_file_baru', 'Open', NOW())";

        if (mysqli_query($koneksi, $query_insert)) {
            echo "<script>
                alert('Berhasil! Tiket [$no_tiket] telah dikirim. Tim IT akan segera mengecek akun dengan NIP $nip_input. Silakan cek berkala halaman riwayat jika Anda sudah bisa login.');
                window.location.href='../index.php'; 
            </script>";
        } else {
            echo "<script>alert('Gagal menyimpan ke database.'); window.history.back();</script>";
        }

    } else {
        echo "<script>alert('Gagal mengunggah file. Cek folder permission.'); window.history.back();</script>";
    }
} else {
    header("Location: pusat_bantuan_luar.php");
}
?>