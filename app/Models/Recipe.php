<?php

namespace App\Models;

use App\Traits\CreateStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends Model
{
    use SoftDeletes, CreateStringId;

    protected $table = "recipes";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $keyType = "string";

    protected $fillable = [
        'title',
        'description',
        'image_url',
        'method_id',
        'status',
        'reason',
        'youtube_url',
        'configs',
        'created_by',
        'updated_by',
        'original_recipe_id',
        'approved_at',
        'approved_by'
    ];

    protected function casts(): array
    {
        return [
            'configs' => 'array',
        ];
    }

    public function steps()
    {
        return $this->hasMany(RecipeStep::class);
    }

    public function ingredients()
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, "created_by");
    }

    public function meta()
    {
        return $this->hasOne(RecipeMetadata::class, "recipe_id");
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, "recipe_id");
    }

    public function method()
    {
        return $this->belongsTo(Method::class, "method_id");
    }
}
