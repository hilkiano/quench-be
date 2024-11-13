<?php

namespace App\Traits;

use Carbon\Carbon;

trait CreateStringId
{
    public static function bootCreateStringId()
    {
        static::creating(function ($model) {
            do {
                $id = "INVALID";
                $date = Carbon::now();
                $dateSegment = $date->year
                    . str_pad((string) $date->month, 2, "0", STR_PAD_LEFT)
                    . str_pad((string) $date->day, 2, "0", STR_PAD_LEFT);

                $seq = str_pad((string) ($model->count() + 1), 5, "0", STR_PAD_LEFT);
                $id = strtoupper(substr(class_basename($model), 0, 1)) . $seq . $dateSegment;
            } while (static::where('id', $id)->exists());

            $model->id = $id;
        });
    }
}
