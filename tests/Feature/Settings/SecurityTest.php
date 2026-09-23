<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('the old security and appearance pages open the profile page', function (string $path) {
    $this->actingAs(User::factory()->create())
        ->get($path)
        ->assertRedirect('/settings/profile');
})->with(['/settings/security', '/settings/appearance']);

test('password can be updated from the profile page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('profile.edit'));
});

test('an account without a password sets a first password from the profile page', function () {
    $user = User::factory()->create(['password' => null]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/profile')->where('auth.hasPassword', false));

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasNoErrors();

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});
