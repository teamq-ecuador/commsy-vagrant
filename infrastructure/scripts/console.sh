#!/usr/bin/env bash

export COMPOSE_INTERACTIVE_NO_CLI=1

CONSOLE_COMMAND="$*"

cd /var/www

HOST_IP=$(netstat -rn | grep "^0.0.0.0 " | cut -d " " -f10)

sudo docker-compose exec -e XDEBUG_CONFIG="remote_host=$HOST_IP" -e PHP_IDE_CONFIG="serverName=sso.local" sso_php php /var/www/app/bin/console $CONSOLE_COMMAND
