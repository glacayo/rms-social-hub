<?php

namespace App\Notifications;

use App\Models\FacebookPage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TokenExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly FacebookPage $page,
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'token_expired',
            'page_id' => $this->page->id,
            'page_name' => $this->page->page_name,
            'message' => 'El token de "'.$this->page->page_name.'" expiró. Revinculá la página.',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Token expirado — '.$this->page->page_name)
            ->greeting('Hola, '.$notifiable->name.'.')
            ->line('El token de acceso de la página "'.$this->page->page_name.'" expiró.')
            ->line('Los posts programados para esta página fueron cancelados automáticamente.')
            ->action('Revincular página', url('/admin/pages'))
            ->line('Necesitás reconectar la página desde el panel de administración.');
    }
}
