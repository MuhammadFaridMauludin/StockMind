import yfinance as yf
import sys
import json
import math

# ======================
# INPUT
# ======================
kode = sys.argv[1].strip().upper()
kode = kode.replace("SAHAM", "").strip()

if not kode.endswith(".JK"):
    kode += ".JK"

stock = yf.Ticker(kode)
hist = stock.history(period="3mo")

if hist.empty:
    print(json.dumps({"error": "Data historis tidak tersedia"}))
    sys.exit()

# ======================
# HELPER
# ======================
def safe_float(value, default=0.0):
    try:
        return float(value)
    except:
        return default

# ======================
# MOVING AVERAGE
# ======================
ma20_series = hist['Close'].rolling(window=20).mean()
ma50_series = hist['Close'].rolling(window=50).mean()

ma20 = safe_float(ma20_series.iloc[-1], hist['Close'].mean())
ma50 = safe_float(ma50_series.iloc[-1], hist['Close'].mean())

last_price = float(hist['Close'].iloc[-1])

# ======================
# TREND
# ======================
if last_price > ma20 and ma20 > ma50:
    trend = "uptrend"
elif last_price < ma20 and ma20 < ma50:
    trend = "downtrend"
else:
    trend = "sideways"

# ======================
# SUPPORT & RESISTANCE
# ======================
support = safe_float(hist['Low'].rolling(window=20).min().iloc[-1])
resistance = safe_float(hist['High'].rolling(window=20).max().iloc[-1])

# ======================
# RSI
# ======================
delta = hist['Close'].diff()
gain = delta.clip(lower=0).rolling(window=14).mean()
loss = (-delta.clip(upper=0)).rolling(window=14).mean()

rs = gain / loss
rsi = 100 - (100 / (1 + rs))

rsi_last = safe_float(rsi.iloc[-1], 50.0)

# ======================
# RSI SIGNAL
# ======================
if rsi_last < 30:
    rsi_signal = "oversold"
elif rsi_last < 50:
    rsi_signal = "weak"
elif rsi_last < 70:
    rsi_signal = "strong"
else:
    rsi_signal = "overbought"

# ======================
# VOLUME
# ======================
vol_avg = hist['Volume'].rolling(window=20).mean().iloc[-1]
vol_last = hist['Volume'].iloc[-1]

if vol_last > vol_avg:
    volume_signal = "high"
else:
    volume_signal = "low"

# ======================
# FUNDAMENTAL
# ======================
data = stock.info

price = (
    data.get("regularMarketPrice")
    or data.get("currentPrice")
    or data.get("previousClose")
    or last_price
)

der_raw = safe_float(data.get("debtToEquity"), 0)
der = der_raw / 100 if der_raw > 10 else der_raw

div_raw = safe_float(data.get("dividendYield"), 0)
div_yield = div_raw / 100 if div_raw > 1 else div_raw

# ======================
# OUTPUT
# ======================
result = {
    "symbol": kode,
    "price": round(price, 0),

    # FUNDAMENTAL
    "eps": round(safe_float(data.get("trailingEps")), 2),
    "per": round(safe_float(data.get("trailingPE")), 2),
    "pbv": round(safe_float(data.get("priceToBook")), 2),
    "roe": round(safe_float(data.get("returnOnEquity")), 4),
    "der": round(der, 2),
    "div_yield": round(div_yield, 4),

    # TECHNICAL
    "rsi": round(rsi_last, 2),
    "rsi_signal": rsi_signal,
    "ma20": round(ma20, 2),
    "ma50": round(ma50, 2),
    "trend": trend,
    "support": round(support, 0),
    "resistance": round(resistance, 0),
    "volume_signal": volume_signal
}

print(json.dumps(result))