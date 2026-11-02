<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('guests are redirected to login when visiting users', function () {
    $this->get(route('users.index'))
        ->assertRedirect(route('login'));
});

test('users without manage users permission cannot access users index', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    $this->actingAs($author)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('admin can view users list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $otherUser = User::factory()->create(['name' => 'John Doe']);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('John Doe');
});

test('admin can create a user with roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::users.create')
        ->set('name', 'Jane Editor')
        ->set('email', 'jane@example.com')
        ->set('password', 'password123')
        ->set('selectedRoles', ['editor'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('users.index'));

    $user = User::where('email', 'jane@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('editor'))->toBeTrue();
});

test('admin can update a user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);
    $user->assignRole('subscriber');

    Livewire::actingAs($admin)
        ->test('pages::users.edit', ['user' => $user])
        ->set('name', 'New Name')
        ->set('selectedRoles', ['author'])
        ->call('update')
        ->assertHasNoErrors()
        ->assertRedirect(route('users.index'));

    expect($user->fresh()->name)->toBe('New Name');
    expect($user->fresh()->hasRole('author'))->toBeTrue();
});

test('admin cannot delete their own account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Livewire::actingAs($admin)
        ->test('pages::users.index')
        ->call('deleteUser', $admin->id);

    expect(User::find($admin->id))->not->toBeNull();
});
