<?php

namespace App\Console\Commands;

use App\Handlers\Kline;
use Illuminate\Console\Command;

class GenerateKline1mon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate_kline_1mon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成1monK线';

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

        $kline->generateKline('1mon');

        $this->info("生成成功！");
    }
}
