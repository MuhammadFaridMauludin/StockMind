def build_features(row):
    return [
        row['return_1d'],
        row['return_5d'],
        row['rsi'],
        row['macd'],
        row['macd_hist'],
        row['dist_ma20'],
        row['dist_support'],
        row['volume_ratio']
    ]