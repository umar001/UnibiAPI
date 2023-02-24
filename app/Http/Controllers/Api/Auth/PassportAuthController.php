<?php

namespace App\Http\Controllers\Api\Auth;
// use App\Http\Controllers\Api\FirebaseController;

// Dependency
use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\FirebaseException;
use Laravel\Passport\PersonalAccessTokenResult;
// APi Repository
use  App\Repositories\Api\ApiUserAuthRepository;
use Kreait\Firebase\Database;
use Illuminate\Support\Str;
use App\Models\Api\AppUser as ApiUsers;
// Request
use App\Http\Requests\Api\ApiAuthRequest;
use App\Http\Requests\Api\RegisterApiRequest;
use App\Http\Requests\Api\ApiLoginRequest;
use App\Models\Api\firebase\AuthUserModel;
use Auth;
use Mail;
use App\Models\Api\AppUser;
use App\Mail\OtpViaEmail;
use Carbon\Carbon;
class PassportAuthController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    private $apiUserRepo;
    protected $auth;
    protected $userProfileModel;
    public function __construct(ApiUserAuthRepository $apiUserRepo,FirebaseAuth $auth, AuthUserModel $userProfileModel)
    {
        // $this->middleware('auth');
        $this->apiUserRepo = $apiUserRepo;
        $this->auth = $auth;
        $this->userProfileModel = $userProfileModel;
    }
    /**
     * Registration
     */
    public function register(RegisterApiRequest $request)
    {
        // dd('here');
        // dd($this->auth);
        $repoResponse = $this->userProfileModel->ApiRegisterUser($request->all());
 
        return $repoResponse;
    }

    public function password(Request $request)
    {
    $request->validate([
        'email' => 'required|email',
    ]);
    $code = rand(1000, 9999);
    $resetUpdate = AppUser::where('email',$request->email)->update(['password_reset'=>$code]);
    if($resetUpdate){
        $details = [
            'title' => 'Reset Password',
            'body'  => 'Please',
            'code'  => $code
        ];
        \Mail::to($request->email)->send(new OtpViaEmail($details));
        return response()->json([
            'status' => 'success',
            'message' => 'Email sent please check your mail',
            'data' => [],
        ]);
    }else{
        return response()->json([
            'status' => 'error',
            'message' => 'Email you enetered does not belong to any account',
        ],401);
    }
   }
   public function resetPassword(Request $request){
      $request->validate([
            'email' => 'required|email',
            'remember_token' => 'required',
            'password' => 'required|confirmed',
      ]);
      $auth = app('firebase.auth');
      $checkToken = AppUser::where(['email'=>$request->email,'password_reset' => $request->remember_token])->get();
      if($checkToken->isNotEmpty()){
          $user=AppUser::where('email',$request->email)->update([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
            'password_reset_at' => Carbon::now()->toDateTimeString(),
            'password_reset'=> null
          ]);
        
          $user1=AppUser::where('email',$request->email)->first();
          $uid = $auth->getUsers([$user1->firebase_uid]);
           $auth->changeUserPassword($user1->firebase_uid, $request->password);
                
          return response()->json([
            'status' => 'success',
            'message' => 'User password Successfully Updated',
            'data' => [],
            ]);
      }else{
        return response()->json([
            'status' => 'error',
            'message' => 'The code you entered not correct',
         ],401);
      }
}
    /**
     * Login
     */
    public function login(ApiLoginRequest $request)
    {
        $repoResponse = $this->apiUserRepo->ApiloginUser($request->all());
        return $repoResponse;
    }  
    
    public function getData(Request $request)
    {
        dd($request);
        # code...
    }

    public function logout(Request $request)
    {
        $repoResponse = $this->apiUserRepo->ApiUserLogout($request);
        return $repoResponse;
    }
}