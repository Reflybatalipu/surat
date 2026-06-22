<?php 
session_start();
include 'config/koneksi.php';

// ================ AKTIVASI AKUN PENGGUNA ================
if (isset($_POST['register'])) {
    
    // 1. Tangkap inputan
    $nip                 = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $telegram_id_raw     = mysqli_real_escape_string($koneksi, $_POST['telegram_id']); 
    $password            = mysqli_real_escape_string($koneksi, $_POST['password']);
    $konfirmasi_password = mysqli_real_escape_string($koneksi, $_POST['konfirmasi_password']);

    // Pastikan Telegram ID hanya berisi angka (opsional tapi aman)
    $telegram_id_bersih = preg_replace('/[^0-9]/', '', $telegram_id_raw);

    // 2. Cek apakah Password dan Konfirmasi sama
    if ($password !== $konfirmasi_password) {
        echo "<script>
            alert('Aktivasi Gagal! Password dan Konfirmasi Password tidak cocok.');
            location.href='register.php';
        </script>";
        exit;
    }

    // 3. Cek apakah NIP terdaftar di database
    $query_cek = mysqli_query($koneksi, 
        "SELECT users.*, roles.nama_role, unit_kerja.nama_unit 
         FROM users 
         JOIN roles ON users.role_id = roles.id 
         JOIN unit_kerja ON users.unit_id = unit_kerja.id 
         WHERE users.nip = '$nip'"
    );

    if (mysqli_num_rows($query_cek) > 0) {
        $data_user = mysqli_fetch_array($query_cek);
        $user_id_baru = $data_user['id'];

        if (!empty($data_user['password_hash'])) {
            echo "<script>
                alert('Akun dengan NIP ini sudah aktif! Silakan masuk melalui halaman Login.');
                location.href='index.php';
            </script>";
            exit;
        }

        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        // 6. Update password_hash DAN telegram_id di database
        $update = mysqli_query($koneksi, "UPDATE users SET password_hash = '$password_hashed', telegram_id = '$telegram_id_bersih' WHERE nip = '$nip'");

        if ($update) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            
            $query_log = "INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                          VALUES (?, 'ACTIVATE_ACCOUNT', 'users', ?, ?, ?)";
            $stmt_log = mysqli_prepare($koneksi, $query_log);
            mysqli_stmt_bind_param($stmt_log, "iiss", $user_id_baru, $user_id_baru, $ip_address, $user_agent);
            mysqli_stmt_execute($stmt_log);
            mysqli_stmt_close($stmt_log);

            echo "<script>
                alert('Aktivasi Berhasil! Silakan Login.');
                location.href='index.php';
            </script>";
        } else {
            echo "<script>
                alert('Terjadi kesalahan server.');
                location.href='register.php';
            </script>";
        }
    } else {
        echo "<script>
            alert('Aktivasi Gagal! NIP tidak terdaftar.');
            location.href='register.php';
        </script>";
    }
}
?>