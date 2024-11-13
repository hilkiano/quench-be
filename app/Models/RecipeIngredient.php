<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeIngredient extends Model
{
    protected $table = "recipe_ingredients";

    protected $fillable = [
        "recipe_id",
        "name",
        "quantity",
        "unit_id",
        "image_url"
    ];
}
