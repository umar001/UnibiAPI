<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\InterestsRequest;
// Model
use App\Models\Api\firebase\InterestsModel;
// Repository
use App\Repositories\Api\firebase\InterestsRepository;

class InterestsController extends Controller
{
    //
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $model;
    private $repo;
    public function __construct(InterestsRepository $repo, InterestsModel $model)
    {
        $this->repo = $repo;
        $this->model = $model;
    }
    public function index()
    {
        $returnResponse = $this->model->getList();
        return $returnResponse;
    }
    public function store(InterestsRequest $request)
    {
        $returnResponse = $this->model->create($request->all());
        return $returnResponse;
    }
    public function update(InterestsRequest $request,$id)
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
