#!/usr/bin/env bash

export COMPOSE_INTERACTIVE_NO_CLI=1

COMPOSER_COMMAND="$*"

cd /var/www

sudo docker-compose run -w /var/www/app --rm sso_composer composer run-script $COMPOSER_COMMAND
