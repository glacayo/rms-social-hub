<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PostFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Post $post,
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
            'type' => 'post_failed',
            'post_id' => $this->post->id,
            'message' => 'Un post falló después de 3 intentos y requiere atención.',
            'failed_reason' => $this->post->failed_reason,
            'post_preview' => \Illuminate\Support\Str::limit($this->post->content, 80),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('❌ Post fallido — acción requerida')
            ->greeting('Hola, '.$notifiable->name.'.')
            ->line('Un post falló después de 3 intentos automáticos.')
            ->line('"'.\Illuminate\Support\Str::limit($this->post->content, 100).'"')
            ->line('**Razón:** '.($this->post->failed_reason ?? 'Error desconocido'))
            ->action('Ver post en RMS Social Hub', url('/publisher'))
            ->line('Por favor revisá la configuración de la página o intentá republicar.');
    }
}
