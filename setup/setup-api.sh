#!/bin/bash
rootDir=$1
cronlist=$(crontab -l | grep -v "/data/")
crontab - <<EOF
${cronlist}
0 5 * * 2-6 ${rootDir}data/dataOdds.php
0 5 * * 2 ${rootDir}data/dataEvents.php
EOF
