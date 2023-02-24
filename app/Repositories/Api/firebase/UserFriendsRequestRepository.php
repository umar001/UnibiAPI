<?php

namespace App\Repositories\Api\firebase;

use JasonGuru\LaravelMakeRepository\Repository\BaseRepository;
//use Your Model
use App\Models\Api\firebase\UserFriendsRequestModel;
/**
 * Class UserFriendsRequestRepository.
 */
class UserFriendsRequestRepository extends BaseRepository
{
    /**
     * @return string
     *  Return the model
     */
    public function model()
    {
        return UserFriendsRequestModel::class;
    }

    public function createFriendRequest(array $var = null)
    {
        $returnResponse = $this->model->createFriendRequest($var);
        return response()->json([
            'status' => 'success',
            'message' => 'User Request Successfully generated',
            'data' => $returnResponse,
        ]);
    }

    public function acceptFriendRequest(array $var = null)
    {
        $returnResponse = $this->model->acceptFriendRequest($var);
        return response()->json([
            'status' => 'success',
            'message' => 'User Request Successfully Accepted',
            'data' => $returnResponse,
        ]);
    }
    public function friendRequestList()
    {
        $returnResponse = $this->model->friendRequestList();
        if($returnResponse){
            return response()->json([
                'status' => 'success',
                'message' => 'User Requests',
                'data' => $returnResponse,
            ]);
        }else{
            return response()->json([
                'status' => 'success',
                'message' => 'No data Found',
                'data' => $returnResponse,
            ]);
        }
    }

    public function receiveFriendRequestList()
    {
        $returnResponse = $this->model->receiveFriendRequestList();
        
        if($returnResponse){
            return response()->json([
                'status' => 'success',
                'message' => 'User Requests',
                'data' => $returnResponse,
            ]);
        }else{
            return response()->json([
                'status' => 'success',
                'message' => 'No data Found',
                'data' => $returnResponse,
            ]);
        }
    }
}
