# Pruebas visuales automatizadas (Playwright sobre Chrome)

Rastrean todas las pantallas por rol y tamaño (escritorio, tablet, móvil), pulsan todos los botones y modales,
recorren los flujos completos (estudiante → líder/administrativo → estudiante), el CRUD de cada catálogo y la PWA
(manifest, Service Worker, sin conexión). **Corren contra el entorno local de Docker, nunca contra producción.**

## Cómo funciona (por qué es tan particular)
En `pcjose` el host no llega a los contenedores (firewall) y los contenedores no tienen salida a internet. Por eso
`run.sh` arranca el **Chrome del host dentro de un contenedor `node:22-alpine`** (con `chroot /host`) en la red de
Docker, y Playwright lo controla por CDP. `lib.js` levanta relés `localhost:8080/8000 → frontend/api` y
`cdnserver.js` sirve las librerías de CDN (jQuery, DataTables, SweetAlert2, Chart.js, FontAwesome…) desde una carpeta
local `cdn/` (se descargan una vez; el navegador las resuelve con `--host-resolver-rules` + certificado propio).

## Preparación única (en el equipo de desarrollo)
1. `mkdir -p /tmp/uitest/out` y copiar aquí `scripts/ui-tests/*` a `/tmp/uitest`.
2. `cp -r <node_modules con playwright-core> /tmp/uitest/node_modules` (p. ej. de `~/.npm/_npx/*/node_modules`).
3. Descargar las librerías CDN a `/tmp/uitest/cdn` y escribir `cdn/map.txt` (`URL<TAB>archivo`) como en `cdnserver.js`;
   generar `key.pem`/`cert.pem` con `openssl req -x509 -newkey rsa:2048 -nodes ...`.
4. Crear usuarios QA locales `qa_e2e_{sis,adm,lid,est,est2}@example.invalid` (clave en `lib.js`, solo BD local) y
   dejar al líder como LIDER de dos semilleros.
5. **Respaldar la BD local antes** (`mysqldump`): las pruebas crean, editan e inactivan registros.

## Uso
`./runui.sh crawl.js VP=desktop|tablet|mobile` · `buttons.js` · `flows.js` · `crudflow.js` · `pwa.js`
(las ejecuciones deben ir **en serie**: comparten el perfil de Chrome). Los hallazgos salen en consola y en
`out/issues_*.json`; las capturas en `out/shots/`.
