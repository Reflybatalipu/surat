<?php
session_start();
include '../config/koneksi.php';
include '../fungsi_telegram.php';
include '../kirim_notifikasi.php';

// 🛡️ Cek Login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: ../login.php");
    exit;
}

// ================ TAMBAH DRAFT SURAT KELUAR ================
if (isset($_POST['simpan_draft'])) {
    
    // 1. Tangkap Inputan & Bersihkan dari karakter berbahaya
    $nomor_surat = mysqli_real_escape_string($koneksi, $_POST['nomor_surat']);
    $tujuan      = mysqli_real_escape_string($koneksi, $_POST['tujuan']);
    $perihal     = mysqli_real_escape_string($koneksi, $_POST['perihal']);

    $sifat_surat = mysqli_real_escape_string($koneksi, $_POST['sifat_surat']);
    
    $draft_by = $_SESSION['user_id'];
    
    // 2. Konfigurasi Upload File PDF
    $ekstensi_diperbolehkan = array('pdf');
    $nama_file_asli         = $_FILES['file_surat']['name'];
    $x                      = explode('.', $nama_file_asli);
    $ekstensi               = strtolower(end($x));
    $ukuran_file            = $_FILES['file_surat']['size'];
    $file_tmp               = $_FILES['file_surat']['tmp_name'];
    
    if (in_array($ekstensi, $ekstensi_diperbolehkan) === true) {
        if ($ukuran_file <= 5000000) {
            
            $nama_file_baru = 'SK_' . date('Ymd_His') . '_' . rand(10,99) . '.pdf';
            $path_upload    = '../uploads/surat_keluar/' . $nama_file_baru;
            
            if (move_uploaded_file($file_tmp, $path_upload)) {
                
             
                $query_insert = "INSERT INTO surat_keluar (nomor_surat, tujuan, perihal, sifat_surat, draft_by, file_path, status_workflow) 
                                 VALUES ('$nomor_surat','$tujuan', '$perihal', '$sifat_surat', '$draft_by', '$nama_file_baru', 'Draft')";
                
                $eksekusi = mysqli_query($koneksi, $query_insert);
                
                if ($eksekusi) {
                    $id_baru = mysqli_insert_id($koneksi);
                    catat_audit_log($koneksi, 'CREATE_DRAFT_SURAT_KELUAR', 'surat_keluar', $id_baru);

                    // 🛠️ PERBAIKAN: Ditambahkan JOIN ke tabel roles agar nama_role bisa dibaca
                    $q_admin = mysqli_query($koneksi, "SELECT u.fcm_token FROM users u JOIN roles r ON u.role_id = r.id WHERE r.nama_role IN ('Kepala Sekolah', 'Admin_TU', 'Kepala_Sekolah')");
                    while ($d_admin = mysqli_fetch_array($q_admin)) {
                        if (!empty($d_admin['fcm_token'])) {
                            kirimNotifikasiFCM($d_admin['fcm_token'], "📝 Pengajuan Surat Keluar", "Ada draf baru: $perihal. Mohon segera di-review.");
                        }
                    }
                    echo "<script>
                        alert('Berhasil! Draft surat/pengajuan telah disimpan.');
                        location.href='surat_keluar.php';
                    </script>";
                } else {
                    unlink($path_upload);
                    echo "<script>alert('Gagal menyimpan ke database! Hubungi Admin.'); location.href='surat_keluar.php';</script>";
                }
            } else {
                echo "<script>alert('Gagal memindahkan file yang diunggah. Cek permission folder!'); location.href='surat_keluar.php';</script>";
            }
        } else {
            echo "<script>alert('Gagal! Ukuran file PDF terlalu besar (Maksimal 5 MB).'); location.href='surat_keluar.php';</script>";
        }
    } else {
        echo "<script>alert('Gagal! Format file tidak valid. Wajib menggunakan format .pdf'); location.href='surat_keluar.php';</script>";
    }
}

