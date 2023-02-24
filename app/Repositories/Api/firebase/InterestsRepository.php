<?php

namespace App\Repositories\Api\firebase;

use JasonGuru\LaravelMakeRepository\Repository\BaseRepository;
//use Your Model
use App\Models\Api\firebase\InterestsModel;
/**
 * Class InterestsRepository.
 */
class InterestsRepository extends BaseRepository
{
    /**
     * @return string
     *  Return the model
     */
    public function model()
    {
        return InterestsModel::class;
    }

    public function create(array $var = null)
    {
        $return = $this->model->create($var);
        return response()->json([
            'status' => 'success',
            'message' => 'Interest Data Successfully added',
            'data' => $return,
        ]);
    }
}
