#!/bin/sh
set -e

echo "Running database migrations..."
php /var/www/html/migrate.php

exec apache2-foreground
