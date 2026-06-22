<?php
session_start();
include '../config/koneksi.php';

if (isset($_POST['simpan_helpdesk'])) {
    $telegram_it = mysqli_real_escape_string($koneksi, $_POST['telegram_it']);
    $user_id_it  = mysqli_real_escape_string($koneksi, $_POST['user_id_it']);
    
    // 1. Simpan/Update Telegram ID (Untuk Notifikasi HP)
    $cek_tele = mysqli_query($koneksi, "SELECT id FROM pengaturan WHERE nama_pengaturan = 'helpdesk_telegram_id'");
    if (mysqli_num_rows($cek_tele) > 0) {
        mysqli_query($koneksi, "UPDATE pengaturan SET nilai_pengaturan = '$telegram_it' WHERE nama_pengaturan = 'helpdesk_telegram_id'");
    } else {
        mysqli_query($koneksi, "INSERT INTO pengaturan (nama_pengaturan, nilai_pengaturan, keterangan) VALUES ('helpdesk_telegram_id', '$telegram_it', 'ID Telegram Tim IT')");
    }

    // 2. Simpan/Update User ID (Untuk Buka Gembok Menu Sidebar)
    $cek_user = mysqli_query($koneksi, "SELECT id FROM pengaturan WHERE nama_pengaturan = 'helpdesk_user_id'");
    if (mysqli_num_rows($cek_user) > 0) {
        mysqli_query($koneksi, "UPDATE pengaturan SET nilai_pengaturan = '$user_id_it' WHERE nama_pengaturan = 'helpdesk_user_id'");
    } else {
        mysqli_query($koneksi, "INSERT INTO pengaturan (nama_pengaturan, nilai_pengaturan, keterangan) VALUES ('helpdesk_user_id', '$user_id_it', 'User ID untuk Akses Menu IT')");
    }
    
    $_SESSION['pesan'] = "Pengaturan Helpdesk & Akses IT berhasil diperbarui!";
    header("Location: setting.php?tab=sistem");
    exit;
}
// ================= AKSI KLASIFIKASI SURAT =================
if (isset($_POST['tambah_klasifikasi'])) {
    $kode = mysqli_real_escape_string($koneksi, $_POST['kode']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    mysqli_query($koneksi, "INSERT INTO klasifikasi_surat (kode, keterangan) VALUES ('$kode', '$keterangan')");
    $_SESSION['pesan'] = "Klasifikasi baru berhasil ditambahkan!";
    header("Location: setting.php?tab=klasifikasi");
    exit;
}

if (isset($_POST['edit_klasifikasi'])) {
    $id = mysqli_real_escape_string($koneksi, $_POST['id']);
    $kode = mysqli_real_escape_string($koneksi, $_POST['kode']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    mysqli_query($koneksi, "UPDATE klasifikasi_surat SET kode = '$kode', keterangan = '$keterangan' WHERE id = '$id'");
    $_SESSION['pesan'] = "Data Klasifikasi berhasil diubah!";
    header("Location: setting.php?tab=klasifikasi");
    exit;
}

if (isset($_GET['hapus_klas'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus_klas']);
    mysqli_query($koneksi, "DELETE FROM klasifikasi_surat WHERE id = '$id'");
    $_SESSION['pesan'] = "Data Klasifikasi berhasil dihapus!";
    header("Location: setting.php?tab=klasifikasi");
    exit;
}

// ================= AKSI UNIT PENGOLAH (BARU) =================
if (isset($_POST['tambah_pengolah'])) {
    $kode_unit = mysqli_real_escape_string($koneksi, $_POST['kode_unit']);
    $nama_unit = mysqli_real_escape_string($koneksi, $_POST['nama_unit']);
    mysqli_query($koneksi, "INSERT INTO unit_pengolah (kode_unit, nama_unit) VALUES ('$kode_unit', '$nama_unit')");
    $_SESSION['pesan'] = "Unit Pengolah berhasil ditambahkan!";
    header("Location: setting.php?tab=pengolah");
    exit;
}

if (isset($_POST['edit_pengolah'])) {
    $id = mysqli_real_escape_string($koneksi, $_POST['id']);
    $kode_unit = mysqli_real_escape_string($koneksi, $_POST['kode_unit']);
    $nama_unit = mysqli_real_escape_string($koneksi, $_POST['nama_unit']);
    mysqli_query($koneksi, "UPDATE unit_pengolah SET kode_unit = '$kode_unit', nama_unit = '$nama_unit' WHERE id = '$id'");
    $_SESSION['pesan'] = "Data Unit Pengolah berhasil diubah!";
    header("Location: setting.php?tab=pengolah");
    exit;
}

if (isset($_GET['hapus_pengolah'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus_pengolah']);
    mysqli_query($koneksi, "DELETE FROM unit_pengolah WHERE id = '$id'");
    $_SESSION['pesan'] = "Data Unit Pengolah berhasil dihapus!";
    header("Location: setting.php?tab=pengolah");
    exit;
}

// ================= AKSI UNIT KERJA LAMA =================
if (isset($_POST['tambah_unit'])) {
    $nama_unit = mysqli_real_escape_string($koneksi, $_POST['nama_unit']);
    mysqli_query($koneksi, "INSERT INTO unit_kerja (nama_unit) VALUES ('$nama_unit')");
    $_SESSION['pesan'] = "Unit Kerja internal berhasil ditambahkan!";
    header("Location: setting.php?tab=unit");
    exit;
}

if (isset($_POST['edit_unit'])) {
    $id = mysqli_real_escape_string($koneksi, $_POST['id']);
    $nama_unit = mysqli_real_escape_string($koneksi, $_POST['nama_unit']);
    mysqli_query($koneksi, "UPDATE unit_kerja SET nama_unit = '$nama_unit' WHERE id = '$id'");
    $_SESSION['pesan'] = "Data Unit Kerja berhasil diubah!";
    header("Location: setting.php?tab=unit");
    exit;
}

if (isset($_GET['hapus_unit'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus_unit']);
    mysqli_query($koneksi, "DELETE FROM unit_kerja WHERE id = '$id'");
    $_SESSION['pesan'] = "Data Unit Kerja berhasil dihapus!";
    header("Location: setting.php?tab=unit");
    exit;
}
?>