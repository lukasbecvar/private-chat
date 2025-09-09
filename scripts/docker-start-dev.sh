#!/bin/bash

# install app requirements
sh scripts/install.sh

# build and start docker services
docker-compose up --build
