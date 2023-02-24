<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\Database\ReferenceHasNotBeenSnapshotted;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging;

class PushNotificationController extends Controller
{
    //
    private $database;
    protected $messaging;
    /**
     * Create a new Model instance.
     *
     * @return void
     */
    public function __construct(Database $database,FirebaseAuth $firebaseAuth,Messaging $messaging){
        $this->messaging = $messaging;
    }

    public function sendNotification()
    {
        $message = CloudMessage::withTarget('token', $deviceToken)
                    ->withNotification($notification) // optional
                    ->withData($data); // optional
        $test = $this->messaging->send($message);
        dd($test);
    }
}
