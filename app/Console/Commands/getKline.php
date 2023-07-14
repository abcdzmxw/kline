<?php

namespace App\Console\Commands;

use App\Models\InsideTradePair;
use App\Services\HuobiService\HuobiapiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class getKline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'getKline';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kline数据';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $symbol = 'btcusdt';
        // Kline数据
        $period = '1min';
        $size = 2000;

        $data = (new HuobiapiService())->getKLine($symbol,$period,$size);
        $kline_book_key = 'market:' . $symbol . '_kline_book_' . $period;
        if(isset($data['data'])){
            $data['data'] = array_reverse($data['data']);
            $cache_data = $data['data'];
            Cache::store('redis')->put($kline_book_key,$cache_data);
        }else{
            $data['data'] = [];
        }
    }
}
