#!/bin/bash

sh wss_stop.sh
cat /dev/null > log.txt
nohup sh wss_start.sh > log.txt 2>&1 &
