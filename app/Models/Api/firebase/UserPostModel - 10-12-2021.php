<?php

namespace App\Models\Api\firebase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kreait\Firebase\Database;
use Carbon\Carbon;
use Auth;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\Database\ReferenceHasNotBeenSnapshotted;
use Throwable;
use App\Models\Api\firebase\AppPostLocation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use URL;
use DB;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
class UserPostModel extends Model
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
    protected $userPost;
    protected $tagList;
    protected $userPostData;
    protected $reportPost;
    protected $messaging;

    public function __construct(Database $database,FirebaseAuth $firebaseAuth,Messaging $messaging)
    {
        $this->messaging = $messaging;
        // Firebase Realtime Database
        $this->database = $database;
        // Firebase Auth 
        $this->firebaseAuth = $firebaseAuth;
        // Realtime Database User Collection
        $this->table = $this->database->getReference('/user');
        // Passport APi Auth 
        $this->user = Auth::guard('app-api')->user();
        if($this->user){
            // Get User ID from Passport APi Auth
            $this->firebase_uid = $this->user->firebase_uid;
            // Get LogIn User data from User Table 
            $this->UIDtable = $this->table->getChild($this->firebase_uid);
            // Post Collection in User Table
            $this->userPost = $this->UIDtable->getChild('/userPost');
            // FRD TagFriend collection where User Tagged In in post by friend 
            $this->tagList = $this->database->getReference('/postTagFriends');
            $this->userTagList = $this->tagList->getChild($this->firebase_uid);
            // FRD PostDetail Collection 
            $this->userPostData = $this->database->getReference('/userPostData');
            // FRD Location Collection 
            $this->location = $this->database->getReference('/location');
            // FRD Request List Collection 
            $this->requestList = $this->database->getReference('/presentRequestList');
            $this->reportPost = $this->database->getReference('/reported_posts');
        }
    }
    public function getAllPost()
    {
        $data = $this->userPostData->getValue();
        $datauser=$this->table->getSnapshot()->getValue();
        $requestcheck=$this->requestList->getSnapshot()->getValue();
       // dd($requestcheck);
        $data  = array_reverse($data);
        $data = $this->paginate($data);
       // dd($data->nextPageUrl());
        $arr['user_posts'] = [];
        $details = [];
        // dd($data);
        if($data){
            foreach($data as $key => $val){
                 
                if(!isset($data[$key]['post_image'])){
                    $val['post_image'] = "";
                }
                if(!empty($val['posted_by']) && isset($val['posted_by']))
                {
                    if(isset($datauser[$val['posted_by']]))
                    {
                        $userDetails = $datauser[$val['posted_by']]; 
                    }
                    else
                    {
                        $userDetails = array();
                    }
                    //$this->table->getChild($val['posted_by'])->getSnapshot()->getValue();
                }
                else
                {
                    $userDetails = array();
                }
               $uid = $this->firebase_uid;
                 if(isset($requestcheck[$key][$uid]))
                            {
                                $present =$requestcheck[$key][$uid];
                             }
                            else
                            {
                                 $present = array();
                            }
                ///$this->requestList->getChild($key)->getChild($this->firebase_uid)->getSnapshot()->getValue();
                if(!empty($present)){
                    $val['subscribed'] = true;
                }
                else
                {
                    $val['subscribed'] = false; 
                }
                if(!empty($userDetails)){
                    if(!empty($userDetails['userDetail'])){
                        $details['userDetail'] = $userDetails['userDetail'];
                    }else{
                        $details['userDetail']=  array(
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
                    if(!empty($userDetails['userProfile'])){
                        $details['userDetail'] +=$userDetails['userProfile'];
                    }
                    if(!empty($userDetails['profileImages'])){
                        $details['profileImages'] = array_values($userDetails['profileImages']);
                    }else{
                        $details['profileImages']= [];
                    }
                    
                }
                $val['user_profile'] = $details;
                $data[$key] = $val;
                
                array_push($arr['user_posts'],$data[$key]);
            }
        }
        $arr['next_page'] = $data->nextPageUrl();
                $arr['previous_page'] = $data->previousPageUrl();
        return response()->json([
            'status' => 'success',
            'message' => 'All Post Data',
            'data'=>$arr,
        ]);
        
        
    }
    public function paginate($items, $perPage = 10, $page = null, $options = [])
    {  // $request = new Request;
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page,['path' => URL::current()]);
    }
    /**
     * Create a new Post.
     *
     */
    public function createUserPost(array $arr = null)
    {       
        
        if(!isset($arr['post_image']) && empty($arr['post_image'])){
            $profile_image = $this->UIDtable->getChild('profileImages')->getSnapshot()->getValue();
            if(!empty($profile_image)){
                foreach($profile_image as $item){
                    if($item['active_profile_image'] == 1){
                        $arr['post_image'] = $item['image_path'];
                    }
                }
            }else{
                $arr['post_image'] = "";
            }
        }
        $postData = $this->userPost->push($arr);
        list($lat,$lon)= \explode(',',$arr['location']);
        AppPostLocation::updateOrCreate(['firebase_uid' => $this->firebase_uid],[
            'lat' => $lat,
            'lon' => $lon
        ]);
        $key = $postData->getKey();
        $arr['post_id'] = $key;
        $this->userPost->getChild($key)->set($arr);
        $arr['posted_by'] = $this->user->firebase_uid; 
        $this->userPostData->getChild($key)->set($arr);
        return $this->userPost->getChild($key)->getSnapshot()->getValue();
    }

    public function updatePost(array $var = null,$id)
    {
        
        if(!isset($var['post_image']) && empty($var['post_image']))
        {
            $oldpost=$this->userPostData->getChild($id)->getSnapshot()->getValue();
            $var['post_image'] = $oldpost['post_image'];
        }
      if(isset($var['datetime']))
      {
        unset($var['datetime']);
      }
        
        $this->userPost = $this->userPost->getChild($id);
        $this->userPost->set($var);
        $var['posted_by'] = $this->firebase_uid;
        $this->userPostData->getChild($id)->set($var);
        return $this->userPost->getValue();
    }

    public function deletePost(string $id = null)
    {
       
        try{
            $oldpost=$this->userPostData->getChild($id)->getSnapshot()->getValue();
            if(!empty($oldpost))
            {
                $this->userPost = $this->userPost->getChild($id);
            $this->userPost->remove();
            $this->userPostData->getChild($id)->remove();
            $this->userTagList->getChild($id)->remove();
            $this->reportPost->getChild($id)->remove();
            return response()->json([
                'status' => 'success',
                'message' => 'User Post Successfully Deleted',
            ]);

            }
            else
            {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Posted Already Deleted',
                ]);
            }
            
        } catch (\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'No id Found',
            ],401);
        }
    }

    public function userTagOnPost()
    {
        try {
            $userTagOnPostIds = $this->userTagList->getChildKeys();
            $tagPostDetail = array();
            foreach($userTagOnPostIds as $val){
                $postDetail = $this->userPostData->getChild($val)->getSnapshot()->getValue();
                unset($postDetail['tag_friends']);
                $data = array(
                    'postDetail' => $postDetail,
                );
                array_push($tagPostDetail,$data);
            }
            return $tagPostDetail;
        } catch (\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'No data found',
            ],401);
        }
    }
    public function sendPresentRequest(array $var = null)
    {
        $data = array(
            'posted_by' => $var['posted_by'],
            'status'=>'pending'
        );
        try {
            $response = $this->requestList->getChild($var['post_id'])->getChild($this->firebase_uid)->set($data);
           $result = $this->SendRequestNotification($var);
           $udata1= $this->UIDtable->getChild('/userDetail')->getSnapshot()->getValue();
           $profileImages = $this->UIDtable->getChild('/profileImages')->getSnapshot()->getValue();
           if(!empty($profileImages))
           {
            foreach($profileImages as $key => $val){
                if($val['active_profile_image'] == 1){
                 $path = $val['image_path'];
                          }
                     }
           }
           else
           {
               $path = '';
           }
           
        
           if(!empty($udata1))
           {
               $data = array(
                   'first_name'=>$udata1['first_name'],
                   'last_name'=>$udata1['last_name'],
                   'profile_image'=>$path,
                   'post_id'=>$var['post_id'],
                   'uid'=>$var['posted_by']
               );
           }
           else
           {
               $data = array(
                   'first_name'=>'',
                   'last_name'=>'',
                   'profile_image'=>$path,
                   'post_id'=>$var['post_id'],
                   'uid'=>$var['posted_by']
               ); 
           }
           
            return response()->json([
                'status' => 'success',
                'message' => 'Your present request Successfully sent',
                'data'=>$data
            ]);
        } catch (\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'No data found',
            ],401);
        }
    }
