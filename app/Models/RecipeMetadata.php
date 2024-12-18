<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeMetadata extends Model
{
    protected $table = "recipe_metadata";

    protected $fillable = [
        "recipe_id",
        "likes",
        "views"
    ];
}
