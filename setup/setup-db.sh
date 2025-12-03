#!/bin/bash

rootDir=$1

sed -E -i "s|^ExecStart=/home/jjr59/versions/[0-9]+/testRabbitMQServer\.php|ExecStart=${rootDir}testRabbitMQServer.php|" ${rootDir}setup/testRabbitMQServer.service
echo -n "Which "
which cp

echo "Copying"
sudo cp ${rootDir}setup/testRabbitMQServer.service /etc/systemd/system/

echo "Reloading Daemon"
sudo systemctl daemon-reload
