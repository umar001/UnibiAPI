<?php

namespace App\Models\Api\firebase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppUserAge extends Model
{
    use HasFactory;
    protected $table = 'app_user_age';
    protected $fillable = [
        'firebase_uid',
        'age',
        'age_date',
    ];
}
