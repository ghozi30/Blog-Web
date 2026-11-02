<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([
        RolePermissionSeeder::class,
        CategorySeeder::class,
    ]);
});

test('guests are redirected to login when visiting posts', function () {
    $this->get(route('posts.index'))
        ->assertRedirect(route('login'));
});

test('authorized users can view posts index', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $post = Post::create([
        'user_id' => $admin->id,
        'title' => 'First Blog Post',
        'content' => 'This is the content of the first blog post.',
        'status' => 'published',
    ]);

    $this->actingAs($admin)
        ->get(route('posts.index'))
        ->assertOk()
        ->assertSee('First Blog Post');
});

test('users can create posts through livewire component', function () {
    Storage::fake('public');

    $author = User::factory()->create();
    $author->assignRole('author');

    $category = Category::first();

    $file = UploadedFile::fake()->create('thumbnail.jpg', 100, 'image/jpeg');

    Livewire::actingAs($author)
        ->test('pages::posts.create')
        ->set('title', 'My Awesome Article')
        ->set('excerpt', 'A brief summary')
        ->set('content', 'Detailed content for the awesome article.')
        ->set('featured_image', $file)
        ->set('status', 'draft')
        ->set('selectedCategories', [$category->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('posts.index'));

    $this->assertDatabaseHas('posts', [
        'title' => 'My Awesome Article',
        'slug' => 'my-awesome-article',
        'excerpt' => 'A brief summary',
        'user_id' => $author->id,
        'status' => 'draft',
    ]);
});

test('author can update their own post', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    $category = Category::first();

    $post = Post::create([
        'user_id' => $author->id,
        'title' => 'Original Title',
        'content' => 'Original content for this post.',
        'status' => 'draft',
    ]);

    Livewire::actingAs($author)
        ->test('pages::posts.edit', ['post' => $post])
        ->set('title', 'Updated Title')
        ->set('content', 'Updated content for this post.')
        ->set('status', 'draft')
        ->set('selectedCategories', [$category->id])
        ->call('update')
        ->assertHasNoErrors()
        ->assertRedirect(route('posts.index'));

    expect($post->fresh()->title)->toBe('Updated Title');
});

test('post can be deleted by owner', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    $post = Post::create([
        'user_id' => $author->id,
        'title' => 'Post To Delete',
        'content' => 'Content to delete.',
        'status' => 'draft',
    ]);

    Livewire::actingAs($author)
        ->test('pages::posts.index')
        ->call('deletePost', $post->id);

    $this->assertDatabaseMissing('posts', [
        'id' => $post->id,
    ]);
});

test('uploading a heic image does not trigger FileNotPreviewableException', function () {
    Storage::fake('public');

    $author = User::factory()->create();
    $author->assignRole('author');

    $heicFile = UploadedFile::fake()->create('photo.heic', 500, 'image/heic');

    Livewire::actingAs($author)
        ->test('pages::posts.create')
        ->set('featured_image', $heicFile)
        ->assertOk();
});

test('creating post with existing title generates unique slug without unique constraint violation', function () {
    $author = User::factory()->create();
    $author->assignRole('author');
    $category = Category::first();

    Post::create([
        'user_id' => $author->id,
        'title' => 'Daily Vlog',
        'content' => 'First daily vlog content here.',
        'status' => 'draft',
    ]);

    Livewire::actingAs($author)
        ->test('pages::posts.create')
        ->set('title', 'Daily Vlog')
        ->set('content', 'Second daily vlog content here.')
        ->set('status', 'draft')
        ->set('selectedCategories', [$category->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('posts.index'));

    $this->assertDatabaseHas('posts', [
        'title' => 'Daily Vlog',
        'slug' => 'daily-vlog-1',
    ]);
});
