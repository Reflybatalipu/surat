<?php
session_start();
include '../config/koneksi.php';

// 🛡️ Cek Login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: ../index.php");
    exit;
}

// Tangkap Tab Aktif dari URL (Default: sistem)
$tab_aktif = isset($_GET['tab']) ? $_GET['tab'] : 'sistem';

// 1. Ambil data Helpdesk
$q_helpdesk = mysqli_query($koneksi, "SELECT nilai_pengaturan FROM pengaturan WHERE nama_pengaturan = 'helpdesk_telegram_id'");
$d_helpdesk = mysqli_fetch_assoc($q_helpdesk);
$telegram_it = $d_helpdesk ? $d_helpdesk['nilai_pengaturan'] : '';

// 2. Ambil data Tabel Lainnya
$q_klasifikasi = mysqli_query($koneksi, "SELECT * FROM klasifikasi_surat ORDER BY kode ASC");
$q_unit = mysqli_query($koneksi, "SELECT * FROM unit_kerja ORDER BY nama_unit ASC");
$q_unit_pengolah = mysqli_query($koneksi, "SELECT * FROM unit_pengolah ORDER BY id ASC");

// Include Header (Sudah mencakup <!DOCTYPE html>, <head>, dan pembuka body/layout)
include '../layouts/header.php';
?>

