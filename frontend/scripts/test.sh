#/bin/bash

# run static code analyse
docker-compose run --rm node npm run lint
