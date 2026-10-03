#!/bin/sh
# Corre dentro de node:22-alpine con el sistema de archivos del host montado en /host.
# Chrome del host se ejecuta en chroot (misma red de Docker que la app); Playwright lo controla por CDP.
rm -rf /host/tmp/utchrome; mkdir -p /host/tmp/utchrome
chroot /host /opt/google/chrome/chrome --headless=new --no-sandbox --disable-gpu --disable-dev-shm-usage \
  --user-data-dir=/tmp/utchrome --remote-debugging-port=9222 --no-first-run --no-default-browser-check \
  --ignore-certificate-errors --host-resolver-rules="MAP cdn.datatables.net 127.0.0.1,MAP cdnjs.cloudflare.com 127.0.0.1,MAP cdn.jsdelivr.net 127.0.0.1,MAP cdn.tailwindcss.com 127.0.0.1,MAP code.jquery.com 127.0.0.1,MAP fonts.googleapis.com 127.0.0.1,MAP fonts.gstatic.com 127.0.0.1,MAP www.ut.edu.co 127.0.0.1" --window-size=1440,900 about:blank >/t/out/chrome.log 2>&1 &
i=0; until wget -q -O- http://127.0.0.1:9222/json/version >/dev/null 2>&1; do i=$((i+1)); [ $i -gt 40 ] && { echo "Chrome no arrancó"; tail -20 /t/out/chrome.log; exit 1; }; sleep 0.5; done
node "$@"; rc=$?
pkill -f utchrome 2>/dev/null; rm -rf /host/tmp/utchrome
exit $rc
