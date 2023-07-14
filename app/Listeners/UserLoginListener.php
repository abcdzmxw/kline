<?php

namespace App\Listeners;

use App\Events\UserLoginEvent;
use App\Models\UserLoginLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Jenssegers\Agent\Agent;

class UserLoginListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserLoginEvent  $event
     * @return void
     */
    public function handle(UserLoginEvent $event)
    {
        $user = $event->user;
        if(blank($user)) return ;

        //登陆记录日志
        $agent = new Agent();
        $ip = request()->getClientIp();
        $site = geoip($ip)->toArray();
        UserLoginLog::query()->create([
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'login_time' => time(),
            'login_ip' => $ip,
            'login_site' => $site['country'] . ',' .  $site['state_name'] . ',' . $site['city'],
            'login_type' => $agent->platform() ?? 'PC',
        ]);
    }
}
