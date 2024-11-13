<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait GeneralHelpers
{
    function jsonResponse(
        bool $status = true,
        mixed $data = null,
        string $message = null,
        mixed $trace = null,
        int $code = 200,
        mixed $cookieData = null
    ) {
        $payload = [
            "status"    => $status,
            "data"      => $data,
            "message"   => $message,
            "trace"     => $trace,
            "code"      => $code
        ];

        if ($cookieData) {
            return response()->json($payload, $code)->withCookie($cookieData);
        } else {
            return response()->json($payload, $code);
        }
    }

    function checkModel(string $className): Model|null
    {
        $validatedClassname = "\\App\\Models\\" . explode("+", $className)[0];

        if (class_exists($validatedClassname)) {
            $model = new $validatedClassname();
            if ($model instanceof Model) {
                return $model;
            }
        }

        return null;
    }

    function checkColumn(\Illuminate\Database\Eloquent\Model $model, string $column): bool
    {
        return $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $column);
    }

    function generateRelationsArray(string $relations)
    {
        return explode("&", $relations);
    }
}
