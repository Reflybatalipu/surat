<?php
session_start();
include '../config/koneksi.php';

// 🛡️ Cek Login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(['error' => 'Unauthorized']));
}

$user_id_sekarang = $_SESSION['user_id'];
$role_sekarang = $_SESSION['nama_role'];

// Ambil parameter limit & offset dari AJAX
$limit = 30; 
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : "";
$filter_jenis = isset($_GET['jenis']) ? $_GET['jenis'] : "Semua";

// 1. Query Dasar Surat Masuk
$sql_masuk = "SELECT id, nomor_surat, perihal, tanggal_surat AS tanggal, klasifikasi, lokasi_fisik, file_path, 'Surat Masuk' AS jenis, created_at 
              FROM surat_masuk WHERE status_workflow IN ('Selesai', 'Diarsipkan')";

// 2. Query Dasar Surat Keluar (Hanya yang Terkirim)
$sql_keluar = "SELECT id, nomor_surat, perihal, tanggal_keluar AS tanggal, sifat_surat, lokasi_fisik, file_path, 'Surat Keluar' AS jenis, created_at 
               FROM surat_keluar WHERE status_workflow = 'Terkirim'";

// --- Filter Pencarian Kata Kunci ---
if ($keyword != "") {
    $sql_masuk .= " AND (nomor_surat LIKE '%$keyword%' OR perihal LIKE '%$keyword%' OR ocr_text LIKE '%$keyword%')";
    $sql_keluar .= " AND (nomor_surat LIKE '%$keyword%' OR perihal LIKE '%$keyword%')";
}

// --- Filter Hak Akses Berdasarkan Role ---
if ($role_sekarang != 'Kepala_Sekolah' && $role_sekarang != 'Admin_TU') {
    $sql_keluar .= " AND draft_by = '$user_id_sekarang'";
    $sql_masuk .= " AND created_by = '$user_id_sekarang'"; 
}

// --- Penggabungan Tabel (UNION) dengan LIMIT & OFFSET global ---
$sql_final = "";
if ($filter_jenis == 'Surat Masuk') {
    $sql_final = $sql_masuk . " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
} elseif ($filter_jenis == 'Surat Keluar') {
    $sql_final = $sql_keluar . " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
} else {
    $sql_final = "($sql_masuk) UNION ALL ($sql_keluar) ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
}

$query_arsip = mysqli_query($koneksi, $sql_final);
$data_arsip = [];
$surat_masuk_ids = [];

if ($query_arsip) {
    while ($row = mysqli_fetch_array($query_arsip)) {
        $data_arsip[] = $row;
        if ($row['jenis'] == 'Surat Masuk') {
            $surat_masuk_ids[] = $row['id'];
        }
    }
}

// AMBIL DATA LAMPIRAN (HANYA UNTUK SURAT MASUK)
$lampiran_arsip = [];
if (!empty($surat_masuk_ids)) {
    $ids_str = implode(',', $surat_masuk_ids);
    $q_lamp = mysqli_query($koneksi, "SELECT * FROM lampiran_surat_masuk WHERE id_surat_masuk IN ($ids_str)");
    if ($q_lamp) {
        while ($lamp = mysqli_fetch_assoc($q_lamp)) {
            $lampiran_arsip[$lamp['id_surat_masuk']][] = $lamp;
        }
    }
}

$no = $offset + 1;
$html_table = "";
$html_card = "";
$html_modal = "";

