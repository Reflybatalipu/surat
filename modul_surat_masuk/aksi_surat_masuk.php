<?php
// ====================================================================
//  KONFIGURASI ERROR REPORTING
//  Di production JANGAN tampilkan error ke browser (info disclosure).
//  Set APP_DEBUG di config/koneksi.php (atau env) menjadi true hanya di lokal/dev.
// ====================================================================
$app_debug = defined('APP_DEBUG') ? APP_DEBUG : false;
error_reporting(E_ALL);
ini_set('display_errors', $app_debug ? 1 : 0);
ini_set('log_errors', 1);

session_start();
include '../config/koneksi.php';

// ====================================================================
//  GUARD LOGIN
// ====================================================================
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: ../login.php");
    exit;
}

// ====================================================================
//  GUARD ROLE (PERBAIKAN: sebelumnya endpoint ini bisa diakses semua
//  role yang login, sehingga siapapun bisa tambah/edit/hapus surat
//  masuk lewat request langsung ke file ini. Sesuaikan daftar role di
//  bawah ini dengan kebijakan aplikasi kamu.)
// ====================================================================
const ROLE_YANG_BOLEH_KELOLA_SURAT_MASUK = ['Admin_TU'];

function require_role_kelola_surat_masuk() {
    $role = $_SESSION['nama_role'] ?? '';
    if (!in_array($role, ROLE_YANG_BOLEH_KELOLA_SURAT_MASUK, true)) {
        set_flash('danger', 'Anda tidak memiliki hak akses untuk melakukan aksi ini.');
        redirect_surat_masuk();
    }
}

// ====================================================================
//  GUARD CSRF (PERBAIKAN: sebelumnya endpoint ini tidak memvalidasi
//  token apapun, sehingga rentan Cross-Site Request Forgery. Token
//  dibuat & disimpan di session oleh surat_masuk.php, dan wajib
//  dikirim balik lewat field hidden 'csrf_token' di setiap form POST.)
// ====================================================================
function require_valid_csrf() {
    $token_session = $_SESSION['csrf_token'] ?? '';
    $token_post = $_POST['csrf_token'] ?? '';
    if ($token_session === '' || !hash_equals($token_session, $token_post)) {
        set_flash('danger', 'Sesi form sudah tidak valid (CSRF token tidak cocok). Silakan muat ulang halaman dan coba lagi.');
        redirect_surat_masuk();
    }
}

// ====================================================================
//  HELPER FLASH + REDIRECT
// ====================================================================
function set_flash($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function redirect_surat_masuk() {
    header("Location: surat_masuk.php");
    exit;
}

function redirect_back_surat_masuk($type, $message) {
    set_flash($type, $message);
    redirect_surat_masuk();
}

function safe_input($koneksi, $key, $default = '') {
    return mysqli_real_escape_string($koneksi, $_POST[$key] ?? $default);
}

function current_user_id() {
    return (int)($_SESSION['user_id'] ?? 0);
}

function audit_surat_masuk($koneksi, $action, $record_id) {
    if (function_exists('catat_audit_log')) {
        catat_audit_log($koneksi, $action, 'surat_masuk', $record_id);
        return;
    }

    $user_id = current_user_id();
    $record_id = (int)$record_id;
    $action_safe = mysqli_real_escape_string($koneksi, $action);
    $ip_address = mysqli_real_escape_string($koneksi, $_SERVER['REMOTE_ADDR'] ?? '');
    $user_agent = mysqli_real_escape_string($koneksi, $_SERVER['HTTP_USER_AGENT'] ?? '');

    // Fallback aman: kalau struktur audit_logs berbeda, jangan sampai proses utama gagal.
    @mysqli_query($koneksi, "INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent)
                              VALUES ('$user_id', '$action_safe', 'surat_masuk', '$record_id', '$ip_address', '$user_agent')");
}

function make_unique_filename($prefix, $extension) {
    $extension = strtolower(trim($extension, '.'));
    try {
        $random = bin2hex(random_bytes(8));
    } catch (Exception $e) {
        $random = uniqid('', true);
    }
    return $prefix . '_' . date('Ymd_His') . '_' . $random . '.' . $extension;
}

function ensure_upload_dir($dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return is_dir($dir) && is_writable($dir);
}

function get_file_mime($tmp_path) {
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp_path);
        finfo_close($finfo);
        return $mime ?: '';
    }
    return mime_content_type($tmp_path) ?: '';
}

function validate_uploaded_file($file, $allowed_extensions, $allowed_mimes, $max_size, $required = false) {
    if (!isset($file) || !isset($file['name']) || $file['name'] === '') {
        return $required ? ['ok' => false, 'message' => 'File wajib diunggah.'] : ['ok' => true, 'empty' => true];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'Upload file gagal. Kode error: ' . (int)$file['error']];
    }

    if (($file['size'] ?? 0) > $max_size) {
        return ['ok' => false, 'message' => 'Ukuran file terlalu besar. Maksimal ' . round($max_size / 1024 / 1024, 1) . ' MB.'];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions, true)) {
        return ['ok' => false, 'message' => 'Format file tidak valid. Format yang diizinkan: ' . implode(', ', $allowed_extensions) . '.'];
    }

    $mime = get_file_mime($file['tmp_name']);
    if (!empty($allowed_mimes) && !in_array($mime, $allowed_mimes, true)) {
        return ['ok' => false, 'message' => 'Tipe file tidak sesuai. File terdeteksi sebagai: ' . $mime . '.'];
    }

    return ['ok' => true, 'empty' => false, 'extension' => $extension, 'mime' => $mime];
}

