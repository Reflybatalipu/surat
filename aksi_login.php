<?php
session_start();
include 'config/koneksi.php';

if (isset($_POST['login'])) {
    $nip      = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);
    
    
date_default_timezone_set('Asia/Makassar');
    // Cek user aktif
    $query = mysqli_query($koneksi, "
        SELECT u.*, r.nama_role, uk.nama_unit 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        LEFT JOIN unit_kerja uk ON u.unit_id = uk.id 
        WHERE u.nip = '$nip' AND u.is_active = 1
    ");

    if (mysqli_num_rows($query) > 0) {
        $data_user = mysqli_fetch_array($query);

        // Verifikasi Password Hash
        if (password_verify($password, $data_user['password_hash'])) {
            
            // Set Session Login
            $_SESSION['status_login'] = true;
            $_SESSION['user_id']      = $data_user['id'];
            $_SESSION['nip']          = $data_user['nip'];
            $_SESSION['nama_lengkap'] = $data_user['nama_lengkap'];
            $_SESSION['role_id']      = $data_user['role_id'];
            $_SESSION['nama_role']    = $data_user['nama_role'];
            $_SESSION['unit_id']      = $data_user['unit_id'];
            $_SESSION['nama_unit']    = $data_user['nama_unit'];
            $_SESSION['foto_profil']  = $data_user['foto_profil'];

            // === LOGIKA "REMEMBER ME" (PERSISTENT COOKIE) ===
            if (isset($_POST['remember'])) {
                // Buat token unik sepanjang 64 karakter
                $token = bin2hex(random_bytes(32));
                
                // Simpan token ke database
                mysqli_query($koneksi, "UPDATE users SET remember_token = '$token' WHERE id = '{$data_user['id']}'");
                
                // Tanamkan Cookie di browser selama 30 hari
                setcookie('simpers_token', $token, time() + (86400 * 30), "/");
            }

            catat_audit_log($koneksi, 'LOGIN_SUCCESS', 'users', $data_user['id']);
            
            header("Location: dashboard.php");
            exit;
        } else {
            echo "<script>alert('Login Gagal! Password salah.'); location.href='index.php';</script>";
        }
    } else {
        echo "<script>alert('Login Gagal! NIP tidak ditemukan atau akun dinonaktifkan.'); location.href='index.php';</script>";
    }
}
?>