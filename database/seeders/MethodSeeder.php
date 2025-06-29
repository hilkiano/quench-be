<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Method;

class MethodSeeder extends Seeder
{
    protected $methods = ["V60", "POUR_OVER", "FRENCH_PRESS", "AERO_PRESS", "MOKA_POT", "COLD_BREW", "SIPHON", "DRIP", "OTHER"];
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();

        $connection = DB::connection("mysql");
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $connection->table("methods")->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        foreach ($this->methods as $method) {
            Method::create([
                "name" => $method,
            ]);
        }

        Model::reguard();
    }
}
