#!/usr/bin/env bash

export COMPOSE_INTERACTIVE_NO_CLI=1

COMPOSER_COMMAND="$*"

cd /var/www

sudo docker-compose run -w /var/www/app --rm sso_composer composer $COMPOSER_COMMAND

echo "Removing stale VendorFiles..."

sudo docker-compose exec sso_php rm -rf /php-vendor

echo "Moving updated files to Container..."

sudo docker cp /var/www/app/vendor sso_php:/php-vendor

echo "Executing ComposerScripts for Symfony - cache:clear"

sudo docker-compose exec sso_php /var/www/app/bin/console cache:clear

echo "Executing ComposerScripts for Symfony - assets:install"

sudo docker-compose exec sso_php /var/www/app/bin/console assets:install /var/www/app/public
