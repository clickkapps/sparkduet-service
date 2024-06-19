<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FirebaseService
{
    protected \Kreait\Firebase\Contract\Auth $auth;

    public function __construct()
    {

//        Log::info('Initializing FirebaseService...');
//        $factory = (new Factory)
//            ->withServiceAccount(Storage::path(config('firebase.projects.app.credentials')));
////            ->withDatabaseUri(config('firebase.projects.app.l'));
//        Log::info('Firebase Factory created.');
//
//        $this->auth = $factory->createAuth();
//        Log::info('FirebaseService initialized.');
        $this->auth = Firebase::auth();
    }

    /**
     * @throws FirebaseException
     * @throws AuthException
     * @throws Exception
     */
    public function createFirebaseUser($email, $password): Auth\UserRecord
    {
        $userProperties = [
            'email' => $email,
            'password' => $password,
        ];

        try {

            return $this->auth->createUser($userProperties);
        } catch (Exception $e) {
            throw new Exception('Error creating Firebase user: ' . $e->getMessage());
        }
    }

    /**
     * @throws FirebaseException
     * @throws AuthException
     * @throws Exception
     */
    public function createCustomToken($uid): \Lcobucci\JWT\UnencryptedToken
    {
        try {
            return $this->auth->createCustomToken($uid);
        } catch (Exception $e) {
            throw new Exception('Error creating custom token: ' . $e->getMessage());
        }
    }

    /**
     * @throws FirebaseException
     * @throws AuthException
     * @throws Exception
     */
    public function getFirebaseUser($email): Auth\UserRecord
    {
        try {
            return $this->auth->getUserByEmail($email);
        } catch (\Exception $e) {
            throw new \Exception('Error retrieving Firebase UID: ' . $e->getMessage());
        }
    }
}
