<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Log;
use Storage;

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

    function storeImage(string $disk, $file, $dir): string | null
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file);
        $name = $file->hashName();
        $destination = env("APP_ENV") === "local" ? "development" : "";
        $destination = "{$destination}/{$dir}/{$name}";
        $put = Storage::disk($disk)->put($destination, $image->encodeByMediaType());
        if ($put) {
            return Storage::disk($disk)->url($destination);
        }

        return null;
    }

    function deleteFile(string $disk, $path): bool
    {
        $delete = Storage::disk($disk)->delete($path);

        return $delete;
    }

    function deleteDirectory(string $disk, $path): bool
    {
        $delete = Storage::disk($disk)->deleteDirectory($path);

        return $delete;
    }
}
