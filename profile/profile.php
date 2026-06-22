<?php
session_start();
include '../config/koneksi.php'; // Sesuaikan path koneksi

// FUNGSI KOMPRESI GAMBAR
function kompresGambar($source, $destination, $quality) {
    $info = getimagesize($source);

    if ($info['mime'] == 'image/jpeg' || $info['mime'] == 'image/jpg') { 
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/png') { 
        $image = imagecreatefrompng($source);
    } else {
        return false; 
    }

    // Simpan gambar dengan kualitas 70% (Sangat cukup untuk foto profil)
    imagejpeg($image, $destination, $quality);
    imagedestroy($image);
    return true;
}

// 🛡️ KEAMANAN: Cek Login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    echo "<script>alert('Akses ditolak. Silakan login terlebih dahulu.'); window.location='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id']; 
$pesan_sukses = "";
$pesan_error = "";

// ==========================================
// PROSES UPDATE PROFIL & FOTO
// ==========================================
if (isset($_POST['update_profil'])) {
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $nip = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $telegram_id = mysqli_real_escape_string($koneksi, $_POST['telegram_id']);
    
    $query_tambahan_foto = "";

    // Cek apakah ada file foto yang diunggah
    if ($_FILES['foto_profil']['name'] != '') {
        $foto_nama = $_FILES['foto_profil']['name'];
        $foto_tmp = $_FILES['foto_profil']['tmp_name'];
        $ekstensi_diperbolehkan = array('png', 'jpg', 'jpeg');
        $x = explode('.', $foto_nama);
        $ekstensi = strtolower(end($x));
        
        // Buat nama file unik
        $nama_file_baru = "profil_" . $user_id . "_" . time() . ".jpg"; // Output kompresi jadi .jpg
        $direktori = '../assets/img/';

        if (in_array($ekstensi, $ekstensi_diperbolehkan) === true) {
            // Ambil foto lama untuk dihapus
            $q_foto_lama = mysqli_query($koneksi, "SELECT foto_profil FROM users WHERE id = '$user_id'");
            $dt_foto_lama = mysqli_fetch_assoc($q_foto_lama);
            $foto_lama = $dt_foto_lama['foto_profil'];
            
            // JALANKAN KOMPRESI
            $tujuan_file = $direktori . $nama_file_baru;
            if (kompresGambar($foto_tmp, $tujuan_file, 70)) {
                // Hapus file lama jika bukan default.png
                if ($foto_lama != 'default.png' && !empty($foto_lama) && file_exists($direktori . $foto_lama)) {
                    unlink($direktori . $foto_lama);
                }
                $query_tambahan_foto = ", foto_profil = '$nama_file_baru'";
                $_SESSION['foto_profil'] = $nama_file_baru; 
            } else {
                $pesan_error = "Gagal memproses kompresi foto profil!";
            }
        } else {
            $pesan_error = "Ekstensi file tidak diperbolehkan! Hanya JPG/PNG.";
        }
    }

    // Update database
    if (empty($pesan_error)) {
        $sql = "UPDATE users SET 
                nama_lengkap = '$nama_lengkap', 
                nip = '$nip', 
                telegram_id = '$telegram_id' 
                $query_tambahan_foto 
                WHERE id = '$user_id'";
                
        if (mysqli_query($koneksi, $sql)) {
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            $pesan_sukses = "Profil berhasil diperbarui!";
        } else {
            $pesan_error = "Gagal memperbarui profil: " . mysqli_error($koneksi);
        }
    }
}

// ==========================================
// PROSES UBAH PASSWORD
// ==========================================
if (isset($_POST['ubah_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    $q_pass = mysqli_query($koneksi, "SELECT password_hash FROM users WHERE id = '$user_id'");
    $data_pass = mysqli_fetch_assoc($q_pass);

    if (password_verify($password_lama, $data_pass['password_hash'])) {
        if ($password_baru === $konfirmasi_password) {
            $password_hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);
            $query_pass = mysqli_query($koneksi, "UPDATE users SET password_hash = '$password_hash_baru' WHERE id = '$user_id'");
            
            if ($query_pass) {
                $pesan_sukses = "Password berhasil diubah!";
            } else {
                $pesan_error = "Gagal mengubah password!";
            }
        } else {
            $pesan_error = "Password baru dan konfirmasi tidak cocok!";
        }
    } else {
        $pesan_error = "Password lama yang Anda masukkan salah!";
    }
}

// ==========================================
// AMBIL DATA USER SAAT INI
// ==========================================
$query_user = mysqli_query($koneksi, "
    SELECT u.*, r.nama_role 
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    WHERE u.id = '$user_id'
");
$user = mysqli_fetch_assoc($query_user);

// Tentukan foto yang akan ditampilkan
$foto_tampil = (!empty($user['foto_profil'])) ? $user['foto_profil'] : 'default.png';

// Include Header & Sidebar
include '../layouts/header.php'; 
?>

<div class="container-fluid mt-4 mb-5">
    <h3 class="fw-bold mb-4"><i class="fa-solid fa-user-circle me-2"></i> Profil Pengguna</h3>

    <?php if ($pesan_sukses): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-1"></i> <?= $pesan_sukses ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($pesan_error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= $pesan_error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="fa-solid fa-address-card me-1"></i> Informasi Akun
                </div>
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        
                        <div class="text-center mb-4">
                           <img src="../assets/img/<?= $foto_tampil ?>?v=<?= time() ?>" alt="Foto Profil" class="img-thumbnail rounded-circle shadow-sm" style="width: 150px; height: 150px; object-fit: cover;">
                            <div class="mt-3">
                                <label for="inputFoto" class="form-label fw-bold">Ganti Foto Profil</label>
                                <input class="form-control form-control-sm" type="file" id="inputFoto" name="foto_profil" accept=".jpg, .jpeg, .png">
                                <small class="text-muted d-block mt-1">*Maksimal 2MB. Format: JPG/PNG.</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Hak Akses (Role)</label>
                            <input type="text" class="form-control bg-light" value="<?= $user['nama_role'] ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" value="<?= $user['nama_lengkap'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">NIP</label>
                            <input type="text" name="nip" class="form-control" value="<?= $user['nip'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">ID Telegram</label>
                            <input type="text" name="telegram_id" class="form-control" value="<?= $user['telegram_id'] ?>">
                        </div>
                        <hr>
                        <button type="submit" name="update_profil" class="btn btn-success w-100">
                            <i class="fa-solid fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white fw-bold">
                    <i class="fa-solid fa-key me-1"></i> Ubah Password
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Password Lama</label>
                            <input type="password" name="password_lama" class="form-control" placeholder="Masukkan password saat ini" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Password Baru</label>
                            <input type="password" name="password_baru" class="form-control" placeholder="Masukkan password baru" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Konfirmasi Password Baru</label>
                            <input type="password" name="konfirmasi_password" class="form-control" placeholder="Ulangi password baru" required minlength="6">
                        </div>
                        <hr>
                        <button type="submit" name="ubah_password" class="btn btn-danger w-100">
                            <i class="fa-solid fa-lock me-1"></i> Ubah Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('inputFoto').addEventListener('change', function() {
    if (this.files[0].size > 40 * 1024 * 1024) {
        alert('File terlalu besar! Maksimal 40MB (akan dikompres otomatis oleh sistem).');
        this.value = '';
    }
});
</script>
<?php include '../layouts/footer.php'; ?>