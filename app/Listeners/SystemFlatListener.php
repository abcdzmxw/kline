<?php

namespace App\Listeners;

use App\Events\SystemFlatEvent;
use App\Models\User;
use App\Notifications\CommonNotice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SystemFlatListener
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
     * @param  SystemFlatEvent  $event
     * @return void
     */
    public function handle(SystemFlatEvent $event)
    {
        $user = User::query()->find($event->user_id);
        $body = [
            'title' => __('爆仓强平'),
            'content' => __('爆仓强平'),
        ];
        $user->notify(new CommonNotice($body));
    }
}