// ================ LENGKAPI DOKUMEN (DARI STATUS DIALOKASIKAN) ================
if (isset($_POST['lengkapi_surat'])) {
    
    $id_surat       = mysqli_real_escape_string($koneksi, $_POST['id_surat']);
    $tujuan         = mysqli_real_escape_string($koneksi, $_POST['tujuan']);
    $tanggal_keluar = mysqli_real_escape_string($koneksi, $_POST['tanggal_keluar']);

    $sifat_surat    = mysqli_real_escape_string($koneksi, $_POST['sifat_surat']); 
    
    $ekstensi_diperbolehkan = array('pdf');
    $nama_file_asli         = $_FILES['file_surat']['name'];
    $x                      = explode('.', $nama_file_asli);
    $ekstensi               = strtolower(end($x));
    $ukuran_file            = $_FILES['file_surat']['size'];
    $file_tmp               = $_FILES['file_surat']['tmp_name'];
    
    if (in_array($ekstensi, $ekstensi_diperbolehkan) === true) {
        if ($ukuran_file <= 5000000) {
            
            $nama_file_baru = 'SK_' . date('Ymd_His') . '_' . rand(10,99) . '.pdf';
            $path_upload    = '../uploads/surat_keluar/' . $nama_file_baru;
            
            if (move_uploaded_file($file_tmp, $path_upload)) {
                
           
                $query_update = "UPDATE surat_keluar 
                                 SET tujuan = '$tujuan', 
                                     tanggal_keluar = '$tanggal_keluar',
                                     sifat_surat = '$sifat_surat',
                                     file_path = '$nama_file_baru', 
                                     status_workflow = 'Draft' 
                                 WHERE id = '$id_surat'";
                                 
                if (mysqli_query($koneksi, $query_update)) {
                    catat_audit_log($koneksi, 'Buat Draf Surat Keluar', 'surat_keluar', $id_surat);
                    
                    // 🚀 Direct Trigger FCM Android setelah lengkapi surat sukses dilakukan
                    $q_info_lengkapi = mysqli_query($koneksi, "SELECT perihal FROM surat_keluar WHERE id = '$id_surat'");
                    $d_info_lengkapi = mysqli_fetch_array($q_info_lengkapi);
                    
                    // 🛠️ PERBAIKAN: Ditambahkan JOIN ke tabel roles
                    $q_admin = mysqli_query($koneksi, "SELECT u.fcm_token FROM users u JOIN roles r ON u.role_id = r.id WHERE r.nama_role IN ('Kepala Sekolah', 'Admin_TU', 'Kepala_Sekolah')");
                    while ($d_admin = mysqli_fetch_array($q_admin)) {
                        if (!empty($d_admin['fcm_token'])) {
                            kirimNotifikasiFCM($d_admin['fcm_token'], "📝 Surat Telah Dilengkapi", "Surat: {$d_info_lengkapi['perihal']} telah dilengkapi dan kembali ke Draft.");
                        }
                    }

                    echo "<script>alert('Berhasil! Surat telah dilengkapi dan disimpan sebagai Draft.'); window.location.href='surat_keluar.php';</script>";
                } else {
                    unlink($path_upload);
                    echo "<script>alert('Gagal menyimpan ke database! Hubungi Admin.'); location.href='surat_keluar.php';</script>";
                }
            } else {
                echo "<script>alert('Gagal memindahkan file yang diunggah. Cek permission folder!'); location.href='surat_keluar.php';</script>";
            }
        } else {
            echo "<script>alert('Gagal! Ukuran file PDF terlalu besar (Maksimal 5 MB).'); location.href='surat_keluar.php';</script>";
        }
    } else {
        echo "<script>alert('Gagal! Format file tidak valid. Wajib menggunakan format .pdf'); location.href='surat_keluar.php';</script>";
    }
}

