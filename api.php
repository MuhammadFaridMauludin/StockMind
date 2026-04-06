<?php
function getDataSaham($kode) {
    $output = shell_exec("python get_stock.py $kode");

    return json_decode($output, true);
}
