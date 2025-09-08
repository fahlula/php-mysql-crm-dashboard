#!/bin/bash
set -e

service mysql start
sleep 5

DB_NAME="${DB_NAME:-crm_database}"
DB_USER_ENV="${DB_USER:-crm_user}"
DB_PASS_ENV="${DB_PASS:-crm_password}"
PORT="${PORT:-80}"

mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER_ENV'@'localhost' IDENTIFIED BY '$DB_PASS_ENV';"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER_ENV'@'127.0.0.1' IDENTIFIED BY '$DB_PASS_ENV';"
mysql -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER_ENV'@'localhost';"
mysql -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER_ENV'@'127.0.0.1';"
mysql -e "FLUSH PRIVILEGES;"

if [ -d /migrations ]; then
	for migration in /migrations/*.sql; do
		if [ -f "$migration" ]; then
			echo "Running migration: $(basename "$migration")"
			mysql --default-character-set=utf8mb4 "$DB_NAME" < "$migration"
		fi
	done
fi

service mysql stop

# Adapt Apache to Render-provided PORT if different from 80
if [ "$PORT" != "80" ]; then
    sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf || true
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf || true
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf