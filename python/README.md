# Prévision Python NutriTrace

Le mode recommandé pour Windows est le service HTTP Python séparé. Il évite les conflits
de chargement de scikit-learn dans le processus web PHP.

```powershell
cd python
py -3 -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
python -m uvicorn forecast_api:app --host 127.0.0.1 --port 8001
```

Puis configurer dans `.env` :

```dotenv
FORECAST_MODE=http
FORECAST_PYTHON=auto
FORECAST_API_URL=http://127.0.0.1:8001/predict
FORECAST_API_TIMEOUT=15
```
