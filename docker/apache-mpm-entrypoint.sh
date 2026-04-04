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
exec /usr/local/bin/docker-php-entrypoint "$@"
