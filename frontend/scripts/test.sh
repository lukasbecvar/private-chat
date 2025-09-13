#/bin/bash

# run tests process
docker-compose run --no-deps node bash -c "
    npm run lint &&
    npx vitest run
"
