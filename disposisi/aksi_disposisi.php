<?php
session_start();
include '../config/koneksi.php';
include '../kirim_notifikasi.php';

// ================ KIRIM DISPOSISI SURAT (ADMIN TU -> PEGAWAI) ================
if (isset($_POST['kirim_disposisi'])) {
    
    $surat_id        = $_POST['surat_id'];
    $dari_user_id    = $_SESSION['user_id'];
    $instruksi       = mysqli_real_escape_string($koneksi, $_POST['instruksi']);
    $batas_waktu_sla = !empty($_POST['batas_waktu_sla']) ? "'" . $_POST['batas_waktu_sla'] . "'" : "NULL";
    
    $ke_user_ids     = $_POST['ke_user_id']; 

    $berhasil = 0;

    $q_surat = mysqli_query($koneksi, "SELECT nomor_surat, pengirim, perihal, file_path FROM surat_masuk WHERE id = '$surat_id'");
    if($q_surat) {
        $d_surat = mysqli_fetch_array($q_surat);
        $nomor_surat   = $d_surat['nomor_surat'];
        $pengirim      = $d_surat['pengirim'];
        $perihal       = $d_surat['perihal'];
        $file_lampiran = $d_surat['file_path']; 
    }

    $q_pengirim = mysqli_query($koneksi, "SELECT nama_lengkap FROM users WHERE id = '$dari_user_id'");
    $d_pengirim = mysqli_fetch_array($q_pengirim);
    $nama_pengirim_disposisi = ($d_pengirim) ? $d_pengirim['nama_lengkap'] : 'Sistem';

    foreach ($ke_user_ids as $penerima_id) {
        $query_disposisi = mysqli_query($koneksi, "
            INSERT INTO disposisi (surat_id, dari_user_id, ke_user_id, instruksi, batas_waktu_sla, status) 
            VALUES ('$surat_id', '$dari_user_id', '$penerima_id', '$instruksi', $batas_waktu_sla, 'Menunggu')
        ");
        
        if ($query_disposisi) {
            $disposisi_id = mysqli_insert_id($koneksi);
            if(function_exists('catat_audit_log')) catat_audit_log($koneksi, 'CREATE_DISPOSISI', 'disposisi', $disposisi_id);
            
            // --- AMBIL DATA PENERIMA ---
            $q_target = mysqli_query($koneksi, "SELECT nama_lengkap, telegram_id, fcm_token FROM users WHERE id = '$penerima_id'");
            $target = mysqli_fetch_array($q_target);
            
            // 🚀 [NOTIFIKASI ANDROID] Tambahan di sini
            if (!empty($target['fcm_token'])) {
                $judul_android = "📩 Disposisi Surat Baru";
                $isi_android   = "Dari: $nama_pengirim_disposisi\nHal: $perihal";
                kirimNotifikasiFCM($target['fcm_token'], $judul_android, $isi_android);
            }

            // [NOTIFIKASI TELEGRAM] Kembali ke format lengkapmu
            if (!empty($target['telegram_id'])) {
                $caption = "🔔 *DISPOSISI SURAT BARU* 🔔\n\n";
                $caption .= "Yth. *{$target['nama_lengkap']}*,\n";
                $caption .= "Anda menerima Disposisi baru dari *$nama_pengirim_disposisi*.\n\n";
                $caption .= "📌 *Detail Surat:*\n";
                $caption .= "▪️ *No. Surat:* $nomor_surat\n";
                $caption .= "▪️ *Pengirim:* $pengirim\n";
                $caption .= "▪️ *Perihal:* $perihal\n\n";
                $caption .= "📝 *Instruksi:*\n_{$instruksi}_\n\n";
                
                if (!empty($_POST['batas_waktu_sla'])) {
                    $caption .= "⏰ *Batas Waktu (SLA):* " . $_POST['batas_waktu_sla'] . "\n\n";
                }
                $caption .= "Mohon segera login ke aplikasi *Simpers* untuk menindaklanjuti. Terima kasih.";
                
                $lokasi_file_fisik = (!empty($file_lampiran)) ? "../uploads/surat_masuk/" . $file_lampiran : "";
                $chat_id_safe = mysqli_real_escape_string($koneksi, $target['telegram_id']);
                $caption_safe = mysqli_real_escape_string($koneksi, $caption);
                $file_safe = mysqli_real_escape_string($koneksi, $lokasi_file_fisik);

                mysqli_query($koneksi, "INSERT INTO antrean_telegram (telegram_id, pesan, file_path, status_kirim) VALUES ('$chat_id_safe', '$caption_safe', '$file_safe', 'pending')");
            }
            $berhasil++;
        }
    }

    if ($berhasil > 0) {
        mysqli_query($koneksi, "UPDATE surat_masuk SET status_workflow = 'Disposisi' WHERE id = '$surat_id'");
        if(function_exists('catat_audit_log')) catat_audit_log($koneksi, 'DISPOSISI_SURAT', 'surat_masuk', $surat_id);
        echo "<script>alert('Sukses! Disposisi tersimpan dan notifikasi sedang dikirim.'); location.href='../modul_surat_masuk/surat_masuk.php';</script>";
    } else {
        echo "<script>alert('Gagal mendisposisikan surat.'); location.href='../modul_surat_masuk/surat_masuk.php';</script>";
    }
}

// ================ TINDAK LANJUT DISPOSISI (LENGKAP) ================
if (isset($_POST['tindak_lanjut'])) {
    $disposisi_id  = $_POST['disposisi_id'];
    $surat_id      = $_POST['surat_id'];
    $jenis_tindakan = $_POST['jenis_tindakan'];
    $dari_user_id  = $_SESSION['user_id']; 
    $laporan_tindak_lanjut = isset($_POST['laporan_tindak_lanjut']) ? mysqli_real_escape_string($koneksi, $_POST['laporan_tindak_lanjut']) : NULL;
    
    $q_info = mysqli_query($koneksi, "
        SELECT d.dari_user_id, u_pemberi.telegram_id AS telegram_pemberi, u_pemberi.fcm_token AS fcm_pemberi, u_pemberi.nama_lengkap AS nama_pemberi,
               sm.nomor_surat, sm.perihal, u_pelaksana.nama_lengkap AS nama_pelaksana
        FROM disposisi d
        JOIN users u_pemberi ON d.dari_user_id = u_pemberi.id
        JOIN surat_masuk sm ON d.surat_id = sm.id
        JOIN users u_pelaksana ON d.ke_user_id = u_pelaksana.id
        WHERE d.id = '$disposisi_id'
    ");
    $d_info = mysqli_fetch_array($q_info);

    $q_update_disp = "UPDATE disposisi SET status = 'Selesai', acted_at = CURRENT_TIMESTAMP, read_at = IFNULL(read_at, CURRENT_TIMESTAMP)";
    if ($laporan_tindak_lanjut) { $q_update_disp .= ", laporan_tindak_lanjut = '$laporan_tindak_lanjut'"; }
    $q_update_disp .= " WHERE id = '$disposisi_id'";
    mysqli_query($koneksi, $q_update_disp);
    
    if(function_exists('catat_audit_log')) catat_audit_log($koneksi, 'FOLLOW_UP_DISPOSISI', 'disposisi', $disposisi_id);

    if ($jenis_tindakan == 'selesai') {
        mysqli_query($koneksi, "UPDATE surat_masuk SET status_workflow = 'Selesai' WHERE id = '$surat_id'");
        if(function_exists('catat_audit_log')) catat_audit_log($koneksi, 'FINISH_SURAT_MASUK', 'surat_masuk', $surat_id);

        // 🚀 [NOTIFIKASI ANDROID KE ATASAN]
        if (!empty($d_info['fcm_pemberi'])) {
            kirimNotifikasiFCM($d_info['fcm_pemberi'], "✅ Tugas Selesai", "Laporan: $laporan_tindak_lanjut");
        }

        // [NOTIFIKASI TELEGRAM KE ATASAN]
        if ($d_info && !empty($d_info['telegram_pemberi']) && !empty($laporan_tindak_lanjut)) {
            $caption = "✅ *LAPORAN TUGAS SELESAI* ✅\n\nYth. *{$d_info['nama_pemberi']}*,\nTugas disposisi telah dikerjakan oleh *{$d_info['nama_pelaksana']}*.\n\n📝 *Laporan:* _{$laporan_tindak_lanjut}_";
            $chat_id_safe = mysqli_real_escape_string($koneksi, $d_info['telegram_pemberi']);
            $caption_safe = mysqli_real_escape_string($koneksi, $caption);
            mysqli_query($koneksi, "INSERT INTO antrean_telegram (telegram_id, pesan, status_kirim) VALUES ('$chat_id_safe', '$caption_safe', 'pending')");
        }
        echo "<script>alert('Laporan berhasil dikirim.'); location.href='disposisi.php';</script>";
        
    } elseif ($jenis_tindakan == 'teruskan') {
        $ke_user_ids_baru = $_POST['ke_user_id_baru']; 
        $instruksi_baru   = mysqli_real_escape_string($koneksi, $_POST['instruksi_baru']);
        $berhasil_teruskan = 0;

        // Ambil data surat & pengirim SEKALI di luar loop, bukan di dalam
        $q_surat = mysqli_query($koneksi, "SELECT nomor_surat, pengirim, perihal, file_path FROM surat_masuk WHERE id = '$surat_id'");
        $d_surat = mysqli_fetch_array($q_surat);
        $file_lampiran = $d_surat['file_path'];

        $q_pengirim = mysqli_query($koneksi, "SELECT nama_lengkap FROM users WHERE id = '$dari_user_id'");
        $nama_pengirim_disposisi = mysqli_fetch_array($q_pengirim)['nama_lengkap'];

        // Satu foreach saja — 1 penerima = 1 INSERT = 1 notifikasi
        foreach ($ke_user_ids_baru as $penerima_id) {
            $q_forward = mysqli_query($koneksi, "
                INSERT INTO disposisi (surat_id, dari_user_id, ke_user_id, instruksi, status) 
                VALUES ('$surat_id', '$dari_user_id', '$penerima_id', '$instruksi_baru', 'Menunggu')
            ");
            if ($q_forward) {
                $id_forward = mysqli_insert_id($koneksi);
                catat_audit_log($koneksi, 'FORWARD_DISPOSISI', 'disposisi', $id_forward);

                $q_target = mysqli_query($koneksi, "SELECT nama_lengkap, telegram_id, fcm_token FROM users WHERE id = '$penerima_id'");
                $target = mysqli_fetch_array($q_target);

                // 🚀 [NOTIFIKASI ANDROID]
                if (!empty($target['fcm_token'])) {
                    kirimNotifikasiFCM($target['fcm_token'], "🔄 Terusan Disposisi", "Instruksi: $instruksi_baru");
                }

                // [NOTIFIKASI TELEGRAM]
                if (!empty($target['telegram_id'])) {
                    $caption = "🔄 *TERUSAN DISPOSISI* 🔄\n\n";
                    $caption .= "Yth. *{$target['nama_lengkap']}*,\n";
                    $caption .= "Ada instruksi lanjutan dari *$nama_pengirim_disposisi* terkait surat:\n\n";
                    $caption .= "▪️ *No. Surat:* " . $d_surat['nomor_surat'] . "\n";
                    $caption .= "▪️ *Perihal:* " . $d_surat['perihal'] . "\n\n";
                    $caption .= "📝 *Instruksi Lanjutan:*\n_{$instruksi_baru}_\n\n";
                    $caption .= "Mohon segera login ke aplikasi *Simpers*.";

                    $lokasi_file_fisik = (!empty($file_lampiran)) ? "../uploads/surat_masuk/" . $file_lampiran : "";
                    $chat_id_safe = mysqli_real_escape_string($koneksi, $target['telegram_id']);
                    $caption_safe = mysqli_real_escape_string($koneksi, $caption);
                    $file_safe    = mysqli_real_escape_string($koneksi, $lokasi_file_fisik);

                    mysqli_query($koneksi, "INSERT INTO antrean_telegram (telegram_id, pesan, file_path, status_kirim) VALUES ('$chat_id_safe', '$caption_safe', '$file_safe', 'pending')");
                }
                $berhasil_teruskan++;
            }
        }

        if ($berhasil_teruskan > 0) {
            // 💡 INI PENAMBAHANNYA JILID 2: Catat jejak kalau surat diteruskan (Forward)
            catat_audit_log($koneksi, 'TERUSKAN_DISPOSISI', 'surat_masuk', $surat_id);
        }
        echo "<script>alert('Surat berhasil diteruskan.'); location.href='disposisi.php';</script>";
    }
}

// ================ BULK ACTION (TETAP UTUH) ================
if (isset($_POST['proses_bulk_action'])) {
    $id_string = mysqli_real_escape_string($koneksi, $_POST['bulk_disposisi_ids']);
    $jenis_tindakan = $_POST['bulk_jenis_tindakan'];
    if (!empty($id_string)) {
        $query_bulk = "UPDATE disposisi SET status = 'Selesai' WHERE id IN ($id_string)";
        mysqli_query($koneksi, $query_bulk);
        echo "<script>alert('Berhasil diproses.'); location.href='disposisi.php';</script>";
    }
}
?>