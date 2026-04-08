<?php
class StockService{
function getDataSaham($kode) {
    $kode = escapeshellarg($kode);
    
    $pythonDir = "C:/laragon/www/bot/python";
    $output = shell_exec("cd $pythonDir && py fetch.py $kode 2>&1");
    
    file_put_contents("C:/laragon/www/bot/storage/log_python.txt", $output . PHP_EOL, FILE_APPEND);
    
    if (empty($output)) return null;
    
    $data = json_decode($output, true);
    return $data;
}
}