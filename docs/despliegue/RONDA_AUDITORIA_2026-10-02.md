# Despliegue de la ronda de auditoría (CU01–CU25 + CU26)

> Preparado el 2026-10-02. **Pendiente de ejecutar**: desde la red de la empresa el firewall bloquea la salida a Hostinger
> (`145.223.77.160:65002`). Ejecutar desde una red que lo permita (hotspot del celular, VPN o red de casa) y desde
> `pcjose`, que es la máquina que tiene el alias SSH `htg`.

**Qué se despliega:** `a076f1d` + `1d17da1` (+ el script `638951a`, que no viaja a producción). Base de comparación = lo que hay
hoy en producción (`0a636ce`: al inicio de la ronda solo diferían SIA y `UserFactory.php`).
**Contenido:** 32 archivos, sin borrados, **2 migraciones**, carpeta nueva `api/lang/`, `CACHE_NAME` = `semilleros-v23`.

## 0. Antes de empezar

- Salir del **modo automático** de Claude Code (Shift+Tab) si va a ejecutarlo Claude: ese modo bloquea escrituras en producción.
- Una sola cuenta Claude despliega a la vez (`ONBOARDING.md`): avisar a la otra.
- Comprobar salida a Hostinger desde `pcjose`:
  `timeout 6 bash -c '</dev/tcp/145.223.77.160/65002' && echo ABIERTO || echo BLOQUEADO`
- En `pcjose`, en el repo: `git status` limpio, `git pull`, `git log --oneline -3` (debe incluir `a076f1d` y `1d17da1`).
- Tests en verde antes de subir:
  `docker exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: ut_semilleros_api php artisan test` → **506 passed**.

## 1. Armar el paquete (local, no toca producción)

```bash
cd /home/jose/proyectos/propios/ut/_public_html
scripts/build-deploy-bundle.sh 0a636ce /tmp/ut_deploy.tgz     # 32 archivos, 2 migraciones
sha256sum -c /tmp/ut_deploy.tgz.sha256
```
El script rechaza un árbol sucio, hace `php -l` de cada PHP y se niega a incluir `.env`, `storage`, `vendor`, tests o `SGAA`.

## 2. Respaldo en el servidor (obligatorio)

La migración `2026_10_01_000001` **cifra los teléfonos de `requests` con `APP_KEY`**. Respaldar primero.

Primero subir el paquete y su lista (desde `pcjose`). Son dos archivos sueltos, sin riesgo de aplanado de rutas:
```bash
scp /tmp/ut_deploy.tgz /tmp/ut_deploy.list htg:~/
ssh htg 'cd ~ && sha256sum ut_deploy.tgz'                 # comparar con el contenido de /tmp/ut_deploy.tgz.sha256
```
Luego, ya dentro del servidor (`ssh htg`):
```bash
B=~/backups/ut-edu.online/$(date +%F)_pre_auditoria; mkdir -p $B && chmod 700 $B
cd ~/domains/ut-edu.online/public_html
# archivos existentes que el paquete va a pisar
while read f; do [ -e "$f" ] && echo "$f"; done < ~/ut_deploy.list > $B/lista_existentes.txt
tar -czf $B/files.tgz -T $B/lista_existentes.txt
# copia del .env (solo permisos; no se imprime)
cp -p api/.env $B/env.bak && chmod 600 $B/env.bak
# tablas que se tocan, en JSON (precedente: CU04/CU05) y conteos
cd api
php artisan tinker --execute='
  foreach (["requests","proposals"] as $t) {
    file_put_contents(getenv("HOME")."/backups/ut-edu.online/".date("Y-m-d")."_pre_auditoria/$t.json", json_encode(DB::table($t)->get()));
    echo "$t: ".DB::table($t)->count()." filas\n";
  }'
chmod 600 $B/*.json
php artisan migrate:status | tail -8        # confirmar que 000001 y 000002 figuran como Pending
grep -E '^APP_(FALLBACK_)?LOCALE' .env      # imprime solo esas líneas (no son secretas)
```
**Anotar los conteos**: sirven para comparar después.
Si `APP_LOCALE=en` (o `APP_FALLBACK_LOCALE=en`) → ponerlo en `es` (o borrar la línea) **antes** de limpiar la caché de configuración.

## 3. Subir el código (modo mantenimiento corto)

