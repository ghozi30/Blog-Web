<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PostList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'category')]
    public string $selectedCategory = '';

    #[Url(as: 'tag')]
    public string $selectedTag = '';

    #[Layout('layouts.public')]
    #[Title('Blog')]
    public function render(): View
    {
        $posts = Post::with(['user', 'categories', 'tags'])
            ->where('status', 'published')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('content', 'like', '%'.$this->search.'%')
                        ->orWhere('excerpt', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->selectedCategory, function ($query) {
                $query->whereHas('categories', function ($q) {
                    $q->where('slug', $this->selectedCategory)
                        ->orWhere('categories.id', $this->selectedCategory);
                });
            })
            ->when($this->selectedTag, function ($query) {
                $query->whereHas('tags', function ($q) {
                    $q->where('slug', $this->selectedTag)
                        ->orWhere('tags.id', $this->selectedTag);
                });
            })
            ->latest('published_at')
            ->paginate(9);

        return view('livewire.post-list', [
            'posts' => $posts,
            'categories' => Category::withCount('posts')->get(),
            'tags' => Tag::withCount('posts')->get(),
        ]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedCategory(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedTag(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->selectedCategory = '';
        $this->selectedTag = '';
        $this->resetPage();
    }
}
