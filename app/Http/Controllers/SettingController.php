<?php

namespace App\Http\Controllers;
// Dependency
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;
use Auth;
// Model
use App\Models\User;

class SettingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     */
    protected $role;
    protected $permisions;
    public function __construct()
    {
        $this->middleware('auth');
        $this->roles = Role::all();
        $this->permisions = Permission::all();
    }
    /**
     * Contyroller Index function
     *
     */
    public function index(Type $var = null)
    {
        // dd(Auth::id());
        $users = User::with('roles')->where('id','!=',Auth::id())->get();
        // dd($users);
        $roles = Role::all();
        $permisions = Permission::all();
        return view('admin.setting.index',compact('users', 'roles', 'permisions'));
    }
    /**
     * get User data  by id
     *
     */
    public function getUserData(Request $request)
    {
        $validatedData = $request->validate([
            'user_id'       => 'required',
        ]);
        $user_id = $validatedData['user_id'];
        // $user = User::with('roles')->with('permissions')->where('id',$user_id)->get();
        $user = User::with('roles')->with('permissions')->find($user_id);
        $userPermissionIds = array();
        if(!empty($user->permissions)){
            foreach($user->permissions as $arr){
                array_push($userPermissionIds,$arr->id);
            }
        }
        return response()->json([
            'html' => view('admin.setting.model.userRoles', [ 'user' => $user,'roles' => $this->roles, 'permission' => $this->permisions, 'userPermissionIds' => $userPermissionIds ])->render()
            ,200, ['Content-Type' => 'application/json']
        ]);
    }

    public function updateUserRole(Request $request)
    {
        // dd($request->input());
        $user_id = $request->input('user_id');
        $user = User::find($user_id);
        $role_name = $request->input('role');
        $role = $user->syncRoles($role_name);

        if($role_name == 'User'){
            $permisions = $request->input('permissionId');
            $user->syncPermissions($permisions);
        }else{
            $permisions = $request->input('permissionId');
            $user->revokePermissionTo($permisions);
        }
        // $user = User::with('roles')->with('permissions')->find($user_id);
        return true;
    }
    public function userDelete(Request $request)
    {
        $user_id = $request->input('user_id');
        $user = User::find($user_id);
        if($user){
            // $user->role()->detach();
            $user->delete();
        }
        return true;
        # code...
    }
    //
}
