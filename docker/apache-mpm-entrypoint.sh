#!/bin/sh
set -e
# Railway / some platforms: ensure only mpm_prefork is loaded (mod_php requires prefork).
# Fixes: AH00534 apache2: More than one MPM loaded.
for f in /etc/apache2/mods-enabled/mpm_event.load \
         /etc/apache2/mods-enabled/mpm_event.conf \
         /etc/apache2/mods-enabled/mpm_worker.load \
         /etc/apache2/mods-enabled/mpm_worker.conf; do
  rm -f "$f"
done
if [ -f /etc/apache2/mods-available/mpm_prefork.load ]; then
  a2enmod mpm_prefork 2>/dev/null || true
fi

# Railway / Render / Fly: traffic and healthchecks use $PORT. Default Apache is 80 only.
LISTEN_PORT="${PORT:-80}"
if [ -f /etc/apache2/ports.conf ]; then
  sed -i "s/^Listen 80\$/Listen ${LISTEN_PORT}/" /etc/apache2/ports.conf
  sed -i "s/^Listen 0.0.0.0:80\$/Listen ${LISTEN_PORT}/" /etc/apache2/ports.conf
fi
for site in /etc/apache2/sites-enabled/*.conf; do
  [ -f "$site" ] || continue
  sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${LISTEN_PORT}>/" "$site"
done

exec /usr/local/bin/docker-php-entrypoint "$@"
