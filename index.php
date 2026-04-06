<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require "config.php";
require "function.php";

$update = json_decode(file_get_contents("php://input"), true);

if (!$update) {
    exit("No data from Telegram");
}

$chat_id = $update["message"]["chat"]["id"] ?? null;
kirimPesan($chat_id, "");

$text = strtoupper($update["message"]["text"] ?? "");

if (strpos($text, "ANALISIS") !== false) {
    $kode = trim(str_replace("ANALISIS", "", $text));

    $hasil = analisisSaham($kode);

    kirimPesan($chat_id, $hasil);
}