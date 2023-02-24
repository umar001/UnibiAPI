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

    public function __construct(Database $database,FirebaseAuth $firebaseAuth)
    {
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
        $data = $this->userPostData->orderByKey()->getValue();
        
        $data  = array_reverse($data);
        $arr['user_posts'] = [];
        $details = [];
        if($data){
            foreach($data as $key => $val){
                if(!isset($data[$key]['post_image'])){
                    $val['post_image'] = "";
                }
                $userDetails = $this->table->getChild($val['posted_by'])->getSnapshot()->getValue();
                $present = $this->requestList->getChild($key)->getChild($this->firebase_uid)->getSnapshot()->getValue();
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
        return response()->json([
            'status' => 'success',
            'message' => 'All Post Data',
            'data'=>$this->paginate($arr),
        ]);
        
        
    }
    public function paginate($items, $perPage = 2, $page = null, $options = [])
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
        $this->userPost = $this->userPost->getChild($id);
        $this->userPost->set($var);
        $var['postOwnerID'] = $this->firebase_uid;
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
            'posted_by' => $var['posted_by']
        );
        try {
            $response = $this->requestList->getChild($var['post_id'])->getChild($this->firebase_uid)->set($data);
            return response()->json([
                'status' => 'success',
                'message' => 'Your present request Successfully sent',
            ]);
        } catch (\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'No data found',
            ],401);
        }
    }
    public function getPostRequestList(array $var = null)
    {
        try {
            $data = $this->requestList->getChild($var['post_id'])->getSnapshot()->getValue();
            //dd($data);
            $userData = array();
            foreach($data as $key=>$val){
                $arr = $this->table->getChild($key)->getSnapshot()->getValue();
                $userVal = $this->firebaseAuth->getUser($key);
                // dd($userVal);
                $profile_image = array();
                if(isset($arr['profileImages'])){
                    foreach($arr['profileImages'] as $i => $v){
                        if($v['active_profile_image'] == 1){
                            $profile_image = $arr['profileImages'][$i];
                        }
                    }
                }
                $user['user'] = array();
                
                $user['user']['user_profile']['profileImages'] = $profile_image;
                
                $user['user']['user_profile']['userDetail'] = isset($arr['userProfile']) ? $arr['userProfile']:array();
                $userVal->providerData[0]->uid = $key;
                if(!empty($userVal->providerData[0]))
                {
                    foreach($userVal->providerData[0] as $key1=>$value)
                    {
                        $user['user']['user_profile']['userDetail'][$key1] = $value;  
                    }
                }
                
                
  
               $user['user']['user_profile']['uid'] = $key;
          
              
                array_push($userData,$user);
              //  dd($userData);
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
            $this->UIDtable->getChild('friendlist')->getChild($var['request_by_uid'])->set($created_at);
            $this->table->getChild($var['request_by_uid'])->getChild('friendlist')->getChild($this->firebase_uid)->set($created_at);
            $this->requestList->getChild($var['post_id'])->remove();
            $this->userPostData->getChild($var['post_id'])->remove();
            return response()->json([
                'status' => 'success',
                'message' => 'You have successfully accept this request',
            ]);
        } catch (\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'No data found',
            ],401);
        }

    }

    public function show($id)
    {
        
       $return = $this->userPost->getChild($id)->getSnapshot()->getValue();
       if(empty($return))
       {
        return response()->json([]);
       }
       else
       {
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
            'created_at' => Carbon::now(),
        );
        $response = $this->database->getReference('/reported_posts')->getChild($var['post_id'])->getChild($this->firebase_uid)->set($arr);
        return response()->json([
            'status' => 'success',
            'message' => 'Post Reported Successfully',
        ]);
    }
}
