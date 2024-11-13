<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeStep extends Model
{
    protected $table = "recipe_steps";

    protected $fillable = [
        "recipe_id",
        "image_url",
        "step",
        "order"
    ];
}
