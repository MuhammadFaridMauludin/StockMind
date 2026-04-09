<?php
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/laragon/www/bot/storage/error.txt');

$SEKTOR = require "C:/laragon/www/bot/config/sektor.php";

require_once "C:/laragon/www/bot/config/config.php";
require_once "C:/laragon/www/bot/services/StockService.php";
require_once "C:/laragon/www/bot/services/AnalysisService.php";
require_once "C:/laragon/www/bot/services/TelegramService.php";

$update = json_decode(file_get_contents("php://input"), true);

if (!$update) exit("No data");

$chat_id = $update["message"]["chat"]["id"] ?? null;
$text = strtoupper(trim($update["message"]["text"] ?? ""));

file_put_contents("C:/laragon/www/bot/storage/log2.txt", "TEXT: $text" . PHP_EOL, FILE_APPEND);

if (strpos($text, "ANALISIS SAHAM") !== false) {
    $kode = trim(str_replace("ANALISIS SAHAM", "", $text));

    file_put_contents("C:/laragon/www/bot/storage/log2.txt", "KODE: $kode" . PHP_EOL, FILE_APPEND);

    $analysis = new AnalysisService();
    $hasil = $analysis->analisisSaham($kode);
    
    file_put_contents("C:/laragon/www/bot/storage/log2.txt", "HASIL: $hasil" . PHP_EOL, FILE_APPEND);
    
    kirimPesan($chat_id, $hasil);
}
if (strpos($text, "RANKING SEKTOR") !== false) {

    $parts = explode(" ", $text);

    // pastikan minimal ada 3 kata
    if (count($parts) < 3) {
        kirimPesan($chat_id, "❌ Format salah. Contoh: ranking sektor bank");
        return;
    }

    $namaSektor = strtolower(implode(" ", array_slice($parts, 2)));

    file_put_contents("C:/laragon/www/bot/storage/log2.txt", "SEKTOR: $namaSektor\n", FILE_APPEND);

    $scoring = new ScoringService();

    $ranking = $scoring->rankingSektor($namaSektor);
    $response = $scoring->formatRanking($namaSektor, $ranking);

    kirimPesan($chat_id, $response);
}
if (strpos($text, "RANKING INDEKS") !== false) {

    $parts = explode(" ", $text);

    if (count($parts) < 3) {
        kirimPesan($chat_id, "❌ Contoh: ranking indeks lq45");
        return;
    }

    $namaIndeks = strtolower(trim(implode(" ", array_slice($parts, 2))));

    $alias = [
        "lq 45" => "lq45",
        "esg" => "esg leaders"
    ];

    $namaIndeks = $alias[$namaIndeks] ?? $namaIndeks;

    $scoring = new ScoringService();

    $ranking = $scoring->rankingIndeks($namaIndeks);
    $response = $scoring->formatRanking("INDEKS " . $namaIndeks, $ranking);

    kirimPesan($chat_id, $response);
}
if ($text == "MENU" || $text == "HELP") {

    $menu = "
🤖 *STOCKMIND BOT*

Berikut perintah yang bisa kamu gunakan:

📊 ANALISIS SAHAM
Ketik:
analisis saham bbri

📈 RANKING SEKTOR
Ketik:
ranking sektor bank
ranking sektor energi
ranking sektor barang baku

📊 RANKING INDEKS
Ketik:
ranking indeks lq45
ranking indeks idx30
ranking indeks jii
ranking indeks issi

💡 Tips:
- Gunakan huruf bebas (besar/kecil tidak masalah)
- Bisa pakai spasi (contoh: barang baku)

🚀 Contoh cepat:
analisis saham bbca
ranking sektor keuangan
ranking indeks lq45
";

    kirimPesan($chat_id, $menu);
}
if ($text == "HI" || $text == "HALO") {
    kirimPesan($chat_id, "Halo 👋\nKetik MENU untuk mulai.");
}