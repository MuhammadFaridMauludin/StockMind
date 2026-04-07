import yfinance as yf
import sys
import json
import math

kode = sys.argv[1].strip().upper()
kode = kode.replace("SAHAM", "").strip()

if not kode.endswith(".JK"):
    kode = kode + ".JK"

stock = yf.Ticker(kode)
hist = stock.history(period="3mo")

if hist.empty:
    print(json.dumps({"error": "Data historis tidak tersedia"}))
    sys.exit()

# ======================
# HITUNG RSI
# ======================
delta = hist['Close'].diff()
gain = (delta.where(delta > 0, 0)).rolling(window=14).mean()
loss = (-delta.where(delta < 0, 0)).rolling(window=14).mean()
rs = gain / loss
rsi = 100 - (100 / (1 + rs))

rsi_last = float(rsi.iloc[-1]) if not rsi.empty else 50.0

# ======================
# MA50
# ======================
ma50_series = hist['Close'].rolling(window=50).mean()
ma50_last = ma50_series.iloc[-1]
ma50 = float(ma50_last) if not math.isnan(ma50_last) else float(hist['Close'].mean())

# ======================
# DATA FUNDAMENTAL
# ======================
data = stock.info

price = (
    data.get("regularMarketPrice") or
    data.get("currentPrice") or
    data.get("previousClose") or
    float(hist['Close'].iloc[-1])
)

der_raw = float(data.get("debtToEquity") or 0)
der = der_raw / 100 if der_raw > 10 else der_raw

div_raw = float(data.get("dividendYield") or 0)
div_yield = div_raw / 100 if div_raw > 1 else div_raw

result = {
    "symbol": kode,
    "price": round(float(price), 0),
    "eps":   round(float(data.get("trailingEps") or 0), 2),
    "per":   round(float(data.get("trailingPE") or 0), 2),
    "pbv":   round(float(data.get("priceToBook") or 0), 2),
    "roe":   round(float(data.get("returnOnEquity") or 0), 4),
    "der":   round(der, 2),
    "div_yield": round(div_yield, 4),
    "rsi":   round(rsi_last, 2),
    "ma50":  round(ma50, 2)
}
print(json.dumps(result))