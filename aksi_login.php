<?php
session_start();
include 'config/koneksi.php';

date_default_timezone_set('Asia/Makassar');

function kembali_login_dengan_error(array $errors, string $nip = ''): void
{
    $_SESSION['login_errors']  = $errors;
    $_SESSION['login_old_nip'] = $nip;
    header("Location: index.php");
    exit;
}

if (!isset($_POST['login'])) {
    header("Location: index.php");
    exit;
}

$nip      = trim($_POST['nip'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];

if ($nip === '') {
    $errors['nip'] = 'NIP / ID Pengguna wajib diisi.';
}

if ($password === '') {
    $errors['password'] = 'Password wajib diisi.';
}

if (!empty($errors)) {
    kembali_login_dengan_error($errors, $nip);
}

$nip_safe = mysqli_real_escape_string($koneksi, $nip);

// Cek user aktif berdasarkan NIP
$query = mysqli_query($koneksi, "
    SELECT u.*, r.nama_role, uk.nama_unit 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    LEFT JOIN unit_kerja uk ON u.unit_id = uk.id 
    WHERE u.nip = '$nip_safe' AND u.is_active = 1
");

if ($query && mysqli_num_rows($query) > 0) {
    $data_user = mysqli_fetch_array($query);

    // Verifikasi Password Hash
    if (password_verify($password, $data_user['password_hash'])) {
        session_regenerate_id(true);

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
            $token_safe = mysqli_real_escape_string($koneksi, $token);
            $user_id_safe = (int)$data_user['id'];
            
            // Simpan token ke database
            mysqli_query($koneksi, "UPDATE users SET remember_token = '$token_safe' WHERE id = '$user_id_safe'");
            
            // Tanamkan Cookie di browser selama 30 hari
            setcookie('simpers_token', $token, [
                'expires'  => time() + (86400 * 30),
                'path'     => '/',
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        catat_audit_log($koneksi, 'LOGIN_SUCCESS', 'users', $data_user['id']);
        
        header("Location: dashboard.php");
        exit;
    }
}

// Pesan dibuat umum agar tidak membocorkan apakah NIP terdaftar atau password salah.
kembali_login_dengan_error([
    'password' => 'NIP atau password tidak sesuai.'
], $nip);
?>
