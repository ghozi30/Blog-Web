<?php

namespace App\Livewire\Blog;

use App\Models\Comment;
use App\Models\Post;
use App\Notifications\NewCommentNotification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Comments extends Component
{
    public Post $post;

    #[Validate('required|string|min:3|max:1000')]
    public string $newComment = '';

    public ?int $replyingTo = null;

    #[Validate('required|string|min:3|max:1000')]
    public string $replyContent = '';

    public function mount(Post $post): void
    {
        $this->post = $post;
    }

    public function postComment(): mixed
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $this->validate(['newComment' => 'required|string|min:3|max:1000']);

        $comment = Comment::create([
            'post_id' => $this->post->id,
            'user_id' => auth()->id(),
            'content' => $this->newComment,
            'status' => 'approved',
        ]);

        if ($this->post->user_id !== auth()->id() && $this->post->user) {
            $this->post->user->notify(new NewCommentNotification($comment));
        }

        $this->newComment = '';

        $this->dispatch('comment-posted');

        session()->flash('comment-success', 'Comment posted successfully!');

        return null;
    }

    public function startReply(int $commentId): mixed
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $this->replyingTo = $commentId;
        $this->replyContent = '';

        return null;
    }

    public function cancelReply(): void
    {
        $this->replyingTo = null;
        $this->replyContent = '';
    }

    public function postReply(int $parentId): mixed
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $this->validate(['replyContent' => 'required|string|min:3|max:1000']);

        $comment = Comment::create([
            'post_id' => $this->post->id,
            'user_id' => auth()->id(),
            'parent_id' => $parentId,
            'content' => $this->replyContent,
            'status' => 'approved',
        ]);

        $parentComment = Comment::find($parentId);
        if ($parentComment && $parentComment->user_id !== auth()->id() && $parentComment->user) {
            $parentComment->user->notify(new NewCommentNotification($comment));
        } elseif ($this->post->user_id !== auth()->id() && $this->post->user) {
            $this->post->user->notify(new NewCommentNotification($comment));
        }

        $this->replyingTo = null;
        $this->replyContent = '';

        $this->dispatch('comment-posted');

        session()->flash('comment-success', 'Reply posted successfully!');

        return null;
    }

    #[On('comment-posted')]
    public function render(): View
    {
        $comments = Comment::where('post_id', $this->post->id)
            ->approved()
            ->topLevel()
            ->with(['user', 'replies.user'])
            ->latest()
            ->get();

        return view('livewire.blog.comments', [
            'comments' => $comments,
        ]);
    }
}
