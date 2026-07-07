#!/bin/bash
set -e

# Fixer les permissions Laravel
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Générer APP_KEY si absente
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
    echo "[entrypoint] APP_KEY généré."
fi

# Générer JWT_SECRET si absent (utilisé par l'API mobile via jwt-auth).
# On persiste la clé dans storage/ pour qu'elle survive aux redémarrages
# (sinon tous les tokens seraient invalidés à chaque restart).
if [ -z "$JWT_SECRET" ]; then
    JWT_SECRET_FILE=/var/www/html/storage/jwt.secret
    if [ ! -f "$JWT_SECRET_FILE" ]; then
        php -r "echo bin2hex(random_bytes(32));" > "$JWT_SECRET_FILE"
        chown www-data:www-data "$JWT_SECRET_FILE"
        chmod 600 "$JWT_SECRET_FILE"
        echo "[entrypoint] JWT_SECRET généré et persisté dans storage/jwt.secret."
    fi
    export JWT_SECRET="$(cat "$JWT_SECRET_FILE")"
fi

# Lien symbolique storage → public/storage
php artisan storage:link --no-interaction 2>/dev/null || true

# En local, reconstruire les assets si le dossier build est absent/incomplet.
# Cela évite un affichage cassé quand un volume Docker masque public/build.
if [ "$APP_ENV" = "local" ]; then
    if [ ! -s /var/www/html/public/build/manifest.json ] || [ ! -d /var/www/html/public/build/assets ] || [ -z "$(ls -A /var/www/html/public/build/assets 2>/dev/null)" ]; then
        echo "[entrypoint] Build assets local manquant/incomplet — reconstruction..."
        npm install --ignore-scripts
        npm run build
        echo "[entrypoint] Assets Vite reconstruits."
    fi
fi

# Exécuter les migrations
echo "[entrypoint] Exécution des migrations..."
php artisan migrate --force
echo "[entrypoint] Migrations terminées."

# Seeding initial : exécuté une seule fois si la table users est vide
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | tr -d '[:space:]')
if [ "$USER_COUNT" = "0" ]; then
    echo "[entrypoint] Première installation détectée — exécution des seeders..."
    php artisan db:seed --force
    echo "[entrypoint] Seeders terminés."
else
    echo "[entrypoint] Base de données déjà peuplée (${USER_COUNT} utilisateur(s)) — seeders ignorés."
fi

# Optimiser les caches (config, routes, vues)
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "[entrypoint] Caches reconstruits."

# Configurer le scheduler Laravel (cron)
touch /var/log/laravel-scheduler.log
chown www-data:www-data /var/log/laravel-scheduler.log
printf "* * * * * www-data /usr/local/bin/php /var/www/html/artisan schedule:run >> /var/log/laravel-scheduler.log 2>&1\n" \
    > /etc/cron.d/laravel-scheduler
chmod 0644 /etc/cron.d/laravel-scheduler
cron
echo "[entrypoint] Scheduler Laravel démarré."

# Démarrer Apache
exec apache2-foreground
