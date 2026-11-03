#!/bin/sh
# Démarrage du conteneur NutriTrace : attente de MySQL, migrations, lien de stockage et caches.
# Variables utiles : APP_KEY (obligatoire), DB_*, PORT (Render), RUN_MIGRATIONS, SEED_DATABASE.
set -e

cd /var/www/html

if [ -z "$APP_KEY" ]; then
    echo "[entrypoint] ERREUR : la variable APP_KEY est vide (générez-la avec « php artisan key:generate --show »)." >&2
    exit 1
fi

# Hébergeur qui impose un port (Render : variable PORT) : Apache écoute sur ce port
if [ -n "$PORT" ] && [ "$PORT" != "80" ]; then
    sed -ri "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
    sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

# Attente de MySQL (60 s au maximum)
if [ "${DB_CONNECTION:-mysql}" = "mysql" ]; then
    echo "[entrypoint] Attente de MySQL sur ${DB_HOST:-127.0.0.1}:${DB_PORT:-3306}…"
    essais=0
    until php -r '
        try {
            new PDO(
                "mysql:host=" . getenv("DB_HOST") . ";port=" . (getenv("DB_PORT") ?: 3306) . ";dbname=" . getenv("DB_DATABASE"),
                getenv("DB_USERNAME"),
                getenv("DB_PASSWORD"),
                [PDO::ATTR_TIMEOUT => 3]
            );
        } catch (Throwable $e) {
            exit(1);
        }
    ' >/dev/null 2>&1; do
        essais=$((essais + 1))
        if [ "$essais" -ge 30 ]; then
            echo "[entrypoint] ERREUR : MySQL injoignable après 60 s." >&2
            exit 1
        fi
        sleep 2
    done
    echo "[entrypoint] MySQL est prêt."
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

# Données de démonstration, uniquement si demandé explicitement (SEED_DATABASE=true)
if [ "${SEED_DATABASE:-false}" = "true" ]; then
    php artisan db:seed --force
fi

php artisan storage:link >/dev/null 2>&1 || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