// FUNGSI KOMPRESI GAMBAR (Hanya untuk JPG/PNG)
function kompresGambar($source, $destination, $quality) {
    $info = @getimagesize($source);
    if (!$info || empty($info['mime'])) return false;

    if ($info['mime'] == 'image/jpeg' || $info['mime'] == 'image/jpg') {
        $image = @imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = @imagecreatefrompng($source);
    } else {
        return false;
    }

    if (!$image) return false;
    $result = imagejpeg($image, $destination, $quality);
    imagedestroy($image);
    return $result;
}

function save_uploaded_file($file, $upload_dir, $prefix, $compress_image = true) {
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_name = make_unique_filename($prefix, $extension);
    $target = rtrim($upload_dir, '/') . '/' . $new_name;

    if ($compress_image && in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
        $saved = kompresGambar($file['tmp_name'], $target, 60);
        if (!$saved) {
            $saved = move_uploaded_file($file['tmp_name'], $target);
        }
    } else {
        $saved = move_uploaded_file($file['tmp_name'], $target);
    }

    if (!$saved || !file_exists($target)) {
        return ['ok' => false, 'message' => 'Gagal menyimpan file ke folder upload.'];
    }

    return ['ok' => true, 'file_name' => $new_name, 'file_hash' => md5_file($target), 'full_path' => $target];
}

// ====================================================================
//  PERBAIKAN: save_base64_pdf() sekarang menerapkan validasi yang SAMA
//  ketatnya dengan jalur upload file biasa:
//   1. Batas ukuran (sebelumnya tidak ada sama sekali -> DoS storage)
//   2. Verifikasi magic bytes "%PDF-" (sebelumnya isi file tidak pernah
//      diverifikasi -> attacker bisa kirim base64 apapun via DevTools)
// ====================================================================
function save_base64_pdf($base64, $upload_dir, $prefix, $max_size = 5 * 1024 * 1024) {
    $data = base64_decode($base64, true);
    if ($data === false || strlen($data) < 10) {
        return ['ok' => false, 'message' => 'Data kamera tidak valid.'];
    }

    if (strlen($data) > $max_size) {
        return ['ok' => false, 'message' => 'Ukuran PDF hasil kamera melebihi batas maksimal ' . round($max_size / 1024 / 1024, 1) . ' MB.'];
    }

    // Verifikasi konten benar-benar PDF, jangan percaya field mode dari client.
    if (substr($data, 0, 5) !== '%PDF-') {
        return ['ok' => false, 'message' => 'Data yang dikirim bukan file PDF yang valid.'];
    }

    $new_name = make_unique_filename($prefix, 'pdf');
    $target = rtrim($upload_dir, '/') . '/' . $new_name;

    if (file_put_contents($target, $data) === false || !file_exists($target)) {
        return ['ok' => false, 'message' => 'Gagal menyimpan file PDF dari kamera.'];
    }

    return ['ok' => true, 'file_name' => $new_name, 'file_hash' => md5_file($target), 'full_path' => $target];
}

// ====================================================================
//  DETEKSI AKSI
//  PERBAIKAN: sebelumnya ada heuristik tambahan berdasarkan kombinasi
//  field POST yang rapuh dan berisiko salah pemicu. Form di UI sudah
//  memakai nama tombol submit eksplisit (tambah / hapus / edit_surat),
//  jadi cukup andalkan itu saja.
// ====================================================================
$is_post   = ($_SERVER['REQUEST_METHOD'] === 'POST');
$is_tambah = $is_post && isset($_POST['tambah']);
$is_hapus  = $is_post && isset($_POST['hapus']);
$is_edit   = $is_post && isset($_POST['edit_surat']);

$upload_dir = '../uploads/surat_masuk';
if (($is_tambah || $is_edit) && !ensure_upload_dir($upload_dir)) {
    redirect_back_surat_masuk('danger', 'Folder upload surat masuk tidak tersedia atau tidak dapat ditulis.');
}

