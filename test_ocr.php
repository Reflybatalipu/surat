<?php
// Panggil autoloader dari Composer
require_once 'vendor/autoload.php';

use thiagoalessio\TesseractOCR\TesseractOCR;

// Nama file gambar yang sudah kamu siapkan di folder yang sama
$gambar_surat = 'contoh_surat.png'; 

echo "<h2>🧪 Uji Coba Tesseract OCR di Windows</h2>";
echo "<p>Sedang mengekstrak teks dari gambar: <b>$gambar_surat</b>...</p>";

try {
    // Pastikan gambar benar-benar ada
    if (!file_exists($gambar_surat)) {
        throw new Exception("File gambar '$gambar_surat' tidak ditemukan di folder ini!");
    }

    $ocr = new TesseractOCR($gambar_surat);
    
    // ⚠️ SANGAT PENTING UNTUK WINDOWS:
    // Sesuaikan path ini dengan lokasi instalasi Tesseract di laptop kamu.
    // Default-nya biasanya di sini:
    $ocr->executable('C:\Program Files\Tesseract-OCR\tesseract.exe');
    
    // Set bahasa (pastikan kamu sudah centang bahasa Indonesia saat instalasi Tesseract)
    // Jika error, coba hapus 'ind' dan sisakan 'eng' saja dulu untuk testing.
    $ocr->lang('ind', 'eng');

    // Eksekusi!
    $teks_hasil = $ocr->run();

    echo "<h3 style='color: green;'>✅ Berhasil Diekstrak!</h3>";
    echo "<b>Hasil Teks:</b><br>";
    echo "<textarea style='width: 100%; height: 300px; padding: 10px; font-family: monospace;'>" . htmlspecialchars($teks_hasil) . "</textarea>";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Gagal! Ada Error:</h3>";
    echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid red;'>";
    echo $e->getMessage();
    echo "</div>";
    echo "<p><b>Tips:</b> Pastikan path <code>executable</code> di dalam kode sesuai dengan folder instalasi Tesseract-mu.</p>";
}
?>