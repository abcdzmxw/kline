<?php

namespace App\Console\Commands;

use App\Models\OptionScene;
use Illuminate\Console\Command;

class CancelScene extends Command
{
    /**
     * The name and signature of the console command.
     * 异常期权场景流局处理
     * @var string
     */
    protected $signature = 'cancelScene';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $finish = time() - 10; // 让时间超过10秒 给交割的执行时间
        $scenes = OptionScene::query()
            ->where('end_time','<',$finish)
            ->whereNotIn('status',[OptionScene::status_delivered,OptionScene::status_cancel])
            ->get();
        if(blank($scenes)) return;

        foreach ($scenes as $scene){
//            echo $scene['scene_id'] . "\r\n";
              $scene->cancel_scene();
           //   $scene->option_order_delivery();
        }
    }
}
