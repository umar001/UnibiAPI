<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\FirebaseException;

class FirebaseController extends Controller
{
    protected $auth;
    public function __construct(FirebaseAuth $auth) {
        $this->auth = $auth;
    }
    //
}

