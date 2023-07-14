<?php

namespace App\Listeners;

use App\Models\PledgeProduct;
use App\Events\UserUpgradeEvent;
use App\Models\User;
use App\Models\UserGrade;
use App\Models\UserPledgePromotionGrade;
use App\Models\UserUpgradeLog;
use App\Models\UserWalletLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PledgeUpgradeListener implements ShouldQueue
{
    /**
     * Create the event listener.
     * 检测用户升级
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserUpgradeEvent  $event
     * @return void
     */
    public function handle(PledgeUpgradeEvent $event)
    {
        
     //  
    }
    
    
    public function pledgeOrderCreated(PledgeOrder $pledgeOrder)
    {
        // Implement mailer or use laravel mailer directly
      //  $this->mailer->notifyArticleCreated($article);
    }
    
    
    
    
}
