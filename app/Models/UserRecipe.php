<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRecipe extends Model
{
    protected $table = "user_recipes";

    protected $fillable = [
        "user_id",
        "recipe_id"
    ];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class, "recipe_id");
    }
}
