<?php

namespace Database\Seeders;

use App\Actions\Stores\CreateStore;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Login accounts for local testing. Every password is "password".
     *
     * - admin@vendly.test manages vendors, plans, payments, and Telegram.
     * - vendor@vendly.test owns the Smile Tea store.
     * - customer@vendly.test can shop and send a request.
     */
    public function run(): void
    {
        $admin = $this->account('Admin', 'admin@vendly.test');
        $admin->is_admin = true;
        $admin->save();

        $vendor = $this->account('Vendor', 'vendor@vendly.test');

        if ($vendor->store()->doesntExist()) {
            app(CreateStore::class)->handle($vendor, 'Smile Tea', 'Tea and small cakes');
        }

        $this->account('Customer', 'customer@vendly.test');
    }

    private function account(string $name, string $email): User
    {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
    }
}
