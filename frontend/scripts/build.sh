#/bin/bash

# install dependencies (if not installed)
if [ ! -d "node_modules" ]; then
    sh scripts/install.sh
fi

# build app
docker-compose run --rm node npm run build
