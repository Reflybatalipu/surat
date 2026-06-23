<?php
session_start();

// Jika sudah login, tendang ke dashboard
if (isset($_SESSION['status_login']) && $_SESSION['status_login'] === true) {
    header("Location: dashboard.php");
    exit;
}

$flash_register = $_SESSION['flash_register'] ?? null;
$old_register   = $_SESSION['old_register'] ?? [];
unset($_SESSION['flash_register'], $_SESSION['old_register']);

function old_value($key, $default = '') {
    global $old_register;
    return htmlspecialchars($old_register[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}

function field_error($field) {
    global $flash_register;
    if (!empty($flash_register['field_errors'][$field])) {
        return htmlspecialchars($flash_register['field_errors'][$field], ENT_QUOTES, 'UTF-8');
    }
    return '';
}

function is_invalid($field) {
    return field_error($field) !== '' ? ' is-invalid' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Akun - SIMPERS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --simpers-primary: #4A70A9;
            --simpers-hover: #3b5a87;
            --simpers-muted: #64748b;
            --simpers-border: #dbe3ef;
            --simpers-bg: #f4f6f9;
        }

        body {
            background-color: var(--simpers-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 0;
        }

        .register-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            background-color: #ffffff;
        }

        .brand-logo {
            width: 70px;
            height: 70px;
            background-color: var(--simpers-primary);
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 10px rgba(74, 112, 169, 0.3);
        }

        .btn-simpers {
            background-color: var(--simpers-primary);
            color: #fff;
            border: none;
        }

        .btn-simpers:hover {
            background-color: var(--simpers-hover);
            color: #fff;
        }

        .password-field {
            position: relative;
        }

        .password-field .form-control {
            padding-right: 46px;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: var(--simpers-muted);
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
            cursor: pointer;
        }

        .password-toggle:hover {
            color: var(--simpers-primary);
        }

        .password-meter {
            height: 6px;
            border-radius: 999px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .password-meter-bar {
            height: 100%;
            width: 0%;
            border-radius: 999px;
            transition: width .2s ease, background-color .2s ease;
            background-color: #dc3545;
        }

        .password-hint {
            font-size: .78rem;
            color: var(--simpers-muted);
        }

        .text-simpers {
            color: var(--simpers-primary) !important;
        }

        .field-help-btn {
            border: none;
            background: #eef4fb;
            color: var(--simpers-primary);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .82rem;
            transition: .2s ease;
        }

        .field-help-btn:hover {
            background: var(--simpers-primary);
            color: #fff;
        }

        .field-mini-note {
            font-size: .76rem;
            color: var(--simpers-muted);
            margin-top: 4px;
            margin-left: 2px;
        }

        .info-list {
            padding-left: 0;
            list-style: none;
            margin: 0;
        }

        .info-list li {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 10px;
            font-size: .9rem;
            color: #334155;
        }

        .info-list li:last-child {
            margin-bottom: 0;
        }

        .info-list i {
            color: var(--simpers-primary);
            margin-top: 3px;
        }

        .btn-loading .btn-text {
            opacity: .75;
        }

        @media (max-width: 576px) {
            body { align-items: flex-start; }
            .register-card { border-radius: 18px; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-8 col-md-6 col-lg-4">
                
                <div class="card register-card border-0 p-3 p-md-4">
                    <div class="card-body text-center">
                        
                        <div class="brand-logo">S</div>
                        <h5 class="fw-bold mb-1 text-dark">Aktivasi Akun</h5>
                        <p class="text-muted mb-4" style="font-size: 0.85rem;">
                            Masukkan NIP yang telah didaftarkan TU untuk membuat password baru.
                        </p>

                        <?php if (!empty($flash_register['message'])): ?>
                            <div class="alert alert-<?= htmlspecialchars($flash_register['type'] ?? 'danger', ENT_QUOTES, 'UTF-8') ?> text-start py-2 mb-3" style="font-size:.86rem;">
                                <i class="fa-solid <?= (($flash_register['type'] ?? '') === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation' ?> me-1"></i>
                                <?= htmlspecialchars($flash_register['message'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <form action="aksi_register.php" method="POST" id="formAktivasi" novalidate>
                            
                            <div class="form-floating mb-3 text-start">
                                <input type="text" class="form-control<?= is_invalid('nip') ?>" id="nip" name="nip" placeholder="Masukkan NIP" required autofocus autocomplete="off" value="<?= old_value('nip') ?>">
                                <label for="nip">NIP / ID Pengguna</label>
                                <?php if (field_error('nip')): ?>
                                    <div class="invalid-feedback text-start"><?= field_error('nip') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label text-start mb-0" for="telegram_id" style="font-size:.82rem;font-weight:600;color:#334155;">ID Telegram <span class="text-muted fw-normal">(Opsional)</span></label>
                                <button type="button" class="field-help-btn" data-bs-toggle="modal" data-bs-target="#modalTelegramInfo" aria-label="Bantuan ID Telegram">
                                    <i class="fa-solid fa-circle-question"></i>
                                </button>
                            </div>
                            <div class="form-floating mb-1 text-start">
                                <input type="text" class="form-control<?= is_invalid('telegram_id') ?>" id="telegram_id" name="telegram_id" placeholder="Contoh: 123456789" autocomplete="off" value="<?= old_value('telegram_id') ?>">
                                <label for="telegram_id">Contoh: 123456789</label>
                                <?php if (field_error('telegram_id')): ?>
                                    <div class="invalid-feedback text-start"><?= field_error('telegram_id') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="field-mini-note text-start mb-3">Boleh dikosongkan, dapat ditambahkan nanti melalui Profil.</div>

                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label text-start mb-0" for="password" style="font-size:.82rem;font-weight:600;color:#334155;">Password Baru</label>
                                <button type="button" class="field-help-btn" data-bs-toggle="modal" data-bs-target="#modalPasswordInfo" aria-label="Bantuan password">
                                    <i class="fa-solid fa-circle-question"></i>
                                </button>
                            </div>
                            <div class="form-floating mb-2 text-start password-field">
                                <input type="password" class="form-control<?= is_invalid('password') ?>" id="password" name="password" placeholder="Password Baru" required autocomplete="new-password" minlength="8">
                                <label for="password">Masukkan Password Baru</label>
                                <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Tampilkan password">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                                <?php if (field_error('password')): ?>
                                    <div class="invalid-feedback text-start"><?= field_error('password') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="password-meter mb-1" aria-hidden="true">
                                <div class="password-meter-bar" id="passwordStrengthBar"></div>
                            </div>
                            <div class="password-hint text-start mb-3" id="passwordStrengthText">
                                Minimal 8 karakter. Kombinasikan huruf besar, kecil, angka, atau simbol agar lebih kuat.
                            </div>

                            <div class="form-floating mb-2 text-start password-field">
                                <input type="password" class="form-control<?= is_invalid('konfirmasi_password') ?>" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi Password" required autocomplete="new-password">
                                <label for="konfirmasi_password">Ulangi Password</label>
                                <button type="button" class="password-toggle" data-toggle-password="konfirmasi_password" aria-label="Tampilkan konfirmasi password">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                                <?php if (field_error('konfirmasi_password')): ?>
                                    <div class="invalid-feedback text-start"><?= field_error('konfirmasi_password') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="text-start mb-4" id="confirmPasswordFeedback" style="font-size:.8rem;color:#64748b;min-height:18px;">
                                Ulangi password yang sama untuk menyelesaikan aktivasi.
                            </div>
                            
                            <button type="submit" name="register" class="btn btn-simpers w-100 py-2 mb-3 fw-bold js-submit-btn" style="border-radius: 8px;">
                                <span class="spinner-border spinner-border-sm me-2 d-none" aria-hidden="true"></span>
                                <span class="btn-text">Aktifkan Akun</span>
                            </button>
                        </form>
                        
                        <div class="mt-2 text-muted" style="font-size: 0.85rem;">
                            Sudah mengaktifkan akun? <a href="index.php" style="color: #4A70A9; font-weight: bold; text-decoration: none;">Login di sini</a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTelegramInfo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 bg-light">
                    <h6 class="modal-title fw-bold text-dark"><i class="fa-brands fa-telegram text-simpers me-2"></i> Informasi ID Telegram</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4 text-start">
                    <ul class="info-list">
                        <li><i class="fa-solid fa-check-circle"></i><span>ID Telegram bersifat opsional saat aktivasi akun.</span></li>
                        <li><i class="fa-solid fa-user-gear"></i><span>Jika dikosongkan, Anda tetap bisa menambahkannya nanti melalui menu Profil setelah berhasil login.</span></li>
                        <li><i class="fa-solid fa-robot"></i><span>Jika ingin mengetahui ID Telegram sekarang, buka <a href="https://t.me/userinfobot" target="_blank" style="color:#4A70A9;font-weight:700;text-decoration:none;">@userinfobot</a>.</span></li>
                    </ul>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-simpers px-4" data-bs-dismiss="modal">Mengerti</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPasswordInfo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 bg-light">
                    <h6 class="modal-title fw-bold text-dark"><i class="fa-solid fa-shield-halved text-simpers me-2"></i> Panduan Password</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4 text-start">
                    <ul class="info-list">
                        <li><i class="fa-solid fa-check-circle"></i><span>Gunakan minimal 8 karakter.</span></li>
                        <li><i class="fa-solid fa-key"></i><span>Disarankan memakai kombinasi huruf besar, huruf kecil, angka, atau simbol.</span></li>
                        <li><i class="fa-solid fa-ban"></i><span>Hindari memakai NIP, nama sendiri, tanggal lahir, atau kata yang mudah ditebak.</span></li>
                        <li><i class="fa-solid fa-eye-slash"></i><span>Gunakan ikon mata untuk memastikan password yang diketik sudah benar.</span></li>
                    </ul>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-simpers px-4" data-bs-dismiss="modal">Mengerti</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('[data-toggle-password]').forEach(button => {
            button.addEventListener('click', function () {
                const input = document.getElementById(this.dataset.togglePassword);
                const icon = this.querySelector('i');
                if (!input || !icon) return;

                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.classList.toggle('fa-eye', !isPassword);
                icon.classList.toggle('fa-eye-slash', isPassword);
            });
        });

        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('konfirmasi_password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        const strengthText = document.getElementById('passwordStrengthText');
        const confirmFeedback = document.getElementById('confirmPasswordFeedback');
        const formAktivasi = document.getElementById('formAktivasi');

        function calculateStrength(value) {
            let score = 0;
            if (value.length >= 8) score++;
            if (/[a-z]/.test(value)) score++;
            if (/[A-Z]/.test(value)) score++;
            if (/[0-9]/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value)) score++;
            return score;
        }

        function updateStrength() {
            const value = passwordInput.value;
            const score = calculateStrength(value);
            const widthMap = [0, 20, 40, 65, 82, 100];
            const colorMap = ['#dc3545', '#dc3545', '#f59e0b', '#f59e0b', '#16a34a', '#15803d'];
            const textMap = [
                'Minimal 8 karakter. Kombinasikan huruf besar, kecil, angka, atau simbol agar lebih kuat.',
                'Password sangat lemah.',
                'Password lemah. Tambahkan variasi karakter.',
                'Password cukup, tapi masih bisa diperkuat.',
                'Password kuat.',
                'Password sangat kuat.'
            ];

            strengthBar.style.width = widthMap[score] + '%';
            strengthBar.style.backgroundColor = colorMap[score];
            strengthText.textContent = textMap[score];
        }

        function validateConfirmation() {
            if (!confirmInput.value) {
                confirmInput.classList.remove('is-valid', 'is-invalid');
                confirmFeedback.textContent = 'Ulangi password yang sama untuk menyelesaikan aktivasi.';
                confirmFeedback.style.color = '#64748b';
                return true;
            }

            if (passwordInput.value === confirmInput.value) {
                confirmInput.classList.remove('is-invalid');
                confirmInput.classList.add('is-valid');
                confirmFeedback.textContent = 'Konfirmasi password sudah sesuai.';
                confirmFeedback.style.color = '#16a34a';
                return true;
            }

            confirmInput.classList.remove('is-valid');
            confirmInput.classList.add('is-invalid');
            confirmFeedback.textContent = 'Konfirmasi password belum sama.';
            confirmFeedback.style.color = '#dc3545';
            return false;
        }

        passwordInput.addEventListener('input', () => {
            updateStrength();
            validateConfirmation();
        });
        confirmInput.addEventListener('input', validateConfirmation);

        formAktivasi.addEventListener('submit', function (event) {
            const isConfirmValid = validateConfirmation();

            if (!this.checkValidity() || !isConfirmValid) {
                event.preventDefault();
                event.stopPropagation();
                this.classList.add('was-validated');
                return;
            }

            const button = this.querySelector('.js-submit-btn');
            const spinner = button.querySelector('.spinner-border');
            const text = button.querySelector('.btn-text');

            if (button && spinner && text) {
                spinner.classList.remove('d-none');
                text.textContent = 'Memproses aktivasi...';
                button.classList.add('btn-loading');
                button.disabled = true;
            }
        });

        updateStrength();
    </script>
</body>
</html>
