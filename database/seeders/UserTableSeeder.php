<?php

namespace Database\Seeders;

use App\Events\UserCreatedEvent;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'clickkapps@gmail.com',
            'password' => Hash::make('0541243508'),
        ]);
        $admin->refresh();
        $admin->assignRole(['Admin']);
        UserCreatedEvent::dispatch($admin);
    }
}
