<?php

use App\Livewire\Blog\Comments;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('public users see comment component and login prompt when guest', function () {
    $author = User::factory()->create();
    $post = Post::create([
        'user_id' => $author->id,
        'title' => 'Test Post With Comments',
        'slug' => 'test-post-with-comments',
        'content' => 'Post content.',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $this->get(route('blog.show', $post->slug))
        ->assertOk()
        ->assertSee('Comments (0)')
        ->assertSee('You must be logged in to comment.')
        ->assertSee(route('login'));
});

test('authenticated user can post a comment on a blog post', function () {
    $user = User::factory()->create();
    $post = Post::create([
        'user_id' => $user->id,
        'title' => 'Post to comment on',
        'slug' => 'post-to-comment-on',
        'content' => 'Content here.',
        'status' => 'published',
        'published_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Comments::class, ['post' => $post])
        ->set('newComment', 'This is an awesome post!')
        ->call('postComment')
        ->assertDispatched('comment-posted')
        ->assertSee('Comment posted successfully!')
        ->assertSee('This is an awesome post!');

    expect(Comment::where('post_id', $post->id)->where('user_id', $user->id)->count())->toBe(1);
    expect(Comment::first()->content)->toBe('This is an awesome post!');
    expect(Comment::first()->status)->toBe('approved');
});

test('cannot post comment with less than 3 characters', function () {
    $user = User::factory()->create();
    $post = Post::create([
        'user_id' => $user->id,
        'title' => 'Another Post',
        'slug' => 'another-post',
        'content' => 'Content',
        'status' => 'published',
        'published_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Comments::class, ['post' => $post])
        ->set('newComment', 'hi')
        ->call('postComment')
        ->assertHasErrors(['newComment' => 'min']);

    expect(Comment::count())->toBe(0);
});

test('authenticated user can reply to a comment', function () {
    $user = User::factory()->create();
    $replier = User::factory()->create();

    $post = Post::create([
        'user_id' => $user->id,
        'title' => 'Post with replies',
        'slug' => 'post-with-replies',
        'content' => 'Content',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $parentComment = Comment::create([
        'post_id' => $post->id,
        'user_id' => $user->id,
        'content' => 'First parent comment',
        'status' => 'approved',
    ]);

    Livewire::actingAs($replier)
        ->test(Comments::class, ['post' => $post])
        ->call('startReply', $parentComment->id)
        ->set('replyContent', 'I agree with your comment!')
        ->call('postReply', $parentComment->id)
        ->assertDispatched('comment-posted')
        ->assertSee('Reply posted successfully!');

    $reply = Comment::where('parent_id', $parentComment->id)->first();
    expect($reply)->not->toBeNull();
    expect($reply->content)->toBe('I agree with your comment!');
    expect($reply->user_id)->toBe($replier->id);
});

test('guests are redirected when accessing admin comments route', function () {
    $this->get(route('comments.index'))
        ->assertRedirect(route('login'));
});

test('user without create posts permission cannot access admin comments page', function () {
    $subscriber = User::factory()->create();
    $subscriber->assignRole('subscriber');

    $this->actingAs($subscriber)
        ->get(route('comments.index'))
        ->assertForbidden();
});

test('admin can view and moderate comments', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $post = Post::create([
        'user_id' => $admin->id,
        'title' => 'Admin Post',
        'slug' => 'admin-post',
        'content' => 'Content',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $comment = Comment::create([
        'post_id' => $post->id,
        'user_id' => $admin->id,
        'content' => 'Moderation needed comment',
        'status' => 'pending',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::comments.index')
        ->assertSee('Moderation needed comment')
        ->assertSee('Pending')
        ->call('approveComment', $comment->id)
        ->assertSee('Comment approved!');

    expect($comment->fresh()->status)->toBe('approved');

    Livewire::actingAs($admin)
        ->test('pages::comments.index')
        ->call('markAsSpam', $comment->id)
        ->assertSee('Comment marked as spam!');

    expect($comment->fresh()->status)->toBe('spam');

    Livewire::actingAs($admin)
        ->test('pages::comments.index')
        ->call('deleteComment', $comment->id)
        ->assertSee('Comment deleted!');

    expect(Comment::find($comment->id))->toBeNull();
});

test('posts index displays comments count badge when comments exist', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $post = Post::create([
        'user_id' => $admin->id,
        'title' => 'Post With Comment Badge',
        'slug' => 'post-with-comment-badge',
        'content' => 'Content',
        'status' => 'published',
        'published_at' => now(),
    ]);

    Comment::create([
        'post_id' => $post->id,
        'user_id' => $admin->id,
        'content' => 'Nice article!',
        'status' => 'approved',
    ]);

    $this->actingAs($admin)
        ->get(route('posts.index'))
        ->assertOk()
        ->assertSee('1 comment');
});
