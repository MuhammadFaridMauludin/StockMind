<?php
require_once __DIR__ . "/../config/Database.php";
require_once __DIR__ . "/StockService.php";

class DataService {

    private $conn;
    private $stockService;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->stockService = new StockService();
    }

    public function getData($kode) {

        // 🔹 ambil dari DB
        $data = $this->getDataDariDB($kode);

        // 🔹 fallback ke API kalau perlu
        if (!$data || (!$this->isFresh($data['updated_at']) && $this->isMarketOpen())) {

            file_put_contents(__DIR__ . "/../storage/log2.txt", "DATASERVICE REFRESH: $kode\n", FILE_APPEND);

            $data = $this->stockService->getDataSaham($kode);

            if (!$data || isset($data["error"])) return null;

            $this->simpanKeDB($kode, $data);
        }

        return $data;
    }

    private function getDataDariDB($kode) {
        $stmt = $this->conn->prepare("SELECT * FROM data_saham WHERE kode = ?");
        $stmt->bind_param("s", $kode);
        $stmt->execute();

        $data = $stmt->get_result()->fetch_assoc();

        if ($data) {
            $data['price'] = $data['harga']; // mapping penting
        }

        return $data;
    }

    private function simpanKeDB($kode, $data) {

        $stmt = $this->conn->prepare("
            INSERT INTO data_saham 
            (kode, harga, eps, per, roe, der, div_yield, rsi, ma20, ma50, support, volume_signal, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            harga=VALUES(harga),
            eps=VALUES(eps),
            per=VALUES(per),
            roe=VALUES(roe),
            der=VALUES(der),
            div_yield=VALUES(div_yield),
            rsi=VALUES(rsi),
            ma20=VALUES(ma20),
            ma50=VALUES(ma50),
            support=VALUES(support),
            volume_signal=VALUES(volume_signal),
            updated_at=NOW()
        ");

        $stmt->bind_param(
            "sdddddddddds",
            $kode,
            $data['price'],
            $data['eps'],
            $data['per'],
            $data['roe'],
            $data['der'],
            $data['div_yield'],
            $data['rsi'],
            $data['ma20'],
            $data['ma50'],
            $data['support'],
            $data['volume_signal']
        );

        $stmt->execute();
    }

    private function isFresh($updated_at) {
        if (!$updated_at) return false;
        return (time() - strtotime($updated_at)) < 300;
    }

    private function isMarketOpen() {
        $hour = date('H');
        return ($hour >= 9 && $hour <= 16);
    }
}