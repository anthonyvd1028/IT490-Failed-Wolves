#!/bin/bash

if [ $# -eq 0 ]; then
	echo "Usage: $0 {dev | qa | prod}"
	exit 1
fi

cp log.service.tmp log.service

sed -i 's|ExecStart=\[PUT FILE PATH\]|ExecStart='"$(pwd)"'\/log.sh '"$1"'|' log.service
sed -i 's|User=\[PUT USER\]|User='"$USER"'|' log.service

sudo cp "$(pwd)/log.service" /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl restart log.service