// ================ EDIT DRAFT SURAT KELUAR ================
if (isset($_POST['edit_draft'])) {
    $id_surat    = mysqli_real_escape_string($koneksi, $_POST['id_surat']);
    $nomor_surat = mysqli_real_escape_string($koneksi, $_POST['nomor_surat']);
    $tujuan      = mysqli_real_escape_string($koneksi, $_POST['tujuan']);
    $perihal     = mysqli_real_escape_string($koneksi, $_POST['perihal']);
    // 💡 UPDATE: Ubah penangkap menjadi sifat_surat
    $sifat_surat = mysqli_real_escape_string($koneksi, $_POST['sifat_surat']);
    $file_lama   = $_POST['file_lama'];
    
    // Cari apakah ada surat lain (ID berbeda) yang sudah memakai nomor ini
    $cek_duplikat = mysqli_query($koneksi, "SELECT id FROM surat_keluar WHERE nomor_surat = '$nomor_surat' AND id != '$id_surat'");
    
    if (mysqli_num_rows($cek_duplikat) > 0) {
        echo "<script>
            alert(' GAGAL DISIMPAN!\\n\\nNomor surat \"$nomor_surat\" sudah digunakan oleh dokumen lain di dalam sistem.\\n\\nSilakan gunakan nomor yang dialokasikan untuk Anda.');
            window.location.href='surat_keluar.php';
        </script>";
        exit;
    }
    
    if ($_FILES['file_surat']['name'] != '') {
        $nama_file_asli = $_FILES['file_surat']['name'];
        $x              = explode('.', $nama_file_asli);
        $ekstensi       = strtolower(end($x));
        $ukuran_file    = $_FILES['file_surat']['size'];
        $file_tmp       = $_FILES['file_surat']['tmp_name'];

        if ($ekstensi == 'pdf' && $ukuran_file <= 5000000) {
            $nama_file_baru = 'SK_' . date('Ymd_His') . '_' . rand(10,99) . '.pdf';
            $path_upload    = '../uploads/surat_keluar/' . $nama_file_baru;
            
            if (move_uploaded_file($file_tmp, $path_upload)) {
                if (file_exists('../uploads/surat_keluar/' . $file_lama) && $file_lama != '') {
                    unlink('../uploads/surat_keluar/' . $file_lama);
                }
   
                $query = "UPDATE surat_keluar SET nomor_surat='$nomor_surat', tujuan='$tujuan', perihal='$perihal', sifat_surat='$sifat_surat', file_path='$nama_file_baru' WHERE id='$id_surat'";
            }
        } else {
            echo "<script>alert('Gagal! Pastikan file berformat PDF dan maksimal 5MB.'); location.href='surat_keluar.php';</script>";
            exit;
        }
    } else {

        $query = "UPDATE surat_keluar SET nomor_surat='$nomor_surat', tujuan='$tujuan', perihal='$perihal', sifat_surat='$sifat_surat' WHERE id='$id_surat'";
    }

    if (mysqli_query($koneksi, $query)) {
        catat_audit_log($koneksi, 'Buat Draf Surat Keluar', 'surat_keluar', $id_surat);
        
        // 🚀 Direct Trigger FCM Android setelah edit draft sukses dilakukan
        // 🛠️ PERBAIKAN: Ditambahkan JOIN ke tabel roles
        $q_admin = mysqli_query($koneksi, "SELECT u.fcm_token FROM users u JOIN roles r ON u.role_id = r.id WHERE r.nama_role IN ('Kepala Sekolah', 'Admin_TU', 'Kepala_Sekolah')");
        while ($d_admin = mysqli_fetch_array($q_admin)) {
            if (!empty($d_admin['fcm_token'])) {
                kirimNotifikasiFCM($d_admin['fcm_token'], "✏️ Perubahan Draft Surat", "Draft surat '$perihal' telah diperbarui.");
            }
        }

        echo "<script>alert('Berhasil! Surat telah Diperbarui dan disimpan sebagai Draft.'); window.location.href='surat_keluar.php';</script>>";
    } else {
        echo "<script>alert('Gagal memperbarui draf!'); location.href='surat_keluar.php';</script>";
    }
}

