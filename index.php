<?php
session_start();
include 'config/koneksi.php';

// ========================================================
// FLASH MESSAGE LOGIN (INLINE ERROR, TANPA alert())
// ========================================================
$login_errors  = $_SESSION['login_errors'] ?? [];
$login_old_nip = $_SESSION['login_old_nip'] ?? '';
unset($_SESSION['login_errors'], $_SESSION['login_old_nip']);

// ========================================================
// PROSES PERMINTAAN RESET PASSWORD (DARI POPUP/MODAL)
// ========================================================
if (isset($_POST['minta_reset'])) {
    $nip_reset = mysqli_real_escape_string($koneksi, $_POST['nip_reset']);
    
    // Cek apakah NIP/User tersebut ada di database
    $cek = mysqli_query($koneksi, "SELECT id FROM users WHERE nip = '$nip_reset'");
    if (mysqli_num_rows($cek) > 0) {
        // Tandai user ini butuh bantuan reset
        mysqli_query($koneksi, "UPDATE users SET req_reset_pass = '1' WHERE nip = '$nip_reset'");
        echo "<script>alert('Permintaan berhasil dikirim ke Admin TU. Silakan tunggu pesan akses masuk di Telegram Anda!'); window.location.href='index.php';</script>";
        exit;
    } else {
        echo "<script>alert('NIP/ID Pengguna tidak ditemukan di sistem!'); window.location.href='index.php';</script>";
        exit;
    }
}

// 1. CEK SESSION: Jika sudah login biasa
if (isset($_SESSION['status_login']) && $_SESSION['status_login'] === true) {
    header("Location: dashboard.php");
    exit;
}

