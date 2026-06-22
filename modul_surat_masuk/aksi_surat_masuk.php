<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../config/koneksi.php';

// Jika kamu menggunakan fungsi_telegram dan catat_audit_log, aktifkan baris di bawah ini
// include '../fungsi_telegram.php';

// FUNGSI KOMPRESI GAMBAR (Hanya untuk JPG/PNG)
function kompresGambar($source, $destination, $quality) {
    $info = getimagesize($source);

    if ($info['mime'] == 'image/jpeg' || $info['mime'] == 'image/jpg') { 
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/png') { 
        $image = imagecreatefrompng($source);
    } else {
        return false; 
    }

    imagejpeg($image, $destination, $quality);
    imagedestroy($image);
    return true;
}

// ====================================================================
// 1. TAMBAH DATA SURAT (TERMASUK MULTI-UPLOAD & KAMERA)
// ====================================================================
if (isset($_POST['tambah'])) {
    
    // Tangkap Inputan Text dari Form
    $nomor_surat    = mysqli_real_escape_string($koneksi, $_POST['nomor_surat']);
    $pengirim       = mysqli_real_escape_string($koneksi, $_POST['pengirim']);
    $tanggal_surat  = $_POST['tanggal_surat'];
    $tanggal_terima = $_POST['tanggal_terima'];
    $klasifikasi    = isset($_POST['klasifikasi']) ? mysqli_real_escape_string($koneksi, $_POST['klasifikasi']) : 'Biasa'; 
    $unit_tujuan_id = $_POST['unit_tujuan_id'];
    $perihal        = mysqli_real_escape_string($koneksi, $_POST['perihal']);
    $created_by     = $_SESSION['user_id'];
    
    $ocr_text   = "Menunggu antrean Cloud OCR..."; 
    $status_ocr = 'processing';

    // ---------------------------------------------------------
    // A. PROSES UPLOAD SURAT UTAMA
    // ---------------------------------------------------------
    $file_path = "";
    $file_hash = "";

    $mode_utama = isset($_POST['surat_utama_mode']) ? $_POST['surat_utama_mode'] : 'file';

    if ($mode_utama === 'kamera' && !empty($_POST['surat_utama_base64'])) {
        // ── MODE KAMERA ──
        $pdf_data   = base64_decode($_POST['surat_utama_base64']);
        $file_path  = "SM_" . date('Ymd_His') . "_" . rand(100, 999) . ".pdf";
        $tujuan_file = "../uploads/surat_masuk/" . $file_path;

        if (file_put_contents($tujuan_file, $pdf_data) !== false) {
            $file_hash = md5_file($tujuan_file);
        } else {
            echo "<script>alert('Gagal menyimpan file PDF dari kamera!'); history.back();</script>";
            exit;
        }

    } elseif (isset($_FILES['file_path']['name']) && $_FILES['file_path']['name'] != '') {
        // ── MODE FILE ──
        $nama_file_asli = $_FILES['file_path']['name'];
        $tmp_file       = $_FILES['file_path']['tmp_name'];
        $ekstensi       = strtolower(pathinfo($nama_file_asli, PATHINFO_EXTENSION));

        $file_path   = "SM_" . date('Ymd_His') . "_" . rand(100, 999) . "." . $ekstensi;
        $tujuan_file = "../uploads/surat_masuk/" . $file_path;

        if (in_array($ekstensi, ['jpg', 'jpeg', 'png'])) {
            kompresGambar($tmp_file, $tujuan_file, 60);
        } else {
            move_uploaded_file($tmp_file, $tujuan_file);
        }

        $file_hash = md5_file($tujuan_file);
    }

    // ---------------------------------------------------------
    // B. SIMPAN KE DATABASE UTAMA
    // ---------------------------------------------------------
    $query_utama = mysqli_query($koneksi, "INSERT INTO surat_masuk 
        (nomor_surat, tanggal_surat, tanggal_terima, pengirim, perihal, klasifikasi, unit_tujuan_id, file_path, file_hash, ocr_text, status_ocr, status_workflow, created_by)
        VALUES 
        ('$nomor_surat', '$tanggal_surat', '$tanggal_terima', '$pengirim', '$perihal', '$klasifikasi', '$unit_tujuan_id', '$file_path', '$file_hash', '$ocr_text', '$status_ocr', 'Baru', '$created_by')");

    if ($query_utama) {
        $id_surat_baru = mysqli_insert_id($koneksi);
        
        // C. PROSES MULTI-UPLOAD LAMPIRAN
        $mode_lampiran = isset($_POST['lampiran_mode']) ? $_POST['lampiran_mode'] : 'file';
 
        if ($mode_lampiran === 'kamera' && !empty($_POST['lampiran_base64'])) {
            $pdf_data_lamp   = base64_decode($_POST['lampiran_base64']);
            $nama_baru_lamp  = "Lampiran_" . date('Ymd_His') . "_" . rand(1000, 9999) . ".pdf";
            $tujuan_lampiran = "../uploads/surat_masuk/" . $nama_baru_lamp;

            if (file_put_contents($tujuan_lampiran, $pdf_data_lamp) !== false) {
                mysqli_query($koneksi, "INSERT INTO lampiran_surat_masuk 
                    (id_surat_masuk, nama_file, path_file) 
                    VALUES ('$id_surat_baru', 'Lampiran_Scan_Kamera.pdf', '$nama_baru_lamp')");
            }

        } elseif (isset($_FILES['lampiran']['name']) && !empty($_FILES['lampiran']['name'][0])) {
            $jumlah_lampiran = count($_FILES['lampiran']['name']);

            for ($i = 0; $i < $jumlah_lampiran; $i++) {
                $nama_lampiran_asli = $_FILES['lampiran']['name'][$i];
                $tmp_lampiran       = $_FILES['lampiran']['tmp_name'][$i];

                if ($nama_lampiran_asli != "") {
                    $ekstensi_lampiran  = strtolower(pathinfo($nama_lampiran_asli, PATHINFO_EXTENSION));
                    $nama_baru_lampiran = "Lampiran_" . date('Ymd_His') . "_" . rand(1000, 9999) . "." . $ekstensi_lampiran;
                    $tujuan_lampiran    = "../uploads/surat_masuk/" . $nama_baru_lampiran;

                    if (in_array($ekstensi_lampiran, ['jpg', 'jpeg', 'png'])) {
                        move_uploaded_file($tmp_lampiran, $tujuan_lampiran);
                    } else {
                        move_uploaded_file($tmp_lampiran, $tujuan_lampiran);
                    }

                    mysqli_query($koneksi, "INSERT INTO lampiran_surat_masuk 
                        (id_surat_masuk, nama_file, path_file) 
                        VALUES ('$id_surat_baru', '$nama_lampiran_asli', '$nama_baru_lampiran')");
                }
            }
        }
        
        echo "<script>alert('Sukses! Surat dan Lampirannya berhasil diregistrasi.'); location.href='surat_masuk.php';</script>";
    } else {
        echo "<script>alert('Error Database: " . mysqli_error($koneksi) . "'); history.back();</script>";
    }
}

// ====================================================================
// 2. HAPUS DATA SURAT 
// ====================================================================
if (isset($_POST['hapus'])) {
    $id = mysqli_real_escape_string($koneksi, $_POST['id']);
    
    $q_lampiran = mysqli_query($koneksi, "SELECT path_file FROM lampiran_surat_masuk WHERE id_surat_masuk = '$id'");
    while($lamp = mysqli_fetch_array($q_lampiran)){
        $file_target = "../uploads/surat_masuk/" . $lamp['path_file'];
        if(file_exists($file_target)) { unlink($file_target); }
    }
    mysqli_query($koneksi, "DELETE FROM lampiran_surat_masuk WHERE id_surat_masuk = '$id'");

    $query = "UPDATE surat_masuk SET deleted_at = NOW() WHERE id = '$id'";
    if(mysqli_query($koneksi, $query)) {
        echo "<script>alert('Data surat dan seluruh lampirannya berhasil dihapus!'); location.href='surat_masuk.php';</script>";
    }
}

// ====================================================================
// 3. PROSES EDIT DATA SURAT 
// ====================================================================
if (isset($_POST['edit_surat'])) {
    $id_surat       = mysqli_real_escape_string($koneksi, $_POST['id_surat']);
    $nomor_surat    = mysqli_real_escape_string($koneksi, $_POST['nomor_surat']);
    $pengirim       = mysqli_real_escape_string($koneksi, $_POST['pengirim']);
    $tanggal_surat  = $_POST['tanggal_surat'];
    $tanggal_terima = $_POST['tanggal_terima'];
    $klasifikasi    = isset($_POST['klasifikasi']) ? mysqli_real_escape_string($koneksi, $_POST['klasifikasi']) : 'Biasa';
    $unit_tujuan_id = $_POST['unit_tujuan_id'];
    $perihal        = mysqli_real_escape_string($koneksi, $_POST['perihal']);
    $file_lama      = $_POST['file_lama'];
    
    $query_update = "UPDATE surat_masuk SET 
                     nomor_surat = '$nomor_surat', 
                     pengirim = '$pengirim', 
                     tanggal_surat = '$tanggal_surat', 
                     tanggal_terima = '$tanggal_terima', 
                     klasifikasi = '$klasifikasi',
                     unit_tujuan_id = '$unit_tujuan_id', 
                     perihal = '$perihal' ";

    if (isset($_FILES['file_path']['name']) && $_FILES['file_path']['name'] != '') {
        $nama_file_asli = $_FILES['file_path']['name'];
        $tmp_file       = $_FILES['file_path']['tmp_name'];
        $ekstensi       = strtolower(pathinfo($nama_file_asli, PATHINFO_EXTENSION));
        
        $file_path_baru = "SM_" . date('Ymd_His') . "_" . rand(100, 999) . "." . $ekstensi;
        $tujuan_file    = "../uploads/surat_masuk/" . $file_path_baru;
        
        if (move_uploaded_file($tmp_file, $tujuan_file)) {
            $file_hash = md5_file($tujuan_file);
            $query_update .= ", file_path = '$file_path_baru', file_hash = '$file_hash', status_ocr = 'processing', ocr_text = 'Menunggu antrean Cloud OCR (Update)...' ";
            
            if (file_exists("../uploads/surat_masuk/" . $file_lama) && $file_lama != "") {
                unlink("../uploads/surat_masuk/" . $file_lama);
            }
        }
    }
    
    $query_update .= " WHERE id = '$id_surat'";
    
    if (mysqli_query($koneksi, $query_update)) {
        echo "<script>alert('Data surat berhasil diperbarui!'); location.href='surat_masuk.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data!'); location.href='surat_masuk.php';</script>";
    }
}
?>
