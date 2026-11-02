<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Comment $comment) {}

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
        $commenterName = $this->comment->user?->name ?? 'Someone';
        $postTitle = $this->comment->post?->title ?? 'your post';
        $commentUrl = route('blog.show', $this->comment->post->slug).'#comment-'.$this->comment->id;

        return (new MailMessage)
            ->subject("New Comment on: {$postTitle}")
            ->greeting("Hello, {$notifiable->name}!")
            ->line("{$commenterName} left a comment on \"{$postTitle}\":")
            ->line('"'.Str::limit($this->comment->content, 200).'"')
            ->action('View Comment', $commentUrl)
            ->line('Thank you for being part of our blog community!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'comment_id' => $this->comment->id,
            'post_id' => $this->comment->post_id,
            'user_id' => $this->comment->user_id,
        ];
    }
}
