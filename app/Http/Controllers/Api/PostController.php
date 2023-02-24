<?php

namespace App\Http\Controllers\Api;
use App\Repositories\Api\firebase\UserProfileRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\AppUser;
use App\Models\Api\firebase\UserPostModel;
use DB;
use App\Repositories\Api\firebase\UserPostRepository;
class PostController extends Controller
{
    //
    protected $model;
    private $userPostRepo;
    public function __construct(UserPostRepository $userPostRepo, UserPostModel $model)
    {
        $this->userPostRepo = $userPostRepo;
        $this->model = $model;
    }
    public function index(Type $var = null)
    {
        return response()->json(['message'=> AppUser::all()], 200);
    }
    public function getsuggestionlist()
    {
           $data = DB::table('report_suggestion as rs')->where('status',1)->get(['rs.statment as statement','rs.id']);
           return response()->json($data);
    }
    public function reportpost(Request $request)
    {
        $request->validate([
            'post_id' => 'required',
            'reason_id'=>'required'
        ]);   
      return $this->model->reportPost($request->all());

    }
}
