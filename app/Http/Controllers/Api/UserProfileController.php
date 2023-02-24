<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\UserProfileRequest;
use App\Http\Requests\Api\ProfileImagesRequest;
use Illuminate\Support\Facades\Hash;

// Model
use App\Models\Api\firebase\UserProfileModel;
use App\Models\Api\firebase\AppUserLocation;
use App\Models\Api\AppUser;
// Repository
use App\Repositories\Api\firebase\UserProfileRepository;
use Auth;
use Mail;
use App\Mail\OtpViaEmail;
use App\Models\Api\firebase\AppPostLocation;
use DB;
class UserProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $model;
    private $userProfileRepo;
    public function __construct(UserProfileRepository $userProfileRepo, UserProfileModel $model)
    {
        $this->middleware('auth:app-api');
        $this->userProfileRepo = $userProfileRepo;
        $this->model = $model;
        $this->user = Auth::guard('app-api')->user();
        if($this->user){
            $this->firebase_uid = $this->user->firebase_uid;
        }
    }
    /**
     * Create a App User Profile.
     *
     * @return void
     */
    public function store(Request $request)
    { 
        $request->validate([
            'bio' => 'required',
            'interests' => 'required',
            'topics' => 'required',
            'language' => 'required',
        ]);
        $returnResponse = $this->userProfileRepo->createUserProfile($request->all());
        return $returnResponse;
    }

    public function update(Request $request,$id)
    {
        if(!empty($request->date_of_birth)){
            $request->validate([
                'date_of_birth' => 'required|date_format:Y-m-d',
            ]);
        }
        $returnResponse = $this->userProfileRepo->update($request->all(),$id);
        return $returnResponse;
    }
    public function getUserProfile()
    {
        $returnResponse = $this->model->showProfile();
        return $returnResponse;
    }

    public function destroy($id)
    {
        $returnResponse = $this->userProfileRepo->destroy($id);
        return $returnResponse;
    }

    public function userFriendList()
    {    

        $returnResponse = $this->userProfileRepo->userFriendList();
        return $returnResponse;
    }
    public function userDetails(Request $request)
    {
        $validated = $request->validate([
            'date_of_birth' => 'required|date_format:Y-m-d',
            'gender' => 'required',
            'interests' => 'required',
            'topics' => 'required',
            'languages' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'bio' => 'required',
            
        ]);
        if($validated){
            $returnResponse = $this->model->userDetails($validated);
            return $returnResponse;
        }
    }
    /**
     * 
     * User Profile Images Methods
     */
    public function profileImages(ProfileImagesRequest $request)
    {
        $returnResponse = $this->userProfileRepo->profileImages($request->all());
        return $returnResponse;
    }
    public function multipleImageUpload(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'images' => 'required'
        ]);
        $returnResponse = $this->userProfileRepo->multipleImageUpload($request->all());
        return $returnResponse;
    }
    public function updateProfileImages(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'profile_image' => 'required',
            'active_profile_image' => 'required',
            'previous_image_path' => 'required',
        ]);
        $returnResponse = $this->userProfileRepo->updateProfileImages($request->all());
        return $returnResponse;
    }
    public function deleteUserImage(Request $request)
    {
        $request->validate([
            'image_path' => 'required'
        ]);
        $returnResponse = $this->userProfileRepo->deleteUserImage($request->all());
        return $returnResponse;
    }
    public function userProfileImagesList()
    {
        $returnResponse = $this->userProfileRepo->userProfileImagesList();
        return $returnResponse;
    }

    public function userLocation(Request $request)
    {
        $request->validate([
            'location' => 'required'
        ]);
        $returnResponse = $this->model->userLocation($request->all());
        return $returnResponse;
    }
    
    public function userPreferenceAgeRange(Request $request)
    {
        $request->validate([
            'start_age' => 'required',
            'end_age' => 'required'
        ]);
        $returnResponse = $this->model->userPreferenceAgeRange($request->all());
        return $returnResponse;
    }
    public function getUserAgeRange()
    {
        $returnResponse = $this->model->getUserAgeRange();
        return $returnResponse;
    }

    public function verifyEmail(Request $request)
    {
        $email = $request->email;
        if(!empty($email)){
            $two_factor_secret = rand(100000, 999999);
            $two_factor_expiry_at = now()->addMinutes(10);
            $verifyCode = array(
                'two_factor_secret' => $two_factor_secret,
                'two_factor_expiry_at' => $two_factor_expiry_at,
            );
            $user = AppUser::where('email',$email)->update($verifyCode);
            if($user){
                $detail = [
                    'title' => 'test',
                    'body'  => 'test body',
                    'code'  => $two_factor_secret
                ];
                Mail::to($email)->send(new OtpViaEmail($detail));
                return response()->json([
                    'status' => 'success',
                    'message' => 'Email sent please check your mail',
                    'data' => [],
                ]);
            }
        }
    }

    public function emailCodeVerification(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'code' => 'required'
        ]);
        $email = $request->email;
        $code =  $request->code;
        $user = AppUser::where('email', $email)->get();
        if($user->isNotEmpty()){
            $currentDateTime = now();
            if(strtotime($user[0]->two_factor_expiry_at) > strtotime($currentDateTime))
            {
                $verify = AppUser::where(['email'=>$email,'two_factor_secret' => $code ])->get();
                if($verify->isNotEmpty() ){
                    $returnResponse = $this->model->verifiedUser($verify[0]);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'User email Successfully verified',
                        'data' => [],
                    ]);
                }else{
                    return response()->json([
                        'status' => 'error',
                        'message' => 'The code you entered is not valid. Please check the latest OTP that you have recieved.'
                    ],401);
                }
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your code is expired'
                ],401);
            }
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Your entered email does not bleong to any account'
            ],401);
        }
    }
    
    
  
    public function postLocationFinder()
    {
        $location = AppPostLocation::where('firebase_uid',$this->firebase_uid)->get();
        $postLocation = $this->find($location[0]->lat,$location[0]->lon);
        $returnResponse = $this->model->postLocationFinder($postLocation);
        return response()->json([
            'status' => 'success',
            'message' => 'User nearby active user within 300m',
            'data' => $returnResponse,
        ]);
    }
    public function postNearToYourLocation()
    {
        $location = AppUserLocation::where('firebase_uid',$this->firebase_uid)->get();
        if($location->isNotEmpty()){
            $postLocation = $this->find($location[0]->lat,$location[0]->lon);
            // dd($postLocation);
            $returnResponse = $this->model->postLocationFinder($postLocation);
            if(!empty($returnResponse)){
                return response()->json([
                    'status' => 'success',
                    'message' => 'Other User Post Location',
                    'data' => $returnResponse,
                ]);
            }else{
                return response()->json([
                    'status' => 'success',
                    'message' => 'No post found',
                ]);
            }
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'There is no user location please add user location first then try',
            ]);
        }
    }
    public function userLocationFinder()
    {
        $location = AppUserLocation::where('firebase_uid',$this->firebase_uid)->get();
        $postLocation = $this->find($location[0]->lat,$location[0]->lon);
        $returnResponse = $this->model->postLocationFinder($postLocation);
        return response()->json([
            'status' => 'success',
            'message' => 'Other users with in 300m',
            'data' => $returnResponse,
        ]);
    }

    private function find($latitude, $longitude, $radius = 30000)
    {
        /*
         * 
         * replace 6371000 with 6371 for kilometer and 3956 for miles
         */
        $postLocation = AppPostLocation::selectRaw("id, firebase_uid,
        ( 6371000  * acos( cos( radians($latitude) ) * cos( radians( lat ) ) 
        * cos( radians( lon ) - radians($longitude) ) + sin( radians($latitude) ) * sin(radians(lat)) ) ) AS distance ")
            // ->where('firebase_uid','!=',$this->firebase_uid)
            ->having("distance", "<", $radius)
            ->offset(0)
            ->limit(20)
            ->get();
        // dd(\DB::getQueryLog());
        return $postLocation;
    }

    public function updatePassword(Request $request)
    {
        // dd($this->user->password);
        $request->validate([
            'old_password' => 'required',
            'password' => 'required|confirmed'
        ]);
        if(Hash::check($request->old_password, $this->user->password)){
            $auth = app('firebase.auth');
            $user=AppUser::where('id',$this->user->id)->update([
                'password' => Hash::make($request->password)
              ]);
           $auth->changeUserPassword($this->firebase_uid, $request->password);
           return response()->json([
            'status' => 'Success',
            'message' => 'Password Successfully updated',
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Your old password is incorrect please enter a right one',
            ],401);
        }

        
    }
    //list of deactive reason
    public function getsuggestionlist()
    {
           $data = DB::table('deactive_account_suggestion as rs')->where('status',1)->get(['rs.statment as statement','rs.id']);
           return response()->json($data);
    }
    //end of
    //function delete account
    function deleteAccount(Request $request)
    {
        $request->validate([
            'reason_id' => 'required',
        ]);
         extract($request->all());
        
        $uid = $this->firebase_uid;
       return $this->model->deleteAccountdata($uid,$reason_id);
      
    }
    //end of delete account
}
