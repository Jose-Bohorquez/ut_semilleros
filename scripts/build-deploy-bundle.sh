#!/usr/bin/env bash
# Arma el paquete INCREMENTAL de despliegue: solo los archivos desplegables que cambiaron
# entre una base (lo último que está en producción) y HEAD. No toca producción.
#
# Uso:  scripts/build-deploy-bundle.sh <commit_base> [salida.tgz]
#   ej: scripts/build-deploy-bundle.sh 0a636ce
#
# Por qué un paquete y no rsync de todo el árbol: el árbol local tiene api/vendor con
# paquetes de desarrollo y datos locales que NO deben viajar a Hostinger. El paquete lleva
# exactamente los archivos del diff, con su ruta relativa (sin el bug de aplanado de scp).
set -euo pipefail

BASE="${1:?uso: $0 <commit_base> [salida.tgz]}"
OUT="${2:-/tmp/ut_deploy_$(date +%Y%m%d_%H%M).tgz}"
cd "$(dirname "$0")/.."

# Solo código de la aplicación. Quedan fuera tests, docs, vendor, storage, .env, SGAA, db, graphify.
DEPLOY_DIRS=(api/app api/routes api/database api/config api/bootstrap api/resources api/lang
             api/public/index.php modules core layout services css
             index.html app.js style.css service-worker.js manifest.json .htaccess)

if [ -n "$(git status --porcelain)" ]; then
  echo "ERROR: el árbol de trabajo tiene cambios sin commitear. Commitea o descarta antes de armar el paquete." >&2
  git status --short >&2
  exit 1
fi
git rev-parse --verify -q "${BASE}^{commit}" >/dev/null || { echo "ERROR: commit base '$BASE' no existe." >&2; exit 1; }

LIST="${OUT%.tgz}.list"
git diff --name-only --diff-filter=ACMR "${BASE}..HEAD" -- "${DEPLOY_DIRS[@]}" | sort > "$LIST"
DELETED="$(git diff --name-only --diff-filter=D "${BASE}..HEAD" -- "${DEPLOY_DIRS[@]}" || true)"

# Red de seguridad: nada sensible o ajeno al despliegue en el paquete.
if grep -E '(^|/)\.env|/storage/|/vendor/|^SGAA/|/tests/|^db/|\.zip$|graphify-out' "$LIST"; then
  echo "ERROR: el paquete incluiría archivos prohibidos (ver arriba)." >&2
  exit 1
fi
[ -s "$LIST" ] || { echo "No hay cambios desplegables entre $BASE y HEAD."; exit 0; }

# Lint de PHP dentro del contenedor local (el servidor repite la comprobación con PHP 8.2).
if command -v docker >/dev/null 2>&1 && docker ps --format '{{.Names}}' | grep -q '^ut_semilleros_api$'; then
  bad=0
  while read -r f; do
    case "$f" in api/*.php|api/*/*.php|api/*/*/*.php|api/*/*/*/*.php|api/*/*/*/*/*.php)
      docker exec ut_semilleros_api php -l "/var/www/html/${f#api/}" >/dev/null 2>&1 || { echo "PHP con error: $f" >&2; bad=1; } ;;
    esac
  done < "$LIST"
  [ "$bad" = 0 ] || exit 1
fi

# version.json: identificador de ESTA versión (commit). La app lo consulta para avisar/actualizarse sola (core/app-update.js).
# Va dentro del paquete pero no está en el repo ni en $LIST (por eso no entra en la comprobación «diff 0» por MD5).
STAMP_DIR="$(mktemp -d)"
printf '{"version":"%s","built":"%s"}\n' "$(git rev-parse --short HEAD)" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "$STAMP_DIR/version.json"
tar -czf "$OUT" -T "$LIST" -C "$STAMP_DIR" version.json
rm -rf "$STAMP_DIR"
( cd "$(dirname "$OUT")"&& sha256sum "$(basename "$OUT")" ) > "${OUT}.sha256"

echo "Base: $BASE   HEAD: $(git rev-parse --short HEAD)"
echo "Archivos en el paquete: $(wc -l < "$LIST")"
echo "Paquete : $OUT ($(du -h "$OUT" | cut -f1))"
echo "Lista   : $LIST"
echo "SHA-256 : $(cut -d' ' -f1 "${OUT}.sha256")"
if grep -q 'api/database/migrations/' "$LIST"; then
  echo "MIGRACIONES NUEVAS (revisar con --pretend antes de aplicar):"
  grep 'api/database/migrations/' "$LIST" | sed 's/^/  /'
fi
if [ -n "$DELETED" ]; then
  echo "ATENCIÓN: archivos borrados en el repo que hay que borrar a mano en producción:"
  echo "$DELETED" | sed 's/^/  /'
fi
