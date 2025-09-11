#/bin/bash

# install dependencies (if not installed)
if [ ! -d "node_modules" ]; then
    docker-compose run --rm node npm install --loglevel=error
fi
