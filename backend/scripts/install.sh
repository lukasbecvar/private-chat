#!/bin/bash

# install dependencies
if [ ! -d 'vendor/' ]
then
    docker-compose run composer
fi

# generate jwk key
if [ ! -d 'token-keypair/' ]
then
    docker-compose run php ./bin/console lexik:jwt:generate-keypair
fi

# fix storage permissions
sudo chmod -R 777 var/
sudo chown -R www-data:www-data var/
