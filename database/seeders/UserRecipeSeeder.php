<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\UserRecipe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class UserRecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();

        $connection = DB::connection("mysql");
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $connection->table("user_recipes")->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $recipes = Recipe::get();

        if (count($recipes) > 0) {
            foreach ($recipes as $recipe) {
                UserRecipe::create([
                    "user_id" => $recipe->created_by,
                    "recipe_id" => $recipe->id
                ]);
            }
        }

        Model::reguard();
    }
}
