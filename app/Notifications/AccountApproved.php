<?php

namespace App\Notifications;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public $account;

    /**
     * Create a new notification instance.
     */
    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = config('app.frontend_url', 'http://localhost:3000') . '/login';

        // Load account with region relationship
        $this->account->load('region');

        return (new MailMessage)
            ->subject('Your Account Has Been Approved!')
            ->replyTo('info@sound-service.eu', 'Sound Service')
            ->view('emails.account-approved', [
                'account' => $this->account,
                'user' => $notifiable,
                'loginUrl' => $loginUrl
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'account_id' => $this->account->id,
            'account_name' => $this->account->name,
            'account_code' => $this->account->code,
        ];
    }
}
