<?php
// Tangkap kode error dari server. Jika tidak ada, atur default ke 404.
$status = isset($_SERVER['REDIRECT_STATUS']) ? $_SERVER['REDIRECT_STATUS'] : 404;

// Jika user mengakses error.php secara langsung tanpa trigger server
if (isset($_GET['code'])) {
    $status = $_GET['code'];
}

// Kamus Pesan Error
$error_codes = [
    400 => [
        'title' => '400 - Bad Request',
        'icon' => 'fa-solid fa-circle-exclamation text-warning',
        'message' => 'Maaf, permintaan yang dikirimkan oleh browser Anda tidak dapat dipahami oleh server kami.'
    ],
    401 => [
        'title' => '401 - Unauthorized',
        'icon' => 'fa-solid fa-lock text-danger',
        'message' => 'Akses ditolak. Kredensial Anda tidak valid atau Anda harus login terlebih dahulu.'
    ],
    403 => [
        'title' => '403 - Forbidden',
        'icon' => 'fa-solid fa-hand-paper text-danger',
        'message' => 'Akses terlarang! Anda tidak memiliki izin (hak akses) untuk membuka direktori atau halaman ini.'
    ],
    404 => [
        'title' => '404 - Not Found',
        'icon' => 'fa-solid fa-magnifying-glass-location text-primary',
        'message' => 'Waduh! Halaman atau berkas yang Anda cari tidak ditemukan, mungkin sudah dihapus atau dipindahkan.'
    ],
    500 => [
        'title' => '500 - Internal Server Error',
        'icon' => 'fa-solid fa-server text-danger',
        'message' => 'Telah terjadi kesalahan pada sistem server kami. Silakan hubungi Administrator IT.'
    ],
    503 => [
        'title' => '503 - Service Unavailable',
        'icon' => 'fa-solid fa-person-digging text-secondary',
        'message' => 'Mohon maaf, server saat ini tidak dapat menangani permintaan karena sedang dalam perbaikan atau kelebihan beban.'
    ]
];

// Ambil data sesuai kode, jika kode aneh/tidak terdaftar, anggap 404
$error_data = isset($error_codes[$status]) ? $error_codes[$status] : $error_codes[404];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $error_data['title'] ?> | SIMPERS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            max-width: 500px;
            text-align: center;
            padding: 40px 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .error-title {
            font-weight: 800;
            color: #343a40;
            margin-bottom: 15px;
        }
        .error-message {
            color: #6c757d;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>

    <div class="error-card">
        <div class="error-icon">
            <i class="<?= $error_data['icon'] ?>"></i>
        </div>
        <h2 class="error-title"><?= $error_data['title'] ?></h2>
        <p class="error-message"><?= $error_data['message'] ?></p>
        
        <button onclick="history.back()" class="btn btn-outline-secondary me-2">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali
        </button>
        <a href="dashboard.php" class="btn btn-primary">
            <i class="fa-solid fa-house me-1"></i> Beranda
        </a>
    </div>

</body>
</html>