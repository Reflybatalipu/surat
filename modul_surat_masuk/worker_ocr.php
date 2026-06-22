<?php
// BIKIN PHP KEBAL WALAUPUN TAB DITUTUP/DITINGGAL USER
ignore_user_abort(true);

// Memaksa PHP agar tidak mati sebelum 3 menit
ini_set('max_execution_time', 180); 
set_time_limit(180);
    error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sesuaikan path koneksi database (asumsi file ini ada di dalam folder 'aksi/')
include '../config/koneksi.php';

if (!isset($_GET['id'])) {
    exit('Error: ID Surat tidak dikirim.');
}

$id_surat = mysqli_real_escape_string($koneksi, $_GET['id']);

// 1. Ambil data nama file dari database
$query = mysqli_query($koneksi, "SELECT file_path FROM surat_masuk WHERE id = '$id_surat'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    exit('Error: Data surat tidak ditemukan di database.');
}

// 2. Tentukan Path File Fisik (Sangat Krusial di Hosting)
// Menggunakan dirname(__DIR__) untuk naik satu level dari folder saat ini secara dinamis
$file_name = $data['file_path'];
$file_path_full = dirname(__DIR__) . '/uploads/surat_masuk/' . $file_name;

if (!file_exists($file_path_full)) {
    mysqli_query($koneksi, "UPDATE surat_masuk SET status_ocr = 'failed', ocr_text = 'Gagal: File fisik tidak ditemukan di server.' WHERE id = '$id_surat'");
    exit('Error: File fisik tidak ada di direktori hosting.');
}

// 3. Siapkan API OCR.space
$api_key = 'K85854829388957'; 

// Gunakan CURLFile untuk upload file (Standar keamanan PHP modern di Hosting)
$cfile = new CURLFile($file_path_full);

$post_data = array(
    'apikey' => $api_key,
    'file' => $cfile,
    'language' => 'eng', // Gunakan 'eng' karena optimal untuk teks formal
    'isOverlayRequired' => 'false'
);

// 4. Eksekusi cURL ke Cloud
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.ocr.space/parse/image');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

// Bypass SSL: Mencegah error jika server hosting gratisan memiliki sertifikat SSL yang cacat/kadaluarsa
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
// Batasi waktu tunggu maksimal 60 detik agar server hosting tidak mematikan script paksa
curl_setopt($ch, CURLOPT_TIMEOUT, 120); 

$response = curl_exec($ch);
$curl_error = curl_error($ch); // Tangkap pesan error dari sistem jika gagal konek
curl_close($ch);

$response = curl_exec($ch);
$curl_error = curl_error($ch); 
curl_close($ch);

// ===============================================================
// SOLUSI TIMEOUT: Panggil ulang koneksi database setelah menunggu 
// OCR yang lama, karena server hosting sering memutus koneksi idle.
// ===============================================================
include '../config/koneksi.php';

// 5. Evaluasi Hasil dari cURL
if ($response === false) {
    // Jika gagal terhubung sama sekali (misal diblokir oleh firewall hosting)
    $pesan_gagal = 'Gagal Koneksi API: ' . $curl_error;
    mysqli_query($koneksi, "UPDATE surat_masuk SET status_ocr = 'failed', ocr_text = '$pesan_gagal' WHERE id = '$id_surat'");
    exit("Gagal: " . $pesan_gagal);
}

// 6. Proses Jawaban dari OCR.space
$result = json_decode($response, true);

if (isset($result['ParsedResults'][0]['ParsedText'])) {
    $ocr_text = trim($result['ParsedResults'][0]['ParsedText']);
    
    // Jika gambar sukses diproses tapi kosong (misal foto dinding)
    if (empty($ocr_text)) {
        $ocr_text = "[Tidak ada teks yang dapat dibaca pada dokumen ini]";
    }
    
    $ocr_text_safe = mysqli_real_escape_string($koneksi, $ocr_text);
    
    // Update database dengan hasil teks
    mysqli_query($koneksi, "UPDATE surat_masuk SET ocr_text = '$ocr_text_safe', status_ocr = 'completed' WHERE id = '$id_surat'");
    echo "Sukses: OCR berhasil diproses.";
} else {
    // Tangani jika OCR.space menolak file (misal file terlalu besar atau format salah)
    $error_msg = isset($result['ErrorMessage'][0]) ? $result['ErrorMessage'][0] : 'Server API tidak mengembalikan teks.';
    mysqli_query($koneksi, "UPDATE surat_masuk SET status_ocr = 'failed', ocr_text = 'Error API: $error_msg' WHERE id = '$id_surat'");
    echo "Gagal: " . $error_msg;
}
?>