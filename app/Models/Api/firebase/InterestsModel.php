<?php

namespace App\Models\Api\firebase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kreait\Firebase\Database;
use Auth;
use Carbon\Carbon;

class InterestsModel extends Model
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
        $this->table = $this->database->getReference('/interests');
        $this->user = Auth::guard('app-api')->user();
    }
    public function getList()
    {
        $data = $this->table->getSnapshot()->getValue();
        $arr['interest'] = [];
        if($data){
            foreach($data as $val){
                array_push($arr['interest'],$val['name']);
            }
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Data Successfully retrieved',
            'data' => $arr,
        ]);
    }
    public function create(array $var = null)
    {
        $arr = array(
            'name' => $var['name'],
            'created_at' => Carbon::now()->toDateTimeString(),
        );
        $data = $this->table->getChild($var['name'])->set($arr);
        $return = $this->table->getSnapshot()->getValue();
        return response()->json([
            'status' => 'success',
            'message' => 'Interest Data Successfully added',
            'data' => $return,
        ]);
    }

    public function updateInterest(array $var = null,$id)
    {
        $this->table->getChild($id)->remove();
        $arr = array(
            'name' => $var['name'],
            'created_at' => Carbon::now()->toDateTimeString(),
        );
        $data = $this->table->getChild($var['name'])->set($arr);
        $return = $this->table->getSnapshot()->getValue();
        return response()->json([
            'status' => 'success',
            'message' => 'Data Successfully updated',
            'data' => $return,
        ]);
    }
    public function deleteRef($id)
    {
        try{
            $this->table->getChild($id)->remove();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully Deleted',
            ]);
        } catch (\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'No id Found',
            ],401);
        }
    }
    // 
}
