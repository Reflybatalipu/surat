<?php
session_start();
include 'config/koneksi.php';

date_default_timezone_set('Asia/Makassar');

// 1. Fungsi Konversi Tanggal ke Indonesia
function tgl_indo($tanggal){
    $bulan = array (
        1 =>   'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $pecahkan = explode('-', $tanggal);
    return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

// 🛡️ KEAMANAN: Cek Login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    die("Akses ditolak.");
}

// 2. Ambil Data Kepala Sekolah dari Database
$query_kepsek = "
    SELECT u.nama_lengkap, u.nip 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE r.nama_role = 'Kepala_Sekolah' 
    LIMIT 1
";
$q_kepsek = mysqli_query($koneksi, $query_kepsek);
$data_kepsek = mysqli_fetch_assoc($q_kepsek);

$nama_kepsek = $data_kepsek['nama_lengkap'] ?? 'Nama Kepala Sekolah Belum Diatur';
$nip_kepsek  = $data_kepsek['nip'] ?? '-';

// 3. Tangkap Parameter Filter
$jenis_surat = $_GET['jenis'] ?? 'keluar';
$tgl_mulai   = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_akhir   = $_GET['tgl_akhir'] ?? date('Y-m-d');
$status      = $_GET['status'] ?? 'all';

// 4. Query Data Surat
$tabel = ($jenis_surat == 'masuk') ? 'surat_masuk' : 'surat_keluar';
$where_clause = "DATE(created_at) BETWEEN '$tgl_mulai' AND '$tgl_akhir' AND deleted_at IS NULL";
if ($jenis_surat == 'keluar' && $status !== 'all') {
    $where_clause .= " AND status_workflow = '$status'";
}
$query = mysqli_query($koneksi, "SELECT * FROM $tabel WHERE $where_clause ORDER BY created_at ASC");

// Persiapan Variabel Nama File
$jenis_teks = strtoupper(str_replace('_', ' ', $jenis_surat));
$nama_file = "Data_Surat_{$jenis_teks}_" . date('d-m-Y') . ".xls";

// Menentukan jumlah total kolom (untuk mengatur colspan KOP)
$jumlah_kolom = ($jenis_surat == 'keluar') ? 6 : 5;

// ========================================================
// HEADER EXCEL
// ========================================================
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=\"$nama_file\"");
header("Pragma: no-cache");
header("Expires: 0");
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        @page { size: A4 landscape; margin: 18mm; }
        body {
            font-family: "Times New Roman", serif;
            font-size: 12px;
            color: #000;
            margin: 0;
        }

        .tabel-data { border-collapse: collapse; }
        .tabel-data th, .tabel-data td { border: 1px solid #000; padding: 5px; }
        .header-tabel th { background-color: #D9D9D9; font-weight: bold; text-align: center; }
    </style>
</head>
<body>

    <table>
        <tr>
            <th colspan="<?= $jumlah_kolom; ?>" style="text-align: center; font-size: 14pt; font-weight: bold;">PEMERINTAH PROVINSI GORONTALO</th>
        </tr>
        <tr>
            <th colspan="<?= $jumlah_kolom; ?>" style="text-align: center; font-size: 14pt; font-weight: bold;">DINAS PENDIDIKAN DAN KEBUDAYAAN</th>
        </tr>
        <tr>
            <th colspan="<?= $jumlah_kolom; ?>" style="text-align: center; font-size: 16pt; font-weight: bold;">SMK NEGERI 4 GORONTALO</th>
        </tr>
        <tr>
            <td colspan="<?= $jumlah_kolom; ?>" style="text-align: center; font-size: 10pt;">Jl. Manado Kel. Pulubala, Kec. Kota Tengah Kota Gorontalo, Telp. (0435) 8717063</td>
        </tr>
        <tr>
            <td colspan="<?= $jumlah_kolom; ?>" style="text-align: center; font-size: 10pt; color: blue; border-bottom: 3px solid #000;">e-mail: smkn4gorontalo@gmail.com - website: http://smkn4gorontalo.sch.id</td>
        </tr>
        <tr><td colspan="<?= $jumlah_kolom; ?>"></td></tr> <tr>
            <th colspan="<?= $jumlah_kolom; ?>" style="text-align: center; font-size: 12pt; font-weight: bold; text-decoration: underline;">LAPORAN DATA SURAT <?= $jenis_teks ?></th>
        </tr>
        <tr>
            <td colspan="<?= $jumlah_kolom; ?>" style="text-align: center; font-size: 11pt;">Periode: <?= tgl_indo($tgl_mulai) ?> s.d <?= tgl_indo($tgl_akhir) ?></td>
        </tr>
        <tr><td colspan="<?= $jumlah_kolom; ?>"></td></tr> </table>

    <table class="tabel-data">
        <thead>
            <tr class="header-tabel">
                <th>No</th>
                <th>Tanggal</th>
                <th>Nomor Surat</th>
                <th><?= ($jenis_surat == 'keluar') ? 'Tujuan' : 'Asal Surat'; ?></th>
                <th>Perihal</th>
                <?php if ($jenis_surat == 'keluar') echo "<th>Status Workflow</th>"; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; 
            if (mysqli_num_rows($query) > 0) {
                while($row = mysqli_fetch_assoc($query)) { 
            ?>
            <tr>
                <td align="center"><?= $no++ ?></td>
                <td align="center"><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                <td style="mso-number-format:'\@';"><?= $row['nomor_surat'] ?></td>
                <td><?= ($jenis_surat == 'keluar') ? $row['tujuan'] : $row['pengirim']; ?></td>
                <td><?= $row['perihal'] ?></td>
                <?php if ($jenis_surat == 'keluar') echo "<td>{$row['status_workflow']}</td>"; ?>
            </tr>
            <?php 
                } 
            } else {
                echo "<tr><td colspan='$jumlah_kolom' align='center'>Tidak ada data pada periode ini</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <?php $kolom_kosong = $jumlah_kolom - 2; ?>
    <table>
        <tr><td colspan="<?= $jumlah_kolom; ?>"></td></tr>
        <tr><td colspan="<?= $jumlah_kolom; ?>"></td></tr>
        
        <tr>
            <td colspan="<?= $kolom_kosong; ?>"></td>
            <td colspan="2" align="center">Kota Tengah, <?= tgl_indo(date('Y-m-d')) ?></td>
        </tr>
        <tr>
            <td colspan="<?= $kolom_kosong; ?>"></td>
            <td colspan="2" align="center">Mengetahui,</td>
        </tr>
        <tr>
            <td colspan="<?= $kolom_kosong; ?>"></td>
            <td colspan="2" align="center">Kepala Sekolah</td>
        </tr>
        
        <tr><td colspan="<?= $jumlah_kolom; ?>" style="height: 50px;"></td></tr>
        
        <tr>
            <td colspan="<?= $kolom_kosong; ?>"></td>
            <td colspan="2" align="center"><b><u><?= $nama_kepsek ?></u></b></td>
        </tr>
        <tr>
            <td colspan="<?= $kolom_kosong; ?>"></td>
            <td colspan="2" align="center">NIP. <?= $nip_kepsek ?></td>
        </tr>
    </table>

</body>
</html>