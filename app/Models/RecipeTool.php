<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeTool extends Model
{
    protected $table = "recipe_tools";

    protected $fillable = [
        "recipe_id",
        "name",
        "quantity",
        'created_by',
        'updated_by'
    ];
}