<style>
    .nav-pills-custom .nav-link { color: #495057; border-radius: 8px; transition: 0.3s; }
    .nav-pills-custom .nav-link.active { background-color: #0d6efd; color: white; box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2); }
    .card-setting { border: none; border-radius: 12px; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
</style>

<div class="container-fluid mt-2 mb-5">
    <h3 class="mb-4 text-secondary"><i class="fas fa-cogs me-2"></i> Pengaturan Sistem</h3>

    <?php if(isset($_SESSION['pesan'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['pesan']; unset($_SESSION['pesan']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="nav nav-pills nav-pills-custom flex-column flex-md-row mb-4 bg-white shadow-sm p-2 rounded" id="v-pills-tab" role="tablist" aria-orientation="horizontal">
        <button class="nav-link <?= $tab_aktif == 'sistem' ? 'active' : '' ?> mb-2 mb-md-0 me-md-2 text-start text-md-center fw-bold" id="v-pills-sistem-tab" data-bs-toggle="pill" data-bs-target="#v-pills-sistem" type="button" role="tab">
            <i class="fas fa-desktop fa-fw me-1"></i> Sistem & IT
        </button>
        <button class="nav-link <?= $tab_aktif == 'klasifikasi' ? 'active' : '' ?> mb-2 mb-md-0 me-md-2 text-start text-md-center fw-bold" id="v-pills-klasifikasi-tab" data-bs-toggle="pill" data-bs-target="#v-pills-klasifikasi" type="button" role="tab">
            <i class="fas fa-folder-open fa-fw me-1"></i> Klasifikasi Surat
        </button>
        <button class="nav-link <?= $tab_aktif == 'pengolah' ? 'active' : '' ?> mb-2 mb-md-0 me-md-2 text-start text-md-center fw-bold" id="v-pills-pengolah-tab" data-bs-toggle="pill" data-bs-target="#v-pills-pengolah" type="button" role="tab">
            <i class="fas fa-layer-group fa-fw me-1"></i> Unit Pengolah
        </button>
        <button class="nav-link <?= $tab_aktif == 'unit' ? 'active' : '' ?> mb-2 mb-md-0 text-start text-md-center fw-bold" id="v-pills-unit-tab" data-bs-toggle="pill" data-bs-target="#v-pills-unit" type="button" role="tab">
            <i class="fas fa-users fa-fw me-1"></i> Unit Kerja
        </button>
    </div>

    <div class="tab-content" id="v-pills-tabContent">
        
        <div class="tab-pane fade <?= $tab_aktif == 'sistem' ? 'show active' : '' ?>" id="v-pills-sistem" role="tabpanel">
            <div class="card card-setting">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4 border-bottom pb-2">Pengaturan Helpdesk IT</h5>
                    <form action="aksi_setting.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">ID Telegram Staf IT</label>
                            <input type="text" name="telegram_it" class="form-control" placeholder="Contoh: 123456789" required value="<?= htmlspecialchars($telegram_it) ?>">
                            <div class="form-text text-primary mt-2">
                                <i class="fa-brands fa-telegram"></i> <b>Cara mendapatkan ID:</b> Minta staf IT membuka Telegram, cari bot <b>@userinfobot</b>, klik Start. Copy angka "Id" yang muncul dan paste di sini.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih Akun Staf IT / Helpdesk</label>
                            <select name="user_id_it" class="form-select" required>
                                <option value="">-- Pilih Guru / Staf --</option>
                                <?php
                                $q_cek = mysqli_query($koneksi, "SELECT nilai_pengaturan FROM pengaturan WHERE nama_pengaturan = 'helpdesk_user_id'");
                                $data_aktif = mysqli_fetch_assoc($q_cek);
                                $it_aktif = $data_aktif ? $data_aktif['nilai_pengaturan'] : '';

                                $query_users = mysqli_query($koneksi, "SELECT id, nama_lengkap FROM users");
                                while ($user = mysqli_fetch_assoc($query_users)) {
                                    $selected = ($user['id'] == $it_aktif) ? 'selected' : '';
                                    echo "<option value='{$user['id']}' {$selected}>{$user['nama_lengkap']}</option>";
                                }
                                ?>
                            </select>
                            <small class="text-muted"><i class="fa-solid fa-info-circle"></i> Akun yang dipilih akan otomatis memiliki menu "Dashboard IT" di layarnya.</small>
                        </div>
                        <button type="submit" name="simpan_helpdesk" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>Simpan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="tab-pane fade <?= $tab_aktif == 'klasifikasi' ? 'show active' : '' ?>" id="v-pills-klasifikasi" role="tabpanel">
            <div class="card card-setting">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Klasifikasi / Index Surat</h5>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKlas"><i class="fas fa-plus me-1"></i> Tambah</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%" class="text-center">No</th>
                                    <th width="15%" class="text-center">Kode</th>
                                    <th>Keterangan</th>
                                    <th width="15%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; while($d_klas = mysqli_fetch_array($q_klasifikasi)) : ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="text-center"><span class="badge bg-secondary px-2 py-1"><?= htmlspecialchars($d_klas['kode']) ?></span></td>
                                    <td><?= htmlspecialchars($d_klas['keterangan']) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-warning btn-sm text-white" data-bs-toggle="modal" data-bs-target="#modalEditKlas<?= $d_klas['id'] ?>"><i class="fas fa-edit"></i></button>
                                        <a href="aksi_setting.php?hapus_klas=<?= $d_klas['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus klasifikasi ini?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <div class="modal fade" id="modalEditKlas<?= $d_klas['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="aksi_setting.php" method="POST">
                                                <div class="modal-header bg-warning text-dark">
                                                    <h5 class="modal-title">Edit Klasifikasi</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <input type="hidden" name="id" value="<?= $d_klas['id'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Kode Klasifikasi</label>
                                                        <input type="text" class="form-control" name="kode" value="<?= htmlspecialchars($d_klas['kode']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Keterangan</label>
                                                        <input type="text" class="form-control" name="keterangan" value="<?= htmlspecialchars($d_klas['keterangan']) ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" name="edit_klasifikasi" class="btn btn-warning text-white">Update Data</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade <?= $tab_aktif == 'pengolah' ? 'show active' : '' ?>" id="v-pills-pengolah" role="tabpanel">
            <div class="card card-setting">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Unit Pengolah / Pembuat Surat</h5>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPengolah"><i class="fas fa-plus me-1"></i> Tambah</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%" class="text-center">No</th>
                                    <th width="15%" class="text-center">Kode</th>
                                    <th>Nama Unit / Deskripsi</th>
                                    <th width="15%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $np=1; while($d_pengolah = mysqli_fetch_array($q_unit_pengolah)) : ?>
                                <tr>
                                    <td class="text-center"><?= $np++ ?></td>
                                    <td class="text-center"><span class="badge bg-primary px-2 py-1"><?= htmlspecialchars($d_pengolah['kode_unit']) ?></span></td>
                                    <td><?= htmlspecialchars($d_pengolah['nama_unit']) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-warning btn-sm text-white" data-bs-toggle="modal" data-bs-target="#modalEditPengolah<?= $d_pengolah['id'] ?>"><i class="fas fa-edit"></i></button>
                                        <a href="aksi_setting.php?hapus_pengolah=<?= $d_pengolah['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus unit pengolah ini?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <div class="modal fade" id="modalEditPengolah<?= $d_pengolah['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="aksi_setting.php" method="POST">
                                                <div class="modal-header bg-warning text-dark">
                                                    <h5 class="modal-title">Edit Unit Pengolah</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <input type="hidden" name="id" value="<?= $d_pengolah['id'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Kode (Misal: UMUM, KUR)</label>
                                                        <input type="text" class="form-control" name="kode_unit" value="<?= htmlspecialchars($d_pengolah['kode_unit']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Unit / Deskripsi</label>
                                                        <input type="text" class="form-control" name="nama_unit" value="<?= htmlspecialchars($d_pengolah['nama_unit']) ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" name="edit_pengolah" class="btn btn-warning text-white">Update Data</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade <?= $tab_aktif == 'unit' ? 'show active' : '' ?>" id="v-pills-unit" role="tabpanel">
            <div class="card card-setting">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Unit Kerja / Jabatan (Internal)</h5>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahUnit"><i class="fas fa-plus me-1"></i> Tambah</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="10%" class="text-center">No</th>
                                    <th>Nama Unit Kerja</th>
                                    <th width="20%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $nu=1; while($d_unt = mysqli_fetch_array($q_unit)) : ?>
                                <tr>
                                    <td class="text-center"><?= $nu++ ?></td>
                                    <td><?= htmlspecialchars($d_unt['nama_unit']) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-warning btn-sm text-white" data-bs-toggle="modal" data-bs-target="#modalEditUnit<?= $d_unt['id'] ?>"><i class="fas fa-edit"></i></button>
                                        <a href="aksi_setting.php?hapus_unit=<?= $d_unt['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus unit ini?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <div class="modal fade" id="modalEditUnit<?= $d_unt['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="aksi_setting.php" method="POST">
                                                <div class="modal-header bg-warning text-dark">
                                                    <h5 class="modal-title">Edit Unit Kerja</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <input type="hidden" name="id" value="<?= $d_unt['id'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Unit</label>
                                                        <input type="text" class="form-control" name="nama_unit" value="<?= htmlspecialchars($d_unt['nama_unit']) ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" name="edit_unit" class="btn btn-warning text-white">Update Data</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="modalTambahKlas" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="aksi_setting.php" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Tambah Klasifikasi Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode (Misal: 000, 400)</label>
                        <input type="text" class="form-control" name="kode" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan Index</label>
                        <input type="text" class="form-control" name="keterangan" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah_klasifikasi" class="btn btn-success">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahPengolah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="aksi_setting.php" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Tambah Unit Pengolah</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode (Misal: UMUM, KUR)</label>
                        <input type="text" class="form-control" name="kode_unit" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama / Deskripsi</label>
                        <input type="text" class="form-control" name="nama_unit" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah_pengolah" class="btn btn-success">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahUnit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="aksi_setting.php" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Tambah Unit Kerja</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Unit Baru</label>
                        <input type="text" class="form-control" name="nama_unit" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah_unit" class="btn btn-success">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>