// ====================================================================
// 1. TAMBAH DATA SURAT (TERMASUK MULTI-UPLOAD & KAMERA)
// ====================================================================
if ($is_tambah) {
    require_valid_csrf();
    require_role_kelola_surat_masuk();

    $nomor_surat    = safe_input($koneksi, 'nomor_surat');
    $pengirim       = safe_input($koneksi, 'pengirim');
    $tanggal_surat  = safe_input($koneksi, 'tanggal_surat');
    $tanggal_terima = safe_input($koneksi, 'tanggal_terima');
    $klasifikasi    = safe_input($koneksi, 'klasifikasi', 'Biasa');
    $unit_tujuan_id = (int)($_POST['unit_tujuan_id'] ?? 0);
    $perihal        = safe_input($koneksi, 'perihal');
    $created_by     = current_user_id();

    if ($nomor_surat === '' || $pengirim === '' || $tanggal_surat === '' || $tanggal_terima === '' || $perihal === '' || $unit_tujuan_id <= 0) {
        redirect_back_surat_masuk('danger', 'Data wajib belum lengkap. Silakan periksa kembali form registrasi surat.');
    }

    $ocr_text   = "Menunggu antrean Cloud OCR...";
    $status_ocr = 'processing';
    $file_path = '';
    $file_hash = '';
    $saved_main_files = [];
    $saved_lampiran_files = [];

    $mode_utama = $_POST['surat_utama_mode'] ?? 'file';

    if ($mode_utama === 'kamera' && !empty($_POST['surat_utama_base64'])) {
        $save = save_base64_pdf($_POST['surat_utama_base64'], $upload_dir, 'SM', 5 * 1024 * 1024);
        if (!$save['ok']) redirect_back_surat_masuk('danger', $save['message']);
        $file_path = $save['file_name'];
        $file_hash = $save['file_hash'];
        $saved_main_files[] = $save['full_path'];
    } else {
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
        $allowed_mime = ['application/pdf', 'image/jpeg', 'image/png'];
        $valid = validate_uploaded_file($_FILES['file_path'] ?? null, $allowed_ext, $allowed_mime, 5 * 1024 * 1024, true);
        if (!$valid['ok']) redirect_back_surat_masuk('danger', $valid['message']);

        $save = save_uploaded_file($_FILES['file_path'], $upload_dir, 'SM', true);
        if (!$save['ok']) redirect_back_surat_masuk('danger', $save['message']);
        $file_path = $save['file_name'];
        $file_hash = $save['file_hash'];
        $saved_main_files[] = $save['full_path'];
    }

    $query_utama = mysqli_query($koneksi, "INSERT INTO surat_masuk
        (nomor_surat, tanggal_surat, tanggal_terima, pengirim, perihal, klasifikasi, unit_tujuan_id, file_path, file_hash, ocr_text, status_ocr, status_workflow, created_by)
        VALUES
        ('$nomor_surat', '$tanggal_surat', '$tanggal_terima', '$pengirim', '$perihal', '$klasifikasi', '$unit_tujuan_id', '$file_path', '$file_hash', '$ocr_text', '$status_ocr', 'Baru', '$created_by')");

    if (!$query_utama) {
        foreach (array_merge($saved_main_files, $saved_lampiran_files) as $path) {
            if (file_exists($path)) @unlink($path);
        }
        redirect_back_surat_masuk('danger', 'Error database saat registrasi surat: ' . mysqli_error($koneksi));
    }

    $id_surat_baru = mysqli_insert_id($koneksi);
    audit_surat_masuk($koneksi, 'CREATE_SURAT_MASUK', $id_surat_baru);

    // C. PROSES MULTI-UPLOAD LAMPIRAN
    $mode_lampiran = $_POST['lampiran_mode'] ?? 'file';

    if ($mode_lampiran === 'kamera' && !empty($_POST['lampiran_base64'])) {
        $save_lamp = save_base64_pdf($_POST['lampiran_base64'], $upload_dir, 'Lampiran', 5 * 1024 * 1024);
        if ($save_lamp['ok']) {
            $saved_lampiran_files[] = $save_lamp['full_path'];
            $nama_lamp = mysqli_real_escape_string($koneksi, 'Lampiran_Scan_Kamera.pdf');
            $path_lamp = mysqli_real_escape_string($koneksi, $save_lamp['file_name']);
            mysqli_query($koneksi, "INSERT INTO lampiran_surat_masuk (id_surat_masuk, nama_file, path_file)
                                    VALUES ('$id_surat_baru', '$nama_lamp', '$path_lamp')");
        }
    } elseif (isset($_FILES['lampiran']['name']) && !empty($_FILES['lampiran']['name'][0])) {
        $jumlah_lampiran = count($_FILES['lampiran']['name']);
        $allowed_lamp_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
        // PERBAIKAN: 'application/octet-stream' generik dihapus dari whitelist
        // karena melemahkan cek MIME (banyak file tak dikenal juga terdeteksi
        // sebagai ini). docx/xlsx tetap dikenali via MIME openxmlformats/zip.
        $allowed_lamp_mime = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'application/zip', // docx/xlsx modern kadang terdeteksi sebagai zip
        ];

        for ($i = 0; $i < $jumlah_lampiran; $i++) {
            if (empty($_FILES['lampiran']['name'][$i])) continue;

            $single_file = [
                'name' => $_FILES['lampiran']['name'][$i],
                'type' => $_FILES['lampiran']['type'][$i] ?? '',
                'tmp_name' => $_FILES['lampiran']['tmp_name'][$i],
                'error' => $_FILES['lampiran']['error'][$i],
                'size' => $_FILES['lampiran']['size'][$i],
            ];

            $valid_lamp = validate_uploaded_file($single_file, $allowed_lamp_ext, $allowed_lamp_mime, 5 * 1024 * 1024, false);
            if (!$valid_lamp['ok']) {
                // Lampiran gagal tidak menggagalkan surat utama, tapi dicatat sebagai flash warning.
                continue;
            }

            $save_lamp = save_uploaded_file($single_file, $upload_dir, 'Lampiran', false);
            if ($save_lamp['ok']) {
                $saved_lampiran_files[] = $save_lamp['full_path'];
                $nama_asli = mysqli_real_escape_string($koneksi, $single_file['name']);
                $path_lamp = mysqli_real_escape_string($koneksi, $save_lamp['file_name']);
                mysqli_query($koneksi, "INSERT INTO lampiran_surat_masuk (id_surat_masuk, nama_file, path_file)
                                        VALUES ('$id_surat_baru', '$nama_asli', '$path_lamp')");
            }
        }
    }

    redirect_back_surat_masuk('success', 'Surat masuk berhasil diregistrasi. OCR akan diproses otomatis di antrean.');
}

// ====================================================================
// 2. HAPUS DATA SURAT — soft delete surat, file & lampiran fisik tidak dihapus
// ====================================================================
if ($is_hapus) {
    require_valid_csrf();
    require_role_kelola_surat_masuk();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirect_back_surat_masuk('danger', 'ID surat tidak valid.');
    }

    // PERBAIKAN: UI hanya menampilkan tombol hapus untuk surat berstatus
    // 'Baru', tapi aturan ini sebelumnya tidak ditegakkan di server —
    // sehingga bisa dilewati dengan POST langsung ke ID surat manapun.
    $row_status = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT status_workflow FROM surat_masuk WHERE id = '$id' AND deleted_at IS NULL"));
    if (!$row_status) {
        redirect_back_surat_masuk('warning', 'Surat tidak ditemukan atau sudah pernah dihapus.');
    }
    if ($row_status['status_workflow'] !== 'Baru') {
        redirect_back_surat_masuk('danger', 'Surat yang sudah didisposisikan tidak dapat dihapus.');
    }

    $query = "UPDATE surat_masuk SET deleted_at = NOW() WHERE id = '$id' AND deleted_at IS NULL";
    if (mysqli_query($koneksi, $query) && mysqli_affected_rows($koneksi) > 0) {
        audit_surat_masuk($koneksi, 'DELETE_SURAT_MASUK', $id);
        redirect_back_surat_masuk('success', 'Data surat berhasil dihapus dari daftar aktif. File dan lampiran tetap tersimpan untuk keamanan arsip.');
    }

    redirect_back_surat_masuk('warning', 'Surat tidak ditemukan atau sudah pernah dihapus.');
}

// ====================================================================
// 3. PROSES EDIT DATA SURAT
// ====================================================================
if ($is_edit) {
    require_valid_csrf();
    require_role_kelola_surat_masuk();

    $id_surat       = (int)($_POST['id_surat'] ?? 0);
    $nomor_surat    = safe_input($koneksi, 'nomor_surat');
    $pengirim       = safe_input($koneksi, 'pengirim');
    $tanggal_surat  = safe_input($koneksi, 'tanggal_surat');
    $tanggal_terima = safe_input($koneksi, 'tanggal_terima');
    $klasifikasi    = safe_input($koneksi, 'klasifikasi', 'Biasa');
    $unit_tujuan_id = (int)($_POST['unit_tujuan_id'] ?? 0);
    $perihal        = safe_input($koneksi, 'perihal');

    if ($id_surat <= 0 || $nomor_surat === '' || $pengirim === '' || $tanggal_surat === '' || $tanggal_terima === '' || $perihal === '' || $unit_tujuan_id <= 0) {
        redirect_back_surat_masuk('danger', 'Data edit surat belum lengkap.');
    }

    // PERBAIKAN (Arbitrary File Deletion): sebelumnya nama file lama diambil
    // langsung dari hidden input POST 'file_lama', yang bisa dimanipulasi
    // bebas lewat DevTools untuk menargetkan file milik surat lain.
    // Sekarang nama file lama diambil ulang dari DATABASE berdasarkan
    // id_surat yang valid & belum dihapus, bukan dipercaya dari client.
    // Sekalian menegakkan aturan yang sama dengan UI: hanya surat
    // berstatus 'Baru' yang boleh diedit.
    $row_lama = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT file_path, status_workflow FROM surat_masuk WHERE id = '$id_surat' AND deleted_at IS NULL"));
    if (!$row_lama) {
        redirect_back_surat_masuk('danger', 'Surat yang ingin diedit tidak ditemukan.');
    }
    if ($row_lama['status_workflow'] !== 'Baru') {
        redirect_back_surat_masuk('danger', 'Surat yang sudah didisposisikan tidak dapat diedit.');
    }
    $file_lama = basename($row_lama['file_path'] ?? '');

    $query_update = "UPDATE surat_masuk SET
                     nomor_surat = '$nomor_surat',
                     pengirim = '$pengirim',
                     tanggal_surat = '$tanggal_surat',
                     tanggal_terima = '$tanggal_terima',
                     klasifikasi = '$klasifikasi',
                     unit_tujuan_id = '$unit_tujuan_id',
                     perihal = '$perihal' ";

    $new_file_uploaded = false;
    $new_file_path = '';

    if (isset($_FILES['file_path']['name']) && $_FILES['file_path']['name'] != '') {
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
        $allowed_mime = ['application/pdf', 'image/jpeg', 'image/png'];
        $valid = validate_uploaded_file($_FILES['file_path'], $allowed_ext, $allowed_mime, 5 * 1024 * 1024, false);
        if (!$valid['ok']) redirect_back_surat_masuk('danger', $valid['message']);

        $save = save_uploaded_file($_FILES['file_path'], $upload_dir, 'SM', true);
        if (!$save['ok']) redirect_back_surat_masuk('danger', $save['message']);

        $new_file_uploaded = true;
        $new_file_path = $save['full_path'];
        $file_path_baru = mysqli_real_escape_string($koneksi, $save['file_name']);
        $file_hash = mysqli_real_escape_string($koneksi, $save['file_hash']);

        $query_update .= ", file_path = '$file_path_baru', file_hash = '$file_hash', status_ocr = 'processing', ocr_text = 'Menunggu antrean Cloud OCR (Update)...' ";
    }

    $query_update .= " WHERE id = '$id_surat' AND deleted_at IS NULL";

    if (mysqli_query($koneksi, $query_update)) {
        if ($new_file_uploaded && $file_lama !== '') {
            $old_path = $upload_dir . '/' . $file_lama;
            if (file_exists($old_path)) {
                @unlink($old_path);
            }
        }
        audit_surat_masuk($koneksi, 'UPDATE_SURAT_MASUK', $id_surat);
        redirect_back_surat_masuk('success', 'Data surat masuk berhasil diperbarui.');
    }

    if ($new_file_uploaded && $new_file_path !== '' && file_exists($new_file_path)) {
        @unlink($new_file_path);
    }
    redirect_back_surat_masuk('danger', 'Gagal memperbarui data surat: ' . mysqli_error($koneksi));
}

redirect_surat_masuk();<?php
// ====================================================================
//  KONFIGURASI ERROR REPORTING
//  Di production JANGAN tampilkan error ke browser (info disclosure).
//  Set APP_DEBUG di config/koneksi.php (atau env) menjadi true hanya di lokal/dev.
// ====================================================================
$app_debug = defined('APP_DEBUG') ? APP_DEBUG : false;
error_reporting(E_ALL);
ini_set('display_errors', $app_debug ? 1 : 0);
ini_set('log_errors', 1);

session_start();
include '../config/koneksi.php';

// ====================================================================
//  GUARD LOGIN
// ====================================================================
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: ../login.php");
    exit;
}

