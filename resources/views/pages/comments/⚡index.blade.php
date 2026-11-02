<?php

use App\Models\Comment;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public function with(): array
    {
        $query = Comment::with(['user', 'post'])
            ->latest();

        // Filter by search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('content', 'like', '%'.$this->search.'%')
                    ->orWhereHas('user', function ($subQuery) {
                        $subQuery->where('name', 'like', '%'.$this->search.'%');
                    });
            });
        }

        // Filter by status
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // Authors only see comments on their own posts
        if (auth()->user()->hasRole('author')) {
            $query->whereHas('post', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        return [
            'comments' => $query->paginate(20),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function approveComment(Comment $comment): void
    {
        $comment->update(['status' => 'approved']);
        session()->flash('success', 'Comment approved!');
    }

    public function markAsSpam(Comment $comment): void
    {
        $comment->update(['status' => 'spam']);
        session()->flash('success', 'Comment marked as spam!');
    }

    public function deleteComment(Comment $comment): void
    {
        $comment->delete();
        session()->flash('success', 'Comment deleted!');
    }
};
?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Comments</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Moderate and manage post comments</p>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white dark:bg-zinc-900 rounded-lg border border-gray-200 dark:border-zinc-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input 
                    type="text"
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Search comments..." 
                    class="w-full rounded-md border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>

            <div class="sm:w-48">
                <select 
                    wire:model.live="statusFilter" 
                    class="w-full rounded-md border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="all">All Status</option>
                    <option value="approved">Approved</option>
                    <option value="pending">Pending</option>
                    <option value="spam">Spam</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    @if (session('success'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/40 border border-green-200 dark:border-green-800 rounded-lg p-4" wire:transition>
            <p class="text-sm text-green-800 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Comments List -->
    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="divide-y divide-gray-200 dark:divide-zinc-700">
            @forelse($comments as $comment)
                <div class="p-6 hover:bg-gray-50 dark:hover:bg-zinc-800/50" wire:key="comment-{{ $comment->id }}">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center">
                            <img 
                                src="https://ui-avatars.com/api/?name={{ urlencode($comment->user->name) }}&background=4f46e5&color=fff" 
                                alt="{{ $comment->user->name }}" 
                                class="w-10 h-10 rounded-full mr-3"
                            >
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $comment->user->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    on <a href="{{ route('blog.show', $comment->post->slug) }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ Str::limit($comment->post->title, 40) }}</a>
                                </p>
                            </div>
                        </div>

                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            {{ $comment->status === 'approved' ? 'bg-green-100 dark:bg-green-900/60 text-green-800 dark:text-green-300' : '' }}
                            {{ $comment->status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/60 text-yellow-800 dark:text-yellow-300' : '' }}
                            {{ $comment->status === 'spam' ? 'bg-red-100 dark:bg-red-900/60 text-red-800 dark:text-red-300' : '' }}
                        ">
                            {{ ucfirst($comment->status) }}
                        </span>
                    </div>

                    <div class="text-gray-700 dark:text-gray-300 mb-3">
                        {{ $comment->content }}
                    </div>

                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $comment->created_at->format('M d, Y \a\t g:i A') }}
                        </p>

                        <div class="flex gap-2">
                            @if($comment->status !== 'approved')
                                <button 
                                    wire:click="approveComment({{ $comment->id }})"
                                    class="text-sm text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 font-medium"
                                >
                                    Approve
                                </button>
                            @endif

                            @if($comment->status !== 'spam')
                                <button 
                                    wire:click="markAsSpam({{ $comment->id }})"
                                    class="text-sm text-orange-600 dark:text-orange-400 hover:text-orange-800 dark:hover:text-orange-300 font-medium"
                                >
                                    Mark as Spam
                                </button>
                            @endif

                            <button 
                                wire:click="deleteComment({{ $comment->id }})"
                                wire:confirm="Are you sure you want to delete this comment?"
                                class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-medium"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                    No comments found.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $comments->links() }}
    </div>
</div>
