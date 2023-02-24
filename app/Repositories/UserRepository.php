<?php

namespace App\Repositories;

use JasonGuru\LaravelMakeRepository\Repository\BaseRepository;
use App\Models\User;
use Auth;
//use Your Model

/**
 * Class UserRepository.
 */
class UserRepository extends BaseRepository
{
    /**
     * @return string
     *  Return the model
     */
    public function model()
    {
        // return Auth::user();
        // dd(BaseRepository::class);
        return User::class;
    }

    public function getUserdata(Type $var = null)
    {
        return $this->all();
        # code...
    }
}
