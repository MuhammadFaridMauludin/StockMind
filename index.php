<?php
require "config.php";
require "function.php";

$update = json_decode(file_get_contents("php://input"), true);

if (!$update) exit("No data");

$chat_id = $update["message"]["chat"]["id"] ?? null;
$text = strtoupper(trim($update["message"]["text"] ?? ""));

file_put_contents("log2.txt", "TEXT: $text" . PHP_EOL, FILE_APPEND);

if (strpos($text, "ANALISIS SAHAM") !== false) {
    // Hapus "ANALISIS SAHAM" sekaligus, bukan cuma "ANALISIS"
    $kode = trim(str_replace("ANALISIS SAHAM", "", $text));

    file_put_contents("log2.txt", "KODE: $kode" . PHP_EOL, FILE_APPEND);

    $hasil = analisisSaham($kode);
    kirimPesan($chat_id, $hasil);
}