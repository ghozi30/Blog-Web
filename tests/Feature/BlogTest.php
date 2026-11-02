<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('public users can visit blog index and see published posts', function () {
    $user = User::factory()->create();

    $publishedPost = Post::create([
        'user_id' => $user->id,
        'title' => 'Published Article',
        'slug' => 'published-article',
        'excerpt' => 'Excerpt of published article',
        'content' => 'Full content of the published article.',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $draftPost = Post::create([
        'user_id' => $user->id,
        'title' => 'Secret Draft Post',
        'slug' => 'secret-draft-post',
        'excerpt' => 'Draft excerpt',
        'content' => 'Full content of the draft post.',
        'status' => 'draft',
    ]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Published Article')
        ->assertDontSee('Secret Draft Post');
});

test('users can search posts on blog index', function () {
    $user = User::factory()->create();

    Post::create([
        'user_id' => $user->id,
        'title' => 'Laravel 12 Tips',
        'slug' => 'laravel-12-tips',
        'excerpt' => 'Great tips for Laravel',
        'content' => 'Some content here.',
        'status' => 'published',
        'published_at' => now(),
    ]);

    Post::create([
        'user_id' => $user->id,
        'title' => 'Vue JS Guide',
        'slug' => 'vue-js-guide',
        'excerpt' => 'Great guide for Vue',
        'content' => 'Some other content here.',
        'status' => 'published',
        'published_at' => now(),
    ]);

    Livewire::test('post-list')
        ->set('search', 'Laravel')
        ->assertSee('Laravel 12 Tips')
        ->assertDontSee('Vue JS Guide');
});

test('public users can view a published post detail page', function () {
    $this->seed(CategorySeeder::class);
    $user = User::factory()->create();

    $category = Category::first();
    $tag = Tag::first();

    $post = Post::create([
        'user_id' => $user->id,
        'title' => 'Deep Dive into Livewire 4',
        'slug' => 'deep-dive-into-livewire-4',
        'excerpt' => 'Explore the new features of Livewire 4.',
        'content' => '<p>Here is the full HTML content of Livewire 4.</p>',
        'status' => 'published',
        'published_at' => now(),
    ]);
    $post->categories()->attach($category->id);
    $post->tags()->attach($tag->id);

    $this->get(route('blog.show', $post->slug))
        ->assertOk()
        ->assertSee('Deep Dive into Livewire 4')
        ->assertSee($user->name)
        ->assertSee($category->name)
        ->assertSee(route('blog.index', ['category' => $category->slug]))
        ->assertSee('#'.$tag->name)
        ->assertSee(route('blog.index', ['tag' => $tag->slug]))
        ->assertSee('Here is the full HTML content of Livewire 4', false);
});

test('viewing a draft post detail page returns 404', function () {
    $user = User::factory()->create();

    $post = Post::create([
        'user_id' => $user->id,
        'title' => 'Unpublished Draft Post',
        'slug' => 'unpublished-draft-post',
        'content' => '<p>Hidden content.</p>',
        'status' => 'draft',
    ]);

    $this->get(route('blog.show', $post->slug))
        ->assertNotFound();
});

test('users can filter posts by category and tag on blog index', function () {
    $this->seed(CategorySeeder::class);

    $user = User::factory()->create();
    $techCategory = Category::where('slug', 'technology')->first();
    $laravelTag = Tag::where('name', 'Laravel')->first();

    $post1 = Post::create([
        'user_id' => $user->id,
        'title' => 'Tech Laravel Post',
        'slug' => 'tech-laravel-post',
        'excerpt' => 'Tech excerpt',
        'content' => 'Tech content.',
        'status' => 'published',
        'published_at' => now(),
    ]);
    $post1->categories()->attach($techCategory->id);
    $post1->tags()->attach($laravelTag->id);

    $post2 = Post::create([
        'user_id' => $user->id,
        'title' => 'Other Article',
        'slug' => 'other-article',
        'excerpt' => 'Other excerpt',
        'content' => 'Other content.',
        'status' => 'published',
        'published_at' => now(),
    ]);

    Livewire::test('post-list')
        ->set('selectedCategory', 'technology')
        ->assertSee('Tech Laravel Post')
        ->assertDontSee('Other Article')
        ->set('selectedCategory', '')
        ->set('selectedTag', 'laravel')
        ->assertSee('Tech Laravel Post')
        ->assertDontSee('Other Article')
        ->call('clearFilters')
        ->assertSet('selectedCategory', '')
        ->assertSet('selectedTag', '')
        ->assertSet('search', '')
        ->assertSee('Other Article');
});
