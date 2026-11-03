from datetime import date, timedelta
from hashlib import sha256
import json
from math import sqrt

from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error

MODEL_CACHE = {}


def _features(points, index, values):
    weekday = points[index].weekday() if index < len(points) else points[-1].weekday()
    recent = values[max(0, index - 3):index] or [0.0]
    lag_1 = values[index - 1] if index > 0 else values[0]
    lag_3 = values[index - 3] if index >= 3 else sum(recent) / len(recent)
    return [index, weekday, lag_1, lag_3, sum(recent) / len(recent)]


def generate_forecast(history, horizon):
    points = sorted(
        [(date.fromisoformat(item['date']), float(item['quantity'])) for item in history],
        key=lambda item: item[0],
    )
    if len(points) < 7 or len({item[0] for item in points}) < 5:
        return {'status': 'insufficient', 'message': 'Historique insuffisant pour entraîner Random Forest.'}

    dates = [item[0] for item in points]
    values = [max(0.0, item[1]) for item in points]
    train_end = max(4, int(len(points) * 0.8))
    if train_end >= len(points):
        train_end = len(points) - 1

    rows = [_features(dates, index, values) for index in range(1, len(points))]
    targets = values[1:]
    split = max(3, min(train_end - 1, len(rows) - 1))
    signature = sha256(json.dumps([(item[0].isoformat(), item[1]) for item in points]).encode()).hexdigest()
    cached = MODEL_CACHE.get(signature)

    if cached is None:
        model = RandomForestRegressor(n_estimators=120, random_state=42, min_samples_leaf=1)
        model.fit(rows[:split], targets[:split])
        validation = model.predict(rows[split:])
        actual = targets[split:]
        cached = {
            'model': model,
            'mae': mean_absolute_error(actual, validation) if actual else 0.0,
            'rmse': sqrt(mean_squared_error(actual, validation)) if actual else 0.0,
        }
        MODEL_CACHE[signature] = cached

    future_values = values[:]
    predictions = []
    for offset in range(1, horizon + 1):
        future_date = dates[-1] + timedelta(days=offset)
        index = len(points) + offset - 1
        recent = future_values[max(0, index - 3):index] or [0.0]
        lag_1 = future_values[index - 1]
        lag_3 = future_values[index - 3] if index >= 3 else sum(recent) / len(recent)
        row = [index, future_date.weekday(), lag_1, lag_3, sum(recent) / len(recent)]
        quantity = max(0.0, float(cached['model'].predict([row])[0]))
        future_values.append(quantity)
        predictions.append({'date': future_date.isoformat(), 'quantity': round(quantity, 3)})

    return {
        'status': 'ok',
        'predictions': predictions,
        'metrics': {'mae': round(float(cached['mae']), 3), 'rmse': round(float(cached['rmse']), 3)},
    }
