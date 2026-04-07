<?php
function getDataSaham($kode) {
    $kode = escapeshellarg($kode);
    
    $dir = __DIR__;
    $output = shell_exec("cd $dir && py get_stock.py $kode 2>&1");
    
    file_put_contents("log_python.txt", $output . PHP_EOL, FILE_APPEND);
    
    if (empty($output)) return null;
    
    $data = json_decode($output, true);
    return $data;
}