#!/usr/bin/env bash
# Instalación local de UT Semilleros (RNF01): deja el entorno Docker listo desde un clon limpio.
# Uso: ./scripts/dev-setup.sh      (idempotente: se puede volver a correr)
set -euo pipefail
cd "$(dirname "$0")/.."

echo "▶ 1/5 Configuración del backend (api/.env)"
[ -f api/.env ] || cp api/.env.example api/.env

echo "▶ 2/5 Contenedores (frontend :8080, API :8000, MySQL :3306, phpMyAdmin :8081)"
docker compose up -d --build

echo "▶ 3/5 Dependencias PHP"
docker compose exec -T api composer install --no-interaction --prefer-dist

echo "▶ 4/5 Clave de la app y permisos de escritura de Laravel"
grep -q '^APP_KEY=base64' api/.env || docker compose exec -T api php artisan key:generate --force
docker compose exec -T api chmod -R a+rwX storage bootstrap/cache

echo "▶ 5/5 Base de datos (espera a MySQL, migra y siembra datos de ejemplo)"
for i in $(seq 1 30); do
  docker compose exec -T api php artisan db:show >/dev/null 2>&1 && break
  sleep 2
done
docker compose exec -T api php artisan migrate --force
USERS=$(docker compose exec -T api php artisan tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tail -1 | tr -dc '0-9')
if [ "${USERS:-0}" = "0" ]; then
  docker compose exec -T api php artisan db:seed --force
else
  echo "  (ya hay ${USERS} usuarios: no se vuelve a sembrar)"
fi

echo "✅ Listo: http://localhost:8080  (usuarios de ejemplo en api/database/seeders/UserSeeder.php)"
