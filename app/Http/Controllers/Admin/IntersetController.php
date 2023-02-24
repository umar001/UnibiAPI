<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kreait\Firebase\Database;
use Auth;
use Carbon\Carbon;
use DB;

class IntersetController extends Controller
{
    private $database;
    protected $Intersettable;
    protected $Languagetable;
    protected $Topicstable;
    protected $user;
    protected $Abusetable;

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->Abusetable =  $this->database->getReference('/Abuses_word');
        $this->Intersettable = $this->database->getReference('/interests');
        $this->Languagetable = $this->database->getReference('/language');
        $this->Topicstable = $this->database->getReference('/topics');
        $this->user = Auth::guard('app-api')->user();
    }
    function index()
    {
        $intersets = $this->Intersettable->getSnapshot()->getValue();
        $language = $this->Languagetable->getSnapshot()->getValue();
        $topics = $this->Topicstable->getSnapshot()->getValue();
       
        return view('admin.interset',compact('intersets','language','topics'));
    }
    function create(Request $request) 
    {

        extract($request->all());
        $arr = array(
            'name' => $name,
            'created_at' => Carbon::now()->toDateTimeString(),
        );
        if($table_name=='interests')
        {
         $this->Intersettable->getChild($name)->set($arr);
        }
        elseif($table_name=='language')
        {
            $this->Languagetable->getChild($name)->set($arr);
        }
        elseif($table_name=='topics')
        {
            $this->Topicstable->getChild($name)->set($arr);
        }
        
        
        return response()->json([
            'status' => '200',
            'message' => 'Successfully Saved Your Options',
            
        ]);
    }
    function delete($table_name,$key)
    {
        
       
        if($table_name=='interests')
        {
          $delete = $this->Intersettable->getChild($key);
        }
        elseif($table_name=='language')
        {
            $delete =  $this->Languagetable->getChild($key);
        }
        elseif($table_name=='topics')
        {
            $delete =  $this->Topicstable->getChild($key);
        }
        else
        {

        }

        if($delete->remove())
        {
            return back();
        }
    }
    function getAbuses()
    {
        $words=$this->Abusetable->getSnapshot()->getValue();
      
        return view('admin.Abuse_word',compact('words'));
    }
    function saveAbuses(Request $request)
    {
        extract($request->all());
        $arr['word'] = $word;
        $this->Abusetable->set($arr);
        return redirect('Abuses');
    }
}
