#!/bin/bash

# delete local docker services data
sudo rm -rf ./services-data

# run clear script for backend
cd ./backend
sh scripts/clear.sh

# run clear script for frontend
cd ./../frontend
sh scripts/clear.sh
