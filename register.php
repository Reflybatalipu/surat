<?php
session_start();
// Jika sudah login, tendang ke dashboard
if (isset($_SESSION['status_login']) && $_SESSION['status_login'] === true) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Akun - SIMPERS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            background-color: #ffffff;
        }
        .brand-logo {
            width: 70px;
            height: 70px;
            background-color: #4A70A9; /* Warna SIMPERS */
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
                            Masukkan NIP Anda yang telah didaftarkan oleh TU untuk membuat Password baru.
                        </p>

                        <form action="aksi_register.php" method="POST">
                            
                            <div class="form-floating mb-3 text-start">
                                <input type="text" class="form-control" id="nip" name="nip" placeholder="Masukkan NIP" required autofocus autocomplete="off">
                                <label for="nip">NIP / ID Pengguna</label>
                            </div>

                            <div class="form-floating mb-1 text-start">
                                <input type="text" class="form-control" id="telegram_id" name="telegram_id" placeholder="Contoh: 123456789" required autocomplete="off">
                                <label for="telegram_id">ID Telegram (Chat ID)</label>
                            </div>
                            <div class="text-start mb-3 ms-1" style="font-size: 0.8rem;">
                                💡 <a href="https://t.me/userinfobot" target="_blank" style="color: #4A70A9; text-decoration: none; font-weight: bold;">Klik di sini (@userinfobot)</a> untuk mengetahui ID Anda.
                            </div>
                            <div class="form-floating mb-3 text-start">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password Baru" required>
                                <label for="password">Password Baru</label>
                            </div>

                            <div class="form-floating mb-4 text-start">
                                <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi Password" required>
                                <label for="konfirmasi_password">Ulangi Password</label>
                            </div>
                            
                            <button type="submit" name="register" class="btn text-white w-100 py-2 mb-3 fw-bold" style="background-color: #4A70A9; border-radius: 8px;">
                                Aktifkan & Masuk
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>