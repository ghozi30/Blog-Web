<?php

use App\Livewire\Blog\Comments;
use App\Livewire\SubscriptionForm;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use App\Notifications\NewPostPublished;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('author is notified with NewCommentNotification when another user comments', function () {
    Notification::fake();

    $author = User::factory()->create();
    $reader = User::factory()->create();

    $post = Post::create([
        'user_id' => $author->id,
        'title' => 'Sample Article',
        'slug' => 'sample-article',
        'content' => 'Article content.',
        'status' => 'published',
        'published_at' => now(),
    ]);

    Livewire::actingAs($reader)
        ->test(Comments::class, ['post' => $post])
        ->set('newComment', 'Insightful read, thank you!')
        ->call('postComment')
        ->assertDispatched('comment-posted');

    Notification::assertSentTo(
        $author,
        NewCommentNotification::class,
        function ($notification) use ($post) {
            return $notification->comment->post_id === $post->id;
        }
    );
});

test('author is not notified when commenting on their own post', function () {
    Notification::fake();

    $author = User::factory()->create();

    $post = Post::create([
        'user_id' => $author->id,
        'title' => 'Author Post',
        'slug' => 'author-post',
        'content' => 'Content.',
        'status' => 'published',
        'published_at' => now(),
    ]);

    Livewire::actingAs($author)
        ->test(Comments::class, ['post' => $post])
        ->set('newComment', 'Author commenting on own post.')
        ->call('postComment');

    Notification::assertNotSentTo($author, NewCommentNotification::class);
});

test('parent comment author is notified when someone replies', function () {
    Notification::fake();

    $postAuthor = User::factory()->create();
    $commentAuthor = User::factory()->create();
    $replyAuthor = User::factory()->create();

    $post = Post::create([
        'user_id' => $postAuthor->id,
        'title' => 'Post with discussion',
        'slug' => 'post-with-discussion',
        'content' => 'Content.',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $comment = Comment::create([
        'post_id' => $post->id,
        'user_id' => $commentAuthor->id,
        'content' => 'First comment.',
        'status' => 'approved',
    ]);

    Livewire::actingAs($replyAuthor)
        ->test(Comments::class, ['post' => $post])
        ->set('replyContent', 'I agree with your comment!')
        ->call('postReply', $comment->id);

    Notification::assertSentTo($commentAuthor, NewCommentNotification::class);
});

test('visitor can subscribe through the subscription form', function () {
    Livewire::test(SubscriptionForm::class)
        ->set('email', 'reader@example.com')
        ->call('subscribe')
        ->assertHasNoErrors()
        ->assertSet('subscribed', true)
        ->assertSet('email', '');

    $subscriber = Subscriber::where('email', 'reader@example.com')->first();
    expect($subscriber)->not->toBeNull();
    expect($subscriber->is_verified)->toBeTrue();
    expect($subscriber->token)->not->toBeEmpty();
});

test('subscription form prevents duplicate email', function () {
    Subscriber::create([
        'email' => 'duplicate@example.com',
        'token' => Str::random(32),
        'is_verified' => true,
    ]);

    Livewire::test(SubscriptionForm::class)
        ->set('email', 'duplicate@example.com')
        ->call('subscribe')
        ->assertHasErrors(['email' => 'unique'])
        ->assertSet('subscribed', false);
});

test('subscribers receive NewPostPublished notification when post is published', function () {
    Notification::fake();

    $subscriber = Subscriber::create([
        'email' => 'subscriber@example.com',
        'token' => Str::random(32),
        'is_verified' => true,
    ]);

    $author = User::factory()->create();

    // Create draft post
    $post = Post::create([
        'user_id' => $author->id,
        'title' => 'Draft Post Title',
        'slug' => 'draft-post-title',
        'excerpt' => 'Summary of post.',
        'content' => 'Full draft content.',
        'status' => 'draft',
    ]);

    Notification::assertNotSentTo($subscriber, NewPostPublished::class);

    // Update status to published
    $post->update(['status' => 'published']);

    Notification::assertSentTo($subscriber, NewPostPublished::class);
});

test('subscriber can unsubscribe via unsubscribe link', function () {
    $token = Str::random(32);
    $subscriber = Subscriber::create([
        'email' => 'leave@example.com',
        'token' => $token,
        'is_verified' => true,
    ]);

    $response = $this->get(route('unsubscribe', $token));
    $response->assertOk()
        ->assertViewIs('unsubscribed')
        ->assertSee('You have been unsubscribed');

    expect(Subscriber::where('email', 'leave@example.com')->exists())->toBeFalse();
});
