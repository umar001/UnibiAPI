<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;
use App\Models\Api\AppUser;
use Kreait\Firebase\Database;
class UserController extends Controller
{
    private $database;
    public $data;
    public function __construct(AppUser $model,Database $database,FirebaseAuth $firebaseAuth)
    {
      
        $this->firebaseAuth = $firebaseAuth;
           // Firebase database
           $this->database = $database;
           
            $this->table = $this->database->getReference('/user');
           
            $this->model = $model;
    }
    public function index()
    {
        
        $fb_ids= $this->model->pluck('firebase_uid')->toArray();
       
        $users= $this->table->getSnapshot()->getValue();
        
         return view('admin.users',compact('users'));
    }
    function deleteuser(Request $request)
    {
        $exist = $this->table->getChild($request->id)->getValue();
        
        if($exist)
        {
            $this->table->getChild($request->id)->remove();
            $user=AppUser::where('firebase_uid',$request->id)->get();
            if($user)
            AppUser::where('firebase_uid',$request->id)->delete();

        }
        $data['status'] = 200;
        return response()->json($data);
       
      
    }
    public function destroy(Request $request)
    {
        $exist = $this->table->getChild($request->id)->getValue();
        
        if($exist)
        {
            $this->table->getChild($request->id)->remove();
            $user=AppUser::where('firebase_uid',$request->id)->get();
            if($user)
            $user->delete();
        }
       
        return redirect()->route('users.index');
    }
    public function edit($id)
    {
        $this->table = $this->table->getChild($id);
        $users=$this->table->getSnapshot()->getValue();
        
        $this->data['id']=$id;
        $this->data['date_of_birth']= $users['userDetail']['date_of_birth'] ?? '';
        $this->data['first_name']= $users['userDetail']['first_name'] ?? '';
        $this->data['date_of_birth']=$users['userDetail']['date_of_birth'] ?? '';
        $this->data['last_name']= $users['userDetail']['last_name'] ?? '';
        $this->data['gender']= $users['userDetail']['gender'] ?? '';
        $this->data['languages']= $users['userProfile']['languages'] ?? '';
        $this->data['topics']= $users['userProfile']['topics'] ?? '';
        $this->data['interests']= $users['userProfile']['interests'] ?? '';
        // dd($this->data);
        return view('admin.users_edit',$this->data);
    }
    public function show($id)
    {
        $this->table = $this->table->getChild($id);
        $user=$this->table->getSnapshot()->getValue();
       
        return view('admin.user_profile',compact('user'));
        
    } 
   
    public function update(Request $request)
    {
        $var=$request->all();
        $this->table = $this->table->getChild($var['id']);
        $userPreference = array(
            'topics' => $var['topics'],
            'interests' => $var['interests'],
            'languages' => $var['language'],
        );
         $this->table->getChild('userProfile')->set($userPreference);
        
         $this->table->getChild('userDetail')->set(array('first_name'=>$var['first_name'],'last_name'=>$var['last_name'],'date_of_birth'=>$var['date_of_birth'],'gender' => $var['gender'], 'date_of_birth'=> $var['date_of_birth']));
         return redirect()->route('users.index');
    }
}
