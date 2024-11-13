<?php

namespace App\Models;

use App\Traits\CreateStringId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RichanFongdasen\EloquentBlameable\BlameableTrait;

class Recipe extends Model
{
    use BlameableTrait, SoftDeletes, CreateStringId;

    protected $table = "recipes";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $keyType = "string";

    protected $fillable = [
        'title',
        'description',
        'approved_at',
        'youtube_url',
        'configs'
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
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
}