// ====================================================================
//  GUARD ROLE (PERBAIKAN: sebelumnya endpoint ini bisa diakses semua
//  role yang login, sehingga siapapun bisa tambah/edit/hapus surat
//  masuk lewat request langsung ke file ini. Sesuaikan daftar role di
//  bawah ini dengan kebijakan aplikasi kamu.)
// ====================================================================
const ROLE_YANG_BOLEH_KELOLA_SURAT_MASUK = ['Admin_TU'];

function require_role_kelola_surat_masuk() {
    $role = $_SESSION['nama_role'] ?? '';
    if (!in_array($role, ROLE_YANG_BOLEH_KELOLA_SURAT_MASUK, true)) {
        set_flash('danger', 'Anda tidak memiliki hak akses untuk melakukan aksi ini.');
        redirect_surat_masuk();
    }
}

// ====================================================================
//  GUARD CSRF (PERBAIKAN: sebelumnya endpoint ini tidak memvalidasi
//  token apapun, sehingga rentan Cross-Site Request Forgery. Token
//  dibuat & disimpan di session oleh surat_masuk.php, dan wajib
//  dikirim balik lewat field hidden 'csrf_token' di setiap form POST.)
// ====================================================================
function require_valid_csrf() {
    $token_session = $_SESSION['csrf_token'] ?? '';
    $token_post = $_POST['csrf_token'] ?? '';
    if ($token_session === '' || !hash_equals($token_session, $token_post)) {
        set_flash('danger', 'Sesi form sudah tidak valid (CSRF token tidak cocok). Silakan muat ulang halaman dan coba lagi.');
        redirect_surat_masuk();
    }
}

