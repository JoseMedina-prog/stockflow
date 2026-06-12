#!/bin/sh
set -e

cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
    echo ">> Instalando dependencias de Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -x node_modules/.bin/vite ]; then
    echo ">> Instalando dependencias de npm..."
    if [ -f package-lock.json ]; then
        npm ci
    else
        npm install
    fi
fi

if [ ! -f public/build/manifest.json ]; then
    echo ">> Compilando assets frontend..."
    npm run build
fi

case "${1:-serve}" in
    serve)
        exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
        ;;
    dev)
        npm run dev -- --host 0.0.0.0 --port 5173 &
        exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
        ;;
    *)
        exec "$@"
        ;;
esac
