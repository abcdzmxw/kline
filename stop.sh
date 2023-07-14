#!/bin/bash

cd "$(dirname "$0")"

cd public/exchange/ && sh ./wss_stop.sh &

cd public/swap/ && sh swap_stop.sh &

cd "$(dirname "$0")"

php artisan workerman option stop


php artisan workerman swap stop




echo "停止成功!"
