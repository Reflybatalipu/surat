<?php
// Baris untuk merekam data yang masuk dari Telegram ke dalam file log_telegram.txt
file_put_contents('log_telegram.txt', file_get_contents('php://input') . "\n", FILE_APPEND);

// ... sisa kode di bawahnya (include koneksi, dst)
// Sesuaikan dengan path koneksi database kamu
include 'config/koneksi.php'; 

// Masukkan Token Bot Telegram kamu
$TOKEN = "8750324075:AAGI3fu1xFQjf4bzzYKn6QuAbJlxUqY9xRA"; 

// Fungsi untuk mengirim pesan dan keyboard ke Telegram
function kirimPesan($chat_id, $text, $keyboard = null) {
    global $TOKEN;
    $url = "https://api.telegram.org/bot" . $TOKEN . "/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ];

    if ($keyboard != null) {
        $data['reply_markup'] = json_encode($keyboard);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}

// 1. Terima Webhook dari Telegram
$update = json_decode(file_get_contents('php://input'), TRUE);

if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $pesan = $update['message']['text'];

    // --- OPSIONAL: CEK KEAMANAN ---
    // Di sini kamu bisa query ke tabel user SIMPERS untuk mengecek 
    // apakah $chat_id ini terdaftar. Jika tidak, tolak aksesnya.
    // ------------------------------

    // 2. Definisi Tombol Menu (Custom Keyboard)
    $menu_utama = [
        'keyboard' => [
            ['🎫 Nomor Terakhir', '📊 Tren Surat'],
            ['❓ Bantuan']
        ],
        'resize_keyboard' => true, // Menyesuaikan ukuran tombol
        'one_time_keyboard' => false // Tombol tetap muncul
    ];

    // 3. Logika Respon Bot
    if ($pesan == '/start') {
        $balasan = "Halo! Saya adalah Bot Asisten SIMPERS. 🤖\n\nSilakan pilih menu di bawah layar untuk mulai menggunakan fitur saya.";
        kirimPesan($chat_id, $balasan, $menu_utama);

    } elseif ($pesan == '🎫 Nomor Terakhir') {
        // Contoh Query (Sesuaikan dengan nama tabel dan kolom di SIMPERS-mu)
        $q = mysqli_query($koneksi, "SELECT nomor_surat FROM surat_keluar ORDER BY id DESC LIMIT 1");
        
        if (mysqli_num_rows($q) > 0) {
            $d = mysqli_fetch_assoc($q);
            $balasan = "Nomor surat keluar terakhir di SIMPERS adalah:\n\n*{$d['nomor_surat']}*";
        } else {
            $balasan = "Belum ada data surat keluar di sistem.";
        }
        kirimPesan($chat_id, $balasan, $menu_utama);

    } elseif ($pesan == '📊 Tren Surat') {
        // Contoh Query sederhana menghitung jumlah baris
        $q_masuk = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM surat_masuk");
        $q_keluar = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM surat_keluar");
        
        $jml_masuk = mysqli_fetch_assoc($q_masuk)['total'];
        $jml_keluar = mysqli_fetch_assoc($q_keluar)['total'];
        
        $balasan = "📊 *Statistik Persuratan SIMPERS*\n\n📥 Surat Masuk: *$jml_masuk Surat*\n📤 Surat Keluar: *$jml_keluar Surat*";
        kirimPesan($chat_id, $balasan, $menu_utama);

    } elseif ($pesan == '❓ Bantuan') {
        $balasan = "Untuk menggunakan bot ini, cukup tekan tombol menu di bagian bawah layar. Jika tombol tidak muncul, klik ikon kotak dengan empat titik di sebelah kolom ketik.";
        kirimPesan($chat_id, $balasan, $menu_utama);
        
    } else {
        $balasan = "Maaf, saya tidak mengerti perintah tersebut. Silakan gunakan tombol menu di bawah.";
        kirimPesan($chat_id, $balasan, $menu_utama);
    }
}
?>