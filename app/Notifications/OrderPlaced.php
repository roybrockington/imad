<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlaced extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;
    public $locale;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, string $locale = 'en')
    {
        $this->order = $order;
        $this->locale = $locale;
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
        // Determine language based on account code
        $locale = $this->locale;

        // Load order with relationships
        $this->order->load(['items.product', 'account.country', 'account.term', 'address']);

        return (new MailMessage)
            ->subject($this->getSubject($locale))
            ->replyTo('info@sound-service.eu', 'Sound Service')
            ->bcc('r.schulze@sound-service.eu')
            ->view('emails.order-confirmation', [
                'order' => $this->order,
                'user' => $notifiable,
                'locale' => $locale
            ]);
    }

    /**
     * Get email subject based on locale
     */
    protected function getSubject(string $locale): string
    {
        return $locale === 'de'
            ? 'Bestellbestätigung - Bestellung #' . $this->order->id
            : 'Order Confirmation - Order #' . $this->order->id;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'total' => $this->order->total,
            'currency' => $this->order->currency,
        ];
    }
}
