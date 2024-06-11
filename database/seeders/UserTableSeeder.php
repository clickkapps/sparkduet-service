<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;

class UserTableSeeder extends Seeder
{

    protected FirebaseService $firebaseService;
//
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    /**
     * @throws AuthException
     * @throws FirebaseException
     * @throws \Exception
     */
    public function run()
    {
        $email = config('custom.super_admin_email');
        $password = Hash::make(config('custom.super_admin_password'));
        $admin = User::with([])->create([
            'name' => 'Admin',
            'email' => $email,
            'password' => $password,
            'last_login_at' => now(),
            'email_verified_at' => now()
        ]);
        $admin->refresh();
        $admin->assignRole(['Admin']);

        // create credentials on firebase
        $this->firebaseService->createFirebaseUser($email, $password);
//        $customToken = $this->firebaseService->createCustomToken($firebaseUser->uid)->toString();
    }
}
