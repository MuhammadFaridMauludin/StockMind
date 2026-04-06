import yfinance as yf
import sys
import json

kode = sys.argv[1] + ".JK"

stock = yf.Ticker(kode)
data = stock.info

result = {
    "symbol": kode,
    "price": round(data.get("regularMarketPrice", 0), 0),
    "pe": data.get("trailingPE"),
    "pbv": data.get("priceToBook"),
    "roe": data.get("returnOnEquity"),
    "eps": data.get("trailingEps")
}

print(json.dumps(result))