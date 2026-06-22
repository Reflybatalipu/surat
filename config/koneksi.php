<?php
    
    date_default_timezone_set('Asia/Makassar');
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    if (!headers_sent()) {
        header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
        exit;
    }
}



// ================ KONFIGURASI DATABASE ================
$host       = "sql100.infinityfree.com"; 
$user       = "if0_41738053";
$password   = "ylUFMppJUqUCmL";
$database   = "if0_41738053_simpers";

// Membuat koneksi
$koneksi = mysqli_connect($host, $user, $password, $database);

// Mengecek koneksi
if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}



// Set charset ke utf8mb4 agar mendukung karakter khusus/emoji dengan aman
mysqli_set_charset($koneksi, "utf8mb4");

mysqli_query($koneksi, "SET time_zone = '+08:00'");

// ==========================================================
// PENCEGAH ERROR "CANNOT REDECLARE"
// ==========================================================
if (!function_exists('catat_audit_log')) {
    function catat_audit_log($koneksi, $action, $table_name, $record_id) {
        // 1. Pastikan session berjalan untuk mengambil ID User
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // 2. Ambil data dari session dan server
        // CATATAN: Sesuaikan 'id_user' dengan nama session yang kamu pakai saat login!
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        
        // Jika tidak ada user yang login (misal error), kita hentikan pencatatan untuk mencegah error DB
        if ($user_id == 0) return false; 

        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        // 3. Siapkan query (Menggunakan Prepared Statement agar aman dari SQL Injection)
        $query = "INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?, ?)";
                  
        $stmt = mysqli_prepare($koneksi, $query);
        
        // i = integer, s = string
        mysqli_stmt_bind_param($stmt, "ississ", $user_id, $action, $table_name, $record_id, $ip_address, $user_agent);
        
        // 4. Eksekusi
        $hasil = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        return $hasil;
    }
}
?>
