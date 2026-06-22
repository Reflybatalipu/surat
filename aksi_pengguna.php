<?php
session_start();
include 'config/koneksi.php';

// ================ TAMBAH DATA PENGGUNA ================
if (isset($_POST['tambah'])) {
    // 1. Tangkap inputan dan bersihkan (Mencegah SQL Injection)
    $nip          = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $role_id      = $_POST['role_id'];
    $unit_id      = $_POST['unit_id'];

    // 2. Cek apakah NIP sudah ada (Jangan sampai ada NIP ganda)
    $cek_nip = mysqli_query($koneksi, "SELECT nip FROM users WHERE nip = '$nip'");
    if (mysqli_num_rows($cek_nip) > 0) {
        echo "<script>
            alert('Gagal! NIP tersebut sudah terdaftar di sistem.');
            location.href='pengguna.php';
        </script>";
        exit;
    }

    // 3. Insert ke database (password_hash dikosongkan agar user aktivasi mandiri)
    $sql = mysqli_query($koneksi, 
        "INSERT INTO users (nip, nama_lengkap, telegram_id, role_id, unit_id, password_hash, is_active) 
         VALUES ('$nip', '$nama_lengkap', '', '$role_id', '$unit_id', '', 1)"
    );

    if ($sql) {
        // CATAT LOG TAMBAH USER
    $id_baru = mysqli_insert_id($koneksi); // Ambil ID user yang baru saja masuk DB
        catat_audit_log($koneksi, 'CREATE_USER', 'users', $id_baru);
        echo "<script>
            alert('Data pegawai berhasil ditambahkan. Silakan instruksikan pegawai untuk melakukan Aktivasi Akun.');
            location.href='pengguna.php';
        </script>";
    } else {
        echo "<script>
            alert('Terjadi kesalahan sistem saat menyimpan data.');
            location.href='pengguna.php';
        </script>";
    }
}

// ================ EDIT DATA PENGGUNA ================
if (isset($_POST['edit'])) {
    $id           = $_POST['id'];
    $nip          = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $is_active    = $_POST['is_active'];

    $sql = mysqli_query($koneksi, 
        "UPDATE users SET 
            nip = '$nip',
            nama_lengkap = '$nama_lengkap',
            is_active = '$is_active'
         WHERE id = '$id'"
    );

    if ($sql) {
        echo "<script>
            alert('Data pegawai berhasil diperbarui!');
            location.href='pengguna.php';
        </script>";
    }
}

// ================ HAPUS DATA PENGGUNA (SOFT DELETE) ================
if (isset($_POST['hapus'])) {
    $id = $_POST['id'];

    // ITIL Security: Kita TIDAK MENGGUNAKAN perintah DELETE FROM. 
    // Kita hanya mengubah is_active menjadi 0 agar jejak surat/disposisi user ini di masa lalu tidak error (Broken Relation).
    $sql = mysqli_query($koneksi, "UPDATE users SET is_active = 0 WHERE id = '$id'");

    if ($sql) {
        echo "<script>
            alert('Akun pegawai berhasil dinonaktifkan!');
            location.href='pengguna.php';
        </script>";
    }
}
?>