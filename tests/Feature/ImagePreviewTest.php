<?php

use App\Http\Controllers\CustomFilePreviewController;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Facades\GenerateSignedUploadUrlFacade;
use Livewire\Features\SupportFileUploads\FilePreviewController;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Livewire;

test('FilePreviewController resolves to CustomFilePreviewController', function () {
    $controller = app()->make(FilePreviewController::class);

    expect($controller)->toBeInstanceOf(CustomFilePreviewController::class);
});

test('preview route serves heic as jpeg with valid signature', function () {
    $storage = FileUploadConfiguration::storage();
    $filename = 'sample_test.heic';
    $filePath = FileUploadConfiguration::path($filename);

    $realHeic = Storage::disk('public')->path('livewire-tmp/CGFpClIyVGiNqcpqmaowyisqvizzHRkzqtcGO3gE.heic');
    $storage->put($filePath, file_get_contents($realHeic));

    $signedUrl = GenerateSignedUploadUrlFacade::signedRoute(
        'livewire.preview-file',
        now()->addMinutes(30),
        ['filename' => $filename]
    );

    $response = $this->get($signedUrl);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/jpeg');
});

test('posts table displays post featured image thumbnail', function () {
    $this->seed(RolePermissionSeeder::class);

    $author = User::factory()->create();
    $author->assignRole('author');

    $post = Post::create([
        'user_id' => $author->id,
        'title' => 'Post With Image',
        'content' => 'Content here',
        'featured_image' => 'posts/sample.jpg',
        'status' => 'published',
    ]);

    Livewire::actingAs($author)
        ->test('pages::posts.index')
        ->assertSee('posts/sample.jpg');
});