```bash
cd ~/domains/ut-edu.online/public_html/api
php artisan down --retry=15
cd ..
tar -xzf ~/ut_deploy.tgz                         # respeta rutas; no toca .env, storage ni SGAA
# lint con PHP 8.2 (el de producción) de todo PHP subido
for f in $(grep '\.php$' ~/ut_deploy.list); do php -l "$f" | grep -v '^No syntax errors' ; done; echo "lint listo"
```
Cualquier línea impresa antes de «lint listo» es un error: **parar** y revisar (restaurar con `tar -xzf $B/files.tgz` si hace falta).

## 4. Migraciones

```bash
cd ~/domains/ut-edu.online/public_html/api
php artisan migrate --pretend          # revisar: alter table `requests` modify `phone` text null ... y add `review_note`
php artisan migrate --force
php artisan migrate:status | tail -4
php artisan config:clear && php artisan route:clear
php artisan up
```
Nunca `--seed` ni `migrate:fresh` en producción. Si la migración aborta con «ya está cifrado con otra APP_KEY», **no continuar**:
significa que hay datos cifrados con una clave distinta; avisar.

## 5. Verificación

1. **Diff 0** contra el repo (desde `pcjose`):
   ```bash
   FILES="api/app api/routes api/database api/config api/bootstrap api/resources api/lang api/public/index.php modules core layout services css index.html app.js service-worker.js manifest.json .htaccess"
   cd /home/jose/proyectos/propios/ut/_public_html && find $FILES -type f | sort | xargs md5sum > /tmp/local.md5
   ssh htg "cd ~/domains/ut-edu.online/public_html && find $FILES -type f | sort | xargs md5sum" > /tmp/prod.md5
   diff /tmp/local.md5 /tmp/prod.md5 && echo "DIFF 0"
   ```
   (Puede diferir `api/database/factories/UserFactory.php`: es solo de tests y ya difería antes.)
2. **Conteos**: `requests` y `proposals` con las mismas filas que antes del despliegue. Un teléfono de `requests` ya no es legible en BD.
3. **Humo sin sesión**:
   ```bash
   curl -s -o /dev/null -w '%{http_code}\n' https://ut-edu.online/api/seedbeds                  # 401
   curl -s -X POST https://ut-edu.online/api/forgot-password -H 'Accept: application/json' -d '{}'   # mensaje en ESPAÑOL, no "validation.required"
   curl -s -X POST https://ut-edu.online/api/register -H 'Accept: application/json' -o /dev/null -w '%{http_code}\n'   # 404/405
   curl -s https://ut-edu.online/service-worker.js | grep CACHE_NAME                            # semilleros-v23
   ```
4. **Con cuentas desechables `qa_temp_*`** (crearlas con `tinker` y borrarlas al final):
   - Estudiante: `GET /api/seedbeds` → solo ACTIVOS y **sin** `authorization_reference`, `users` ni `inactivation_reason`;
     `GET /api/proposals/my` → incluye `status_label` y `review_note`; `PUT /api/profile` con otro `email` → el correo **no cambia**;
     `POST /api/proposals` con `areas:[X,X]` → 422 y no se crea la propuesta.
   - Solicitud de prueba: `phone` cifrado en BD y legible en `GET /api/requests/{id}` para el líder.
   - Líder/Admin: `GET /api/seedbeds` sin correo ni teléfono de los usuarios; Admin puede enviar `leader_id`.
5. **Navegador** (lo único que falta por probar de la ronda): ver «Pasos de prueba manual» en
   `docs/validacion/AUDITORIA_CU01-CU25_2026-09-30.md` §7 y `docs/validacion/CU26.md`. Esperar ~6 min por la caché del CDN y recargar
   dos veces para tomar `semilleros-v23`.
6. Borrar `qa_temp_*` y `~/ut_deploy.*` del servidor al terminar. Dejar el respaldo.
7. Registrar en las actas: fecha, conteos antes/después, resultado del diff y de las pruebas.

## 6. Reversión

```bash
cd ~/domains/ut-edu.online/public_html
B=~/backups/ut-edu.online/<fecha>_pre_auditoria
php api/artisan down
php api/artisan migrate:rollback --step=2 --force    # 000002 y 000001 (el down() de 000001 DESCIFRA los teléfonos)
tar -xzf $B/files.tgz                                # restaura los archivos que se pisaron
# los archivos NUEVOS del paquete (no están en files.tgz) pueden quedarse; no se usan sin las rutas/código viejo
php api/artisan config:clear && php api/artisan route:clear && php api/artisan up
```
Los JSON de `$B` son la copia de seguridad de los datos de `requests` y `proposals`.
