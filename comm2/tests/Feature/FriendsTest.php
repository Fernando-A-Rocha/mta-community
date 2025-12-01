<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\FriendService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

test('authenticated users can add friends from a profile page', function () {
    $user = createTestUser();
    $friend = createTestUser();

    $response = $this->actingAs($user)
        ->from(route('profile.show', $friend))
        ->post(route('friends.store', $friend));

    $response->assertRedirect(route('profile.show', $friend));
    $response->assertSessionHas('friends_success');

    $this->assertDatabaseHas('friendships', [
        'user_id' => $user->id,
        'friend_id' => $friend->id,
    ]);

    $this->assertDatabaseHas('friendships', [
        'user_id' => $friend->id,
        'friend_id' => $user->id,
    ]);
});

test('users can remove friends via the controller endpoint', function () {
    $user = createTestUser();
    $friend = createTestUser();

    app(FriendService::class)->add($user->fresh(), $friend->fresh());

    $response = $this->actingAs($user)
        ->from(route('profile.show', $friend))
        ->delete(route('friends.destroy', $friend));

    $response->assertRedirect(route('profile.show', $friend));
    $response->assertSessionHas('friends_success');

    $this->assertDatabaseMissing('friendships', [
        'user_id' => $user->id,
        'friend_id' => $friend->id,
    ]);

    $this->assertDatabaseMissing('friendships', [
        'user_id' => $friend->id,
        'friend_id' => $user->id,
    ]);
});

test('public friends list is visible on profile pages', function () {
    $user = createTestUser();
    $friend = createTestUser();

    app(FriendService::class)->add($user->fresh(), $friend->fresh());

    $response = $this->get(route('profile.show', $user));

    $response->assertOk();
    $response->assertSeeText(__('Friends'));
    $response->assertSeeText($friend->name);
});

test('private friends list stays hidden from other users', function () {
    $user = createTestUser(['friends_visibility' => 'private']);
    $friend = createTestUser();
    $viewer = createTestUser();

    app(FriendService::class)->add($user->fresh(), $friend->fresh());

    $response = $this->actingAs($viewer)->get(route('profile.show', $user));

    $response->assertOk();
    $response->assertSeeText(__('This user keeps their friends list private.'));
    $response->assertDontSeeText($friend->name);
});

function createTestUser(array $overrides = []): User
{
    static $counter = 1;

    return User::create(array_merge([
        'name' => Str::lower('user'.$counter++),
        'email' => Str::uuid()->toString().'@example.com',
        'password' => Hash::make('secret-password'),
    ], $overrides));
}

