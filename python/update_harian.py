import yfinance as yf
import mysql.connector
from datetime import datetime

# ======================
# DB CONNECT
# ======================
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="stockmindd"
)
cursor = conn.cursor()

# ======================
# LIST SAHAM
# ======================
kode_list = [
    "ADRO","PTBA","BYAN","ITMG","INDY",
    "MEDC","PGAS","HRUM","MBAP","DOID",
    "BUMI","DEWA"
]

today = datetime.now().date()

# ======================
# UPDATE HARGA HARIAN
# ======================
for kode in kode_list:

    kode_yf = kode + ".JK"
    print("Update:", kode_yf)

    stock = yf.Ticker(kode_yf)
    hist = stock.history(period="5d")

    if hist.empty:
        continue

    last = hist.iloc[-1]

    try:
        cursor.execute("""
            INSERT IGNORE INTO riwayat_harga
            (kode, tanggal, harga_tutup, volume, open, high, low)
            VALUES (%s,%s,%s,%s,%s,%s,%s)
        """, (
            kode,
            today,
            float(last['Close']),
            int(last['Volume']),
            float(last['Open']),
            float(last['High']),
            float(last['Low'])
        ))
    except Exception as e:
        print("Error:", e)

# ======================
# EVALUASI PREDIKSI (7 HARI)
# ======================
print("Evaluasi prediksi...")

cursor.execute("""
    SELECT id, kode, harga_awal, tanggal
    FROM prediksi_harga
    WHERE dievaluasi = 0
    AND tanggal <= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
""")

rows = cursor.fetchall()

for row in rows:
    id_, kode, harga_awal, tanggal = row

    # ambil harga terbaru
    cursor.execute("""
        SELECT harga_tutup 
        FROM riwayat_harga
        WHERE kode = %s
        ORDER BY tanggal DESC
        LIMIT 1
    """, (kode,))

    result = cursor.fetchone()

    if not result:
        continue

    harga_now = result[0]

    hasil = 1 if harga_now > harga_awal else 0

    cursor.execute("""
        UPDATE prediksi_harga
        SET 
            harga_aktual = %s,
            hasil = %s,
            dievaluasi = 1,
            tanggal_evaluasi = NOW()
        WHERE id = %s
    """, (harga_now, hasil, id_))

conn.commit()

cursor.close()
conn.close()

print("✅ Update & evaluasi selesai")