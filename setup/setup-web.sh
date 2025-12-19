#!/bin/bash

rootDir=$1

sudo rm -r /var/www/sample/
sudo cp -r ${rootDir}sample /var/www/
