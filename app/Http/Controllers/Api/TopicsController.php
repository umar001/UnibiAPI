<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\TopicsRequest;
// Model
use App\Models\Api\firebase\TopicsModel;

class TopicsController extends Controller
{
    //
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $model;
    private $repo;
    public function __construct(TopicsModel $model)
    {
        $this->model = $model;
    }
    public function index()
    {
        $returnResponse = $this->model->getList();
        return $returnResponse;
    }
    public function store(TopicsRequest $request)
    {
        $returnResponse = $this->model->create($request->all());
        return $returnResponse;
    }
    public function update(TopicsRequest $request,$id)
    {
        $returnResponse = $this->model->updateInterest($request->all(),$id);
        return $returnResponse;
    }

    public function destroy($id)
    {
        $returnResponse = $this->model->deleteRef($id);
        return $returnResponse;
    }
}
