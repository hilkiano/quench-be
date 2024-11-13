<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    protected $units = [
        [
            "name" => "gram",
            "abbreviation" => "g"
        ],
        [
            "name" => "mililiter",
            "abbreviation" => "ml"
        ],
        [
            "name" => "kilogram",
            "abbreviation" => "kg"
        ],
        [
            "name" => "liter",
            "abbreviation" => "L"
        ],
        [
            "name" => "centimeter",
            "abbreviation" => "cm"
        ],
        [
            "name" => "teaspoon",
            "abbreviation" => "tsp"
        ],
        [
            "name" => "tablespoon",
            "abbreviation" => "tbsp"
        ],
    ];
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();

        $connection = DB::connection("mysql");
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $connection->table("units")->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        foreach ($this->units as $unit) {
            Unit::create([
                "name" => $unit["name"],
                "abbreviation" => $unit["abbreviation"]
            ]);
        }

        Model::reguard();
    }
}
