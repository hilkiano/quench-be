<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DestroyedUser extends Model
{
    protected $table = "destroyed_users";

    protected $fillable = [
        "user_id"
    ];
}