// ================ AJUKAN DRAFT KE KEPSEK ================
if (isset($_GET['aksi']) && $_GET['aksi'] == 'ajukan') {
    $id_surat = mysqli_real_escape_string($koneksi, $_GET['id']);
    $user_id_sekarang = $_SESSION['user_id'];
    
    $query = "UPDATE surat_keluar SET status_workflow = 'Review', catatan_revisi = NULL WHERE id = '$id_surat' AND draft_by = '$user_id_sekarang'";
    
    if (mysqli_query($koneksi, $query)) {
        catat_audit_log($koneksi, 'SUBMIT_SURAT_KELUAR', 'surat_keluar', $id_surat);

        $q_kepsek = mysqli_query($koneksi, "
            SELECT u.telegram_id, u.fcm_token, sk.nomor_surat, sk.perihal, u_pengaju.nama_lengkap 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            JOIN surat_keluar sk ON sk.id = '$id_surat' 
            JOIN users u_pengaju ON sk.draft_by = u_pengaju.id 
            WHERE r.nama_role IN ('Kepala Sekolah', 'Kepala_Sekolah') LIMIT 1
        ");
        
        if ($d_kepsek = mysqli_fetch_array($q_kepsek)) {
            // 🚀 Direct Trigger FCM Android ke Kepala Sekolah sewaktu surat diajukan
            if (!empty($d_kepsek['fcm_token'])) {
                kirimNotifikasiFCM($d_kepsek['fcm_token'], "🔔 Pengajuan Surat Keluar", "Diajukan oleh: {$d_kepsek['nama_lengkap']}. Perihal: {$d_kepsek['perihal']}");
            }

            if (!empty($d_kepsek['telegram_id'])) {
                $pesan = "🔔 *PENGAJUAN / REVISI SURAT KELUAR*\n\n";
                $pesan .= "Halo Bapak/Ibu Kepala Sekolah, terdapat draf surat keluar yang memerlukan *Review* Anda.\n\n";
                $pesan .= "📄 *Nomor Surat:* " . ($d_kepsek['nomor_surat'] ? $d_kepsek['nomor_surat'] : 'Belum ada nomor') . "\n";
                $pesan .= "📝 *Perihal:* " . $d_kepsek['perihal'] . "\n";
                $pesan .= "👤 *Diajukan Oleh:* " . $d_kepsek['nama_lengkap'] . "\n\n";
                $pesan .= "Silakan login ke SIMPERS untuk melakukan tindakan lebih lanjut.";
                
                kirim_telegram($d_kepsek['telegram_id'], $pesan);
            }
        }

        echo "<script>
            alert('Sukses! Draf berhasil diajukan ke Kepala Sekolah untuk di-Review.');
            location.href='surat_keluar.php';
        </script>";
    } else {
        echo "<script>alert('Gagal mengajukan draf!'); location.href='surat_keluar.php';</script>";
    }
}

// ================ PROSES REVIEW OLEH KEPALA SEKOLAH ================
if (isset($_POST['proses_review'])) {
    
    // Gunakan pengecekan fleksibel untuk menghindari salah ketik nama_role
    if ($_SESSION['nama_role'] != 'Kepala_Sekolah' && $_SESSION['nama_role'] != 'Kepala Sekolah') {
        echo "<script>alert('Akses Ditolak! Hanya Kepala Sekolah yang dapat melakukan ini.'); location.href='surat_keluar.php';</script>";
        exit;
    }

    $id_surat  = mysqli_real_escape_string($koneksi, $_POST['id_surat']);
    $keputusan = mysqli_real_escape_string($koneksi, $_POST['keputusan']);
    $catatan_revisi = isset($_POST['catatan_revisi']) ? mysqli_real_escape_string($koneksi, $_POST['catatan_revisi']) : NULL;
    $kepsek_id = $_SESSION['user_id'];

    $q_surat = mysqli_query($koneksi, "SELECT sk.nomor_surat, sk.perihal, sk.file_path, u.telegram_id, u.fcm_token FROM surat_keluar sk JOIN users u ON sk.draft_by = u.id WHERE sk.id = '$id_surat'");
    $d_surat = mysqli_fetch_array($q_surat);
    $telegram_id_pembuat = $d_surat['telegram_id'];
    $fcm_token_pembuat   = $d_surat['fcm_token'];
    $nomor_surat_notif   = $d_surat['nomor_surat'];

    if ($keputusan == 'Revisi') {
        
        // === TAMBAHAN LOGIKA UPLOAD REVISI ===
        $file_path_update = ""; 
        if (isset($_FILES['file_revisi']) && $_FILES['file_revisi']['error'] == 0) {
            $nama_file_asli = $_FILES['file_revisi']['name'];
            $tmp_file = $_FILES['file_revisi']['tmp_name'];
            
            // Gunakan prefix REVISI_ agar pembuat tahu ini file yang sudah dicoret
            $nama_file_revisi = "REVISI_" . date('YmdHis') . "_" . $nama_file_asli;
            $path_tujuan = "../uploads/surat_keluar/" . $nama_file_revisi;

            if (move_uploaded_file($tmp_file, $path_tujuan)) {
                $file_path_update = ", file_path = '$nama_file_revisi'";
            }
        }
        // =====================================

        $query = "UPDATE surat_keluar SET status_workflow = 'Revisi', approved_by = NULL, catatan_revisi = '$catatan_revisi' $file_path_update WHERE id = '$id_surat'";
        mysqli_query($koneksi, $query);

        catat_audit_log($koneksi, 'REJECT_SURAT_KELUAR', 'surat_keluar', $id_surat);

        // 🚀 Direct Trigger FCM Android ke Staf Pembuat jika keputusan adalah REVISI
        if (!empty($fcm_token_pembuat)) {
            kirimNotifikasiFCM($fcm_token_pembuat, "❌ Surat Perlu Revisi", "Surat '{$d_surat['perihal']}' dikembalikan. Alasan: $catatan_revisi");
        }

        if (!empty($telegram_id_pembuat)) {
            $pesan = "⚠️ *STATUS SURAT: REVISI*\n\n";
            $pesan .= "Draf surat keluar yang Anda ajukan telah dikembalikan oleh Kepala Sekolah untuk *Direvisi*.\n\n";
            $pesan .= "📄 *Nomor Surat:* " . ($nomor_surat_notif ? $nomor_surat_notif : 'Belum ada nomor') . "\n";
            
            if (!empty($catatan_revisi)) {
                $pesan .= "📝 *Catatan Kepsek:*\n_" . $catatan_revisi . "_\n\n";
            }
            
            if ($file_path_update != "") {
                $pesan .= "📎 *Info:* Kepala Sekolah telah melampirkan file PDF dengan coretan revisi.\n\n";
            }
            
            $pesan .= "Silakan periksa dan perbaiki draf tersebut melalui aplikasi SIMPERS.";
            kirim_telegram($telegram_id_pembuat, $pesan);
        }

        echo "<script>alert('Surat dikembalikan untuk Revisi!'); location.href='surat_keluar.php';</script>";
        exit;
        
    } elseif ($keputusan == 'Approved') {
        $is_tte = mysqli_real_escape_string($koneksi, $_POST['is_tte']);
        
        $query_file = mysqli_query($koneksi, "SELECT file_path FROM surat_keluar WHERE id = '$id_surat'");
        $data_file = mysqli_fetch_assoc($query_file);
        $file_lama = $data_file['file_path'];

        $nama_file_final = $file_lama;
        $pesan_tte = "Surat disetujui untuk tanda tangan basah.";

        if ($is_tte == 1) {
            require_once '../vendor/autoload.php';
            $link_validasi = "http://simpers.42web.io//validasi.php?id=" . $id_surat;
            
            if (!file_exists('../uploads/qr/')) {
                mkdir('../uploads/qr/', 0777, true);
            }

            $file_qr = "../uploads/qr/qr_" . $id_surat . ".png";
            $nama_file_final = "TTE_" . $file_lama; 
            $path_pdf_lama = "../uploads/surat_keluar/" . $file_lama;
            $path_pdf_baru = "../uploads/surat_keluar/" . $nama_file_final;

            $ungu = [102, 0, 153];
            
            $options = new \chillerlan\QRCode\QROptions([
                'version'    => 5,
                'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel'   => \chillerlan\QRCode\QRCode::ECC_L,
                'scale'      => 4, 
                'moduleValues' => [
                    \chillerlan\QRCode\Data\QRMatrix::M_FINDER_DARK    => $ungu, 
                    \chillerlan\QRCode\Data\QRMatrix::M_FINDER_DOT     => $ungu, 
                    \chillerlan\QRCode\Data\QRMatrix::M_ALIGNMENT_DARK => $ungu, 
                    \chillerlan\QRCode\Data\QRMatrix::M_TIMING_DARK    => $ungu, 
                    \chillerlan\QRCode\Data\QRMatrix::M_FORMAT_DARK    => $ungu, 
                    \chillerlan\QRCode\Data\QRMatrix::M_VERSION_DARK   => $ungu, 
                    \chillerlan\QRCode\Data\QRMatrix::M_DATA_DARK      => $ungu, 
                    \chillerlan\QRCode\Data\QRMatrix::M_DARKMODULE     => $ungu, 
                ],
            ]);
            
            $qrcode = new \chillerlan\QRCode\QRCode($options);
            $qrcode->render($link_validasi, $file_qr);

            $pdf = new \setasign\Fpdi\Fpdi();
            $pageCount = $pdf->setSourceFile($path_pdf_lama);
            
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

            
                if ($pageNo == $pageCount) {
                    
                    $pdf->SetAutoPageBreak(false);
                    $pdf->SetXY(10, 265);
                    $pdf->SetFont('Arial', 'BI', 9);
                    $pdf->Cell(0, 4, 'Catatan:', 0, 1, 'L');
                    $pdf->Line(10, 270, 200, 270); 

                    $pdf->SetXY(10, 271);
                    $pdf->SetFont('Arial', '', 8);
                    $pdf->Cell(0, 4, 'UU ITE No. 11 Tahun 2008 Pasal 5 ayat 1 :', 0, 1, 'L');
                    
                    $pdf->SetX(10);
                    $pdf->SetFont('Arial', 'I', 8);
                    $pdf->Cell(0, 4, '"Informasi Elektronik dan/atau Dokumen Elektronik dan/atau hasil cetaknya merupakan alat bukti hukum yang sah."', 0, 1, 'L');
                    
                    $pdf->SetX(10);
                    $pdf->SetFont('Arial', '', 8);
                    $pdf->Cell($pdf->GetStringWidth('Dokumen ini telah ditandatangani secara elektronik menggunakan '), 4, 'Dokumen ini telah ditandatangani secara elektronik menggunakan ', 0, 0, 'L');
                    $pdf->SetFont('Arial', 'B', 8); 
                    $pdf->Cell($pdf->GetStringWidth('sertifikat elektronik'), 4, 'sertifikat elektronik', 0, 0, 'L');
                    $pdf->SetFont('Arial', '', 8); 
                    $pdf->Cell($pdf->GetStringWidth(' yang diterbitkan '), 4, ' yang diterbitkan ', 0, 0, 'L');
                    $pdf->SetFont('Arial', 'B', 8); 
                    $pdf->Cell(0, 4, 'SIMPERS.', 0, 1, 'L'); 
                    
                    $pdf->SetX(10);
                    $pdf->SetFont('Arial', '', 8);
                    $pdf->Cell($pdf->GetStringWidth('Cetakan ini merupakan salinan dan '), 4, 'Cetakan ini merupakan salinan dan ', 0, 0, 'L');
                    $pdf->SetFont('Arial', 'B', 8); 
                    $pdf->Cell($pdf->GetStringWidth('dapat dibuktikan keasliannya melalui scan QRCode'), 4, 'dapat dibuktikan keasliannya melalui scan QRCode', 0, 0, 'L');
                    $pdf->SetFont('Arial', '', 8); 
                    $pdf->Cell(0, 4, ' yang terdapat pada dokumen ini', 0, 1, 'L');

                    $pdf->Image($file_qr, 178, 268, 22, 22);
                }
            }
            
            $pdf->Output('F', $path_pdf_baru);
            $pesan_tte = "Surat berhasil disetujui dan disegel dengan TTE (QR Code).";
        }

        $query_approve = "UPDATE surat_keluar SET 
                          status_workflow = 'Approved', 
                          is_tte = '$is_tte', 
                          file_path = '$nama_file_final',
                          approved_by = '$kepsek_id',
                          tanggal_keluar = CURRENT_DATE() 
                          WHERE id = '$id_surat'";
        
        if (mysqli_query($koneksi, $query_approve)) {
            catat_audit_log($koneksi, 'APPROVE_SURAT_KELUAR', 'surat_keluar', $id_surat);

            // 🚀 Direct Trigger FCM Android ke Staf Pembuat jika keputusan adalah APPROVED
            if (!empty($fcm_token_pembuat)) {
                kirimNotifikasiFCM($fcm_token_pembuat, "✅ Surat Disetujui", "Surat: {$d_surat['perihal']} telah disetujui oleh Kepala Sekolah.");
            }

            if (!empty($telegram_id_pembuat)) {
                $pesan = "✅ *STATUS SURAT: APPROVED*\n\n";
                $pesan .= "Selamat! Draf surat keluar Anda telah *Disetujui* oleh Kepala Sekolah.\n\n";
                $pesan .= "📄 *Nomor Surat:* " . ($nomor_surat_notif ? $nomor_surat_notif : 'Belum ada nomor') . "\n";
                if ($is_tte == 1) {
                    $pesan .= "🔏 *TTE:* Dokumen telah otomatis ditandatangani secara elektronik (QR Code).\n\n";
                } else {
                    $pesan .= "✍️ *Catatan:* Dokumen disetujui untuk tanda tangan basah.\n\n";
                }
                $pesan .= "Silakan proses ke tahap pengiriman surat.";
                
                kirim_telegram($telegram_id_pembuat, $pesan);
            }

            echo "<script>alert('Sukses! $pesan_tte'); location.href='surat_keluar.php';</script>";
        } else {
            echo "<script>alert('Gagal menyetujui surat!'); location.href='surat_keluar.php';</script>";
        }
    }
}

