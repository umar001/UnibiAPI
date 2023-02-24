<?php

namespace App\Models\Api\firebase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kreait\Firebase\Database;
use Auth;
use Illuminate\Support\Str;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Laravel\Passport\HasApiTokens;
use App\Models\Api\AppUser;
use Illuminate\Support\Facades\Hash;
use App\Mail\OtpViaEmail;
use DateTime;
use Mail;
use DB;

class AuthUserModel extends Model
{
    use HasFactory,HasApiTokens;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    private $database;
    protected $table;
    protected $user;
    protected $firebase_uid;
    protected $UIDtable;
    protected $userProfile;

    public function __construct(Database $database,FirebaseAuth $firebaseAuth)
    {
        // Firebase Auth
        $this->firebaseAuth = $firebaseAuth;
        // Firebase database
        $this->database = $database;
        // User Collections
        $this->table = $this->database->getReference('/user');
        $this->user = Auth::guard('app-api')->user();
        if($this->user){
            $this->firebase_uid = $this->user->firebase_uid;
            // Login user Detail
            $this->UIDtable = $this->table->getChild($this->firebase_uid);
            // User Profile detail
            $this->userProfile = $this->UIDtable->getChild('/userProfile');
            // Duplicate User Profile Collection
            $this->d_userProfile = $this->database->getReference('/userProfile');
        }
    }

    public function ApiRegisterUser(array $data = null)
    {
        $auth = app('firebase.auth');
        $userInfo = array(
            'email' => $data['email'],
            'emailVerified' => false,
            'password'=> $data['password'],
            'disabled' => false,
        );
        try{
            $user  = $auth->createUser($userInfo);
            $firebase_uid = $user->uid;
            $result=DB::table('app_user_age_preferences')->insert([
                'firebase_uid'=>$firebase_uid,
                'start_age'=>18,
                'end_age'=>50
                ]);
            if($result)
            {
                $var = array(
                    'start_age'=>18,
                    'end_age'=>50
                );
               
                $this->table->getChild($firebase_uid)->getChild('/agePreference')->set($var);
               
            }
            
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ],401);
        }
        $random = Str::random(40);
        if($user){
            $two_factor_secret = rand(1000, 9999);
            $two_factor_expiry_at = now()->addMinutes(10);
            $user = AppUser::firstOrCreate([ 'email' => $data['email']],[
                                                  'password'=> Hash::make($data['password']),
                                                  'firebase_uid' => $firebase_uid,
                                                  'device_token' => $data['device_token'],
                                                  'device_type' => $data['device_type'],
                                                  'two_factor_secret' => $two_factor_secret,
                                                  'two_factor_expiry_at' => $two_factor_expiry_at,
                                                ]);
            if($user->wasRecentlyCreated){
                $detail = [
                    'title' => 'Email Verification',
                    'body'  => 'test body',
                    'code'  => $two_factor_secret
                ];
                Mail::to($data['email'])->send(new OtpViaEmail($detail));
                $token = $user->createToken($random)->accessToken;
                return response()->json([
                    'status' => 'success',
                    'message' => 'User Successfully Registered',
                    'data' => [
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        // 'expires_at' => $tokenResult->token->expires_at,
                        'user' => $user,
                    ],
                ]);
            }else{
                return response()->json(['status'=>'error', 'message'=> 'User Already Exist'], 200);
            }
        }else{
            return response()->json(['message'=> 'error'], 200);
        }
    }

}