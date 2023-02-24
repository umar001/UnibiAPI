<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\PreferenceRequest;
// Model
use App\Models\Api\firebase\PreferenceModel;
// Repository
use App\Repositories\Api\firebase\PreferenceRepository;

class PreferenceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $model;
    private $preferneceRepo;
    public function __construct(PreferenceRepository $preferneceRepo, PreferenceModel $model)
    {
        $this->preferneceRepo = $preferneceRepo;
        $this->model = $model;
    }
    public function store(PreferenceRequest $request)
    {
        $returnResponse = $this->preferneceRepo->create($request->all());
        return $returnResponse;
    }
    public function update(PreferenceRequest $request,$id)
    {
        $returnResponse = $this->model->updatePref($request->all(),$id);
        return $returnResponse;
    }

    public function destroy($id)
    {
        $returnResponse = $this->model->deleteRef($id);
        return $returnResponse;
    }
}
