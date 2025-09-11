#!/bin/bash

# run install script for backend
cd ./backend
sh scripts/install.sh

# run install script for frontend
cd ./../frontend
sh scripts/install.sh
