#/bin/bash

# setup testing database
docker-compose run php bash -c "
    php bin/console doctrine:database:create --if-not-exists &&
    php bin/console doctrine:database:create --if-not-exists --env=test &&
    php bin/console doctrine:migrations:migrate --no-interaction &&
    php bin/console doctrine:migrations:migrate --no-interaction --env=test &&
    php bin/console doctrine:fixtures:load --no-interaction &&
    php bin/console doctrine:fixtures:load --no-interaction --env=test
"

# generate jwk keys
if [ ! -d 'services/api-server/jwt-keys/' ]
then
    docker-compose run php ./bin/console lexik:jwt:generate-keypair
fi
