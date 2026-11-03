import json
import sys

from forecast_model import generate_forecast


def main():
    request = json.load(sys.stdin)
    result = generate_forecast(request['history'], int(request['horizon']))
    print(json.dumps(result, ensure_ascii=False))


if __name__ == '__main__':
    main()
