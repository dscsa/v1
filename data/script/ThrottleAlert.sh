#!/bin/bash

var=$(ps -A -o %cpu | awk '{s+=$1} END {print s}');
now=$(date +"+%Y-%m-%d %H:%M:%S");

if [ $(echo "scale=0; $var/35" | bc) -ge 1 ];

   then ps -eo pcpu,pid -o comm= | grep -v "CPU" | sort -k1 -n -r | head -5 | mail -s "Throttle Alert $now" info@sirum.org

fi
