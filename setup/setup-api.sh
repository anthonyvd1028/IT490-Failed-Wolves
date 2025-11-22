#!/bin/bash
if [[ "$PWD" =~ ^(.*\/versions\/[0-9]+\/) ]] 
then
	rootDir="${BASH_REMATCH[1]}" 
fi

cronlist=$(crontab -l | grep -v "/data/")
crontab - <<EOF
${cronlist}
0 5 * * 2-6 ${rootDir}data/dataOdds.php
0 5 * * 2 ${rootDir}data/dataEvents.php
EOF