// 2. CEK COOKIE (Auto-Login via Token)
if (isset($_COOKIE['simpers_token'])) {
    $token = mysqli_real_escape_string($koneksi, $_COOKIE['simpers_token']);
    
    // Cari user yang memiliki token ini
    $query_cookie = mysqli_query($koneksi, "
        SELECT u.*, r.nama_role, uk.nama_unit 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        LEFT JOIN unit_kerja uk ON u.unit_id = uk.id 
        WHERE u.remember_token = '$token' AND u.is_active = 1
    ");

    if (mysqli_num_rows($query_cookie) > 0) {
        $data_user = mysqli_fetch_array($query_cookie);
        
        // Daftarkan ulang Session
        $_SESSION['status_login'] = true;
        $_SESSION['user_id']      = $data_user['id'];
        $_SESSION['nip']          = $data_user['nip'];
        $_SESSION['nama_lengkap'] = $data_user['nama_lengkap'];
        $_SESSION['role_id']      = $data_user['role_id'];
        $_SESSION['nama_role']    = $data_user['nama_role'];
        $_SESSION['unit_id']      = $data_user['unit_id'];
        $_SESSION['nama_unit']    = $data_user['nama_unit'];

        // Lompati form, langsung ke Dashboard!
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="SJuTX2Ax-Sn3dF0fkPLluYguRDtsQQnU2z4pbrJQNJ8" />
    <title>Login - SIMPERS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); background-color: #ffffff; }
        .brand-logo { width: 70px; height: 70px; background-color: #4A70A9; color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; margin: 0 auto 1rem; box-shadow: 0 4px 10px rgba(74, 112, 169, 0.3); }
        .btn-simpers { background-color: #4A70A9; color: white; border: none; }
        .btn-simpers:hover { background-color: #3b5a87; color: white; }
        .password-field { position: relative; }
        .password-field .form-control { padding-right: 3.25rem; }
        .toggle-password {
            position: absolute; top: 50%; right: 12px; transform: translateY(-50%);
            border: 0; background: transparent; color: #6c757d; z-index: 5;
            width: 34px; height: 34px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .toggle-password:hover { background: #eef2f7; color: #4A70A9; }
        .field-error { font-size: .78rem; color: #dc3545; margin-top: .35rem; text-align: left; }
        .btn-loading .btn-text { opacity: .75; }
        .btn-loading .spinner-border { display: inline-block !important; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-8 col-md-6 col-lg-4">
                <div class="card login-card border-0 p-3 p-md-4">
                    <div class="card-body text-center">
                        <div class="brand-logo">S</div>
                        <h4 class="fw-bold mb-1 text-dark">SIMPERS</h4>
                        <p class="text-muted mb-4" style="font-size: 0.9rem;">Sistem Informasi Manajemen Persuratan Sekolah</p>

                        <form action="aksi_login.php" method="POST" class="js-auth-form" novalidate>
                            <div class="form-floating text-start <?= isset($login_errors['nip']) ? 'mb-1' : 'mb-3' ?>">
                                <input type="text" class="form-control <?= isset($login_errors['nip']) ? 'is-invalid' : '' ?>" id="nip" name="nip" placeholder="Masukkan NIP" required autofocus autocomplete="username" value="<?= htmlspecialchars($login_old_nip) ?>">
                                <label for="nip">NIP / ID Pengguna</label>
                            </div>
                            <?php if (isset($login_errors['nip'])): ?>
                                <div class="field-error mb-3"><i class="fa-solid fa-circle-exclamation me-1"></i><?= htmlspecialchars($login_errors['nip']) ?></div>
                            <?php endif; ?>

                            <div class="form-floating password-field text-start <?= isset($login_errors['password']) ? 'mb-1' : 'mb-3' ?>">
                                <input type="password" class="form-control <?= isset($login_errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" placeholder="Password" required autocomplete="current-password">
                                <label for="password">Password</label>
                                <button type="button" class="toggle-password" data-target="password" aria-label="Tampilkan password">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($login_errors['password'])): ?>
                                <div class="field-error mb-3"><i class="fa-solid fa-circle-exclamation me-1"></i><?= htmlspecialchars($login_errors['password']) ?></div>
                            <?php endif; ?>

                            <div class="form-check text-start mb-4">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label text-muted" for="remember" style="font-size: 0.9rem;">
                                    Ingat saya di perangkat ini
                                </label>
                            </div>
                            
                            <button type="submit" name="login" class="btn btn-simpers w-100 py-2 mb-3 fw-bold js-submit-btn" style="border-radius: 8px;">
                                <span class="spinner-border spinner-border-sm me-2 d-none" aria-hidden="true"></span>
                                <span class="btn-text">Masuk Sistem</span>
                            </button>
                        </form>
                        
                        <div class="mt-3 text-muted d-flex justify-content-between px-1" style="font-size: 0.85rem;">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#modalLupaPassword" style="color: #6c757d; text-decoration: none;">Lupa Password?</a>
                            <span>|</span>
                            <a href="register.php" style="color: #4A70A9; font-weight: bold; text-decoration: none;">Aktifkan Akun</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalLupaPassword" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-lock text-warning me-2"></i> Lupa Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4 text-start">
                        <p class="text-muted small mb-4">
                            Masukkan NIP/ID Pengguna Anda. Sistem akan mengirimkan notifikasi kepada Admin TU untuk membuatkan password sementara yang akan dikirim via Bot Telegram Anda.
                        </p>
                        <div class="form-floating mb-2">
                            <input type="text" class="form-control" id="nip_reset" name="nip_reset" placeholder="Masukkan NIP" required autocomplete="off">
                            <label for="nip_reset">NIP / ID Pengguna</label>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="minta_reset" class="btn btn-simpers fw-bold"><i class="fa-solid fa-paper-plane me-1"></i> Kirim Permintaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
 <?php
// 1. SETTING JAM KERJA
date_default_timezone_set('Asia/Jakarta'); // Pastikan timezone sesuai
$hari_ini    = date('N'); // 1 (Senin) sampai 7 (Minggu)
$jam_sekarang = date('H:i');
$jam_mulai    = "07:00";
$jam_selesai  = "15:30";

// 2. CEK APAKAH SEDANG JAM KERJA
// Kondisi: Senin-Jumat (1-5) DAN jam di antara 07:00 - 15:30
$is_jam_kerja = ($hari_ini >= 1 && $hari_ini <= 5) && ($jam_sekarang >= $jam_mulai && $jam_sekarang <= $jam_selesai);
?>

<?php if ($is_jam_kerja): ?>
    <a href="../pengaduan/pusat_bantuan_luar.php" class="floating-helpdesk" title="Pusat Bantuan IT (SLA Based)">
        <span class="helpdesk-text">Butuh Bantuan IT? (Online)</span>
        <div class="helpdesk-icon">
            <i class="fa-solid fa-headset"></i>
        </div>
    </a>
<?php else: ?>
    <div class="floating-helpdesk helpdesk-offline" onclick="alert('Maaf, Pusat Bantuan sedang Offline. Jam Kerja IT: Senin-Jumat, 07:00 - 15:30 WIB.')" title="Offline (Di Luar Jam Kerja)">
        <span class="helpdesk-text text-danger">Layanan Offline (Lapor Jam 07:00)</span>
        <div class="helpdesk-icon bg-secondary">
            <i class="fa-solid fa-lock"></i>
        </div>
    </div>
<?php endif; ?>

<style>
/* STYLE DASAR (MILIK ANDA) */
.floating-helpdesk {
    position: fixed;
    bottom: 30px;
    right: 30px;
    display: flex;
    flex-direction: row-reverse; /* Supaya teks di kiri ikon */
    align-items: center;
    text-decoration: none;
    z-index: 9999;
    transition: all 0.3s ease;
    cursor: pointer;
}

.helpdesk-icon {
    width: 60px;
    height: 60px;
    background-color: #dc3545;
    color: white;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 24px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    border: 2px solid white;
    animation: pulse-red 2s infinite;
}

.helpdesk-text {
    background-color: white;
    color: #333;
    padding: 8px 15px;
    border-radius: 20px;
    margin-right: 10px;
    font-weight: bold;
    font-size: 14px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    opacity: 0;
    transform: translateX(20px);
    transition: all 0.3s ease;
}

.floating-helpdesk:hover .helpdesk-text {
    opacity: 1;
    transform: translateX(0);
}

/* MODIFIKASI UNTUK MODE OFFLINE */
.helpdesk-offline .helpdesk-icon {
    background-color: #6c757d !important; /* Warna Abu-abu */
    animation: none; /* Matikan animasi denyut */
    box-shadow: none;
}

.helpdesk-offline:hover .helpdesk-icon {
    transform: scale(1.0); /* Jangan membesar saat hover */
}

@keyframes pulse-red {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 15px rgba(220, 53, 69, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}
</style>

    <script>
        // Toggle show/hide password
        document.querySelectorAll('.toggle-password').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const targetId = btn.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = btn.querySelector('i');
                if (!input) return;

                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
                if (icon) {
                    icon.classList.toggle('fa-eye', !show);
                    icon.classList.toggle('fa-eye-slash', show);
                }
            });
        });

        // Loading state saat form login dikirim
        document.querySelectorAll('.js-auth-form').forEach(function(form) {
            form.addEventListener('submit', function() {
                const btn = form.querySelector('.js-submit-btn');
                if (!btn) return;
                btn.disabled = true;
                btn.classList.add('btn-loading');
                const text = btn.querySelector('.btn-text');
                const spinner = btn.querySelector('.spinner-border');
                if (text) text.textContent = 'Memproses...';
                if (spinner) spinner.classList.remove('d-none');
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>