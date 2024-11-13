<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();

        $connection = DB::connection("mysql");
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $connection->table("recipes")->truncate();
        $connection->table("recipe_ingredients")->truncate();
        $connection->table("recipe_steps")->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        for ($i = 0; $i < 100; $i++) {
            $recipe = Recipe::create([
                "title" => fake()->catchPhrase(),
                "description" => mt_rand(0, 1) ? fake()->paragraph() : null,
                "approved_at" => mt_rand(0, 1) ? Carbon::now() : null,
            ]);

            $stepsCount = mt_rand(1, 8);
            $ingredientsCount = mt_rand(1, 10);

            for ($j = 1; $j < $stepsCount; $j++) {
                RecipeStep::create([
                    "recipe_id" => $recipe->id,
                    "order" => $j,
                    "step" => fake()->paragraph()
                ]);
            }

            for ($k = 1; $k < $ingredientsCount; $k++) {
                RecipeIngredient::create([
                    "recipe_id" => $recipe->id,
                    "name" => fake()->company(),
                    "quantity" => fake()->randomNumber(3, true),
                    "unit_id" => Unit::inRandomOrder()->first()->id
                ]);
            }
        }

        Model::reguard();
    }
}
