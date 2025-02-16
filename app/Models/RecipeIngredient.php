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
        'created_by',
        'updated_by'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, "unit_id");
    }
}
