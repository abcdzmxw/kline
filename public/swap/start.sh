#!/bin/bash

sh swap_stop.sh
cat /dev/null > log.txt
nohup sh swap_start.sh > log.txt 2>&1 &
