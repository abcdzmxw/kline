<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserAuth;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckUserAuth extends Command
{
    /**
     * The name and signature of the console command.
     * 用户认证自动审核通过
     * @var string
     */
    protected $signature = 'checkUserAuth';

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
        $switch = 1;
        if(!$switch) return ;
        UserAuth::query()->where('primary_status',1)->where('status',1)
            ->chunkById(100,function ($auths){

                try{

                    DB::beginTransaction();

                    foreach ($auths as $auth) {
                        if($auth->status != UserAuth::STATUS_WAIT){
                            continue;
                        }

                        $auth->status = UserAuth::STATUS_AUTH;
                        $auth->check_time = Carbon::now()->toDateTimeString();
                        $auth->save();

                        $user = User::query()->find($auth['user_id']);
                        if(!blank($user)) $user->update(['user_auth_level' => User::user_auth_level_top]);
                    }

                    DB::commit();
                }catch (\Exception $e){
                    DB::rollBack();
                    throw $e;
                }

            });

    }
}
