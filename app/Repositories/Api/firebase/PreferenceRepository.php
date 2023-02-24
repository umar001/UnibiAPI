<?php

namespace App\Repositories\Api\Firebase;

use JasonGuru\LaravelMakeRepository\Repository\BaseRepository;
//use Your Model
use App\Models\Api\firebase\PreferenceModel;

/**
 * Class PreferenceRepository.
 */
class PreferenceRepository extends BaseRepository
{
    /**
     * @return string
     *  Return the model
     */
    public function model()
    {
        return PreferenceModel::class;
    }

    public function create(array $var = null)
    {
        // Logic
        return $this->model->create($var); 
        
    }
}
