<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\UserFriendsRequest;
// Model
use App\Models\Api\firebase\UserFriendsRequestModel;
// Repository
use App\Repositories\Api\firebase\UserFriendsRequestRepository;
// use Auth;

class UserFriendsRequestController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $model;
    private $friendRequest;
    public function __construct(UserFriendsRequestRepository $friendRequest, UserFriendsRequestModel $model)
    {
        $this->middleware('auth:app-api');
        $this->friendRequest = $friendRequest;
        $this->model = $model;
    }
    /**
     * Create a App User Post.
     *
     * @return void
     */
    public function store(UserFriendsRequest $request)
    {   
        // dd($request);
        $returnResponse = $this->friendRequest->createFriendRequest($request->all());
        return $returnResponse;
    }

    public function acceptFriendRequest(Request $request)
    {
        $returnResponse = $this->friendRequest->acceptFriendRequest($request->all());
        return $returnResponse;
    }
    /**
     * 
     * Friend Request List Data
     */

    public function friendRequestList()
    {
        // dd(Auth::guard('app-api')->user());
        $returnResponse = $this->friendRequest->friendRequestList();
        return $returnResponse;
    }

    public function receiveFriendRequestList()
    {
        $returnResponse = $this->friendRequest->receiveFriendRequestList();
        return $returnResponse;
    }
}