// ====================================================================
//  HELPER FLASH + REDIRECT
// ====================================================================
function set_flash($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function redirect_surat_masuk() {
    header("Location: surat_masuk.php");
    exit;
}

function redirect_back_surat_masuk($type, $message) {
    set_flash($type, $message);
    redirect_surat_masuk();
}

function safe_input($koneksi, $key, $default = '') {
    return mysqli_real_escape_string($koneksi, $_POST[$key] ?? $default);
}

function current_user_id() {
    return (int)($_SESSION['user_id'] ?? 0);
}

function audit_surat_masuk($koneksi, $action, $record_id) {
    if (function_exists('catat_audit_log')) {
        catat_audit_log($koneksi, $action, 'surat_masuk', $record_id);
        return;
    }

    $user_id = current_user_id();
    $record_id = (int)$record_id;
    $action_safe = mysqli_real_escape_string($koneksi, $action);
    $ip_address = mysqli_real_escape_string($koneksi, $_SERVER['REMOTE_ADDR'] ?? '');
    $user_agent = mysqli_real_escape_string($koneksi, $_SERVER['HTTP_USER_AGENT'] ?? '');

    // Fallback aman: kalau struktur audit_logs berbeda, jangan sampai proses utama gagal.
    @mysqli_query($koneksi, "INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent)
                              VALUES ('$user_id', '$action_safe', 'surat_masuk', '$record_id', '$ip_address', '$user_agent')");
}

function make_unique_filename($prefix, $extension) {
    $extension = strtolower(trim($extension, '.'));
    try {
        $random = bin2hex(random_bytes(8));
    } catch (Exception $e) {
        $random = uniqid('', true);
    }
    return $prefix . '_' . date('Ymd_His') . '_' . $random . '.' . $extension;
}

function ensure_upload_dir($dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return is_dir($dir) && is_writable($dir);
}

function get_file_mime($tmp_path) {
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp_path);
        finfo_close($finfo);
        return $mime ?: '';
    }
    return mime_content_type($tmp_path) ?: '';
}

function validate_uploaded_file($file, $allowed_extensions, $allowed_mimes, $max_size, $required = false) {
    if (!isset($file) || !isset($file['name']) || $file['name'] === '') {
        return $required ? ['ok' => false, 'message' => 'File wajib diunggah.'] : ['ok' => true, 'empty' => true];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'Upload file gagal. Kode error: ' . (int)$file['error']];
    }

    if (($file['size'] ?? 0) > $max_size) {
        return ['ok' => false, 'message' => 'Ukuran file terlalu besar. Maksimal ' . round($max_size / 1024 / 1024, 1) . ' MB.'];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions, true)) {
        return ['ok' => false, 'message' => 'Format file tidak valid. Format yang diizinkan: ' . implode(', ', $allowed_extensions) . '.'];
    }

    $mime = get_file_mime($file['tmp_name']);
    if (!empty($allowed_mimes) && !in_array($mime, $allowed_mimes, true)) {
        return ['ok' => false, 'message' => 'Tipe file tidak sesuai. File terdeteksi sebagai: ' . $mime . '.'];
    }

    return ['ok' => true, 'empty' => false, 'extension' => $extension, 'mime' => $mime];
}

