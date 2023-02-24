<?php

namespace App\Repositories\Api;

// Dependency
use JasonGuru\LaravelMakeRepository\Repository\BaseRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\FirebaseException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Laravel\Passport\HasApiTokens;
use Kreait\Firebase\Database;
// use Auth;
// Model
use App\Models\Api\AppUser;
use App\Models\Api\firebase\UserProfileModel;
/**
 * Class ApiUserRepository.
 */
class ApiUserAuthRepository extends BaseRepository
{

    use HasApiTokens;
    /**
     * @return string
     *  Return the model
     */
    public function model()
    {
        return AppUser::class;
    }
    /**
     * Register User through API
     *
     */
    public function ApiRegisterUser($data)
    {
        // dd(FirebaseAuth);
        $database = new Database;
      
        $auth = app('firebase.auth');
        $userInfo = array(
            'email' => $data['email'],
            'emailVerified' => false,
            'password'=> $data['password'],
            'displayName'   => $data['name'],
            'disabled' => false,
        );
        $user  = $auth->createUser($userInfo);
        $firebase_uid = $user->uid;
        $random = Str::random(40);
        if($user){
            // dd($firebase_uid);
            $userProfile = array(
                'date_of_birth' => $data['date_of_birth'],
                'gender'        => $data['gender'],
                'interests'     => $data['interests'],
                'languages'     => $data['languages'],
            );
            $this->userDatabase->getChild($firebase_uid)->getChild('userProfile')->set($userProfile);
            $user = AppUser::firstOrCreate([ 'email' => $data['email']],[
                                                  'password'=> Hash::make($data['password']),
                                                  'name'   => $data['name'],
                                                  'firebase_uid' => $firebase_uid
                                                ]);
            if($user->wasRecentlyCreated){
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
    /**
     * Login User through API
     *
     */
    public function ApiloginUser($data)
    {
        // dd($data);
        $auth = app('firebase.auth');
        $database = app('firebase.database');
        // dd(auth()->guard('app')->attempt($data));
        try {
            $signInResult = $auth->signInWithEmailAndPassword($data['email'], $data['password']);
            $firebase_uid = $signInResult->data()['localId'];
            $data['firebase_uid'] = $firebase_uid;
            $device_token = $data['device_token'];
            $device_type = $data['device_type'];
           
            unset($data['device_token']);
            unset($data['device_type']);
            $random = Str::random(40);
           
            if (auth()->guard('app')->attempt($data)) {
                if(!empty($device_token) && !empty($device_type)){
                    $this->model->where('firebase_uid',$firebase_uid)->update(['device_token'=>$device_token, 'device_type' => $device_type]);
                }
                $token = auth()->guard('app')->user()->createToken($random)->accessToken;
                $userDetails = $database->getReference('/user')->getChild($firebase_uid)->getSnapshot()->getValue();
                $agep = $database->getReference('/user')->getChild($firebase_uid)->getChild('/agePreference')->getSnapshot()->getValue();
                $arr=[];
                if($userDetails && isset($userDetails['userDetail']) && isset($userDetails['userProfile'])){
                    $userDetails['userDetail'] +=$userDetails['userProfile'];
                    unset($userDetails['userProfile']);
                }else{
                    $userDetails['userDetail'] = array(
                        "bio"=> null,
                        "date_of_birth"=> null,
                        "first_name"=> null,
                        "gender"=> null,
                        "last_name"=> null,
                        "interests"=> null,
                        "languages"=> null,
                        "topics"=> null
                    );
                }
                if($userDetails && !empty($userDetails['profileImages'])){
                    $userDetails['profileImages'] = array_values($userDetails['profileImages']);
                }else{
                    $userDetails['profileImages'] = [];
                }
                if($userDetails && !empty($userDetails['userPost'])){
                    $userDetails['userPost'] = array_values($userDetails['userPost']); 
                }else{
                    $userDetails['userPost'] =[];
                }
                $userDetails['agePreference'] = $agep;
                $arr=array(
                    'login_detail' => auth()->guard('app')->user(),
                    'user_profile' => $userDetails
                );
                return response()->json([
                    'status' => 'success',
                    'message' => 'User Successfully Login',
                    'data' => [
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        // 'expires_at' => $tokenResult->token->expires_at,
                        'user' => $arr,
                    ],
                    200
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 401);
            }
         } catch (FirebaseException $e) {
            throw ValidationException::withMessages(['message' => trans('auth.failed'),]);
         }
    }

    public function ApiUserLogout($data)
    {
        $user = $data->user()->token();
        $user->revoke();
        return response()->json([
            'status' => 'success',
            'message' => 'User Successfully logged out'
            ]);
    }
    
}
