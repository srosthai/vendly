<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('a telegram account can save its name without an email', function () {
    $user = User::factory()->create(['email' => null, 'password' => null, 'telegram_id' => '42']);

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => 'Ada Telegram', 'email' => ''])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh()->name)->toBe('Ada Telegram');
    $this->get(route('dashboard'))->assertOk();
});

test('the profile keeps phone, telegram username, and bio', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Sokha',
            'email' => $user->email,
            'phone' => '+855 12 345 678',
            'telegram_username' => '@sokha_shop',
            'bio' => '<b>Tea</b> seller in Phnom Penh.',
        ])
        ->assertSessionHasNoErrors();

    expect($user->fresh())
        ->phone->toBe('+855 12 345 678')
        ->telegram_username->toBe('sokha_shop')
        ->bio->toBe('Tea seller in Phnom Penh.');
});

test('profile details are validated', function (string $field, string $value) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => 'Sokha', 'email' => $user->email, $field => $value])
        ->assertInvalid([$field]);
})->with([
    'a phone with letters' => ['phone', 'call me'],
    'a short telegram username' => ['telegram_username', '@ab'],
    'a very long bio' => ['bio', str_repeat('a', 501)],
]);

test('a profile photo is uploaded, replaced, removed, and shown as the avatar', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->image('me.png', 200, 200),
    ])->assertSessionHasNoErrors();

    $first = $user->fresh()->avatar_path;
    Storage::disk('public')->assertExists($first);

    $this->actingAs($user)->get(route('profile.edit'))
        ->assertInertia(fn ($page) => $page->where('auth.user.avatar', Storage::disk('public')->url($first)));

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->image('new.png', 200, 200),
    ]);
    Storage::disk('public')->assertMissing($first);

    $second = $user->fresh()->avatar_path;

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'remove_avatar' => '1',
    ]);

    expect($user->fresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($second);
});

test('accounts can no longer be deleted from settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->delete('/settings/profile')->assertMethodNotAllowed();

    expect($user->fresh())->not->toBeNull();
});
