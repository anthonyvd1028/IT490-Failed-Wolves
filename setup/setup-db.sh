#!/bin/bash

rootDir=$1

sed -E -i "s|^ExecStart=/home/avd8/versions/[0-9]+/testRabbitMQServer\.php|ExecStart=${rootDir}testRabbitMQServer.php|" ${rootDir}setup/testRabbitMQServer.service

sudo systemctl stop deployementAgent.service
sudo rm /etc/systemd/system/deployementAgent.service
sudo systemctl daemon-reload

sudo cp ${rootDir}setup/testRabbitMQServer.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl start deployementAgent.service
