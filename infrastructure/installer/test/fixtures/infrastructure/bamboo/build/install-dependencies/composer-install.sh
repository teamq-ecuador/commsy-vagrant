#!/usr/bin/env bash

set -x

sudo $(aws ecr get-login --no-include-email --region eu-central-1)

export COMPOSER_INTERACTIVE_NO_CLI=1
export SERVICE_TAG=bamboo

if [ ! -d "$PWD/app/vendor" ]
then
    sudo mkdir -p $PWD/app/vendor
fi

sudo docker-compose run --rm --volume $PWD/app/vendor:/php-vendor composer composer install --no-ansi --no-interaction --no-progress --no-scripts --optimize-autoloader -d /var/www/app

sudo chmod -R 0777 $PWD/app/vendor
