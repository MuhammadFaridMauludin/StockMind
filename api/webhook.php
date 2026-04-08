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

    $namaSektor = strtolower(end($parts));

    file_put_contents("C:/laragon/www/bot/storage/log2.txt", "SEKTOR: $namaSektor\n", FILE_APPEND);

    $scoring = new ScoringService();

    $ranking = $scoring->rankingSektor($namaSektor);
    $response = $scoring->formatRanking($namaSektor, $ranking);

    kirimPesan($chat_id, $response);
}
