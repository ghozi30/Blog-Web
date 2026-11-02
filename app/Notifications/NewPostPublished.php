<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewPostPublished extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Post $post) {}

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
        $mail = (new MailMessage)
            ->subject("New Post: {$this->post->title}")
            ->greeting('Hello!')
            ->line('A new post has been published on our blog:')
            ->line('"'.$this->post->title.'"');

        if ($this->post->excerpt) {
            $mail->line($this->post->excerpt);
        }

        $mail->action('Read Post', route('blog.show', $this->post->slug))
            ->line('Thank you for subscribing to our updates!');

        if (isset($notifiable->token)) {
            $mail->line('If you no longer wish to receive these notifications, you can unsubscribe anytime: '.route('unsubscribe', $notifiable->token));
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'title' => $this->post->title,
            'slug' => $this->post->slug,
        ];
    }
}
