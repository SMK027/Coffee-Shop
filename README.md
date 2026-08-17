# Coffee Shop

Application complète de gestion pour coffee shop avec:

- un site web public (menu, contact, fidélité)
- un back-office salarié (commandes, remboursements, bons, superviseurs, logs)
- une application mobile React Native connectée à l'API

## Sommaire

- Présentation
- Stack technique
- Architecture du projet
- Démarrage rapide (Docker développement)
- Configuration reCAPTCHA v3
- Démarrage production (Docker)
- Application mobile
- Commandes utiles
- Tests et qualité
- Dépannage

## Présentation

Le projet couvre les besoins métier suivants:

- gestion du menu et des boissons
- prise de commandes et suivi de statuts
- remboursements et moyens de paiement
- gestion des bons d'achat
- fidélité (cartes, PIN, points, offres, réductions)
- gestion des salariés et superviseurs
- historisation des actions (journal d'activité)
- flux de supervision des opérations sensibles

## Stack technique

- Backend: Laravel 13, PHP 8.3
- Auth API mobile: JWT (php-open-source-saver/jwt-auth)
- Front web: Blade, Tailwind CSS, Vite, Alpine.js
- Base de données: MariaDB/MySQL
- Mobile: Expo / React Native (TypeScript)
- Déploiement: Docker, Docker Compose, Traefik (prod)

## Architecture du projet

- `app/Http/Controllers`: contrôleurs web et API
- `app/Models`: modèles Eloquent
- `app/Services`: services applicatifs (logs, captcha, etc.)
- `resources/views`: vues Blade (visiteur + employé)
- `routes/web.php`: routes web
- `routes/api.php`: routes API mobile
- `mobile/`: application mobile Expo
- `docker-compose.dev.yml`: stack locale de développement
- `docker-compose.yml`: stack de production

## Démarrage rapide (Docker développement)

### Prérequis

- Docker + Docker Compose v2

### Étapes

1. Copier l'environnement:

```bash
cp .env.example .env
```

2. Renseigner les variables importantes dans `.env`:

- base de données
- SMTP
- reCAPTCHA v3 (voir section dédiée)

3. Lancer la stack locale:

```bash
docker compose -f docker-compose.dev.yml up -d --build
```

4. Accéder aux services:

- App web: http://localhost:8099
- phpMyAdmin: http://localhost:8100
- Mailpit UI: http://localhost:8026
- Mailpit SMTP: localhost:1026

### Notes importantes

- Le conteneur app exécute les migrations au démarrage.
- Les caches Laravel sont reconstruits automatiquement via l'entrypoint.

## Configuration reCAPTCHA v3

Le projet utilise Google reCAPTCHA v3 pour les formulaires publics et la connexion salarié.

### Générer les clés API Google reCAPTCHA v3

1. Ouvrir la console Google reCAPTCHA: https://www.google.com/recaptcha/admin/create
2. Se connecter avec le compte Google de gestion.
3. Donner un nom explicite à la clé (exemple: Coffee Shop Dev).
4. Choisir `Score based (v3)`.
5. Ajouter les domaines autorisés:
	- local: `localhost`
	- dev/prod: vos domaines réels
6. Créer la clé et récupérer:
	- `Site key` (publique)
	- `Secret key` (privée)

### Variables d'environnement

```env
RECAPTCHA_SITE_KEY=...
RECAPTCHA_SECRET_KEY=...
RECAPTCHA_MIN_SCORE=0.5
```

`RECAPTCHA_MIN_SCORE` est ajustable entre 0.0 et 1.0.

### Point Docker important

Le fichier `.env` est exclu de l'image via `.dockerignore`.
Les variables reCAPTCHA doivent être injectées au runtime (déjà prévu dans les fichiers compose).

Après modification des variables, redémarrer/rebuilder les services puis régénérer le cache config:

```bash
php artisan optimize:clear
php artisan config:cache
```

## Démarrage production (Docker)

Le fichier `docker-compose.yml` est prévu pour un déploiement derrière Traefik.

### Prérequis réseau

- réseau Docker `proxy` existant
- réseau Docker `db_internal` existant

### Lancement

```bash
docker compose up -d --build
```

### Variables clés à définir

- `APP_URL`, `APP_HOST`
- `APP_KEY`
- `DB_*`
- `SMTP_*`
- `RECAPTCHA_*`
- `JWT_SECRET` (optionnel, auto-généré si absent et persistance via storage)

## Application mobile

Le dossier `mobile/` contient l'app Expo / React Native.

### Démarrage en dev mobile

```bash
cd mobile
npm install
npm run start
```

### Build APK local

Script fourni:

```bash
cd mobile
./build-apk.sh
```

L'APK final est copié vers:

- `mobile/CoffeeShop-release.apk`

## Commandes utiles

### Stack locale Docker

```bash
docker compose -f docker-compose.dev.yml up -d
docker compose -f docker-compose.dev.yml down
docker compose -f docker-compose.dev.yml logs -f app
```

### Laravel (dans le conteneur app local)

```bash
docker exec -it app_web php artisan migrate --force
docker exec -it app_web php artisan db:seed --force
docker exec -it app_web php artisan route:list
docker exec -it app_web php artisan test
```

### Front web

```bash
npm install
npm run dev
npm run build
```

## Tests et qualité

- Tests backend: `php artisan test`
- Lint syntaxe PHP: `php -l <fichier>`
- Build front: `npm run build`

## Dépannage

### Message: reCAPTCHA_SITE_KEY manquante

Vérifier:

- présence des variables `RECAPTCHA_*` dans l'environnement du conteneur
- cache config Laravel à jour
- domaine autorisé dans Google reCAPTCHA

Commande de diagnostic:

```bash
printenv | grep RECAPTCHA
php artisan tinker --execute="dump(config('services.recaptcha'))"
```

### Erreurs de permissions storage/logs

Si des erreurs d'écriture apparaissent, vérifier les permissions sur:

- `storage/`
- `bootstrap/cache/`

## Licence

Projet interne Coffee Shop.
