<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\AppUser;
use DB;
use Kreait\Firebase\Database;
use Kreait\Firebase\Auth as FirebaseAuth;
use App\Repositories\Api\firebase\UserPostRepository;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use URL;
// use App\Http\Requests\Api\UserPostRequest;
class PostController extends Controller
{
    // Retrieving all Posts
    protected $model;
    private $database;
    private $firebaseAuth;
    private $reporttable;
    // protected $userPostRepo;
    public function __construct(AppUser $model,FirebaseAuth $firebaseAuth,Database $database)
    {
        $this->firebaseAuth = $firebaseAuth;
        $this->model = $model;
        $this->database = $database;
        $this->table = $this->database->getReference('/userPostData');
        $this->reporttable = $this->database->getReference('/reported_posts');
        $this->usertable = $this->database->getReference('/user');
    }
    public function index(Type $var = null)
    {
        ///dd(URL::current());   
        $myArray= $this->table->getSnapshot()->getValue();
        foreach($myArray as $key=>$value)
        {
            $myArray[$key]['name'] = $this->getusername($value['posted_by']);
        }
      
        $posts = $this->paginate($myArray);

        return view('admin.posts',compact('posts'));
    }
    function getreportedposts()
    {
        $myArray= $this->reporttable->getSnapshot()->getValue();
        //$posts = array();
        if(!empty($myArray))
        {
            foreach($myArray as $key=>$value)
        {
            $post = array();
            // $this->rlist($key);
           $post =  $this->table->getChild($key)->getSnapshot()->getValue();
           $post['name'] = $this->getusername($key);
           $posts[]= $post;
        }
  
        $posts = $this->paginate($posts);
        }
        else
        {
           $posts = array(); 
        }
        
        return view('admin.report_posts',compact('posts'));
    }
    function rlist(Request $request)
    {
        extract($request->all());
      $rpost =  $this->reporttable->getChild($post_id)->getSnapshot()->getValue();    
$ar = array();
$html = '<div class="row">';

      foreach($rpost as $r=>$value)
      {
        $html.='<div class="col-md-12 p-3 mb-3" style="background: aquamarine;">';
        $html.='<span><b>'.$this->getusername($r).'</b></span>';
        $html.=':  <span>'.$this->getreasonbyid($value['reason_id']).'</span>';
        $html.='</div>';
      }
      $html.='</div>';
      $data['html'] = $html;
      $data['status'] = 200;
      return response()->json($data);

    }
    function getreasonbyid($id)
    {
      return  DB::table('report_suggestion')->where('id',$id)->get()->first()->statment;
    
    }
    function getusername($uid)
    {
        $rpost =  $this->usertable->getChild($uid)->getChild('userDetail')->getSnapshot()->getValue();
        if(!empty($rpost))
        {
            return $rpost['first_name'].' '.$rpost['last_name'];
        }
        else
        {
            return 'No Name';
        }
        
    }
    public function paginate($items, $perPage = 50, $page = null, $options = [])
    {  // $request = new Request;
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page,['path' => URL::current()]);
    }
   public function deletepost(Request $request)
   {
       extract($request->all());
       $post=$this->table->getChild($id);
       $data = array();
       if($post->remove())
       {
          $data['status'] = 200;
          $data['message'] = 'Successfully Deleted';
       }
       else
       {
        $data['status'] = 401;
        $data['message'] = 'Successfully Deleted';
       }
    return response()->json($data);
   }
}
