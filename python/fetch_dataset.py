import yfinance as yf
import pandas as pd

kode = "BBRI.JK"

stock = yf.Ticker(kode)
hist = stock.history(period="6mo")

if hist.empty:
    print("❌ Data kosong")
    exit()

# ======================
# FEATURE
# ======================
hist['return_1d'] = hist['Close'].pct_change()
hist['return_5d'] = hist['Close'].pct_change(5)

hist['ma20'] = hist['Close'].rolling(20).mean()
hist['ma50'] = hist['Close'].rolling(50).mean()

hist['dist_ma20'] = (hist['Close'] - hist['ma20']) / hist['ma20']

# RSI
delta = hist['Close'].diff()
gain = delta.clip(lower=0).rolling(14).mean()
loss = (-delta.clip(upper=0)).rolling(14).mean()
rs = gain / loss
hist['rsi'] = 100 - (100 / (1 + rs))

# volume
hist['volume_ratio'] = hist['Volume'] / hist['Volume'].rolling(20).mean()

# target
hist['target'] = (hist['Close'].shift(-1) > hist['Close']).astype(int)

# cleanup
hist = hist.dropna()

# save
hist.to_csv("data/dataset.csv")

print("✅ Dataset berhasil dibuat:", len(hist), "baris")