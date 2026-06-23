<?php 
session_start();
include 'config/koneksi.php';

function set_register_flash($type, $message, $field_errors = [], $old = []) {
    $_SESSION['flash_register'] = [
        'type' => $type,
        'message' => $message,
        'field_errors' => $field_errors
    ];
    $_SESSION['old_register'] = $old;
}

function redirect_register() {
    header("Location: register.php");
    exit;
}

function redirect_login() {
    header("Location: index.php");
    exit;
}

// ================ AKTIVASI AKUN PENGGUNA ================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit;
}

// 1. Tangkap inputan
$nip                 = trim($_POST['nip'] ?? '');
$telegram_id_raw     = trim($_POST['telegram_id'] ?? '');
$password            = $_POST['password'] ?? '';
$konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

$old = [
    'nip' => $nip,
    'telegram_id' => $telegram_id_raw
];

$field_errors = [];

if ($nip === '') {
    $field_errors['nip'] = 'NIP / ID Pengguna wajib diisi.';
}

if ($password === '') {
    $field_errors['password'] = 'Password baru wajib diisi.';
} elseif (strlen($password) < 8) {
    $field_errors['password'] = 'Password minimal 8 karakter.';
}

if ($konfirmasi_password === '') {
    $field_errors['konfirmasi_password'] = 'Konfirmasi password wajib diisi.';
} elseif ($password !== $konfirmasi_password) {
    $field_errors['konfirmasi_password'] = 'Konfirmasi password tidak sama.';
}

// Telegram ID opsional. Jika diisi, simpan angka saja.
$telegram_id_bersih = preg_replace('/[^0-9]/', '', $telegram_id_raw);

if ($telegram_id_raw !== '' && $telegram_id_bersih === '') {
    $field_errors['telegram_id'] = 'ID Telegram hanya boleh berisi angka.';
}

if (!empty($field_errors)) {
    set_register_flash('danger', 'Aktivasi belum dapat diproses. Periksa kembali data yang ditandai.', $field_errors, $old);
    redirect_register();
}

$nip_safe = mysqli_real_escape_string($koneksi, $nip);

// 3. Cek apakah NIP terdaftar di database
$query_cek = mysqli_query($koneksi, 
    "SELECT users.*, roles.nama_role, unit_kerja.nama_unit 
     FROM users 
     JOIN roles ON users.role_id = roles.id 
     JOIN unit_kerja ON users.unit_id = unit_kerja.id 
     WHERE users.nip = '$nip_safe'"
);

if (!$query_cek) {
    set_register_flash('danger', 'Terjadi kesalahan server saat memeriksa data akun.', [], $old);
    redirect_register();
}

if (mysqli_num_rows($query_cek) <= 0) {
    set_register_flash('danger', 'NIP / ID Pengguna tidak terdaftar untuk aktivasi akun.', [
        'nip' => 'NIP / ID Pengguna tidak ditemukan.'
    ], $old);
    redirect_register();
}

$data_user = mysqli_fetch_array($query_cek);
$user_id_baru = (int)$data_user['id'];

if (!empty($data_user['password_hash'])) {
    $_SESSION['flash_login'] = [
        'type' => 'warning',
        'message' => 'Akun dengan NIP tersebut sudah aktif. Silakan login.'
    ];
    redirect_login();
}

$password_hashed = password_hash($password, PASSWORD_DEFAULT);
$password_hashed_safe = mysqli_real_escape_string($koneksi, $password_hashed);
$telegram_id_safe = mysqli_real_escape_string($koneksi, $telegram_id_bersih);

// 6. Update password_hash DAN telegram_id di database.
// Telegram ID tetap boleh kosong dan dapat dilengkapi nanti di profil.
$update = mysqli_query($koneksi, "
    UPDATE users 
    SET password_hash = '$password_hashed_safe', telegram_id = '$telegram_id_safe' 
    WHERE nip = '$nip_safe'
");

if ($update) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $query_log = "INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                  VALUES (?, 'ACTIVATE_ACCOUNT', 'users', ?, ?, ?)";
    $stmt_log = mysqli_prepare($koneksi, $query_log);
    if ($stmt_log) {
        mysqli_stmt_bind_param($stmt_log, "iiss", $user_id_baru, $user_id_baru, $ip_address, $user_agent);
        mysqli_stmt_execute($stmt_log);
        mysqli_stmt_close($stmt_log);
    }

    $_SESSION['flash_login'] = [
        'type' => 'success',
        'message' => 'Aktivasi berhasil. Silakan login menggunakan password yang baru dibuat.'
    ];
    redirect_login();
}

set_register_flash('danger', 'Terjadi kesalahan server saat mengaktifkan akun.', [], $old);
redirect_register();
?>
