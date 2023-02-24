<?php

namespace App\Models\Api\firebase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kreait\Firebase\Database;
use Auth;
use Carbon\Carbon;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Kreait\Firebase\Http\HttpClientOptions;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth\SignInResult\SignInResult;

// use Laravel\Passport\HasApiTokens;


class UserFriendsRequestModel extends Model
{
    use HasFactory;
    /**
     * Create a new Model instance.
     *
     * @return void
     */
    private $database;
    protected $table;
    protected $user;
    protected $firebase_uid;
    protected $UIDtable;
    protected $requestSend;
    protected $firebaseAuth;

    public function __construct(Database $database,FirebaseAuth $firebaseAuth)
    {
        $this->firebaseAuth = $firebaseAuth;
        $this->database = $database;
        $this->table = $this->database->getReference('/user');
        $this->user = Auth::guard('app-api')->user();
        if($this->user){
            $this->firebase_uid = $this->user->firebase_uid;
            $this->UIDtable = $this->table->getChild($this->firebase_uid);
            $this->requestSend = $this->UIDtable->getChild('/friendRequestSend');
            $this->friendRequestCollection = $this->database->getReference('/friendRequestSend'); 
            $this->d_userRequest = $this->database->getReference('/friendRequestSend')->getChild($this->firebase_uid); 
            $this->userRequestList = $this->database->getReference('/requestReceiver');
        }
    }

    public function createFriendRequest(array $var = null)
    {
        $checkNotEmpty = $this->requestSend->getSnapshot()->getValue();
        $arr = array(
            'created_at' => Carbon::now()->toDateTimeString()
        );
        $requestReceiver_ = $this->database->getReference('/requestReceiver')->getChild($var['request_send_to_uid']);
        $requestReceiver_ = $requestReceiver_->getChild($this->firebase_uid)->set($arr);
        $ref = $this->requestSend->getChild($var['request_send_to_uid']);
        $postData = $ref->set($arr);
        $this->d_userRequest->getChild($var['request_send_to_uid'])->set($arr);
        return $this->requestSend->getSnapshot()->getValue();
    }

    public function acceptFriendRequest(array $var = null)
    {
        // dd($var);
        $requestSentbyUid = $var['request_sent_by_uid'];
        $checkValueExist = $this->friendRequestCollection->getChild($requestSentbyUid)->getSnapshot()->exists();
        if($checkValueExist){
            $friendList = $this->UIDtable->getChild('/friendList')->getChild($requestSentbyUid);
            $friendList->set(['created_at' => Carbon::now()->toDateTimeString()]);
            $this->friendRequestCollection->getChild($this->firebase_uid)->getChild($requestSentbyUid)->remove();
            $this->denormalize->getChild($requestSentbyUid)->getChild($this->firebase_uid)->remove();
            $sentUserRemoveRequest = $this->table->getChild($requestSentbyUid)->getChild('friendRequestSend');
            $sentUserRemoveRequest = $sentUserRemoveRequest->getChild($this->firebase_uid)->remove();
            return $this->userRequestList->getChild($this->firebase_uid)->getSnapshot()->getValue();
        }

    }
    
    public function friendRequestList()
    {
        $checkValueExist = $this->d_userRequest->getSnapshot()->exists();
        if($checkValueExist){
            $sendingRequest = $this->d_userRequest->getSnapshot()->getValue();
            $userList = array();
            foreach($sendingRequest as $key => $user){
                try {
                    $user = $this->firebaseAuth->getUser($key);
                    array_push($userList,$user->providerData[0]);
                } catch (UserNotFound $e) {
                    return $e->getMessage();
                }
            }
            return $userList;
        }
    }

    public function receiveFriendRequestList()
    {
        $checkValueExist = $this->userRequestList->getSnapshot()->exists();
        if($checkValueExist){
            $receivingRequest = $this->userRequestList->getSnapshot()->getValue();
            $userList = array();
            foreach($receivingRequest as $key => $user){
                try {
                    $user = $this->firebaseAuth->getUser($key);
                    array_push($userList,$user->providerData[0]);
                } catch (UserNotFound $e) {
                    return $e->getMessage();
                }
            }
            return $userList;
        }
    }
}
