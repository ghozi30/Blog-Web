<?php

use App\Livewire\PostList;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/blog');
})->name('home');

Route::livewire('dashboard', 'pages::dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/blog', PostList::class)->name('blog.index');
Route::livewire('/blog/{slug}', 'pages::posts.show')->name('blog.show');

Route::get('/unsubscribe/{token}', function (string $token) {
    $subscriber = Subscriber::where('token', $token)->firstOrFail();
    $subscriber->delete();

    return view('unsubscribed');
})->name('unsubscribe');

Route::middleware('auth')->group(function () {
    Route::livewire('/posts', 'pages::posts.index')
        ->middleware('can:create posts')
        ->name('posts.index');

    Route::livewire('/posts/create', 'pages::posts.create')
        ->middleware('can:create posts')
        ->name('posts.create');

    Route::livewire('/posts/{post}/edit', 'pages::posts.edit')
        ->name('posts.edit');

    Route::livewire('/users', 'pages::users.index')
        ->middleware('can:manage users')
        ->name('users.index');

    Route::livewire('users/create', 'pages::users.create')
        ->middleware('can:manage users')
        ->name('users.create');

    Route::livewire('users/{user}/edit', 'pages::users.edit')
        ->middleware('can:manage users')
        ->name('users.edit');

    Route::livewire('/categories', 'pages::categories.index')
        ->middleware('can:manage roles')
        ->name('categories.index');

    Route::livewire('/categories/create', 'pages::categories.create')
        ->middleware('can:manage roles')
        ->name('categories.create');

    Route::livewire('/categories/{category}/edit', 'pages::categories.edit')
        ->middleware('can:manage roles')
        ->name('categories.edit');

    Route::livewire('/comments', 'pages::comments.index')
        ->middleware('can:create posts')
        ->name('comments.index');
});

require __DIR__.'/settings.php';
