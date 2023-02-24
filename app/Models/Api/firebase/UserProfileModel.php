<?php

namespace App\Models\Api\firebase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kreait\Firebase\Database;
use Auth;
use Carbon\Carbon;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\UserNotFound;

use App\Models\Api\firebase\AppUserLocation;
use App\Models\Api\firebase\AppUserAge;
use App\Models\Api\firebase\AppUserAgePreference;
use DateTime;
use DB;
use Illuminate\Support\Facades\File; 
class UserProfileModel extends Model
{
    use HasFactory;
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
    protected $userPost;
    protected $userAgePreference;
    protected $tagList;
    protected $userPostData;
    protected $reportPost;

    public function __construct(Database $database,FirebaseAuth $firebaseAuth)
    {
        // Firebase Auth
        $this->firebaseAuth = $firebaseAuth;
        // Firebase database
        $this->database = $database;
        // User Collections
        $this->table = $this->database->getReference('/user');
        $this->userPost =$this->database->getReference('/userPostData');
        $this->user = Auth::guard('app-api')->user();
        if($this->user){
            $this->firebase_uid = $this->user->firebase_uid;
            // Login user Detail
            $this->UIDtable = $this->table->getChild($this->firebase_uid);
            // User Profile detail
            $this->userProfile = $this->UIDtable->getChild('/userProfile');
            //user age preference
            $this->userAgePreference = $this->UIDtable->getChild('/agePreference');
            // Duplicate User Profile Collection
            $this->d_userProfile = $this->database->getReference('/userProfile');
            //new
            $this->tagList = $this->database->getReference('/postTagFriends');
            $this->userTagList = $this->tagList->getChild($this->firebase_uid);
            $this->reportPost = $this->database->getReference('/reported_posts');
            // FRD PostDetail Collection 
            $this->userPostData = $this->database->getReference('/userPostData');
        }
    }

    public function createProfile(array $arr = null)
    {
        $postData = $this->userProfile->set($arr);
        
        // $age_date = date('Y-m-d', strtotime($age_date));

        $this->database->getReference('/userProfile')->getChild($this->firebase_uid)->set($arr);
        return $this->userProfile->getValue();
    }

