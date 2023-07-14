<?php

namespace App\Notifications;

use App\Models\UserWallet;
use App\Models\UserWalletLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

        
use Illuminate\Http\Request;


class WalletChanged extends Notification
{
    use Queueable;

    private $params;

    /**
     * Create a new notification instance.
     * @param array $params
     * @return void
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray_copy($notifiable)
    {
        return [
            'title' => $this->params['coin_name'] . '资产' . $this->params['change_type'],
            'content' => UserWalletLog::$logType[$this->params['log_type']] . '：' . $this->params['coin_name'] . '资产' . $this->params['change_type'] . $this->params['amount'],
        ];
    }
    
    public function toArray($notifiable)
    {
//        $lang = App::getLocale();
        $lang = 'en';

        $title = $this->params['coin_name'] . ' assets ' . __($this->params['change_type'],[],$lang);
//        $title = baiduTransAPI($title, 'auto', $lang);

        $content = __(UserWalletLog::$logType[$this->params['log_type']],[],$lang) . ' ：' . $this->params['coin_name'] . ' assets ' . __($this->params['change_type'],[],$lang) . ' ' . $this->params['amount'];
//        $content = baiduTransAPI($content, 'auto', $lang);

        return [
            'title' => $title,
            'content' => $content,
        ];
    }
}










