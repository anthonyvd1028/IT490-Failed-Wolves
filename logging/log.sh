#!/bin/bash

# TODO: Add missing machines

if ls /etc/systemd/system | grep -q ssh; then
	echo "ssh found in systemd"
else
	sudo apt install openssh-server -y
	sudo systemctl start openssh-server
fi 

case "$1" in
	dev)
		machines=("avd8@172.29.21.212" "avd8@172.29.100.228")
		;;
	qa)
		machines=("avd8@172.29.14.130")
		;;
	prod)
		machines=("")
		;;
	*)
		echo "Usage: $0 {dev|qa|prod}"
		exit 
		;;
esac

if [[ -f "~/.ssh/id_ed25519.pub" ]]; then
	echo "No id_ed25519.pub found"
	echo "Generating key now"
	ssh-keygen -t ed25519
fi

echo

for machine in ${machines[@]}; do
	echo "Copying ssh ID for $machine"
	ssh-copy-id $machine
done

if [[ ! -d "/var/log/it490" ]]; then
	sudo mkdir /var/log/it490
	sudo chown $USER:$USER /var/log/it490 
fi

ip=$(hostname -I | grep -o -E "172\.29\.[0-9]+\.[0-9]+")
sudo touch /var/log/it490/local-${ip}.log
sudo touch /var/log/it490/local.log
sudo chown $USER:$USER /var/log/it490/local-${ip}.log
sudo chown $USER:$USER /var/log/it490/local.log
echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"
echo "Done with setup"

while true; do
	#TODO: append more logs into local.log
	journalctl -q -u testRabbitMQServer.service -b | grep -Fv -f /var/log/it490/local.log | sudo tee -a /var/log/it490/local.log


	while IFS= read -r line; do
		timestamp=$(date --iso-8601=seconds)
		
		if ! grep -qF "$line" /var/log/it490/local-${ip}.log; then
			echo "[${timestamp}][${ip}][${line}]" | sudo tee -a /var/log/it490/local-${ip}.log
		fi
	done < "/var/log/it490/local.log"

		
	for machine in ${machines[@]}; do
		machineIP=$(cut -d@ -f2 <<< "$machine")
		if [[ "$machineIP" == "$ip" ]]; then
			continue
		fi

		scp ${machine}:/var/log/it490/local-${machineIP}.log /var/log/it490/local-${machineIP}.log
	done

	sleep 10
done
