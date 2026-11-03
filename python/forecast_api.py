from datetime import date
from typing import List

from fastapi import FastAPI
from pydantic import BaseModel, Field

from forecast_model import generate_forecast

app = FastAPI(title='NutriTrace Production Forecast API', version='1.0.0')


class HistoryPoint(BaseModel):
    date: date
    quantity: float = Field(ge=0)


class ForecastRequest(BaseModel):
    history: List[HistoryPoint]
    horizon: int = Field(ge=7, le=30)


@app.post('/predict')
def predict(request: ForecastRequest):
    return generate_forecast([
        {'date': point.date.isoformat(), 'quantity': point.quantity}
        for point in request.history
    ], request.horizon)
