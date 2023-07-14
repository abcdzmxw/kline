#!/bin/bash

cd "$(dirname "$0")"

php artisan workerman option stop
php artisan workerman option start --d

php artisan workerman swap stop
php artisan workerman swap start --d

cd public/exchange/ && sh ./wss_stop.sh && cat /dev/null > log.txt && nohup sh wss_start.sh > log.txt 2>&1 &
cd public/swap/ && sh swap_stop.sh && cat /dev/null > log.txt && nohup sh swap_start.sh > log.txt 2>&1 &

echo "启动成功!"
