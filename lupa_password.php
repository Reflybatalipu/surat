<?php
include 'config/koneksi.php';

if (isset($_POST['minta_reset'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    
    // Cek apakah user ada?
    $cek = mysqli_query($koneksi, "SELECT id, nama_lengkap FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        // Tandai bahwa user ini minta reset
        mysqli_query($koneksi, "UPDATE users SET req_reset_pass = '1' WHERE username = '$username'");
        echo "<script>alert('Permintaan berhasil dikirim ke Admin TU. Silakan tunggu pesan masuk di Telegram Anda!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Username tidak ditemukan!'); window.location.href='lupa_password.php';</script>";
    }
}
?>
<form method="POST">
    <input type="text" name="username" placeholder="Masukkan Username Anda" required>
    <button type="submit" name="minta_reset">Minta Reset Password via Admin</button>
</form>