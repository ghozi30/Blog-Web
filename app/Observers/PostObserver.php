<?php

namespace App\Observers;

use App\Models\Post;
use App\Models\Subscriber;
use App\Notifications\NewPostPublished;
use Illuminate\Support\Facades\Notification;

class PostObserver
{
    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        if ($post->status === 'published') {
            $subscribers = Subscriber::where('is_verified', true)->get();

            if ($subscribers->isNotEmpty()) {
                Notification::send($subscribers, new NewPostPublished($post));
            }
        }
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        if ($post->isDirty('status') && $post->status === 'published') {
            $subscribers = Subscriber::where('is_verified', true)->get();

            if ($subscribers->isNotEmpty()) {
                Notification::send($subscribers, new NewPostPublished($post));
            }
        }
    }
}
