<?php

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = "session";
    protected $connection = "mysql_backoffice";
}
