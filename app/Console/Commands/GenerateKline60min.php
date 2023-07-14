<?php

namespace App\Console\Commands;

use App\Handlers\Kline;
use Illuminate\Console\Command;

class GenerateKline60min extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate_kline_60min';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成60minK线';

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
    public function handle(Kline $kline)
    {
        $this->info("开始生成K线...");

        $kline->generateKline('60min');

        $this->info("生成成功！");
    }
}
