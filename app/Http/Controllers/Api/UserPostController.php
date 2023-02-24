<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\UserPostRequest;
// Model
use App\Models\Api\firebase\UserPostModel;
// Repository
use App\Repositories\Api\firebase\UserPostRepository;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\Database\ReferenceHasNotBeenSnapshotted;
use Kreait\Firebase\Database;
use DB;
class UserPostController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $database;
    protected $model;
    private $userPostRepo;
    protected $table;
    public function __construct(UserPostRepository $userPostRepo, UserPostModel $model,Database $database)
    {
        $this->database = $database;
        $this->table = $this->database->getReference('/Abuses_word');
        $this->userPostRepo = $userPostRepo;
        $this->model = $model;
    }
    public function index()
    {
        $data = $this->model->getAllPost();
        return $data;
    }
    /**
     * Create a App User Post.
     *
     * @return void
     */
    public function store(UserPostRequest $request)
    {
        extract($request->all());
        // dd($request->all());
        $words=$this->table->getSnapshot()->getValue();
        $wordarray = explode(',',$words['word']);
        foreach($wordarray as $word)
        {
            if(strpos($post_content,$word) !== false){
                $data['message'] = 'The given data was invalid.';
                $dk = array(
                    'post_content'=>'Please Removed a Abuses Word'
                );
                $data['errors'] = $dk;
                return response()->json($data);
            }
        }
        
        $returnResponse = $this->userPostRepo->createUserPost($request->all());
        return $returnResponse;
    }
    public function update(UserPostRequest $request,$id)
    {
        
        $returnResponse = $this->userPostRepo->update($request->all(),$id);
        return $returnResponse;
    }
    public function updatePostdata(UserPostRequest $request)
    {
        extract($request->all());
        $id = $post_id;
        // $words=$this->table->getSnapshot()->getValue();
        // $wordarray = explode(',',$words['word']);
        // foreach($wordarray as $word)
        // {
        //     if(strpos($post_content,$word) !== false){
        //         $data['message'] = 'The given data was invalid.';
        //         $dk = array(
        //             'post_content'=>'Please Removed a Abuses Word'
        //         );
        //         $data['errors'] = $dk;
        //         return response()->json($data);
        //     }
        // }
        $returnResponse = $this->userPostRepo->update($request->all(),$id);
        return $returnResponse;
    }
    public function destroy($id)
    {
       
        $returnResponse = $this->userPostRepo->destroy($id);
        return $returnResponse;
    }
    public function userTagOnPost()
    {
        $returnResponse = $this->userPostRepo->userTagOnPost();
        return $returnResponse;
    }

    public function sendPresentRequest(Request $request)
    {
        $request->validate([
            'post_id' => 'required',
            'posted_by' => 'required',
        ]);
        $returnResponse = $this->model->sendPresentRequest($request->all());
        return $returnResponse;
    }

    public function getPostRequestList(Request $request)
    {
        $request->validate([
            'post_id' => 'required',
        ]);
        $returnResponse = $this->model->getPostRequestList($request->all());
        return $returnResponse;
    }

    public function acceptPresentRequest(Request $request)
    {
        $request->validate([
            'post_id' => 'required',
            'request_by_uid' => 'required',
        ]);
        $returnResponse = $this->model->acceptPresentRequest($request->all());
        return $returnResponse;
    }
    public function declinedPresentRequest(Request $request)
    {
        
        $request->validate([
            'post_id' => 'required',
            'request_by_uid' => 'required',
        ]);
        
        $returnResponse = $this->model->declinedPresentRequest($request->all());
        return $returnResponse;
    }
    public function cancelPresentRequest(Request $request)
    {
        
        $request->validate([
            'post_id' => 'required',
            
        ]);
        
        $returnResponse = $this->model->cancelPresentRequest($request->all());
        return $returnResponse;
    }
    
    public function show($id)
    {
       
        $returnResponse = $this->model->show($id);
        return $returnResponse;
    }
}
