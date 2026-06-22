<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
$id_user = $_SESSION['user_id'];

// ========================================================
// AMBIL DATA PENGADUAN & SIMPAN KE ARRAY
// ========================================================
$query = mysqli_query($koneksi, "SELECT * FROM pengaduan WHERE id_user = '$id_user' ORDER BY waktu_lapor DESC");
$data_pengaduan = [];
while ($row = mysqli_fetch_array($query)) {
    $data_pengaduan[] = $row;
}

include '../layouts/header.php'; 
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="fa-solid fa-headset"></i> Pusat Pengaduan Saya</h4>
        <p class="text-muted small mb-0">Sistem Bermasalah? Jangan Khawatir, Kami Siap Mengawal Solusinya. Lapor Tanpa Cemas, Kami Selesaikan Sesuai Standar Layanan.</p>
    </div>
</div>
<div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
    <div class="card-header bg-dark text-white p-3 border-0">
        <h6 class="mb-0"><i class="fa-solid fa-hand-holding-medical text-info me-2"></i> Pusat Bantuan Mandiri (Self-Help)</h6>
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
                        <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-target="#a2" data-bs-toggle="collapse">2. Segarkan Halaman (Aplikasi Android)</button></h2>
                        <div id="a2" class="accordion-collapse collapse" data-bs-parent="#accApp"><div class="accordion-body small text-muted">Sentuh layar lalu <strong>Tarik ke Bawah</strong> sampai muncul ikon putar (Swipe to Refresh).</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-target="#a3" data-bs-toggle="collapse">3. Tampilan Menu Berantakan</button></h2>
                        <div id="a3" class="accordion-collapse collapse" data-bs-parent="#accApp"><div class="accordion-body small text-muted">Nonaktifkan fitur "Desktop Site" jika di HP, atau kembalikan Zoom browser ke 100%.</div></div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="net">
                <div class="accordion accordion-flush" id="accNet">
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-target="#n1" data-bs-toggle="collapse">4. Internet Aktif Tapi Tidak Bisa Akses SIMPERS</button></h2>
                        <div id="n1" class="accordion-collapse collapse" data-bs-parent="#accNet"><div class="accordion-body small text-muted">Coba akses situs lain (misal Google). Jika situs lain bisa namun SIMPERS tidak, kemungkinan cache DNS Anda bermasalah. <strong>Restart Wi-Fi atau Modem</strong> Anda selama 10 detik.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-target="#n2" data-bs-toggle="collapse">5. Sinyal Lemah di Dalam Ruangan (Mobile)</button></h2>
                        <div id="n2" class="accordion-collapse collapse" data-bs-parent="#accNet"><div class="accordion-body small text-muted">Gunakan prosedur "Mode Pesawat": Aktifkan <strong>Mode Pesawat</strong> selama 5 detik, lalu matikan kembali. Ini memaksa HP mencari tower sinyal terkuat.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-target="#n3" data-bs-toggle="collapse">6. Masalah Firewall / Blokir Jaringan Kantor</button></h2>
                        <div id="n3" class="accordion-collapse collapse" data-bs-parent="#accNet"><div class="accordion-body small text-muted">Jika menggunakan Wi-Fi kantor/sekolah dan gagal akses, coba beralih ke <strong>Data Seluler</strong> sementara untuk memastikan tidak ada blokir pada jaringan Wi-Fi tersebut.</div></div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="doc">
                <div class="accordion accordion-flush" id="accDoc">
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-target="#d1" data-bs-toggle="collapse">7. PDF Tidak Muncul / Loading Lama</button></h2>
                        <div id="d1" class="accordion-collapse collapse" data-bs-parent="#accDoc"><div class="accordion-body small text-muted">Gunakan fitur <strong>"Buka Fullscreen"</strong> (tombol Hijau) untuk membuka dokumen langsung tanpa viewer sistem.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-target="#d2" data-bs-toggle="collapse">8. QR Code TTE Tidak Terbaca</button></h2>
                        <div id="d2" class="accordion-collapse collapse" data-bs-parent="#accDoc"><div class="accordion-body small text-muted">Lakukan <strong>Zoom In</strong> pada layar laptop agar QR terlihat besar dan jelas sebelum discan oleh HP.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-target="#d3" data-bs-toggle="collapse">9. Gagal Mengunggah PDF Revisi</button></h2>
                        <div id="d3" class="accordion-collapse collapse" data-bs-parent="#accDoc"><div class="accordion-body small text-muted">Pastikan ukuran file maksimal <strong>2MB</strong> dan nama file tidak mengandung karakter aneh (#, %, @).</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-target="#d4" data-bs-toggle="collapse">10. Gagal Ambil Foto Lampiran (HP)</button></h2>
                        <div id="d4" class="accordion-collapse collapse" data-bs-parent="#accDoc"><div class="accordion-body small text-muted">Jangan ambil foto langsung dari aplikasi. <strong>Foto dulu lewat kamera HP</strong>, simpan, baru pilih file dari galeri.</div></div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tech">
                <div class="accordion accordion-flush" id="accTech">
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-target="#t1" data-bs-toggle="collapse">11. Sistem Logout Otomatis</button></h2>
                        <div id="t1" class="accordion-collapse collapse" data-bs-parent="#accTech"><div class="accordion-body small text-muted">Terjadi karena perpindahan jaringan. Login kembali dan centang <strong>"Ingat Saya"</strong>.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-target="#t2" data-bs-toggle="collapse">12. Gagal Login (User/Pass Benar)</button></h2>
                        <div id="t2" class="accordion-collapse collapse" data-bs-parent="#accTech"><div class="accordion-body small text-muted">Cek jam di HP/Laptop. Jika waktu tidak sinkron dengan internet, proses login akan ditolak sistem keamanan.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed small fw-bold" data-bs-target="#t3" data-bs-toggle="collapse">13. Notifikasi Telegram Tidak Masuk</button></h2>
                        <div id="t3" class="accordion-collapse collapse" data-bs-parent="#accTech"><div class="accordion-body small text-muted">Ketik <strong>/start</strong> di Bot Telegram SIMPERS untuk mematikan mode 'tidur' pada sambungan akun Anda.</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-light text-center border-0 py-3">
        <span class="small text-muted d-block mb-2">Semua langkah di atas gagal? Baru kemudian:</span>
        <button type="button" class="btn btn-danger btn-sm px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPengaduan">
            <i class="fa-solid fa-circle-plus me-1"></i> Buat Laporan Tiket Resmi
        </button>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="fa-solid fa-clock-rotate-left text-danger me-2"></i> Riwayat Pengaduan Saya</h4>
        <p class="text-muted small mb-0">Pantau status penanganan kendala Anda sesuai standar SLA.</p>
    </div>
    <button type="button" class="btn btn-danger shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambahPengaduan">
        <i class="fa-solid fa-circle-plus me-1"></i> Buat Laporan Baru
    </button>
</div>
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0 p-md-4">
        
        <?php if (empty($data_pengaduan)): ?>
            <div class='text-center py-5 text-muted'>
                <i class='fa-solid fa-headset fs-1 d-block mb-3 text-light'></i> 
                Belum ada riwayat pengaduan.
            </div>
        <?php else: ?>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="15%" class="ps-3">No. Tiket</th>
                            <th width="20%">Tanggal Lapor</th>
                            <th width="30%">Subjek Masalah</th>
                            <th width="10%" class="text-center">Severity</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_pengaduan as $row): 
                            $status_badge = match($row['status']) {
                                'Open' => 'bg-secondary',
                                'In Progress' => 'bg-primary',
                                'Resolved' => 'bg-success',
                                'Closed' => 'bg-dark',
                                default => 'bg-light text-dark'
                            };

                            $sev_badge = match($row['level_gangguan']) {
                                'S1' => 'bg-danger',
                                'S2' => 'bg-warning text-dark',
                                'S3' => 'bg-info text-white',
                                'S4' => 'bg-light text-dark border',
                                default => 'bg-secondary'
                            };
                        ?>
                        <tr>
                            <td class="ps-3"><strong class="font-monospace text-primary">#<?= $row['no_tiket']; ?></strong></td>
                            <td class="small text-muted"><i class="fa-regular fa-clock me-1"></i> <?= date('d M Y, H:i', strtotime($row['waktu_lapor'])); ?> WIB</td>
                            <td class="fw-bold text-dark" style="font-size: 0.95rem;"><?= $row['subjek']; ?></td>
                            <td class="text-center"><span class="badge <?= $sev_badge; ?> px-2 shadow-sm"><?= $row['level_gangguan']; ?></span></td>
                            <td class="text-center"><span class="badge <?= $status_badge; ?>"><?= $row['status']; ?></span></td>
                            <td class="text-center">
                                <a href="detail_tiket_user.php?id=<?= $row['id']; ?>" class="btn btn-outline-dark btn-sm fw-bold">
                                    <i class="fa-solid fa-eye me-1"></i> Detail
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-block d-md-none bg-light p-3" style="max-height: 75vh; overflow-y: auto;">
                <?php foreach ($data_pengaduan as $row): 
                    $status_badge = match($row['status']) {
                        'Open' => 'bg-secondary',
                        'In Progress' => 'bg-primary',
                        'Resolved' => 'bg-success',
                        'Closed' => 'bg-dark',
                        default => 'bg-light text-dark'
                    };

                    $sev_badge = match($row['level_gangguan']) {
                        'S1' => 'bg-danger',
                        'S2' => 'bg-warning text-dark',
                        'S3' => 'bg-info text-white',
                        'S4' => 'bg-light text-dark border',
                        default => 'bg-secondary'
                    };

                    // Border kiri mengikuti level prioritas
                    $border_color = match($row['level_gangguan']) {
                        'S1' => 'border-danger',
                        'S2' => 'border-warning',
                        'S3' => 'border-info',
                        'S4' => 'border-secondary',
                        default => 'border-light'
                    };
                ?>
                <div class="card border-0 shadow-sm rounded-4 mb-3 border-start border-4 <?= $border_color; ?>">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <span class="fw-bold text-primary font-monospace">#<?= $row['no_tiket']; ?></span>
                            <span class="badge <?= $status_badge; ?>"><?= $row['status']; ?></span>
                        </div>
                        
                        <div class="mb-2">
                            <h6 class="mb-1 fw-bold text-dark"><?= $row['subjek']; ?></h6>
                            <div class="small text-muted mb-2">
                                <i class="fa-regular fa-clock me-1"></i> <?= date('d M Y, H:i', strtotime($row['waktu_lapor'])); ?> WIB
                            </div>
                            <span class="badge <?= $sev_badge; ?> shadow-sm">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Level: <?= $row['level_gangguan']; ?>
                            </span>
                        </div>

                        <div class="mt-2 pt-2 border-top text-end">
                            <a href="detail_tiket_user.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-dark fw-bold w-100">
                                <i class="fa-solid fa-eye me-1"></i> Lihat Detail Progress
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalTambahPengaduan" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content text-start">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="modalLabel"><i class="fa-solid fa-headset me-2"></i> Form Pengaduan Layanan TI</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="simpan_pengaduan.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body bg-light">
                    
                    <div class="alert alert-info small mb-4 shadow-sm border-info">
                        <i class="fa-solid fa-circle-info me-1"></i> <strong>Informasi SLA:</strong> Laporan akan direspon pada jam kerja (Senin-Jumat, 07.00 - 15.30 WIB).
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Subjek Masalah <span class="text-danger">*</span></label>
                        <input type="text" name="subjek" class="form-control border-danger" placeholder="Contoh: Akun E-Arsip tidak bisa login" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Klasifikasi Gangguan (Severity Level) <span class="text-danger">*</span></label>
                        <select name="level_gangguan" class="form-select border-danger" required>
                            <option value="">-- Pilih Level Kendala --</option>
                            <option value="S1">S1 - Kritis (Layanan mati total / Down)</option>
                            <option value="S2">S2 - Tinggi (Fitur utama error / Gagal TTE)</option>
                            <option value="S3">S3 - Sedang (Gangguan fitur pendukung / Lambat)</option>
                            <option value="S4">S4 - Rendah (Bantuan edukasi / Tanya cara pakai)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi Detail <span class="text-danger">*</span></label>
                        <textarea name="deskripsi" class="form-control border-danger" rows="4" placeholder="Ceritakan kronologi kendalanya secara lengkap..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Bukti / Screenshot Error <span class="text-danger">*</span></label>
                        <input type="file" name="file_lampiran" class="form-control" accept="image/*" required>
                        <small class="text-muted"><i class="fa-solid fa-camera me-1"></i> Wajib melampirkan bukti agar Tim IT bisa segera memproses.</small>
                    </div>

                </div>
                <div class="modal-footer bg-white border-top-0">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold">
                        <i class="fa-solid fa-paper-plane me-1"></i> Kirim Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>