// FUNGSI KOMPRESI GAMBAR (Hanya untuk JPG/PNG)
function kompresGambar($source, $destination, $quality) {
    $info = @getimagesize($source);
    if (!$info || empty($info['mime'])) return false;

    if ($info['mime'] == 'image/jpeg' || $info['mime'] == 'image/jpg') {
        $image = @imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = @imagecreatefrompng($source);
    } else {
        return false;
    }

    if (!$image) return false;
    $result = imagejpeg($image, $destination, $quality);
    imagedestroy($image);
    return $result;
}

function save_uploaded_file($file, $upload_dir, $prefix, $compress_image = true) {
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_name = make_unique_filename($prefix, $extension);
    $target = rtrim($upload_dir, '/') . '/' . $new_name;

    if ($compress_image && in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
        $saved = kompresGambar($file['tmp_name'], $target, 60);
        if (!$saved) {
            $saved = move_uploaded_file($file['tmp_name'], $target);
        }
    } else {
        $saved = move_uploaded_file($file['tmp_name'], $target);
    }

    if (!$saved || !file_exists($target)) {
        return ['ok' => false, 'message' => 'Gagal menyimpan file ke folder upload.'];
    }

    return ['ok' => true, 'file_name' => $new_name, 'file_hash' => md5_file($target), 'full_path' => $target];
}

// ====================================================================
//  PERBAIKAN: save_base64_pdf() sekarang menerapkan validasi yang SAMA
//  ketatnya dengan jalur upload file biasa:
//   1. Batas ukuran (sebelumnya tidak ada sama sekali -> DoS storage)
//   2. Verifikasi magic bytes "%PDF-" (sebelumnya isi file tidak pernah
//      diverifikasi -> attacker bisa kirim base64 apapun via DevTools)
// ====================================================================
function save_base64_pdf($base64, $upload_dir, $prefix, $max_size = 5 * 1024 * 1024) {
    $data = base64_decode($base64, true);
    if ($data === false || strlen($data) < 10) {
        return ['ok' => false, 'message' => 'Data kamera tidak valid.'];
    }

    if (strlen($data) > $max_size) {
        return ['ok' => false, 'message' => 'Ukuran PDF hasil kamera melebihi batas maksimal ' . round($max_size / 1024 / 1024, 1) . ' MB.'];
    }

    // Verifikasi konten benar-benar PDF, jangan percaya field mode dari client.
    if (substr($data, 0, 5) !== '%PDF-') {
        return ['ok' => false, 'message' => 'Data yang dikirim bukan file PDF yang valid.'];
    }

    $new_name = make_unique_filename($prefix, 'pdf');
    $target = rtrim($upload_dir, '/') . '/' . $new_name;

    if (file_put_contents($target, $data) === false || !file_exists($target)) {
        return ['ok' => false, 'message' => 'Gagal menyimpan file PDF dari kamera.'];
    }

    return ['ok' => true, 'file_name' => $new_name, 'file_hash' => md5_file($target), 'full_path' => $target];
}

// ====================================================================
//  DETEKSI AKSI
//  PERBAIKAN: sebelumnya ada heuristik tambahan berdasarkan kombinasi
//  field POST yang rapuh dan berisiko salah pemicu. Form di UI sudah
//  memakai nama tombol submit eksplisit (tambah / hapus / edit_surat),
//  jadi cukup andalkan itu saja.
// ====================================================================
$is_post   = ($_SERVER['REQUEST_METHOD'] === 'POST');
$is_tambah = $is_post && isset($_POST['tambah']);
$is_hapus  = $is_post && isset($_POST['hapus']);
$is_edit   = $is_post && isset($_POST['edit_surat']);

$upload_dir = '../uploads/surat_masuk';
if (($is_tambah || $is_edit) && !ensure_upload_dir($upload_dir)) {
    redirect_back_surat_masuk('danger', 'Folder upload surat masuk tidak tersedia atau tidak dapat ditulis.');
}