    public function updateProfile($var,$id)
    {
        unset($var['_method']);
        $this->table = $this->table->getChild($id);
        // $userPreference = array(
        //     'topics' => $var['topics'],
        //     'interests' => $var['interest'],
        //     'languages' => $var['language'],
        // );
         $userProfile = $this->table->getChild('userProfile')->getSnapshot()->getValue();
         if(!empty($var['topics'])){
            $userProfile['topics'] = $var['topics'];
         }
         if(!empty($var['interests'])){
            $userProfile['interests'] = $var['interests'];
         }
         if(!empty($var['languages'])){
            $userProfile['languages'] = $var['languages'];
         }
         $this->table->getChild('userProfile')->set($userProfile);
         $userDetail = $this->table->getChild('userDetail')->getSnapshot()->getValue();
         if(!empty($var['first_name'])){
            $userDetail['first_name'] = $var['first_name'];
         }
         if(!empty($var['last_name'])){
            $userDetail['last_name'] = $var['last_name'];
         }
         if(!empty($var['date_of_birth'])){
            $userDetail['date_of_birth'] = $var['date_of_birth'];
         }
         if(!empty($var['gender'])){
            $userDetail['gender'] = $var['gender'];
         }
         if(!empty($var['bio'])){
            $userDetail['bio'] = $var['bio'];
         }
         $this->table->getChild('userDetail')->set($userDetail);
         $userProfile = $this->table->getChild('userProfile')->getSnapshot()->getValue();
         $userDetail = $this->table->getChild('userDetail')->getSnapshot()->getValue();

         return array('userProfile'=> $userProfile,'userDetail'=>$userDetail);
    }
    public function showProfile()
    {
        try{
            $data =$this->refineObject($this->table->getChild($this->firebase_uid)->getSnapshot()->getValue());
            return response()->json([
                'status' => 'success',
                'message' => 'User Profile detail',
                'data' => $data
            ]);
        }catch(\Exception $e){
            return $e->getMessage();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ],401);
        }
    }
    public function deleteProfile($id)
    {
        $checkNotEmpty = $this->table->getSnapshot()->exists();
        if($checkNotEmpty){
            $this->table = $this->table->getChild($id);
            if($this->table->getSnapshot()->exists()){
                $this->table->remove();
                return response()->json([
                    'status' => 'success',
                    'message' => 'User Profile Successfully Deleted',
                ]);
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'No id Found',
                ],401);
            }
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'No data Found',
            ],401);
        }
    }

    public function userFriendList()
    {
       
        try{
            $friendsList = $this->table->getChild($this->firebase_uid)->getChild('/friendlist')->getSnapshot()->getValue();
            
            $userList = array();
            // dd($friendsList);
            foreach($friendsList as $key=>$value){
                try {
                    
                    $user = $this->firebaseAuth->getUser($key);
                  
                    array_push($userList,$user->providerData[0]);
                } catch (UserNotFound $e) {
                    return $e->getMessage();
                }
            }
            return $userList;
        }catch(\Exception $e){
            return $e->getMessage();
        }
    }
    public function userDetails(array $var = null)
    {
        $age_date = $var['date_of_birth'];
        $age_D = date('Y-m-d', strtotime($age_date));
        $age_date = new DateTime($var['date_of_birth']);
        $current = new DateTime();
        $interval = $current->diff($age_date);
        $years = $interval->format('%Y');

        try{
            $userPreference = array(
                'topics' => $var['topics'],
                'interests' => $var['interests'],
                'languages' => $var['languages'],
            );
            $this->UIDtable->getChild('userProfile')->set($userPreference);
            $this->UIDtable->getChild('userDetail')->set(array('bio'=>$var['bio'],'first_name'=>$var['first_name'],'last_name'=>$var['last_name'],'date_of_birth'=> $age_D,'gender' => $var['gender'],));
            $user_age = array(
                'age' => $years,
                'age_date' => $age_D,
                'firebase_uid' => $this->firebase_uid,
            );
            AppUserAge::updateOrCreate($user_age);
            $properties = [
                'displayName' => $var['first_name'].' '.$var['last_name']
            ];
            $this->firebaseAuth->updateUser($this->firebase_uid, $properties);
            $arr = $this->userDetailObject($this->UIDtable->getSnapshot()->getValue());
            return response()->json([
                'status' => 'success',
                'message' => 'User Details',
                'data' => $arr,
            ]);
        }catch(\Exception $e){
            return $e->getMessage();
        }
    }
    /**
     * 
     * User Profile Images Method 
     * @profileImages
     * @deleteImage
     * @userProfileImagesList
     */
    public function profileImages(array $var = null)
    {
        
        try{
            $profileImages = $this->UIDtable->getChild('/profileImages')->getSnapshot()->getValue();
        //    dd($profileImages);
            if(empty($profileImages)){
                $this->UIDtable->getChild('/profileImages')->getChild($var['image_id'])->set($var);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Image Successfully inserted',
                    'data' => $this->UIDtable->getChild('/profileImages')->getChild($var['image_id'])->getSnapshot()->getValue()
                ]);
            }
               else{
              
                if($var['active_profile_image'] == 1){
                    foreach($profileImages as $key => $val){
                        if($val['active_profile_image'] == 1){
                            $profileImages[$key]['active_profile_image'] = 0;
                        }
                    }
                }
                if($var['active_profile_image']==1)
                {
                    $var['active_profile_image']=1;
                }
                else
                {
                    $var['active_profile_image']=0;
                }
                
                $this->UIDtable->getChild('/profileImages')->set($profileImages);
                $this->UIDtable->getChild('/profileImages')->getChild($var['image_id'])->set($var);
                  
              
            //  $this->UIDtable->getChild('/profileImages')->getChild($var['image_id'])->set($var);
                return response()->json([
                    'status' => 'success',
                    'message' => 'successfully updated',
                    'data' => $this->UIDtable->getChild('/profileImages')->getChild($var['image_id'])->getSnapshot()->getValue(),
                ]);
            }
        }catch(\Exception $e){
            // return $e->getMessage();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ],401);
        }

    }
    public function multipleImageUpload(array $var = null)
    {
        try{
            $profileImages = $this->UIDtable->getChild('/profileImages')->getSnapshot()->getValue();
            if(empty($profileImages)){
                $var['active_profile_image'] = 1;
                $this->UIDtable->getChild('/profileImages')->getChild($var['image_id'])->set($var);
                return true;
            }elseif(count($profileImages) < 6){
                $this->UIDtable->getChild('/profileImages')->getChild($var['image_id'])->set($var);
                return true;
            }else{
                return false;
            }
        }catch(\Exception $e){
            // return $e->getMessage();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ],401);
        }

    }
    public function deleteImage(string $var = null)
    {
        try{
            $profileImages = $this->UIDtable->getChild('/profileImages/'.$var)->remove();
            return true;

        }catch(\Exception $e){
            return $e->getMessage();
        }
    }

    public function userProfileImagesList()
    {
        try{
            $profileImages = $this->UIDtable->getChild('/profileImages')->getSnapshot()->getValue();
            return $profileImages;

        }catch(\Exception $e){
            return $e->getMessage();
        }
    }

    public function userLocation(array $var = null)
    {
        try {
            $data = array(
                'created_at' => Carbon::now()->toDateTimeString(),
                'latlong'  => $var['location']
            );
            $this->database->getReference('/location')->getChild($this->firebase_uid)->set($data);
            list($lat,$lon)= \explode(',',$var['location']);
            AppUserLocation::updateOrCreate(['firebase_uid' => $this->firebase_uid],[
                'lat' => $lat,
                'lon' => $lon
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Your location successfully added',
            ]);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ],401);
        }
    }

    public function userPreferenceAgeRange(array $var = null)
    {
        try {
            $ageRange = AppUserAgePreference::updateOrCreate(['firebase_uid'=> $this->firebase_uid],[
                'start_age'  => $var['start_age'],
                'end_age'  => $var['end_age'],
            ]);
            $data = $this->UIDtable->getChild('agePreference')->set(['start_age'  => $var['start_age'],
            'end_age'  => $var['end_age']]);
            
            if($ageRange->wasRecentlyCreated){
                return response()->json([
                    'status' => 'success',
                    'message' => 'Age Range Successfully added',
                    'data' => $this->userAgePreference->getSnapshot()->getValue(),
                ]);
            }else{
                return response()->json([
                    'status' => 'success',
                    'message' => 'User Age Range Successfully updated',
                    'data' => $this->userAgePreference->getSnapshot()->getValue(),
                ]);
            }
        }catch(\Exception $e){
            return $e->getMessage();
        }
    }

    // public function userPreferenceAgeRange(array $var = null)
    // {
    //     try {
    //         $data = array(
    //             'start_age'  => $var['start_age'],
    //             'end_age'  => $var['end_age']
    //         );
    //         $ageRange = AppUserAge::where('firebase_uid','!=',$this->firebase_uid)->whereBetween('age',[$var['start_age'], $var['end_age']])->get();
    //         $this->UIDtable->getChild('preference')->getChild('ageRange')->set($data);
    //         if($ageRange->isNotEmpty()){
    //             $data = $this->postLocationFinder($ageRange);
    //             return response()->json([
    //                 'status' => 'success',
    //                 'message' => 'The list fo user data in this range',
    //                 'data' => $data,
    //             ]);
    //         }else{
    //             return response()->json([
    //                 'status' => 'success',
    //                 'message' => 'No User found in this age range',
    //                 'data' => [],
    //             ]);
    //         }
    //     }catch(\Exception $e){
    //         return $e->getMessage();
    //     }
    // }

    public function verifiedUser($var)
    {
        $uid = $var->firebase_uid;
        $properties = [
            'emailVerified' => true
        ];
        $updatedUser = $this->firebaseAuth->updateUser($uid, $properties);
        return true;
    }
    public function postLocationFinder($var)
    {
        $arr = array();
        foreach($var as $item){
            $user = $this->table->getChild($item->firebase_uid)->getSnapshot()->getValue();
            array_push($arr,$user);
        }
        return $arr;
    }

    public function getUserAgeRange()
    {
        $data['user_age_range'] = $this->UIDtable->getChild('preference')->getChild('ageRange')->getSnapshot()->getValue();
        return response()->json([
            'status' => 'success',
            'message' => 'User Age Range',
            'data' => $data,
        ]);
    }

    public function getUserDetails()
    {
        $userDetails = $this->table->getChild($this->firebase_uid)->getSnapshot()->getValue();
        $arr = [];
        if($userDetails && !empty($userDetails['profileImages'])){
            foreach($userDetails['profileImages'] as $key => $val){
                array_push($arr,$userDetails['profileImages'][$key]);
            }
            $userDetails['profileImages'] = $arr;
        }
        return $userDetails;
    }

    public function refineObject(array $arr = null)
    {
        if($arr && !empty($arr['profileImages'])){
            $arr['profileImages'] = array_values($arr['profileImages']);
        }
        if($arr && !empty($arr['userPost'])){
            $arr['userPost'] = array_values($arr['userPost']);
        }
        if($arr && !empty($arr['userDetail']) && !empty($arr['userProfile'])){
            $arr['userDetail'] += $arr['userProfile'];
            unset($arr['userProfile']);
        }
        return $arr;
    }
    public function userDetailObject(array $arr = null)
    {
        $val = array();
        if($arr && !empty($arr['userDetail']) && !empty($arr['userProfile'])){
            $val = $arr['userDetail'];
            $val += $arr['userProfile'];
        }
        return $val;
    }
    function deleteAccountdata($uid,$reason_id)
    {

        //start
        // $path=public_path('storage/mages/user-post/'.$uid);
        // dd($path);
        $posts=$this->table->getChild($uid)->getChild('userPost')->getSnapshot()->getValue();
        
        try{
            $user=$this->table->getChild($uid);
            
            if($user->remove())
            {
            
                   
                   $result = DB::table('app_users')->where('firebase_uid',$uid)->delete();
                   if($result)
                   {
                       if(!empty($posts))
                       {
                        foreach($posts as $key=>$value)
                        {
                         //    unlink(public_path($key['post_image']));
                         $this->userPost = $this->userPost->getChild($key);
                         $this->userPost->remove();
                         $this->userPostData->getChild($key)->remove();
                         $this->userTagList->getChild($key)->remove();
                         $this->reportPost->getChild($key)->remove();
                           
                        }

                       }
                      
                       $arrdata= array(
                        'reason_id'=>$reason_id,
                        'created_at'=>date('y-m-d h:i:s')
                    );
                    $this->database->getReference('/deleteAccountReasons')->getChild($uid)->set($arrdata);
                    $this->firebaseAuth->deleteUser($uid);
                       
                       $arr['status'] = 'success';
                       $arr['message'] = 'Account successfully Deleted';
                   }
                   else
                   {
                    $arr['status'] = 'success';
                    $arr['message'] = 'Account unable to Delete From Mysql';   
                   }
                
    
                
            }
            else
            {
                $arr['status'] = 'success';
                $arr['message'] = 'unable to Removed User object';
                       
            }

        }catch(\Exception $e)
        {
            $arr['status'] = 'success';
            $arr['message'] = 'Account Already Deleted';   
        }
      return response()->json($arr);

        //end
        
        
    }
}
