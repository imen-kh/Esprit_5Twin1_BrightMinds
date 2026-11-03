# NutriTrace

[![CI](https://github.com/imen-kh/Esprit_5Twin1_BrightMinds/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/imen-kh/Esprit_5Twin1_BrightMinds/actions/workflows/ci.yml)
[![CD](https://github.com/imen-kh/Esprit_5Twin1_BrightMinds/actions/workflows/cd.yml/badge.svg)](https://github.com/imen-kh/Esprit_5Twin1_BrightMinds/actions/workflows/cd.yml)

Plateforme tunisienne de traçabilité alimentaire « de la ferme à l'assiette » (Laravel 12, PHP 8.2, MySQL, Vite) :
catalogue, traçabilité des lots avec QR code et IA (Gemini), certifications, bilans environnementaux,
commandes et livraisons, réclamations, gestion des utilisateurs (vérification d'email, 2FA, rôles Spatie).

Projet Esprit — 5Twin1 — équipe BrightMinds.

## Lancer le projet

### En local (XAMPP)

Prérequis : PHP 8.2 (XAMPP) avec `pdo_mysql`, `pdo_sqlite`, `gd`, `zip`, `intl` ; Composer 2 ; Node 20 ; MySQL.

```bash
composer install
npm ci
cp .env.example .env          # puis renseigner DB_*, MAIL_* (Mailtrap), GEMINI_API_KEY
php artisan key:generate
php artisan migrate --seed    # base MySQL locale (ex. nutritrace_dev)
npm run build                 # ou « npm run dev » pendant le développement
php artisan serve             # http://127.0.0.1:8000
```

Comptes de démonstration (mot de passe `password`) : `admin@nutritrace.tn`, `producteur@nutritrace.tn`,
`transformateur@nutritrace.tn`, `distributeur@nutritrace.tn`, `client@nutritrace.tn`.

### Avec Docker

```bash
# APP_KEY est lue dans l'environnement (ou dans le .env local, seulement pour docker compose)
export APP_KEY=$(php artisan key:generate --show)
SEED_DATABASE=true docker compose up -d --build
```

| Service | Adresse |
|---|---|
| Application | http://localhost:8000 |
| phpMyAdmin | http://localhost:8080 |
| Mailpit (emails) | http://localhost:8025 |

Arrêt : `docker compose down` (ajouter `-v` pour effacer aussi la base).

## Tests et qualité

```bash
php artisan test     # SQLite en mémoire (phpunit.xml) : la base MySQL n'est jamais touchée
composer lint        # Pint (preset laravel) sur les fichiers contrôlés
composer lint:fix    # correction automatique de ces fichiers
```

## Intégration et déploiement continus

Chaque push et chaque Pull Request vers `main` lancent la CI (Pint → PHPUnit → build Docker) ;
une fois `main` validée, la CD publie l'image sur `ghcr.io` et déclenche le déploiement.
Détails, diagrammes et secrets : [docs/DEVOPS.md](docs/DEVOPS.md).