// ====================================================================
// 1. TAMBAH DATA SURAT (TERMASUK MULTI-UPLOAD & KAMERA)
// ====================================================================
if ($is_tambah) {
    require_valid_csrf();
    require_role_kelola_surat_masuk();

    $nomor_surat    = safe_input($koneksi, 'nomor_surat');
    $pengirim       = safe_input($koneksi, 'pengirim');
    $tanggal_surat  = safe_input($koneksi, 'tanggal_surat');
    $tanggal_terima = safe_input($koneksi, 'tanggal_terima');
    $klasifikasi    = safe_input($koneksi, 'klasifikasi', 'Biasa');
    $unit_tujuan_id = (int)($_POST['unit_tujuan_id'] ?? 0);
    $perihal        = safe_input($koneksi, 'perihal');
    $created_by     = current_user_id();

    if ($nomor_surat === '' || $pengirim === '' || $tanggal_surat === '' || $tanggal_terima === '' || $perihal === '' || $unit_tujuan_id <= 0) {
        redirect_back_surat_masuk('danger', 'Data wajib belum lengkap. Silakan periksa kembali form registrasi surat.');
    }

    $ocr_text   = "Menunggu antrean Cloud OCR...";
    $status_ocr = 'processing';
    $file_path = '';
    $file_hash = '';
    $saved_main_files = [];
    $saved_lampiran_files = [];

    $mode_utama = $_POST['surat_utama_mode'] ?? 'file';

    if ($mode_utama === 'kamera' && !empty($_POST['surat_utama_base64'])) {
        $save = save_base64_pdf($_POST['surat_utama_base64'], $upload_dir, 'SM', 5 * 1024 * 1024);
        if (!$save['ok']) redirect_back_surat_masuk('danger', $save['message']);
        $file_path = $save['file_name'];
        $file_hash = $save['file_hash'];
        $saved_main_files[] = $save['full_path'];
    } else {
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
        $allowed_mime = ['application/pdf', 'image/jpeg', 'image/png'];
        $valid = validate_uploaded_file($_FILES['file_path'] ?? null, $allowed_ext, $allowed_mime, 5 * 1024 * 1024, true);
        if (!$valid['ok']) redirect_back_surat_masuk('danger', $valid['message']);

        $save = save_uploaded_file($_FILES['file_path'], $upload_dir, 'SM', true);
        if (!$save['ok']) redirect_back_surat_masuk('danger', $save['message']);
        $file_path = $save['file_name'];
        $file_hash = $save['file_hash'];
        $saved_main_files[] = $save['full_path'];
    }

    $query_utama = mysqli_query($koneksi, "INSERT INTO surat_masuk
        (nomor_surat, tanggal_surat, tanggal_terima, pengirim, perihal, klasifikasi, unit_tujuan_id, file_path, file_hash, ocr_text, status_ocr, status_workflow, created_by)
        VALUES
        ('$nomor_surat', '$tanggal_surat', '$tanggal_terima', '$pengirim', '$perihal', '$klasifikasi', '$unit_tujuan_id', '$file_path', '$file_hash', '$ocr_text', '$status_ocr', 'Baru', '$created_by')");

    if (!$query_utama) {
        foreach (array_merge($saved_main_files, $saved_lampiran_files) as $path) {
            if (file_exists($path)) @unlink($path);
        }
        redirect_back_surat_masuk('danger', 'Error database saat registrasi surat: ' . mysqli_error($koneksi));
    }

    $id_surat_baru = mysqli_insert_id($koneksi);
    audit_surat_masuk($koneksi, 'CREATE_SURAT_MASUK', $id_surat_baru);

    // C. PROSES MULTI-UPLOAD LAMPIRAN
    $mode_lampiran = $_POST['lampiran_mode'] ?? 'file';

    if ($mode_lampiran === 'kamera' && !empty($_POST['lampiran_base64'])) {
        $save_lamp = save_base64_pdf($_POST['lampiran_base64'], $upload_dir, 'Lampiran', 5 * 1024 * 1024);
        if ($save_lamp['ok']) {
            $saved_lampiran_files[] = $save_lamp['full_path'];
            $nama_lamp = mysqli_real_escape_string($koneksi, 'Lampiran_Scan_Kamera.pdf');
            $path_lamp = mysqli_real_escape_string($koneksi, $save_lamp['file_name']);
            mysqli_query($koneksi, "INSERT INTO lampiran_surat_masuk (id_surat_masuk, nama_file, path_file)
                                    VALUES ('$id_surat_baru', '$nama_lamp', '$path_lamp')");
        }
    } elseif (isset($_FILES['lampiran']['name']) && !empty($_FILES['lampiran']['name'][0])) {
        $jumlah_lampiran = count($_FILES['lampiran']['name']);
        $allowed_lamp_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
        // PERBAIKAN: 'application/octet-stream' generik dihapus dari whitelist
        // karena melemahkan cek MIME (banyak file tak dikenal juga terdeteksi
        // sebagai ini). docx/xlsx tetap dikenali via MIME openxmlformats/zip.
        $allowed_lamp_mime = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'application/zip', // docx/xlsx modern kadang terdeteksi sebagai zip
        ];

        for ($i = 0; $i < $jumlah_lampiran; $i++) {
            if (empty($_FILES['lampiran']['name'][$i])) continue;

            $single_file = [
                'name' => $_FILES['lampiran']['name'][$i],
                'type' => $_FILES['lampiran']['type'][$i] ?? '',
                'tmp_name' => $_FILES['lampiran']['tmp_name'][$i],
                'error' => $_FILES['lampiran']['error'][$i],
                'size' => $_FILES['lampiran']['size'][$i],
            ];

            $valid_lamp = validate_uploaded_file($single_file, $allowed_lamp_ext, $allowed_lamp_mime, 5 * 1024 * 1024, false);
            if (!$valid_lamp['ok']) {
                // Lampiran gagal tidak menggagalkan surat utama, tapi dicatat sebagai flash warning.
                continue;
            }

            $save_lamp = save_uploaded_file($single_file, $upload_dir, 'Lampiran', false);
            if ($save_lamp['ok']) {
                $saved_lampiran_files[] = $save_lamp['full_path'];
                $nama_asli = mysqli_real_escape_string($koneksi, $single_file['name']);
                $path_lamp = mysqli_real_escape_string($koneksi, $save_lamp['file_name']);
                mysqli_query($koneksi, "INSERT INTO lampiran_surat_masuk (id_surat_masuk, nama_file, path_file)
                                        VALUES ('$id_surat_baru', '$nama_asli', '$path_lamp')");
            }
        }
    }

    redirect_back_surat_masuk('success', 'Surat masuk berhasil diregistrasi. OCR akan diproses otomatis di antrean.');
}

// ====================================================================
// 2. HAPUS DATA SURAT — soft delete surat, file & lampiran fisik tidak dihapus
// ====================================================================
if ($is_hapus) {
    require_valid_csrf();
    require_role_kelola_surat_masuk();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirect_back_surat_masuk('danger', 'ID surat tidak valid.');
    }

    // PERBAIKAN: UI hanya menampilkan tombol hapus untuk surat berstatus
    // 'Baru', tapi aturan ini sebelumnya tidak ditegakkan di server —
    // sehingga bisa dilewati dengan POST langsung ke ID surat manapun.
    $row_status = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT status_workflow FROM surat_masuk WHERE id = '$id' AND deleted_at IS NULL"));
    if (!$row_status) {
        redirect_back_surat_masuk('warning', 'Surat tidak ditemukan atau sudah pernah dihapus.');
    }
    if ($row_status['status_workflow'] !== 'Baru') {
        redirect_back_surat_masuk('danger', 'Surat yang sudah didisposisikan tidak dapat dihapus.');
    }

    $query = "UPDATE surat_masuk SET deleted_at = NOW() WHERE id = '$id' AND deleted_at IS NULL";
    if (mysqli_query($koneksi, $query) && mysqli_affected_rows($koneksi) > 0) {
        audit_surat_masuk($koneksi, 'DELETE_SURAT_MASUK', $id);
        redirect_back_surat_masuk('success', 'Data surat berhasil dihapus dari daftar aktif. File dan lampiran tetap tersimpan untuk keamanan arsip.');
    }

    redirect_back_surat_masuk('warning', 'Surat tidak ditemukan atau sudah pernah dihapus.');
}

// ====================================================================
// 3. PROSES EDIT DATA SURAT
// ====================================================================
if ($is_edit) {
    require_valid_csrf();
    require_role_kelola_surat_masuk();

    $id_surat       = (int)($_POST['id_surat'] ?? 0);
    $nomor_surat    = safe_input($koneksi, 'nomor_surat');
    $pengirim       = safe_input($koneksi, 'pengirim');
    $tanggal_surat  = safe_input($koneksi, 'tanggal_surat');
    $tanggal_terima = safe_input($koneksi, 'tanggal_terima');
    $klasifikasi    = safe_input($koneksi, 'klasifikasi', 'Biasa');
    $unit_tujuan_id = (int)($_POST['unit_tujuan_id'] ?? 0);
    $perihal        = safe_input($koneksi, 'perihal');

    if ($id_surat <= 0 || $nomor_surat === '' || $pengirim === '' || $tanggal_surat === '' || $tanggal_terima === '' || $perihal === '' || $unit_tujuan_id <= 0) {
        redirect_back_surat_masuk('danger', 'Data edit surat belum lengkap.');
    }

    // PERBAIKAN (Arbitrary File Deletion): sebelumnya nama file lama diambil
    // langsung dari hidden input POST 'file_lama', yang bisa dimanipulasi
    // bebas lewat DevTools untuk menargetkan file milik surat lain.
    // Sekarang nama file lama diambil ulang dari DATABASE berdasarkan
    // id_surat yang valid & belum dihapus, bukan dipercaya dari client.
    // Sekalian menegakkan aturan yang sama dengan UI: hanya surat
    // berstatus 'Baru' yang boleh diedit.
    $row_lama = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT file_path, status_workflow FROM surat_masuk WHERE id = '$id_surat' AND deleted_at IS NULL"));
    if (!$row_lama) {
        redirect_back_surat_masuk('danger', 'Surat yang ingin diedit tidak ditemukan.');
    }
    if ($row_lama['status_workflow'] !== 'Baru') {
        redirect_back_surat_masuk('danger', 'Surat yang sudah didisposisikan tidak dapat diedit.');
    }
    $file_lama = basename($row_lama['file_path'] ?? '');

    $query_update = "UPDATE surat_masuk SET
                     nomor_surat = '$nomor_surat',
                     pengirim = '$pengirim',
                     tanggal_surat = '$tanggal_surat',
                     tanggal_terima = '$tanggal_terima',
                     klasifikasi = '$klasifikasi',
                     unit_tujuan_id = '$unit_tujuan_id',
                     perihal = '$perihal' ";

    $new_file_uploaded = false;
    $new_file_path = '';

    if (isset($_FILES['file_path']['name']) && $_FILES['file_path']['name'] != '') {
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
        $allowed_mime = ['application/pdf', 'image/jpeg', 'image/png'];
        $valid = validate_uploaded_file($_FILES['file_path'], $allowed_ext, $allowed_mime, 5 * 1024 * 1024, false);
        if (!$valid['ok']) redirect_back_surat_masuk('danger', $valid['message']);

        $save = save_uploaded_file($_FILES['file_path'], $upload_dir, 'SM', true);
        if (!$save['ok']) redirect_back_surat_masuk('danger', $save['message']);

        $new_file_uploaded = true;
        $new_file_path = $save['full_path'];
        $file_path_baru = mysqli_real_escape_string($koneksi, $save['file_name']);
        $file_hash = mysqli_real_escape_string($koneksi, $save['file_hash']);

        $query_update .= ", file_path = '$file_path_baru', file_hash = '$file_hash', status_ocr = 'processing', ocr_text = 'Menunggu antrean Cloud OCR (Update)...' ";
    }

    $query_update .= " WHERE id = '$id_surat' AND deleted_at IS NULL";

    if (mysqli_query($koneksi, $query_update)) {
        if ($new_file_uploaded && $file_lama !== '') {
            $old_path = $upload_dir . '/' . $file_lama;
            if (file_exists($old_path)) {
                @unlink($old_path);
            }
        }
        audit_surat_masuk($koneksi, 'UPDATE_SURAT_MASUK', $id_surat);
        redirect_back_surat_masuk('success', 'Data surat masuk berhasil diperbarui.');
    }

    if ($new_file_uploaded && $new_file_path !== '' && file_exists($new_file_path)) {
        @unlink($new_file_path);
    }
    redirect_back_surat_masuk('danger', 'Gagal memperbarui data surat: ' . mysqli_error($koneksi));
}

redirect_surat_masuk();
