<?php

namespace App\Repositories\Api\firebase;

use JasonGuru\LaravelMakeRepository\Repository\BaseRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Auth;
//use Your Model
use App\Models\Api\firebase\UserPostModel;

/**
 * Class UserPostRepository.
 */
class UserPostRepository extends BaseRepository
{
    /**
     * @return string
     *  Return the model
     */
    public function model()
    {
        return UserPostModel::class;
    }

    public function createUserPost(array $var = null)
    {
        $uid = Auth::guard('app-api')->user()->firebase_uid;
        $post_detail = array(
            'post_content' => $var['post_content'],
            'location' => $var['location'],
            'time' => $var['datetime'],
            'how_many' => $var['how_many']
        );
        // if($var['alone_or_not']){
        //     $post_detail['how_many'] = $var['how_many'];
        // }
        if(!empty($var['post_image'])){
            $path = $this->imageUpload($var['post_image'],'images/user-post/'.$uid);
            $path = Storage::url($path);
            $filename = \pathinfo($path);
            $extension = $filename['extension'];
            $filename = $filename['filename'];
            $post_detail['post_image'] = $path;
        }
        $data = $this->model->createUserPost($post_detail);
        return response()->json([
            'status' => 'success',
            'message' => 'User Post Successfully Created',
            'data' => $data,
        ]);
    }
    ///function edit post
   function editUserPost(array $var = null)
   {
       
    $uid = Auth::guard('app-api')->user()->firebase_uid;
    $post_detail = array(
        'post_content' => $var['post_content'],
        'location' => $var['location'],
        'time' => $var['datetime'],
        'how_many' => $var['how_many']
    );
    
    if(!empty($var['post_image'])){
        $path = $this->imageUpload($var['post_image'],'images/user-post/'.$uid);
        $path = Storage::url($path);
        $filename = \pathinfo($path);
        $extension = $filename['extension'];
        $filename = $filename['filename'];
        $post_detail['post_image'] = $path;
    }
    $data = $this->model->createUserPost($post_detail);
    return response()->json([
        'status' => 'success',
        'message' => 'User Post Successfully Created',
        'data' => $data,
    ]);
}
    //end of edit post
    public function update(array $var = null,$id)
    {
        
        
        
        $var['time'] = Carbon::now()->toDateTimeString();
        if(isset($var['post_image']) && !empty($var['post_image']))
        {
            $path = $this->imageUpload($var['post_image'],'images/user-post-img');
            $path = Storage::url($path);
            $var['post_image'] = $path;
        }
       
        $response = $this->model->updatePost($var,$id);
        return response()->json([
            'status' => 'success',
            'message' => 'User Post Successfully Updated',
            'data' => $response,
        ]);
    }
    public function destroy(string $var = null)
    {
      
        $response = $this->model->deletePost($var);
        return $response;
    }

    public function imageUpload($image,$image_path)
    {
        $post_image = $image;
        $random = Str::random(20);
        $img_extention = $post_image->extension();
        $timestamp = Carbon::now()->timestamp;
        $imag_name = $random.'_'.$timestamp.'.'.$img_extention;
        $path = $post_image->storeAs($image_path, $imag_name, 'public');
        return $path;
    }

    public function userTagOnPost()
    {
        $response = $this->model->userTagOnPost();
        if($response){
            return response()->json([
                'status' => 'success',
                'message' => 'User Post detail',
                'data' => $response,
            ]);
        }else{
            return response()->json([
                'status' => 'success',
                'message' => 'No tag list aginst current login user',
                'data' => $response,
            ]);
        }
    }
    
    
}
