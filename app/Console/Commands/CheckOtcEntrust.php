<?php

namespace App\Console\Commands;

use App\Models\OtcEntrust;
use App\Services\OtcService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckOtcEntrust extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check-otc-entrust';

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
        $entrusts = OtcEntrust::query()->where('status', OtcEntrust::status_normal)->where(function ($query) {
            $query->where('overed_at', '<', Carbon::now()->toDateTimeString());
        })->cursor();

        foreach ($entrusts as $entrust){
            try{
                $user_id = $entrust['user_id'];
                $params = ['entrust_id'=>$entrust['id']];
                (new OtcService())->cancelEntrust($user_id,$params);
            }catch (\Exception $exception){
                info($exception);
                continue;
            }
        }
    }
}