// =========================================================================
// PROSES TANDAI TERKIRIM (OLEH GURU / TU)
// =========================================================================
if (isset($_POST['tandai_terkirim'])) {
    $id_surat = mysqli_real_escape_string($koneksi, $_POST['id_surat']);
    
    $query_terkirim = "UPDATE surat_keluar SET status_workflow = 'Terkirim' WHERE id = '$id_surat'";
    $eksekusi = mysqli_query($koneksi, $query_terkirim);

    if ($eksekusi) {
        catat_audit_log($koneksi, 'SEND_SURAT_KELUAR', 'surat_keluar', $id_surat);

        $q_sk = mysqli_query($koneksi, "SELECT sk.nomor_surat, u.fcm_token FROM surat_keluar sk JOIN users u ON sk.draft_by = u.id WHERE sk.id = '$id_surat'");
        $d_sk = mysqli_fetch_array($q_sk);
        
        // 🚀 Direct Trigger FCM Android ke Staf Pembuat sewaktu surat ditandai Terkirim
        if (!empty($d_sk['fcm_token'])) {
            kirimNotifikasiFCM($d_sk['fcm_token'], "🚀 Surat Terkirim", "Surat No: {$d_sk['nomor_surat']} sudah diproses dan dikirim.");
        }
        
        $q_surat_kirim = mysqli_query($koneksi, "SELECT sk.nomor_surat, u.telegram_id FROM surat_keluar sk JOIN users u ON sk.draft_by = u.id WHERE sk.id = '$id_surat'");
        $d_surat_kirim = mysqli_fetch_array($q_surat_kirim);
        
        if (!empty($d_surat_kirim['telegram_id'])) {
            $pesan = "🚀 *SURAT TERKIRIM*\n\n";
            $pesan .= "Surat keluar yang Anda ajukan telah berhasil ditandai sebagai *Terkirim* oleh Tata Usaha / Admin.\n\n";
            $pesan .= "📄 *Nomor Surat:* " . $d_surat_kirim['nomor_surat'];
            
            kirim_telegram($d_surat_kirim['telegram_id'], $pesan);
        }

        echo "<script>
                alert('Berhasil! Surat telah ditandai sebagai Terkirim.');
                window.location.href = 'surat_keluar.php';
              </script>";
    } else {
        echo "<script>
                alert('Gagal menandai surat: " . mysqli_error($koneksi) . "');
                window.location.href = 'surat_keluar.php';
              </script>";
    }
}
?>