foreach ($data_arsip as $data) {
    $warna_badge = ($data['jenis'] == 'Surat Masuk') ? 'bg-success' : 'bg-primary';
    $warna_icon = ($data['jenis'] == 'Surat Masuk') ? 'text-success' : 'text-primary';
    $bg_icon = ($data['jenis'] == 'Surat Masuk') ? 'bg-success' : 'bg-primary';
    $tgl = !empty($data['tanggal']) ? date('d/m/Y', strtotime($data['tanggal'])) : '-';
    $jenis_id = str_replace(' ', '', $data['jenis']);
    
    $lokasi_fisik_txt = $data['lokasi_fisik'] ? htmlspecialchars($data['lokasi_fisik']) : '<span class="fst-italic fw-normal">Belum di-set</span>';
    $lokasi_fisik_card = $data['lokasi_fisik'] ? htmlspecialchars($data['lokasi_fisik']) : '-';
    $nomor_surat_txt = $data['nomor_surat'] ? htmlspecialchars($data['nomor_surat']) : '-';
    $nomor_surat_card = $data['nomor_surat'] ? htmlspecialchars($data['nomor_surat']) : 'Tanpa Nomor';
    
    // 1. Render Baris Tabel
    $html_table .= '<tr style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalDetail' . $jenis_id . $data['id'] . '" title="Klik untuk melihat detail" class="hover-arsip">';
    $html_table .= '<td class="ps-3">' . $no++ . '</td>';
    $html_table .= '<td><span class="badge ' . $warna_badge . ' px-2 py-1"><i class="fa-solid fa-file-signature me-1"></i> ' . $data['jenis'] . '</span></td>';
    $html_table .= '<td>';
    $html_table .= '<div class="fw-bold text-dark font-monospace" style="font-size: 0.9rem;">' . $nomor_surat_txt . '</div>';
    $html_table .= '<div class="small text-muted"><i class="fa-regular fa-calendar me-1"></i> ' . $tgl . '</div>';
    $html_table .= '</td>';
    $html_table .= '<td>';
    $html_table .= '<div class="fw-bold text-truncate" style="max-width: 250px; font-size:0.95rem;">' . htmlspecialchars($data['perihal']) . '</div>';
    $html_table .= '<span class="badge bg-secondary mt-1" style="font-size: 0.7em;">' . htmlspecialchars($data['klasifikasi']) . '</span>';
    $html_table .= '</td>';
    $html_table .= '<td>';
    $html_table .= '<div class="small text-muted"><i class="fa-solid fa-folder-open me-1 text-warning"></i> <strong>' . $lokasi_fisik_txt . '</strong></div>';
    $html_table .= '</td>';
    $html_table .= '</tr>';

    // 2. Render Card Layout (Mobile)
    $html_card .= '<div class="card border-0 shadow-sm rounded-4 mb-3 hover-arsip" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalDetail' . $jenis_id . $data['id'] . '">';
    $html_card .= '<div class="card-body p-3">';
    $html_card .= '<div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">';
    $html_card .= '<span class="badge ' . $warna_badge . '" style="font-size: 0.7rem;"><i class="fa-solid fa-file-signature me-1"></i> ' . $data['jenis'] . '</span>';
    $html_card .= '<span class="text-muted small"><i class="fa-regular fa-calendar me-1"></i> ' . $tgl . '</span>';
    $html_card .= '</div>';
    $html_card .= '<div class="d-flex align-items-start mb-2">';
    $html_card .= '<div class="' . $bg_icon . ' bg-opacity-10 ' . $warna_icon . ' rounded-circle d-flex justify-content-center align-items-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">';
    $html_card .= '<i class="fa-solid ' . (($data['jenis'] == 'Surat Masuk') ? 'fa-inbox' : 'fa-paper-plane') . ' fs-5"></i>';
    $html_card .= '</div>';
    $html_card .= '<div class="flex-grow-1 overflow-hidden">';
    $html_card .= '<div class="fw-bold text-dark font-monospace text-truncate mb-1" style="font-size: 0.85rem;">' . $nomor_surat_card . '</div>';
    $html_card .= '<h6 class="mb-1 fw-bold text-dark text-truncate" style="font-size: 0.95rem;">' . htmlspecialchars($data['perihal']) . '</h6>';
    $html_card .= '<div class="text-muted small"><i class="fa-solid fa-folder-open me-1 text-warning"></i> Fisik: <strong>' . $lokasi_fisik_card . '</strong></div>';
    $html_card .= '</div>';
    $html_card .= '</div>';
    $html_card .= '</div>';
    $html_card .= '</div>';

    // 3. Render Modal Detail
    $html_modal .= '<div class="modal fade" id="modalDetail' . $jenis_id . $data['id'] . '" tabindex="-1" aria-hidden="true">';
    $html_modal .= '<div class="modal-dialog modal-lg">';
    $html_modal .= '<div class="modal-content text-start">';
    $html_modal .= '<div class="modal-header ' . (($data['jenis'] == 'Surat Masuk') ? 'bg-success text-white' : 'bg-primary text-white') . '">';
    $html_modal .= '<h5 class="modal-title fw-bold"><i class="fa-solid fa-file-lines me-2"></i> Detail ' . $data['jenis'] . '</h5>';
    $html_modal .= '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>';
    $html_modal .= '</div>';
    $html_modal .= '<div class="modal-body bg-light">';
    $html_modal .= '<div class="row g-3">';
    $html_modal .= '<div class="col-md-7">';
    $html_modal .= '<div class="card border-0 shadow-sm h-100">';
    $html_modal .= '<div class="card-body">';
    $html_modal .= '<h6 class="fw-bold text-muted border-bottom pb-2 mb-3">Informasi Utama</h6>';
    $html_modal .= '<table class="table table-borderless table-sm mb-0">';
    $html_modal .= '<tr><td width="35%" class="text-muted">Nomor Surat</td><td class="fw-bold">: ' . $nomor_surat_txt . '</td></tr>';
    $html_modal .= '<tr><td class="text-muted">Tanggal</td><td class="fw-bold">: ' . $tgl . '</td></tr>';
    $html_modal .= '<tr><td class="text-muted">Klasifikasi</td><td>: <span class="badge bg-secondary">' . htmlspecialchars($data['klasifikasi']) . '</span></td></tr>';
    $html_modal .= '<tr><td class="text-muted pb-0">Perihal</td><td class="fw-bold text-dark pb-0">: ' . htmlspecialchars($data['perihal']) . '</td></tr>';
    $html_modal .= '</table>';
    $html_modal .= '</div>';
    $html_modal .= '</div>';
    $html_modal .= '</div>';
    $html_modal .= '<div class="col-md-5">';
    $html_modal .= '<div class="card border-0 shadow-sm mb-3">';
    $html_modal .= '<div class="card-body text-center p-3">';
    $html_modal .= '<h6 class="fw-bold text-muted border-bottom pb-2 mb-3">Dokumen Utama</h6>';
    if (!empty($data['file_path'])) {
        $html_modal .= '<button type="button" onclick="bukaPreviewPDF(\'' . $data['file_path'] . '\')" class="btn btn-outline-info w-100 fw-bold mb-4" title="Lihat Dokumen"><i class="fa-solid fa-file-pdf me-1"></i> Buka Dokumen PDF</button>';
    } else {
        $html_modal .= '<span class="text-danger small"><i class="fa-solid fa-triangle-exclamation"></i> File tidak tersedia</span>';
    }
    $html_modal .= '</div>';
    $html_modal .= '</div>';
    
    if ($role_sekarang == 'Admin_TU') {
        $html_modal .= '<div class="card border-0 shadow-sm border-start border-warning border-4 mb-3">';
        $html_modal .= '<div class="card-body p-3">';
        $html_modal .= '<form action="" method="POST">';
        $html_modal .= '<input type="hidden" name="id_surat" value="' . $data['id'] . '">';
        $html_modal .= '<input type="hidden" name="jenis_surat" value="' . $data['jenis'] . '">';
        $html_modal .= '<label class="form-label fw-bold small text-muted"><i class="fa-solid fa-map-location-dot me-1"></i> Update Lokasi Fisik</label>';
        $html_modal .= '<div class="input-group input-group-sm">';
        $html_modal .= '<input type="text" class="form-control" name="lokasi_fisik" value="' . htmlspecialchars($data['lokasi_fisik']) . '" required>';
        $html_modal .= '<button type="submit" name="update_lokasi" class="btn btn-warning fw-bold text-dark" title="Simpan Lokasi"><i class="fa-solid fa-save"></i></button>';
        $html_modal .= '</div>';
        $html_modal .= '</form>';
        $html_modal .= '</div>';
        $html_modal .= '</div>';
    }
    $html_modal .= '</div>';

    if ($data['jenis'] == 'Surat Masuk') {
        $html_modal .= '<div class="col-md-12">';
        $html_modal .= '<div class="card border-0 shadow-sm">';
        $html_modal .= '<div class="card-body p-3">';
        $html_modal .= '<h6 class="fw-bold text-muted border-bottom pb-2 mb-3"><i class="fa-solid fa-paperclip me-2"></i>Lampiran Pendukung</h6>';
        $html_modal .= '<div class="row g-2">';
        $id_sm = $data['id'];
        if (isset($lampiran_arsip[$id_sm]) && count($lampiran_arsip[$id_sm]) > 0) {
            foreach ($lampiran_arsip[$id_sm] as $lamp) {
                $html_modal .= '<div class="col-md-6">';
                $html_modal .= '<a href="../uploads/surat_masuk/' . $lamp['path_file'] . '" target="_blank" class="btn btn-light border w-100 text-start text-dark d-flex align-items-center p-2 shadow-sm">';
                $html_modal .= '<i class="fa-solid fa-file text-secondary fs-5 me-3 ms-1"></i>';
                $html_modal .= '<div class="flex-grow-1 text-truncate small fw-bold" style="max-width: 85%;">' . htmlspecialchars($lamp['nama_file']) . '</div>';
                $html_modal .= '</a>';
                $html_modal .= '</div>';
            }
        } else {
            $html_modal .= '<div class="col-12 text-center py-2"><span class="text-muted small fst-italic">Tidak ada file lampiran tambahan.</span></div>';
        }
        $html_modal .= '</div>';
        $html_modal .= '</div>';
        $html_modal .= '</div>';
        $html_modal .= '</div>';
    }

    $html_modal .= '</div>';
    $html_modal .= '</div>';
    $html_modal .= '<div class="modal-footer border-top-0 bg-white">';
    $html_modal .= '<button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Tutup</button>';
    $html_modal .= '</div>';
    $html_modal .= '</div>';
    $html_modal .= '</div>';
    $html_modal .= '</div>';
}

if (ob_get_length()) ob_clean();
header('Content-Type: application/json');
echo json_encode([
    'table' => $html_table,
    'card'  => $html_card,
    'modal' => $html_modal
]);
exit;