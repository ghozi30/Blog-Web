<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public Post $post;

    #[Validate('required|string|min:3|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:500')]
    public string $excerpt = '';

    #[Validate('required|string|min:10')]
    public string $content = '';

    #[Validate('nullable|file|mimes:jpg,jpeg,png,webp,gif,heic,heif|max:10240', message: [
        'mimes' => 'The featured image must be an image (jpg, jpeg, png, webp, gif, heic).',
        'max' => 'The featured image must not be greater than 10MB.',
    ])]
    public $featured_image;

    #[Validate('required|in:draft,published,archived')]
    public string $status = '';

    public string $existing_image = '';

    #[Validate('required|array|min:1')]
    public array $selectedCategories = [];

    #[Validate('nullable|array')]
    public array $selectedTags = [];

    public function mount(Post $post): void
    {
        // Authorization check
        if (! auth()->user()->can('edit all posts') &&
            ! (auth()->user()->can('edit own posts') && $post->user_id === auth()->id())) {
            abort(403);
        }

        $this->post = $post;
        $this->title = $post->title;
        $this->excerpt = $post->excerpt ?? '';
        $this->content = $post->content;
        $this->status = $post->status;
        $this->existing_image = $post->featured_image ?? '';

        // Load existing categories and tags
        $this->selectedCategories = $post->categories->pluck('id')->toArray();
        $this->selectedTags = $post->tags->pluck('id')->toArray();
    }

    public function with(): array
    {
        return [
            'categories' => Category::all(),
            'tags' => Tag::all(),
        ];
    }

    public function update(): void
    {
        $this->validate();

        $this->post->title = $this->title;
        $this->post->slug = Post::generateUniqueSlug($this->title, $this->post->id);
        $this->post->excerpt = $this->excerpt;
        $this->post->content = $this->content;
        $this->post->status = $this->status;

        if ($this->featured_image) {
            // Delete old image if exists
            if ($this->existing_image) {
                Storage::disk('public')->delete($this->existing_image);
            }

            $path = $this->featured_image->store('posts', 'public');
            $convertedPath = \App\Services\ImageConverter::convertIfHeic($path);
            $this->post->featured_image = $convertedPath;
            $this->existing_image = $convertedPath;
        }

        if ($this->status === 'published' && ! $this->post->published_at) {
            $this->post->published_at = now();
        }

        $this->post->save();

        // Sync categories and tags
        $this->post->categories()->sync($this->selectedCategories);
        $this->post->tags()->sync($this->selectedTags);

        session()->flash('success', 'Post updated successfully!');

        $this->redirect(route('posts.index'), navigate: true);
    }
};
?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Edit Post</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Update your blog post</p>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-lg border border-gray-200 dark:border-zinc-700 p-6">
        <form wire:submit="update" class="space-y-6">
            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Title
                </label>
                <input
                    type="text"
                    id="title"
                    wire:model.live.debounce="title"
                    placeholder="Enter post title"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                @error('title')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Excerpt -->
            <div>
                <label for="excerpt" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Excerpt
                </label>
                <textarea
                    id="excerpt"
                    wire:model="excerpt"
                    placeholder="A short summary of your post (optional)"
                    rows="2"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                ></textarea>
                @error('excerpt')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Content -->
            <div>
                <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Content
                </label>
                <div wire:ignore
                    x-data="{
                        content: $wire.entangle('content'),
                    }"
                    x-init="
                        let editor = $refs.trixEditor.editor;
                        editor.loadHTML(content);
                        $refs.trixEditor.addEventListener('trix-change', function(e){
                            content = e.target.value;
                        });
                    "
                >
                <input id="x-content" type="hidden" name="content">
                <trix-editor
                    input="x-content"
                    class="trix-content"
                    x-ref="trixEditor"
                ></trix-editor>
                </div>
                @error('content')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Featured Image -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Featured Image
                </label>

                @if ($existing_image && !$featured_image)
                    <div class="mt-2 mb-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Current image:</p>
                        <img src="{{ Storage::url($existing_image) }}" class="h-32 w-auto rounded border border-gray-300 dark:border-zinc-700" alt="Current image">
                    </div>
                @endif

                <input
                    type="file"
                    wire:model="featured_image"
                    accept="image/*"
                    class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-md file:border-0
                        file:text-sm file:font-semibold
                        file:bg-indigo-50 file:text-indigo-700
                        hover:file:bg-indigo-100"
                />
                @error('featured_image')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                @if ($featured_image)
                    <div class="mt-3" wire:transition>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">New image:</p>
                        @php
                            $canPreview = false;
                            $previewUrl = null;
                            try {
                                if (is_object($featured_image) && method_exists($featured_image, 'isPreviewable') && $featured_image->isPreviewable()) {
                                    $previewUrl = $featured_image->temporaryUrl();
                                    $canPreview = true;
                                }
                            } catch (\Throwable $e) {
                                $canPreview = false;
                            }
                        @endphp

                        @if ($canPreview && $previewUrl)
                            <img src="{{ $previewUrl }}" class="h-32 w-auto rounded border border-gray-300 dark:border-zinc-700" alt="Preview">
                        @else
                            <div class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-zinc-800 rounded border border-gray-200 dark:border-zinc-700 text-sm text-gray-700 dark:text-gray-300">
                                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>{{ (is_object($featured_image) && method_exists($featured_image, 'getClientOriginalName')) ? $featured_image->getClientOriginalName() : 'Selected image' }}</span>
                                <span class="text-xs text-amber-600 dark:text-amber-400">(Preview not available)</span>
                            </div>
                        @endif
                    </div>
                @endif

                <div wire:loading wire:target="featured_image" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Uploading...
                </div>
            </div>

            <!-- Categories -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Categories (Required)
                </label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-300 dark:border-zinc-700 rounded-md p-3">
                    @foreach($categories as $category)
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                wire:model="selectedCategories"
                                value="{{ $category->id }}"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-zinc-700 rounded"
                            />
                            <span class="ml-3 flex items-center">
                                <span
                                    class="inline-block w-3 h-3 rounded-full mr-2"
                                    style="background-color: {{ $category->color }}"
                                ></span>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $category->name }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('selectedCategories')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tags -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Tags (Optional)
                </label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-300 dark:border-zinc-700 rounded-md p-3">
                    @foreach($tags as $tag)
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                wire:model="selectedTags"
                                value="{{ $tag->id }}"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-zinc-700 rounded"
                            />
                            <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $tag->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('selectedTags')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select relevant tags to help readers find your content</p>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Status
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input
                            type="radio"
                            wire:model="status"
                            value="draft"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-zinc-700"
                        />
                        <span class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">Draft</span>
                    </label>
                    @can('publish posts')
                    <label class="flex items-center">
                        <input
                            type="radio"
                            wire:model="status"
                            value="published"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-zinc-700"
                        />
                        <span class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">Published</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            type="radio"
                            wire:model="status"
                            value="archived"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-zinc-700"
                        />
                        <span class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">Archived</span>
                    </label>
                    @endcan
                </div>
                @error('status')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    Update Post
                </button>
                <a
                    href="{{ route('posts.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-zinc-800 border border-gray-300 dark:border-zinc-700 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
