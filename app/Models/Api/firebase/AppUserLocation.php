<?php

namespace App\Models\Api\firebase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppUserLocation extends Model
{
    use HasFactory;
    protected $table = 'app_user_location';
    protected $fillable = [
        'firebase_uid',
        'lat',
        'lon',
    ];
}