///Notification of Send Request
public function SendRequestNotification($var)
    {
       
        $requestuser =  DB::table('app_users')->where('firebase_uid',$var['posted_by'])->get()->first();
        $owneruser =  Auth::guard('app-api')->user();
        
        $tokens = $requestuser->device_token;
       
        $result = $this->messaging->validateRegistrationTokens($tokens);
        $title = 'Send Request';
        $body = $owneruser->name.'has Successfully Send Request';
        
        if(isset($result['valid']) && !empty($result['valid']) )
        {
            try {
                $data['title'] = $title;
                $data['body'] = $body;
                $message = CloudMessage::withTarget('token',$tokens)->withNotification($data);
                $resp = $this->messaging->send($message); 
                return true;          
            } catch (InvalidMessage $e) {
                
                return $e->errors();
                
            }
            
        }
        else
        {
           // return false;
            return 'Invalid FCM Registration Device Token. Please use a valid Token.';

        }
    }

//end of Send Notification
    public function getPostRequestList(array $var = null)
    {

        try {
            $data = $this->requestList->getChild($var['post_id'])->getSnapshot()->getValue();
             
            $userData = array();
            foreach($data as $key=>$val){
                
                $arr = $this->table->getChild($key)->getSnapshot()->getValue();
               
                $userVal = $this->firebaseAuth->getUser($key);
                
                $profile_image = array();
                // if(isset($arr['profileImages'])){
                //     foreach($arr['profileImages'] as $i => $v){
                //         if($v['active_profile_image'] == 1){
                //             $profile_image = $arr['profileImages'][$i];
                //         }
                //     }
                // }
                
                if(isset($arr['profileImages'])){
                   
                }
               
                $user['user'] = array();
                
               
                if(isset($arr['profileImages'])){
                    $user['user']['user_profile']['profileImages'] = array_values($arr['profileImages']);
                  //  $user['user']['user_profile']['profileImages'] =  $arr['profileImages'];
                }
                else
                {
                    $user['user']['user_profile']['profileImages'] = array();
                }
                $user['user']['user_profile']['userDetail'] = isset($arr['userDetail']) ? $arr['userDetail']:array();
                $user['user']['user_profile']['userDetail'] = array_merge($user['user']['user_profile']['userDetail'],isset($arr['userProfile']) ? $arr['userProfile']:array());
               // dd( $user['user']['user_profile']['userDetail']);
                $userVal->providerData[0]->uid = $key;
                // if(!empty($userVal->providerData[0]))
                // {
                //     foreach($userVal->providerData[0] as $key1=>$value)
                //     {
                //         $user['user']['user_profile']['userDetail'][$key1] = $value;  
                //     }
                // }
                
                
  
               $user['user']['user_profile']['uid'] = $key;
               $user['user']['user_profile']['status'] = $val['status'];
          
              
                array_push($userData,$user);
             
            }
            return $userData;
        } catch (\Exception $e){
            return response()->json([]);
        }
    }
    public function acceptPresentRequest(array $var = null)
    {
       
        try {
            $created_at = array(
                'created_at' => Carbon::now()->toDateTimeString(),
               
            );
            $udata = array(
                'created_at' => Carbon::now()->toDateTimeString(),
                'status'=>'accepted' 
            );
            $this->UIDtable->getChild('friendlist')->getChild($var['request_by_uid'])->set($created_at);
            $this->table->getChild($var['request_by_uid'])->getChild('friendlist')->getChild($this->firebase_uid)->set($created_at);
            $this->requestList->getChild($var['post_id'])->getChild($var['request_by_uid'])->set($udata);
            //$this->requestList->getChild($var['post_id'])->remove();
            ///$this->userPostData->getChild($var['post_id'])->remove();
           $result = $this->AcceptNotification($var);
            $udata1= $this->UIDtable->getChild('/userDetail')->getSnapshot()->getValue();
           
            if(!empty($udata1))
            {
                $data = array(
                    'first_name'=>$udata1['first_name'],
                    'last_name'=>$udata1['last_name'],
                    'post_id'=>$var['post_id']
                );
            }
            else
            {
                $data = array(
                    'first_name'=>'',
                    'last_name'=>'',
                    'post_id'=>$var['post_id']
                ); 
            }
            
           if( $result== true )
           {
            return response()->json([
                'status' => 'success',
                'message' => 'You have successfully accept this request',
                'data'=>$data
            ]);
           }
           else
           {
            return response()->json([
                'status' => 'success',
                'message' => $result,
                'data'=>$data
            ]);
           }
            
        } catch (\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'No data found',
            ],401);
        }

    }
    ///send Accept Notification
    public function AcceptNotification($var)
    {
       
        $requestuser =  DB::table('app_users')->where('firebase_uid',$var['request_by_uid'])->get()->first();
        $owneruser =  Auth::guard('app-api')->user();
        
        $tokens = $requestuser->device_token;
       
        $result = $this->messaging->validateRegistrationTokens($tokens);
        $title = 'Accept Request';
        $body = $owneruser->name.'has Send You the Position';
        
        if(isset($result['valid']) && !empty($result['valid']) )
        {
            try {
                $data['title'] = $title;
                $data['body'] = $body;
                $message = CloudMessage::withTarget('token',$tokens)->withNotification($data);
                $resp = $this->messaging->send($message); 
                return true;          
            } catch (InvalidMessage $e) {
                
                return $e->errors();
                
            }
            
        }
        else
        {
           // return false;
            return 'Invalid FCM Registration Device Token. Please use a valid Token.';

        }
    }

    //end of send Accept Notification


    ///declind present request
    public function declinedPresentRequest(array $var = null)
    {
        
        $udata = array(
            'created_at' => Carbon::now()->toDateTimeString(),
            'status'=>'declined'
        );
        try {
            $this->requestList->getChild($var['post_id'])->getChild($var['request_by_uid'])->set($udata);
            //$this->requestList->getChild($var['post_id'])->remove();
           $result = $this->declindNotification($var);
            $udata1= $this->UIDtable->getChild('/userDetail')->getSnapshot()->getValue();
           
            if(!empty($udata1))
            {
                $data = array(
                    'first_name'=>$udata1['first_name'],
                    'last_name'=>$udata1['last_name'],
                    'post_id'=>$var['post_id']
                );
            }
            else
            {
                $data = array(
                    'first_name'=>'',
                    'last_name'=>'',
                    'post_id'=>$var['post_id']
                ); 
            }
            
           if( $result== true )
           {
            return response()->json([
                'status' => 'success',
                'message' => 'You have successfully Declined this request',
                'data'=>$data
            ]);
           }
           else
           {
            return response()->json([
                'status' => 'success',
                'message' => $result,
                'data'=>$data
            ]);
           }
            
        } catch (\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'No data found',
            ],401);
        }

    }

    //end of decalind present request
