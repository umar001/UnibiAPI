<?php

namespace App\Repositories\Api\Firebase;

use JasonGuru\LaravelMakeRepository\Repository\BaseRepository;
use Kreait\Firebase\Database;
use Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
//use Your Model
use App\Models\Api\firebase\UserProfileModel;


/**
 * Class UserProfileRepository.
 */
class UserProfileRepository extends BaseRepository
{
    /**
     * @return string
     *  Return the model
     */
    public function model()
    {
        return UserProfileModel::class;
    }
    /**
     * @return string
     *  Set User profile
     */
    public function createUserProfile($data)
    {
        // Logic Here
        $response =  $this->model->createProfile($data);
        return response()->json([
            'status' => 'success',
            'message' => 'User Data Successfully Submited',
            'data' => $response,
        ]);
    }

    public function update(array $var = null,$id)
    {
        $response = $this->model->updateProfile($var,$id);
        // $response = $this->model->updateDetail($var2,$id);
        
        return response()->json([
            'status' => 'success',
            'message' => 'User Data Successfully Updated',
            'data' => $response,
        ]);
    }

    public function destroy($id)
    {
        $response = $this->model->deleteProfile($id);
        return $response;
    }
    public function userFriendList(Type $var = null)
    {
        $response = $this->model->userFriendList();
        return response()->json([
            'status' => 'success',
            'message' => 'User Data Successfully Updated',
            'data' => $response,
        ]);
    }

    public function multipleImageUpload(array $var)
    {
        $uid = Auth::guard('app-api')->user()->firebase_uid;
        foreach($var['images'] as $key => $item){
            $path = $this->imageUpload($item,'images/user-profile-images/'.$uid);
            $path = Storage::url($path);
            $filename = \pathinfo($path);
            $filename = $filename['filename'];
            
            $imageData = array(
                'image_path' => $path,
                'image_id' => $filename,
                'active_profile_image' => 0,
            );
            $response = $this->model->multipleImageUpload($imageData);
        }
        if($response){
            return $this->userProfileImagesList();
        }else{
            $response = $this->userProfileImagesList();
            $response = json_decode($response->getContent());
            return response()->json([
                'status' => 'success',
                'message' => 'You already have 6 Profile Images',
                'data' => $response->data,
            ]);
        }
        
    }
    
    public function profileImages(array $var = null)
    {
        $uid = Auth::guard('app-api')->user()->firebase_uid;
        $path = $this->imageUpload($var['profile_image'],'images/user-profile-images/'.$uid);
        $path = Storage::url($path);
        $filename = \pathinfo($path);
        $filename = $filename['filename'];
        $imageData = array(
            'image_path' => $path,
            'active_profile_image' => $var['active_profile_image'],
            'image_id' => $filename
        );

        $response = $this->model->profileImages($imageData);
        return $response;
    }
    public function updateProfileImages(array $var = null)
    {
        //  dd($var);
        $uid = Auth::guard('app-api')->user()->firebase_uid;
        $oldFilePath = $var['previous_image_path'];
        $fileinfo = \pathinfo($oldFilePath);
        $oldFileName = $fileinfo['filename'].'.'.$fileinfo['extension'];
        \File::delete(\public_path('storage/images/user-profile-images/'.$uid.'/'.$oldFileName));
        $dltImage = $this->model->deleteImage($fileinfo['filename']);
        if($dltImage){
            $path = $this->imageUpload($var['profile_image'],'images/user-profile-images/'.$uid);
            $path = Storage::url($path);
            $filename = \pathinfo($path);
            $filename = $filename['filename'];
           
            $imageData = array(
                'image_path' => $path,
                'active_profile_image' => $var['active_profile_image'],
                'image_id' => $filename 
            );
            $response = $this->model->profileImages($imageData);
            return $response;
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Your old image path is not correct'
            ],401);
        }

    }
    public function deleteUserImage(array $var = null)
    {
        $uid = Auth::guard('app-api')->user()->firebase_uid;
        $filePath = $var['image_path'];
        $fileinfo = \pathinfo($filePath);
        $fileName = $fileinfo['filename'].'.'.$fileinfo['extension'];
        // dd($uid);
        $filePath = 'storage/images/user-profile-images/'.$uid.'/'.$fileName;
        \File::delete($filePath);
        $dltImage = $this->model->deleteImage($fileinfo['filename']);
        if($dltImage){
            return response()->json([
                'status' => 'success',
                'message' => 'Image Successfully deleted',
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Your old image path is not correct'
            ],401);
        }
    }
    public function userProfileImagesList()
    {
        $response = $this->model->userProfileImagesList();
        $arr['user_images'] =[];
        if($response){
            foreach($response as $key => $val){
                array_push($arr['user_images'],$response[$key]);
            }
        }
        return response()->json([
            'status' => 'success',
            'message' => 'User Images List',
            'data'    => $arr
        ]);
    }
    /**
     * Image Upload Method
     */
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
}

