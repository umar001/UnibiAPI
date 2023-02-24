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
class DeactiveAccountController extends Controller
{
    protected $model;
    private $database;
    private $firebaseAuth;
    // protected $userPostRepo;
    public function __construct(AppUser $model,FirebaseAuth $firebaseAuth,Database $database)
    {
        $this->firebaseAuth = $firebaseAuth;
        $this->model = $model;
        $this->database = $database;
        $this->table = $this->database->getReference('/userPostData');
      
    }
    function index()
    {
        $statments = DB::table('deactive_account_suggestion')->get();
        return view('admin.deactive_account_suggestion',compact('statments'));
    }
    function save(Request $request)
    {
        extract($request->all());
        if(!empty($statment_id))
        {
            $result = DB::table('deactive_account_suggestion')->where('id',$statment_id)->update(['statment'=>$statment]);
        }
        else
        {
            $result = DB::table('deactive_account_suggestion')->insert(['statment'=>$statment]);
        }
      
        if($result)
        {
           $arr['status'] = 200;
           $arr['message'] = 'successfully Saved Changes';
        }
        else
        {
            $arr['status'] = 401;
            $arr['message'] = 'Failed to Save Changes';
        }
        return response()->json($arr);
    }
    function changestatue($id,$status)
    {
        if( $result = DB::table('deactive_account_suggestion')->where('id',$id)->update(['status'=>$status]))
        {
          return back();
        }

    }
    function feedback()
    {
         $data= $this->database->getReference('/deleteAccountReasons')->getSnapshot()->getValue();
         $reasondata = array();
         foreach($data as $d)
         {
            array_push($reasondata,$this->specificereason($d['reason_id'])); 
         }
         
         return view('admin.deactive_feedback',compact('reasondata'));
    }
    function specificereason($id)
    {
        return DB::table('deactive_account_suggestion')->where('id',$id)->get()->first()->statment;
        
    }
}
