#!/usr/bin/env bash
set -e

echo "=========================================="
echo "  Instalador automatico Ynentario Docker  "
echo "=========================================="

echo ">> 1. Levantando contenedores Docker..."
docker compose up -d --build

echo ">> 2. Instalando dependencias de Composer..."
docker compose exec -T app composer install

echo ">> 3. Generando APP_KEY de Laravel..."
docker compose exec -T app php artisan key:generate --force

echo ">> 4. Ejecutando migraciones y seeders iniciales..."
docker compose exec -T app php artisan migrate:fresh --seed --force

echo ">> 5. Instalando dependencias de frontend y compilando..."
docker compose exec -T app npm install
docker compose exec -T app npm run build

echo "=========================================="
echo "  ¡Instalacion completada con exito!      "
echo "  Accede en: http://localhost:8000        "
echo "  Usuario:   admin@ynentario.local        "
echo "  Password:  password                     "
echo "=========================================="
