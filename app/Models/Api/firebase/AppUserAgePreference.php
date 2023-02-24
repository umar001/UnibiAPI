<?php

namespace App\Models\Api\firebase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppUserAgePreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_age',
        'end_age',
        'firebase_uid',
    ];
}
