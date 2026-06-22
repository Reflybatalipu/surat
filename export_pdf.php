<?php
session_start();
include 'config/koneksi.php';

// Panggil autoloader Composer
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

date_default_timezone_set('Asia/Makassar');

// 🛡️ KEAMANAN: Cek Login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

// 2. Ambil Data Kepala Sekolah dari Database
$q_kepsek = mysqli_query($koneksi, "SELECT u.nama_lengkap, u.nip FROM users u JOIN roles r ON u.role_id = r.id WHERE r.nama_role = 'Kepala_Sekolah' LIMIT 1");
$data_kepsek = mysqli_fetch_assoc($q_kepsek);

$nama_kepsek = $data_kepsek['nama_lengkap'] ?? 'Nama Kepala Sekolah Belum Diatur';
$nip_kepsek  = $data_kepsek['nip'] ?? '-';


// Tangkap Parameter Filter
$jenis_surat = $_GET['jenis'] ?? 'keluar';
$tgl_mulai   = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_akhir   = $_GET['tgl_akhir'] ?? date('Y-m-d');
$status      = $_GET['status'] ?? 'all';

// Kueri Database
$tabel = ($jenis_surat == 'masuk') ? 'surat_masuk' : 'surat_keluar';
$where_clause = "DATE(created_at) BETWEEN '$tgl_mulai' AND '$tgl_akhir' AND deleted_at IS NULL";

if ($jenis_surat == 'keluar' && $status !== 'all') {
    $where_clause .= " AND status_workflow = '$status'";
}

$query = mysqli_query($koneksi, "SELECT * FROM $tabel WHERE $where_clause ORDER BY created_at ASC");

// Variabel untuk cetak di PDF
$jenis_teks = strtoupper(str_replace('_', ' ', $jenis_surat));
$tgl_awal_teks = date('d/m/Y', strtotime($tgl_mulai));
$tgl_akhir_teks = date('d/m/Y', strtotime($tgl_akhir));

// ============================
// MULAI BUILD HTML (Output Buffer)
// ============================
ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Persuratan SIMPERS</title>
    <style>
        @page { size: A4 portrait; margin: 18mm; }
        body {
            font-family: "Times New Roman", serif;
            font-size: 12px;
            color: #000;
            margin: 0;
        }

        /* --- STYLE KOP SURAT --- */
        .kop-surat { width: 100%; border-bottom: 3px solid #000; padding-bottom: 6px; margin-bottom: 15px; }
        .kop-surat td { vertical-align: middle; }
        .kop-surat h1 { font-size: 14px; margin: 0; font-weight: 700; text-transform: uppercase; line-height: 1.2; }
        .kop-surat p { font-size: 10px; margin-top: 4px; margin-bottom: 0; color: blue; }
        
        /* --- STYLE TABEL --- */
        .judul-laporan { text-align: center; margin-bottom: 15px; }
        .judul-laporan h2 { margin: 0; font-size: 14px; text-decoration: underline; }
        
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #000; padding: 6px 8px; vertical-align: top; }
        .table th { background: #f0f0f0; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
        
        /* --- STYLE TANDA TANGAN --- */
        .ttd-container { margin-top: 40px; width: 100%; font-size: 12px; }
        .ttd-box { float: right; text-align: center; width: 250px; }
    </style>
</head>
<body>

    <table class="kop-surat">
        <tr>
            <td width="15%" align="left">
                <img src="<?= realpath(__DIR__ . '/assets/img/logo_left.jpg') ?>" width="70" alt="Logo Kiri">
            </td>
            <td width="70%" align="center">
                <h1>PEMERINTAH PROVINSI GORONTALO<br>
                    DINAS PENDIDIKAN DAN KEBUDAYAAN<br>
                    SEKOLAH MENENGAH KEJURUAN<br>
                    SMK NEGERI 4 GORONTALO</h1>
                Jl. Manado Kel. Pulubala, Kec. Kota Tengah Kota Gorontalo, Telp. (0435) 8717063
                <p>e-mail: smkn4gorontalo@gmail.com - website: http://smkn4gorontalo.sch.id</p>
            </td>
            <td width="15%" align="right">
                <img src="<?= realpath(__DIR__ . '/assets/img/logo_right.jpeg') ?>" width="80" alt="Logo Kanan">
            </td>
        </tr>
    </table>

    <div class="judul-laporan">
        <h2>LAPORAN ADMINISTRASI SURAT <?= $jenis_teks ?></h2>
        <div>Periode: <?= $tgl_awal_teks ?> s.d <?= $tgl_akhir_teks ?></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="15%">Tanggal</th>
                <th width="20%">Nomor Surat</th>
                <th width="25%"><?= ($jenis_surat == 'keluar') ? 'Tujuan' : 'Asal Surat'; ?></th>
                <th width="35%">Perihal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            if (mysqli_num_rows($query) > 0) {
                while ($row = mysqli_fetch_array($query)) {
                    $pihak = ($jenis_surat == 'keluar') ? ($row['tujuan'] ?? '-') : ($row['pengirim'] ?? '-');
                    
                    echo '<tr>
                        <td class="text-center">' . $no++ . '</td>
                        <td class="text-center">' . date('d/m/Y', strtotime($row['created_at'])) . '</td>
                        <td>' . htmlspecialchars($row['nomor_surat'] ?? '-') . '</td>
                        <td>' . htmlspecialchars($pihak) . '</td>
                        <td>' . htmlspecialchars($row['perihal'] ?? '-') . '</td>
                    </tr>';
                }
            } else {
                echo '<tr><td colspan="5" class="text-center">Tidak ada data pada periode ini.</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <div class="ttd-container">
        <div class="ttd-box">
            Batudaa, <?= date('d F Y') ?><br>
            Mengetahui,<br>
            Kepala Sekolah<br>
            <br><br><br><br>
           <u><b><?= $nama_kepsek ?></b></u><br>
            NIP. <?= $nip_kepsek ?>
        </div>
        <div style="clear: both;"></div>
    </div>

</body>
</html>
<?php
$html = ob_get_clean();

// ============================
// PROSES RENDER DOMPDF
// ============================
$options = new Options();
$options->set('isRemoteEnabled', true);
// chroot diatur ke root aplikasi (karena export_pdf.php ada di root)
$options->set('chroot', realpath(__DIR__)); 
$options->set('dpi', 96);
$options->set('defaultMediaType', 'print');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Menghasilkan nama file otomatis (Contoh: Rekap_Laporan_SURAT_MASUK_April_2026.pdf)
$nama_bulan = date('F', strtotime($tgl_mulai));
$tahun = date('Y', strtotime($tgl_mulai));
$nama_file = "Rekap_Laporan_SURAT_{$jenis_teks}_{$nama_bulan}_{$tahun}.pdf";

$dompdf->stream($nama_file, ["Attachment" => 1]);
exit;
?>