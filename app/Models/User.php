<?php

namespace App\Models;

use App\Traits\CreateStringId;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use SoftDeletes, CreateStringId;

    protected $table = "users";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $keyType = "string";

    protected $fillable = [
        'username',
        'email',
        'avatar_url',
        'socialite_data',
        'geolocation',
        'configs'
    ];

    protected function casts(): array
    {
        return [
            'socialite_data' => 'array',
            'geolocation' => 'array',
            'configs' => 'array',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        if ($this->can('use-extended-time')) {
            $expiration = Carbon::now('UTC')->addYears(2)->getTimestamp();
            return ['exp' => $expiration];
        }
        return [];
    }

    public function getGeolocation()
    {
        return json_decode($this->attributes["geolocation"]);
    }
}
