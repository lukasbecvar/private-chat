#!/bin/bash

# run test script for backend
cd ./backend
sh scripts/test.sh

# run test script for frontend
cd ../frontend
sh scripts/test.sh
