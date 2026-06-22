<?php
session_start();
include 'config/koneksi.php';

// 🛡️ 1. CEK OTORISASI: Pastikan hanya Admin TU / Kepsek yang bisa mengeksekusi
if (!isset($_SESSION['status_login']) || ($_SESSION['nama_role'] != 'Admin_TU' && $_SESSION['nama_role'] != 'Kepala_Sekolah')) {
    echo "<script>alert('Akses Ditolak!'); location.href='index.php';</script>";
    exit;
}

// ⚙️ 2. PROSES RESET PASSWORD
if (isset($_POST['reset_telegram'])) {
    $id_user = mysqli_real_escape_string($koneksi, $_POST['id_user']);
    
    // Ambil data user target
    $q_user = mysqli_query($koneksi, "SELECT nip, nama_lengkap, telegram_id FROM users WHERE id = '$id_user'");
    $d_user = mysqli_fetch_array($q_user);
    
    // Validasi: Apakah pegawai ini sudah mendaftarkan ID Telegram?
    if (empty($d_user['telegram_id'])) {
        echo "<script>
            alert('GAGAL: Pegawai ini belum menautkan akun Telegram di SIMPERS. Silakan hubungi pegawai bersangkutan secara manual untuk setup awal!'); 
            window.location.href='pengguna.php';
        </script>";
        exit;
    }

    // 🔑 3. BUAT PASSWORD SEMENTARA
    // Menggabungkan kata 'Simpers' dengan 4 angka acak (Contoh: Simpers8231)
    $password_sementara = "Simpers" . rand(1000, 9999);
    
    // Enkripsi password menggunakan standar keamanan PHP modern (Bcrypt)
    $password_hashed = password_hash($password_sementara, PASSWORD_DEFAULT);

    // 💾 4. UPDATE DATABASE
    // Mengubah password_hash dan menghilangkan status Minta Reset (mengembalikan jadi '0')
    $update = mysqli_query($koneksi, "UPDATE users SET password_hash = '$password_hashed', req_reset_pass = '0' WHERE id = '$id_user'");

    if ($update) {
        // ✉️ 5. SIAPKAN PESAN TELEGRAM
        $pesan = "🔐 *PEMULIHAN AKUN SIMPERS* 🔐\n\n";
        $pesan .= "Halo *{$d_user['nama_lengkap']}*,\n";
        $pesan .= "Admin TU telah menyetujui permintaan reset kata sandi Anda.\n\n";
        $pesan .= "▪️ *NIP / ID:* `{$d_user['nip']}`\n";
        $pesan .= "▪️ *Password Baru:* `$password_sementara`\n\n";
        $pesan .= "⚠️ *PENTING:* Anda bisa menyentuh NIP atau Password di atas untuk langsung menyalin (copy). Segera login dan ubah password Anda di menu Profil!";

        // 🚀 6. FIRE AND FORGET: MASUKKAN KE ANTREAN
        $chat_id_safe = mysqli_real_escape_string($koneksi, $d_user['telegram_id']);
        $pesan_safe = mysqli_real_escape_string($koneksi, $pesan);
        
        mysqli_query($koneksi, "INSERT INTO antrean_telegram (telegram_id, pesan, status_kirim) VALUES ('$chat_id_safe', '$pesan_safe', 'pending')");

        // 7. KEMBALI KE HALAMAN ADMIN
        echo "<script>
            alert('Sukses! Password telah di-reset. Pesan pemulihan sedang dikirimkan oleh Bot Telegram.'); 
            window.location.href='pengguna.php';
        </script>";
    } else {
        echo "<script>
            alert('Terjadi kesalahan saat mereset password di database!'); 
            window.location.href='pengguna.php';
        </script>";
    }
} else {
    // Jika ada yang mencoba akses file ini langsung dari URL tanpa klik tombol
    header("Location: pengguna.php");
}
?>