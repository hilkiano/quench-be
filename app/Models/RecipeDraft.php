<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeDraft extends Model
{
    protected $table = "recipe_drafts";
    protected $primaryKey = "id";

    protected $fillable = [
        'data',
        'recipe_id',
        'image_url',
        'created_by',
        'updated_by'
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
