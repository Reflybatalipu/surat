<?php
include 'config/koneksi.php'; // Pastikan path koneksi benar
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Bantuan IT - SIMPERS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .bg-gradient-dark { background: linear-gradient(45deg, #212529, #343a40); }
        .nav-pills .nav-link.active { background-color: #dc3545; } /* Warna merah khas SIMPERS */
        .nav-pills .nav-link { color: #6c757d; }
        .card { border-radius: 15px; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-dark"><i class="fa-solid fa-user-gear text-danger me-2"></i> Pusat Bantuan IT</h3>
        <p class="text-muted small">Mengalami kendala akses atau sistem? Silakan ikuti panduan mandiri di bawah ini.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            
            <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                <div class="card-header bg-dark text-white p-3 border-0">
                    <h6 class="mb-0"><i class="fa-solid fa-hand-holding-medical text-info me-2"></i> Solusi Cepat Kendala Sistem (Self-Help)</h6>
                </div>
                <div class="card-body">
                    <ul class="nav nav-pills nav-fill mb-3 bg-light p-1 rounded" id="pills-tab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active small fw-bold" id="tab-app" data-bs-toggle="pill" data-bs-target="#app" type="button"><i class="fa-solid fa-mobile-screen-button"></i> App</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link small fw-bold" id="tab-net" data-bs-toggle="pill" data-bs-target="#net" type="button"><i class="fa-solid fa-wifi"></i> Jaringan</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link small fw-bold" id="tab-doc" data-bs-toggle="pill" data-bs-target="#doc" type="button"><i class="fa-solid fa-file-pdf"></i> Dokumen</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link small fw-bold" id="tab-tech" data-bs-toggle="pill" data-bs-target="#tech" type="button"><i class="fa-solid fa-gears"></i> Teknis</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="app">
                            <div class="accordion accordion-flush" id="accApp">
                                <div class="accordion-item">
                                    <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-toggle="collapse" data-bs-target="#a1">1. Halaman Macet / Blank (Laptop)</button></h2>
                                    <div id="a1" class="accordion-collapse collapse" data-bs-parent="#accApp"><div class="accordion-body small text-muted">Tekan <strong>CTRL + F5</strong> untuk memuat ulang skrip sistem secara bersih.</div></div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-toggle="collapse" data-bs-target="#a2">2. Segarkan Halaman (Aplikasi Android)</button></h2>
                                    <div id="a2" class="accordion-collapse collapse" data-bs-parent="#accApp"><div class="accordion-body small text-muted">Sentuh layar lalu <strong>Tarik ke Bawah</strong> (Swipe to Refresh).</div></div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="net">
                            <div class="accordion accordion-flush" id="accNet">
                                <div class="accordion-item">
                                    <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-toggle="collapse" data-bs-target="#n1">4. Internet Aktif Tapi Tidak Bisa Akses</button></h2>
                                    <div id="n1" class="accordion-collapse collapse" data-bs-parent="#accNet"><div class="accordion-body small text-muted">Restart Wi-Fi atau Modem Anda selama 10 detik atau gunakan Data Seluler.</div></div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-toggle="collapse" data-bs-target="#n2">5. Sinyal Lemah (Mode Pesawat)</button></h2>
                                    <div id="n2" class="accordion-collapse collapse" data-bs-parent="#accNet"><div class="accordion-body small text-muted">Aktifkan <strong>Mode Pesawat</strong> 5 detik lalu matikan kembali untuk reset sinyal.</div></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-3 text-center small text-muted">
                            <i class="fa-solid fa-circle-info me-1"></i> Klik judul masalah untuk melihat solusi.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-danger text-white p-3 border-0">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-paper-plane me-2"></i> Masalah Belum Selesai? Kirim Tiket Laporan</h6>
                </div>
                <form action="simpan_pengaduan_luar.php" method="POST" enctype="multipart/form-data">
                    <div class="card-body p-4 bg-white">
                        <div class="alert alert-warning small border-0 shadow-sm mb-4">
                            <strong>Penting:</strong> Gunakan NIP Anda yang terdaftar agar sistem dapat mengenali akun Anda secara otomatis.
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small">NIP / ID Akun <span class="text-danger">*</span></label>
                                <input type="text" name="nip_manual" class="form-control border-danger shadow-sm" placeholder="Masukkan NIP Anda" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small">No. WhatsApp <span class="text-danger">*</span></label>
                                <input type="number" name="kontak_wa" class="form-control border-danger shadow-sm" placeholder="Contoh: 0812345xxx" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Subjek Masalah <span class="text-danger">*</span></label>
                            <input type="text" name="subjek" class="form-control border-danger shadow-sm" placeholder="Contoh: Lupa password atau Akun Terkunci" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Deskripsi Detail <span class="text-danger">*</span></label>
                            <textarea name="deskripsi" class="form-control border-danger shadow-sm" rows="4" placeholder="Jelaskan kendala Anda..." required></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small">Bukti Screenshot <span class="text-danger">*</span></label>
                            <input type="file" name="file_lampiran" class="form-control shadow-sm" accept="image/*" required>
                        </div>

                        <button type="submit" class="btn btn-danger w-100 fw-bold py-2 shadow">
                            <i class="fa-solid fa-paper-plane me-1"></i> KIRIM LAPORAN KE TIM IT
                        </button>
                    </div>
                </form>
            </div>

            <div class="text-center mt-4 mb-5">
                <a href="index.php" class="text-decoration-none text-muted small"><i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Halaman Login</a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>