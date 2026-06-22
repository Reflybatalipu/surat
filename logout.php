<?php
session_start();
include 'config/koneksi.php';

// Hapus token di Database jika user sedang login
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    mysqli_query($koneksi, "UPDATE users SET remember_token = NULL WHERE id = '$user_id'");

    catat_audit_log($koneksi, 'LOGOUT', 'users', $user_id);
    
    }

// Hancurkan Session
session_unset();
session_destroy();

// Hancurkan Cookie dari Browser (Mundurkan waktu 1 jam)
if (isset($_COOKIE['simpers_token'])) {
    setcookie('simpers_token', '', time() - 3600, "/");
}

echo "<script>
    alert('Anda telah berhasil keluar dari sistem.');
    location.href='index.php';
</script>";
exit;
?>