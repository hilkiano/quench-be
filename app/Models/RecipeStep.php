<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeStep extends Model
{
    protected $table = "recipe_steps";

    protected $fillable = [
        "recipe_id",
        "step",
        "order",
        "timer_seconds",
        "video_starts_at",
        "video_stops_at",
        'created_by',
        'updated_by'
    ];
}
