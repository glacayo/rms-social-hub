<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PostPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Post $post,
        private readonly string $facebookPostUrl = '',
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
            'type' => 'post_published',
            'post_id' => $this->post->id,
            'message' => '¡Tu post fue publicado exitosamente!',
            'facebook_post_url' => $this->facebookPostUrl,
            'post_preview' => \Illuminate\Support\Str::limit($this->post->content, 80),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('✅ Post publicado exitosamente')
            ->greeting('¡Hola, '.$notifiable->name.'!')
            ->line('Tu post fue publicado exitosamente en Facebook.')
            ->line('"'.\Illuminate\Support\Str::limit($this->post->content, 100).'"')
            ->when($this->facebookPostUrl, fn ($m) => $m->action('Ver en Facebook', $this->facebookPostUrl))
            ->line('Gracias por usar RMS Social Hub.');
    }
}
