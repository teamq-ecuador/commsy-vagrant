#!/usr/bin/env bash

if [ -f /usr/bin/composer ]; then
  rm /usr/bin/composer
fi

cp /vagrant/infrastructure/scripts/composer.sh /usr/bin/composer

chmod a+x /usr/bin/composer

if [ -f /usr/bin/console ]; then
  rm /usr/bin/console
fi

cp /vagrant/infrastructure/scripts/console.sh /usr/bin/console

chmod a+x /usr/bin/console

if [ -f /usr/bin/phpunit ]; then
  rm /usr/bin/phpunit
fi

cp /vagrant/infrastructure/scripts/phpunit.sh /usr/bin/phpunit

chmod a+x /usr/bin/phpunit

if [ -f /usr/bin/php ]; then
  rm /usr/bin/php
fi

cp /vagrant/infrastructure/scripts/php.sh /usr/bin/php

chmod a+x /usr/bin/php

if [ -f /usr/bin/composer-script ]; then
  rm /usr/bin/composer-script
fi

cp /vagrant/infrastructure/scripts/composer-script.sh /usr/bin/composer-script

chmod a+x /usr/bin/composer-script