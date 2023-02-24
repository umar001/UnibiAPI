<?php

namespace App\Models\Api\firebase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppPostLocation extends Model
{
    use HasFactory;
    protected $table = 'app_post_location';
    protected $fillable = [
        'firebase_uid',
        'lat',
        'lon',
    ];
}
