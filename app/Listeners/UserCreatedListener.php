<?php

namespace App\Listeners;

use App\Events\UserCreatedEvent;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UserCreatedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UserCreatedEvent  $event
     * @return void
     */
    public function handle(UserCreatedEvent $event)
    {
        // once a user is created
        $user = $event->user;
        // update the username field
        $user->update([
            'username' => 'user'.$user->{'id'}
        ]);

    }
}
