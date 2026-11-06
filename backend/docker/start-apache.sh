#!/bin/sh
set -eu

# Railway supplies PORT; Render and local Docker can use Apache's default 80.
port="${PORT:-80}"
case "$port" in
    ''|*[!0-9]*) echo 'PORT debe ser un número.' >&2; exit 1 ;;
esac

sed -ri "s/^Listen 80$/Listen ${port}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

# mod_php requires prefork. Some hosts enable event/worker at runtime too.
a2dismod -f mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1

exec apache2-foreground
