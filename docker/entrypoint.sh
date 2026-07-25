#!/bin/sh
set -e

if [ -f /var/www/html/migrate.php ]; then
    echo "Running database migrations..."
    php /var/www/html/migrate.php || true
else
    echo "migrate.php not present in image — skipping migrations."
fi

exec apache2-foreground
