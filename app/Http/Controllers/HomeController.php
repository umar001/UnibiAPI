<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;
use App\Models\User;
use Auth;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Support\Facades\Gate;
use DB;
use Kreait\Firebase\Database;

use App\Repositories\UserRepository;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    private $userRepo;
    private $database;
    private $table;
    public function __construct(UserRepository $userRepo,Database $database)
    {
        $this->middleware('auth');
        $this->userRepo = $userRepo;
        $this->database = $database;
        $this->table = $this->database->getReference('/userPostData');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $posts= $this->table->getSnapshot()->getValue();
        $totalpost = count($posts);
        
        return view('admin.dashboard.index',compact('totalpost'));
    }

    public function role(Type $var = null)
    {
        $user = Auth::user();
        $user = User::find(2);
        // dd($user);
        // $test = $user->assignRole('Admin');
        $test = $user->getAllPermissions();
        dd($test);
        # code...
    }
    public function firebase(Type $var = null)
    {
        $firebase = Firebase::auth();
        dd($firebase);
    }

    public function setting(Type $var = null)
    {
        return view('admin.setting.index');
        # code...
    }

    public function notification()
    {

            //API URL of FCM
            $url = 'https://fcm.googleapis.com/fcm/send';
            $device_id = '863503045054193';
            $message = 'Hello';
            /*api_key available in:
            Firebase Console -> Project Settings -> CLOUD MESSAGING -> Server key*/    
            $api_key = 'AAAAm1vzUY4:APA91bFT7h3roZ8Pfei9aYieBi_JLcCWNGC34sex4QjLnzuV88HC5foZDlFzELvvlntG5-fdDj4KKwPI_biOGTaU_0m9N24Z8JAAY6Mi7rSUeK22FseEBAINchgXyr17AfQ3jYDWi3d6';
                        
            $fields = array (
                'registration_ids' => array (
                        $device_id
                ),
                'data' => array (
                        "message" => $message
                )
            );
        
            //header includes Content type and api key
            $headers = array(
                'Content-Type:application/json',
                'Authorization:key='.$api_key
            );
                        
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);
            if ($result === FALSE) {
                die('FCM Send Error: ' . curl_error($ch));
            }
            curl_close($ch);
            return $result;
    }
}