///send declind Notification
public function declindNotification($var)
{
    
    $requestuser =  DB::table('app_users')->where('firebase_uid',$var['request_by_uid'])->get()->first();
    $owneruser =  Auth::guard('app-api')->user();
    
    $tokens = $requestuser->device_token;
   
    $result = $this->messaging->validateRegistrationTokens($tokens);
    $title = 'Declined Request';
    $body = $owneruser->name.'has Send Declined your Request';
    if(isset($result['valid']) && !empty($result['valid']) )
    {
        try {
            $data['title'] = $title;
            $data['body'] = $body;
            $message = CloudMessage::withTarget('token',$tokens)->withNotification($data);
            $resp = $this->messaging->send($message); 
            return true;          
        } catch (InvalidMessage $e) {
            
            return $e->errors();
            
        }
        
    }
    else
    {
        
        return 'Invalid FCM Registration Device Token. Please use a valid Token.';
       
    }
}

//end of send Declind Notification
///cancel present request
public function cancelPresentRequest(array $var = null)
{
   
    try {
        
       $request =  $this->requestList->getChild($var['post_id']);
        $request->getChild($this->firebase_uid)->remove(); 
       return response()->json([
            'status' => 'success',
            'message' => 'You have successfully Cancel this request',
        ]);
    } catch (\Exception $e){
        return response()->json([
            'status' => 'error',
            'message' => 'No data found',
        ],401);
    }

}

//end of cancel present request



    public function show($id)
    {
       
       $return = $this->userPostData->getChild($id)->getSnapshot()->getValue();
       
       if(empty($return))
       {
        return response()->json([]);
       }
       else
       {
           $return['status'] = 'pending';
        
        return response()->json([
            'status' => 'success',
            'message' => 'User Post Details',
            'data' => $return
        ]);
       }
       
    }
    function reportPost(array $var=null)
    {
      
        $data['reported_by'] = $this->firebase_uid;
        $arr = array(
            'reason_id'=>$var['reason_id'],
            'created_at' => Carbon::now()
        );
        $response = $this->database->getReference('/reported_posts')->getChild($var['post_id'])->getChild($this->firebase_uid)->set($arr);
        return response()->json([
            'status' => 'success',
            'message' => 'Post Reported Successfully',
        ]);
    }
}
