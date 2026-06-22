<?php
session_start();
include 'config/koneksi.php';

// Pastikan ada token yang dikirim dan user sudah login
if (isset($_POST['token']) && isset($_SESSION['status_login']) && $_SESSION['status_login'] === true) {
    
    $token = mysqli_real_escape_string($koneksi, $_POST['token']);
    
    // Menggunakan Session ID yang tepat dari sistem SIMPERS-mu!
    $user_id = $_SESSION['user_id']; 

    // Simpan token ke database
    $query = "UPDATE users SET fcm_token = '$token' WHERE id = '$user_id'";
    
    if (mysqli_query($koneksi, $query)) {
        echo "Token FCM berhasil disimpan untuk user ID: $user_id";
    } else {
        echo "Gagal menyimpan token: " . mysqli_error($koneksi);
    }
} else {
    echo "Ditolak: Sesi tidak valid atau token kosong.";
}
?>