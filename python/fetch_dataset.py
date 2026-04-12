import yfinance as yf
import pandas as pd

kode_list = [
    "BBRI.JK", "BBCA.JK", "BMRI.JK", "TLKM.JK", "ASII.JK",
    "ADRO.JK", "UNVR.JK", "ICBP.JK", "ANTM.JK", "INDF.JK",
    "BYAN.JK", "GOTO.JK", "BREN.JK", "AMMN.JK", "MDKA.JK",
    "SMGR.JK", "GGRM.JK", "HMSP.JK", "KLBF.JK", "SIDO.JK"
]

all_data = []

for kode in kode_list:

    print("Ambil:", kode)

    stock = yf.Ticker(kode)
    hist = stock.history(period="2y")

    if hist.empty:
        print("❌ Skip:", kode)
        continue

    # ======================
    # PRICE ACTION
    # ======================
    hist['body']         = hist['Close'] - hist['Open']
    hist['range']        = hist['High'] - hist['Low']
    hist['upper_shadow'] = hist['High'] - hist[['Close', 'Open']].max(axis=1)
    hist['lower_shadow'] = hist[['Close', 'Open']].min(axis=1) - hist['Low']

    # ✅ Hindari division by zero SEBELUM normalisasi
    hist['range'] = hist['range'].replace(0, 1)

    # ======================
    # NORMALISASI
    # ======================
    hist['body_ratio']  = hist['body'] / hist['range']
    hist['upper_ratio'] = hist['upper_shadow'] / hist['range']
    hist['lower_ratio'] = hist['lower_shadow'] / hist['range']

    # ======================
    # ATR
    # ======================
    hist['tr'] = hist[['High', 'Low', 'Close']].apply(
        lambda x: max(
            x['High'] - x['Low'],
            abs(x['High'] - x['Close']),
            abs(x['Low']  - x['Close'])
        ),
        axis=1
    )
    hist['atr'] = hist['tr'].rolling(14).mean()

    # ======================
    # RETURN
    # ======================
    hist['return_1d']  = hist['Close'].pct_change()
    hist['return_5d']  = hist['Close'].pct_change(5)
    hist['return_10d'] = hist['Close'].pct_change(10)
    hist['return_20d'] = hist['Close'].pct_change(20)

    # ======================
    # MOVING AVERAGE
    # ======================
    hist['ma20'] = hist['Close'].rolling(20).mean()
    hist['ma50'] = hist['Close'].rolling(50).mean()

    # DISTANCE TO MA
    hist['dist_ma20'] = (hist['Close'] - hist['ma20']) / hist['ma20']
    hist['dist_ma50'] = (hist['Close'] - hist['ma50']) / hist['ma50']

    # ======================
    # VOLATILITY
    # ======================
    hist['volatility'] = hist['Close'].rolling(20).std()

    # ======================
    # VOLUME
    # ======================
    hist['volume_ma20']  = hist['Volume'].rolling(20).mean()
    hist['volume_spike'] = hist['Volume'] / hist['volume_ma20']  # ✅ hapus duplikat volume_ratio
    
    # ======================
    # RSI
    # ======================
    delta = hist['Close'].diff()
    gain  = delta.clip(lower=0).rolling(14).mean()
    loss  = (-delta.clip(upper=0)).rolling(14).mean()
    rs    = gain / loss
    hist['rsi']        = 100 - (100 / (1 + rs))
    hist['rsi_change'] = hist['rsi'].diff()

    # ======================
    # TARGET
    # ======================
    hist['target'] = (hist['Close'].shift(-7) > hist['Close']).astype(int)

    # ======================
    # CLEAN
    # ======================
    hist = hist.dropna()

    hist['kode'] = kode
    all_data.append(hist)

# ======================
# GABUNG & SAVE
# ======================
if not all_data:
    print("❌ Tidak ada data yang berhasil diambil")
else:
    df = pd.concat(all_data)
    df.to_csv("data/dataset.csv", index=False)
    print("✅ Total dataset:", len(df))