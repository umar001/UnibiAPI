<?php

namespace App\Models\Api\firebase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kreait\Firebase\Database;
use Auth;

class PreferenceModel extends Model
{
    use HasFactory;
    /**
     * Create a new Model instance.
     *
     * @return void
     */
    private $database;
    protected $table;
    protected $user;

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->table = $this->database->getReference('/preference');
        $this->user = Auth::guard('app-api')->user();
    }
    public function create(array $var = null)
    {
        $checkNotEmpty = $this->table->getSnapshot()->exists();
        if($checkNotEmpty){
            $getChildKey = $this->table->getChildKeys();
            $key = count($getChildKey);
            $refData = $this->table->getSnapshot()->getValue();
            $CheckKey = array_search($var['name'], array_column($refData, 'name'));
        }else{
            $CheckKey = false;
            $key = 0;
        }
        if(!$CheckKey){
            $this->table = $this->database->getReference('/preference/'.$key);
            $postData = $this->table->set($var);
            // return $this->table->getSnapshot()->getValue();
            return response()->json([
                'status' => 'success',
                'message' => 'User Data Successfully Submited',
                'data' => $this->table->getSnapshot()->getValue(),
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Duplicate Data Entry',
            ],401);
        }

    }

    public function updatePref(array $var = null,$id)
    {
        $getChildKey = $this->table->getChildKeys();
        $refData = $this->table->getSnapshot()->getValue();
        $CheckKey = array_search($var['name'], array_column($refData, 'name'));
        if(!$CheckKey){
            $this->table = $this->database->getReference('/preference/'.$id);
            $postData = $this->table->set($var);
            // return $this->table->getSnapshot()->getValue();
            return response()->json([
                'status' => 'success',
                'message' => 'Preference Data Successfully Submited',
                'data' => $this->table->getSnapshot()->getValue(),
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Duplicate Data Entry',
            ],401);
        }
    }
    public function deleteRef(int $id = null)
    {
        $checkNotEmpty = $this->table->getSnapshot()->exists();
        if($checkNotEmpty){
            $this->table = $this->database->getReference('/preference/'.$id);
            if($this->table->getSnapshot()->exists()){
                $this->table->remove();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Preference Data Successfully Deleted',
                ]);
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'No id Found',
                ],401);
            }
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Empty table',
            ],401);
        }
    }
    // 
   
}
