#!/bin/sh
# uso: runui.sh <script.js> [VAR=valor ...]   (corre en el contenedor de pruebas)
S="$1"; shift
ENVS=""
for v in "$@"; do ENVS="$ENVS -e $v"; done
docker run --rm --network public_html_default --shm-size=1g $ENVS -v /:/host -v /tmp/uitest:/t -w /t node:22-alpine sh /t/run.sh "$S"
